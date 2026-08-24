<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Database;

use WP_Error;

final class GeographyImporter
{
    /**
     * Multiple mirrors are used because some shared hosts block raw.githubusercontent.com.
     * Runtime form requests never use these URLs; they are only used by the admin importer.
     */
    private const SOURCES = [
        'provinces' => [
            ['type' => 'json', 'url' => 'https://cdn.jsdelivr.net/gh/sajaddp/list-of-cities-in-Iran@main/dist/json/provinces.json'],
            ['type' => 'json', 'url' => 'https://raw.githubusercontent.com/sajaddp/list-of-cities-in-Iran/main/dist/json/provinces.json'],
            ['type' => 'github_api', 'url' => 'https://api.github.com/repos/sajaddp/list-of-cities-in-Iran/contents/dist/json/provinces.json?ref=main'],
        ],
        'counties' => [
            ['type' => 'json', 'url' => 'https://cdn.jsdelivr.net/gh/sajaddp/list-of-cities-in-Iran@main/dist/json/counties.json'],
            ['type' => 'json', 'url' => 'https://raw.githubusercontent.com/sajaddp/list-of-cities-in-Iran/main/dist/json/counties.json'],
            ['type' => 'github_api', 'url' => 'https://api.github.com/repos/sajaddp/list-of-cities-in-Iran/contents/dist/json/counties.json?ref=main'],
        ],
        'districts' => [
            ['type' => 'json', 'url' => 'https://cdn.jsdelivr.net/gh/sajaddp/list-of-cities-in-Iran@main/dist/json/districts.json'],
            ['type' => 'json', 'url' => 'https://raw.githubusercontent.com/sajaddp/list-of-cities-in-Iran/main/dist/json/districts.json'],
            ['type' => 'github_api', 'url' => 'https://api.github.com/repos/sajaddp/list-of-cities-in-Iran/contents/dist/json/districts.json?ref=main'],
        ],
    ];

    private const REQUIRED_KEYS = [
        'provinces' => ['id', 'name'],
        'counties' => ['id', 'province_id', 'name'],
        'districts' => ['id', 'province_id', 'county_id', 'name'],
    ];

    private const MIN_ROWS = [
        'provinces' => 20,
        'counties' => 100,
        'districts' => 100,
    ];

    public function seedProvincesIfEmpty(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'afe_geo_provinces';
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $provinces = [
            [100,'مرکزی','مرکزی','086'],[101,'گیلان','گیلان','013'],[102,'مازندران','مازندران','011'],
            [103,'آذربایجان شرقی','آذربایجان-شرقی','041'],[104,'آذربایجان غربی','آذربایجان-غربی','044'],
            [105,'کرمانشاه','کرمانشاه','083'],[106,'خوزستان','خوزستان','061'],[107,'فارس','فارس','071'],
            [108,'کرمان','کرمان','034'],[109,'خراسان رضوی','خراسان-رضوی','051'],[110,'اصفهان','اصفهان','031'],
            [111,'سیستان و بلوچستان','سیستان-و-بلوچستان','054'],[112,'کردستان','کردستان','087'],
            [113,'همدان','همدان','081'],[114,'چهارمحال و بختیاری','چهارمحال-و-بختیاری','038'],
            [115,'لرستان','لرستان','066'],[116,'ایلام','ایلام','084'],[117,'کهگیلویه و بویراحمد','کهگیلویه-و-بویراحمد','074'],
            [118,'بوشهر','بوشهر','077'],[119,'زنجان','زنجان','024'],[120,'سمنان','سمنان','023'],
            [121,'یزد','یزد','035'],[122,'هرمزگان','هرمزگان','076'],[123,'تهران','تهران','021'],
            [124,'اردبیل','اردبیل','045'],[125,'قم','قم','025'],[126,'قزوین','قزوین','028'],
            [127,'گلستان','گلستان','017'],[128,'خراسان شمالی','خراسان-شمالی','058'],
            [129,'خراسان جنوبی','خراسان-جنوبی','056'],[130,'البرز','البرز','026'],
        ];

        foreach ($provinces as [$id,$name,$slug,$tel]) {
            $wpdb->replace($table, ['id'=>$id,'name'=>$name,'slug'=>$slug,'tel_prefix'=>$tel], ['%d','%s','%s','%s']);
        }
    }

