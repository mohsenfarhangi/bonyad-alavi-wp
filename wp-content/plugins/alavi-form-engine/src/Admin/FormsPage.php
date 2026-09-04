<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Admin;

use BonyadAlavi\FormEngine\Core\Capabilities;
use BonyadAlavi\FormEngine\Core\FormAccess;
use BonyadAlavi\FormEngine\Style\StyleIsolationManager;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Repository\FormRepository;
use BonyadAlavi\FormEngine\Submission\SubmissionService;
use BonyadAlavi\FormEngine\Template\TemplateResolver;
use BonyadAlavi\FormEngine\Events\EventRegistry;
use BonyadAlavi\FormEngine\Actions\ActionRegistry;
use BonyadAlavi\FormEngine\Actions\ActionDefinition;
use BonyadAlavi\FormEngine\Actions\ActionConfigSanitizer;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenRegistry;
use BonyadAlavi\FormEngine\Duplicate\DuplicatePolicy;
use BonyadAlavi\FormEngine\Validation\ValidatorRegistry;
use BonyadAlavi\FormEngine\InputMask\InputMaskPattern;
use BonyadAlavi\FormEngine\InputMask\InputMaskRegistry;

final class FormsPage
{
    public function __construct(
        private readonly FormRegistry $registry,
        private readonly FormRepository $forms,
        private readonly SubmissionService $service,
        private readonly FormAccess $access,
        private readonly TemplateResolver $templates,
        private readonly EventRegistry $events,
        private readonly ActionRegistry $actions,
        private readonly TokenRegistry $tokens,
        private readonly DuplicatePolicy $duplicatePolicy,
        private readonly ValidatorRegistry $validators,
        private readonly InputMaskRegistry $inputMasks
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
        $codeForm=$this->registry->get($slug)->toArray();
        $resolvedFlat=$this->flatten((array)($form['steps']??[]));

        echo '<div class="wrap afe-admin-wrap"><h1>مدیریت فرم‌ها</h1>';
        if (!empty($_GET['updated'])) echo '<div class="notice notice-success is-dismissible"><p>تنظیمات فرم ذخیره شد.</p></div>';
        if (!empty($_GET['afe_error'])) echo '<div class="notice notice-error is-dismissible"><p>'.esc_html(sanitize_text_field(wp_unslash((string)$_GET['afe_error']))).'</p></div>';
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
        $this->renderTabs();

        echo '<section class="afe-form-tab-panel is-active" data-afe-form-tab-panel="general">';
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
        echo '</section>';

        echo '<section class="afe-form-tab-panel" data-afe-form-tab-panel="preview" hidden>';
        $previewEnabled=!empty($storedSettings['preview_enabled']??$form['settings']['preview_enabled']);
        $lockAfter=!empty($storedSettings['lock_after_submit']??$form['settings']['lock_after_submit']);
        $showRequest=!empty($storedSettings['show_edit_request_button']??$form['settings']['show_edit_request_button']);
        $previewStored=(string)($storedSettings['preview_template']??'');
        $previewDefault=wp_kses_post($this->templates->defaultPreview($codeForm));
        echo '<div class="afe-admin-card"><div class="afe-admin-card-title"><div><h2>پیش‌نمایش و قفل فرم</h2><p>مرحله پیش‌نمایش یک مرحله سیستمی قبل از ثبت نهایی است و می‌تواند برای هر فرم مستقل فعال شود.</p></div></div><div class="afe-admin-grid">';
        echo '<label><input type="checkbox" name="preview_enabled" value="1" '.checked($previewEnabled,true,false).'> نمایش مرحله پیش‌نمایش قبل از ثبت نهایی</label>';
        echo '<label><input type="checkbox" name="lock_after_submit" value="1" '.checked($lockAfter,true,false).'> قفل ثبت بعد از ارسال نهایی</label>';
        echo '<label><input type="checkbox" name="show_edit_request_button" value="1" '.checked($showRequest,true,false).'> نمایش دکمه درخواست ویرایش در حالت قفل</label>';
        echo '<label>عنوان مرحله پیش‌نمایش<input name="preview_title" value="'.esc_attr((string)($storedSettings['preview_title']??$form['settings']['preview_title']??'پیش‌نمایش اطلاعات ارسالی')).'"></label>';
        echo '<label class="afe-span-2">توضیحات پیش‌نمایش<textarea name="preview_description" rows="3">'.esc_textarea((string)($storedSettings['preview_description']??$form['settings']['preview_description']??'')).'</textarea></label>';
        echo '<label class="afe-span-2">هشدار قفل بعد از ارسال<textarea name="lock_warning" rows="3">'.esc_textarea((string)($storedSettings['lock_warning']??$form['settings']['lock_warning']??'')).'</textarea></label>';
        echo '</div>';
        $previewDefinition=$this->templates->definition('preview');
        $this->renderTemplateEditor(
            'preview_template',
            $previewDefinition->label(),
            $previewDefinition->description(),
            $previewDefault,
            $previewStored,
            $previewDefinition->tokens(),
            12
        );
        echo '</div>';
        echo '</section>';

        echo '<section class="afe-form-tab-panel" data-afe-form-tab-panel="fields" hidden>';
        $this->renderFieldOrdering($form);
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
                    $dateMode=(string)($ov['date_input_mode']??'inherit');
                    echo '<label><span>روش ورود تاریخ</span><select name="field_override['.esc_attr($path).'][date_input_mode]"><option value="inherit" '.selected($dateMode,'inherit',false).'>ارث‌بری ('.esc_html((string)($field['date_input_mode']??'combined')).')</option><option value="combined" '.selected($dateMode,'combined',false).'>انتخاب + ورود دستی</option><option value="picker" '.selected($dateMode,'picker',false).'>فقط انتخاب از تقویم</option><option value="manual" '.selected($dateMode,'manual',false).'>فقط ورود دستی</option></select></label>';
                }
                if (in_array((string)($field['type']??''),['text','tel'],true)) {
                    $maskOverride=$ov['input_mask']??null;
                    if (is_string($maskOverride)) $maskOverride=['key'=>$maskOverride];
                    $maskKey=is_array($maskOverride)?sanitize_key((string)($maskOverride['key']??'')):'inherit';
                    if ($maskKey==='') $maskKey='inherit';
                    $sourceMask=$field['input_mask']??null;
                    if (is_string($sourceMask)) $sourceMask=['key'=>$sourceMask];
                    $sourceMaskKey=is_array($sourceMask)?sanitize_key((string)($sourceMask['key']??'')):'';
                    $inheritLabel='ارث‌بری'.($sourceMaskKey!==''?' ('.($this->inputMasks->get($sourceMaskKey)?->label??$sourceMaskKey).')':' (بدون Mask)');
                    echo '<div class="afe-override-span-2 afe-input-mask-override" data-afe-input-mask-override><label><span>Input Mask</span><select name="field_override['.esc_attr($path).'][input_mask_key]" data-afe-input-mask-select>';
                    echo '<option value="inherit" '.selected($maskKey,'inherit',false).'>'.esc_html($inheritLabel).'</option>';
                    echo '<option value="none" '.selected($maskKey,'none',false).'>بدون Mask</option>';
                    foreach ($this->inputMasks->forFieldType((string)($field['type']??'')) as $maskDefinition) {
                        $label=$maskDefinition->label.($maskDefinition->example!==''?' — '.$maskDefinition->example:'');
                        echo '<option value="'.esc_attr($maskDefinition->key).'" '.selected($maskKey,$maskDefinition->key,false).'>'.esc_html($label).'</option>';
                    }
                    echo '<option value="custom" '.selected($maskKey,'custom',false).'>Mask سفارشی</option></select></label>';
                    $customPattern=is_array($maskOverride)?(string)($maskOverride['pattern']??''):'';
                    echo '<label data-afe-custom-mask-row'.($maskKey==='custom'?'':' hidden').'><span>الگوی Mask سفارشی</span><input dir="ltr" maxlength="'.esc_attr((string)InputMaskPattern::MAX_PATTERN_LENGTH).'" name="field_override['.esc_attr($path).'][input_mask_pattern]" value="'.esc_attr($customPattern).'" placeholder="9999 999 9999"><small>9 = رقم، A = حرف، * = حرف یا رقم. جداکننده‌ها مثل فاصله، / و - فقط نمایشی هستند. برای نوشتن خود 9/A/* به‌صورت literal از \ استفاده کنید.</small></label>';
                    echo '<p class="description afe-input-mask-help">Mask فقط UX ورودی است؛ مقدار قبل از Validation، Duplicate، Token، SMS و ذخیره‌سازی بدون جداکننده‌های Mask نرمال می‌شود.</p></div>';
                }
                if (($field['type']??'')==='date') {
                    echo '<p class="afe-override-span-2 description">Input Mask تاریخ به‌صورت خودکار از تقویم تعیین می‌شود: جلالی <code>YYYY/MM/DD</code> و میلادی <code>YYYY-MM-DD</code>.</p>';
                }
                if (in_array((string)($field['type']??''),['text','textarea','tel','email','url'],true)) {
                    $characterMode=(string)($ov['character_mode']??'inherit');
                    echo '<label><span>نوع کاراکتر</span><select name="field_override['.esc_attr($path).'][character_mode]"><option value="inherit" '.selected($characterMode,'inherit',false).'>ارث‌بری / Normal</option><option value="normal" '.selected($characterMode,'normal',false).'>Normal</option><option value="digits" '.selected($characterMode,'digits',false).'>فقط اعداد</option><option value="persian" '.selected($characterMode,'persian',false).'>فقط حروف فارسی</option><option value="english" '.selected($characterMode,'english',false).'>فقط حروف انگلیسی</option><option value="alnum" '.selected($characterMode,'alnum',false).'>حروف و اعداد</option></select></label>';
                    echo '<label><span>کاراکترهای مجاز اضافی</span><input name="field_override['.esc_attr($path).'][allowed_extra]" value="'.esc_attr((string)($ov['allowed_extra']??'')).'" maxlength="50" placeholder="مثلاً -_/"></label>';
                    echo '<label><span>کاراکترهای ممنوع اضافی</span><input name="field_override['.esc_attr($path).'][forbidden_extra]" value="'.esc_attr((string)($ov['forbidden_extra']??'')).'" maxlength="50"></label>';
                    echo '<label><span>حداقل طول</span><input type="number" min="1" max="10000" name="field_override['.esc_attr($path).'][min_length]" value="'.esc_attr((string)($ov['min_length']??'')).'"></label>';
                    echo '<label><span>حداکثر طول</span><input type="number" min="1" max="10000" name="field_override['.esc_attr($path).'][max_length]" value="'.esc_attr((string)($ov['max_length']??'')).'"></label>';
                    echo '<label><span>طول دقیق</span><input type="number" min="1" max="10000" name="field_override['.esc_attr($path).'][exact_length]" value="'.esc_attr((string)($ov['exact_length']??'')).'" placeholder="در صورت تنظیم، اولویت دارد"></label>';
                }
                $this->renderValidatorOverride($path,$field,$ov);
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

