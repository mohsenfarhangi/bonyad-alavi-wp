<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Admin;

use BonyadAlavi\FormEngine\Core\Capabilities;
use BonyadAlavi\FormEngine\Database\DedicatedStorage;
use BonyadAlavi\FormEngine\Database\GeographyImporter;
use BonyadAlavi\FormEngine\Database\Migrator;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Localization\LocaleDateService;

final class DatabasePage
{
    public function __construct(
        private readonly Migrator $migrator,
        private readonly FormRegistry $registry,
        private readonly DedicatedStorage $dedicated,
        private readonly LocaleDateService $dates
    ) {}

    public function render(): void
    {
        if(!current_user_can(Capabilities::MANAGE_DATABASE)) wp_die('دسترسی کافی ندارید.');
        $notice='';
        if($_SERVER['REQUEST_METHOD']==='POST') {
            check_admin_referer('afe_database_action','afe_database_nonce');
            $action=sanitize_key(wp_unslash($_POST['afe_db_action']??''));
            if($action==='repair') {
                $changes=$this->migrator->migrate();
                $notice='بررسی و بروزرسانی ساختار دیتابیس انجام شد. '.count($changes).' تغییر گزارش شد.';
            } elseif($action==='geo_import') {
                $result=(new GeographyImporter())->importRemote();
                $notice=is_wp_error($result)?'خطا: '.$result->get_error_message():'دیتاست جغرافیا بروزرسانی شد: '.number_format_i18n($result['provinces']).' استان، '.number_format_i18n($result['counties']).' شهرستان، '.number_format_i18n($result['districts']).' بخش.';
            } elseif($action==='geo_upload') {
                $result=(new GeographyImporter())->importUploaded($_FILES['afe_geo']??[]);
                $notice=is_wp_error($result)?'خطا: '.$result->get_error_message():'فایل‌های جغرافیا با موفقیت وارد شدند: '.number_format_i18n($result['provinces']).' استان، '.number_format_i18n($result['counties']).' شهرستان، '.number_format_i18n($result['districts']).' بخش.';
            } elseif($action==='dedicated') {
                $slug=sanitize_key(wp_unslash($_POST['form_slug']??''));
                if($this->registry->has($slug)) { $this->dedicated->ensure($slug); $notice='جدول اختصاصی فرم بررسی/ایجاد شد.'; }
            }
        }

        $health=$this->migrator->health();
        global $wpdb;
        $geo=[
            'استان'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}afe_geo_provinces"),
            'شهرستان'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}afe_geo_counties"),
            'بخش'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}afe_geo_districts"),
        ];
        $geoVersion=(string)get_option('afe_geo_version','');
        $geoLastError=(string)get_option('afe_geo_last_error','');
        $geoSources=(array)get_option('afe_geo_source_urls',[]);

        echo '<div class="wrap afe-admin-wrap"><h1>سلامت دیتابیس</h1>';
        if($notice) echo '<div class="notice notice-info"><p>'.esc_html($notice).'</p></div>';
        echo '<div class="afe-admin-columns"><div class="afe-admin-card"><h2>جداول پیش‌فرض</h2><table class="widefat striped"><tbody>';
        foreach($health as $table=>$ok) echo '<tr><td><code>'.esc_html($table).'</code></td><td>'.($ok?'<span class="afe-ok">موجود</span>':'<span class="afe-bad">وجود ندارد</span>').'</td></tr>';
        echo '</tbody></table><form method="post">'; wp_nonce_field('afe_database_action','afe_database_nonce');
        echo '<input type="hidden" name="afe_db_action" value="repair">'; submit_button('بررسی و Repair دیتابیس','primary','submit',false); echo '</form></div>';

        echo '<div class="afe-admin-card"><h2>تقسیمات کشوری ایران</h2>';
        $geoDatasetKeys=['استان'=>'provinces','شهرستان'=>'counties','بخش'=>'districts'];
        foreach($geo as $label=>$count) echo '<p><strong>'.esc_html($label).':</strong> <span data-geo-count="'.esc_attr($geoDatasetKeys[$label]??'').'">'.number_format_i18n($count).'</span></p>';
        $geoReady=$geo['شهرستان']>0 && $geo['بخش']>0;
        echo '<p class="description">در زمان نمایش فرم، استان/شهرستان/بخش فقط از جداول دیتابیس وردپرس خوانده می‌شوند. دریافت GitHub فقط برای Import/Update دیتابیس است.</p>';
        if(!$geoReady) echo '<div class="notice notice-warning inline"><p>داده شهرستان یا بخش هنوز وارد نشده است. یک‌بار دکمه زیر را اجرا کنید.</p></div>';
        if($geoLastError!=='') echo '<div class="notice notice-error inline"><p><strong>آخرین خطای Import:</strong> '.esc_html($geoLastError).'</p></div>';
        if($geoVersion!=='') echo '<p class="description"><strong>آخرین بروزرسانی موفق:</strong> '.esc_html($this->dates->formatUtc($geoVersion,true)).'</p>';
        if($geoSources) { echo '<details><summary>منابع استفاده‌شده در آخرین Import موفق</summary><ul>'; foreach($geoSources as $level=>$source) echo '<li><code>'.esc_html((string)$level).'</code>: '.esc_html((string)$source).'</li>'; echo '</ul></details>'; }
        echo '<form method="post" style="margin-bottom:18px">';
        wp_nonce_field('afe_database_action','afe_database_nonce'); echo '<input type="hidden" name="afe_db_action" value="geo_import">';
        submit_button('دریافت / بروزرسانی خودکار دیتاست جغرافیا','secondary','submit',false); echo '</form>';

        echo '<hr><div class="afe-geo-import-head"><div><h3>Import دستی JSON با Ajax و Chunk</h3><p class="description">هر فایل به قطعات کوچک تقسیم می‌شود و هر قطعه بلافاصله پس از رسیدن به سرور در جدول مربوطه ثبت می‌شود. لازم نیست PHP کل فایل را یک‌جا دریافت یا در حافظه نگه دارد.</p></div><span class="afe-geo-live-dot">پردازش مستقیم دیتابیس</span></div>';
        echo '<div class="afe-geo-chunk-importer" data-afe-geo-chunk-uploader>';
        $datasets = [
            'provinces' => ['استان‌ها','provinces.json','afe_geo_provinces'],
            'counties' => ['شهرستان‌ها','counties.json','afe_geo_counties'],
            'districts' => ['بخش‌ها','districts.json','afe_geo_districts'],
        ];
        foreach ($datasets as $key => [$label,$filename,$table]) {
            echo '<section class="afe-geo-upload-card" data-dataset="'.esc_attr($key).'">';
            echo '<div class="afe-geo-upload-card__head"><div><strong>'.esc_html($label).'</strong><small>'.esc_html($filename).' → <code>'.esc_html($wpdb->prefix.$table).'</code></small></div><span class="afe-geo-upload-state is-idle" data-role="state">آماده انتخاب فایل</span></div>';
            echo '<label class="afe-geo-file-picker"><input type="file" accept="application/json,.json" data-role="file"><span>انتخاب '.esc_html($filename).'</span></label>';
            echo '<div class="afe-geo-file-info" data-role="file-info">هنوز فایلی انتخاب نشده است.</div>';
            echo '<div class="afe-geo-progress" aria-hidden="true"><span data-role="progress-bar"></span></div>';
            echo '<div class="afe-geo-progress-meta"><span data-role="progress-text">۰٪</span><span data-role="rows-text">۰ رکورد ثبت شده</span></div>';
            echo '<div class="afe-geo-upload-actions"><button type="button" class="button button-secondary" data-action="upload" disabled>شروع ارسال این فایل</button><button type="button" class="button" data-action="retry" hidden>تلاش مجدد</button></div>';
            echo '<div class="afe-geo-upload-message" data-role="message" aria-live="polite"></div>';
            echo '</section>';
        }
        echo '<div class="afe-geo-all-actions"><button type="button" class="button button-primary button-hero" data-action="upload-all">ارسال هر سه فایل به ترتیب</button><p class="description">ترتیب پیشنهادی: استان ← شهرستان ← بخش. در زمان ارسال، فایل انتخابی غیرفعال می‌شود تا session همان فایل تغییر نکند.</p></div>';
        echo '</div></div></div>';

        echo '<div class="afe-admin-card"><h2>جداول اختصاصی فرم</h2><form method="post" class="afe-inline-form">';
        wp_nonce_field('afe_database_action','afe_database_nonce'); echo '<input type="hidden" name="afe_db_action" value="dedicated"><select name="form_slug">';
        foreach($this->registry->all() as $slug=>$form) echo '<option value="'.esc_attr($slug).'">'.esc_html($form->toArray()['title']).'</option>';
        echo '</select><button class="button">بررسی / ایجاد جدول اختصاصی</button></form></div></div>';
    }
}