    public function importRemote(): array|WP_Error
    {
        if (!function_exists('wp_safe_remote_get')) {
            return new WP_Error('afe_http', 'WordPress HTTP API در دسترس نیست.');
        }

        $datasets = [];
        $usedSources = [];
        foreach (array_keys(self::SOURCES) as $name) {
            $result = $this->fetchDataset($name);
            if (is_wp_error($result)) {
                update_option('afe_geo_last_error', $result->get_error_message(), false);
                return $result;
            }
            $datasets[$name] = $result['data'];
            $usedSources[$name] = $result['source'];
        }

        $result = $this->importArrays($datasets['provinces'], $datasets['counties'], $datasets['districts']);
        if (is_wp_error($result)) {
            update_option('afe_geo_last_error', $result->get_error_message(), false);
            return $result;
        }

        delete_option('afe_geo_last_error');
        update_option('afe_geo_source_urls', $usedSources, false);
        $result['sources'] = $usedSources;

        return $result;
    }

    /**
     * Import JSON files uploaded from the admin page. This path does not need
     * outbound HTTP access and is useful on restricted shared hosts.
     *
     * @param array<string,array<string,mixed>> $files
     */
    public function importUploaded(array $files): array|WP_Error
    {
        $datasets = [];
        foreach (array_keys(self::REQUIRED_KEYS) as $name) {
            $file = $files[$name] ?? null;
            if (!is_array($file)) {
                return new WP_Error('afe_geo_upload_missing', 'فایل ' . $this->datasetLabel($name) . ' انتخاب نشده است.');
            }

            $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($error !== UPLOAD_ERR_OK) {
                return new WP_Error('afe_geo_upload_error', sprintf('آپلود فایل %s با خطای %d مواجه شد.', $this->datasetLabel($name), $error));
            }

            $tmp = (string) ($file['tmp_name'] ?? '');
            $filename = sanitize_file_name((string) ($file['name'] ?? ''));
            $size = (int) ($file['size'] ?? 0);

            if ($tmp === '' || !is_uploaded_file($tmp) || !is_readable($tmp)) {
                return new WP_Error('afe_geo_upload_invalid', 'فایل موقت ' . $this->datasetLabel($name) . ' معتبر نیست.');
            }
            if ($size <= 0 || $size > 5 * 1024 * 1024) {
                return new WP_Error('afe_geo_upload_size', 'حجم فایل ' . $this->datasetLabel($name) . ' باید کمتر از ۵ مگابایت باشد.');
            }
            if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'json') {
                return new WP_Error('afe_geo_upload_ext', 'فرمت فایل ' . $this->datasetLabel($name) . ' باید JSON باشد.');
            }

            $body = file_get_contents($tmp);
            if (!is_string($body) || $body === '') {
                return new WP_Error('afe_geo_upload_read', 'خواندن فایل ' . $this->datasetLabel($name) . ' ناموفق بود.');
            }

            $data = json_decode($body, true);
            $validation = $this->validateDataset($name, $data);
            if (is_wp_error($validation)) {
                return $validation;
            }
            $datasets[$name] = $data;
        }

        $result = $this->importArrays($datasets['provinces'], $datasets['counties'], $datasets['districts']);
        if (is_wp_error($result)) {
            update_option('afe_geo_last_error', $result->get_error_message(), false);
            return $result;
        }

        delete_option('afe_geo_last_error');
        update_option('afe_geo_source_urls', [
            'provinces' => 'manual-upload',
            'counties' => 'manual-upload',
            'districts' => 'manual-upload',
        ], false);
        $result['sources'] = ['manual-upload'];

