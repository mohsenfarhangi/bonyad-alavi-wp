<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Submission;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionManager;
use BonyadAlavi\FormEngine\Actions\ActionExecutionRepository;
use BonyadAlavi\FormEngine\Actions\ActionRuntime;
use BonyadAlavi\FormEngine\Database\DedicatedStorage;
use BonyadAlavi\FormEngine\Duplicate\DuplicateDecision;
use BonyadAlavi\FormEngine\Duplicate\DuplicatePolicy;
use BonyadAlavi\FormEngine\Duplicate\DuplicateRepository;
use BonyadAlavi\FormEngine\Core\FormAccess;
use BonyadAlavi\FormEngine\Events\EventDispatcher;
use BonyadAlavi\FormEngine\Events\SubmissionCreated;
use BonyadAlavi\FormEngine\Events\SubmissionUpdated;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Form\Validator;
use BonyadAlavi\FormEngine\Repository\FormRepository;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use BonyadAlavi\FormEngine\Security\SecurityManager;
use BonyadAlavi\FormEngine\Security\Validators;
use WP_Error;

final class SubmissionService
{
    public function __construct(
        private readonly FormRegistry $registry,
        private readonly FormRepository $forms,
        private readonly SubmissionRepository $submissions,
        private readonly SecurityManager $security,
        private readonly Validator $validator,
        private readonly FileUploader $files,
        private readonly ActionManager $actions,
        private readonly EventDispatcher $events,
        private readonly DedicatedStorage $dedicated,
        private readonly FormAccess $formAccess,
        private readonly DuplicatePolicy $duplicatePolicy,
        private readonly DuplicateRepository $duplicateRepository,
        private readonly ActionExecutionRepository $actionExecutions
    ) {}

    public function handleHttp(): array|WP_Error
    {
        $slug = sanitize_key(wp_unslash($_POST['afe_form_slug'] ?? ''));
        if (!$this->registry->has($slug)) return new WP_Error('form','فرم پیدا نشد.');
        $form = $this->resolvedForm($slug);
        $intent = sanitize_key(wp_unslash($_POST['afe_intent'] ?? 'submit'));
        $draft = $intent === 'draft';

        $security = $this->security->verifyRequest($slug, (int)($form['settings']['rate_limit'] ?? 10));
        if (is_wp_error($security)) return $security;

        $data = $this->sanitizeBySchema($form, (array)wp_unslash($_POST['afe_data'] ?? []));
        $existing = $this->resolveExisting($form);
        if ($existing && !$this->canEdit($form, $existing)) return new WP_Error('forbidden','این ثبت قفل است یا اجازه ویرایش آن را ندارید.');

        $errors = $this->validator->validate($form, $data, $draft);
        $fileErrors = $this->files->validate($form, $existing ? (int)$existing['id'] : 0, $draft);
        $errors = array_merge($errors, $fileErrors);
        if ($errors) return new WP_Error('validation', 'لطفاً خطاهای فرم را اصلاح کنید.', $errors);

        if (!$draft) {
            $captcha = $this->security->verifyCaptcha((string)($form['settings']['captcha'] ?? 'custom'));
            if (is_wp_error($captcha)) return $captcha;
        }

        $duplicate = $this->duplicatePolicy->evaluate($form, $data, $existing ? (int)$existing['id'] : 0);
        if ($duplicate->blocksDuplicate()) return $this->duplicateError($form, $duplicate);

        $now = current_time('mysql', true);
        $status = $draft ? 'draft' : 'new';
        $plainToken = '';
        $wasExisting = $existing !== null;
        $wasLocked = $existing ? !empty($existing['is_locked']) : false;

        global $wpdb;
        $wpdb->query('START TRANSACTION');
        try {
        if ($existing) {
            $id = (int)$existing['id'];
            if (!$draft && !in_array((string)$existing['status'], ['draft','revision','new'], true)) {
                $status = (string)$existing['status'];
            }
            $updateRow=[
                'status'=>$status,
                'data_json'=>wp_json_encode($data, JSON_UNESCAPED_UNICODE),
                'updated_at'=>$now,
                ...$this->duplicateMetadata($duplicate),
            ];
            if (!$draft && !empty($form['settings']['lock_after_submit'])) {
                $updateRow['is_locked']=1;
                $updateRow['locked_at']=$now;
                $updateRow['edit_request_status']='';
                $updateRow['edit_request_message']=null;
                $updateRow['edit_requested_at']=null;
                $updateRow['edit_request_updated_at']=$now;
            }
            if (!$this->submissions->update($id, $updateRow)) {
                throw new \RuntimeException('submission update failed');
            }
            $tracking = (string)$existing['tracking_code'];
            $this->submissions->audit($id, $slug, 'submission.updated', ['status'=>$status]);
        } else {
            $tracking = $this->trackingCode();
            $plainToken = wp_generate_password(48, false, false);
            $id = $this->submissions->create([
                'form_slug'=>$slug,
                'status'=>$status,
                'tracking_code'=>$tracking,
                'edit_token_hash'=>hash('sha256',$plainToken),
                'user_id'=>get_current_user_id(),
                'data_json'=>wp_json_encode($data, JSON_UNESCAPED_UNICODE),
                'ip_hash'=>$this->ipHash(),
                'user_agent'=>sanitize_text_field(substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,500)),
                'is_locked'=>(!$draft && !empty($form['settings']['lock_after_submit'])) ? 1 : 0,
                'locked_at'=>(!$draft && !empty($form['settings']['lock_after_submit'])) ? $now : null,
                'edit_request_status'=>'',
                ...$this->duplicateMetadata($duplicate),
                'created_at'=>$now,
                'updated_at'=>$now,
            ]);
            if (!$id) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('database','ذخیره اطلاعات انجام نشد.');
            }
            $this->submissions->audit($id, $slug, 'submission.created', ['status'=>$status]);
        }

