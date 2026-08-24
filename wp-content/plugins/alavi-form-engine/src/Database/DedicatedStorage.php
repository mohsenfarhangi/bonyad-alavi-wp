<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Database;

final class DedicatedStorage
{
    public function tableFor(string $formSlug): string
    {
        global $wpdb;
        $slug = sanitize_key(str_replace('-', '_', $formSlug));
        return $wpdb->prefix . 'afe_form_' . $slug;
    }

    public function ensure(string $formSlug): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $this->tableFor($formSlug);
        $charset = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) unsigned NOT NULL,
            data_json longtext NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY submission_id (submission_id)
        ) {$charset};");
    }

    public function mirror(string $formSlug, int $submissionId, array $data): void
    {
        global $wpdb;
        $this->ensure($formSlug);
        $table = $this->tableFor($formSlug);
        $now = current_time('mysql', true);
        $wpdb->replace($table, [
            'submission_id'=>$submissionId,
            'data_json'=>wp_json_encode($data, JSON_UNESCAPED_UNICODE),
            'created_at'=>$now,
            'updated_at'=>$now,
        ], ['%d','%s','%s','%s']);
    }
}
