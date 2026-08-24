<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Admin;

use BonyadAlavi\FormEngine\Core\Capabilities;

final class Menu
{
    public function __construct(
        private readonly FormsPage $forms,
        private readonly SubmissionsPage $submissions,
        private readonly ReportsPage $reports,
        private readonly DatabasePage $database,
        private readonly SettingsPage $settings
    ) {}

    public function register(): void
    {
        add_action('admin_menu', function(): void {
            add_menu_page(
                'Alavi Form Engine','فرم‌های علوی',Capabilities::ACCESS_ADMIN,
                'alavi-form-engine',[$this,'landing'],'dashicons-feedback',26
            );
            add_submenu_page('alavi-form-engine','فرم‌ها','فرم‌ها',Capabilities::MANAGE_FORMS,'alavi-form-engine-forms',[$this->forms,'render']);
            add_submenu_page('alavi-form-engine','اطلاعات ارسالی','اطلاعات ارسالی',Capabilities::VIEW_SUBMISSIONS,'alavi-form-engine-submissions',[$this->submissions,'render']);
            add_submenu_page('alavi-form-engine','گزارش‌ها','گزارش‌ها',Capabilities::VIEW_REPORTS,'alavi-form-engine-reports',[$this->reports,'render']);
            add_submenu_page('alavi-form-engine','دیتابیس','دیتابیس',Capabilities::MANAGE_DATABASE,'alavi-form-engine-database',[$this->database,'render']);
            add_submenu_page('alavi-form-engine','تنظیمات و دسترسی','تنظیمات و دسترسی',Capabilities::MANAGE_SETTINGS,'alavi-form-engine-settings',[$this->settings,'render']);
            remove_submenu_page('alavi-form-engine','alavi-form-engine');
        });
    }

    public function landing(): void
    {
        if(current_user_can(Capabilities::MANAGE_FORMS)) {
            wp_safe_redirect(admin_url('admin.php?page=alavi-form-engine-forms'));
            exit;
        }
        if(current_user_can(Capabilities::VIEW_SUBMISSIONS)) {
            wp_safe_redirect(admin_url('admin.php?page=alavi-form-engine-submissions'));
            exit;
        }
        if(current_user_can(Capabilities::VIEW_REPORTS)) {
            wp_safe_redirect(admin_url('admin.php?page=alavi-form-engine-reports'));
            exit;
        }
        if(current_user_can(Capabilities::MANAGE_DATABASE)) {
            wp_safe_redirect(admin_url('admin.php?page=alavi-form-engine-database'));
            exit;
        }
        if(current_user_can(Capabilities::MANAGE_SETTINGS)) {
            wp_safe_redirect(admin_url('admin.php?page=alavi-form-engine-settings'));
            exit;
        }
        wp_die('دسترسی کافی ندارید.');
    }
}
