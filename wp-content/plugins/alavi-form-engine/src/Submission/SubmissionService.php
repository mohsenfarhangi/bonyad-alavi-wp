<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Submission;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionManager;
use BonyadAlavi\FormEngine\Database\DedicatedStorage;
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
        private readonly FormAccess $formAccess
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

        $now = current_time('mysql', true);
        $status = $draft ? 'draft' : 'new';
        $plainToken = '';
        $actionEvent = $existing ? 'updated' : 'created';

        if ($existing) {
            $id = (int)$existing['id'];
            if (!$draft && !in_array((string)$existing['status'], ['draft','revision','new'], true)) {
                $status = (string)$existing['status'];
            }
            $updateRow=[
                'status'=>$status,
                'data_json'=>wp_json_encode($data, JSON_UNESCAPED_UNICODE),
                'updated_at'=>$now,
            ];
            if (!$draft && !empty($form['settings']['lock_after_submit'])) {
                $updateRow['is_locked']=1;
                $updateRow['locked_at']=$now;
                $updateRow['edit_request_status']='';
                $updateRow['edit_request_message']=null;
                $updateRow['edit_requested_at']=null;
                $updateRow['edit_request_updated_at']=$now;
            }
            $this->submissions->update($id, $updateRow);
            $tracking = (string)$existing['tracking_code'];
            $this->submissions->audit($id, $slug, 'submission.updated', ['status'=>$status]);
            $this->events->dispatch(new SubmissionUpdated($id,$slug,$data,$status));
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
                'created_at'=>$now,
                'updated_at'=>$now,
            ]);
            if (!$id) return new WP_Error('database','ذخیره اطلاعات انجام نشد.');
            $this->submissions->audit($id, $slug, 'submission.created', ['status'=>$status]);
            $this->events->dispatch(new SubmissionCreated($id,$slug,$data));
        }

        $uploaded = $this->files->process($form, $id);
        $this->submissions->replaceValues($id, $data);

        $storage = (string)($form['settings']['storage'] ?? $form['storage'] ?? 'shared');
        if ($storage === 'dedicated') $this->dedicated->mirror($slug,$id,$data);

        $row = $this->submissions->find($id) ?: [];
        $actionErrors = [];
        if (!$draft) {
            $actionErrors = $this->actions->run($form['actions'] ?? [], new ActionContext($id,$slug,$data,$row,$actionEvent));
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

        $now = current_time('mysql', true);
        $status = $draft ? 'draft' : 'new';
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
            'created_at'=>$now,
            'updated_at'=>$now,
        ]);
        if (!$id) return new WP_Error('database','ذخیره اطلاعات انجام نشد.');

        $uploaded = $this->files->process($form, $id);
        $this->submissions->replaceValues($id, $data);
        $this->submissions->audit($id, $slug, 'submission.api_created', ['status'=>$status]);

        $storage = (string)($form['settings']['storage'] ?? $form['storage'] ?? 'shared');
        if ($storage === 'dedicated') $this->dedicated->mirror($slug,$id,$data);

        $this->events->dispatch(new SubmissionCreated($id,$slug,$data));
        $row = $this->submissions->find($id) ?: [];
        $actionErrors = $draft ? [] : $this->actions->run(
            $form['actions'] ?? [],
            new ActionContext($id,$slug,$data,$row,'created')
        );

        return [
            'submission_id'=>$id,
            'status'=>$status,
            'tracking_code'=>$tracking,
            'edit_token'=>$plainToken,
            'uploaded'=>$uploaded,
            'files'=>$this->publicFiles($id),
            'action_errors'=>$actionErrors,
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
        return ['message'=>'درخواست ویرایش با موفقیت ثبت شد.','status'=>'pending'];
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