        $collision = $this->syncDuplicateFingerprint($form, $id, $duplicate);
        if ($collision instanceof WP_Error) {
            $wpdb->query('ROLLBACK');
            return $collision;
        }
        $duplicate = $collision ?? $duplicate;
        $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }

        $uploaded = $this->files->process($form, $id);
        $this->submissions->replaceValues($id, $data);

        $storage = (string)($form['settings']['storage'] ?? $form['storage'] ?? 'shared');
        if ($storage === 'dedicated') $this->dedicated->mirror($slug,$id,$data);

        $row = $this->submissions->find($id) ?: [];
        $actionErrors = [];
        $actionRedirect = '';

        // Keep the original typed events for backward compatibility, while all
        // new Action Engine execution uses the canonical registry event keys.
        if ($wasExisting) {
            $this->events->dispatch(new SubmissionUpdated($id,$slug,$data,$status));
            $this->rememberRedirect($actionRedirect, $this->emitActionEvent('submission.updated', $form, $id, $data, $row, $actionErrors));
        } else {
            $this->events->dispatch(new SubmissionCreated($id,$slug,$data));
            $this->rememberRedirect($actionRedirect, $this->emitActionEvent('submission.created', $form, $id, $data, $row, $actionErrors));
        }

        $this->rememberRedirect($actionRedirect, $this->emitActionEvent(
            $draft ? 'submission.draft_saved' : 'submission.submitted',
            $form,
            $id,
            $data,
            $row,
            $actionErrors
        ));

        if (!$draft && !empty($row['is_locked']) && !$wasLocked) {
            $this->rememberRedirect($actionRedirect, $this->emitActionEvent('submission.locked', $form, $id, $data, $row, $actionErrors));
        }

        $base = wp_get_referer() ?: home_url('/');
        $editUrl = '';
        if (!empty($form['settings']['editing_enabled']) || !empty($form['settings']['lock_after_submit'])) {
            $modes=(array)($form['settings']['editing_modes']??[]);
            $cleanBase=remove_query_arg(['afe_edit','afe_tracking','afe_submission'],$base);
            if (in_array('link',$modes,true) && $plainToken !== '') {
                $editUrl = add_query_arg('afe_edit', rawurlencode($plainToken), $cleanBase);
            } elseif (in_array('link',$modes,true) && !empty($_POST['_afe_edit_token'])) {
                $editUrl = add_query_arg('afe_edit', rawurlencode(sanitize_text_field(wp_unslash($_POST['_afe_edit_token']))), $cleanBase);
            } elseif (in_array('tracking',$modes,true)) {
                $editUrl = add_query_arg('afe_tracking', rawurlencode($tracking), $cleanBase);
            } elseif (in_array('wordpress',$modes,true) && get_current_user_id()>0) {
                $editUrl = add_query_arg('afe_submission', $id, $cleanBase);
            }
        }

        return [
            'submission_id'=>$id,
            'status'=>$status,
            'tracking_code'=>$tracking,
            'edit_url'=>$editUrl,
            'edit_token'=>$plainToken,
            'uploaded'=>$uploaded,
            'files'=>$this->publicFiles($id),
            'action_errors'=>$actionErrors,
            'redirect_url'=>$actionRedirect,
            'is_duplicate'=>$duplicate->isDuplicate() && $duplicate->allowsDuplicate(),
            'duplicate_of_submission_id'=>$duplicate->isDuplicate() && $duplicate->allowsDuplicate() ? (int)$duplicate->duplicateSubmissionId : 0,
            'locked'=>(!$draft && !empty($form['settings']['lock_after_submit'])),
            'message'=>$draft ? 'پیش‌نویس با موفقیت ذخیره شد.' : 'اطلاعات با موفقیت ثبت شد.',
        ];
    }

    /**
     * Creates a submission from an authenticated REST integration.
     * CSRF/CAPTCHA are intentionally not used here: the REST route requires the
     * dedicated afe_api_submit capability and relies on WordPress REST auth.
     * Uploaded files can be sent as multipart using the same afe_files[field][]
     * shape used by the front-end form.
     */
    public function submitApi(string $slug, array $rawData, bool $draft = false): array|WP_Error
    {
        $slug = sanitize_key($slug);
        if (!$this->registry->has($slug)) return new WP_Error('form','فرم پیدا نشد.');

        $form = $this->resolvedForm($slug);
        $data = $this->sanitizeBySchema($form, $rawData);
        $errors = array_merge(
            $this->validator->validate($form, $data, $draft),
            $this->files->validate($form, 0, $draft)
        );
        if ($errors) return new WP_Error('validation','اطلاعات ارسالی معتبر نیست.',$errors);

        $duplicate = $this->duplicatePolicy->evaluate($form, $data);
        if ($duplicate->blocksDuplicate()) return $this->duplicateError($form, $duplicate, false);

        $now = current_time('mysql', true);
        $status = $draft ? 'draft' : 'new';
        $tracking = $this->trackingCode();
        $plainToken = wp_generate_password(48, false, false);
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        try {
        $id = $this->submissions->create([
            'form_slug'=>$slug,
            'status'=>$status,
            'tracking_code'=>$tracking,
            'edit_token_hash'=>hash('sha256',$plainToken),
            'user_id'=>get_current_user_id(),
            'data_json'=>wp_json_encode($data, JSON_UNESCAPED_UNICODE),
            'ip_hash'=>$this->ipHash(),
            'user_agent'=>sanitize_text_field(substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,500)),
            'is_locked'=>(!$draft && !empty($form['settings']['lock_after_submit'])) ? 1 : 0,
            'locked_at'=>(!$draft && !empty($form['settings']['lock_after_submit'])) ? $now : null,
            'edit_request_status'=>'',
            ...$this->duplicateMetadata($duplicate),
            'created_at'=>$now,
            'updated_at'=>$now,
        ]);
        if (!$id) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('database','ذخیره اطلاعات انجام نشد.');
        }
        $collision = $this->syncDuplicateFingerprint($form, $id, $duplicate);
        if ($collision instanceof WP_Error) {
            $wpdb->query('ROLLBACK');
            return $collision;
        }
        $duplicate = $collision ?? $duplicate;
        $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }

        $uploaded = $this->files->process($form, $id);
        $this->submissions->replaceValues($id, $data);
        $this->submissions->audit($id, $slug, 'submission.api_created', ['status'=>$status]);

        $storage = (string)($form['settings']['storage'] ?? $form['storage'] ?? 'shared');
        if ($storage === 'dedicated') $this->dedicated->mirror($slug,$id,$data);

        $this->events->dispatch(new SubmissionCreated($id,$slug,$data));
        $row = $this->submissions->find($id) ?: [];
        $actionErrors = [];
        $actionRedirect = '';
        $this->rememberRedirect($actionRedirect, $this->emitActionEvent('submission.created', $form, $id, $data, $row, $actionErrors));
        $this->rememberRedirect($actionRedirect, $this->emitActionEvent(
            $draft ? 'submission.draft_saved' : 'submission.submitted',
            $form,
            $id,
            $data,
            $row,
            $actionErrors
        ));
        if (!$draft && !empty($row['is_locked'])) {
            $this->rememberRedirect($actionRedirect, $this->emitActionEvent('submission.locked', $form, $id, $data, $row, $actionErrors));
        }

        return [
            'submission_id'=>$id,
            'status'=>$status,
            'tracking_code'=>$tracking,
            'edit_token'=>$plainToken,
            'uploaded'=>$uploaded,
            'files'=>$this->publicFiles($id),
            'action_errors'=>$actionErrors,
            'redirect_url'=>$actionRedirect,
            'is_duplicate'=>$duplicate->isDuplicate() && $duplicate->allowsDuplicate(),
            'duplicate_of_submission_id'=>$duplicate->isDuplicate() && $duplicate->allowsDuplicate() ? (int)$duplicate->duplicateSubmissionId : 0,
            'locked'=>(!$draft && !empty($form['settings']['lock_after_submit'])),
        ];
    }

    public function resolvedForm(string $slug): array
    {
        $form = $this->registry->get($slug)->toArray();
        $overrides = $this->forms->overrides($slug);
        $adminSettings = $this->forms->adminSettings($slug);

        if (!empty($overrides['fields']) && is_array($overrides['fields'])) {
            foreach ($form['steps'] as &$step) {
                foreach ($step['items'] as &$field) {
                    $this->applyFieldOverrides($field, $overrides['fields']);
                }
            }
            unset($step,$field);
        }
        if (!empty($overrides['title'])) $form['title'] = sanitize_text_field((string)$overrides['title']);
        if (!empty($overrides['description'])) $form['description'] = sanitize_textarea_field((string)$overrides['description']);
        if (!empty($overrides['steps']) && is_array($overrides['steps'])) {
            foreach ($form['steps'] as &$step) {
                $stepOverride = $overrides['steps'][$step['key']] ?? null;
                if (!is_array($stepOverride)) continue;
                if (array_key_exists('template',$stepOverride)) $step['template'] = (string)$stepOverride['template'];
                if (!empty($stepOverride['title'])) $step['title'] = sanitize_text_field((string)$stepOverride['title']);
                if (array_key_exists('description',$stepOverride)) $step['description'] = sanitize_textarea_field((string)$stepOverride['description']);
            }
            unset($step);
        }

        $form['settings'] = array_replace_recursive($form['settings'], $adminSettings);
        if (!empty($adminSettings['workflow']) && is_array($adminSettings['workflow'])) {
            $form['workflow'] = $adminSettings['workflow'];
        }
        if (array_key_exists('actions', $adminSettings) && is_array($adminSettings['actions'])) {
            $form['actions'] = $adminSettings['actions'];
        }
        return $form;
    }

    private function applyFieldOverrides(array &$field, array $overrides, string $prefix = ''): void
    {
        $path = $prefix === '' ? (string)$field['name'] : $prefix . '.' . $field['name'];
        $override = $overrides[$path] ?? ($prefix === '' ? ($overrides[$field['name']] ?? null) : null);
        if (is_array($override)) {
            $allowed = ['label','description','placeholder','required','options','max_files','max_size_mb','accept','calendar','select_mode','searchable','search_threshold'];
            foreach ($allowed as $key) {
                if (array_key_exists($key,$override)) $field[$key]=$override[$key];
            }
        }
        if (($field['type'] ?? '') === 'repeater') {
            foreach ($field['fields'] as &$child) $this->applyFieldOverrides($child,$overrides,$path);
            unset($child);
        }
    }

    private function resolveExisting(array $form): ?array
    {
        $id = (int)($_POST['_afe_submission_id'] ?? 0);
        if (!$id) return null;
        $row = $this->submissions->find($id);
        if (!$row || $row['form_slug'] !== $form['slug']) return null;
        return $row;
    }

    public function adminCanEdit(string $slug): bool
    {
        return current_user_can('afe_edit_submissions') && $this->formAccess->can($slug,FormAccess::EDIT);
    }

    public function requestEditHttp(): array|WP_Error
    {
        $slug=sanitize_key(wp_unslash($_POST['afe_form_slug']??''));
        $id=(int)($_POST['submission_id']??0);
        if (!$slug || !$id || !$this->registry->has($slug)) return new WP_Error('invalid','درخواست ویرایش معتبر نیست.');
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce']??'')),'afe_edit_request_'.$slug)) return new WP_Error('nonce','اعتبار درخواست منقضی شده است. صفحه را تازه‌سازی کنید.');
        $form=$this->resolvedForm($slug);
        if (empty($form['settings']['show_edit_request_button'])) return new WP_Error('disabled','درخواست ویرایش برای این فرم غیرفعال است.');
        $row=$this->submissions->find($id);
        if (!$row || (string)$row['form_slug']!==$slug) return new WP_Error('not_found','ثبت پیدا نشد.');
        if (!$this->canApplicantAccess($form,$row)) return new WP_Error('forbidden','اجازه دسترسی به این ثبت را ندارید.');
        if (empty($row['is_locked'])) return new WP_Error('unlocked','این ثبت در حال حاضر برای ویرایش باز است.');
        if ((string)($row['edit_request_status']??'')==='pending') return ['message'=>'درخواست ویرایش قبلاً ثبت شده و در انتظار بررسی است.','status'=>'pending'];
        $now=current_time('mysql',true);
        $reason=sanitize_textarea_field(wp_unslash($_POST['reason']??''));
        $this->submissions->update($id,[
            'edit_request_status'=>'pending',
            'edit_request_message'=>$reason,
            'edit_requested_at'=>$now,
            'edit_request_updated_at'=>$now,
        ]);
        $this->submissions->audit($id,$slug,'edit_request.created',['reason'=>$reason]);
        $row = $this->submissions->find($id) ?: [];
        $actionErrors = [];
        $this->emitActionEvent('edit_request.created', $form, $id, $this->rowData($row), $row, $actionErrors);
        return ['message'=>'درخواست ویرایش با موفقیت ثبت شد.','status'=>'pending','action_errors'=>$actionErrors];
    }

    /**
     * Emits a canonical lifecycle event after an admin-side mutation. Action
     * failures are returned for observability but never roll back the mutation.
     *
     * @return array<string, string>
     */
    public function emitSubmissionEvent(string $eventKey, int $submissionId): array
    {
        $row = $this->submissions->find($submissionId, true);
        if (!$row || !$this->registry->has((string)$row['form_slug'])) return [];

        $form = $this->resolvedForm((string)$row['form_slug']);
        $errors = [];
        $this->emitActionEvent($eventKey, $form, $submissionId, $this->rowData($row), $row, $errors);
        return $errors;
    }

    private function emitActionEvent(string $eventKey, array $form, int $submissionId, array $data, array $row, array &$errors): string
    {
        $runtime = new ActionRuntime();
        $queue = [$eventKey];
        $processed = [];
        $redirect = '';
        $iterations = 0;

        while ($queue !== [] && $iterations < 12) {
            $currentEvent = array_shift($queue);
            if (!is_string($currentEvent) || $currentEvent === '' || isset($processed[$currentEvent])) continue;
            $processed[$currentEvent] = true;
            $iterations++;

            if ($currentEvent !== $eventKey) {
                $fresh = $this->submissions->find($submissionId, true);
                if (is_array($fresh)) {
                    $row = $fresh;
                    $data = $this->rowData($fresh);
                }
            }

            $this->events->dispatch($currentEvent, $submissionId, (string)$form['slug'], $data, $row);
            $result = $this->actions->runWithResult(
                (array)($form['actions'] ?? []),
                new ActionContext(
                    $submissionId,
                    (string)$form['slug'],
                    $data,
                    $row,
                    $currentEvent,
                    (string)($form['title'] ?? ''),
                    $this->fieldKeys($form),
                    $form,
                    $runtime
                )
            );
            foreach ($result->errors as $actionKey => $message) {
                $errors[$currentEvent . ':' . $actionKey] = $message;
            }
            if ($redirect === '' && $result->redirectUrl() !== '') $redirect = $result->redirectUrl();
            foreach ($result->emittedEvents() as $followUp) {
                if (!isset($processed[$followUp]) && !in_array($followUp, $queue, true)) $queue[] = $followUp;
            }
        }

        return $redirect;
    }

    private function rememberRedirect(string &$current, string $candidate): void
    {
        if ($current === '' && $candidate !== '') $current = $candidate;
    }

    /** @return list<string> */
    private function fieldKeys(array $form): array
    {
        $keys = [];
        foreach ((array)($form['steps'] ?? []) as $step) {
            foreach ((array)($step['items'] ?? []) as $field) {
                $this->collectFieldKeys((array)$field, '', $keys);
            }
        }
        return array_values(array_unique($keys));
    }

    /** @param list<string> $keys */
    private function collectFieldKeys(array $field, string $prefix, array &$keys): void
    {
        $name = (string)($field['name'] ?? '');
        if ($name === '' || ($field['type'] ?? '') === 'html') return;
        $path = $prefix === '' ? $name : $prefix . '.' . $name;
        $keys[] = $path;
        if (($field['type'] ?? '') === 'repeater') {
            foreach ((array)($field['fields'] ?? []) as $child) {
                $this->collectFieldKeys((array)$child, $path, $keys);
            }
        }
    }

    /** @return array{is_duplicate:int,duplicate_of_submission_id:int} */
    private function duplicateMetadata(DuplicateDecision $decision): array
    {
        return [
            'is_duplicate'=>$decision->isDuplicate() && $decision->allowsDuplicate() ? 1 : 0,
            'duplicate_of_submission_id'=>$decision->isDuplicate() && $decision->allowsDuplicate()
                ? (int)$decision->duplicateSubmissionId
                : 0,
        ];
    }

    /**
     * Synchronize the unique fingerprint inside the caller's transaction.
     * A concurrent insert can turn a preflight-unique submission into a duplicate;
     * allow-mode is converted into an explicit duplicate marker, while blocking
     * modes return an error so the caller can roll the transaction back.
     */
    private function syncDuplicateFingerprint(array $form, int $submissionId, DuplicateDecision $decision): DuplicateDecision|WP_Error|null
    {
        $formSlug = (string)$form['slug'];
        $existingFingerprint = $this->duplicateRepository->fingerprintForSubmission($submissionId);

        // If this Submission already owns exactly the same fingerprint, keep the
        // row in place. Besides avoiding unnecessary churn, this preserves the
        // canonical owner while allow-mode dependants point to it.
        if (
            $decision->enabled
            && !$decision->isDuplicate()
            && $existingFingerprint !== ''
            && hash_equals($existingFingerprint, $decision->fingerprint)
        ) {
            return null;
        }

        if ($existingFingerprint !== '') {
            if ($decision->enabled) {
                $this->duplicateRepository->releaseAndPromote($formSlug, $submissionId, $existingFingerprint);
            } else {
                $this->duplicateRepository->releaseBySubmission($submissionId);
            }
        }

        if (!$decision->enabled || ($decision->isDuplicate() && $decision->allowsDuplicate())) return null;
        if ($this->duplicateRepository->reserve($formSlug, $submissionId, $decision->fingerprint)) return null;

        $duplicateId = $this->duplicateRepository->find($formSlug, $decision->fingerprint, $submissionId);
        if ($duplicateId === null) return new WP_Error('duplicate_race','رزرو شناسه تکراری انجام نشد. دوباره تلاش کنید.');

        $collision = new DuplicateDecision(true, $decision->fingerprint, $duplicateId, $decision->behavior, $decision->message);
        if ($collision->allowsDuplicate()) {
            $this->submissions->update($submissionId, $this->duplicateMetadata($collision));
            return $collision;
        }
        return $this->duplicateError($form, $collision);
    }

    private function duplicateError(array $form, DuplicateDecision $decision, bool $allowReferenceUrl = true): WP_Error
    {
        $data = ['duplicate'=>true];
        if ($allowReferenceUrl && $decision->behavior === 'reference' && $decision->duplicateSubmissionId) {
            $row = $this->submissions->find((int)$decision->duplicateSubmissionId);
            if ($row) {
                $url = $this->duplicateReferenceUrl($form, $row);
                if ($url !== '') $data['edit_url'] = $url;
            }
        }
        return new WP_Error('duplicate', $decision->message, $data);
    }

    private function duplicateReferenceUrl(array $form, array $row): string
    {
        if (!$this->canApplicantAccess($form, $row)) return '';
        $base = remove_query_arg(['afe_edit','afe_tracking','afe_submission'], wp_get_referer() ?: home_url('/'));
        $modes = (array)($form['settings']['editing_modes'] ?? []);
        if (in_array('wordpress',$modes,true) && is_user_logged_in() && (int)$row['user_id']===get_current_user_id() && get_current_user_id()>0) {
            return add_query_arg('afe_submission', (int)$row['id'], $base);
        }
        $token = sanitize_text_field(wp_unslash($_POST['_afe_edit_token'] ?? ''));
        if (in_array('link',$modes,true) && $token !== '' && hash_equals((string)$row['edit_token_hash'], hash('sha256',$token))) {
            return add_query_arg('afe_edit', rawurlencode($token), $base);
        }
        $tracking = sanitize_text_field(wp_unslash($_POST['_afe_tracking'] ?? ''));
        if (in_array('tracking',$modes,true) && $tracking !== '' && hash_equals((string)$row['tracking_code'],$tracking)) {
            return add_query_arg('afe_tracking', rawurlencode($tracking), $base);
        }
        return '';
    }

    public function updateSubmissionDataAdmin(int $submissionId, array $data): bool|WP_Error
    {
        $row = $this->submissions->find($submissionId);
        if (!$row || !$this->registry->has((string)$row['form_slug'])) return new WP_Error('not_found','ثبت پیدا نشد.');
        $form = $this->resolvedForm((string)$row['form_slug']);
        $decision = $this->duplicatePolicy->evaluate($form, $data, $submissionId);
        if ($decision->blocksDuplicate()) return $this->duplicateError($form, $decision, false);

        global $wpdb;
        $wpdb->query('START TRANSACTION');
        try {
            if (!$this->submissions->update($submissionId,[
                'data_json'=>wp_json_encode($data,JSON_UNESCAPED_UNICODE),
                'updated_at'=>current_time('mysql',true),
                ...$this->duplicateMetadata($decision),
            ])) throw new \RuntimeException('submission update failed');
            $collision = $this->syncDuplicateFingerprint($form,$submissionId,$decision);
            if ($collision instanceof WP_Error) {
                $wpdb->query('ROLLBACK');
                return $collision;
            }
            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
        $this->submissions->replaceValues($submissionId,$data);
        $this->submissions->audit($submissionId,(string)$row['form_slug'],'submission.updated',['source'=>'admin']);
        $this->emitSubmissionEvent('submission.updated',$submissionId);
        return true;
    }

    public function trashSubmission(int $submissionId, int $userId): bool
    {
        $row = $this->submissions->find($submissionId);
        if (!$row) return false;
        $form = $this->registry->has((string)$row['form_slug'])
            ? $this->resolvedForm((string)$row['form_slug'])
            : null;
        $duplicateEnabled = $form !== null && $this->duplicatePolicy->config($form)['enabled'];

        global $wpdb;
        $wpdb->query('START TRANSACTION');
        try {
            if (!$this->submissions->trash($submissionId,$userId)) { $wpdb->query('ROLLBACK'); return false; }
            if ($duplicateEnabled) {
                $this->duplicateRepository->releaseAndPromote((string)$row['form_slug'], $submissionId);
            } else {
                $this->duplicateRepository->releaseBySubmission($submissionId);
            }
            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
        $this->submissions->audit($submissionId,(string)$row['form_slug'],'submission.trashed');
        $this->emitSubmissionEvent('submission.trashed',$submissionId);
        return true;
    }

    public function restoreSubmission(int $submissionId): bool|WP_Error
    {
        $row = $this->submissions->find($submissionId,true);
        if (!$row || empty($row['trashed_at']) || !$this->registry->has((string)$row['form_slug'])) return false;
        $form = $this->resolvedForm((string)$row['form_slug']);
        $data = $this->rowData($row);
        $decision = $this->duplicatePolicy->evaluate($form,$data,$submissionId);
        if ($decision->blocksDuplicate()) return new WP_Error('duplicate_restore',$decision->message);

        global $wpdb;
        $wpdb->query('START TRANSACTION');
        try {
            if (!$this->submissions->restore($submissionId)) { $wpdb->query('ROLLBACK'); return false; }
            $this->submissions->update($submissionId,$this->duplicateMetadata($decision));
            $collision = $this->syncDuplicateFingerprint($form,$submissionId,$decision);
            if ($collision instanceof WP_Error) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('duplicate_restore',$collision->get_error_message());
            }
            $wpdb->query('COMMIT');
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
        $this->submissions->audit($submissionId,(string)$row['form_slug'],'submission.restored');
        $this->emitSubmissionEvent('submission.restored',$submissionId);
        return true;
    }

    public function retryActionLog(int $logId): array|WP_Error
    {
        $log = $this->actionExecutions->find($logId);
        if (!$log) return new WP_Error('action_log_not_found','Action Log پیدا نشد.');
        if ((string)($log['status'] ?? '') !== 'failed') return new WP_Error('action_not_failed','فقط Action ناموفق قابل Retry است.');

        $submissionId = (int)($log['submission_id'] ?? 0);
        $row = $this->submissions->find($submissionId, true);
        if (!$row || !empty($row['trashed_at'])) return new WP_Error('submission_unavailable','Submission فعال برای Retry در دسترس نیست.');
        $slug = (string)($row['form_slug'] ?? '');
        if (!$this->registry->has($slug)) return new WP_Error('form_unavailable','تعریف فرم برای Retry در دسترس نیست.');
        $form = $this->resolvedForm($slug);
        $data = $this->rowData($row);

        try {
            $result = $this->actions->retry(
                (array)($form['actions'] ?? []),
                new ActionContext(
                    $submissionId,
                    $slug,
                    $data,
                    $row,
                    (string)($log['event_key'] ?? ''),
                    (string)($form['title'] ?? ''),
                    $this->fieldKeys($form),
                    $form
                ),
                (string)($log['action_key'] ?? '')
            );
        } catch (\Throwable $e) {
            return new WP_Error('action_retry_rejected',$e->getMessage());
        }

        if ($result->errors !== []) {
            return new WP_Error('action_retry_failed', implode(' | ', array_values($result->errors)));
        }

        $followUpErrors = [];
        foreach ($result->emittedEvents() as $followUp) {
            $this->emitActionEvent($followUp, $form, $submissionId, $data, $row, $followUpErrors);
        }
        $this->submissions->audit($submissionId, $slug, 'action.retried', [
            'log_id'=>$logId,
            'action_key'=>(string)($log['action_key'] ?? ''),
            'event_key'=>(string)($log['event_key'] ?? ''),
            'follow_up_errors'=>$followUpErrors,
        ]);

        return [
            'message'=>'Action با موفقیت دوباره اجرا شد.',
            'redirect_url'=>$result->redirectUrl(),
            'follow_up_errors'=>$followUpErrors,
        ];
    }

    private function rowData(array $row): array
    {
        if (isset($row['data']) && is_array($row['data'])) return $row['data'];
        $decoded = json_decode((string)($row['data_json'] ?? '{}'), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function canApplicantAccess(array $form,array $row): bool
    {
        $modes=(array)($form['settings']['editing_modes']??[]);
        if (in_array('wordpress',$modes,true) && is_user_logged_in() && (int)$row['user_id']===get_current_user_id() && get_current_user_id()>0) return true;
        if (in_array('link',$modes,true)) {
            $token=sanitize_text_field(wp_unslash($_POST['_afe_edit_token']??''));
            if ($token!=='' && hash_equals((string)$row['edit_token_hash'],hash('sha256',$token))) return true;
        }
        if (in_array('tracking',$modes,true)) {
            $tracking=sanitize_text_field(wp_unslash($_POST['_afe_tracking']??''));
            if ($tracking!=='' && hash_equals((string)$row['tracking_code'],$tracking)) return true;
        }
        return false;
    }

    private function canEdit(array $form, array $row): bool
    {
        if ($this->adminCanEdit((string)$form['slug'])) return true;
        if (!empty($row['is_locked'])) return false;
        if (empty($form['settings']['editing_enabled'])) return false;
        $modes = (array)($form['settings']['editing_modes'] ?? []);

        if (in_array('wordpress',$modes,true) && is_user_logged_in() && (int)$row['user_id'] === get_current_user_id() && get_current_user_id()>0) return true;

        if (in_array('link',$modes,true)) {
            $token = sanitize_text_field(wp_unslash($_POST['_afe_edit_token'] ?? ''));
            if ($token !== '' && hash_equals((string)$row['edit_token_hash'], hash('sha256',$token))) return true;
        }

        if (in_array('tracking',$modes,true)) {
            $tracking = sanitize_text_field(wp_unslash($_POST['_afe_tracking'] ?? ''));
            if ($tracking !== '' && hash_equals((string)$row['tracking_code'],$tracking)) return true;
        }
        return false;
    }

    private function sanitizeBySchema(array $form, array $data): array
    {
        $clean=[];
        foreach ($form['steps'] as $step) {
            foreach ($step['items'] as $field) {
                if (($field['type']??'')==='html' || ($field['type']??'')==='file') continue;
                $name=$field['name'];
                if (!array_key_exists($name,$data)) continue;
                if (($field['type']??'')==='repeater') {
                    $rows=[];
                    foreach ((array)$data[$name] as $row) {
                        if (!is_array($row)) continue;
                        $one=[];
                        foreach ($field['fields']??[] as $child) {
                            if (($child['type']??'')==='html' || !array_key_exists($child['name'],$row)) continue;
                            $one[$child['name']]=$this->sanitizeScalar($child,$row[$child['name']]);
                        }
                        if (array_filter($one, static fn($v)=>$v!==''&&$v!==null)) $rows[]=$one;
                    }
                    $clean[$name]=array_slice($rows,0,(int)($field['max']??50));
                } else {
                    $clean[$name]=$this->sanitizeScalar($field,$data[$name]);
                }
            }
        }
        return $clean;
    }

    private function sanitizeScalar(array $field, mixed $value): mixed
    {
        if (is_array($value)) return array_map('sanitize_text_field',$value);
        $value = Validators::latinDigits((string)$value);
        $normalizePrefix=trim((string)($field['normalize_input_prefix']??''));
        if ($normalizePrefix !== '' && str_starts_with(strtoupper($value),strtoupper($normalizePrefix))) {
            $value=substr($value,strlen($normalizePrefix));
        }
        return match ($field['type']??'text') {
            'textarea' => sanitize_textarea_field($value),
            'email' => sanitize_email($value),
            'url' => esc_url_raw($value),
            'number' => is_numeric($value) ? $value : '',
            default => sanitize_text_field($value),
        };
    }

    /**
     * Public-safe file rows for front-end upload state synchronization.
     * Never expose absolute server paths through the AJAX response.
     */
    private function publicFiles(int $submissionId): array
    {
        return array_map(static fn(array $row): array => [
            'id'=>(int)($row['id'] ?? 0),
            'field_key'=>(string)($row['field_key'] ?? ''),
            'attachment_id'=>(int)($row['attachment_id'] ?? 0),
            'url'=>(string)($row['url'] ?? ''),
            'mime'=>(string)($row['mime'] ?? ''),
            'size'=>(int)($row['size'] ?? 0),
            'original_name'=>(string)($row['original_name'] ?? ''),
        ], $this->submissions->files($submissionId));
    }

    private function trackingCode(): string
    {
        do {
            $code = strtoupper(wp_generate_password(18,false,false));
            $code = preg_replace('/[^A-Z0-9]/','',$code) ?: strtoupper(bin2hex(random_bytes(9)));
        } while ($this->submissions->findByTracking($code));
        return $code;
    }

    private function ipHash(): string
    {
        return hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??''),wp_salt('auth'));
    }
}
