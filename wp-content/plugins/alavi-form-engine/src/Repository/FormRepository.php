<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Repository;

use BonyadAlavi\FormEngine\Form\FormRegistry;

final class FormRepository
{
    public function syncRegistry(FormRegistry $registry): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'afe_forms';
        foreach ($registry->all() as $form) {
            $definition = $form->toArray();
            $hash = hash('sha256', wp_json_encode($definition, JSON_UNESCAPED_UNICODE));
            $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$table} WHERE slug=%s", $form->slug()));
            $now = current_time('mysql', true);
            if ($existing) {
                $wpdb->update($table, [
                    'title'=>$definition['title'],
                    'definition_hash'=>$hash,
                    'updated_at'=>$now,
                ], ['id'=>(int)$existing->id], ['%s','%s','%s'], ['%d']);
            } else {
                $wpdb->insert($table, [
                    'slug'=>$form->slug(),
                    'title'=>$definition['title'],
                    'definition_hash'=>$hash,
                    'overrides_json'=>'{}',
                    'template_html'=>'',
                    'custom_css'=>'',
                    'custom_js'=>'',
                    'settings_json'=>'{}',
                    'created_at'=>$now,
                    'updated_at'=>$now,
                ], ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s']);
            }
        }
    }

    public function row(string $slug): ?object
    {
        global $wpdb;
        $table = $wpdb->prefix . 'afe_forms';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE slug=%s", $slug)) ?: null;
    }

    public function overrides(string $slug): array
    {
        $row = $this->row($slug);
        if (!$row) return [];
        $decoded = json_decode((string)$row->overrides_json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function adminSettings(string $slug): array
    {
        $row = $this->row($slug);
        $decoded = $row ? json_decode((string)$row->settings_json, true) : [];
        return is_array($decoded) ? $decoded : [];
    }

    public function saveAdminConfig(string $slug, array $overrides, string $template, string $css, string $js, array $settings): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'afe_forms';
        $result = $wpdb->update($table, [
            'overrides_json'=>wp_json_encode($overrides, JSON_UNESCAPED_UNICODE),
            'template_html'=>$template,
            'custom_css'=>$css,
            'custom_js'=>$js,
            'settings_json'=>wp_json_encode($settings, JSON_UNESCAPED_UNICODE),
            'updated_at'=>current_time('mysql', true),
        ], ['slug'=>$slug], ['%s','%s','%s','%s','%s','%s'], ['%s']);
        return $result !== false;
    }
}
