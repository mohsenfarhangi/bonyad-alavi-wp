<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Admin;

use BonyadAlavi\FormEngine\Core\Capabilities;
use BonyadAlavi\FormEngine\Core\FormAccess;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Style\StyleIsolationManager;

final class SettingsPage
{
    public function __construct(private readonly FormRegistry $registry, private readonly FormAccess $formAccess) {}

    public function render(): void
    {
        if(!current_user_can(Capabilities::MANAGE_SETTINGS)) wp_die('دسترسی کافی ندارید.');
        if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['afe_save_settings'])) $this->save();

        $settings=get_option('afe_settings',[]);
        $roles=wp_roles()->roles;
        echo '<div class="wrap afe-admin-wrap"><h1>تنظیمات و مدیریت دسترسی</h1>';
        if(!empty($_GET['updated'])) echo '<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div>';
        echo '<form method="post">'; wp_nonce_field('afe_save_settings','afe_settings_nonce'); echo '<input type="hidden" name="afe_save_settings" value="1">';

        echo '<div class="afe-admin-card"><h2>API و کپچا</h2><div class="afe-admin-grid">';
        echo '<label><input type="checkbox" name="api_enabled" value="1" '.checked(!empty($settings['api_enabled']),true,false).'> REST API مدیریت فعال باشد</label>';
        echo '<label><input type="checkbox" name="api_public_forms" value="1" '.checked(!empty($settings['api_public_forms']),true,false).'> Schema فرم‌ها بدون ورود قابل خواندن باشد</label>';
        echo '<label>Google reCAPTCHA Site Key<input class="regular-text" name="recaptcha_site_key" value="'.esc_attr((string)($settings['recaptcha_site_key']??'')).'"></label>';
        echo '<label>Google reCAPTCHA Secret Key<input class="regular-text" type="password" autocomplete="new-password" name="recaptcha_secret_key" value="'.esc_attr((string)($settings['recaptcha_secret_key']??'')).'"></label>';
        echo '<label class="afe-span-2"><input type="checkbox" name="delete_data_on_uninstall" value="1" '.checked(!empty($settings['delete_data_on_uninstall']),true,false).'> هنگام Uninstall تمام داده‌ها و جداول افزونه حذف شوند <strong>(غیرقابل بازگشت)</strong></label>';
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

    private function save(): void
    {
        check_admin_referer('afe_save_settings','afe_settings_nonce');
        $old=get_option('afe_settings',[]);
        $secret=wp_unslash($_POST['recaptcha_secret_key']??'');
        $settings=array_replace((array)$old,[
            'api_enabled'=>!empty($_POST['api_enabled']),
            'api_public_forms'=>!empty($_POST['api_public_forms']),
            'recaptcha_site_key'=>sanitize_text_field(wp_unslash($_POST['recaptcha_site_key']??'')),
            'recaptcha_secret_key'=>sanitize_text_field($secret),
            'delete_data_on_uninstall'=>!empty($_POST['delete_data_on_uninstall']),
            'style_isolation'=>(new StyleIsolationManager())->normalize(sanitize_key(wp_unslash($_POST['style_isolation']??StyleIsolationManager::MODE_STRONG))),
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