        $stepDefinition=$this->templates->definition('step');
        echo '<div class="afe-admin-card"><h2>قالب اختصاصی هر مرحله</h2><p class="description">قالب پیش‌فرض واقعی هر Step در Editor نمایش داده می‌شود. فقط در صورت تغییر، Override ذخیره خواهد شد.</p>';
        foreach ((array)($codeForm['steps']??[]) as $stepDef) {
            $stepKey=(string)$stepDef['key'];
            $storedStep=(string)($storedOverrides['steps'][$stepKey]['template']??'');
            $stepDefault=wp_kses_post($this->templates->defaultStep($stepDef));
            echo '<details class="afe-step-template"><summary>'.esc_html((string)$stepDef['title']).' <code>'.esc_html($stepKey).'</code></summary>';
            $this->renderTemplateEditor(
                'step_template['.$stepKey.']',
                $stepDefinition->label(),
                $stepDefinition->description(),
                $stepDefault,
                $storedStep,
                $stepDefinition->tokens(),
                9,
                true
            );
            echo '</details>';
        }
        echo '</div>';
        echo '</section>';

        echo '<section class="afe-form-tab-panel" data-afe-form-tab-panel="duplicate" hidden>';
        $this->renderDuplicateSettings($form);
        echo '</section>';

        echo '<section class="afe-form-tab-panel" data-afe-form-tab-panel="actions" hidden>';
        $this->renderActionBuilder($form,$codeForm,$storedSettings);
        echo '</section>';

        echo '<section class="afe-form-tab-panel" data-afe-form-tab-panel="templates" hidden>';
        $template=$row?(string)$row->template_html:'';
        $css=$row?(string)$row->custom_css:'';
        $js=$row?(string)$row->custom_js:'';
        $workflow=(array)($storedSettings['workflow']??$form['workflow']);
        $formDefinition=$this->templates->definition('form');
        $formDefault=wp_kses_post($this->templates->defaultForm($codeForm));
        echo '<div class="afe-admin-card"><h2>کد قالب فرم</h2><p class="description">ساختار پیش‌فرض واقعی فرم با Tokenهای داینامیک نمایش داده می‌شود. اگر آن را تغییر ندهید، Override جداگانه‌ای ذخیره نمی‌شود.</p>';
        $this->renderTemplateEditor(
            'template_html',
            $formDefinition->label(),
            $formDefinition->description(),
            $formDefault,
            $template,
            $formDefinition->tokens(),
            14
        );
        echo '</div>';
        echo '</section>';

        echo '<section class="afe-form-tab-panel" data-afe-form-tab-panel="assets" hidden>';
        echo '<div class="afe-admin-card"><h2>CSS اختصاصی</h2><textarea class="afe-code" name="custom_css" rows="10" spellcheck="false">'.esc_textarea($css).'</textarea></div>';
        echo '<div class="afe-admin-card"><h2>JavaScript اختصاصی</h2><div class="afe-warning">این کد در Front-end اجرا می‌شود و فقط کاربران دارای دسترسی تنظیمات باید آن را ویرایش کنند. PHP خام از پنل اجرا نمی‌شود.</div><textarea class="afe-code" name="custom_js" rows="10" spellcheck="false">'.esc_textarea($js).'</textarea></div>';
        echo '</section>';

        echo '<section class="afe-form-tab-panel" data-afe-form-tab-panel="workflow" hidden>';
        echo '<div class="afe-admin-card"><h2>Workflow</h2><p>JSON وضعیت‌ها به شکل <code>{"new":"جدید","approved":"تأیید شده"}</code></p><textarea class="afe-code" name="workflow_json" rows="8">'.esc_textarea(wp_json_encode($workflow,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)).'</textarea></div>';
        echo '</section>';

        echo '<section class="afe-form-tab-panel" data-afe-form-tab-panel="access" hidden>';
        echo '<div class="afe-admin-card"><div class="afe-admin-card-title"><div><h2>دسترسی</h2><p>Capabilityهای اختصاصی هر فرم در صفحه تنظیمات سراسری مدیریت می‌شوند تا یک ماتریس واحد برای نقش‌ها وجود داشته باشد.</p></div></div><a class="button button-secondary" href="'.esc_url(admin_url('admin.php?page=alavi-form-engine-settings')).'">مدیریت دسترسی نقش‌ها</a></div>';
        echo '</section>';

