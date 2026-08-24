<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Core;

use BonyadAlavi\FormEngine\Actions\ActionManager;
use BonyadAlavi\FormEngine\Admin\DatabasePage;
use BonyadAlavi\FormEngine\Admin\FormsPage;
use BonyadAlavi\FormEngine\Admin\Menu;
use BonyadAlavi\FormEngine\Admin\ReportsPage;
use BonyadAlavi\FormEngine\Admin\SettingsPage;
use BonyadAlavi\FormEngine\Admin\SubmissionsPage;
use BonyadAlavi\FormEngine\Admin\FormDataPresenter;
use BonyadAlavi\FormEngine\DataSource\DataSourceManager;
use BonyadAlavi\FormEngine\Database\DedicatedStorage;
use BonyadAlavi\FormEngine\Database\GeographyImporter;
use BonyadAlavi\FormEngine\Database\Migrator;
use BonyadAlavi\FormEngine\Elementor\Integration as ElementorIntegration;
use BonyadAlavi\FormEngine\Events\EventDispatcher;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Form\Renderer;
use BonyadAlavi\FormEngine\Form\PreviewRenderer;
use BonyadAlavi\FormEngine\Form\Validator;
use BonyadAlavi\FormEngine\Forms\JihadiGroupRegistrationForm;
use BonyadAlavi\FormEngine\Localization\LocaleDateService;
use BonyadAlavi\FormEngine\Repository\FormRepository;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use BonyadAlavi\FormEngine\Rest\ApiController;
use BonyadAlavi\FormEngine\Security\SecurityManager;
use BonyadAlavi\FormEngine\Submission\FileUploader;
use BonyadAlavi\FormEngine\Submission\SubmissionService;
use BonyadAlavi\FormEngine\Style\StyleIsolationManager;
use Throwable;

final class Plugin
{
    private static ?self $instance = null;
    private bool $booted = false;
    private Container $container;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public static function activate(): void
    {
        (new Migrator())->migrate();
        Capabilities::install();
    }

    public static function deactivate(): void {}

    public function boot(): void
    {
        if ($this->booted) return;
        $this->booted = true;

        load_plugin_textdomain('alavi-form-engine', false, dirname(plugin_basename(AFE_FILE)) . '/languages');

        $migrator = new Migrator();
        $health = $migrator->health();
        if (version_compare((string)get_option('afe_db_version','0'), AFE_DB_VERSION, '<') || in_array(false,$health,true)) {
            $migrator->migrate();
        }

        $this->container = new Container();

        $registry = new FormRegistry();
        $registry->register((new JihadiGroupRegistrationForm())->build());
        do_action('afe_register_forms', $registry);

        $formRepo = new FormRepository();
        $submissionRepo = new SubmissionRepository();
        $sources = new DataSourceManager();
        $security = new SecurityManager();
        $validator = new Validator();
        $fileUploader = new FileUploader();
        $actions = new ActionManager();
        $events = new EventDispatcher();
        $dedicated = new DedicatedStorage();
        $styleIsolation = new StyleIsolationManager();
        $formAccess = new FormAccess();
        $dates = new LocaleDateService();

        do_action('afe_register_data_sources', $sources);
        do_action('afe_register_actions', $actions);
        do_action('afe_register_events', $events);

        $service = new SubmissionService(
            $registry, $formRepo, $submissionRepo, $security, $validator,
            $fileUploader, $actions, $events, $dedicated, $formAccess
        );
        $previewPresenter = new FormDataPresenter($sources, $dates);
        $previewRenderer = new PreviewRenderer($previewPresenter, $submissionRepo);
        $renderer = new Renderer($registry, $formRepo, $submissionRepo, $service, $sources, $security, $styleIsolation, $dates, $previewRenderer);

        $formRepo->syncRegistry($registry);
        $formAccess->sync($registry);
        Capabilities::syncAccess();
        foreach ($registry->all() as $form) {
            $def = $service->resolvedForm($form->slug());
            if (($def['settings']['storage'] ?? $def['storage'] ?? 'shared') === 'dedicated') {
                $dedicated->ensure($form->slug());
            }
        }
        if (!get_option('afe_lock_policy_backfill_1_0_20', false)) {
            foreach ($registry->all() as $form) {
                $def=$service->resolvedForm($form->slug());
                if (!empty($def['settings']['lock_after_submit'])) $submissionRepo->backfillLocksForForm($form->slug());
            }
            update_option('afe_lock_policy_backfill_1_0_20',1,false);
        }

        $this->container->set(FormRegistry::class,$registry);
        $this->container->set(FormRepository::class,$formRepo);
        $this->container->set(SubmissionRepository::class,$submissionRepo);
        $this->container->set(DataSourceManager::class,$sources);
        $this->container->set(SecurityManager::class,$security);
        $this->container->set(ActionManager::class,$actions);
        $this->container->set(EventDispatcher::class,$events);
        $this->container->set(SubmissionService::class,$service);
        $this->container->set(Renderer::class,$renderer);
        $this->container->set(StyleIsolationManager::class,$styleIsolation);
        $this->container->set(FormAccess::class,$formAccess);
        $this->container->set(LocaleDateService::class,$dates);
        $this->container->set(PreviewRenderer::class,$previewRenderer);

        $this->registerAssets();
        add_shortcode('alavi_form', [$renderer,'shortcode']);

        add_action('wp_ajax_afe_submit', [$this,'ajaxSubmit']);
        add_action('wp_ajax_nopriv_afe_submit', [$this,'ajaxSubmit']);
        add_action('wp_ajax_afe_request_edit', [$this,'ajaxRequestEdit']);
        add_action('wp_ajax_nopriv_afe_request_edit', [$this,'ajaxRequestEdit']);
        add_action('wp_ajax_afe_refresh_captcha', [$this,'ajaxRefreshCaptcha']);
        add_action('wp_ajax_nopriv_afe_refresh_captcha', [$this,'ajaxRefreshCaptcha']);
        add_action('wp_ajax_afe_geo_options', [$this,'ajaxGeoOptions']);
        add_action('wp_ajax_nopriv_afe_geo_options', [$this,'ajaxGeoOptions']);
        add_action('wp_ajax_afe_geo_import_chunk', [$this,'ajaxGeoImportChunk']);

        if (is_admin()) {
            $formsPage = new FormsPage($registry,$formRepo,$service,$formAccess);
            $submissionsPage = new SubmissionsPage($registry,$submissionRepo,$service,$sources,$formAccess,$dates);
            $reportsPage = new ReportsPage($registry,$submissionRepo,$formAccess,$dates);
            $databasePage = new DatabasePage(new Migrator(),$registry,$dedicated,$dates);
            $settingsPage = new SettingsPage($registry,$formAccess);
            (new Menu($formsPage,$submissionsPage,$reportsPage,$databasePage,$settingsPage))->register();
        }

        add_action('rest_api_init', static function() use ($registry,$submissionRepo,$service,$formAccess): void {
            $settings = get_option('afe_settings',[]);
            if (!empty($settings['api_enabled'])) {
                (new ApiController($registry,$submissionRepo,$service,$formAccess))->registerRoutes();
            }
        });

        (new ElementorIntegration($registry,$renderer))->boot();
        do_action('afe_booted', $this);
    }

