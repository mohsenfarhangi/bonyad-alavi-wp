<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

final class ActionExecutionRepository
{
    public function hasSucceeded(int $submissionId, string $eventKey, string $actionKey): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'afe_action_log';
        $count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE submission_id=%d AND event_key=%s AND action_key=%s AND status='success'",
            $submissionId,
            $eventKey,
            $actionKey
        ));
        return $count > 0;
    }

    /**
     * Atomically claims an execution guard. The caller supplies the guard scope:
     * `__submission__` for once-per-submission or the actual event key for
     * first-in-event-cycle semantics.
     */
    public function claimOnce(int $submissionId, string $guardScope, string $actionKey): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'afe_action_once';
        $now = current_time('mysql', true);
        $result = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$table} (submission_id,event_key,action_key,status,created_at,updated_at) VALUES (%d,%s,%s,'claimed',%s,%s)",
            $submissionId,
            $guardScope,
            $actionKey,
            $now,
            $now
        ));
        return $result === 1;
    }

    public function markOnce(int $submissionId, string $guardScope, string $actionKey, string $status): void
    {
        global $wpdb;
        $status = in_array($status, ['claimed','success','failed'], true) ? $status : 'failed';
        $wpdb->update(
            $wpdb->prefix . 'afe_action_once',
            ['status' => $status, 'updated_at' => current_time('mysql', true)],
            ['submission_id' => $submissionId, 'event_key' => $guardScope, 'action_key' => $actionKey],
            ['%s', '%s'],
            ['%d', '%s', '%s']
        );
    }

    /**
     * Explicit retry path for administrators. Normal event execution never
     * clears a once guard after failure, preventing accidental duplicate sends.
     */
    public function releaseOnceForRetry(int $submissionId, string $guardScope, string $actionKey): bool
    {
        global $wpdb;
        $deleted = $wpdb->delete(
            $wpdb->prefix . 'afe_action_once',
            ['submission_id'=>$submissionId,'event_key'=>$guardScope,'action_key'=>$actionKey],
            ['%d','%s','%s']
        );
        return $deleted !== false;
    }

    public function start(int $submissionId, string $formSlug, string $eventKey, string $actionKey, string $actionType, array $context = []): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'afe_action_log';
        $attempts = 1 + (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE submission_id=%d AND event_key=%s AND action_key=%s",
            $submissionId,
            $eventKey,
            $actionKey
        ));
        $wpdb->insert($table, [
            'submission_id' => $submissionId,
            'form_slug' => $formSlug,
            'event_key' => $eventKey,
            'action_key' => $actionKey,
            'action_type' => $actionType,
            'status' => 'running',
            'attempts' => $attempts,
            'context_json' => wp_json_encode($context, JSON_UNESCAPED_UNICODE),
            'error_message' => null,
            'created_at' => current_time('mysql', true),
            'updated_at' => current_time('mysql', true),
        ]);
        return (int)$wpdb->insert_id;
    }

    public function finish(int $id, string $status, ?string $errorMessage = null): void
    {
        global $wpdb;
        $status = in_array($status, ['success','failed','skipped'], true) ? $status : 'failed';
        $wpdb->update(
            $wpdb->prefix . 'afe_action_log',
            [
                'status' => $status,
                'error_message' => $errorMessage,
                'updated_at' => current_time('mysql', true),
            ],
            ['id' => $id],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }
}
