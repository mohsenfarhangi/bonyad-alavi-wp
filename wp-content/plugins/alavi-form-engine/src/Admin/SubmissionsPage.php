<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Admin;

use BonyadAlavi\FormEngine\Core\Capabilities;
use BonyadAlavi\FormEngine\Core\FormAccess;
use BonyadAlavi\FormEngine\DataSource\DataSourceManager;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Localization\LocaleDateService;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use BonyadAlavi\FormEngine\Submission\SubmissionService;

final class SubmissionsPage
{
    private FormDataPresenter $presenter;
    /** @var array<string,array> */
    private array $formCache=[];

    public function __construct(
        private readonly FormRegistry $registry,
        private readonly SubmissionRepository $repo,
        private readonly SubmissionService $service,
        DataSourceManager $sources,
        private readonly FormAccess $access,
        private readonly LocaleDateService $dates
    ) {
        $this->presenter=new FormDataPresenter($sources,$dates);
    }

    public function render(): void
    {
        $action=sanitize_key(wp_unslash($_GET['action']??''));
        $id=(int)($_GET['submission']??0);
        if ($action==='export_list') { $this->exportList(); return; }
        if ($id && in_array($action,['print','pdf','excel'],true)) { $this->export($id,$action); return; }
        if (!current_user_can(Capabilities::VIEW_SUBMISSIONS)) wp_die('دسترسی کافی ندارید.');
        if ($id && $action==='view') { $this->detail($id); return; }
        $this->listing();
    }