    private function registerAssets(): void
    {
        add_action('wp_enqueue_scripts', static function(): void {
            // Third-party browser assets are deliberately self-hosted. The package
            // itself does not bundle JalaliDatePicker; site owners place the two
            // official dist files in assets/vendor/jalalidatepicker/.
            $jdpBase = AFE_URL.'assets/vendor/jalalidatepicker/';
            wp_register_style('afe-jalali-datepicker', $jdpBase.'jalalidatepicker.min.css', [], '1.0.0');
            wp_register_script('afe-jalali-datepicker', $jdpBase.'jalalidatepicker.min.js', [], '1.0.0', true);
            wp_register_style('afe-frontend', AFE_URL.'assets/css/frontend.css', [], AFE_VERSION);
            wp_register_script('afe-frontend', AFE_URL.'assets/js/frontend.js', [], AFE_VERSION, true);
            wp_localize_script('afe-frontend','afeFrontend',[
                'ajaxUrl'=>admin_url('admin-ajax.php'),
                'geoNonce'=>wp_create_nonce('afe_geo'),
            ]);
        });

        add_action('admin_enqueue_scripts', static function(string $hook): void {
            if (!str_contains($hook,'alavi-form-engine')) return;
            $dates=new LocaleDateService();
            $deps=[];
            if($dates->isJalali()){
                $jdpBase = AFE_URL.'assets/vendor/jalalidatepicker/';
                wp_enqueue_style('afe-admin-jalali-datepicker',$jdpBase.'jalalidatepicker.min.css',[],'1.0.0');
                wp_enqueue_script('afe-admin-jalali-datepicker',$jdpBase.'jalalidatepicker.min.js',[],'1.0.0',true);
                $deps[]='afe-admin-jalali-datepicker';
            }
            wp_enqueue_style('afe-admin', AFE_URL.'assets/css/admin.css', [], AFE_VERSION);
            wp_enqueue_script('afe-admin', AFE_URL.'assets/js/admin.js', $deps, AFE_VERSION, true);
            wp_localize_script('afe-admin', 'afeAdmin', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'geoChunkNonce' => wp_create_nonce('afe_geo_chunk'),
                'geoChunkSize' => 32768,
                'locale' => $dates->locale(),
                'calendar' => $dates->calendar(),
                'isJalali' => $dates->isJalali(),
            ]);
        });

