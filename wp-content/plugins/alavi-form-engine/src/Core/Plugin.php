<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Core;

use BonyadAlavi\FormEngine\Actions\ActionManager;
use BonyadAlavi\FormEngine\Actions\ActionRegistry;
use BonyadAlavi\FormEngine\Actions\ActionExecutionRepository;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenRegistry;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use BonyadAlavi\FormEngine\Actions\Sms\MeliPayamakProvider;
use BonyadAlavi\FormEngine\Actions\Sms\PersianWooCommerceSmsProvider;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderRegistry;
use BonyadAlavi\FormEngine\Actions\Sms\SmsSettings;
use BonyadAlavi\FormEngine\Actions\Pdf\PdfGenerator;
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
use BonyadAlavi\FormEngine\Duplicate\DuplicateFingerprint;
use BonyadAlavi\FormEngine\Duplicate\DuplicatePolicy;
use BonyadAlavi\FormEngine\Duplicate\DuplicateRepository;
use BonyadAlavi\FormEngine\Elementor\Integration as ElementorIntegration;
use BonyadAlavi\FormEngine\Events\EventDispatcher;
use BonyadAlavi\FormEngine\Events\EventRegistry;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Form\Renderer;
use BonyadAlavi\FormEngine\Form\PreviewRenderer;
use BonyadAlavi\FormEngine\Form\Validator;
use BonyadAlavi\FormEngine\Localization\LocaleDateService;
use BonyadAlavi\FormEngine\InputMask\InputMaskRegistry;
use BonyadAlavi\FormEngine\Repository\FormRepository;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use BonyadAlavi\FormEngine\Rest\ApiController;
use BonyadAlavi\FormEngine\Security\SecurityManager;
use BonyadAlavi\FormEngine\Submission\FileUploader;
use BonyadAlavi\FormEngine\Submission\SubmissionService;
use BonyadAlavi\FormEngine\Style\StyleIsolationManager;
use BonyadAlavi\FormEngine\Template\TemplateRegistry;
use BonyadAlavi\FormEngine\Template\TemplateResolver;
use BonyadAlavi\FormEngine\Template\TemplateOverrideNormalizer;
use BonyadAlavi\FormEngine\Validation\ValidatorRegistry;
use BonyadAlavi\FormEngine\Export\PdfExporter;
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
        do_action('afe_register_forms', $registry);

        $formRepo = new FormRepository();
        $submissionRepo = new SubmissionRepository();
        $duplicateRepo = new DuplicateRepository();
        $duplicatePolicy = new DuplicatePolicy(new DuplicateFingerprint(), $duplicateRepo);
        $sources = new DataSourceManager();
        $security = new SecurityManager();
        $validatorRegistry = new ValidatorRegistry();
        do_action('afe_register_validator_definitions', $validatorRegistry);
        $inputMaskRegistry = new InputMaskRegistry();
        do_action('afe_register_input_mask_definitions', $inputMaskRegistry);
        $validator = new Validator($validatorRegistry);
        $fileUploader = new FileUploader();
        $tokenRegistry = new TokenRegistry();
        $tokenResolver = new TokenResolver();
        $secretStore = new SecretStore();
        $globalSettings = (array)get_option('afe_settings', []);
        $smsSettings = SmsSettings::section($globalSettings);
        $smsSettings['password'] = $secretStore->decrypt((string)($smsSettings['password'] ?? ''));
        $smsSettings['api_key'] = $secretStore->decrypt((string)($smsSettings['api_key'] ?? ''));
        $defaultSmsProvider = SmsSettings::defaultProvider($globalSettings);
        $smsProviders = new SmsProviderRegistry($defaultSmsProvider, SmsSettings::enabled($globalSettings));
        $smsProviders->setEnabledResolver(static function(): bool {
            return SmsSettings::enabled((array)get_option('afe_settings', []));
        });
        $meliPayamak = new MeliPayamakProvider($smsSettings);
        $smsProviders->register(
            'melipayamak',
            'ملی پیامک — تنظیمات Alavi Form Engine',
            $meliPayamak,
            ['free','pattern'],
            true,
            static function() use ($smsSettings): array {
                $authMode = sanitize_key((string)($smsSettings['auth_mode'] ?? 'legacy'));
                $configured = $authMode === 'api_key'
                    ? trim((string)($smsSettings['api_key'] ?? '')) !== ''
                    : trim((string)($smsSettings['username'] ?? '')) !== '' && (string)($smsSettings['password'] ?? '') !== '';
                return [
                    'configured'=>$configured,
                    'auth_mode'=>$authMode,
                    'sender'=>trim((string)($smsSettings['sender'] ?? '')),
                ];
            }
        );
        $pwsmsProvider = new PersianWooCommerceSmsProvider();
        $smsProviders->register(
            PersianWooCommerceSmsProvider::KEY,
            'Persian WooCommerce SMS',
            $pwsmsProvider,
            static fn(): array => $pwsmsProvider->supportedModes(),
            static fn(): bool => $pwsmsProvider->isAvailable(),
            static fn(): array => $pwsmsProvider->status()
        );
        do_action('afe_register_sms_providers', $smsProviders);
        $actionRegistry = new ActionRegistry();
        $actionRegistry->registerCore($tokenResolver);
        $pdfGenerator = new PdfGenerator($submissionRepo, new PdfExporter());
        $actionRegistry->registerExtended($tokenResolver, $submissionRepo, $pdfGenerator);
        $actionRegistry->registerSms($tokenResolver, $smsProviders);
        $actionExecutions = new ActionExecutionRepository();
        $actions = new ActionManager($actionRegistry, $actionExecutions);
        $eventRegistry = new EventRegistry();
        $events = new EventDispatcher();
        $dedicated = new DedicatedStorage();
        $styleIsolation = new StyleIsolationManager();
        $formAccess = new FormAccess();
        $dates = new LocaleDateService();
        $templateRegistry = new TemplateRegistry();
        do_action('afe_register_templates', $templateRegistry);
        $templates = new TemplateResolver($templateRegistry);

        do_action('afe_register_data_sources', $sources);
        do_action('afe_register_action_definitions', $actionRegistry);
        do_action('afe_register_actions', $actions);
        do_action('afe_register_event_definitions', $eventRegistry);
        do_action('afe_register_events', $events);

        $service = new SubmissionService(
            $registry, $formRepo, $submissionRepo, $security, $validator,
            $fileUploader, $actions, $events, $dedicated, $formAccess,
            $duplicatePolicy, $duplicateRepo, $actionExecutions, $inputMaskRegistry
        );
        $previewPresenter = new FormDataPresenter($sources, $dates);
        $previewRenderer = new PreviewRenderer($previewPresenter, $submissionRepo, $templates);
        $renderer = new Renderer($registry, $formRepo, $submissionRepo, $service, $sources, $security, $styleIsolation, $dates, $previewRenderer, $templates);

        $formRepo->syncRegistry($registry);
        (new TemplateOverrideNormalizer($formRepo,$templates))->run($registry);
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
        $this->container->set(DuplicateRepository::class,$duplicateRepo);
        $this->container->set(DuplicatePolicy::class,$duplicatePolicy);
        $this->container->set(DataSourceManager::class,$sources);
        $this->container->set(SecurityManager::class,$security);
        $this->container->set(TokenRegistry::class,$tokenRegistry);
        $this->container->set(ValidatorRegistry::class,$validatorRegistry);
        $this->container->set(InputMaskRegistry::class,$inputMaskRegistry);
        $this->container->set(TokenResolver::class,$tokenResolver);
        $this->container->set(SecretStore::class,$secretStore);
        $this->container->set(SmsProviderRegistry::class,$smsProviders);
        $this->container->set(ActionRegistry::class,$actionRegistry);
        $this->container->set(ActionExecutionRepository::class,$actionExecutions);
        $this->container->set(ActionManager::class,$actions);
        $this->container->set(EventRegistry::class,$eventRegistry);
        $this->container->set(EventDispatcher::class,$events);
        $this->container->set(SubmissionService::class,$service);
        $this->container->set(Renderer::class,$renderer);
        $this->container->set(StyleIsolationManager::class,$styleIsolation);
        $this->container->set(FormAccess::class,$formAccess);
        $this->container->set(LocaleDateService::class,$dates);
        $this->container->set(PreviewRenderer::class,$previewRenderer);
        $this->container->set(TemplateRegistry::class,$templateRegistry);
        $this->container->set(TemplateResolver::class,$templates);

        $this->registerAssets();
        add_shortcode('alavi_form', [$renderer,'shortcode']);

        add_action('wp_ajax_afe_submit', [$this,'ajaxSubmit']);
        add_action('wp_ajax_nopriv_afe_submit', [$this,'ajaxSubmit']);
        add_action('wp_ajax_afe_check_duplicate', [$this,'ajaxCheckDuplicate']);
        add_action('wp_ajax_nopriv_afe_check_duplicate', [$this,'ajaxCheckDuplicate']);
        add_action('wp_ajax_afe_request_edit', [$this,'ajaxRequestEdit']);
        add_action('wp_ajax_nopriv_afe_request_edit', [$this,'ajaxRequestEdit']);
        add_action('wp_ajax_afe_refresh_captcha', [$this,'ajaxRefreshCaptcha']);
        add_action('wp_ajax_nopriv_afe_refresh_captcha', [$this,'ajaxRefreshCaptcha']);
        add_action('wp_ajax_afe_geo_options', [$this,'ajaxGeoOptions']);
        add_action('wp_ajax_nopriv_afe_geo_options', [$this,'ajaxGeoOptions']);
        add_action('wp_ajax_afe_geo_import_chunk', [$this,'ajaxGeoImportChunk']);

        if (is_admin()) {
            $formsPage = new FormsPage($registry,$formRepo,$service,$formAccess,$templates,$eventRegistry,$actionRegistry,$tokenRegistry,$duplicatePolicy,$validatorRegistry,$inputMaskRegistry);
            $submissionsPage = new SubmissionsPage($registry,$submissionRepo,$service,$sources,$formAccess,$dates,$actionExecutions,$actionRegistry,$eventRegistry);
            $reportsPage = new ReportsPage($registry,$submissionRepo,$formAccess,$dates);
            $databasePage = new DatabasePage(new Migrator(),$registry,$dedicated,$dates);
            $settingsPage = new SettingsPage($registry,$formAccess,$secretStore,$smsProviders);
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
            // Third-party browser assets are deliberately self-hosted. The pinned
            // JalaliDatePicker dist files are bundled under assets/vendor so public
            // form rendering never depends on a runtime CDN request.
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
            if (str_contains($hook,'alavi-form-engine-forms')) wp_enqueue_media();
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

    public function ajaxCheckDuplicate(): void
    {
        /** @var SubmissionService $service */
        $service = $this->container->get(SubmissionService::class);
        try {
            nocache_headers();
            $result = $service->checkDuplicateHttp();
            if (is_wp_error($result)) {
                wp_send_json_error([
                    'message'=>$result->get_error_message(),
                    'code'=>$result->get_error_code(),
                ], 422);
            }
            wp_send_json_success($result);
        } catch (Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) error_log('[AFE duplicate preflight] '.$e->getMessage());
            wp_send_json_error(['message'=>'بررسی تکراری بودن اطلاعات انجام نشد.'],500);
        }
    }

    public function ajaxSubmit(): void
    {
        /** @var SubmissionService $service */
        $service = $this->container->get(SubmissionService::class);
        try {
            $result = $service->handleHttp();
            if (is_wp_error($result)) {
                $code=$result->get_error_code();
                $errorData=(array)$result->get_error_data();
                $payload=[
                    'message'=>$result->get_error_message(),
                    'code'=>$code,
                    'errors'=>$code==='validation'?$errorData:[],
                ];
                if($code==='duplicate'){
                    $payload['duplicate']=true;
                    if(!empty($errorData['edit_url'])) $payload['edit_url']=esc_url_raw((string)$errorData['edit_url']);
                }
                wp_send_json_error($payload, 422);
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
