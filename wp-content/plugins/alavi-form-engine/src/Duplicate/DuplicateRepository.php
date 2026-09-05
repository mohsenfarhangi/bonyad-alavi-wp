<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Duplicate;

final class DuplicateRepository
{
    public function find(string $formSlug, string $fingerprint, int $excludeSubmissionId = 0): ?int
    {
        global $wpdb;
        $fingerprints = $wpdb->prefix . 'afe_submission_fingerprints';
        $submissions = $wpdb->prefix . 'afe_submissions';
        $sql = "SELECT f.submission_id
                FROM {$fingerprints} f
                INNER JOIN {$submissions} s ON s.id=f.submission_id
                WHERE f.form_slug=%s AND f.fingerprint=%s AND s.trashed_at IS NULL";
        $args = [$formSlug, $fingerprint];
        if ($excludeSubmissionId > 0) {
            $sql .= ' AND f.submission_id<>%d';
            $args[] = $excludeSubmissionId;
        }
        $sql .= ' LIMIT 1';
        $id = $wpdb->get_var($wpdb->prepare($sql, ...$args));
        return $id ? (int)$id : null;
    }


    public function clearForForm(string $formSlug): int
    {
        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'afe_submission_fingerprints',
            ['form_slug'=>$formSlug],
            ['%s']
        );
        return $result === false ? 0 : (int)$result;
    }

    public function reserve(string $formSlug, int $submissionId, string $fingerprint): bool
    {
        global $wpdb;
        $result = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->prefix}afe_submission_fingerprints (form_slug,submission_id,fingerprint,created_at) VALUES (%s,%d,%s,%s)",
            $formSlug,
            $submissionId,
            $fingerprint,
            current_time('mysql', true)
        ));
        return $result === 1;
    }

    public function releaseBySubmission(int $submissionId): void
    {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'afe_submission_fingerprints', ['submission_id' => $submissionId], ['%d']);
    }

    /**
     * Release a canonical fingerprint and, when possible, promote one active
     * allow-mode duplicate to become the new canonical owner. This keeps every
     * active value combination discoverable after the previous owner is edited
     * or moved to trash. The caller is expected to wrap this operation in the
     * same transaction as the Submission mutation.
     */
    public function releaseAndPromote(string $formSlug, int $submissionId, string $fingerprint = ''): ?int
    {
        global $wpdb;
        $fingerprints = $wpdb->prefix . 'afe_submission_fingerprints';
        $submissions = $wpdb->prefix . 'afe_submissions';

        if ($fingerprint === '') {
            $fingerprint = $this->fingerprintForSubmission($submissionId);
        }
        $this->releaseBySubmission($submissionId);
        if ($fingerprint === '') return null;

        $candidate = (int)($wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$submissions}
             WHERE form_slug=%s AND is_duplicate=1 AND duplicate_of_submission_id=%d
               AND trashed_at IS NULL
             ORDER BY id ASC LIMIT 1 FOR UPDATE",
            $formSlug,
            $submissionId
        )) ?: 0);
        if ($candidate <= 0) return null;

        if (!$this->reserve($formSlug, $candidate, $fingerprint)) {
            // A concurrent transaction may already have established another
            // canonical owner. Retarget the old dependants to that owner.
            $owner = $this->find($formSlug, $fingerprint, $submissionId);
            if ($owner === null) {
                throw new \RuntimeException('Duplicate fingerprint owner promotion failed.');
            }
            $this->retargetDependents($formSlug, $submissionId, $owner);
            return $owner;
        }

        $updated = $wpdb->update(
            $submissions,
            ['is_duplicate'=>0, 'duplicate_of_submission_id'=>0],
            ['id'=>$candidate],
            ['%d','%d'],
            ['%d']
        );
        if ($updated === false) {
            throw new \RuntimeException('Duplicate canonical owner metadata update failed.');
        }
        $this->retargetDependents($formSlug, $submissionId, $candidate, $candidate);
        return $candidate;
    }

    private function retargetDependents(string $formSlug, int $oldOwnerId, int $newOwnerId, int $excludeSubmissionId = 0): void
    {
        global $wpdb;
        $submissions = $wpdb->prefix . 'afe_submissions';
        $sql = "UPDATE {$submissions}
                SET duplicate_of_submission_id=%d
                WHERE form_slug=%s AND is_duplicate=1 AND duplicate_of_submission_id=%d
                  AND trashed_at IS NULL";
        $args = [$newOwnerId, $formSlug, $oldOwnerId];
        if ($excludeSubmissionId > 0) {
            $sql .= ' AND id<>%d';
            $args[] = $excludeSubmissionId;
        }
        if ($wpdb->query($wpdb->prepare($sql, ...$args)) === false) {
            throw new \RuntimeException('Duplicate dependant retarget failed.');
        }
    }

    public function fingerprintForSubmission(int $submissionId): string
    {
        global $wpdb;
        return (string)($wpdb->get_var($wpdb->prepare(
            "SELECT fingerprint FROM {$wpdb->prefix}afe_submission_fingerprints WHERE submission_id=%d LIMIT 1",
            $submissionId
        )) ?: '');
    }
}