        echo '<div class="afe-form-sticky-save">';
        submit_button('ذخیره Overrideهای فرم','primary','submit',false);
        echo '<span>تغییرات همه تب‌ها با این دکمه ذخیره می‌شوند.</span></div>';
        echo '</form></main></div></div>';
    }

    private function renderTabs(): void
    {
        $tabs=[
            'general'=>'عمومی',
            'fields'=>'فیلدها و چیدمان',
            'duplicate'=>'جلوگیری از تکرار',
            'actions'=>'رویدادها و اکشن‌ها',
            'workflow'=>'Workflow',
            'preview'=>'Preview و Lock',
            'templates'=>'قالب‌ها',
            'assets'=>'CSS/JS',
            'access'=>'دسترسی',
        ];
        echo '<nav class="afe-form-tabs" data-afe-form-tabs role="tablist" aria-label="تنظیمات فرم">';
        foreach($tabs as $key=>$label){
            echo '<button type="button" class="afe-form-tab'.($key==='general'?' is-active':'').'" role="tab" aria-selected="'.($key==='general'?'true':'false').'" data-afe-form-tab="'.esc_attr($key).'">'.esc_html($label).'</button>';
        }
        echo '</nav>';
    }

    private function renderDuplicateSettings(array $form): void
    {
        $config=$this->duplicatePolicy->config($form);
        $fields=$this->duplicatePolicy->fieldLabels($form);
        echo '<div class="afe-admin-card afe-duplicate-card" data-afe-duplicate-settings>';
        echo '<div class="afe-admin-card-title"><div><h2>جلوگیری از ثبت تکراری</h2><p>Fingerprint از ترکیب همه فیلدهای انتخاب‌شده ساخته می‌شود. Draftهای دیگر هم تکراری محسوب می‌شوند؛ ثبت در حال ویرایش خودش مستثنا است و موارد زباله‌دان‌شده در تطبیق شرکت نمی‌کنند.</p></div></div>';
        echo '<label class="afe-action-override-toggle"><input type="checkbox" name="duplicate_enabled" value="1" '.checked($config['enabled'],true,false).' data-afe-duplicate-enabled> جلوگیری از ثبت تکراری برای این فرم فعال باشد</label>';
        echo '<div class="afe-admin-card-subsection"><h3>فیلدهای تشکیل‌دهنده Fingerprint <span class="afe-duplicate-count" data-afe-duplicate-count>'.count($config['fields']).' فیلد</span></h3><p class="description">تکراری بودن فقط زمانی رخ می‌دهد که مقدار همه فیلدهای انتخاب‌شده با یک ثبت فعال دیگر برابر باشد.</p>';
        if($fields===[]){
            echo '<div class="afe-warning">فیلد قابل استفاده‌ای برای Fingerprint پیدا نشد.</div>';
        }else{
            echo '<div class="afe-duplicate-fields">';
            foreach($fields as $key=>$label){
                echo '<label><input type="checkbox" name="duplicate_fields[]" value="'.esc_attr($key).'" '.checked(in_array($key,$config['fields'],true),true,false).'> <span>'.esc_html($label).'</span><code>'.esc_html($key).'</code></label>';
            }
            echo '</div>';
        }
        echo '<div class="afe-warning afe-duplicate-warning" data-afe-duplicate-warning hidden>برای فعال‌کردن جلوگیری از تکرار، حداقل یک فیلد Fingerprint انتخاب کنید.</div>';
        echo '</div>';
        echo '<div class="afe-admin-grid">';
        echo '<label>رفتار در صورت تکراری بودن<select name="duplicate_behavior" data-afe-duplicate-behavior>';
        foreach([
            'block'=>'جلوگیری کامل از ثبت',
            'reference'=>'ارجاع به ثبت قبلی در صورت داشتن دسترسی معتبر',
            'message'=>'جلوگیری با پیام سفارشی',
            'allow'=>'اجازه ثبت و علامت‌گذاری به عنوان Duplicate',
        ] as $key=>$label){
            echo '<option value="'.esc_attr($key).'" '.selected($config['behavior'],$key,false).'>'.esc_html($label).'</option>';
        }
        echo '</select></label>';
        echo '<label class="afe-span-2">پیام تکراری<textarea name="duplicate_message" rows="3" placeholder="این اطلاعات قبلاً ثبت شده است.">'.esc_textarea($config['message']).'</textarea><span class="description">در حالت ارجاع، لینک ثبت قبلی فقط زمانی نمایش داده می‌شود که مالکیت/دسترسی معتبر کاربر سمت PHP تأیید شود.</span></label>';
        echo '</div>';
        echo '<div class="afe-admin-callout">حالت «اجازه ثبت» رکورد جدید را با <code>is_duplicate=1</code> و مرجع Submission اصلی ذخیره می‌کند. این علامت‌گذاری مستقل از Workflow است.</div>';
        echo '</div>';
    }

    private function renderActionBuilder(array $resolvedForm,array $codeForm,array $storedSettings): void
    {
        $overrideEnabled=array_key_exists('actions',$storedSettings) && is_array($storedSettings['actions']);
        $source=$overrideEnabled?(array)$storedSettings['actions']:(array)($codeForm['actions']??[]);
        $groups=$this->actionGroupsForUi($source);
        if($groups===[]) $groups=[['event'=>'submission.submitted','actions'=>[]]];
        $fieldLabels=$this->fieldLabels($resolvedForm);
        $definitions=$this->actions->all();

        echo '<div class="afe-admin-card afe-action-builder-card" data-afe-action-builder>';
        echo '<div class="afe-admin-card-title"><div><h2>رویدادها و اکشن‌ها</h2><p>Event با عنوان فارسی انتخاب می‌شود و slug فنی فقط به‌عنوان مرجع توسعه‌دهنده نمایش داده می‌شود. ترتیب اکشن‌ها با Drag & Drop قابل تغییر است.</p></div><span class="afe-action-source'.($overrideEnabled?' is-override':' is-code').'" data-afe-action-source>'.($overrideEnabled?'Override مدیریتی':'ارث‌بری از تعریف کد').'</span></div>';
        echo '<label class="afe-action-override-toggle"><input type="checkbox" name="actions_override_enabled" value="1" '.checked($overrideEnabled,true,false).' data-afe-actions-override> Override مدیریتی اکشن‌ها برای این فرم فعال باشد <span class="description">با اولین تغییر در Builder به‌صورت خودکار فعال می‌شود. برای بازگشت کامل به تعریف PHP، تیک را بردارید و ذخیره کنید.</span></label>';

        echo '<div class="afe-token-palette"><div class="afe-token-palette__head"><div><strong>Token Palette</strong><span>برای کپی روی Token کلیک کنید.</span></div><span class="afe-token-toast" data-afe-token-toast aria-live="polite"></span></div><div class="afe-token-palette__items">';
        foreach($this->tokens->forForm($resolvedForm) as $token=>$definition){
            if($token==='{{field:*}}') continue;
            echo '<button type="button" class="afe-token-chip" data-afe-token-copy="'.esc_attr($token).'" title="'.esc_attr($definition->description).'"><span>'.esc_html($definition->label).'</span><code>'.esc_html($token).'</code></button>';
        }
        echo '</div></div>';

        if($definitions===[]){
            echo '<div class="afe-warning">هیچ ActionDefinition در Registry ثبت نشده است.</div></div>';
            return;
        }

        echo '<div class="afe-event-groups" data-afe-event-groups>';
        foreach($groups as $groupIndex=>$group) $this->renderActionGroup((string)$groupIndex,$group,$resolvedForm);
        echo '</div>';
        echo '<button type="button" class="button button-secondary" data-afe-add-event>افزودن Event</button>';

        echo '<template data-afe-event-template>';
        $this->renderActionGroup('__GROUP__',['event'=>'submission.submitted','actions'=>[]],$resolvedForm,true);
        echo '</template>';

        $firstType=(string)array_key_first($definitions);
        echo '<template data-afe-action-row-template>';
        $this->renderActionRow('__GROUP__','__ACTION__',[
            'action_key'=>'__ACTION_KEY__','type'=>$firstType,'enabled'=>true,'execution_policy'=>'always','on_error'=>'continue','config'=>[],'when'=>[],
        ],$resolvedForm,true);
        echo '</template>';

        foreach($definitions as $type=>$definition){
            echo '<template data-afe-action-config-template="'.esc_attr($type).'">';
            echo '<div class="afe-action-config-grid" data-afe-action-config-grid>';
            $this->renderActionConfigFields($definition,[], 'action_groups[__GROUP__][actions][__ACTION__]', $resolvedForm);
            echo '</div></template>';
        }

        echo '<template data-afe-condition-template>';
        $this->renderConditionRow('__GROUP__','__ACTION__','__CONDITION__',[], $fieldLabels, true);
        echo '</template>';
        echo '</div>';
    }

    private function renderActionGroup(string $groupIndex,array $group,array $form,bool $template=false): void
    {
        $event=(string)($group['event']??'submission.submitted');
        if(!$this->events->has($event)) $event='submission.submitted';
        echo '<section class="afe-event-group" data-afe-event-group data-group-index="'.esc_attr($groupIndex).'">';
        echo '<header class="afe-event-group__head"><label><span>رویداد</span><select name="action_groups['.esc_attr($groupIndex).'][event]" data-afe-event-select>';
        foreach($this->events->all() as $key=>$definition){
            echo '<option value="'.esc_attr($key).'" '.selected($event,$key,false).'>'.esc_html($definition->label).'</option>';
        }
        echo '</select><small>slug: <code data-afe-event-slug>'.esc_html($event).'</code></small></label><button type="button" class="button-link-delete" data-afe-remove-event>حذف Event</button></header>';
        echo '<div class="afe-event-actions" data-afe-event-actions>';
        foreach((array)($group['actions']??[]) as $actionIndex=>$action){
            $this->renderActionRow($groupIndex,(string)$actionIndex,(array)$action,$form,$template);
        }
        echo '</div><button type="button" class="button" data-afe-add-action>افزودن Action</button></section>';
    }

    private function renderActionRow(string $groupIndex,string $actionIndex,array $action,array $form,bool $template=false): void
    {
        $definitions=$this->actions->all();
        $type=sanitize_key((string)($action['type']??array_key_first($definitions)??''));
        if(!$this->actions->has($type)) $type=(string)array_key_first($definitions);
        $definition=$this->actions->get($type);
        if(!$definition) return;

        $actionKey=sanitize_key((string)($action['action_key']??''));
        if($actionKey==='') $actionKey='ui_'.$type.'_'.substr(hash('sha256',$groupIndex.'|'.$actionIndex.'|'.$type),0,12);
        if($template && (string)($action['action_key']??'')==='__ACTION_KEY__') $actionKey='__ACTION_KEY__';
        $prefix='action_groups['.$groupIndex.'][actions]['.$actionIndex.']';
        $enabled=!array_key_exists('enabled',$action)||!empty($action['enabled']);
        $policy=(string)($action['execution_policy']??'always');
        if(!in_array($policy,['always','once_per_submission','first_in_cycle'],true)) $policy='always';
        $onError=(string)($action['on_error']??'continue');
        if(!in_array($onError,['continue','stop'],true)) $onError='continue';

        echo '<article class="afe-action-row" draggable="true" data-afe-action-row data-action-index="'.esc_attr($actionIndex).'">';
        echo '<header class="afe-action-row__head"><button type="button" class="afe-action-drag" title="برای جابه‌جایی بکشید" aria-label="جابه‌جایی Action">⋮⋮</button><div><strong data-afe-action-label>'.esc_html($definition->label).'</strong><code>'.esc_html($actionKey).'</code></div><button type="button" class="button-link-delete" data-afe-remove-action>حذف</button></header>';
        echo '<input type="hidden" name="'.esc_attr($prefix.'[action_key]').'" value="'.esc_attr($actionKey).'">';
        echo '<div class="afe-action-meta-grid">';
        echo '<label><span>نوع Action</span><select name="'.esc_attr($prefix.'[type]').'" data-afe-action-type>';
        foreach($definitions as $key=>$one) echo '<option value="'.esc_attr($key).'" '.selected($type,$key,false).'>'.esc_html($one->label).'</option>';
        echo '</select></label>';
        if($definition->supportsExecutionPolicy){
            echo '<label><span>سیاست اجرا</span><select name="'.esc_attr($prefix.'[execution_policy]').'"><option value="always" '.selected($policy,'always',false).'>هر بار Event</option><option value="once_per_submission" '.selected($policy,'once_per_submission',false).'>فقط یک بار برای Submission</option><option value="first_in_cycle" '.selected($policy,'first_in_cycle',false).'>فقط اولین بار در چرخه Event</option></select></label>';
        }else{
            echo '<input type="hidden" name="'.esc_attr($prefix.'[execution_policy]').'" value="always"><div class="afe-action-meta-note"><span>سیاست اجرا</span><strong>هر بار Event</strong></div>';
        }
        echo '<label><span>رفتار در خطا</span><select name="'.esc_attr($prefix.'[on_error]').'"><option value="continue" '.selected($onError,'continue',false).'>ادامه Actionهای بعدی</option><option value="stop" '.selected($onError,'stop',false).'>توقف زنجیره</option></select></label>';
        echo '<label class="afe-action-enabled"><input type="checkbox" name="'.esc_attr($prefix.'[enabled]').'" value="1" '.checked($enabled,true,false).'> فعال</label>';
        echo '</div>';

        echo '<div class="afe-action-config" data-afe-action-config><div class="afe-action-config-grid" data-afe-action-config-grid>';
        $this->renderActionConfigFields($definition,(array)($action['config']??$this->legacyActionConfig($action)),$prefix,$form);
        echo '</div></div>';

        if($definition->supportsConditionalLogic){
            $conditions=(array)($action['when']??[]);
            echo '<details class="afe-action-conditions"'.($conditions!==[]?' open':'').'><summary>Conditional Logic <span>'.number_format_i18n(count($conditions)).' شرط</span></summary><div class="afe-condition-rows" data-afe-condition-rows>';
            $fieldLabels=$this->fieldLabels($form);
            foreach($conditions as $conditionIndex=>$condition) $this->renderConditionRow($groupIndex,$actionIndex,(string)$conditionIndex,(array)$condition,$fieldLabels,$template);
            echo '</div><button type="button" class="button button-small" data-afe-add-condition>افزودن شرط</button></details>';
        }
        echo '</article>';
    }

    private function renderActionConfigFields(ActionDefinition $definition,array $config,string $prefix,array $form): void
    {
        $fields=$this->fieldLabels($form);
        foreach($definition->settingsSchema as $key=>$schema){
            if(!is_array($schema)) continue;
            $capability=(string)($schema['capability']??'');
            if($capability!=='' && !current_user_can($capability)) continue;
            $type=sanitize_key((string)($schema['type']??'text'));
            $value=$config[$key]??($schema['default']??'');
            $showWhen=(array)($schema['show_when']??[]);
            $attrs='';
            if($showWhen!==[]){
                $showField=(string)array_key_first($showWhen);
                $attrs.=' data-afe-show-when-field="'.esc_attr($showField).'" data-afe-show-when-value="'.esc_attr((string)$showWhen[$showField]).'"';
            }
            $name=$prefix.'[config]['.$key.']';
            echo '<label class="afe-action-config-field'.(in_array($type,['textarea','json','key_value','repeater_text'],true)?' is-wide':'').'"'.$attrs.'><span>'.esc_html((string)($schema['label']??$key));
            if(!empty($schema['tokens'])) echo ' <small>Token ✓</small>';
            echo '</span>';

            if($type==='textarea'){
                echo '<textarea rows="4" name="'.esc_attr($name).'" data-afe-config-field-key="'.esc_attr((string)$key).'">'.esc_textarea((string)$value).'</textarea>';
            }elseif($type==='json'){
                $json=is_array($value)?wp_json_encode($value,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT):(string)$value;
                echo '<textarea rows="7" class="afe-code" name="'.esc_attr($name).'" data-afe-config-field-key="'.esc_attr((string)$key).'">'.esc_textarea((string)$json).'</textarea>';
            }elseif($type==='key_value'){
                $lines='';
                if(is_array($value)) foreach($value as $oneKey=>$oneValue) $lines.=$oneKey.'|'.$oneValue."\n";
                else $lines=(string)$value;
                echo '<textarea rows="4" name="'.esc_attr($name).'" data-afe-config-field-key="'.esc_attr((string)$key).'" placeholder="Header|Value">'.esc_textarea(trim($lines)).'</textarea>';
            }elseif($type==='repeater_text'){
                $lines=is_array($value)?implode("\n",array_map('strval',$value)):(string)$value;
                echo '<textarea rows="4" name="'.esc_attr($name).'" data-afe-config-field-key="'.esc_attr((string)$key).'" placeholder="هر پارامتر در یک خط">'.esc_textarea($lines).'</textarea>';
            }elseif($type==='select'){
                echo '<select name="'.esc_attr($name).'" data-afe-config-field-key="'.esc_attr((string)$key).'">';
                foreach((array)($schema['options']??[]) as $option=>$label) echo '<option value="'.esc_attr((string)$option).'" '.selected((string)$value,(string)$option,false).'>'.esc_html((string)$label).'</option>';
                echo '</select>';
            }elseif($type==='field_select'){
                echo '<select name="'.esc_attr($name).'" data-afe-config-field-key="'.esc_attr((string)$key).'"><option value="">انتخاب فیلد…</option>';
                foreach($fields as $path=>$label) echo '<option value="'.esc_attr($path).'" '.selected((string)$value,$path,false).'>'.esc_html($label).' — '.esc_html($path).'</option>';
                echo '</select>';
            }elseif($type==='user'){
                $dropdown=wp_dropdown_users(['name'=>$name,'selected'=>(int)$value,'show_option_none'=>'انتخاب کاربر…','option_none_value'=>'0','echo'=>0]);
                echo is_string($dropdown)?$dropdown:'<input type="number" min="0" name="'.esc_attr($name).'" value="'.esc_attr((string)$value).'">';
            }elseif($type==='boolean'){
                echo '<input type="hidden" name="'.esc_attr($name).'" value="0"><span class="afe-action-inline-check"><input type="checkbox" name="'.esc_attr($name).'" value="1" '.checked(!empty($value),true,false).' data-afe-config-field-key="'.esc_attr((string)$key).'"> فعال</span>';
            }elseif($type==='role_select'){
                echo '<select name="'.esc_attr($name).'" data-afe-config-field-key="'.esc_attr((string)$key).'"><option value="">انتخاب نقش…</option>';
                foreach(wp_roles()->roles as $roleKey=>$roleData){
                    $roleCaps=is_array($roleData['capabilities']??null)?$roleData['capabilities']:[];
                    $privilegedRole=$roleKey==='administrator'||!empty($roleCaps['manage_options']);
                    if($privilegedRole && !current_user_can(Capabilities::MANAGE_SETTINGS)) continue;
                    echo '<option value="'.esc_attr((string)$roleKey).'" '.selected((string)$value,(string)$roleKey,false).'>'.esc_html((string)($roleData['name']??$roleKey)).'</option>';
                }
                echo '</select>';
            }elseif($type==='workflow_select'){
                echo '<select name="'.esc_attr($name).'" data-afe-config-field-key="'.esc_attr((string)$key).'"><option value="">انتخاب وضعیت…</option>';
                foreach((array)($form['workflow']??[]) as $statusKey=>$statusLabel) echo '<option value="'.esc_attr((string)$statusKey).'" '.selected((string)$value,(string)$statusKey,false).'>'.esc_html((string)$statusLabel).'</option>';
                echo '</select>';
            }elseif($type==='post_type_select'){
                echo '<select name="'.esc_attr($name).'" data-afe-config-field-key="'.esc_attr((string)$key).'">';
                $postTypes=get_post_types(['show_ui'=>true],'objects');
                foreach($postTypes as $postTypeKey=>$postTypeObject){
                    if(in_array((string)$postTypeKey,['attachment','revision','nav_menu_item'],true)) continue;
                    $label=(string)($postTypeObject->labels->singular_name??$postTypeObject->label??$postTypeKey);
                    echo '<option value="'.esc_attr((string)$postTypeKey).'" '.selected((string)$value,(string)$postTypeKey,false).'>'.esc_html($label).' — '.esc_html((string)$postTypeKey).'</option>';
                }
                echo '</select>';
            }elseif($type==='post_status_select'){
                echo '<select name="'.esc_attr($name).'" data-afe-config-field-key="'.esc_attr((string)$key).'">';
                foreach(['draft'=>'پیش‌نویس','pending'=>'در انتظار بررسی','private'=>'خصوصی','publish'=>'منتشرشده'] as $statusKey=>$statusLabel) echo '<option value="'.esc_attr($statusKey).'" '.selected((string)$value,$statusKey,false).'>'.esc_html($statusLabel).'</option>';
                echo '</select>';
            }else{
                $inputType=$type==='url'?'url':'text';
                echo '<input type="'.esc_attr($inputType).'" name="'.esc_attr($name).'" value="'.esc_attr((string)$value).'" data-afe-config-field-key="'.esc_attr((string)$key).'">';
            }
            echo '</label>';
        }
    }

    private function renderConditionRow(string $groupIndex,string $actionIndex,string $conditionIndex,array $condition,array $fieldLabels,bool $template=false): void
    {
        $prefix='action_groups['.$groupIndex.'][actions]['.$actionIndex.'][when]['.$conditionIndex.']';
        $operator=(string)($condition['operator']??'=');
        $value=$condition['value']??'';
        if(is_array($value)) $value=implode(',',$value);
        echo '<div class="afe-condition-row" data-afe-condition-row>';
        echo '<select name="'.esc_attr($prefix.'[field]').'"><option value="">فیلد…</option>';
        foreach($fieldLabels as $path=>$label) echo '<option value="'.esc_attr($path).'" '.selected((string)($condition['field']??''),$path,false).'>'.esc_html($label).'</option>';
        echo '</select><select name="'.esc_attr($prefix.'[operator]').'" data-afe-condition-operator>';
        foreach(['='=>'برابر','!='=>'نابرابر','>'=>'بزرگ‌تر','>='=>'بزرگ‌تر/برابر','<'=>'کوچک‌تر','<='=>'کوچک‌تر/برابر','in'=>'یکی از','contains'=>'شامل','empty'=>'خالی','not_empty'=>'غیرخالی'] as $key=>$label){
            echo '<option value="'.esc_attr($key).'" '.selected($operator,$key,false).'>'.esc_html($label).'</option>';
        }
        echo '</select><input name="'.esc_attr($prefix.'[value]').'" value="'.esc_attr((string)$value).'" placeholder="مقدار" data-afe-condition-value><button type="button" class="button-link-delete" data-afe-remove-condition>حذف</button></div>';
    }

    private function actionGroupsForUi(array $source): array
    {
        $groups=[];
        foreach($source as $item){
            if(!is_array($item)) continue;
            if(isset($item['actions']) && is_array($item['actions'])){
                $event=(string)($item['event']??'submission.submitted');
                if(!$this->events->has($event)) continue;
                $groups[]=['event'=>$event,'actions'=>array_values(array_filter($item['actions'],'is_array'))];
                continue;
            }
            $rawEvents=$item['events']??$item['on']??$item['event']??['submission.submitted'];
            $rawEvents=is_array($rawEvents)?$rawEvents:[$rawEvents];
            foreach($rawEvents as $event){
                $event=$this->canonicalEvent((string)$event);
                if(!$this->events->has($event)) continue;
                $found=null;
                foreach($groups as $index=>$group) if($group['event']===$event){$found=$index;break;}
                if($found===null){$groups[]=['event'=>$event,'actions'=>[]];$found=array_key_last($groups);}
                $groups[$found]['actions'][]=$item;
            }
        }
        return $groups;
    }

    private function legacyActionConfig(array $action): array
    {
        $config=$action;
        foreach(['type','action_key','enabled','on','events','event','when','execution_policy','on_error','actions'] as $reserved) unset($config[$reserved]);
        return $config;
    }

    private function canonicalEvent(string $event): string
    {
        return match(trim($event)){
            'created'=>'submission.created','updated'=>'submission.updated','submitted'=>'submission.submitted','draft','draft_saved'=>'submission.draft_saved',default=>trim($event),
        };
    }

    /** @return array<string,string> */
    private function fieldLabels(array $form): array
    {
        $labels=[];
        foreach((array)($form['steps']??[]) as $step){
            foreach((array)($step['items']??[]) as $field) $this->collectFieldLabel((array)$field,'',$labels);
        }
        return $labels;
    }

    /** @param array<string,string> $labels */
    private function collectFieldLabel(array $field,string $prefix,array &$labels): void
    {
        $name=(string)($field['name']??'');
        if($name===''||($field['type']??'')==='html') return;
        $path=$prefix===''?$name:$prefix.'.'.$name;
        $labels[$path]=(string)($field['label']??$path);
        if(($field['type']??'')==='repeater') foreach((array)($field['fields']??[]) as $child) $this->collectFieldLabel((array)$child,$path,$labels);
    }

    private function renderFieldOrdering(array $form): void
    {
        echo '<div class="afe-admin-card afe-field-order-card" data-afe-field-ordering><div class="afe-admin-card-title"><div><h2>چیدمان فیلدها</h2><p>Drag & Drop فقط داخل همان Step مجاز است. HtmlBlockها نیز قابل جابه‌جایی هستند و فیلدهای داخل Repeater فقط در همان Repeater مرتب می‌شوند.</p></div></div>';
        foreach ((array)($form['steps']??[]) as $step) {
            $stepKey=(string)($step['key']??'');
            echo '<details class="afe-field-order-step" open><summary><strong>'.esc_html((string)($step['title']??$stepKey)).'</strong><code>'.esc_html($stepKey).'</code></summary>';
            echo '<ol class="afe-field-order-list" data-afe-field-order-list data-afe-order-scope="step">';
            foreach ((array)($step['items']??[]) as $item) $this->renderOrderItem((array)$item,'field_order['.$stepKey.'][]','');
            echo '</ol></details>';
        }
        echo '</div>';
    }

    private function renderOrderItem(array $item,string $inputName,string $prefix): void
    {
        $name=(string)($item['name']??'');
        if ($name==='') return;
        $path=$prefix===''?$name:$prefix.'.'.$name;
        $type=(string)($item['type']??'');
        if ($type==='html') {
            $plain=trim(wp_strip_all_tags((string)($item['html']??'')));
            $label=$plain!==''?(function_exists('mb_substr')?mb_substr($plain,0,72):substr($plain,0,72)):'بلوک HTML';
        } else $label=(string)($item['label']??$name);
        echo '<li class="afe-field-order-item" draggable="true" data-afe-field-order-item><div class="afe-field-order-item__row"><span class="afe-field-order-drag" aria-hidden="true">↕</span><strong>'.esc_html($label).'</strong><small>'.esc_html($this->fieldTypeLabel($type)).'</small><code>'.esc_html($path).'</code><input type="hidden" name="'.esc_attr($inputName).'" value="'.esc_attr($name).'"></div>';
        if ($type==='repeater') {
            $hash=substr(md5($path),0,12);
            echo '<div class="afe-field-order-children"><span>ترتیب فیلدهای داخل Repeater</span><input type="hidden" name="repeater_order['.esc_attr($hash).'][path]" value="'.esc_attr($path).'">';
            echo '<ol class="afe-field-order-list afe-field-order-list--nested" data-afe-field-order-list data-afe-order-scope="repeater">';
            foreach ((array)($item['fields']??[]) as $child) $this->renderOrderItem((array)$child,'repeater_order['.$hash.'][items][]',$path);
            echo '</ol></div>';
        }
        echo '</li>';
    }

    private function renderValidatorOverride(string $path,array $field,array $override): void
    {
        $definitions=$this->validators->forFieldType((string)($field['type']??''));
        if ($definitions===[]) return;
        $configs=[];
        foreach ((array)($override['validators']??[]) as $config) {
            if (is_string($config)) $config=['key'=>$config];
            if (is_array($config) && !empty($config['key'])) $configs[(string)$config['key']]=$config;
        }
        $selected=array_keys($configs);
        $canRegex=current_user_can(Capabilities::MANAGE_SETTINGS);
        echo '<div class="afe-override-span-2 afe-validator-override" data-afe-validator-override><label><span>Validationها <small>انتخاب چندگانه</small></span><select multiple size="'.esc_attr((string)min(8,max(3,count($definitions)))).'" name="field_override['.esc_attr($path).'][validators][]" data-afe-validator-select>';
        foreach ($definitions as $key=>$definition) {
            $disabled=($key==='custom_regex'&&!$canRegex&&!isset($configs[$key]))?' disabled':'';
            echo '<option value="'.esc_attr($key).'" '.selected(in_array($key,$selected,true),true,false).$disabled.'>'.esc_html($definition->label).'</option>';
        }
        echo '</select></label><div class="afe-validator-details">';
        foreach ($definitions as $key=>$definition) {
            $config=(array)($configs[$key]??[]);
            $hidden=in_array($key,$selected,true)?'':' hidden';
            echo '<div class="afe-validator-detail" data-afe-validator-detail="'.esc_attr($key).'"'.$hidden.'><strong>'.esc_html($definition->label).'</strong>';
            echo '<label><span>پیام خطای سفارشی</span><input name="field_override['.esc_attr($path).'][validator_messages]['.esc_attr($key).']" value="'.esc_attr((string)($config['message']??'')).'" placeholder="'.esc_attr($definition->defaultMessage).'"></label>';
            if ($key==='custom_regex') {
                echo '<label><span>Regex <small>بدون delimiter، حداکثر '.esc_html((string)ValidatorRegistry::CUSTOM_REGEX_MAX_LENGTH).' کاراکتر</small></span><input class="afe-code" name="field_override['.esc_attr($path).'][custom_regex_pattern]" value="'.esc_attr((string)($config['pattern']??'')).'" '.($canRegex?'':'readonly').'></label>';
                echo '<label><span>Flagها</span><input name="field_override['.esc_attr($path).'][custom_regex_flags]" value="'.esc_attr((string)($config['flags']??'u')).'" placeholder="imu" maxlength="5" '.($canRegex?'':'readonly').'></label>';
                if (!$canRegex) echo '<p class="description">تعریف یا تغییر Custom Regex فقط با capability <code>'.esc_html(Capabilities::MANAGE_SETTINGS).'</code> مجاز است.</p>';
            }
            echo '</div>';
        }
        echo '</div></div>';
    }

    private function sanitizeOrderOverrides(array $codeForm): array
    {
        $out=['steps'=>[],'repeaters'=>[]];
        $postedSteps=(array)($_POST['field_order']??[]);
        foreach ((array)($codeForm['steps']??[]) as $step) {
            $key=(string)($step['key']??'');
            $original=array_values(array_filter(array_map(static fn($item)=>(string)($item['name']??''),(array)($step['items']??[]))));
            $posted=array_map(static fn($value)=>sanitize_text_field(wp_unslash((string)$value)),(array)($postedSteps[$key]??[]));
            $clean=$this->normalizedOrder($posted,$original);
            if ($clean!==$original) $out['steps'][$key]=$clean;
        }
        $originalRepeaters=[];
        foreach ((array)($codeForm['steps']??[]) as $step) foreach ((array)($step['items']??[]) as $item) $this->collectRepeaterOrders((array)$item,'',$originalRepeaters);
        foreach ((array)($_POST['repeater_order']??[]) as $entry) {
            if (!is_array($entry)) continue;
            $path=sanitize_text_field(wp_unslash((string)($entry['path']??'')));
            if ($path==='' || !isset($originalRepeaters[$path])) continue;
            $posted=array_map(static fn($value)=>sanitize_text_field(wp_unslash((string)$value)),(array)($entry['items']??[]));
            $clean=$this->normalizedOrder($posted,$originalRepeaters[$path]);
            if ($clean!==$originalRepeaters[$path]) $out['repeaters'][$path]=$clean;
        }
        if ($out['steps']===[]) unset($out['steps']);
        if ($out['repeaters']===[]) unset($out['repeaters']);
        return $out;
    }

    /** @param list<string> $posted @param list<string> $allowed @return list<string> */
    private function normalizedOrder(array $posted,array $allowed): array
    {
        $clean=[];
        foreach ($posted as $name) if (in_array($name,$allowed,true) && !in_array($name,$clean,true)) $clean[]=$name;
        foreach ($allowed as $name) if (!in_array($name,$clean,true)) $clean[]=$name;
        return $clean;
    }

    /** @param array<string,list<string>> $out */
    private function collectRepeaterOrders(array $field,string $prefix,array &$out): void
    {
        $name=(string)($field['name']??''); if ($name==='') return;
        $path=$prefix===''?$name:$prefix.'.'.$name;
        if (($field['type']??'')!=='repeater') return;
        $out[$path]=array_values(array_filter(array_map(static fn($child)=>(string)($child['name']??''),(array)($field['fields']??[]))));
        foreach ((array)($field['fields']??[]) as $child) $this->collectRepeaterOrders((array)$child,$path,$out);
    }

    /** @return list<array<string,mixed>> */
    private function sanitizeValidatorOverrides(array $values,array $field,array $existing): array
    {
        $type=(string)($field['type']??'');
        $available=$this->validators->forFieldType($type);
        $selected=array_values(array_unique(array_map('sanitize_key',(array)($values['validators']??[]))));
        $messages=(array)($values['validator_messages']??[]);
        $existingConfigs=[];
        foreach ((array)($existing['validators']??[]) as $config) {
            if (is_string($config)) $config=['key'=>$config];
            if (is_array($config)&&!empty($config['key'])) $existingConfigs[(string)$config['key']]=$config;
        }
        $out=[];
        foreach ($selected as $key) {
            if (!isset($available[$key])) continue;
            if ($key==='custom_regex' && !current_user_can(Capabilities::MANAGE_SETTINGS)) {
                if (isset($existingConfigs[$key])) $out[]=$existingConfigs[$key];
                continue;
            }
            $definition=$available[$key];
            $message=sanitize_text_field(wp_unslash((string)($messages[$key]??'')));
            $config=['key'=>$key];
            if ($message!=='') $config['message']=$message;
            if ($key==='custom_regex') {
                $pattern=trim(wp_unslash((string)($values['custom_regex_pattern']??'')));
                $flags=ValidatorRegistry::sanitizeRegexFlags(sanitize_text_field(wp_unslash((string)($values['custom_regex_flags']??'u'))));
                if ($pattern==='' || ValidatorRegistry::compileRegex($pattern,$flags)===null) {
                    wp_die('Custom Regex فیلد «'.esc_html((string)($field['label']??$field['name']??'')).'» معتبر نیست. Regex را بدون delimiter وارد کنید؛ Flagهای مجاز: '.esc_html(ValidatorRegistry::CUSTOM_REGEX_FLAGS).'.','Regex نامعتبر',['response'=>400,'back_link'=>true]);
                }
                if ($message==='') wp_die('برای Custom Regex پیام خطای سفارشی الزامی است.','پیام خطا الزامی است',['response'=>400,'back_link'=>true]);
                $config['pattern']=$pattern;
                $config['flags']=$flags;
            }
            $out[]=$config;
        }
        if (!current_user_can(Capabilities::MANAGE_SETTINGS) && isset($existingConfigs['custom_regex']) && !in_array('custom_regex',$selected,true)) $out[]=$existingConfigs['custom_regex'];
        return $out;
    }

    private function save(string $slug): void
    {
        check_admin_referer('afe_save_form_'.$slug,'afe_form_nonce');
        $existing=$this->forms->overrides($slug);
        $codeForm=$this->registry->get($slug)->toArray();
        $codeFlat=$this->flatten((array)($codeForm['steps']??[]));
        $overrides=[
            'title'=>sanitize_text_field(wp_unslash($_POST['override_title']??'')),
            'description'=>sanitize_textarea_field(wp_unslash($_POST['override_description']??'')),
            'fields'=>[],
        ];
        foreach ((array)($_POST['field_override']??[]) as $path=>$values) {
            $path=sanitize_text_field((string)$path);
            if (!is_array($values) || !isset($codeFlat[$path]) || (($codeFlat[$path]['type']??'')==='html')) continue;
            $fieldDef=(array)$codeFlat[$path];
            $existingField=(array)($existing['fields'][$path]??[]);
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
            if (($fieldDef['type']??'')==='date') {
                $dateMode=sanitize_key((string)($values['date_input_mode']??'inherit'));
                if (in_array($dateMode,['combined','picker','manual'],true)) $one['date_input_mode']=$dateMode;
            }
            if (in_array((string)($fieldDef['type']??''),['text','tel'],true)) {
                $maskKey=sanitize_key((string)($values['input_mask_key']??'inherit'));
                if ($maskKey==='none') {
                    $one['input_mask']=['key'=>'none'];
                } elseif ($maskKey==='custom') {
                    $pattern=trim(wp_unslash((string)($values['input_mask_pattern']??'')));
                    if (!InputMaskPattern::isValid($pattern)) {
                        wp_die('Input Mask سفارشی فیلد «'.esc_html((string)($fieldDef['label']??$fieldDef['name']??'')).'» معتبر نیست. Syntax مجاز: 9 برای رقم، A برای حرف و * برای حرف یا رقم.','Input Mask نامعتبر',['response'=>400,'back_link'=>true]);
                    }
                    $one['input_mask']=['key'=>'custom','pattern'=>$pattern];
                } elseif ($maskKey!=='inherit') {
                    $maskDefinition=$this->inputMasks->get($maskKey);
                    if ($maskDefinition && $maskDefinition->supports((string)($fieldDef['type']??''))) $one['input_mask']=['key'=>$maskDefinition->key];
                }
            }
            if (in_array((string)($fieldDef['type']??''),['text','textarea','tel','email','url'],true)) {
                $characterMode=sanitize_key((string)($values['character_mode']??'inherit'));
                if (in_array($characterMode,['normal','digits','persian','english','alnum'],true)) $one['character_mode']=$characterMode;
                foreach (['allowed_extra','forbidden_extra'] as $charKey) {
                    $charValue=sanitize_text_field(wp_unslash((string)($values[$charKey]??'')));
                    if ($charValue!=='') $one[$charKey]=function_exists('mb_substr')?mb_substr($charValue,0,50):substr($charValue,0,50);
                }
                foreach (['min_length','max_length','exact_length'] as $lengthKey) {
                    if (($values[$lengthKey]??'')!=='') $one[$lengthKey]=max(1,min(10000,(int)$values[$lengthKey]));
                }
                if (isset($one['min_length'],$one['max_length']) && $one['min_length']>$one['max_length']) {
                    [$one['min_length'],$one['max_length']]=[$one['max_length'],$one['min_length']];
                }
            }
            $validatorOverrides=$this->sanitizeValidatorOverrides($values,$fieldDef,$existingField);
            if ($validatorOverrides!==[]) $one['validators']=$validatorOverrides;
            if (($values['max_files']??'')!=='') $one['max_files']=max(1,(int)$values['max_files']);
            if (($values['max_size_mb']??'')!=='') $one['max_size_mb']=max(1,(int)$values['max_size_mb']);
            if (!empty($values['accept'])) {
                $one['accept']=array_values(array_filter(array_map('sanitize_text_field',array_map('trim',explode(',',wp_unslash($values['accept']))))));
            }
            if ($one) $overrides['fields'][$path]=$one;
        }
        $order=$this->sanitizeOrderOverrides($codeForm);
        if ($order!==[]) $overrides['order']=$order;
        $overrides['steps']=[];
        $codeSteps=[];
        foreach ((array)($codeForm['steps']??[]) as $stepDef) $codeSteps[(string)($stepDef['key']??'')]=$stepDef;
        foreach ((array)($_POST['step_template']??[]) as $stepKey=>$templateValue) {
            $stepKey=sanitize_key((string)$stepKey);
            if (!isset($codeSteps[$stepKey])) continue;
            $templateValue=wp_kses_post(wp_unslash($templateValue));
            $default=wp_kses_post($this->templates->defaultStep($codeSteps[$stepKey]));
            $templateValue=$this->templates->normalizeOverride($templateValue,$default);
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
            'lock_after_submit'=>!empty($_POST['lock_after_submit']),
            'show_edit_request_button'=>!empty($_POST['show_edit_request_button']),
            'lock_warning'=>sanitize_textarea_field(wp_unslash($_POST['lock_warning']??'')),
            'brand_mark_mode'=>in_array(sanitize_key((string)($_POST['brand_mark_mode']??'default')),['default','image','none'],true)?sanitize_key((string)$_POST['brand_mark_mode']):'default',
            'brand_mark_image_id'=>absint($_POST['brand_mark_image_id']??0),
            'brand_mark_image_url'=>esc_url_raw(wp_unslash($_POST['brand_mark_image_url']??'')),
            'brand_mark_alt'=>sanitize_text_field(wp_unslash($_POST['brand_mark_alt']??'')),
            'workflow'=>$workflowClean,
        ];
        $duplicateAllowed=array_keys($this->duplicatePolicy->fieldLabels($codeForm));
        $duplicateFields=array_values(array_unique(array_intersect(
            $duplicateAllowed,
            array_map(static fn($one)=>sanitize_text_field(wp_unslash((string)$one)),(array)($_POST['duplicate_fields']??[]))
        )));
        $duplicateBehavior=sanitize_key((string)($_POST['duplicate_behavior']??'block'));
        if(!in_array($duplicateBehavior,DuplicatePolicy::BEHAVIORS,true)) $duplicateBehavior='block';
        $settings['duplicate']=[
            'enabled'=>!empty($_POST['duplicate_enabled']) && $duplicateFields!==[],
            'fields'=>$duplicateFields,
            'behavior'=>$duplicateBehavior,
            'message'=>sanitize_textarea_field(wp_unslash($_POST['duplicate_message']??'')),
        ];
        if(!empty($_POST['actions_override_enabled'])){
            $actionForm=$codeForm;
            $actionForm['workflow']=$workflowClean!==[]?$workflowClean:(array)($codeForm['workflow']??[]);
            $settings['actions']=(new ActionConfigSanitizer($this->actions,$this->events))->sanitizeGroups(
                (array)($_POST['action_groups']??[]),
                $actionForm
            );
        }
        $previewSubmitted=wp_kses_post(wp_unslash($_POST['preview_template']??''));
        $previewDefault=wp_kses_post($this->templates->defaultPreview($codeForm));
        $previewOverride=$this->templates->normalizeOverride($previewSubmitted,$previewDefault);
        if ($previewOverride!=='') $settings['preview_template']=$previewOverride;

        $styleIsolation=sanitize_key((string)($_POST['style_isolation']??'inherit'));
        if (array_key_exists($styleIsolation, StyleIsolationManager::modes())) {
            $settings['style_isolation']=$styleIsolation;
        }

        $template=wp_kses_post(wp_unslash($_POST['template_html']??''));
        $template=$this->templates->normalizeOverride($template,wp_kses_post($this->templates->defaultForm($codeForm)));
        $css=wp_strip_all_tags(wp_unslash($_POST['custom_css']??''));
        $js=current_user_can(Capabilities::MANAGE_SETTINGS) ? wp_unslash($_POST['custom_js']??'') : '';
        $this->forms->saveAdminConfig($slug,$overrides,$template,$css,$js,$settings);
        wp_safe_redirect(admin_url('admin.php?page=alavi-form-engine-forms&form='.$slug.'&updated=1')); exit;
    }

    /** @param list<string> $tokens */
    private function renderTemplateEditor(
        string $name,
        string $label,
        string $description,
        string $default,
        string $stored,
        array $tokens,
        int $rows = 10,
        bool $compact = false
    ): void {
        $value=$this->templates->editorValue($stored,$default);
        $hasOverride=$this->templates->hasOverride($stored,$default);
        $tokenHtml='';
        foreach ($tokens as $token) $tokenHtml.='<code>'.esc_html($token).'</code>';
        $classes='afe-template-editor'.($compact?' afe-template-editor--compact':'');
        echo '<div class="'.esc_attr($classes).'" data-afe-template-editor>';
        echo '<div class="afe-template-editor__head"><div><h3>'.esc_html($label).'</h3><p>'.esc_html($description).'</p></div><span class="afe-template-status'.($hasOverride?' is-custom':' is-default').'" data-afe-template-status>'.($hasOverride?'قالب سفارشی':'قالب پیش‌فرض').'</span></div>';
        if ($tokenHtml!=='') echo '<div class="afe-template-tokens"><span>Tokenهای قابل استفاده:</span>'.$tokenHtml.'</div>';
        echo '<textarea class="afe-code" name="'.esc_attr($name).'" rows="'.esc_attr((string)$rows).'" spellcheck="false" data-afe-template-input>'.esc_textarea($value).'</textarea>';
        echo '<textarea hidden tabindex="-1" aria-hidden="true" data-afe-template-default>'.esc_textarea($default).'</textarea>';
        echo '<div class="afe-template-editor__footer"><button type="button" class="button" data-afe-template-reset>بازگردانی به قالب پیش‌فرض</button><span data-afe-template-hint>'.($hasOverride?'این فرم در حال استفاده از Override ذخیره‌شده است.':'در حال استفاده از قالب پیش‌فرض کد/AFE است؛ تا زمان تغییر، Override ذخیره نمی‌شود.').'</span></div>';
        echo '</div>';
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
