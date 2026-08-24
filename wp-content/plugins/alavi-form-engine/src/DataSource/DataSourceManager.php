<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\DataSource;

use RuntimeException;

final class DataSourceManager
{
    /** @var array<string, callable> */
    private array $custom = [];

    public function register(string $name, callable $resolver): void
    {
        $this->custom[$name] = $resolver;
    }

    public function resolve(array|string|null $source, array $context = []): array
    {
        if (!$source) return [];
        if (is_string($source)) {
            if (isset($this->custom[$source])) return (array)($this->custom[$source])($context);
            return [];
        }

        $type = $source['type'] ?? 'static';
        return match ($type) {
            'static' => (array)($source['options'] ?? []),
            'callback' => $this->callback($source, $context),
            'database' => $this->database($source, $context),
            'posts' => $this->posts($source),
            'taxonomy' => $this->taxonomy($source),
            'users' => $this->users($source),
            'json' => $this->json($source),
            'rest' => $this->rest($source, $context),
            'geo' => $this->geo($source, $context),
            'custom' => $this->customSource($source, $context),
            default => apply_filters('afe_data_source_' . sanitize_key((string)$type), [], $source, $context),
        };
    }

    private function callback(array $source, array $context): array
    {
        $cb = $source['callback'] ?? null;
        return is_callable($cb) ? (array)$cb($context) : [];
    }

    private function database(array $source, array $context): array
    {
        global $wpdb;
        $table = (string)($source['table'] ?? '');
        $value = sanitize_key((string)($source['value_column'] ?? 'id'));
        $label = sanitize_key((string)($source['label_column'] ?? 'name'));
        $table = str_replace('{prefix}', $wpdb->prefix, $table);
        if ($table === '' || !preg_match('/^[A-Za-z0-9_]+$/', $table)) return [];
        $limit = min(1000, max(1, (int)($source['limit'] ?? 500)));
        $rows = $wpdb->get_results("SELECT `{$value}`,`{$label}` FROM `{$table}` LIMIT {$limit}", ARRAY_A) ?: [];
        $out=[]; foreach($rows as $r) $out[(string)$r[$value]]=(string)$r[$label]; return $out;
    }

    private function posts(array $source): array
    {
        $posts = get_posts([
            'post_type'=>$source['post_type'] ?? 'post',
            'post_status'=>'publish',
            'numberposts'=>min(500,(int)($source['limit']??100)),
        ]);
        $out=[]; foreach($posts as $p) $out[(string)$p->ID]=$p->post_title; return $out;
    }

    private function taxonomy(array $source): array
    {
        $terms = get_terms(['taxonomy'=>$source['taxonomy']??'category','hide_empty'=>false]);
        if (is_wp_error($terms)) return [];
        $out=[]; foreach($terms as $t) $out[(string)$t->term_id]=$t->name; return $out;
    }

    private function users(array $source): array
    {
        $users = get_users(['number'=>min(500,(int)($source['limit']??100))]);
        $out=[]; foreach($users as $u) $out[(string)$u->ID]=$u->display_name; return $out;
    }

    private function json(array $source): array
    {
        $path = (string)($source['path']??'');
        if ($path === '' || !is_readable($path)) return [];
        $data = json_decode((string)file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    private function rest(array $source, array $context): array
    {
        $url = esc_url_raw((string)($source['url']??''));
        if ($url === '') return [];
        $response = wp_remote_get(add_query_arg($context, $url), ['timeout'=>10]);
        if (is_wp_error($response)) return [];
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data)) return [];
        $valueKey = $source['value_key'] ?? 'id';
        $labelKey = $source['label_key'] ?? 'name';
        $out=[]; foreach($data as $row) if(is_array($row)&&isset($row[$valueKey],$row[$labelKey])) $out[(string)$row[$valueKey]]=(string)$row[$labelKey];
        return $out;
    }

    private function geo(array $source, array $context): array
    {
        global $wpdb;
        $level = $source['level'] ?? 'province';
        if ($level === 'province') {
            $rows = $wpdb->get_results("SELECT id,name FROM {$wpdb->prefix}afe_geo_provinces ORDER BY name", ARRAY_A) ?: [];
        } elseif ($level === 'county') {
            $parentRaw = trim((string)($context[$source['parent'] ?? 'province'] ?? ''));
            $province = $this->geoId($wpdb->prefix . 'afe_geo_provinces', $parentRaw);
            if (!$province) return [];
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id,name FROM {$wpdb->prefix}afe_geo_counties WHERE province_id=%d ORDER BY name", $province
            ), ARRAY_A) ?: [];
        } elseif ($level === 'district') {
            $parentRaw = trim((string)($context[$source['parent'] ?? 'county'] ?? ''));
            $county = $this->geoId($wpdb->prefix . 'afe_geo_counties', $parentRaw);
            if (!$county) return [];
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id,name FROM {$wpdb->prefix}afe_geo_districts WHERE county_id=%d ORDER BY name", $county
            ), ARRAY_A) ?: [];
        } else {
            return [];
        }
        $out=[]; foreach($rows as $r) $out[(string)$r['id']]=$r['name']; return $out;
    }


    /**
     * Resolve a geography parent supplied either as its numeric database ID or,
     * for backwards compatibility with forms rendered before 1.0.8, by name.
     */
    private function geoId(string $table, string $value): int
    {
        global $wpdb;
        if ($value === '') return 0;
        if (ctype_digit($value)) return (int)$value;

        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE name=%s ORDER BY id LIMIT 1",
            $value
        ));
        return $id ? (int)$id : 0;
    }

    private function customSource(array $source, array $context): array
    {
        $name = (string)($source['name'] ?? '');
        return isset($this->custom[$name]) ? (array)($this->custom[$name])($context, $source) : [];
    }
}
