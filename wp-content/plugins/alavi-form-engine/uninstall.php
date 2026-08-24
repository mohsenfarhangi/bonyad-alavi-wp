<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$settings = get_option('afe_settings', []);
if (empty($settings['delete_data_on_uninstall'])) {
    return;
}

global $wpdb;
$tables = [
    'afe_forms',
    'afe_submissions',
    'afe_submission_values',
    'afe_files',
    'afe_notes',
    'afe_audit_log',
    'afe_geo_provinces',
    'afe_geo_counties',
    'afe_geo_districts',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
}

$like = $wpdb->esc_like($wpdb->prefix . 'afe_form_') . '%';
$custom = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $like));
foreach ($custom as $table) {
    if (str_starts_with($table, $wpdb->prefix . 'afe_form_')) {
        $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
    }
}

delete_option('afe_settings');
delete_option('afe_db_version');
delete_option('afe_geo_version');
