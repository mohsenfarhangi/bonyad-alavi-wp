<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Database;

use BonyadAlavi\FormEngine\Core\Capabilities;

final class Migrator
{
    public function migrate(): array
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $p = $wpdb->prefix;

        $queries = [
            "CREATE TABLE {$p}afe_forms (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                slug varchar(191) NOT NULL,
                title varchar(255) NOT NULL,
                definition_hash char(64) NOT NULL,
                overrides_json longtext NULL,
                template_html longtext NULL,
                custom_css longtext NULL,
                custom_js longtext NULL,
                settings_json longtext NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY slug (slug)
            ) {$charset};",

            "CREATE TABLE {$p}afe_submissions (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                form_slug varchar(191) NOT NULL,
                status varchar(50) NOT NULL DEFAULT 'new',
                tracking_code varchar(64) NOT NULL,
                edit_token_hash char(64) NULL,
                user_id bigint(20) unsigned NOT NULL DEFAULT 0,
                data_json longtext NOT NULL,
                ip_hash char(64) NULL,
                user_agent varchar(500) NULL,
                is_locked tinyint(1) NOT NULL DEFAULT 0,
                locked_at datetime NULL,
                edit_request_status varchar(20) NOT NULL DEFAULT '',
                edit_request_message longtext NULL,
                edit_requested_at datetime NULL,
                edit_request_updated_at datetime NULL,
                trashed_at datetime NULL,
                trashed_by bigint(20) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY tracking_code (tracking_code),
                KEY form_status (form_slug,status),
                KEY user_form (user_id,form_slug),
                KEY updated_at (updated_at),
                KEY locked_request (is_locked,edit_request_status),
                KEY trash_scope (trashed_at,form_slug)
            ) {$charset};",

            "CREATE TABLE {$p}afe_submission_values (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                submission_id bigint(20) unsigned NOT NULL,
                field_key varchar(191) NOT NULL,
                value_text longtext NULL,
                value_num decimal(30,8) NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY submission_id (submission_id),
                KEY field_key (field_key(100)),
                KEY field_number (field_key(80),value_num)
            ) {$charset};",

            "CREATE TABLE {$p}afe_files (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                submission_id bigint(20) unsigned NOT NULL,
                field_key varchar(191) NOT NULL,
                attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
                path text NULL,
                url text NULL,
                mime varchar(191) NULL,
                size bigint(20) unsigned NOT NULL DEFAULT 0,
                original_name varchar(255) NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY submission_id (submission_id),
                KEY field_key (field_key(100))
            ) {$charset};",

            "CREATE TABLE {$p}afe_notes (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                submission_id bigint(20) unsigned NOT NULL,
                user_id bigint(20) unsigned NOT NULL DEFAULT 0,
                note longtext NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY submission_id (submission_id)
            ) {$charset};",

            "CREATE TABLE {$p}afe_audit_log (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                submission_id bigint(20) unsigned NULL,
                form_slug varchar(191) NULL,
                user_id bigint(20) unsigned NOT NULL DEFAULT 0,
                action varchar(100) NOT NULL,
                context_json longtext NULL,
                ip_hash char(64) NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY submission_id (submission_id),
                KEY form_slug (form_slug(100)),
                KEY action (action)
            ) {$charset};",

            "CREATE TABLE {$p}afe_geo_provinces (
                id bigint(20) unsigned NOT NULL,
                name varchar(191) NOT NULL,
                slug varchar(191) NULL,
                tel_prefix varchar(10) NULL,
                PRIMARY KEY  (id),
                KEY name (name(100))
            ) {$charset};",

            "CREATE TABLE {$p}afe_geo_counties (
                id bigint(20) unsigned NOT NULL,
                province_id bigint(20) unsigned NOT NULL,
                name varchar(191) NOT NULL,
                slug varchar(191) NULL,
                PRIMARY KEY  (id),
                KEY province_id (province_id),
                KEY name (name(100))
            ) {$charset};",

            "CREATE TABLE {$p}afe_geo_districts (
                id bigint(20) unsigned NOT NULL,
                province_id bigint(20) unsigned NOT NULL,
                county_id bigint(20) unsigned NOT NULL,
                name varchar(191) NOT NULL,
                slug varchar(191) NULL,
                PRIMARY KEY  (id),
                KEY province_id (province_id),
                KEY county_id (county_id),
                KEY name (name(100))
            ) {$charset};",
        ];

        $changes = [];
        foreach ($queries as $query) {
            $changes = array_merge($changes, dbDelta($query));
        }

        update_option('afe_db_version', AFE_DB_VERSION, true);
        Capabilities::install();
        (new GeographyImporter())->seedProvincesIfEmpty();

        return $changes;
    }

    public function health(): array
    {
        global $wpdb;
        $required = [
            'afe_forms','afe_submissions','afe_submission_values','afe_files',
            'afe_notes','afe_audit_log','afe_geo_provinces','afe_geo_counties','afe_geo_districts',
        ];
        $status = [];
        foreach ($required as $short) {
            $table = $wpdb->prefix . $short;
            $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
            $status[$table] = $exists;
        }
        return $status;
    }
}
