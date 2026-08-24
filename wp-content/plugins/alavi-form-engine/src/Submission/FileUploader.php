<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Submission;

final class FileUploader
{
    public function validate(array $form, int $existingSubmissionId = 0, bool $draft = false): array
    {
        $errors = [];

        foreach ($this->fileFields($form) as $field) {
            $fieldKey = (string)$field['name'];
            $slots = $this->rawFilesForField($fieldKey);
            $files = array_values(array_filter($slots, static fn(array $file): bool =>
                (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
                && (string)($file['name'] ?? '') !== ''
            ));
            $selectedExisting = $existingSubmissionId > 0
                ? $this->selectedExistingRows($existingSubmissionId, $fieldKey)
                : [];
            $clientManifest = $this->clientFileManifest($fieldKey);

            // Never collapse a PHP upload transport failure into a misleading
            // "required" error. If the browser selected a file but PHP rejected
            // or dropped it, report the real transport/configuration issue.
            $transportError = $this->uploadTransportError($slots, $clientManifest);
            if ($transportError !== '') {
                $errors[$fieldKey] = $transportError;
                continue;
            }

            $count = count($files) + count($selectedExisting);

            if (!$draft && !empty($field['required']) && $count === 0) {
                $errors[$fieldKey] = ($field['label'] ?? $fieldKey) . ' الزامی است.';
                continue;
            }

            if ($count > (int)($field['max_files'] ?? 1)) {
                $errors[$fieldKey] = 'تعداد فایل‌های انتخاب‌شده بیشتر از حد مجاز است.';
                continue;
            }

            foreach ($files as $file) {
                $size = (int)($file['size'] ?? 0);
                if ($size > ((int)($field['max_size_mb'] ?? 5) * MB_IN_BYTES)) {
                    $errors[$fieldKey] = 'حجم یکی از فایل‌ها بیشتر از حد مجاز است.';
                    break;
                }

                $accepted = (array)($field['accept'] ?? []);
                if ($accepted) {
                    $tmpName = (string)($file['tmp_name'] ?? '');
                    $originalName = (string)($file['name'] ?? '');
                    $checked = ($tmpName !== '' && is_file($tmpName))
                        ? wp_check_filetype_and_ext($tmpName, $originalName)
                        : wp_check_filetype($originalName);
                    $mime = (string)($checked['type'] ?? '');

                    if ($mime === '' || !in_array($mime, $accepted, true)) {
                        $errors[$fieldKey] = 'نوع واقعی یکی از فایل‌ها مجاز نیست.';
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Detect upload failures before the normal required validation runs.
     *
     * The browser also sends a lightweight manifest for newly selected files.
     * This lets us distinguish "no file selected" from cases where PHP dropped
     * the multipart upload entirely because file_uploads/post_max_size/server
     * limits rejected it before $_FILES became usable.
     */
    private function uploadTransportError(array $slots, array $clientManifest): string
    {
        $clientCount = max(0, (int)($clientManifest['count'] ?? 0));

        foreach ($slots as $slot) {
            $name = (string)($slot['name'] ?? '');
            $error = (int)($slot['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($name === '' && $error === UPLOAD_ERR_NO_FILE) continue;
            if ($error === UPLOAD_ERR_OK) continue;

            return $this->uploadErrorMessage($error, $name);
        }

        if ($clientCount > 0 && !$slots) {
            if (!$this->phpFileUploadsEnabled()) {
                return 'فایل در مرورگر انتخاب شده است، اما آپلود فایل در تنظیمات PHP سرور غیرفعال است (file_uploads=Off).';
            }

            return sprintf(
                'فایل در مرورگر انتخاب شده است، اما PHP آن را دریافت نکرد. محدودیت‌های سرور را بررسی کنید (upload_max_filesize: %s، post_max_size: %s).',
                (string)ini_get('upload_max_filesize'),
                (string)ini_get('post_max_size')
            );
        }

        return '';
    }

    private function uploadErrorMessage(int $error, string $name = ''): string
    {
        $prefix = $name !== '' ? 'آپلود فایل «' . sanitize_file_name($name) . '» ناموفق بود. ' : 'آپلود فایل ناموفق بود. ';

        return $prefix . match ($error) {
            UPLOAD_ERR_INI_SIZE => sprintf(
                'حجم فایل از محدودیت PHP سرور بیشتر است (upload_max_filesize: %s).',
                (string)ini_get('upload_max_filesize')
            ),
            UPLOAD_ERR_FORM_SIZE => 'حجم فایل از محدودیت مجاز فرم بیشتر است.',
            UPLOAD_ERR_PARTIAL => 'فایل فقط به‌صورت ناقص به سرور رسیده است. دوباره تلاش کنید.',
            UPLOAD_ERR_NO_FILE => 'هیچ داده فایلی به PHP نرسیده است.',
            UPLOAD_ERR_NO_TMP_DIR => 'پوشه موقت آپلود روی سرور در دسترس نیست.',
            UPLOAD_ERR_CANT_WRITE => 'سرور نتوانست فایل را روی دیسک ذخیره کند.',
            UPLOAD_ERR_EXTENSION => 'یک افزونه PHP آپلود فایل را متوقف کرده است.',
            default => 'خطای ناشناخته‌ای هنگام دریافت فایل رخ داده است (کد ' . $error . ').',
        };
    }

    private function phpFileUploadsEnabled(): bool
    {
        $value = strtolower(trim((string)ini_get('file_uploads')));
        return !in_array($value, ['', '0', 'off', 'false', 'no'], true);
    }

    private function clientFileManifest(string $fieldKey): array
    {
        $root = $_POST['afe_new_file_manifest'] ?? [];
        if (!is_array($root) || !array_key_exists($fieldKey, $root)) return [];

        $raw = wp_unslash($root[$fieldKey]);
        if (is_array($raw)) return $raw;
        if (!is_string($raw) || $raw === '') return [];

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Upload new files and synchronize the explicit keep/remove selection for
     * existing files.
     *
     * Synchronization is atomic per field as far as practical: if a new upload
     * fails, any files created during that field's attempt are rolled back and
     * existing files are left untouched. This prevents replacing a required
     * photo from deleting the old photo when the new upload fails.
     */
    public function process(array $form, int $submissionId): array
    {
        $uploaded = [];

        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        global $wpdb;

        foreach ($this->fileFields($form) as $field) {
            $fieldKey = (string)$field['name'];
            // Snapshot rows that existed before this request. Newly uploaded rows
            // must never be treated as "unchecked existing files" and deleted by
            // the keep/remove synchronization below.
            $existingBeforeUpload = $this->existingRows($submissionId, $fieldKey);
            $fieldUploaded = [];
            $created = [];
            $fieldFailed = false;

            foreach ($this->filesForField($fieldKey) as $file) {
                $result = wp_handle_upload($file, ['test_form'=>false]);
                if (!empty($result['error']) || empty($result['file']) || empty($result['url'])) {
                    $fieldFailed = true;
                    break;
                }

                $attachmentId = wp_insert_attachment([
                    'post_mime_type'=>$result['type'],
                    'post_title'=>sanitize_file_name(pathinfo((string)$file['name'], PATHINFO_FILENAME)),
                    'post_status'=>'inherit',
                ], $result['file']);

                if (!is_wp_error($attachmentId) && $attachmentId) {
                    $attachmentId = (int)$attachmentId;
                    $meta = wp_generate_attachment_metadata($attachmentId, $result['file']);
                    wp_update_attachment_metadata($attachmentId, $meta);
                    update_post_meta($attachmentId, '_afe_managed_upload', '1');
                    update_post_meta($attachmentId, '_afe_submission_id', $submissionId);
                    update_post_meta($attachmentId, '_afe_field_key', $fieldKey);
                } else {
                    $attachmentId = 0;
                }

                $inserted = $wpdb->insert($wpdb->prefix . 'afe_files', [
                    'submission_id'=>$submissionId,
                    'field_key'=>$fieldKey,
                    'attachment_id'=>$attachmentId,
                    'path'=>$result['file'],
                    'url'=>$result['url'],
                    'mime'=>$result['type'],
                    'size'=>(int)($file['size'] ?? 0),
                    'original_name'=>sanitize_file_name((string)($file['name'] ?? '')),
                    'created_at'=>current_time('mysql', true),
                ]);

                if ($inserted === false) {
                    $this->cleanupCreatedUpload([
                        'id'=>0,
                        'attachment_id'=>$attachmentId,
                        'path'=>(string)$result['file'],
                    ]);
                    $fieldFailed = true;
                    break;
                }

                $rowId = (int)$wpdb->insert_id;
                $created[] = [
                    'id'=>$rowId,
                    'attachment_id'=>$attachmentId,
                    'path'=>(string)$result['file'],
                ];
                $fieldUploaded[] = [
                    'id'=>$rowId,
                    'attachment_id'=>$attachmentId,
                    'url'=>$result['url'],
                    'mime'=>$result['type'],
                ];
            }

            if ($fieldFailed) {
                foreach ($created as $createdRow) {
                    $wpdb->delete(
                        $wpdb->prefix . 'afe_files',
                        ['id'=>(int)$createdRow['id'], 'submission_id'=>$submissionId, 'field_key'=>$fieldKey],
                        ['%d','%d','%s']
                    );
                    $this->cleanupCreatedUpload($createdRow);
                }
                // Do not apply keep/remove changes for this field. Existing files
                // remain available so a failed replacement never causes data loss.
                continue;
            }

            $this->syncExistingSelection($submissionId, $fieldKey, $existingBeforeUpload);
            if ($fieldUploaded) {
                $uploaded[$fieldKey] = $fieldUploaded;
            }
        }

        return $uploaded;
    }

    /**
     * Existing rows for an exact submission + field pair.
     */
    private function existingRows(int $submissionId, string $fieldKey): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}afe_files
             WHERE submission_id=%d AND field_key=%s
             ORDER BY id ASC",
            $submissionId,
            $fieldKey
        ), ARRAY_A) ?: [];
    }

    /**
     * Return the existing rows selected by the current field UI.
     *
     * A stale pre-1.0.13 cached form has no manifest; in that case all existing
     * files are retained for backwards compatibility. When the manifest exists,
     * only IDs that already belong to this exact submission + field are accepted.
     */
    private function selectedExistingRows(int $submissionId, string $fieldKey): array
    {
        $rows = $this->existingRows($submissionId, $fieldKey);
        if (!$this->hasExistingManifest($fieldKey)) {
            return $rows;
        }

        $keep = array_fill_keys($this->requestedKeepIds($fieldKey), true);
        return array_values(array_filter($rows, static fn(array $row): bool =>
            isset($keep[(int)($row['id'] ?? 0)])
        ));
    }

    private function syncExistingSelection(int $submissionId, string $fieldKey, array $existingRows): void
    {
        if (!$this->hasExistingManifest($fieldKey)) {
            return;
        }

        $keep = array_fill_keys($this->requestedKeepIds($fieldKey), true);
        foreach ($existingRows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0 || isset($keep[$id])) {
                continue;
            }
            $this->deleteExistingRelation($submissionId, $fieldKey, $row);
        }
    }

    /**
     * Delete only a row proven to belong to the current submission + field.
     *
     * For uploads created by AFE 1.0.13+, the underlying attachment is also
     * removed when no other AFE file row references it. Older attachments are
     * only detached to avoid deleting media that may have been reused elsewhere.
     */
    private function deleteExistingRelation(int $submissionId, string $fieldKey, array $row): void
    {
        global $wpdb;

        $id = (int)($row['id'] ?? 0);
        if ($id <= 0) return;

        $deleted = $wpdb->delete(
            $wpdb->prefix . 'afe_files',
            ['id'=>$id, 'submission_id'=>$submissionId, 'field_key'=>$fieldKey],
            ['%d','%d','%s']
        );
        if ($deleted === false || $deleted === 0) return;

        $attachmentId = (int)($row['attachment_id'] ?? 0);
        if ($attachmentId <= 0) return;

        $remaining = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}afe_files WHERE attachment_id=%d",
            $attachmentId
        ));

        if ($remaining === 0 && get_post_meta($attachmentId, '_afe_managed_upload', true) === '1') {
            wp_delete_attachment($attachmentId, true);
        }
    }

    private function cleanupCreatedUpload(array $row): void
    {
        $attachmentId = (int)($row['attachment_id'] ?? 0);
        if ($attachmentId > 0) {
            wp_delete_attachment($attachmentId, true);
            return;
        }

        $path = (string)($row['path'] ?? '');
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
    }

    private function hasExistingManifest(string $fieldKey): bool
    {
        $root = $_POST['afe_existing_manifest'] ?? null;
        return is_array($root) && array_key_exists($fieldKey, $root);
    }

    private function requestedKeepIds(string $fieldKey): array
    {
        $root = $_POST['afe_keep_files'] ?? [];
        if (!is_array($root) || !isset($root[$fieldKey])) return [];

        $raw = is_array($root[$fieldKey]) ? $root[$fieldKey] : [$root[$fieldKey]];
        $ids = [];
        foreach ($raw as $value) {
            $id = (int)$value;
            if ($id > 0) $ids[$id] = $id;
        }
        return array_values($ids);
    }

    private function fileFields(array $form): array
    {
        $fields=[];
        foreach ($form['steps'] as $step) {
            foreach ($step['items'] as $field) {
                if (($field['type'] ?? '') === 'file') $fields[]=$field;
            }
        }
        return $fields;
    }

    private function filesForField(string $field): array
    {
        return array_values(array_filter(
            $this->rawFilesForField($field),
            static fn(array $file): bool =>
                (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
                && (string)($file['name'] ?? '') !== ''
        ));
    }

    /**
     * Normalize both scalar and []-style PHP file structures. Some server or
     * security layers can normalize a single upload differently; validation and
     * processing must accept both shapes without silently treating it as empty.
     */
    private function rawFilesForField(string $field): array
    {
        // 1.0.16+: prefer a flat top-level multipart key per FileField. This is
        // intentionally independent of nested PHP upload normalization.
        $flatKey = 'afe_upload_' . sanitize_key($field);
        if (isset($_FILES[$flatKey]) && is_array($_FILES[$flatKey])) {
            return $this->normalizePhpFileNode($_FILES[$flatKey]);
        }

        // Backwards compatibility for cached/pre-1.0.16 forms that submit
        // name="afe_files[field][]".
        $root = $_FILES['afe_files'] ?? null;
        if (is_array($root) && isset($root['name']) && is_array($root['name']) && array_key_exists($field, $root['name'])) {
            return $this->normalizeParallelFileArrays(
                $root['name'][$field] ?? '',
                $root['type'][$field] ?? '',
                $root['tmp_name'][$field] ?? '',
                $root['error'][$field] ?? UPLOAD_ERR_NO_FILE,
                $root['size'][$field] ?? 0
            );
        }

        // Defensive compatibility: some multipart/security layers transform
        // top-level keys. Scan only for a key that explicitly ends in this exact
        // field slug; never borrow a file from another AFE field.
        $needle = sanitize_key($field);
        foreach ($_FILES as $key => $node) {
            if (!is_string($key) || !is_array($node)) continue;
            $normalizedKey = sanitize_key($key);
            if ($normalizedKey !== $needle && !str_ends_with($normalizedKey, '_' . $needle)) continue;
            $files = $this->normalizePhpFileNode($node);
            if ($files) return $files;
        }

        return [];
    }

    /**
     * Normalize a normal PHP $_FILES top-level node, accepting both scalar and
     * [] inputs. No field ownership inference happens here; callers already
     * selected the exact multipart key belonging to the FileField.
     */
    private function normalizePhpFileNode(array $node): array
    {
        if (!array_key_exists('name', $node)) return [];
        return $this->normalizeParallelFileArrays(
            $node['name'] ?? '',
            $node['type'] ?? '',
            $node['tmp_name'] ?? '',
            $node['error'] ?? UPLOAD_ERR_NO_FILE,
            $node['size'] ?? 0
        );
    }

    private function normalizeParallelFileArrays(mixed $namesRaw, mixed $typesRaw, mixed $tmpRaw, mixed $errorsRaw, mixed $sizesRaw): array
    {
        $names = is_array($namesRaw) ? array_values($namesRaw) : [$namesRaw];
        $types = is_array($typesRaw) ? array_values($typesRaw) : [$typesRaw];
        $tmpNames = is_array($tmpRaw) ? array_values($tmpRaw) : [$tmpRaw];
        $errors = is_array($errorsRaw) ? array_values($errorsRaw) : [$errorsRaw];
        $sizes = is_array($sizesRaw) ? array_values($sizesRaw) : [$sizesRaw];

        $files = [];
        foreach ($names as $i => $name) {
            $files[] = [
                'name'=>(string)$name,
                'type'=>(string)($types[$i] ?? ''),
                'tmp_name'=>(string)($tmpNames[$i] ?? ''),
                'error'=>(int)($errors[$i] ?? UPLOAD_ERR_NO_FILE),
                'size'=>(int)($sizes[$i] ?? 0),
            ];
        }
        return $files;
    }

}
