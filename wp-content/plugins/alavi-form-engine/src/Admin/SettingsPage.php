<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Admin;

use BonyadAlavi\FormEngine\Core\Capabilities;
use BonyadAlavi\FormEngine\Core\FormAccess;
use BonyadAlavi\FormEngine\Core\SecretStore;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Style\StyleIsolationManager;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderRegistry;
use BonyadAlavi\FormEngine\Actions\Sms\SmsSettings;
use BonyadAlavi\FormEngine\Actions\Sms\SmsMessage;
use BonyadAlavi\FormEngine\Security\SmsDraftRecovery;

final class SettingsPage
{
    public function __construct(
        private readonly FormRegistry $registry,
        private readonly FormAccess $formAccess,
        private readonly SecretStore $secrets,
        private readonly ?SmsProviderRegistry $smsProviders = null
    ) {}

    public function render(): void
    {
        if(!current_user_can(Capabilities::MANAGE_SETTINGS)) wp_die('دسترسی کافی ندارید.');
        if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['afe_save_settings'])) $this->save();

        wp_enqueue_script('afe-admin-sms-test', AFE_URL.'assets/js/admin-sms-test.js', [], AFE_VERSION, true);
        wp_add_inline_script('afe-admin-sms-test', 'window.afeSmsTest='.wp_json_encode([
            'url'=>admin_url('admin-ajax.php'), 'nonce'=>wp_create_nonce('afe_test_sms'),
        ]).';', 'before');
        $settings=get_option('afe_settings',[]);
        $roles=wp_roles()->roles;
        echo '<div class="wrap afe-admin-wrap"><h1>تنظیمات و مدیریت دسترسی</h1>';
        if(!empty($_GET['updated'])) echo '<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div>';
        echo '<form method="post">'; wp_nonce_field('afe_save_settings','afe_settings_nonce'); echo '<input type="hidden" name="afe_save_settings" value="1">';

        echo '<div class="afe-admin-card"><h2>API و کپچا</h2><div class="afe-admin-grid">';
        echo '<label><input type="checkbox" name="api_enabled" value="1" '.checked(!empty($settings['api_enabled']),true,false).'> REST API مدیریت فعال باشد</label>';
        echo '<label><input type="checkbox" name="api_public_forms" value="1" '.checked(!empty($settings['api_public_forms']),true,false).'> Schema فرم‌ها بدون ورود قابل خواندن باشد</label>';
        echo '<label>Google reCAPTCHA Site Key<input class="regular-text" name="recaptcha_site_key" value="'.esc_attr((string)($settings['recaptcha_site_key']??'')).'" disabled><span class="description">در حالت Local-only بارگذاری اسکریپت خارجی گوگل انجام نمی‌شود و provider قدیمی Google به کپچای داخلی AFE تبدیل می‌شود.</span></label>';
        echo '<label>Google reCAPTCHA Secret Key<input class="regular-text" type="password" autocomplete="new-password" value="" disabled><span class="description">برای جلوگیری از وابستگی مرورگر به سرویس خارجی استفاده نمی‌شود.</span></label>';
        echo '<label class="afe-span-2"><input type="checkbox" name="delete_data_on_uninstall" value="1" '.checked(!empty($settings['delete_data_on_uninstall']),true,false).'> هنگام Uninstall تمام داده‌ها و جداول افزونه حذف شوند <strong>(غیرقابل بازگشت)</strong></label>';
        echo '</div></div>';

        $sms=SmsSettings::section($settings);
        $smsEnabled=SmsSettings::enabled($settings);
        $smsAuth=in_array((string)($sms['auth_mode']??'legacy'),['legacy','api_key'],true)?(string)$sms['auth_mode']:'legacy';
        $hasSmsPassword=(string)($sms['password']??'')!=='';
        $hasSmsApiKey=(string)($sms['api_key']??'')!=='';
        $farapayamak=is_array($sms['farapayamak']??null)?(array)$sms['farapayamak']:[];
        $hasFarapayamakKey=(string)($farapayamak['api_key']??'')!=='';
        $smsDefault=sanitize_key((string)($sms['default_provider']??$sms['provider']??'melipayamak'));
        $providerOptions=$this->smsProviders?->providerOptions()??[
            'melipayamak'=>'ملی پیامک — تنظیمات Alavi Form Engine',
            'farapayamak'=>'فراپیامک — REST JSON',
            'persian_woocommerce_sms'=>'Persian WooCommerce SMS',
        ];
        if(!isset($providerOptions[$smsDefault])) $smsDefault='melipayamak';
        $pwsmsStatus=$this->smsProviders?->status('persian_woocommerce_sms')??['available'=>false,'modes'=>[],'gateway'=>[]];
        $pwsmsGateway=is_array($pwsmsStatus['gateway']??null)?(array)$pwsmsStatus['gateway']:[];
        $pwsmsGatewayName=trim((string)($pwsmsGateway['name']??''));
        $pwsmsModes=array_map('sanitize_key',(array)($pwsmsStatus['modes']??[]));

        echo '<div class="afe-admin-card"><h2>پیامک</h2><div class="afe-admin-grid">';
        echo '<label class="afe-span-2"><input type="checkbox" name="sms_enabled" value="1" '.checked($smsEnabled,true,false).'> سرویس پیامک برای Actionهای فرم فعال باشد</label>';
        echo '<label class="afe-span-2">Provider پیش‌فرض<select name="sms_default_provider">';
        foreach($providerOptions as $providerKey=>$providerLabel){
            $unavailable=$this->smsProviders!==null&&!$this->smsProviders->isAvailable((string)$providerKey);
            $disabled=$unavailable&&$smsDefault!==(string)$providerKey?' disabled':'';
            echo '<option value="'.esc_attr((string)$providerKey).'" '.selected($smsDefault,(string)$providerKey,false).$disabled.'>'.esc_html((string)$providerLabel).'</option>';
        }
        echo '</select><span class="description">هر SMS Action می‌تواند این Provider را Override کند. با انتخاب Persian WooCommerce SMS هیچ Credentialای داخل AFE کپی نمی‌شود و Gateway فعال همان افزونه استفاده خواهد شد.</span></label>';

        if(!empty($pwsmsStatus['available'])){
            $modeLabels=[];
            if(in_array('free',$pwsmsModes,true)) $modeLabels[]='ارسال آزاد';
            if(in_array('pattern',$pwsmsModes,true)) $modeLabels[]='Pattern';
            echo '<div class="afe-span-2 afe-admin-callout"><strong>Persian WooCommerce SMS:</strong> فعال است';
            if($pwsmsGatewayName!=='') echo ' — درگاه فعال: <strong>'.esc_html($pwsmsGatewayName).'</strong>';
            if($modeLabels!==[]) echo ' — روش‌های قابل استفاده در AFE: '.esc_html(implode('، ',$modeLabels));
            else echo ' — درگاه فعال قابل تشخیص/استفاده نیست.';
            echo '<br><span class="description">تنظیمات درگاه، نام کاربری/API Key و شماره فرستنده مستقیماً از Persian WooCommerce SMS خوانده می‌شود.</span></div>';
        }else{
            echo '<div class="afe-span-2 afe-admin-callout"><strong>Persian WooCommerce SMS:</strong> افزونه فعال یا API عمومی آن در دسترس نیست. پس از فعال‌سازی، بدون ورود مجدد Credential در AFE قابل انتخاب خواهد بود.</div>';
        }

        echo '<div class="afe-span-2"><details '.($smsDefault==='melipayamak'?'open':'').'><summary><strong>تنظیمات Provider داخلی — ملی پیامک</strong></summary><div class="afe-admin-grid" style="margin-top:12px">';
        echo '<label>روش اتصال<select name="sms_auth_mode"><option value="legacy" '.selected($smsAuth,'legacy',false).'>نام کاربری / رمز عبور (REST قدیمی)</option><option value="api_key" '.selected($smsAuth,'api_key',false).'>API Key / Token کنسول جدید</option></select></label>';
        echo '<label>شماره فرستنده پیش‌فرض<input class="regular-text" name="sms_sender" value="'.esc_attr((string)($sms['sender']??'')).'" inputmode="numeric"><span class="description">فقط برای Provider داخلی AFE استفاده می‌شود.</span></label>';
        echo '<label>نام کاربری ملی پیامک<input class="regular-text" name="sms_username" value="'.esc_attr((string)($sms['username']??'')).'" autocomplete="off"></label>';
        echo '<label>رمز عبور ملی پیامک<input class="regular-text" type="password" name="sms_password" value="" autocomplete="new-password" placeholder="'.esc_attr($hasSmsPassword?'ذخیره شده — برای تغییر وارد کنید':'').'"><span class="description">خالی بگذارید تا مقدار ذخیره‌شده تغییر نکند.</span></label>';
        echo '<label class="afe-span-2">API Key / Token<input class="regular-text" type="password" name="sms_api_key" value="" autocomplete="new-password" placeholder="'.esc_attr($hasSmsApiKey?'ذخیره شده — برای تغییر وارد کنید':'').'"><span class="description">برای روش کنسول جدید؛ فقط Provider داخلی.</span></label>';
        if(!$this->secrets->available()) echo '<div class="afe-span-2 afe-admin-callout"><strong>هشدار:</strong> OpenSSL روی سرور در دسترس نیست؛ Credential جدید Provider داخلی AFE قابل ذخیره امن نیست. این محدودیت روی Persian WooCommerce SMS اثری ندارد.</div>';
        echo '<div class="afe-span-2 afe-admin-callout"><strong>امنیت:</strong> Credentialهای Provider داخلی با WordPress salts رمز می‌شوند. Persian WooCommerce SMS Credentialهای خودش را در اختیار AFE قرار نمی‌دهد و AFE آن‌ها را ذخیره نمی‌کند.</div>';
        echo '</div></details></div>';
        echo '<div class="afe-span-2"><details '.($smsDefault==='farapayamak'?'open':'').'><summary><strong>تنظیمات Provider — فراپیامک (REST JSON)</strong></summary><div class="afe-admin-grid" style="margin-top:12px">';
        echo '<label>نام کاربری فراپیامک<input class="regular-text" name="sms_farapayamak_username" value="'.esc_attr((string)($farapayamak['username']??'')).'" autocomplete="off"></label>';
        echo '<label>شماره فرستنده پیش‌فرض<input class="regular-text" name="sms_farapayamak_sender" value="'.esc_attr((string)($farapayamak['sender']??'')).'" autocomplete="off" inputmode="numeric"></label>';
        echo '<label class="afe-span-2">کلید API فراپیامک<input class="regular-text" type="password" name="sms_farapayamak_api_key" value="" autocomplete="new-password" placeholder="'.esc_attr($hasFarapayamakKey?'ذخیره شده — برای تغییر وارد کنید':'').'" ><span class="description">کلید API در پارامتر password JSON ارسال می‌شود؛ برای حفظ مقدار ذخیره‌شده این فیلد را خالی بگذارید.</span></label>';
        echo '<div class="afe-span-2 afe-admin-callout">ارسال عادی: <code>SendSMS/SendSMS</code> و ارسال خدماتی: <code>SendSMS/BaseServiceNumber</code>. برای خدماتی باید شناسه الگوی تأییدشده (bodyId) و مقادیر متغیرهای آن را در SMS Action تنظیم کنید. فراپیامک مستقل از تنظیمات ملی‌پیامک است.</div>';
        if(!$this->secrets->available()) echo '<div class="afe-span-2 afe-admin-callout"><strong>هشدار:</strong> OpenSSL در دسترس نیست؛ کلید API جدید فراپیامک ذخیره نخواهد شد.</div>';
        echo '</div></details></div>';
        echo '<div class="afe-span-2 afe-admin-callout" data-afe-sms-test><strong>تست اتصال پنل پیامک (ارسال واقعی)</strong><p>ابتدا تنظیمات سرویس را ذخیره کنید، سپس شماره مقصد و Provider را انتخاب کنید. ارسال آزمایشی ممکن است هزینه داشته باشد.</p>';
        echo '<div class="afe-admin-grid"><label>Provider تست<select data-afe-sms-provider>';
        foreach($providerOptions as $providerKey=>$providerLabel){
            echo '<option value="'.esc_attr((string)$providerKey).'" '.selected($smsDefault,(string)$providerKey,false).'>'.esc_html((string)$providerLabel).'</option>';
        }
        echo '</select></label><label>شماره همراه مقصد<input type="tel" inputmode="numeric" dir="ltr" autocomplete="off" data-afe-sms-mobile placeholder="09123456789"></label></div>';
        echo '<button type="button" class="button button-secondary" data-afe-sms-test-button>ارسال پیامک آزمایشی</button><p role="status" aria-live="polite" data-afe-sms-test-result></p></div>';
        echo '</div></div>';

        echo '<div class="afe-admin-card"><h2>ایزوله‌سازی استایل فرم</h2><div class="afe-admin-grid">';
        echo '<label class="afe-span-2">حالت ایزوله‌سازی<select name="style_isolation">';
        $currentIsolation=(new StyleIsolationManager())->normalize((string)($settings['style_isolation']??StyleIsolationManager::MODE_STRONG));
        foreach(StyleIsolationManager::modes() as $value=>$label) {
            echo '<option value="'.esc_attr($value).'" '.selected($currentIsolation,$value,false).'>'.esc_html($label).'</option>';
        }
        echo '</select><span class="description">حالت «قوی» برای سایت‌هایی که CSS قالب روی input، label، button، select یا ساختار فرم اثر می‌گذارد پیشنهاد می‌شود. این تنظیم فقط داخل فرم‌های AFE اعمال می‌شود.</span></label>';
        echo '<div class="afe-span-2 afe-admin-callout"><strong>ترتیب استایل:</strong> CSS قالب → Reset ایزوله AFE → Design Tokenها → Componentهای AFE → CSS سفارشی هر فرم</div>';
        echo '</div></div>';

        echo '<div class="afe-admin-card"><h2>Capabilityها بر اساس نقش</h2><p class="description">Administrator همیشه همه Capabilityهای افزونه را دریافت می‌کند. برای نقش‌های دیگر دسترسی‌های لازم را انتخاب کنید.</p><div class="afe-table-scroll"><table class="widefat striped"><thead><tr><th>نقش</th>';
        foreach(Capabilities::assignable() as $cap) echo '<th><code>'.esc_html(str_replace('afe_','',$cap)).'</code></th>';
        echo '</tr></thead><tbody>';
        foreach($roles as $roleKey=>$roleData) {
            $role=get_role($roleKey); echo '<tr><th>'.esc_html(translate_user_role($roleData['name'])).'<code>'.esc_html($roleKey).'</code></th>';
            foreach(Capabilities::assignable() as $cap) {
                $disabled=$roleKey==='administrator'?' disabled':'';
                echo '<td><input type="checkbox" name="role_caps['.esc_attr($roleKey).'][]" value="'.esc_attr($cap).'" '.checked($role?->has_cap($cap),true,false).$disabled.'></td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></div></div>';

        echo '<div class="afe-admin-card"><div class="afe-admin-card-title"><div><h2>دسترسی اختصاصی هر فرم</h2><p>این بخش مشخص می‌کند هر نقش روی کدام فرم چه عملیاتی انجام دهد. Capability سراسری لازم برای ورود به صفحه مدیریت به‌صورت خودکار همگام می‌شود و دسترسی کاربر همچنان فقط به فرم‌های انتخاب‌شده محدود می‌ماند.</p></div></div>';
        foreach($this->registry->all() as $slug=>$formObj){
            $title=(string)($formObj->toArray()['title']??$slug);
            echo '<details class="afe-form-access-card" open><summary><strong>'.esc_html($title).'</strong><code>'.esc_html($slug).'</code></summary><div class="afe-table-scroll"><table class="widefat striped afe-form-access-table"><thead><tr><th>نقش</th>';
            foreach(FormAccess::levels() as $level=>$label) echo '<th>'.esc_html($label).'<code>'.esc_html(FormAccess::capability($slug,$level)).'</code></th>';
            echo '</tr></thead><tbody>';
            foreach($roles as $roleKey=>$roleData){
                $role=get_role($roleKey);
                echo '<tr><th>'.esc_html(translate_user_role($roleData['name'])).'<code>'.esc_html($roleKey).'</code></th>';
                foreach(FormAccess::levels() as $level=>$label){
                    $cap=FormAccess::capability($slug,$level);
                    $disabled=$roleKey==='administrator'?' disabled':'';
                    echo '<td><label class="afe-cap-check"><input type="checkbox" name="form_caps['.esc_attr($slug).']['.esc_attr($roleKey).'][]" value="'.esc_attr($level).'" '.checked($role?->has_cap($cap),true,false).$disabled.'><span>'.esc_html($label).'</span></label></td>';
                }
                echo '</tr>';
            }
            echo '</tbody></table></div></details>';
        }
        echo '</div>';
        submit_button('ذخیره تنظیمات'); echo '</form></div>';
    }

    /** Test uses saved credentials, never unsaved admin form values. */
    public function testSms(): void
    {
        if(!current_user_can(Capabilities::MANAGE_SETTINGS)) {
            wp_send_json_error(['message'=>'دسترسی کافی ندارید.'],403);
        }
        if(!check_ajax_referer('afe_test_sms','nonce',false)) {
            wp_send_json_error(['message'=>'اعتبار درخواست منقضی شده است.'],403);
        }
        $mobile=SmsDraftRecovery::normalizeMobile(sanitize_text_field(wp_unslash($_POST['mobile']??'')));
        $providerKey=sanitize_key((string)wp_unslash($_POST['provider']??''));
        if($mobile==='' || !$this->smsProviders || !$this->smsProviders->has($providerKey)) {
            wp_send_json_error(['message'=>'شماره موبایل یا سرویس‌دهنده معتبر نیست.'],422);
        }
        $rate='afe_sms_test_'.hash('sha256',get_current_user_id().'|'.(string)($_SERVER['REMOTE_ADDR']??''));
        if((int)get_transient($rate)>=3) {
            wp_send_json_error(['message'=>'حداکثر سه پیام آزمایشی در ۱۰ دقیقه مجاز است.'],429);
        }
        set_transient($rate,(int)get_transient($rate)+1,10*MINUTE_IN_SECONDS);
        try {
            $provider=$this->smsProviders->resolve($providerKey,'free');
            $sent=$provider->send(new SmsMessage($mobile,'free','پیامک آزمایشی اتصال پنل Alavi Form Engine'));
            if(empty($sent['success'])) {
                // External gateway errors may contain secrets or raw request data.
                wp_send_json_error(['message'=>'ارسال آزمایشی ناموفق بود. تنظیمات ذخیره‌شده، اعتبار، خط فرستنده و دسترسی وب‌سرویس را بررسی کنید.'],422);
            }
            $receipt=(string)($sent['message_id']??'');
            wp_send_json_success([
                'message'=>'درخواست ارسال پیامک آزمایشی با موفقیت در پنل ثبت شد. رسیدن پیام به گوشی جداگانه بررسی شود.',
                'message_id'=>preg_match('/^[0-9]{2,40}$/D',$receipt)?$receipt:'',
            ]);
        } catch(\Throwable) {
            wp_send_json_error(['message'=>'برقراری ارتباط با پنل پیامک ممکن نشد.'],422);
        }
    }

    private function save(): void
    {
        check_admin_referer('afe_save_settings','afe_settings_nonce');
        $old=(array)get_option('afe_settings',[]);
        $oldSms=is_array($old['sms']??null)?(array)$old['sms']:[];
        $smsPassword=(string)($oldSms['password']??'');
        $smsApiKey=(string)($oldSms['api_key']??'');
        $oldFarapayamak=is_array($oldSms['farapayamak']??null)?(array)$oldSms['farapayamak']:[];
        $farapayamakKey=(string)($oldFarapayamak['api_key']??'');
        $farapayamakInput=trim((string)wp_unslash($_POST['sms_farapayamak_api_key']??''));
        if($farapayamakInput!=='' && $this->secrets->available()) {
            $farapayamakKey=$this->secrets->encrypt($farapayamakInput);
        }
        $passwordInput=(string)wp_unslash($_POST['sms_password']??'');
        $apiKeyInput=trim((string)wp_unslash($_POST['sms_api_key']??''));
        if($passwordInput!=='' && $this->secrets->available()) $smsPassword=$this->secrets->encrypt($passwordInput);
        if($apiKeyInput!=='' && $this->secrets->available()) $smsApiKey=$this->secrets->encrypt($apiKeyInput);
        $authMode=sanitize_key(wp_unslash($_POST['sms_auth_mode']??'legacy'));
        if(!in_array($authMode,['legacy','api_key'],true)) $authMode='legacy';
        $smsEnabled=!empty($_POST['sms_enabled']);
        $defaultProvider=sanitize_key(wp_unslash($_POST['sms_default_provider']??($oldSms['default_provider']??$oldSms['provider']??'melipayamak')));
        $allowedProviders=array_keys($this->smsProviders?->providerOptions()??['melipayamak'=>'','farapayamak'=>'','persian_woocommerce_sms'=>'']);
        if(!in_array($defaultProvider,$allowedProviders,true)) $defaultProvider='melipayamak';
        $settings=array_replace($old,[
            'api_enabled'=>!empty($_POST['api_enabled']),
            'api_public_forms'=>!empty($_POST['api_public_forms']),
            'recaptcha_site_key'=>(string)($old['recaptcha_site_key']??''),
            'recaptcha_secret_key'=>(string)($old['recaptcha_secret_key']??''),
            'delete_data_on_uninstall'=>!empty($_POST['delete_data_on_uninstall']),
            'style_isolation'=>(new StyleIsolationManager())->normalize(sanitize_key(wp_unslash($_POST['style_isolation']??StyleIsolationManager::MODE_STRONG))),
            // Keep the legacy top-level switch synchronized for upgrades/downgrades.
            'sms_enabled'=>$smsEnabled,
            'sms'=>[
                'enabled'=>$smsEnabled,
                'provider'=>$defaultProvider,
                'default_provider'=>$defaultProvider,
                'auth_mode'=>$authMode,
                'username'=>sanitize_text_field(wp_unslash($_POST['sms_username']??'')),
                'password'=>$smsPassword,
                'api_key'=>$smsApiKey,
                'sender'=>sanitize_text_field(wp_unslash($_POST['sms_sender']??'')),
                'farapayamak'=>[
                    'username'=>sanitize_text_field(wp_unslash($_POST['sms_farapayamak_username']??'')),
                    'api_key'=>$farapayamakKey,
                    'sender'=>sanitize_text_field(wp_unslash($_POST['sms_farapayamak_sender']??'')),
                ],
            ],
        ]);
        update_option('afe_settings',$settings,true);

        $submitted=(array)($_POST['role_caps']??[]);
        foreach(wp_roles()->roles as $roleKey=>$data) {
            if($roleKey==='administrator') continue;
            $role=get_role($roleKey); if(!$role) continue;
            $wanted=array_values(array_intersect(Capabilities::assignable(),array_map('sanitize_key',(array)($submitted[$roleKey]??[]))));
            foreach(Capabilities::assignable() as $cap) {
                if(in_array($cap,$wanted,true)) $role->add_cap($cap); else $role->remove_cap($cap);
            }
        }
        $submittedFormCaps=is_array($_POST['form_caps']??null)?(array)$_POST['form_caps']:[];
        foreach($this->registry->all() as $slug=>$formObj){
            foreach(wp_roles()->roles as $roleKey=>$data){
                $role=get_role($roleKey); if(!$role) continue;
                if($roleKey==='administrator'){
                    foreach(FormAccess::levels() as $level=>$label) $role->add_cap(FormAccess::capability($slug,$level));
                    continue;
                }
                $wanted=array_values(array_intersect(array_keys(FormAccess::levels()),array_map('sanitize_key',(array)($submittedFormCaps[$slug][$roleKey]??[]))));
                // Editing/managing/exporting a submission necessarily needs read access.
                if(array_intersect($wanted,[FormAccess::EDIT,FormAccess::MANAGE,FormAccess::EXPORT]) && !in_array(FormAccess::VIEW,$wanted,true)) $wanted[]=FormAccess::VIEW;
                foreach(FormAccess::levels() as $level=>$label){
                    $cap=FormAccess::capability($slug,$level);
                    if(in_array($level,$wanted,true)) $role->add_cap($cap); else $role->remove_cap($cap);
                }
            }
        }
        Capabilities::install();
        $this->formAccess->sync($this->registry);
        Capabilities::syncAccess();
        wp_safe_redirect(admin_url('admin.php?page=alavi-form-engine-settings&updated=1')); exit;
    }
}
