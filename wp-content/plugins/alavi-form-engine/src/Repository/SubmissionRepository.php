<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Repository;

final class SubmissionRepository
{
    public function create(array $row): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'afe_submissions';
        $wpdb->insert($table, $row);
        return (int)$wpdb->insert_id;
    }

    public function update(int $id, array $row): bool
    {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'afe_submissions', $row, ['id'=>$id]) !== false;
    }

    public function find(int $id, bool $includeTrashed = false): ?array
    {
        global $wpdb;
        $trashSql = $includeTrashed ? "" : " AND trashed_at IS NULL";
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}afe_submissions WHERE id=%d{$trashSql}", $id
        ), ARRAY_A);
        if (!$row) return null;
        $row['data'] = json_decode((string)$row['data_json'], true) ?: [];
        return $row;
    }

    public function findByTracking(string $tracking, string $formSlug = ''): ?array
    {
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}afe_submissions WHERE tracking_code=%s AND trashed_at IS NULL";
        $args = [$tracking];
        if ($formSlug !== '') {
            $sql .= " AND form_slug=%s";
            $args[] = $formSlug;
        }
        $row = $wpdb->get_row($wpdb->prepare($sql, ...$args), ARRAY_A);
        if (!$row) return null;
        $row['data'] = json_decode((string)$row['data_json'], true) ?: [];
        return $row;
    }

    public function findByToken(string $token, string $formSlug = ''): ?array
    {
        global $wpdb;
        $hash = hash('sha256', $token);
        $sql = "SELECT * FROM {$wpdb->prefix}afe_submissions WHERE edit_token_hash=%s AND trashed_at IS NULL";
        $args = [$hash];
        if ($formSlug !== '') {
            $sql .= " AND form_slug=%s";
            $args[] = $formSlug;
        }
        $row = $wpdb->get_row($wpdb->prepare($sql, ...$args), ARRAY_A);
        if (!$row) return null;
        $row['data'] = json_decode((string)$row['data_json'], true) ?: [];
        return $row;
    }

    public function replaceValues(int $submissionId, array $data): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'afe_submission_values';
        $wpdb->delete($table, ['submission_id'=>$submissionId], ['%d']);
        $flat = $this->flatten($data);
        foreach ($flat as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $text = (string)$value;
            $numeric = is_numeric($value) ? (float)$value : null;
            $wpdb->insert($table, [
                'submission_id'=>$submissionId,
                'field_key'=>$key,
                'value_text'=>$text,
                'value_num'=>$numeric,
                'created_at'=>current_time('mysql', true),
            ], ['%d','%s','%s',$numeric === null ? '%s' : '%f','%s']);
        }
    }

    private function flatten(array $data, string $prefix = ''): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string)$key : $prefix . '.' . $key;
            if (is_array($value) && !array_is_list($value)) {
                $result += $this->flatten($value, $path);
            } elseif (is_array($value)) {
                $result[$path] = wp_json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                $result[$path] = $value;
            }
        }
        return $result;
    }

    public function files(int $submissionId): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}afe_files WHERE submission_id=%d ORDER BY id ASC", $submissionId
        ), ARRAY_A) ?: [];
    }

    /**
     * Return files that belong to one exact upload field.
     *
     * Field ownership is part of the query itself. This intentionally prevents
     * a file from another upload field in the same submission from leaking into
     * the current field's UI.
     */
    public function filesForField(int $submissionId, string $fieldKey): array
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

    public function notes(int $submissionId): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT n.*,u.display_name FROM {$wpdb->prefix}afe_notes n
             LEFT JOIN {$wpdb->users} u ON u.ID=n.user_id
             WHERE n.submission_id=%d ORDER BY n.id DESC", $submissionId
        ), ARRAY_A) ?: [];
    }

    public function addNote(int $submissionId, int $userId, string $note): int
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'afe_notes', [
            'submission_id'=>$submissionId,'user_id'=>$userId,
            'note'=>$note,'created_at'=>current_time('mysql', true),
        ], ['%d','%d','%s','%s']);
        return (int)$wpdb->insert_id;
    }

    public function audit(?int $submissionId, ?string $formSlug, string $action, array $context = []): void
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'afe_audit_log', [
            'submission_id'=>$submissionId,
            'form_slug'=>$formSlug,
            'user_id'=>get_current_user_id(),
            'action'=>$action,
            'context_json'=>wp_json_encode($context, JSON_UNESCAPED_UNICODE),
            'ip_hash'=>$this->ipHash(),
            'created_at'=>current_time('mysql', true),
        ]);
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        global $wpdb;
        $where = ['1=1']; $args = [];
        $allowedSlugs=array_values(array_filter(array_map('sanitize_key',(array)($filters['form_slugs']??[]))));
        if (array_key_exists('form_slugs',$filters) && !$allowedSlugs) {
            // Explicit empty scope means the user has access to no forms.
            $where[]='1=0';
        } elseif ($allowedSlugs) {
            $placeholders=implode(',',array_fill(0,count($allowedSlugs),'%s'));
            $where[]='s.form_slug IN ('.$placeholders.')';
            array_push($args,...$allowedSlugs);
        }
        if (!empty($filters['form_slug'])) { $where[]='s.form_slug=%s'; $args[]=$filters['form_slug']; }
        if (!empty($filters['status'])) { $where[]='s.status=%s'; $args[]=$filters['status']; }
        if (!empty($filters['from_utc'])) { $where[]='s.created_at>=%s'; $args[]=$filters['from_utc']; }
        elseif (!empty($filters['from'])) { $where[]='s.created_at>=%s'; $args[]=$filters['from'].' 00:00:00'; }
        if (!empty($filters['to_utc'])) { $where[]='s.created_at<=%s'; $args[]=$filters['to_utc']; }
        elseif (!empty($filters['to'])) { $where[]='s.created_at<=%s'; $args[]=$filters['to'].' 23:59:59'; }
        if (!empty($filters['trash'])) { $where[]='s.trashed_at IS NOT NULL'; } else { $where[]='s.trashed_at IS NULL'; }
        if (!empty($filters['q'])) {
            $like = '%' . $wpdb->esc_like($filters['q']) . '%';
            $where[]='(s.tracking_code LIKE %s OR s.data_json LIKE %s)';
            $args[]=$like; $args[]=$like;
        }
        $sql = "SELECT s.* FROM {$wpdb->prefix}afe_submissions s WHERE ".implode(' AND ',$where)." ORDER BY s.id DESC";
        $countSql = "SELECT COUNT(*) FROM {$wpdb->prefix}afe_submissions s WHERE ".implode(' AND ',$where);
        $total = (int)$wpdb->get_var($args ? $wpdb->prepare($countSql, ...$args) : $countSql);
        $offset = max(0, ($page-1)*$perPage);
        $sql .= " LIMIT %d OFFSET %d";
        $queryArgs = [...$args, $perPage, $offset];
        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$queryArgs), ARRAY_A) ?: [];
        foreach ($rows as &$row) {
            $row['data'] = json_decode((string)$row['data_json'], true) ?: [];
        }
        return ['rows'=>$rows,'total'=>$total,'pages'=>(int)ceil($total/max(1,$perPage))];
    }


    public function recentByUser(int $userId, string $formSlug, int $limit = 5): array
    {
        global $wpdb;
        if ($userId <= 0) return [];
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id,status,tracking_code,updated_at FROM {$wpdb->prefix}afe_submissions
             WHERE user_id=%d AND form_slug=%s AND trashed_at IS NULL ORDER BY updated_at DESC LIMIT %d",
            $userId, $formSlug, max(1,min(20,$limit))
        ), ARRAY_A) ?: [];
    }

    public function backfillLocksForForm(string $formSlug): int
    {
        global $wpdb;
        $table=$wpdb->prefix.'afe_submissions';
        $now=current_time('mysql',true);
        $sql=$wpdb->prepare(
            "UPDATE {$table} SET is_locked=1, locked_at=COALESCE(locked_at,%s) WHERE form_slug=%s AND is_locked=0 AND status NOT IN ('draft','revision')",
            $now,$formSlug
        );
        $result=$wpdb->query($sql);
        return $result===false?0:(int)$result;
    }

    public function trash(int $id, int $userId): bool
    {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'afe_submissions', [
            'trashed_at'=>current_time('mysql', true),
            'trashed_by'=>$userId,
            'updated_at'=>current_time('mysql', true),
        ], ['id'=>$id, 'trashed_at'=>null]) !== false;
    }

    public function restore(int $id): bool
    {
        global $wpdb;
        return $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}afe_submissions SET trashed_at=NULL, trashed_by=0, updated_at=%s WHERE id=%d AND trashed_at IS NOT NULL",
            current_time('mysql', true), $id
        )) !== false;
    }

    /** Permanently removes one already-trashed submission and its owned relations/files. */
    public function deletePermanently(int $id): bool
    {
        global $wpdb;
        $row=$this->find($id,true);
        if(!$row || empty($row['trashed_at'])) return false;
        $files=$this->files($id);
        $formSlug=(string)$row['form_slug'];
        $tracking=(string)$row['tracking_code'];
        $p=$wpdb->prefix;
        $wpdb->query('START TRANSACTION');
        try {
            $wpdb->delete($p.'afe_submission_values',['submission_id'=>$id],['%d']);
            $wpdb->delete($p.'afe_notes',['submission_id'=>$id],['%d']);
            $wpdb->delete($p.'afe_files',['submission_id'=>$id],['%d']);
            $wpdb->delete($p.'afe_audit_log',['submission_id'=>$id],['%d']);
            $wpdb->delete($p.'afe_action_log',['submission_id'=>$id],['%d']);
            $wpdb->delete($p.'afe_action_once',['submission_id'=>$id],['%d']);
            $wpdb->delete($p.'afe_submission_fingerprints',['submission_id'=>$id],['%d']);
            $dedicated=$p.'afe_form_'.sanitize_key(str_replace('-','_',$formSlug));
            if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$dedicated))===$dedicated){
                $wpdb->delete($dedicated,['submission_id'=>$id],['%d']);
            }
            $deleted=$wpdb->delete($p.'afe_submissions',['id'=>$id],['%d']);
            if($deleted!==1) throw new \RuntimeException('submission delete failed');
            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        foreach($files as $file){
            $attachmentId=(int)($file['attachment_id']??0);
            if($attachmentId>0 && get_post_meta($attachmentId,'_afe_managed_upload',true)==='1' && (int)get_post_meta($attachmentId,'_afe_submission_id',true)===$id){
                $remaining=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}afe_files WHERE attachment_id=%d",$attachmentId));
                if($remaining===0) wp_delete_attachment($attachmentId,true);
                continue;
            }
            if($attachmentId<=0){
                $path=(string)($file['path']??'');
                $uploads=wp_get_upload_dir();
                $base=realpath((string)($uploads['basedir']??''));
                $real=$path!==''?realpath($path):false;
                if($base && $real && str_starts_with($real,$base.DIRECTORY_SEPARATOR) && is_file($real)) @unlink($real);
            }
        }
        $this->audit(null,$formSlug,'submission.deleted_permanently',['submission_id'=>$id,'tracking_code'=>$tracking]);
        return true;
    }

    public function countsByStatus(string $formSlug = ''): array
    {
        global $wpdb;
        $sql = "SELECT status,COUNT(*) total FROM {$wpdb->prefix}afe_submissions WHERE trashed_at IS NULL";
        $args = [];
        if ($formSlug !== '') { $sql .= " AND form_slug=%s"; $args[]=$formSlug; }
        $sql .= " GROUP BY status";
        $rows = $wpdb->get_results($args ? $wpdb->prepare($sql,...$args) : $sql, ARRAY_A) ?: [];
        $out = [];
        foreach ($rows as $row) $out[$row['status']] = (int)$row['total'];
        return $out;
    }

    private function ipHash(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return hash_hmac('sha256', (string)$ip, wp_salt('auth'));
    }
}