        return $result;
    }

    /**
     * Stream one client-side file chunk into the matching geography table.
     * Complete JSON objects are decoded and persisted immediately. Only an
     * unfinished JSON object at the end of the chunk is kept in a transient
     * until the next request arrives.
     *
     * @return array<string,mixed>|WP_Error
     */
    public function importChunk(
        string $dataset,
        string $uploadId,
        int $chunkIndex,
        int $totalChunks,
        string $tmpPath,
        int $totalSize = 0
    ): array|WP_Error {
        $dataset = sanitize_key($dataset);
        if (!array_key_exists($dataset, self::REQUIRED_KEYS)) {
            return new WP_Error('afe_geo_chunk_dataset', 'نوع دیتاست معتبر نیست.');
        }
        if (!preg_match('/^[A-Za-z0-9_-]{8,100}$/', $uploadId)) {
            return new WP_Error('afe_geo_chunk_upload_id', 'شناسه آپلود معتبر نیست.');
        }
        if ($chunkIndex < 0 || $totalChunks < 1 || $chunkIndex >= $totalChunks) {
            return new WP_Error('afe_geo_chunk_index', 'شماره chunk معتبر نیست.');
        }
        if ($tmpPath === '' || !is_readable($tmpPath)) {
            return new WP_Error('afe_geo_chunk_file', 'خواندن chunk آپلودشده ممکن نیست.');
        }

        $chunk = file_get_contents($tmpPath);
        if (!is_string($chunk)) {
            return new WP_Error('afe_geo_chunk_read', 'خواندن داده chunk ناموفق بود.');
        }
        if (strlen($chunk) > 1024 * 1024) {
            return new WP_Error('afe_geo_chunk_size', 'حجم هر chunk نباید بیشتر از ۱ مگابایت باشد.');
        }

        $stateKey = $this->chunkStateKey($dataset, $uploadId);
        $state = get_transient($stateKey);
        if (!is_array($state)) {
            if ($chunkIndex !== 0) {
                return new WP_Error('afe_geo_chunk_session', 'جلسه آپلود منقضی شده است؛ ارسال این فایل را از ابتدا شروع کنید.');
            }

            $cleared = $this->clearDatasetTable($dataset);
            if (is_wp_error($cleared)) {
                return $cleared;
            }

            $state = [
                'dataset' => $dataset,
                'next_index' => 0,
                'total_chunks' => $totalChunks,
                'carry' => '',
                'rows' => 0,
                'bytes' => 0,
                'total_size' => max(0, $totalSize),
                'started_at' => time(),
            ];
            update_option('afe_geo_importing_' . $dataset, [
                'upload_id' => $uploadId,
                'started_at' => gmdate('c'),
                'total_chunks' => $totalChunks,
            ], false);
        }

        $nextIndex = (int) ($state['next_index'] ?? 0);
        if ($chunkIndex < $nextIndex) {
            return $this->chunkResponse($dataset, $chunkIndex, $state, (bool) ($state['completed'] ?? false), true);
        }
        if ($chunkIndex > $nextIndex) {
            return new WP_Error(
                'afe_geo_chunk_order',
                sprintf('ترتیب ارسال صحیح نیست. chunk مورد انتظار %d است.', $nextIndex + 1)
            );
        }
        if ((int) ($state['total_chunks'] ?? $totalChunks) !== $totalChunks) {
            return new WP_Error('afe_geo_chunk_total', 'تعداد chunkهای این جلسه تغییر کرده است؛ آپلود را از ابتدا شروع کنید.');
        }

        if ($chunkIndex === 0) {
            $chunk = preg_replace('/^\xEF\xBB\xBF/', '', $chunk) ?? $chunk;
        }

        $buffer = (string) ($state['carry'] ?? '') . $chunk;
        [$objects, $carry] = $this->extractCompleteJsonObjects($buffer);

        $rows = [];
        foreach ($objects as $jsonObject) {
            $row = json_decode($jsonObject, true);
            if (!is_array($row)) {
                return new WP_Error('afe_geo_chunk_json', 'یکی از رکوردهای JSON در chunk قابل پردازش نیست.');
            }
            $validation = $this->validateRow($dataset, $row);
            if (is_wp_error($validation)) {
                return $validation;
            }
            $rows[] = $row;
        }

        if ($rows) {
            try {
                $this->upsertDatasetRows($dataset, $rows);
            } catch (\Throwable $e) {
                update_option('afe_geo_last_error', $e->getMessage(), false);
                return new WP_Error('afe_geo_chunk_database', $e->getMessage());
            }
        }

        $state['carry'] = $carry;
        $state['rows'] = (int) ($state['rows'] ?? 0) + count($rows);
        $state['bytes'] = (int) ($state['bytes'] ?? 0) + strlen($chunk);
        $state['next_index'] = $chunkIndex + 1;
        $state['updated_at'] = time();

        $completed = $chunkIndex === $totalChunks - 1;
        if ($completed) {
            if (trim((string) $state['carry']) !== '') {
                return new WP_Error('afe_geo_chunk_incomplete', 'فایل JSON ناقص است و آخرین رکورد کامل نشده است.');
            }

            $count = $this->datasetCount($dataset);
            $minimum = self::MIN_ROWS[$dataset] ?? 1;
            if ($count < $minimum) {
                return new WP_Error(
                    'afe_geo_chunk_rows',
                    sprintf('فایل %s ناقص به نظر می‌رسد؛ فقط %d رکورد ثبت شد.', $this->datasetLabel($dataset), $count)
                );
            }

            delete_option('afe_geo_importing_' . $dataset);
            delete_option('afe_geo_last_error');

            $sources = (array) get_option('afe_geo_source_urls', []);
            $sources[$dataset] = 'ajax-chunk-upload';
            update_option('afe_geo_source_urls', $sources, false);
            update_option('afe_geo_version', gmdate('c'), false);
            update_option('afe_geo_source', 'manual-ajax-chunk', false);

            $state['db_count'] = $count;
            $state['completed'] = true;
            // Keep a short completion receipt so a retry after a lost Ajax response is idempotent.
            set_transient($stateKey, $state, 15 * MINUTE_IN_SECONDS);
        } else {
            // Keep only the small unfinished JSON tail in WordPress storage.
            set_transient($stateKey, $state, HOUR_IN_SECONDS);
        }

        return $this->chunkResponse($dataset, $chunkIndex, $state, $completed, false, count($rows));
    }

    /** @return array<string,mixed> */
    private function chunkResponse(
        string $dataset,
        int $chunkIndex,
        array $state,
        bool $completed,
        bool $duplicate = false,
        int $rowsInChunk = 0
    ): array {
        return [
            'dataset' => $dataset,
            'label' => $this->datasetLabel($dataset),
            'chunk_index' => $chunkIndex,
            'next_chunk' => (int) ($state['next_index'] ?? ($chunkIndex + 1)),
            'total_chunks' => (int) ($state['total_chunks'] ?? 1),
            'rows_in_chunk' => $rowsInChunk,
            'rows_total' => (int) ($state['rows'] ?? 0),
            'db_count' => isset($state['db_count']) ? (int) $state['db_count'] : $this->datasetCount($dataset),
            'bytes_received' => (int) ($state['bytes'] ?? 0),
            'total_size' => (int) ($state['total_size'] ?? 0),
            'completed' => $completed,
            'duplicate' => $duplicate,
        ];
    }

    private function chunkStateKey(string $dataset, string $uploadId): string
    {
        return 'afe_geo_chunk_' . get_current_user_id() . '_' . md5($dataset . '|' . $uploadId);
    }

    /** @return array{0:list<string>,1:string} */
    private function extractCompleteJsonObjects(string $buffer): array
    {
        $objects = [];
        $length = strlen($buffer);
        $depth = 0;
        $start = null;
        $inString = false;
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $buffer[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }
                if ($char === '"') {
                    $inString = false;
                }
                continue;
            }

            if ($char === '"') {
                $inString = true;
                continue;
            }
            if ($char === '{') {
                if ($depth === 0) {
                    $start = $i;
                }
                $depth++;
                continue;
            }
            if ($char === '}' && $depth > 0) {
                $depth--;
                if ($depth === 0 && $start !== null) {
                    $objects[] = substr($buffer, $start, $i - $start + 1);
                    $start = null;
                }
            }
        }

        $carry = ($depth > 0 && $start !== null) ? substr($buffer, $start) : '';
        return [$objects, $carry];
    }

    private function validateRow(string $dataset, array $row): true|WP_Error
    {
        foreach (self::REQUIRED_KEYS[$dataset] ?? [] as $key) {
            if (!array_key_exists($key, $row)) {
                return new WP_Error('afe_geo_chunk_key', sprintf('کلید %s در یکی از رکوردهای %s وجود ندارد.', $key, $this->datasetLabel($dataset)));
            }
            if ($key === 'name' && trim((string) $row[$key]) === '') {
                return new WP_Error('afe_geo_chunk_name', 'نام یکی از رکوردهای ' . $this->datasetLabel($dataset) . ' خالی است.');
            }
        }
        return true;
    }

    private function clearDatasetTable(string $dataset): true|WP_Error
    {
        global $wpdb;
        $table = $this->datasetTable($dataset);
        if ($table === '') {
            return new WP_Error('afe_geo_chunk_table', 'جدول دیتاست معتبر نیست.');
        }
        if ($wpdb->query("DELETE FROM {$table}") === false) {
            return new WP_Error('afe_geo_chunk_clear', 'پاکسازی جدول قبل از Import ناموفق بود: ' . ($wpdb->last_error ?: 'Database error'));
        }
        return true;
    }

    private function datasetTable(string $dataset): string
    {
        global $wpdb;
        return match ($dataset) {
            'provinces' => $wpdb->prefix . 'afe_geo_provinces',
            'counties' => $wpdb->prefix . 'afe_geo_counties',
            'districts' => $wpdb->prefix . 'afe_geo_districts',
            default => '',
        };
    }

    private function datasetCount(string $dataset): int
    {
        global $wpdb;
        $table = $this->datasetTable($dataset);
        return $table !== '' ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}") : 0;
    }

    private function upsertDatasetRows(string $dataset, array $rows): void
    {
        $table = $this->datasetTable($dataset);
        match ($dataset) {
            'provinces' => $this->bulkInsertProvinces($table, $rows, true),
            'counties' => $this->bulkInsertCounties($table, $rows, true),
            'districts' => $this->bulkInsertDistricts($table, $rows, true),
            default => throw new \RuntimeException('نوع دیتاست برای ثبت در دیتابیس معتبر نیست.'),
        };
    }

    /**
     * @return array{data:array,source:string}|WP_Error
     */
    private function fetchDataset(string $name): array|WP_Error
    {
        $errors = [];

        foreach (self::SOURCES[$name] ?? [] as $source) {
            $url = (string) ($source['url'] ?? '');
            $type = (string) ($source['type'] ?? 'json');
            if ($url === '') {
                continue;
            }

            $response = wp_safe_remote_get($url, [
                'timeout' => 45,
                'redirection' => 5,
                'user-agent' => 'AlaviFormEngine/' . (defined('AFE_VERSION') ? AFE_VERSION : 'dev') . '; ' . home_url('/'),
                'headers' => [
                    'Accept' => $type === 'github_api' ? 'application/vnd.github+json' : 'application/json',
                ],
            ]);

            if (is_wp_error($response)) {
                $errors[] = $this->shortHost($url) . ': ' . $response->get_error_message();
                continue;
            }

            $code = (int) wp_remote_retrieve_response_code($response);
            if ($code !== 200) {
                $errors[] = $this->shortHost($url) . ': HTTP ' . $code;
                continue;
            }

            $body = (string) wp_remote_retrieve_body($response);
            if ($type === 'github_api') {
                $envelope = json_decode($body, true);
                if (!is_array($envelope) || empty($envelope['content'])) {
                    $errors[] = $this->shortHost($url) . ': پاسخ GitHub API فاقد content است.';
                    continue;
                }
                $decoded = base64_decode(preg_replace('/\s+/', '', (string) $envelope['content']), true);
                if ($decoded === false) {
                    $errors[] = $this->shortHost($url) . ': محتوای Base64 معتبر نیست.';
                    continue;
                }
                $body = $decoded;
            }

            $data = json_decode($body, true);
            $validation = $this->validateDataset($name, $data);
            if (is_wp_error($validation)) {
                $errors[] = $this->shortHost($url) . ': ' . $validation->get_error_message();
                continue;
            }

            return ['data' => $data, 'source' => $url];
        }

        return new WP_Error(
            'afe_geo_download_failed',
            sprintf(
                'دریافت دیتاست «%s» از همه منابع ناموفق بود. %s',
                $this->datasetLabel($name),
                $errors ? implode(' | ', $errors) : 'هیچ منبعی تعریف نشده است.'
            )
        );
    }

    public function importArrays(array $provinces, array $counties, array $districts): array|WP_Error
    {
        foreach ([
            'provinces' => $provinces,
            'counties' => $counties,
            'districts' => $districts,
        ] as $name => $rows) {
            $validation = $this->validateDataset($name, $rows);
            if (is_wp_error($validation)) {
                return $validation;
            }
        }

        global $wpdb;
        $pTable = $wpdb->prefix . 'afe_geo_provinces';
        $cTable = $wpdb->prefix . 'afe_geo_counties';
        $dTable = $wpdb->prefix . 'afe_geo_districts';

        // Using bulk INSERTs keeps the import well below typical shared-host execution limits.
        // The upstream dataset currently contains hundreds of counties and more than a thousand districts.
        $wpdb->query('START TRANSACTION');

        try {
            foreach ([$dTable, $cTable, $pTable] as $table) {
                if ($wpdb->query("DELETE FROM {$table}") === false) {
                    throw new \RuntimeException($wpdb->last_error ?: 'خطا در پاکسازی جدول ' . $table);
                }
            }

            $this->bulkInsertProvinces($pTable, $provinces);
            $this->bulkInsertCounties($cTable, $counties);
            $this->bulkInsertDistricts($dTable, $districts);

            $actual = [
                'provinces' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$pTable}"),
                'counties' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$cTable}"),
                'districts' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$dTable}"),
            ];
            $expected = [
                'provinces' => count($provinces),
                'counties' => count($counties),
                'districts' => count($districts),
            ];

            foreach ($expected as $name => $count) {
                if ($actual[$name] !== $count) {
                    throw new \RuntimeException(sprintf(
                        'تعداد رکوردهای %s پس از Import صحیح نیست. انتظار: %d، ثبت‌شده: %d',
                        $this->datasetLabel($name),
                        $count,
                        $actual[$name]
                    ));
                }
            }

            if ($wpdb->query('COMMIT') === false) {
                throw new \RuntimeException($wpdb->last_error ?: 'COMMIT دیتابیس ناموفق بود.');
            }
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('afe_geo_database', $e->getMessage());
        }

        update_option('afe_geo_version', gmdate('c'), true);
        update_option('afe_geo_source', 'sajaddp/list-of-cities-in-Iran', true);

        return [
            'provinces'=>count($provinces),
            'counties'=>count($counties),
            'districts'=>count($districts),
        ];
    }

    private function bulkInsertProvinces(string $table, array $rows, bool $upsert = false): void
    {
        global $wpdb;
        foreach (array_chunk($rows, 150) as $chunk) {
            $placeholders = [];
            $values = [];
            foreach ($chunk as $row) {
                $placeholders[] = '(%d,%s,%s,%s)';
                $values[] = (int) $row['id'];
                $values[] = sanitize_text_field((string) $row['name']);
                $values[] = sanitize_text_field((string) ($row['slug'] ?? $row['name']));
                $values[] = sanitize_text_field((string) ($row['tel_prefix'] ?? ''));
            }
            $sql = "INSERT INTO {$table} (id,name,slug,tel_prefix) VALUES " . implode(',', $placeholders);
            if ($upsert) {
                $sql .= " ON DUPLICATE KEY UPDATE name=VALUES(name),slug=VALUES(slug),tel_prefix=VALUES(tel_prefix)";
            }
            $prepared = $wpdb->prepare($sql, $values);
            if (!is_string($prepared) || $wpdb->query($prepared) === false) {
                throw new \RuntimeException('خطا در درج استان‌ها: ' . ($wpdb->last_error ?: 'Database error'));
            }
        }
    }

    private function bulkInsertCounties(string $table, array $rows, bool $upsert = false): void
    {
        global $wpdb;
        foreach (array_chunk($rows, 150) as $chunk) {
            $placeholders = [];
            $values = [];
            foreach ($chunk as $row) {
                $placeholders[] = '(%d,%d,%s,%s)';
                $values[] = (int) $row['id'];
                $values[] = (int) $row['province_id'];
                $values[] = sanitize_text_field((string) $row['name']);
                $values[] = sanitize_text_field((string) ($row['slug'] ?? $row['name']));
            }
            $sql = "INSERT INTO {$table} (id,province_id,name,slug) VALUES " . implode(',', $placeholders);
            if ($upsert) {
                $sql .= " ON DUPLICATE KEY UPDATE province_id=VALUES(province_id),name=VALUES(name),slug=VALUES(slug)";
            }
            $prepared = $wpdb->prepare($sql, $values);
            if (!is_string($prepared) || $wpdb->query($prepared) === false) {
                throw new \RuntimeException('خطا در درج شهرستان‌ها: ' . ($wpdb->last_error ?: 'Database error'));
            }
        }
    }

    private function bulkInsertDistricts(string $table, array $rows, bool $upsert = false): void
    {
        global $wpdb;
        foreach (array_chunk($rows, 150) as $chunk) {
            $placeholders = [];
            $values = [];
            foreach ($chunk as $row) {
                $placeholders[] = '(%d,%d,%d,%s,%s)';
                $values[] = (int) $row['id'];
                $values[] = (int) $row['province_id'];
                $values[] = (int) $row['county_id'];
                $values[] = sanitize_text_field((string) $row['name']);
                $values[] = sanitize_text_field((string) ($row['slug'] ?? $row['name']));
            }
            $sql = "INSERT INTO {$table} (id,province_id,county_id,name,slug) VALUES " . implode(',', $placeholders);
            if ($upsert) {
                $sql .= " ON DUPLICATE KEY UPDATE province_id=VALUES(province_id),county_id=VALUES(county_id),name=VALUES(name),slug=VALUES(slug)";
            }
            $prepared = $wpdb->prepare($sql, $values);
            if (!is_string($prepared) || $wpdb->query($prepared) === false) {
                throw new \RuntimeException('خطا در درج بخش‌ها: ' . ($wpdb->last_error ?: 'Database error'));
            }
        }
    }

    private function validateDataset(string $name, mixed $data): true|WP_Error
    {
        if (!is_array($data) || !array_is_list($data)) {
            return new WP_Error('afe_geo_json', 'ساختار JSON دیتاست ' . $this->datasetLabel($name) . ' معتبر نیست.');
        }

        $minRows = self::MIN_ROWS[$name] ?? 1;
        if (count($data) < $minRows) {
            return new WP_Error(
                'afe_geo_rows',
                sprintf('دیتاست %s ناقص است؛ فقط %d ردیف دریافت شد.', $this->datasetLabel($name), count($data))
            );
        }

        $keys = self::REQUIRED_KEYS[$name] ?? [];
        $sampleIndexes = array_unique([0, (int) floor((count($data) - 1) / 2), count($data) - 1]);
        foreach ($sampleIndexes as $index) {
            $row = $data[$index] ?? null;
            if (!is_array($row)) {
                return new WP_Error('afe_geo_row', 'یکی از ردیف‌های دیتاست ' . $this->datasetLabel($name) . ' معتبر نیست.');
            }
            foreach ($keys as $key) {
                if (!array_key_exists($key, $row) || ($key === 'name' && trim((string)$row[$key]) === '')) {
                    return new WP_Error('afe_geo_key', sprintf('کلید %s در دیتاست %s وجود ندارد.', $key, $this->datasetLabel($name)));
                }
            }
        }

        return true;
    }

    private function shortHost(string $url): string
    {
        $host = wp_parse_url($url, PHP_URL_HOST);
        return is_string($host) && $host !== '' ? $host : $url;
    }

    private function datasetLabel(string $name): string
    {
        return match ($name) {
            'provinces' => 'استان',
            'counties' => 'شهرستان',
            'districts' => 'بخش',
            default => $name,
        };
    }
}