    private function listing(): void
    {
        if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['afe_list_action'])) {
            $this->handleTrashPost();
            return;
        }
        $view=sanitize_key(wp_unslash($_GET['view']??''));
        $trashView=$view==='trash';
        $filters=[
            'form_slug'=>sanitize_key(wp_unslash($_GET['form_slug']??'')),
            'status'=>sanitize_key(wp_unslash($_GET['status']??'')),
            'q'=>sanitize_text_field(wp_unslash($_GET['q']??'')),
            'from'=>sanitize_text_field(wp_unslash($_GET['from']??'')),
            'to'=>sanitize_text_field(wp_unslash($_GET['to']??'')),
            'trash'=>$trashView,
        ];
        $accessible=$this->access->accessibleForms($this->registry,FormAccess::VIEW);
        if($filters['form_slug']!=='' && !isset($accessible[$filters['form_slug']])) wp_die('به این فرم دسترسی ندارید.');
        $filters['form_slugs']=array_keys($accessible);
        $filters['from_utc']=$this->dates->filterBoundary($filters['from'],false)??'';
        $filters['to_utc']=$this->dates->filterBoundary($filters['to'],true)??'';
        $page=max(1,(int)($_GET['paged']??1));
        $result=$this->repo->list($filters,$page,30);
        $statuses=$this->defaultStatuses();

        echo '<div class="wrap afe-admin-wrap"><h1>اطلاعات ارسالی</h1>';
        if(isset($_GET['trash_updated'])) echo '<div class="notice notice-success is-dismissible"><p>عملیات زباله‌دان انجام شد.</p></div>';
        if(isset($_GET['deleted'])) echo '<div class="notice notice-success is-dismissible"><p>ثبت و اطلاعات وابسته به‌صورت دائمی حذف شد.</p></div>';
        echo '<nav class="nav-tab-wrapper afe-submission-tabs"><a class="nav-tab '.(!$trashView?'nav-tab-active':'').'" href="'.esc_url(admin_url('admin.php?page=alavi-form-engine-submissions')).'">اطلاعات فعال</a><a class="nav-tab '.($trashView?'nav-tab-active':'').'" href="'.esc_url(admin_url('admin.php?page=alavi-form-engine-submissions&view=trash')).'">زباله‌دان</a></nav>';
        echo '<form class="afe-filter-bar" method="get"><input type="hidden" name="page" value="alavi-form-engine-submissions">'.($trashView?'<input type="hidden" name="view" value="trash">':'');
        echo '<input name="q" value="'.esc_attr($filters['q']).'" placeholder="کد رهگیری یا جستجو در داده‌ها">';
        echo '<select name="form_slug"><option value="">همه فرم‌ها</option>';
        foreach($accessible as $slug=>$form) echo '<option value="'.esc_attr($slug).'" '.selected($filters['form_slug'],$slug,false).'>'.esc_html($this->formTitle($slug)).'</option>';
        echo '</select><select name="status"><option value="">همه وضعیت‌ها</option>';
        foreach($statuses as $k=>$v) echo '<option value="'.esc_attr($k).'" '.selected($filters['status'],$k,false).'>'.esc_html($v).'</option>';
        echo '</select>'.$this->dateFilterInput('from',$filters['from'],'از تاریخ').$this->dateFilterInput('to',$filters['to'],'تا تاریخ').'<button class="button button-primary">فیلتر</button>';
        $exportForms=$this->access->accessibleForms($this->registry,FormAccess::EXPORT);
        if(!$trashView && current_user_can(Capabilities::EXPORT) && $exportForms) {
            $exportUrl=add_query_arg(array_filter([
                'page'=>'alavi-form-engine-submissions','action'=>'export_list','q'=>$filters['q'],
                'form_slug'=>$filters['form_slug'],'status'=>$filters['status'],'from'=>$filters['from'],'to'=>$filters['to'],
            ],static fn($v)=>$v!==''),admin_url('admin.php'));
            echo '<a class="button" href="'.esc_url($exportUrl).'">Excel لیست</a>';
        }
        echo '</form>';

        echo '<div class="afe-admin-card"><div class="afe-table-scroll"><table class="widefat striped"><thead><tr><th>ID</th><th>فرم</th><th>عنوان/گروه</th><th>کد رهگیری</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead><tbody>';
        if (!$result['rows']) echo '<tr><td colspan="7">موردی پیدا نشد.</td></tr>';
        foreach($result['rows'] as $row) {
            $data=$row['data']; $title=$data['group_name']??($data['full_name']??'—');
            $actions='<a class="button" href="'.esc_url(admin_url('admin.php?page=alavi-form-engine-submissions&action=view&submission='.(int)$row['id'].($trashView?'&view=trash':''))).'">مشاهده</a>';
            if($this->canDelete((string)$row['form_slug'])) {
                $actions.=$this->trashActionForm((int)$row['id'],$trashView?'restore':'trash',$trashView?'بازیابی':'انتقال به زباله‌دان',$trashView?'secondary':'delete');
                if($trashView) $actions.=$this->trashActionForm((int)$row['id'],'delete_permanently','حذف دائمی','delete',true);
            }
            echo '<tr><td>'.(int)$row['id'].'</td><td><strong>'.esc_html($this->formTitle((string)$row['form_slug'])).'</strong></td><td>'.esc_html((string)$title).'</td><td><code dir="ltr">'.esc_html($row['tracking_code']).'</code></td><td><span class="afe-status afe-status-'.esc_attr($row['status']).'">'.esc_html($statuses[$row['status']]??$row['status']).'</span>'.(!empty($row['is_duplicate'])?'<small class="afe-duplicate-badge">Duplicate از #'.(int)($row['duplicate_of_submission_id']??0).'</small>':'').'</td><td>'.esc_html($this->dates->formatUtc((string)$row['created_at'],true)).'</td><td><div class="afe-row-actions">'.$actions.'</div></td></tr>';
        }
        echo '</tbody></table></div></div>';

        if ($result['pages']>1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            for($i=1;$i<=$result['pages'];$i++) {
                $url=add_query_arg(array_merge($_GET,['paged'=>$i]),admin_url('admin.php'));
                echo '<a class="button '.($i===$page?'button-primary':'').'" href="'.esc_url($url).'">'.$i.'</a> ';
            }
            echo '</div></div>';
        }
        echo '</div>';
    }

    private function detail(int $id): void
    {
        $row=$this->repo->find($id,true);
        if (!$row) wp_die('ثبت پیدا نشد.');
        if (!$this->registry->has($row['form_slug'])) wp_die('تعریف فرم در دسترس نیست.');
        $formSlug=(string)$row['form_slug'];
        if(!$this->access->can($formSlug,FormAccess::VIEW)) wp_die('به اطلاعات این فرم دسترسی ندارید.');
        $form=$this->resolvedForm($formSlug);

        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $this->handleDetailPost($row,$form);
            return;
        }
        $trashed=!empty($row['trashed_at']);
        $editing=!$trashed && !empty($_GET['edit']) && $this->access->can($formSlug,FormAccess::EDIT);

        echo '<div class="wrap afe-admin-wrap">';
        if (!empty($_GET['updated'])) echo '<div class="notice notice-success is-dismissible"><p>تغییرات ذخیره شد.</p></div>';
        if ($trashed) echo '<div class="notice notice-warning"><p><strong>این ثبت در زباله‌دان است.</strong> تا زمان بازیابی، در فرم عمومی و گزارش‌های فعال نمایش داده نمی‌شود.</p></div>';
        if (!empty($row['is_duplicate'])) echo '<div class="notice notice-info"><p><strong>این ثبت به عنوان Duplicate علامت‌گذاری شده است.</strong> ثبت مرجع: #'.(int)($row['duplicate_of_submission_id']??0).'</p></div>';
        echo '<p><a href="'.esc_url(admin_url('admin.php?page=alavi-form-engine-submissions'.($trashed?'&view=trash':''))).'">← بازگشت به فهرست</a></p>';
        echo '<div class="afe-admin-card"><div class="afe-admin-card-head"><div><h1>'.esc_html($form['title']).' — ثبت #'.(int)$id.'</h1><p>کد رهگیری: <code dir="ltr">'.esc_html($row['tracking_code']).'</code> · تاریخ ثبت: <strong>'.esc_html($this->dates->formatUtc((string)$row['created_at'],true)).'</strong></p></div><div class="afe-admin-actions">';
        if(!$trashed && $this->access->can($formSlug,FormAccess::EDIT)) {
            if($editing) echo '<a class="button" href="'.esc_url($this->detailUrl($id)).'">انصراف از ویرایش</a> ';
            else echo '<a class="button button-primary" href="'.esc_url(add_query_arg('edit','1',$this->detailUrl($id))).'">ویرایش اطلاعات</a> ';
        }
        if(!$trashed && $this->access->can($formSlug,FormAccess::EXPORT)) {
            $base=admin_url('admin.php?page=alavi-form-engine-submissions&submission='.$id.'&');
            echo '<a class="button" target="_blank" href="'.esc_url($base.'action=pdf').'">PDF / چاپ</a> <a class="button" href="'.esc_url($base.'action=excel').'">Excel</a>';
        }
        echo '</div></div></div>';

        echo '<div class="afe-admin-columns"><div>';
        if($editing) {
            echo '<form method="post" class="afe-admin-edit-form">';
            wp_nonce_field('afe_submission_edit_'.$id,'afe_submission_nonce');
            echo '<input type="hidden" name="afe_detail_action" value="edit_data">';
        }
        echo '<div class="afe-admin-card"><div class="afe-admin-card-title"><div><h2>'.($editing?'ویرایش اطلاعات':'اطلاعات ثبت‌شده').'</h2><p>'.($editing?'مقادیر را مستقیماً ویرایش و ذخیره کنید.':'اطلاعات بر اساس ساختار اصلی فرم نمایش داده می‌شود.').'</p></div></div>';
        echo '<div class="afe-table-scroll"><table class="widefat afe-submission-data-table"><tbody>';
        foreach($this->presenter->sections($form) as $section) {
            $stepTitle=(string)($section['step']['title']??'');
            echo '<tr class="afe-submission-step-row"><th colspan="2">'.esc_html($stepTitle).'</th></tr>';
            foreach($section['fields'] as $field) {
                $name=(string)$field['name'];
                if (($field['type']??'')==='file') continue;
                $value=$row['data'][$name]??'';
                echo '<tr class="afe-submission-field-row"><th><strong>'.esc_html($this->presenter->fieldLabel($field)).'</strong><small>'.esc_html($this->typeLabel((string)($field['type']??''))).'</small></th><td>';
                echo $editing ? $this->presenter->editField($field,$value,$row['data']) : $this->presenter->displayField($field,$value,$row['data']);
                echo '</td></tr>';
            }
        }
        echo '</tbody></table></div>';
        if($editing) echo '<div class="afe-admin-savebar"><button class="button button-primary button-large" type="submit">ذخیره تغییرات</button><a class="button button-large" href="'.esc_url($this->detailUrl($id)).'">انصراف</a></div>';
        echo '</div>';
        if($editing) echo '</form>';

        if($this->access->can($formSlug,FormAccess::MANAGE) && current_user_can(Capabilities::VIEW_FILES)) {
            $files=$this->repo->files($id);
            echo '<div class="afe-admin-card"><h2>فایل‌ها</h2>';
            if(!$files) echo '<p>فایلی ثبت نشده است.</p>';
            else {
                $fieldMap=$this->presenter->fieldMap($form);
                echo '<table class="widefat striped"><thead><tr><th>فیلد</th><th>نام فایل</th><th>نوع</th><th>حجم</th><th></th></tr></thead><tbody>';
                foreach($files as $file) {
                    $field=$fieldMap[$file['field_key']]??null;
                    $label=$field?$this->presenter->fieldLabel($field):(string)$file['field_key'];
                    echo '<tr><td><strong>'.esc_html($label).'</strong></td><td>'.esc_html($file['original_name']).'</td><td>'.esc_html($file['mime']).'</td><td>'.esc_html(size_format((int)$file['size'])).'</td><td><a class="button" target="_blank" rel="noopener" href="'.esc_url($file['url']).'">مشاهده فایل</a></td></tr>';
                }
                echo '</tbody></table>';
            }
            echo '</div>';
        }
        echo '</div><aside>';
        if($this->canDelete($formSlug)) {
            echo '<div class="afe-admin-card afe-danger-card"><h2>'.($trashed?'زباله‌دان':'حذف ثبت').'</h2>';
            if($trashed) {
                echo '<p>می‌توانید این ثبت را بازیابی یا برای همیشه حذف کنید.</p>'.$this->detailTrashActionForm($id,'restore','بازیابی ثبت','secondary').$this->detailTrashActionForm($id,'delete_permanently','حذف دائمی','delete',true);
            } else {
                echo '<p>حذف اولیه قابل بازیابی است و ثبت را به زباله‌دان منتقل می‌کند.</p>'.$this->detailTrashActionForm($id,'trash','انتقال به زباله‌دان','delete');
            }
            echo '</div>';
        }

        if(!$trashed && $this->access->can($formSlug,FormAccess::MANAGE) && current_user_can(Capabilities::EDIT_SUBMISSIONS)) {
            $locked=!empty($row['is_locked']);
            $requestStatus=(string)($row['edit_request_status']??'');
            echo '<div class="afe-admin-card afe-lock-admin"><h2>قفل و درخواست ویرایش</h2>';
            echo '<p><strong>وضعیت قفل:</strong> <span class="afe-status '.($locked?'afe-status-rejected':'afe-status-approved').'">'.($locked?'قفل':'باز برای ویرایش').'</span></p>';
            if($requestStatus==='pending') {
                echo '<div class="notice notice-warning inline"><p><strong>درخواست ویرایش جدید</strong></p>'.(!empty($row['edit_request_message'])?'<p>'.nl2br(esc_html((string)$row['edit_request_message'])).'</p>':'').'</div>';
                echo '<div class="afe-admin-actions"><form method="post">'; wp_nonce_field('afe_submission_edit_'.$id,'afe_submission_nonce');
                echo '<input type="hidden" name="afe_detail_action" value="approve_edit_request"><button class="button button-primary" type="submit">تأیید و باز کردن قفل</button></form>';
                echo '<form method="post">'; wp_nonce_field('afe_submission_edit_'.$id,'afe_submission_nonce');
                echo '<input type="hidden" name="afe_detail_action" value="reject_edit_request"><button class="button" type="submit">رد درخواست</button></form></div>';
            } else {
                echo '<form method="post">'; wp_nonce_field('afe_submission_edit_'.$id,'afe_submission_nonce');
                echo '<input type="hidden" name="afe_detail_action" value="'.($locked?'unlock_submission':'lock_submission').'">';
                submit_button($locked?'باز کردن قفل برای ویرایش':'قفل کردن ثبت','secondary','submit',false); echo '</form>';
            }
            echo '</div>';
        }

        if(!$trashed && $this->access->can($formSlug,FormAccess::MANAGE) && current_user_can(Capabilities::CHANGE_STATUS)) {
            echo '<div class="afe-admin-card"><h2>وضعیت</h2><form method="post">';
            wp_nonce_field('afe_submission_edit_'.$id,'afe_submission_nonce');
            echo '<input type="hidden" name="afe_detail_action" value="status"><select name="status" style="width:100%">';
            foreach($form['workflow'] as $k=>$v) echo '<option value="'.esc_attr($k).'" '.selected($row['status'],$k,false).'>'.esc_html($v).'</option>';
            echo '</select>'; submit_button('تغییر وضعیت','primary','submit',false); echo '</form></div>';
        }

        if($this->access->can($formSlug,FormAccess::MANAGE)) {
            echo '<div class="afe-admin-card"><h2>یادداشت داخلی</h2>';
            if(!$trashed && current_user_can(Capabilities::ADD_NOTES)) {
                echo '<form method="post">'; wp_nonce_field('afe_submission_edit_'.$id,'afe_submission_nonce');
                echo '<input type="hidden" name="afe_detail_action" value="note"><textarea name="note" rows="4" style="width:100%" required></textarea>';
                submit_button('ثبت یادداشت','secondary','submit',false); echo '</form><hr>';
            }
            foreach($this->repo->notes($id) as $note) {
                echo '<div class="afe-note"><strong>'.esc_html($note['display_name']?:'کاربر').'</strong><small>'.esc_html($this->dates->formatUtc((string)$note['created_at'],true)).'</small><p>'.nl2br(esc_html($note['note'])).'</p></div>';
            }
            echo '</div>';
        }
        echo '</aside></div></div>';
    }

    private function handleDetailPost(array $row,array $form): void
    {
        check_admin_referer('afe_submission_edit_'.(int)$row['id'],'afe_submission_nonce');
        $action=sanitize_key(wp_unslash($_POST['afe_detail_action']??''));
        $formSlug=(string)$row['form_slug'];
        $changed=false;
        if(in_array($action,['trash','restore','delete_permanently'],true) && $this->canDelete($formSlug)) {
            if($action==='trash') {
                $changed=$this->service->trashSubmission((int)$row['id'],get_current_user_id());
            } elseif($action==='restore') {
                $restored=$this->service->restoreSubmission((int)$row['id']);
                if(is_wp_error($restored)) wp_die(esc_html($restored->get_error_message()));
                $changed=$restored;
            }
            else { $changed=$this->repo->deletePermanently((int)$row['id']); if($changed){ wp_safe_redirect(admin_url('admin.php?page=alavi-form-engine-submissions&view=trash&deleted=1')); exit; } }
            wp_safe_redirect(add_query_arg('updated',$changed?'1':'0',$this->detailUrl((int)$row['id']))); exit;
        }
        if(!empty($row['trashed_at'])) wp_die('این ثبت در زباله‌دان است. ابتدا آن را بازیابی کنید.');
        if(in_array($action,['approve_edit_request','reject_edit_request','lock_submission','unlock_submission'],true) && $this->access->can($formSlug,FormAccess::MANAGE) && current_user_can(Capabilities::EDIT_SUBMISSIONS)) {
            $now=current_time('mysql',true);
            if($action==='approve_edit_request' || $action==='unlock_submission') {
                $this->repo->update((int)$row['id'],['is_locked'=>0,'locked_at'=>null,'edit_request_status'=>$action==='approve_edit_request'?'approved':(string)($row['edit_request_status']??''),'edit_request_updated_at'=>$now,'updated_at'=>$now]);
                $eventKey=$action==='approve_edit_request'?'edit_request.approved':'submission.unlocked';
                $this->repo->audit((int)$row['id'],$row['form_slug'],$eventKey);
                $this->service->emitSubmissionEvent($eventKey,(int)$row['id']);
                if($action==='approve_edit_request') $this->service->emitSubmissionEvent('submission.unlocked',(int)$row['id']);
            } elseif($action==='reject_edit_request') {
                $this->repo->update((int)$row['id'],['is_locked'=>1,'edit_request_status'=>'rejected','edit_request_updated_at'=>$now,'updated_at'=>$now]);
                $this->repo->audit((int)$row['id'],$row['form_slug'],'edit_request.rejected');
                $this->service->emitSubmissionEvent('edit_request.rejected',(int)$row['id']);
            } else {
                $this->repo->update((int)$row['id'],['is_locked'=>1,'locked_at'=>$now,'updated_at'=>$now]);
                $this->repo->audit((int)$row['id'],$row['form_slug'],'submission.locked');
                $this->service->emitSubmissionEvent('submission.locked',(int)$row['id']);
            }
            $changed=true;
        } elseif($action==='status' && $this->access->can($formSlug,FormAccess::MANAGE) && current_user_can(Capabilities::CHANGE_STATUS)) {
            $status=sanitize_key(wp_unslash($_POST['status']??''));
            if(isset($form['workflow'][$status]) && $status !== (string)$row['status']) {
                $this->repo->update((int)$row['id'],['status'=>$status,'updated_at'=>current_time('mysql',true)]);
                $this->repo->audit((int)$row['id'],$row['form_slug'],'submission.status_changed',['from'=>$row['status'],'to'=>$status]);
                $this->service->emitSubmissionEvent('submission.status_changed',(int)$row['id']);
                $changed=true;
            }
        } elseif($action==='note' && $this->access->can($formSlug,FormAccess::MANAGE) && current_user_can(Capabilities::ADD_NOTES)) {
            $note=sanitize_textarea_field(wp_unslash($_POST['note']??''));
            if($note!=='') {
                $this->repo->addNote((int)$row['id'],get_current_user_id(),$note);
                $this->repo->audit((int)$row['id'],$row['form_slug'],'note.created');
                $changed=true;
            }
        } elseif($action==='edit_data' && $this->access->can($formSlug,FormAccess::EDIT)) {
            $input=is_array($_POST['afe_admin_data']??null)?(array)$_POST['afe_admin_data']:[];
            $clean=$this->presenter->sanitizeSubmitted($form,$input,(array)$row['data']);
            $updated=$this->service->updateSubmissionDataAdmin((int)$row['id'],$clean);
            if(is_wp_error($updated)) wp_die(esc_html($updated->get_error_message()));
            $changed=$updated;
        }
        wp_safe_redirect(add_query_arg('updated',$changed?'1':'0',$this->detailUrl((int)$row['id']))); exit;
    }

    private function canDelete(string $formSlug): bool
    {
        return current_user_can(Capabilities::DELETE_SUBMISSIONS) && $this->access->can($formSlug,FormAccess::MANAGE);
    }

    private function trashActionForm(int $id,string $action,string $label,string $style='secondary',bool $confirmPermanent=false): string
    {
        $nonce=wp_create_nonce('afe_submission_trash_'.$id);
        $confirm=$confirmPermanent?' data-afe-confirm="این حذف دائمی است و اطلاعات، فایل‌های مدیریت‌شده، یادداشت‌ها و سوابق وابسته قابل بازیابی نخواهند بود. ادامه می‌دهید؟"':'';
        $class=$style==='delete'?'button-link-delete afe-danger-button':'button';
        return '<form method="post" class="afe-inline-action"'.$confirm.'><input type="hidden" name="afe_list_action" value="'.esc_attr($action).'"><input type="hidden" name="submission_id" value="'.(int)$id.'"><input type="hidden" name="_afe_trash_nonce" value="'.esc_attr($nonce).'"><button type="submit" class="'.esc_attr($class).'">'.esc_html($label).'</button></form>';
    }

    private function detailTrashActionForm(int $id,string $action,string $label,string $style='secondary',bool $confirmPermanent=false): string
    {
        $nonce=wp_create_nonce('afe_submission_edit_'.$id);
        $confirm=$confirmPermanent?' data-afe-confirm="این حذف دائمی است و اطلاعات، فایل‌های مدیریت‌شده، یادداشت‌ها و سوابق وابسته قابل بازیابی نخواهند بود. ادامه می‌دهید؟"':'';
        $class=$style==='delete'?'button-link-delete afe-danger-button':'button';
        return '<form method="post" class="afe-inline-action"'.$confirm.'><input type="hidden" name="afe_detail_action" value="'.esc_attr($action).'"><input type="hidden" name="afe_submission_nonce" value="'.esc_attr($nonce).'"><button type="submit" class="'.esc_attr($class).'">'.esc_html($label).'</button></form>';
    }

    private function handleTrashPost(): void
    {
        $id=(int)($_POST['submission_id']??0);
        $action=sanitize_key(wp_unslash($_POST['afe_list_action']??''));
        if(!$id || !in_array($action,['trash','restore','delete_permanently'],true)) wp_die('درخواست نامعتبر است.');
        check_admin_referer('afe_submission_trash_'.$id,'_afe_trash_nonce');
        $row=$this->repo->find($id,true);
        if(!$row) wp_die('ثبت پیدا نشد.');
        $formSlug=(string)$row['form_slug'];
        if(!$this->canDelete($formSlug)) wp_die('دسترسی حذف این فرم را ندارید.');
        $ok=false;
        if($action==='trash' && empty($row['trashed_at'])) {
            $ok=$this->service->trashSubmission($id,get_current_user_id());
        } elseif($action==='restore' && !empty($row['trashed_at'])) {
            $restored=$this->service->restoreSubmission($id);
            if(is_wp_error($restored)) wp_die(esc_html($restored->get_error_message()));
            $ok=$restored;
        }
        elseif($action==='delete_permanently' && !empty($row['trashed_at'])) { $ok=$this->repo->deletePermanently($id); }
        $view=$action==='trash'?'':'trash';
        $url=admin_url('admin.php?page=alavi-form-engine-submissions'.($view?'&view=trash':'').'&trash_updated='.($ok?'1':'0'));
        wp_safe_redirect($url); exit;
    }

    private function export(int $id,string $action): void
    {
        if(!current_user_can(Capabilities::EXPORT)) wp_die('دسترسی کافی ندارید.');
        $row=$this->repo->find($id); if(!$row) wp_die('ثبت پیدا نشد.');
        if(!$this->registry->has($row['form_slug'])) wp_die('تعریف فرم در دسترس نیست.');
        $formSlug=(string)$row['form_slug'];
        if(!$this->access->can($formSlug,FormAccess::EXPORT)) wp_die('برای خروجی این فرم دسترسی ندارید.');
        $form=$this->resolvedForm($formSlug);
        if($action==='excel') { $this->exportSubmissionExcel($id,$row,$form); return; }

        $title=$form['title'].' — ثبت #'.$id.' — '.$row['tracking_code'];
        $html=$this->printDocument($title,$row,$form,$action==='pdf');
        if($action==='pdf' && class_exists('\\Dompdf\\Dompdf')) {
            $dompdf=new \Dompdf\Dompdf(['isRemoteEnabled'=>false]);
            $dompdf->loadHtml($html,'UTF-8'); $dompdf->setPaper('A4','portrait'); $dompdf->render();
            $dompdf->stream('submission-'.$id.'.pdf',['Attachment'=>true]); exit;
        }
        echo $html; exit;
    }

    private function exportSubmissionExcel(int $id,array $row,array $form): void
    {
        nocache_headers();
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="submission-'.$id.'.xls"');
        echo "\xEF\xBB\xBF";
        echo '<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?>';
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"><Worksheet ss:Name="Submission"><Table>';
        $this->excelRow(['فرم',(string)$form['title']]);
        $this->excelRow(['کد رهگیری',(string)$row['tracking_code']]);
        $this->excelRow(['وضعیت',(string)($form['workflow'][$row['status']]??$row['status'])]);
        $this->excelRow(['تاریخ ثبت',$this->dates->formatUtc((string)$row['created_at'],true)]);
        foreach($this->presenter->sections($form) as $section) {
            $this->excelRow([(string)($section['step']['title']??''),'']);
            foreach($section['fields'] as $field) {
                $name=(string)$field['name'];
                if(($field['type']??'')==='file') {
                    $fileNames=[];
                    foreach($this->repo->filesForField($id,$name) as $file) $fileNames[]=(string)$file['original_name'];
                    $this->excelRow([$this->presenter->fieldLabel($field),$fileNames?implode("\n",$fileNames):'—']);
                    continue;
                }
                $this->excelRow([$this->presenter->fieldLabel($field),$this->presenter->plainField($field,$row['data'][$name]??'',$row['data'])]);
            }
        }
        echo '</Table></Worksheet></Workbook>'; exit;
    }

    private function printDocument(string $title,array $row,array $form,bool $autoPrint): string
    {
        $html='<!doctype html><html dir="rtl" lang="fa"><head><meta charset="utf-8"><title>'.esc_html($title).'</title><style>body{font-family:DejaVu Sans,Tahoma,Arial,sans-serif;direction:rtl;margin:30px;color:#17231f}h1{color:#0f6b4f;font-size:20px}.meta{margin-bottom:18px;color:#55635e}table{width:100%;border-collapse:collapse}th,td{border:1px solid #dce4e0;padding:9px;text-align:right;vertical-align:top}th{width:28%;background:#f4f8f6}.step th{width:auto;background:#e7f1ed;color:#0f6b4f;font-size:15px}.afe-admin-repeater-read table{font-size:11px}.afe-admin-repeater-read th{width:auto;background:#fafcfb}.afe-admin-empty{color:#8a9691}.toolbar{margin-bottom:20px}@media print{.toolbar{display:none}}</style></head><body>';
        $html.='<div class="toolbar"><button onclick="window.print()">چاپ / ذخیره PDF</button></div><h1>'.esc_html($title).'</h1><div class="meta">وضعیت: '.esc_html((string)($form['workflow'][$row['status']]??$row['status'])).' · تاریخ ثبت: '.esc_html($this->dates->formatUtc((string)$row['created_at'],true)).'</div><table>';
        foreach($this->presenter->sections($form) as $section) {
            $html.='<tr class="step"><th colspan="2">'.esc_html((string)($section['step']['title']??'')).'</th></tr>';
            foreach($section['fields'] as $field) {
                $name=(string)$field['name'];
                if(($field['type']??'')==='file') {
                    $files=$this->repo->filesForField((int)$row['id'],$name);
                    $fileNames=array_map(static fn($file)=>(string)$file['original_name'],$files);
                    $value=$fileNames?esc_html(implode('، ',$fileNames)):'<span class="afe-admin-empty">—</span>';
                    $html.='<tr><th>'.esc_html($this->presenter->fieldLabel($field)).'</th><td>'.$value.'</td></tr>';
                    continue;
                }
                $html.='<tr><th>'.esc_html($this->presenter->fieldLabel($field)).'</th><td>'.$this->presenter->displayField($field,$row['data'][$name]??'',$row['data']).'</td></tr>';
            }
        }
        $html.='</table>';
        if($autoPrint) $html.='<script>setTimeout(()=>window.print(),400);</script>';
        return $html.'</body></html>';
    }

    private function exportList(): void
    {
        if(!current_user_can(Capabilities::EXPORT)) wp_die('دسترسی کافی ندارید.');
        $filters=[
            'form_slug'=>sanitize_key(wp_unslash($_GET['form_slug']??'')),'status'=>sanitize_key(wp_unslash($_GET['status']??'')),
            'q'=>sanitize_text_field(wp_unslash($_GET['q']??'')),'from'=>sanitize_text_field(wp_unslash($_GET['from']??'')),'to'=>sanitize_text_field(wp_unslash($_GET['to']??'')),
        ];
        $accessible=$this->access->accessibleForms($this->registry,FormAccess::EXPORT);
        if($filters['form_slug']!=='' && !isset($accessible[$filters['form_slug']])) wp_die('برای خروجی این فرم دسترسی ندارید.');
        $filters['form_slugs']=array_keys($accessible);
        $filters['from_utc']=$this->dates->filterBoundary($filters['from'],false)??'';
        $filters['to_utc']=$this->dates->filterBoundary($filters['to'],true)??'';
        $rows=$this->repo->list($filters,1,5000)['rows'];
        nocache_headers(); header('Content-Type: application/vnd.ms-excel; charset=UTF-8'); header('Content-Disposition: attachment; filename="submissions-'.gmdate('Ymd-His').'.xls"');
        echo "\xEF\xBB\xBF"; echo '<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?>';
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"><Worksheet ss:Name="Submissions"><Table>';
        $this->excelRow(['ID','فرم','وضعیت','کد رهگیری','تاریخ','عنوان/گروه','اطلاعات']);
        foreach($rows as $row) {
            $form=$this->resolvedForm((string)$row['form_slug']);
            $info=[];
            foreach($this->presenter->sections($form) as $section) foreach($section['fields'] as $field) {
                if(($field['type']??'')==='file') continue;
                $name=(string)$field['name'];
                $value=$this->presenter->plainField($field,$row['data'][$name]??'',$row['data']);
                if($value!=='—') $info[]=$this->presenter->fieldLabel($field).': '.$value;
            }
            $this->excelRow([
                (string)$row['id'],(string)$form['title'],(string)($form['workflow'][$row['status']]??$row['status']),$row['tracking_code'],$this->dates->formatUtc((string)$row['created_at'],true),
                (string)($row['data']['group_name']??$row['data']['full_name']??''),implode("\n",$info),
            ]);
        }
        echo '</Table></Worksheet></Workbook>'; exit;
    }

    private function excelRow(array $cells): void
    {
        echo '<Row>';
        foreach($cells as $cell) echo '<Cell><Data ss:Type="String">'.esc_html((string)$cell).'</Data></Cell>';
        echo '</Row>';
    }

    private function resolvedForm(string $slug): array
    {
        if(!isset($this->formCache[$slug])) $this->formCache[$slug]=$this->service->resolvedForm($slug);
        return $this->formCache[$slug];
    }

    private function formTitle(string $slug): string
    {
        if(!$this->registry->has($slug)) return $slug;
        return (string)($this->resolvedForm($slug)['title']??$slug);
    }

    private function detailUrl(int $id): string
    {
        return admin_url('admin.php?page=alavi-form-engine-submissions&action=view&submission='.$id);
    }

    private function dateFilterInput(string $name,string $value,string $label): string
    {
        $jalali=$this->dates->isJalali();
        return '<label class="afe-filter-date"><span>'.esc_html($label).'</span><input type="'.($jalali?'text':'date').'" class="afe-admin-date" data-afe-admin-date="1" '.($jalali?'data-jdp ':'').'autocomplete="off" name="'.esc_attr($name).'" value="'.esc_attr($value).'" placeholder="'.esc_attr($this->dates->filterPlaceholder()).'"></label>';
    }

    private function defaultStatuses(): array
    {
        return ['draft'=>'پیش‌نویس','new'=>'جدید','review'=>'در حال بررسی','revision'=>'نیاز به اصلاح','approved'=>'تأیید شده','rejected'=>'رد شده'];
    }

    private function typeLabel(string $type): string
    {
        return ['text'=>'متن','textarea'=>'متن بلند','number'=>'عدد','select'=>'انتخاب','radio'=>'انتخاب','date'=>'تاریخ','tel'=>'تلفن','email'=>'ایمیل','url'=>'نشانی وب','repeater'=>'لیست تکرارشونده'][$type]??$type;
    }
}
