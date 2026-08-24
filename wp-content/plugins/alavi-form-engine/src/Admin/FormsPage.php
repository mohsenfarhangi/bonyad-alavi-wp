<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Admin;

use BonyadAlavi\FormEngine\Core\Capabilities;
use BonyadAlavi\FormEngine\Core\FormAccess;
use BonyadAlavi\FormEngine\Style\StyleIsolationManager;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Repository\FormRepository;
use BonyadAlavi\FormEngine\Submission\SubmissionService;

final class FormsPage
{
    public function __construct(
        private readonly FormRegistry $registry,
        private readonly FormRepository $forms,
        private readonly SubmissionService $service,
        private readonly FormAccess $access
    ) {}

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_FORMS)) wp_die('دسترسی کافی ندارید.');
        $all=$this->access->accessibleForms($this->registry,FormAccess::CONFIGURE);
        if(!$all) wp_die('هیچ فرمی برای مدیریت در اختیار شما نیست.');
        $slug=sanitize_key(wp_unslash($_GET['form'] ?? array_key_first($all) ?? ''));
        if (!$slug || !isset($all[$slug])) $slug=(string)array_key_first($all);

        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['afe_save_form'])) {
            $this->save($slug);
        }

        $form=$this->service->resolvedForm($slug);
        $row=$this->forms->row($slug);
        $storedOverrides=$this->forms->overrides($slug);
        $storedSettings=$this->forms->adminSettings($slug);
        $resolvedFlat=$this->flatten((array)($form['steps']??[]));

        echo '<div class="wrap afe-admin-wrap"><h1>مدیریت فرم‌ها</h1>';
        if (!empty($_GET['updated'])) echo '<div class="notice notice-success is-dismissible"><p>تنظیمات فرم ذخیره شد.</p></div>';
        echo '<div class="afe-admin-layout"><aside class="afe-admin-side"><h3>فرم‌های کدنویسی‌شده</h3>';
        foreach ($all as $key=>$obj) {
            $active=$key===$slug?' is-active':'';
            echo '<a class="afe-admin-form-link'.$active.'" href="'.esc_url(admin_url('admin.php?page=alavi-form-engine-forms&form='.$key)).'">'.esc_html($obj->toArray()['title']).'<code>'.esc_html($key).'</code></a>';
        }
        echo '</aside><main class="afe-admin-main">';

        echo '<div class="afe-admin-card"><div class="afe-admin-card-head"><div><h2>'.esc_html($form['title']).'</h2><p>'.esc_html($form['description']).'</p></div><code>[alavi_form id="'.esc_html($slug).'"]</code></div>';
        echo '<p class="description">تعریف PHP منبع حقیقت است. مقادیر این صفحه فقط Override می‌شوند و با بروزرسانی تعریف فرم از بین نمی‌روند.</p></div>';

        echo '<form method="post">';
        wp_nonce_field('afe_save_form_'.$slug,'afe_form_nonce');
        echo '<input type="hidden" name="afe_save_form" value="1">';

        $titleOverride=(string)($storedOverrides['title']??'');
        $descOverride=(string)($storedOverrides['description']??'');
        echo '<div class="afe-admin-card"><h2>متن و رفتار فرم</h2><div class="afe-admin-grid">';
        echo '<label>عنوان Override<input class="regular-text" name="override_title" value="'.esc_attr($titleOverride).'" placeholder="'.esc_attr($form['title']).'"></label>';
        echo '<label>کپچا<select name="captcha"><option value="custom" '.selected(($storedSettings['captcha']??$form['settings']['captcha'])==='custom',true,false).'>کپچای سمت سرور</option><option value="google" '.selected(($storedSettings['captcha']??'')==='google',true,false).'>Google reCAPTCHA</option><option value="none" '.selected(($storedSettings['captcha']??'')==='none',true,false).'>بدون کپچا</option></select></label>';
        echo '<label class="afe-span-2">توضیحات Override<textarea name="override_description" rows="3">'.esc_textarea($descOverride).'</textarea></label>';
        echo '<label>Rate limit در ۱۰ دقیقه<input type="number" min="1" max="500" name="rate_limit" value="'.esc_attr((string)($storedSettings['rate_limit']??$form['settings']['rate_limit'])).'"></label>';
        echo '<label>Storage<select name="storage"><option value="shared" '.selected(($storedSettings['storage']??$form['settings']['storage']??'shared')==='shared',true,false).'>Shared tables</option><option value="dedicated" '.selected(($storedSettings['storage']??'')==='dedicated',true,false).'>Dedicated table + shared workflow</option></select></label>';
        $isolationOverride=(string)($storedSettings['style_isolation']??'inherit');
        $codeIsolation=(string)($this->registry->get($slug)->toArray()['settings']['style_isolation']??'global');
        echo '<label>ایزوله‌سازی CSS<select name="style_isolation"><option value="inherit" '.selected($isolationOverride,'inherit',false).'>ارث‌بری ('.esc_html($codeIsolation==='global'?'تنظیم سراسری':$codeIsolation).')</option><option value="strong" '.selected($isolationOverride,'strong',false).'>قوی</option><option value="default" '.selected($isolationOverride,'default',false).'>معمولی</option><option value="disabled" '.selected($isolationOverride,'disabled',false).'>غیرفعال</option></select></label>';
        echo '<label><input type="checkbox" name="editing_enabled" value="1" '.checked(!empty($storedSettings['editing_enabled']??$form['settings']['editing_enabled']),true,false).'> امکان ویرایش توسط متقاضی</label>';
        $modes=(array)($storedSettings['editing_modes']??$form['settings']['editing_modes']);
        foreach (['wordpress'=>'حساب وردپرس','link'=>'لینک اختصاصی','tracking'=>'کد رهگیری'] as $key=>$label) {
            echo '<label><input type="checkbox" name="editing_modes[]" value="'.esc_attr($key).'" '.checked(in_array($key,$modes,true),true,false).'> '.esc_html($label).'</label>';
        }
        echo '</div></div>';

        $brandMode=sanitize_key((string)($storedSettings['brand_mark_mode']??$form['settings']['brand_mark_mode']??'default'));
        if(!in_array($brandMode,['default','image','none'],true)) $brandMode='default';
        $brandImageId=absint($storedSettings['brand_mark_image_id']??$form['settings']['brand_mark_image_id']??0);
        $brandImageUrl=esc_url_raw((string)($storedSettings['brand_mark_image_url']??$form['settings']['brand_mark_image_url']??''));
        if($brandImageId>0){
            $resolvedBrandUrl=wp_get_attachment_image_url($brandImageId,'medium');
            if(is_string($resolvedBrandUrl) && $resolvedBrandUrl!=='') $brandImageUrl=$resolvedBrandUrl;
        }
        $brandAlt=(string)($storedSettings['brand_mark_alt']??$form['settings']['brand_mark_alt']??'');
        echo '<div class="afe-admin-card" data-afe-brand-settings><div class="afe-admin-card-title"><div><h2>نشان فرم</h2><p>برای هر فرم می‌توانید نشان پیش‌فرض AFE، یک تصویر از کتابخانه رسانه یا عدم نمایش نشان را انتخاب کنید.</p></div></div><div class="afe-admin-grid">';
        echo '<label>نوع نشان<select name="brand_mark_mode" data-afe-brand-mode><option value="default" '.selected($brandMode,'default',false).'>نشان پیش‌فرض AFE</option><option value="image" '.selected($brandMode,'image',false).'>تصویر سفارشی</option><option value="none" '.selected($brandMode,'none',false).'>عدم نمایش</option></select></label>';
        echo '<div class="afe-span-2 afe-brand-media" data-afe-brand-media'.($brandMode==='image'?'':' hidden').'>';
        echo '<label class="afe-brand-alt">متن جایگزین تصویر<input name="brand_mark_alt" value="'.esc_attr($brandAlt).'" placeholder="مثلاً نشان خانه نوآوری جهاد"></label>';
        echo '<input type="hidden" name="brand_mark_image_id" value="'.esc_attr((string)$brandImageId).'" data-afe-brand-image-id>';
        echo '<input type="hidden" name="brand_mark_image_url" value="'.esc_attr($brandImageUrl).'" data-afe-brand-image-url>';
        echo '<div class="afe-brand-picker"><div class="afe-brand-picker__preview" data-afe-brand-preview>'.($brandImageUrl!==''?'<img src="'.esc_url($brandImageUrl).'" alt="">':'<span>هنوز تصویری انتخاب نشده است.</span>').'</div><div class="afe-brand-picker__actions"><button type="button" class="button button-secondary" data-afe-brand-select>انتخاب تصویر از رسانه</button><button type="button" class="button" data-afe-brand-remove'.($brandImageUrl!==''?'':' hidden').'>حذف تصویر انتخابی</button><p class="description">برای نتیجه بهتر از تصویر مربع یا نزدیک به مربع استفاده کنید. تصویر در اندازه نشان فرم و با <code>object-fit: contain</code> نمایش داده می‌شود.</p></div></div>';
        echo '</div></div></div>';

        $previewEnabled=!empty($storedSettings['preview_enabled']??$form['settings']['preview_enabled']);
        $lockAfter=!empty($storedSettings['lock_after_submit']??$form['settings']['lock_after_submit']);
        $showRequest=!empty($storedSettings['show_edit_request_button']??$form['settings']['show_edit_request_button']);
        $previewTemplate=(string)($storedSettings['preview_template']??$form['settings']['preview_template']??'');
        echo '<div class="afe-admin-card"><div class="afe-admin-card-title"><div><h2>پیش‌نمایش و قفل فرم</h2><p>مرحله پیش‌نمایش یک مرحله سیستمی قبل از ثبت نهایی است و می‌تواند برای هر فرم مستقل فعال شود.</p></div></div><div class="afe-admin-grid">';
        echo '<label><input type="checkbox" name="preview_enabled" value="1" '.checked($previewEnabled,true,false).'> نمایش مرحله پیش‌نمایش قبل از ثبت نهایی</label>';
        echo '<label><input type="checkbox" name="lock_after_submit" value="1" '.checked($lockAfter,true,false).'> قفل ثبت بعد از ارسال نهایی</label>';
        echo '<label><input type="checkbox" name="show_edit_request_button" value="1" '.checked($showRequest,true,false).'> نمایش دکمه درخواست ویرایش در حالت قفل</label>';
        echo '<label>عنوان مرحله پیش‌نمایش<input name="preview_title" value="'.esc_attr((string)($storedSettings['preview_title']??$form['settings']['preview_title']??'پیش‌نمایش اطلاعات ارسالی')).'"></label>';
        echo '<label class="afe-span-2">توضیحات پیش‌نمایش<textarea name="preview_description" rows="3">'.esc_textarea((string)($storedSettings['preview_description']??$form['settings']['preview_description']??'')).'</textarea></label>';
        echo '<label class="afe-span-2">هشدار قفل بعد از ارسال<textarea name="lock_warning" rows="3">'.esc_textarea((string)($storedSettings['lock_warning']??$form['settings']['lock_warning']??'')).'</textarea></label>';
        echo '</div><p class="description">توکن‌های قالب: <code>{{title}}</code>، <code>{{description}}</code>، <code>{{preview_title}}</code>، <code>{{preview_description}}</code>، <code>{{preview_fields}}</code> و <code>{{field:field_name}}</code>.</p>';
        echo '<textarea class="afe-code" name="preview_template" rows="10" spellcheck="false">'.esc_textarea($previewTemplate).'</textarea></div>';

        echo '<div class="afe-admin-card afe-overrides-card"><div class="afe-admin-card-title"><div><h2>Override فیلدها</h2><p>فیلدها بر اساس مرحله گروه‌بندی شده‌اند. عنوان اصلی فیلد درشت نمایش داده می‌شود و کلید فنی فقط به‌عنوان مرجع ثانویه باقی می‌ماند.</p></div></div>';
        echo '<div class="afe-override-steps">';
        foreach ($this->registry->get($slug)->toArray()['steps'] as $stepDef) {
            $stepFields=[];
            foreach ((array)($stepDef['items']??[]) as $fieldDef) $this->flattenField($fieldDef,$stepFields,'');
            $stepFields=array_filter($stepFields,static fn($field)=>(($field['type']??'')!=='html'));
            if(!$stepFields) continue;
            echo '<details class="afe-override-step" open><summary><strong>'.esc_html((string)($stepDef['title']??'مرحله')).'</strong><span>'.number_format_i18n(count($stepFields)).' فیلد</span></summary><div class="afe-override-fields">';
            foreach ($stepFields as $path=>$field) {
                $ov=(array)($storedOverrides['fields'][$path]??[]);
                $options='';
                if (isset($ov['options']) && is_array($ov['options'])) foreach ($ov['options'] as $k=>$v) $options.=$k.'|'.$v."\n";
                $req=array_key_exists('required',$ov)?($ov['required']?'required':'optional'):'inherit';
                $displayField=$resolvedFlat[$path]??$field;
                $fieldTitle=(string)($displayField['label']??$field['label']??$field['name']??$path);
                echo '<article class="afe-override-field"><header><div><strong>'.esc_html($fieldTitle).'</strong><small><code>'.esc_html($path).'</code> · '.esc_html($this->fieldTypeLabel((string)($field['type']??''))).'</small></div>';
                if(!empty($field['required'])) echo '<span class="afe-override-badge">الزامی در کد</span>';
                echo '</header><div class="afe-override-field-grid">';
                echo '<label><span>عنوان نمایشی</span><input name="field_override['.esc_attr($path).'][label]" value="'.esc_attr((string)($ov['label']??'')).'" placeholder="'.esc_attr($fieldTitle).'"></label>';
                echo '<label><span>Placeholder</span><input name="field_override['.esc_attr($path).'][placeholder]" value="'.esc_attr((string)($ov['placeholder']??'')).'" placeholder="'.esc_attr((string)($field['placeholder']??'')).'"></label>';
                echo '<label><span>الزامی بودن</span><select name="field_override['.esc_attr($path).'][required]"><option value="inherit" '.selected($req==='inherit',true,false).'>ارث‌بری از کد</option><option value="required" '.selected($req==='required',true,false).'>الزامی</option><option value="optional" '.selected($req==='optional',true,false).'>اختیاری</option></select></label>';
                if (in_array($field['type']??'',['select','radio'],true)) {
                    echo '<label class="afe-override-span-2"><span>گزینه‌ها <small>هر خط value|label</small></span><textarea rows="4" name="field_override['.esc_attr($path).'][options]">'.esc_textarea(trim($options)).'</textarea></label>';
                }
                if (($field['type']??'')==='select') {
                    $selectMode=(string)($ov['select_mode']??'inherit');
                    $searchable=array_key_exists('searchable',$ov)?($ov['searchable']?'yes':'no'):'inherit';
                    echo '<label><span>نوع Select</span><select name="field_override['.esc_attr($path).'][select_mode]"><option value="inherit" '.selected($selectMode,'inherit',false).'>ارث‌بری ('.esc_html((string)($field['select_mode']??'custom')).')</option><option value="custom" '.selected($selectMode,'custom',false).'>Select اختصاصی</option><option value="native" '.selected($selectMode,'native',false).'>Native</option></select></label>';
                    echo '<label><span>جستجو</span><select name="field_override['.esc_attr($path).'][searchable]"><option value="inherit" '.selected($searchable,'inherit',false).'>خودکار</option><option value="yes" '.selected($searchable,'yes',false).'>فعال</option><option value="no" '.selected($searchable,'no',false).'>غیرفعال</option></select></label>';
                }
                if (($field['type']??'')==='date') {
                    $calendar=(string)($ov['calendar']??'inherit');
                    echo '<label><span>تقویم</span><select name="field_override['.esc_attr($path).'][calendar]"><option value="inherit" '.selected($calendar,'inherit',false).'>ارث‌بری ('.esc_html((string)($field['calendar']??'gregorian')).')</option><option value="jalali" '.selected($calendar,'jalali',false).'>جلالی</option><option value="gregorian" '.selected($calendar,'gregorian',false).'>میلادی</option></select></label>';
                }
                if (($field['type']??'')==='file') {
                    echo '<label><span>حداکثر تعداد فایل</span><input type="number" min="1" name="field_override['.esc_attr($path).'][max_files]" value="'.esc_attr((string)($ov['max_files']??'')).'" placeholder="'.esc_attr((string)($field['max_files']??1)).'"></label>';
                    echo '<label><span>حداکثر حجم هر فایل (MB)</span><input type="number" min="1" name="field_override['.esc_attr($path).'][max_size_mb]" value="'.esc_attr((string)($ov['max_size_mb']??'')).'" placeholder="'.esc_attr((string)($field['max_size_mb']??5)).'"></label>';
                    echo '<label class="afe-override-span-2"><span>MIMEهای مجاز</span><input name="field_override['.esc_attr($path).'][accept]" value="'.esc_attr(isset($ov['accept'])?implode(',',(array)$ov['accept']):'').'" placeholder="'.esc_attr(implode(',',(array)($field['accept']??[]))).'"></label>';
                }
                echo '</div></article>';
            }
            echo '</div></details>';
        }
        echo '</div></div>';

        echo '<div class="afe-admin-card"><h2>قالب اختصاصی هر مرحله</h2><p class="description">در هر Step می‌توانید HTML دلخواه بنویسید و از <code>{{items}}</code> یا <code>{{field:field_name}}</code> استفاده کنید. اگر خالی باشد چیدمان کد استفاده می‌شود.</p>';
        foreach ($this->registry->get($slug)->toArray()['steps'] as $stepDef) {
            $stepKey=$stepDef['key'];
            $current=(string)($storedOverrides['steps'][$stepKey]['template']??'');
            echo '<details class="afe-step-template"><summary>'.esc_html($stepDef['title']).' <code>'.esc_html($stepKey).'</code></summary>';
            echo '<textarea class="afe-code" name="step_template['.esc_attr($stepKey).']" rows="9" spellcheck="false">'.esc_textarea($current).'</textarea>';
            echo '</details>';
        }
        echo '</div>';

        $template=$row?(string)$row->template_html:'';
        $css=$row?(string)$row->custom_css:'';
        $js=$row?(string)$row->custom_js:'';
        $workflow=(array)($storedSettings['workflow']??$form['workflow']);
        echo '<div class="afe-admin-card"><h2>کد قالب فرم</h2><p>توکن‌های سطح فرم: <code>{{title}}</code>، <code>{{description}}</code>، <code>{{brand_mark}}</code> و <code>{{steps}}</code>. اگر خالی باشد قالب استاندارد افزونه استفاده می‌شود.</p>';
        echo '<textarea class="afe-code" name="template_html" rows="10" spellcheck="false">'.esc_textarea($template).'</textarea></div>';
        echo '<div class="afe-admin-card"><h2>CSS اختصاصی</h2><textarea class="afe-code" name="custom_css" rows="10" spellcheck="false">'.esc_textarea($css).'</textarea></div>';
        echo '<div class="afe-admin-card"><h2>JavaScript اختصاصی</h2><div class="afe-warning">این کد در Front-end اجرا می‌شود و فقط کاربران دارای دسترسی تنظیمات باید آن را ویرایش کنند. PHP خام از پنل اجرا نمی‌شود.</div><textarea class="afe-code" name="custom_js" rows="10" spellcheck="false">'.esc_textarea($js).'</textarea></div>';
        echo '<div class="afe-admin-card"><h2>Workflow</h2><p>JSON وضعیت‌ها به شکل <code>{"new":"جدید","approved":"تأیید شده"}</code></p><textarea class="afe-code" name="workflow_json" rows="8">'.esc_textarea(wp_json_encode($workflow,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)).'</textarea></div>';

        submit_button('ذخیره Overrideهای فرم');
        echo '</form></main></div></div>';
    }

    private function save(string $slug): void
    {
        check_admin_referer('afe_save_form_'.$slug,'afe_form_nonce');
        $existing=$this->forms->overrides($slug);
        $overrides=[
            'title'=>sanitize_text_field(wp_unslash($_POST['override_title']??'')),
            'description'=>sanitize_textarea_field(wp_unslash($_POST['override_description']??'')),
            'fields'=>[],
        ];
        foreach ((array)($_POST['field_override']??[]) as $path=>$values) {
            $path=sanitize_text_field((string)$path);
            $one=[];
            $label=sanitize_text_field(wp_unslash($values['label']??''));
            $placeholder=sanitize_text_field(wp_unslash($values['placeholder']??''));
            if ($label!=='') $one['label']=$label;
            if ($placeholder!=='') $one['placeholder']=$placeholder;
            $req=sanitize_key((string)($values['required']??'inherit'));
            if ($req==='required') $one['required']=true;
            elseif ($req==='optional') $one['required']=false;
            if (isset($values['options'])) {
                $text=sanitize_textarea_field(wp_unslash($values['options']));
                if (trim($text)!=='') $one['options']=$this->parseOptions($text);
            }
            $selectMode=sanitize_key((string)($values['select_mode']??'inherit'));
            if (in_array($selectMode,['custom','native'],true)) $one['select_mode']=$selectMode;
            $searchable=sanitize_key((string)($values['searchable']??'inherit'));
            if ($searchable==='yes') $one['searchable']=true;
            elseif ($searchable==='no') $one['searchable']=false;
            $calendar=sanitize_key((string)($values['calendar']??'inherit'));
            if (in_array($calendar,['jalali','gregorian'],true)) $one['calendar']=$calendar;
            if (($values['max_files']??'')!=='') $one['max_files']=max(1,(int)$values['max_files']);
            if (($values['max_size_mb']??'')!=='') $one['max_size_mb']=max(1,(int)$values['max_size_mb']);
            if (!empty($values['accept'])) {
                $one['accept']=array_values(array_filter(array_map('sanitize_text_field',array_map('trim',explode(',',wp_unslash($values['accept']))))));
            }
            if ($one) $overrides['fields'][$path]=$one;
        }
        $overrides['steps']=[];
        foreach ((array)($_POST['step_template']??[]) as $stepKey=>$templateValue) {
            $stepKey=sanitize_key((string)$stepKey);
            $templateValue=wp_kses_post(wp_unslash($templateValue));
            if (trim($templateValue)!=='') $overrides['steps'][$stepKey]=['template'=>$templateValue];
        }

        $workflow=json_decode(wp_unslash($_POST['workflow_json']??'{}'),true);
        if (!is_array($workflow)) $workflow=[];
        $workflowClean=[];
        foreach ($workflow as $k=>$v) $workflowClean[sanitize_key((string)$k)]=sanitize_text_field((string)$v);

        $settings=[
            'captcha'=>in_array($_POST['captcha']??'custom',['custom','google','none'],true)?$_POST['captcha']:'custom',
            'rate_limit'=>max(1,min(500,(int)($_POST['rate_limit']??20))),
            'storage'=>($_POST['storage']??'shared')==='dedicated'?'dedicated':'shared',
            'editing_enabled'=>!empty($_POST['editing_enabled']),
            'editing_modes'=>array_values(array_intersect(['wordpress','link','tracking'],array_map('sanitize_key',(array)($_POST['editing_modes']??[])))),
            'preview_enabled'=>!empty($_POST['preview_enabled']),
            'preview_title'=>sanitize_text_field(wp_unslash($_POST['preview_title']??'پیش‌نمایش اطلاعات ارسالی')),
            'preview_description'=>sanitize_textarea_field(wp_unslash($_POST['preview_description']??'')),
            'preview_template'=>wp_kses_post(wp_unslash($_POST['preview_template']??'')),
            'lock_after_submit'=>!empty($_POST['lock_after_submit']),
            'show_edit_request_button'=>!empty($_POST['show_edit_request_button']),
            'lock_warning'=>sanitize_textarea_field(wp_unslash($_POST['lock_warning']??'')),
            'brand_mark_mode'=>in_array(sanitize_key((string)($_POST['brand_mark_mode']??'default')),['default','image','none'],true)?sanitize_key((string)$_POST['brand_mark_mode']):'default',
            'brand_mark_image_id'=>absint($_POST['brand_mark_image_id']??0),
            'brand_mark_image_url'=>esc_url_raw(wp_unslash($_POST['brand_mark_image_url']??'')),
            'brand_mark_alt'=>sanitize_text_field(wp_unslash($_POST['brand_mark_alt']??'')),
            'workflow'=>$workflowClean,
        ];
        $styleIsolation=sanitize_key((string)($_POST['style_isolation']??'inherit'));
        if (array_key_exists($styleIsolation, StyleIsolationManager::modes())) {
            $settings['style_isolation']=$styleIsolation;
        }

        $template=wp_kses_post(wp_unslash($_POST['template_html']??''));
        $css=wp_strip_all_tags(wp_unslash($_POST['custom_css']??''));
        $js=current_user_can(Capabilities::MANAGE_SETTINGS) ? wp_unslash($_POST['custom_js']??'') : '';
        $this->forms->saveAdminConfig($slug,$overrides,$template,$css,$js,$settings);
        wp_safe_redirect(admin_url('admin.php?page=alavi-form-engine-forms&form='.$slug.'&updated=1')); exit;
    }

    private function parseOptions(string $text): array
    {
        $out=[];
        foreach (preg_split('/\R/u',$text) as $line) {
            $line=trim($line); if ($line==='') continue;
            if (str_contains($line,'|')) [$k,$v]=array_map('trim',explode('|',$line,2));
            else $k=$v=$line;
            $out[sanitize_text_field($k)]=sanitize_text_field($v);
        }
        return $out;
    }

    private function fieldTypeLabel(string $type): string
    {
        return [
            'text'=>'متن','textarea'=>'متن بلند','number'=>'عدد','select'=>'انتخاب','radio'=>'انتخاب',
            'date'=>'تاریخ','tel'=>'تلفن','email'=>'ایمیل','url'=>'نشانی وب','file'=>'فایل','repeater'=>'Repeater',
        ][$type] ?? $type;
    }

    private function flatten(array $steps): array
    {
        $out=[];
        foreach ($steps as $step) foreach ($step['items'] as $field) $this->flattenField($field,$out,'');
        return $out;
    }

    private function flattenField(array $field,array &$out,string $prefix): void
    {
        $name=(string)($field['name']??'');
        $path=$prefix===''?$name:$prefix.'.'.$name;
        $out[$path]=$field;
        if (($field['type']??'')==='repeater') foreach($field['fields']??[] as $child) $this->flattenField($child,$out,$path);
    }
}