        add_action('admin_notices', static function(): void {
            if (!current_user_can('manage_options')) return;
            $js = AFE_PATH.'assets/vendor/jalalidatepicker/jalalidatepicker.min.js';
            $css = AFE_PATH.'assets/vendor/jalalidatepicker/jalalidatepicker.min.css';
            if (is_readable($js) && is_readable($css)) return;
            echo '<div class="notice notice-warning"><p><strong>Alavi Form Engine:</strong> فایل‌های لوکال JalaliDatePicker پیدا نشدند. فایل‌های <code>jalalidatepicker.min.js</code> و <code>jalalidatepicker.min.css</code> را داخل <code>assets/vendor/jalalidatepicker/</code> قرار دهید.</p></div>';
        });
    }

    public function ajaxSubmit(): void
    {
        /** @var SubmissionService $service */
        $service = $this->container->get(SubmissionService::class);
        try {
            $result = $service->handleHttp();
            if (is_wp_error($result)) {
                wp_send_json_error([
                    'message'=>$result->get_error_message(),
                    'errors'=>(array)$result->get_error_data(),
                    'code'=>$result->get_error_code(),
                ], 422);
            }
            wp_send_json_success($result);
        } catch (Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) error_log('[AFE] '.$e->getMessage());
            wp_send_json_error(['message'=>'خطای داخلی هنگام پردازش فرم رخ داد.'],500);
        }
    }


    public function ajaxRequestEdit(): void
    {
        /** @var SubmissionService $service */
        $service = $this->container->get(SubmissionService::class);
        try {
            $result = $service->requestEditHttp();
            if (is_wp_error($result)) {
                wp_send_json_error([
                    'message'=>$result->get_error_message(),
                    'code'=>$result->get_error_code(),
                ], 422);
            }
            wp_send_json_success($result);
        } catch (Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) error_log('[AFE edit request] '.$e->getMessage());
            wp_send_json_error(['message'=>'خطای داخلی هنگام ثبت درخواست ویرایش رخ داد.'],500);
        }
    }

    public function ajaxRefreshCaptcha(): void
    {
        /** @var SecurityManager $security */
        $security = $this->container->get(SecurityManager::class);
        try {
            nocache_headers();
            wp_send_json_success($security->newCustomCaptchaChallenge());
        } catch (Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) error_log('[AFE captcha refresh] '.$e->getMessage());
            wp_send_json_error(['message'=>'تولید کپچای جدید ناموفق بود.'],500);
        }
    }

    public function ajaxGeoImportChunk(): void
    {
        check_ajax_referer('afe_geo_chunk', 'nonce');
        if (!current_user_can(Capabilities::MANAGE_DATABASE)) {
            wp_send_json_error(['message' => 'دسترسی کافی برای Import دیتاست ندارید.'], 403);
        }

        $dataset = sanitize_key(wp_unslash($_POST['dataset'] ?? ''));
        $uploadId = sanitize_text_field(wp_unslash($_POST['upload_id'] ?? ''));
        $chunkIndex = (int) ($_POST['chunk_index'] ?? -1);
        $totalChunks = (int) ($_POST['total_chunks'] ?? 0);
        $totalSize = (int) ($_POST['total_size'] ?? 0);
        $file = $_FILES['chunk'] ?? null;

        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => 'دریافت chunk فایل روی سرور ناموفق بود.'], 422);
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            wp_send_json_error(['message' => 'فایل موقت chunk معتبر نیست.'], 422);
        }

        $result = (new GeographyImporter())->importChunk(
            $dataset,
            $uploadId,
            $chunkIndex,
            $totalChunks,
            $tmp,
            $totalSize
        );

        if (is_wp_error($result)) {
            update_option('afe_geo_last_error', $result->get_error_message(), false);
            wp_send_json_error([
                'message' => $result->get_error_message(),
                'code' => $result->get_error_code(),
            ], 422);
        }

        wp_send_json_success($result);
    }

    public function ajaxGeoOptions(): void
    {
        // County/district names are public reference data and this endpoint is
        // read-only. Do not require a WordPress nonce here: public/Elementor pages
        // are commonly page-cached and an expired cached nonce would silently break
        // the province -> county -> district cascade.
        $level = sanitize_key(wp_unslash($_POST['level'] ?? ''));
        // Since 1.0.8 the renderer sends numeric IDs. Keep accepting a textual
        // parent too so pages cached/rendered by older versions can recover without
        // forcing a hard refresh before the first dependent request.
        $parent = sanitize_text_field(wp_unslash($_POST['parent'] ?? ''));
        if (!in_array($level,['county','district'],true) || $parent === '') {
            wp_send_json_error(['message'=>'پارامترهای جغرافیایی معتبر نیستند.'], 400);
        }

        /** @var DataSourceManager $sources */
        $sources = $this->container->get(DataSourceManager::class);
        $source=['type'=>'geo','level'=>$level,'parent'=>'parent'];
        $options=$sources->resolve($source,['parent'=>$parent]);

        nocache_headers();
        wp_send_json_success([
            'options'=>$options,
            'level'=>$level,
            'parent'=>$parent,
            'count'=>count($options),
        ]);
    }

    public function container(): Container
    {
        return $this->container;
    }
}
