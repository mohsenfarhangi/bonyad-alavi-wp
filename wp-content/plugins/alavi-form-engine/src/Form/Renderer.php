<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Form;

use BonyadAlavi\FormEngine\DataSource\DataSourceManager;
use BonyadAlavi\FormEngine\Repository\FormRepository;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use BonyadAlavi\FormEngine\Localization\LocaleDateService;
use BonyadAlavi\FormEngine\Security\SecurityManager;
use BonyadAlavi\FormEngine\Submission\SubmissionService;
use BonyadAlavi\FormEngine\Style\StyleIsolationManager;

final class Renderer
{
    private int $currentSubmissionId = 0;
    public function __construct(
        private readonly FormRegistry $registry,
        private readonly FormRepository $forms,
        private readonly SubmissionRepository $submissions,
        private readonly SubmissionService $service,
        private readonly DataSourceManager $sources,
        private readonly SecurityManager $security,
        private readonly StyleIsolationManager $styleIsolation,
        private readonly LocaleDateService $dates,
        private readonly PreviewRenderer $previewRenderer
    ) {}

    public function shortcode(array|string $atts = []): string
    {
        $atts = shortcode_atts(['id'=>'jihadi-group-registration'], (array)$atts, 'alavi_form');
        return $this->render((string)$atts['id']);
    }

    public function render(string $slug): string
    {
        if (!$this->registry->has($slug)) {
            return '<div class="afe-notice afe-notice-error">فرم موردنظر پیدا نشد.</div>';
        }

        $form = $this->service->resolvedForm($slug);
        $row = $this->forms->row($slug);
        $editing = $this->resolveEditing($form);
        $values = $editing['row']['data'] ?? [];
        $credentials = $editing['credentials'] ?? [];
        $this->currentSubmissionId = !empty($editing['row']['id']) ? (int)$editing['row']['id'] : 0;
        // A public/front-end form must always respect the persisted submission lock.
        // Administrators can unlock/edit from wp-admin; a capability must not silently bypass
        // the applicant-facing lock and expose edit controls on the public page.
        $locked = !empty($editing['row']['is_locked']);
        $previewEnabled = !empty($form['settings']['preview_enabled']);

        wp_enqueue_style('afe-frontend');
        if ($this->usesJalaliCalendar($form)) {
            wp_enqueue_style('afe-jalali-datepicker');
            wp_enqueue_script('afe-jalali-datepicker');
        }
        wp_enqueue_script('afe-frontend');

        if ($row && trim((string)$row->custom_css) !== '') {
            wp_add_inline_style('afe-frontend', (string)$row->custom_css);
        }
        if ($row && trim((string)$row->custom_js) !== '') {
            wp_add_inline_script('afe-frontend', "\n/* AFE per-form custom JS */\n" . (string)$row->custom_js, 'after');
        }

        if ($locked && $previewEnabled) {
            $shellClass = $this->styleIsolation->shellClass($form);
            return $this->renderLockedPreview($form, $editing, $shellClass);
        }

        $stepHtml = [];
        $dataSteps = count($form['steps']);
        $total = $dataSteps + ($previewEnabled ? 1 : 0);
        foreach ($form['steps'] as $index=>$step) {
            $items=[]; $byName=[];
            foreach ($step['items'] as $item) {
                $html = $this->renderItem($item, $values, $form, null);
                $items[] = $html;
                if (!empty($item['name'])) $byName[$item['name']] = $html;
            }
            $body = implode('', $items);
            if (!empty($step['template'])) {
                $body = $this->applyTokens((string)$step['template'], $byName, $body);
            }
            $stepHtml[] = '<section class="afe-step'.($index===0?' is-active':'').'" data-step="'.esc_attr((string)$index).'">'
                . '<div class="afe-step-heading"><span class="afe-step-kicker">مرحله '.esc_html((string)($index+1)).' از '.esc_html((string)$total).'</span>'
                . '<h2>'.esc_html($step['title']).'</h2>'
                . (!empty($step['description'])?'<p>'.esc_html((string)$step['description']).'</p>':'')
                . '</div><div class="afe-grid">'.$body.'</div>'
                . $this->stepActions($index,$dataSteps,$form,$previewEnabled,$locked)
                . '</section>';
        }
        if ($previewEnabled && !$locked) {
            $previewIndex=$dataSteps;
            $previewBody=$this->previewRenderer->render($form, [], 0, true);
            $warning='';
            if (!empty($form['settings']['lock_after_submit'])) {
                $warning='<div class="afe-lock-warning"><strong>توجه قبل از ثبت نهایی</strong><p>'.esc_html((string)($form['settings']['lock_warning']??'')).'</p><label><input type="checkbox" data-afe-lock-confirm> متن بالا را مطالعه کردم و ثبت نهایی را تأیید می‌کنم.</label></div>';
            }
            $stepHtml[]='<section class="afe-step afe-preview-step" data-afe-preview-step="1" data-step="'.esc_attr((string)$previewIndex).'"><div class="afe-step-heading"><span class="afe-step-kicker">مرحله '.esc_html((string)($previewIndex+1)).' از '.esc_html((string)$total).'</span><h2>'.esc_html((string)($form['settings']['preview_title']??'پیش‌نمایش اطلاعات ارسالی')).'</h2><p>'.esc_html((string)($form['settings']['preview_description']??'')).'</p></div>'.$previewBody.$warning.$this->previewActions().'</section>';
        }

        $steps = implode('', $stepHtml);
        $slogan = trim((string)($form['settings']['header_slogan'] ?? ''));
        $sloganHtml = $slogan !== '' ? '<div class="afe-form-slogan">'.esc_html($slogan).'</div>' : '';
        $template = $row ? trim((string)$row->template_html) : '';
        if ($template !== '') {
            $content = strtr(wp_kses_post($template), [
                '{{steps}}'=>$steps,
                '{{title}}'=>esc_html($form['title']),
                '{{description}}'=>esc_html($form['description']),
                '{{slogan}}'=>$sloganHtml,
            ]);
        } else {
            $content = '<header class="afe-form-header"><div class="afe-brand-mark" aria-hidden="true"><span></span><span></span></div>'
                . '<div><h1>'.esc_html($form['title']).'</h1><p>'.esc_html($form['description']).'</p>'.$sloganHtml.'</div></header>'
                . $this->progress($form, $total) . $steps;
        }

        $hidden = '<input type="hidden" name="action" value="afe_submit">'
            . '<input type="hidden" name="afe_form_slug" value="'.esc_attr($slug).'">'
            . '<input type="hidden" name="_afe_nonce" value="'.esc_attr(wp_create_nonce('afe_submit_'.$slug)).'">'
            . '<div class="afe-hp" aria-hidden="true"><label>Website<input type="text" name="_afe_website" tabindex="-1" autocomplete="off"></label></div>';

        if (!empty($editing['row'])) {
            $hidden .= '<input type="hidden" name="_afe_submission_id" value="'.esc_attr((string)$editing['row']['id']).'">';
            if (!empty($credentials['token'])) $hidden .= '<input type="hidden" name="_afe_edit_token" value="'.esc_attr($credentials['token']).'">';
            if (!empty($credentials['tracking'])) $hidden .= '<input type="hidden" name="_afe_tracking" value="'.esc_attr($credentials['tracking']).'">';
        }

        $editingNotice = '';
        if (!empty($editing['row'])) {
            $editingNotice = '<div class="afe-notice afe-notice-info">در حال ویرایش ثبت با کد رهگیری <strong>'.esc_html((string)$editing['row']['tracking_code']).'</strong></div>';
        } elseif (!empty($editing['error'])) {
            $editingNotice = '<div class="afe-notice afe-notice-error">'.esc_html((string)$editing['error']).'</div>';
        }

        if ($locked && !empty($editing['row'])) {
            $editingNotice = '<div class="afe-notice afe-notice-warning"><strong>این ثبت قفل شده است.</strong><span>امکان ویرایش یا ذخیره اطلاعات تا زمان تأیید درخواست ویرایش وجود ندارد.</span></div>';
        }
        $lockPanel = $locked && !empty($editing['row']) ? $this->editRequestPanel($form,$editing) : '';
        $resumeBox = $this->resumeBox($form, !empty($editing['row']));

        $shellClass = $this->styleIsolation->shellClass($form);

        return '<div class="'.esc_attr($shellClass).'" data-afe-isolation="'.esc_attr($this->styleIsolation->modeForForm($form)).'" dir="rtl">'.$editingNotice.$lockPanel.$resumeBox
            . '<form class="afe-form" method="post" enctype="multipart/form-data" novalidate data-form="'.esc_attr($slug).'"'.($locked?' data-afe-readonly="1"':'').' data-afe-lock-after-submit="'.(!empty($form['settings']['lock_after_submit'])?'1':'0').'" data-afe-preview-enabled="'.($previewEnabled?'1':'0').'" data-afe-lock-warning="'.esc_attr((string)($form['settings']['lock_warning']??'')).'">'
            . $hidden . $content
            . ($locked?'':'<div class="afe-captcha-wrap">'.$this->security->captchaMarkup((string)($form['settings']['captcha']??'custom')).'</div>')
            . '<div class="afe-form-result" aria-live="polite"></div>'
            . '</form></div>';
    }

    private function progress(array $form, int $total): string
    {
        if (empty($form['settings']['show_progress'])) return '';
        $labels='';
        foreach ($form['steps'] as $i=>$step) {
            $labels .= '<button type="button" class="afe-progress-step'.($i===0?' is-active':'').'" data-goto-step="'.esc_attr((string)$i).'" aria-label="'.esc_attr($step['title']).'"><span>'.esc_html((string)($i+1)).'</span></button>';
        }
        if (!empty($form['settings']['preview_enabled'])) {
            $i=count($form['steps']);
            $labels .= '<button type="button" class="afe-progress-step afe-progress-step--preview" data-goto-step="'.esc_attr((string)$i).'" aria-label="'.esc_attr((string)($form['settings']['preview_title']??'پیش‌نمایش اطلاعات ارسالی')).'"><span>'.esc_html((string)($i+1)).'</span></button>';
        }
        return '<div class="afe-progress"><div class="afe-progress-meta"><span class="afe-progress-text">مرحله ۱ از '.esc_html((string)$total).'</span><strong class="afe-progress-percent">'.esc_html((string)round(100/max(1,$total))).'٪</strong></div>'
            . '<div class="afe-progress-track"><span style="width:'.esc_attr((string)(100/max(1,$total))).'%"></span></div>'
            . '<div class="afe-progress-dots">'.$labels.'</div></div>';
    }

    private function stepActions(int $index, int $dataSteps, array $form, bool $previewEnabled, bool $readOnly): string
    {
        $html='<div class="afe-step-actions">';
        if ($index>0) $html.='<button class="afe-btn afe-btn-secondary afe-prev" type="button">مرحله قبل</button>';
        if ($readOnly) { if ($index<$dataSteps-1) $html.='<button class="afe-btn afe-btn-primary afe-next" type="button">مرحله بعد</button>'; return $html.'</div>'; }
        if (!empty($form['settings']['save_draft'])) $html.='<button class="afe-btn afe-btn-ghost afe-save-draft" type="button">ذخیره پیش‌نویس</button>';
        if ($index<$dataSteps-1) $html.='<button class="afe-btn afe-btn-primary afe-next" type="button">مرحله بعد</button>';
        elseif ($previewEnabled) $html.='<button class="afe-btn afe-btn-primary afe-next" type="button">پیش‌نمایش اطلاعات</button>';
        else $html.='<button class="afe-btn afe-btn-primary afe-submit" type="submit">ثبت نهایی اطلاعات</button>';
        return $html.'</div>';
    }

    private function previewActions(): string
    {
        return '<div class="afe-step-actions"><button class="afe-btn afe-btn-secondary afe-prev" type="button">بازگشت و اصلاح اطلاعات</button><button class="afe-btn afe-btn-primary afe-submit" type="submit">تأیید و ثبت نهایی اطلاعات</button></div>';
    }

    private function renderLockedPreview(array $form, array $editing, string $shellClass): string
    {
        $row=(array)$editing['row'];
        $content=$this->previewRenderer->render($form,(array)($row['data']??[]),(int)$row['id'],false);
        $notice=(!empty($_GET['afe_submitted'])?'<div class="afe-notice afe-notice-success"><strong>اطلاعات با موفقیت ثبت شد.</strong><span>فرم اکنون قفل شده است.</span></div>':'').'<div class="afe-notice afe-notice-warning"><strong>این فرم پس از ثبت نهایی قفل شده است.</strong><span>اطلاعات زیر فقط قابل مشاهده است.</span></div>';
        return '<div class="'.esc_attr($shellClass).'" data-afe-isolation="'.esc_attr($this->styleIsolation->modeForForm($form)).'" dir="rtl">'.$notice.$this->editRequestPanel($form,$editing).'<div class="afe-locked-preview"><header class="afe-form-header"><div><h1>'.esc_html((string)$form['title']).'</h1><p>کد رهگیری: <strong dir="ltr">'.esc_html((string)$row['tracking_code']).'</strong></p></div></header>'.$content.'</div></div>';
    }

    private function editRequestPanel(array $form, array $editing): string
    {
        $row=(array)($editing['row']??[]);
        if (!$row || empty($form['settings']['show_edit_request_button'])) return '';
        $status=(string)($row['edit_request_status']??'');
        if ($status==='pending') return '<div class="afe-edit-request afe-edit-request--pending"><strong>درخواست ویرایش شما ثبت شده است.</strong><span>پس از بررسی مدیر، وضعیت دسترسی ویرایش تغییر می‌کند.</span></div>';
        $credentials=(array)($editing['credentials']??[]);
        $hidden='<input type="hidden" name="action" value="afe_request_edit"><input type="hidden" name="afe_form_slug" value="'.esc_attr((string)$form['slug']).'"><input type="hidden" name="submission_id" value="'.esc_attr((string)$row['id']).'"><input type="hidden" name="nonce" value="'.esc_attr(wp_create_nonce('afe_edit_request_'.(string)$form['slug'])).'">';
        if (!empty($credentials['token'])) $hidden.='<input type="hidden" name="_afe_edit_token" value="'.esc_attr((string)$credentials['token']).'">';
        if (!empty($credentials['tracking'])) $hidden.='<input type="hidden" name="_afe_tracking" value="'.esc_attr((string)$credentials['tracking']).'">';
        return '<form class="afe-edit-request" data-afe-edit-request>'.$hidden.'<div><strong>نیاز به اصلاح اطلاعات دارید؟</strong><span>درخواست خود را برای مدیر ارسال کنید.</span></div><textarea name="reason" rows="2" placeholder="دلیل درخواست ویرایش (اختیاری)"></textarea><button type="submit" class="afe-btn afe-btn-secondary">درخواست ویرایش</button><div class="afe-edit-request__result" aria-live="polite"></div></form>';
    }

    private function renderItem(array $field, array $values, array $form, ?array $repeaterContext): string
    {
        if (($field['type']??'')==='html') return '<div class="afe-html-block afe-col-12">'.wp_kses_post((string)($field['html']??'')).'</div>';

        $name=$field['name'];
        $value = $repeaterContext !== null ? ($repeaterContext[$name]??($field['default']??'')) : ($values[$name]??($field['default']??''));
        if (($field['type']??'')==='repeater') {
            $rows=(array)$value;
            if (!$rows && $repeaterContext === null) $rows=$this->legacyRepeaterRows($field,$values);
            return $this->renderRepeater($field,$rows,$form);
        }

        $conditions = !empty($field['conditions']) ? esc_attr(wp_json_encode($field['conditions'],JSON_UNESCAPED_UNICODE)) : '';
        $classes='afe-field afe-col-'.(int)($field['width']??12);
        $attrs=' data-field="'.esc_attr($name).'" data-field-type="'.esc_attr((string)$field['type']).'" data-required="'.(!empty($field['required'])?'1':'0').'"';
        if ($conditions!=='') $attrs.=' data-conditions="'.$conditions.'"';
        if (!empty($field['unique_group'])) $attrs.=' data-unique-group="'.esc_attr((string)$field['unique_group']).'"';

        $label = '<label class="afe-label" for="afe_'.esc_attr($name).'">'.esc_html((string)($field['label']??$name)).(!empty($field['required'])?'<span class="afe-required">*</span>':'').'</label>';
        $desc = !empty($field['description'])?'<small class="afe-help">'.esc_html((string)$field['description']).'</small>':'';
        $input = $this->input($field,$value,$values,null);

        return '<div class="'.$classes.'"'.$attrs.'>'.$label.$input.$desc.'<div class="afe-field-error"></div></div>';
    }

    private function input(array $field, mixed $value, array $allValues, ?string $nameOverride): string
    {
        $name = $nameOverride ?: 'afe_data['.$field['name'].']';
        $normalizePrefix=trim((string)($field['normalize_input_prefix']??''));
        if ($normalizePrefix !== '' && is_scalar($value)) {
            $raw=(string)$value;
            if (str_starts_with(strtoupper($raw),strtoupper($normalizePrefix))) $value=substr($raw,strlen($normalizePrefix));
        }
        $id = 'afe_' . sanitize_html_class(str_replace(['[',']'],'_',$name));
        $required = !empty($field['required']) && empty($field['conditions']) ? ' required' : '';
        $placeholder = !empty($field['placeholder']) ? ' placeholder="'.esc_attr((string)$field['placeholder']).'"' : '';
        $common = ' id="'.esc_attr($id).'" name="'.esc_attr($name).'"'.$placeholder.$required.$this->inputAttributes($field);

        $control = match ($field['type']) {
            'textarea' => '<textarea class="afe-control" rows="5"'.$common.'>'.esc_textarea((string)$value).'</textarea>',
            'select' => $this->select($field,$value,$allValues,$name,$id,$required),
            'radio' => $this->radio($field,$value,$name),
            'file' => $this->fileInput($field, $id),
            'number' => '<input class="afe-control" type="number" inputmode="numeric"'.$common.' value="'.esc_attr((string)$value).'">',
            'date' => $this->dateInput($field,$value,$common),
            'tel' => '<input class="afe-control" type="tel"'.$common.' value="'.esc_attr((string)$value).'">',
            'email' => '<input class="afe-control" type="email"'.$common.' value="'.esc_attr((string)$value).'">',
            'url' => '<input class="afe-control" type="url"'.$common.' value="'.esc_attr((string)$value).'">',
            default => '<input class="afe-control" type="text"'.$common.' value="'.esc_attr((string)$value).'">',
        };

        $prefix=trim((string)($field['input_prefix']??''));
        if ($prefix !== '' && in_array((string)($field['type']??''),['text','tel','number'],true)) {
            return '<div class="afe-input-affix" data-afe-input-affix><span class="afe-input-prefix" dir="ltr">'.esc_html($prefix).'</span>'.$control.'</div>';
        }
        return $control;
    }

    private function inputAttributes(array $field): string
    {
        $out='';
        $allowed=['maxlength','minlength','pattern','inputmode','autocomplete','dir','min','max','step','aria-label','aria-describedby'];
        foreach ((array)($field['attributes']??[]) as $key=>$value) {
            $key=strtolower(trim((string)$key));
            if ($key==='' || str_starts_with($key,'on')) continue;
            if (!in_array($key,$allowed,true) && !str_starts_with($key,'data-afe-')) continue;
            if ($value === false || $value === null) continue;
            if ($value === true) { $out.=' '.esc_attr($key); continue; }
            $out.=' '.esc_attr($key).'="'.esc_attr((string)$value).'"';
        }
        return $out;
    }

    private function legacyRepeaterRows(array $field,array $values): array
    {
        $map=(array)($field['legacy_row_map']??[]);
        if (!$map) return [];
        $row=[]; $has=false;
        foreach ($map as $legacy=>$child) {
            $value=$values[(string)$legacy]??'';
            $row[(string)$child]=$value;
            if ($value !== '' && $value !== null) $has=true;
        }
        return $has ? [$row] : [];
    }

    private function dateInput(array $field, mixed $value, string $common): string
    {
        $calendar=(string)($field['calendar']??'gregorian');
        if ($calendar==='jalali') {
            $placeholder = empty($field['placeholder']) ? ' placeholder="1403/01/01"' : '';
            return '<input class="afe-control afe-date afe-date-jalali" type="text" inputmode="numeric" data-jdp data-afe-calendar="jalali" autocomplete="off" pattern="[0-9۰-۹٠-٩]{4}/[0-9۰-۹٠-٩]{2}/[0-9۰-۹٠-٩]{2}"'.$common.$placeholder.' value="'.esc_attr((string)$value).'">';
        }
        return '<input class="afe-control afe-date afe-date-gregorian" type="date" data-afe-calendar="gregorian"'.$common.' value="'.esc_attr((string)$value).'">';
    }

    private function usesJalaliCalendar(array $form): bool
    {
        foreach ((array)($form['steps']??[]) as $step) {
            foreach ((array)($step['items']??[]) as $field) {
                if ($this->fieldUsesJalali((array)$field)) return true;
            }
        }
        return false;
    }

    private function fieldUsesJalali(array $field): bool
    {
        if (($field['type']??'')==='date' && ($field['calendar']??'gregorian')==='jalali') return true;
        if (($field['type']??'')==='repeater') {
            foreach ((array)($field['fields']??[]) as $child) {
                if ($this->fieldUsesJalali((array)$child)) return true;
            }
        }
        return false;
    }

    private function select(array $field, mixed $value, array $allValues, string $name, string $id, string $required): string
    {
        $options=(array)($field['options']??[]);
        if (!empty($field['source'])) {
            $options=$this->sources->resolve($field['source'],$allValues);
        }

        $multiple=!empty($field['multiple']);
        $mode=in_array(($field['select_mode']??'custom'),['custom','native'],true)
            ? (string)($field['select_mode']??'custom')
            : 'custom';
        $placeholder=trim((string)($field['placeholder']??''));
        if ($placeholder==='') $placeholder='انتخاب کنید';
        $searchable=array_key_exists('searchable',$field) && $field['searchable'] !== null
            ? (!empty($field['searchable']) ? '1' : '0')
            : 'auto';
        $searchThreshold=max(1,(int)($field['search_threshold']??7));

        // Multiple compatibility classes are intentional. Themes commonly use one
        // of these to opt fields out of their global Select2 initializers.
        $classes='afe-control afe-select afe-select-source afe-no-select2 no-select2 select2-ignore';
        if ($mode==='custom') $classes.=' afe-select-custom-source';

        $attrs=' data-afe-select-mode="'.esc_attr($mode).'"'
            .' data-afe-select-placeholder="'.esc_attr($placeholder).'"'
            .' data-afe-select-searchable="'.esc_attr($searchable).'"'
            .' data-afe-select-search-threshold="'.esc_attr((string)$searchThreshold).'"';
        if (!empty($field['depends_on'])) {
            $attrs.=' data-depends-on="'.esc_attr((string)$field['depends_on']).'"';
            if (is_array($field['source']??null)) $attrs.=' data-source-level="'.esc_attr((string)($field['source']['level']??'')).'"';
        }

        $html='<select class="'.esc_attr($classes).'" id="'.esc_attr($id).'" name="'.esc_attr($name.($multiple?'[]':'')).'"'.($multiple?' multiple':'').$required.$attrs.'>';
        if (!$multiple) $html.='<option value="">'.esc_html($placeholder).'</option>';

        // Only a true PHP list (0..n sequential keys) means "value equals label".
        // Numeric associative keys are valid option values too (e.g. Iran geography
        // IDs such as 101, 1000001). The previous is_int() check incorrectly
        // converted those IDs to their labels and broke dependent geo selects.
        $optionsAreList = array_is_list($options);
        foreach ($options as $key=>$label) {
            if ($optionsAreList) $key=$label;
            $selected = $multiple ? in_array((string)$key,array_map('strval',(array)$value),true) : (string)$key===(string)$value;
            $html.='<option value="'.esc_attr((string)$key).'"'.selected($selected,true,false).'>'.esc_html((string)$label).'</option>';
        }
        return $html.'</select>';
    }

    private function radio(array $field, mixed $value, string $name): string
    {
        $html='<div class="afe-radio-group">';
        foreach ((array)($field['options']??[]) as $key=>$label) {
            if (is_int($key)) $key=$label;
            $html.='<label class="afe-radio"><input type="radio" name="'.esc_attr($name).'" value="'.esc_attr((string)$key).'"'.checked((string)$key,(string)$value,false).'>'
                .'<span>'.esc_html((string)$label).'</span></label>';
        }
        return $html.'</div>';
    }

    private function fileInput(array $field, string $id): string
    {
        $accept = implode(',', array_map('esc_attr',(array)($field['accept']??[])));
        $multiple = !empty($field['multiple']);
        $maxFiles = max(1, (int)($field['max_files']??1));
        $maxSize = max(1, (int)($field['max_size_mb']??5));
        $fieldKey = (string)$field['name'];

        // Existing uploads are queried by BOTH submission id and exact field key.
        // Do not preload all submission files into shared renderer state: a file
        // belonging to leader_photo must never be rendered in deputy_photo.
        $existing = $this->currentSubmissionId > 0
            ? $this->submissions->filesForField($this->currentSubmissionId, $fieldKey)
            : [];

        $inputId = $id . '_file';
        // Use one top-level multipart field per AFE FileField. Nested PHP upload
        // arrays (afe_files[field][]) are surprisingly easy for security/multipart
        // middleware to normalize incorrectly. A flat top-level key makes the
        // browser -> PHP contract deterministic. FileUploader still accepts the
        // legacy nested shape for cached pages/backwards compatibility.
        $uploadName = 'afe_upload_' . sanitize_key($fieldKey);
        $input = '<input class="afe-upload__input afe-no-theme-file" type="file" id="'.esc_attr($inputId).'" name="'.esc_attr($uploadName).'[]"'
            .($multiple?' multiple':'').' '.($accept?'accept="'.$accept.'"':'')
            .' data-afe-file-input="1" data-upload-name="'.esc_attr($uploadName).'" data-max-files="'.esc_attr((string)$maxFiles).'" data-max-size="'.esc_attr((string)$maxSize).'">';

        $dropzone = '<label class="afe-upload__dropzone" for="'.esc_attr($inputId).'">'
            .'<span class="afe-upload__icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M12 3a1 1 0 0 1 1 1v7.59l2.3-2.3a1 1 0 1 1 1.4 1.42l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.42l2.3 2.3V4a1 1 0 0 1 1-1Z"/><path d="M5 14a1 1 0 0 1 1 1v3h12v-3a1 1 0 1 1 2 0v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1Z"/></svg></span>'
            .'<span class="afe-upload__copy"><strong>'.($existing && $maxFiles===1 ? 'جایگزینی فایل' : 'فایل را انتخاب کنید').'</strong><small>یا فایل را اینجا رها کنید</small></span>'
            .'<span class="afe-upload__browse">انتخاب فایل</span>'
            .'</label>';

        $meta = '<div class="afe-upload__meta"><span>حداکثر '.esc_html((string)$maxFiles).' فایل</span><span>هر فایل تا '.esc_html((string)$maxSize).' مگابایت</span></div>';

        $existingHtml = '';
        if ($this->currentSubmissionId > 0) {
            // The manifest lets the server distinguish the new keep/remove UI
            // from a stale cached pre-1.0.13 form. Without it, old clients keep
            // all current files for backwards compatibility.
            $existingHtml .= '<input type="hidden" name="afe_existing_manifest['.esc_attr($fieldKey).']" value="1">';
        }
        if ($existing) {
            $existingHtml .= '<div class="afe-upload__existing" data-role="existing-files" aria-label="فایل‌های موجود">';
            $existingHtml .= '<div class="afe-upload__existing-head"><strong>فایل موجود</strong><span>فایل‌هایی که می‌خواهید برای این فیلد باقی بمانند انتخاب کنید.</span></div>';
            foreach ($existing as $file) {
                $fileId = (int)($file['id'] ?? 0);
                if ($fileId <= 0) continue;
                $name = (string)($file['original_name'] ?: basename((string)($file['path'] ?? 'file')));
                $mime = (string)($file['mime'] ?? '');
                $size = (int)($file['size'] ?? 0);
                $url = (string)($file['url'] ?? '');
                $isImage = str_starts_with($mime, 'image/') && $url !== '';
                $preview = $isImage
                    ? '<span class="afe-upload__existing-thumb"><img src="'.esc_url($url).'" alt="" loading="lazy"></span>'
                    : '<span class="afe-upload__existing-badge">'.esc_html(strtoupper((string)(pathinfo($name, PATHINFO_EXTENSION) ?: 'FILE'))).'</span>';

                // No <a> element is used intentionally. Elementor/theme lightbox
                // handlers therefore cannot hijack clicks on existing files.
                $existingHtml .= '<div class="afe-upload__existing-file is-selected" data-existing-file data-file-id="'.esc_attr((string)$fileId).'">'
                    .'<label class="afe-upload__existing-choice">'
                    .'<input type="checkbox" class="afe-upload__existing-toggle" data-existing-file-toggle name="afe_keep_files['.esc_attr($fieldKey).'][]" value="'.esc_attr((string)$fileId).'" checked>'
                    .'<span class="afe-upload__existing-check" aria-hidden="true"></span>'
                    .$preview
                    .'<span class="afe-upload__existing-info"><strong title="'.esc_attr($name).'">'.esc_html($name).'</strong>'
                    .'<small>'.esc_html(size_format($size)).' · فایل موجود</small></span>'
                    .'<span class="afe-upload__existing-state" data-role="existing-state">استفاده می‌شود</span>'
                    .'</label>'
                    .'<button type="button" class="afe-upload__remove-existing" data-action="remove-existing" aria-label="حذف '.esc_attr($name).'">حذف</button>'
                    .'</div>';
            }
            $existingHtml .= '</div>';
        }

        $selection = '<div class="afe-upload__selection" data-role="file-selection" aria-live="polite" hidden>'
            .'<div class="afe-upload__summary" data-role="file-summary"></div>'
            .'<div class="afe-upload__files" data-role="file-list"></div>'
            .'</div>';

        return '<div class="afe-upload" data-field-key="'.esc_attr($fieldKey).'" data-existing-count="'.esc_attr((string)count($existing)).'" data-existing-selected-count="'.esc_attr((string)count($existing)).'" data-max-files="'.esc_attr((string)$maxFiles).'" data-max-size="'.esc_attr((string)$maxSize).'">'
            .$input.$dropzone.$meta.$existingHtml.$selection.'</div>';
    }

    private function renderRepeater(array $field, array $rows, array $form): string
    {
        $min=(int)($field['min']??0);
        if (!$rows && $min>0) $rows=array_fill(0,$min,[]);
        $items='';
        foreach ($rows as $i=>$row) $items.=$this->repeaterRow($field,(int)$i,(array)$row,false);
        $template=$this->repeaterRow($field,-1,[],true);
        $conditions = !empty($field['conditions']) ? esc_attr(wp_json_encode($field['conditions'],JSON_UNESCAPED_UNICODE)) : '';
        $attrs=' data-field="'.esc_attr($field['name']).'" data-required="'.(!empty($field['required'])?'1':'0').'" data-min="'.esc_attr((string)$min).'" data-max="'.esc_attr((string)($field['max']??50)).'"';
        if ($conditions!=='') $attrs.=' data-conditions="'.$conditions.'"';
        return '<div class="afe-field afe-repeater afe-col-'.(int)($field['width']??12).'"'.$attrs.'>'
            .'<div class="afe-repeater-head"><label class="afe-label">'.esc_html((string)($field['label']??$field['name'])).(!empty($field['required'])?'<span class="afe-required">*</span>':'').'</label>'
            .'<span class="afe-repeater-count">'.esc_html((string)count($rows)).' مورد</span></div>'
            .'<div class="afe-repeater-items">'.$items.'</div>'
            .'<template class="afe-repeater-template">'.$template.'</template>'
            .'<button type="button" class="afe-btn afe-btn-secondary afe-repeater-add">'.esc_html((string)($field['add_button']??'افزودن مورد')).'</button>'
            .'<div class="afe-field-error"></div></div>';
    }

    private function repeaterRow(array $field, int $index, array $row, bool $template): string
    {
        $idx=$template?'__INDEX__':(string)$index;
        $html='<div class="afe-repeater-row" data-index="'.esc_attr($idx).'"><div class="afe-repeater-rowbar"><strong>مورد <span class="afe-repeater-number">'.($template?'#':esc_html((string)($index+1))).'</span></strong><button type="button" class="afe-repeater-remove" aria-label="حذف">×</button></div><div class="afe-grid">';
        foreach ($field['fields']??[] as $child) {
            if (($child['type']??'')==='html') { $html.='<div class="afe-html-block afe-col-12">'.wp_kses_post((string)$child['html']).'</div>'; continue; }
            $childName='afe_data['.$field['name'].']['.$idx.']['.$child['name'].']';
            $label='<label class="afe-label">'.esc_html((string)($child['label']??$child['name'])).(!empty($child['required'])?'<span class="afe-required">*</span>':'').'</label>';
            $html.='<div class="afe-field afe-col-'.(int)($child['width']??12).'" data-field="'.esc_attr($child['name']).'" data-required="'.(!empty($child['required'])?'1':'0').'">'
                .$label.$this->input($child,$row[$child['name']]??'', $row, $childName).'<div class="afe-field-error"></div></div>';
        }
        return $html.'</div></div>';
    }

    private function applyTokens(string $template, array $byName, string $items): string
    {
        $safe=wp_kses_post($template);
        $safe=str_replace('{{items}}',$items,$safe);
        foreach ($byName as $name=>$html) $safe=str_replace('{{field:'.$name.'}}',$html,$safe);
        return preg_replace('/\{\{field:[^}]+\}\}/','',$safe) ?: $safe;
    }

    private function resumeBox(array $form, bool $editing): string
    {
        if ($editing || empty($form['settings']['editing_enabled'])) return '';
        $modes=(array)($form['settings']['editing_modes']??[]);
        $html='';

        if (in_array('tracking',$modes,true)) {
            $html.='<div class="afe-resume"><div><strong>قبلاً این فرم را ذخیره کرده‌اید؟</strong><span>کد رهگیری را وارد کنید تا ادامه دهید.</span></div>'
                .'<div class="afe-resume-control"><input type="text" class="afe-resume-code" dir="ltr" placeholder="کد رهگیری"><button type="button" class="afe-btn afe-btn-secondary afe-resume-button">ادامه فرم</button></div></div>';
        }

        if (in_array('wordpress',$modes,true) && is_user_logged_in()) {
            $recent=$this->submissions->recentByUser(get_current_user_id(),$form['slug'],5);
            if ($recent) {
                $html.='<details class="afe-account-drafts"><summary>ثبت‌های اخیر حساب کاربری من</summary><div>';
                foreach ($recent as $row) {
                    $url=add_query_arg('afe_submission',(int)$row['id'],remove_query_arg(['afe_edit','afe_tracking','afe_submission']));
                    $html.='<a href="'.esc_url($url).'"><code dir="ltr">'.esc_html($row['tracking_code']).'</code><span>'.esc_html($this->dates->formatUtc((string)$row['updated_at'],true)).'</span></a>';
                }
                $html.='</div></details>';
            }
        }

        return $html;
    }

    private function resolveEditing(array $form): array
    {
        $modes=(array)($form['settings']['editing_modes']??[]);
        if (empty($form['settings']['editing_enabled']) && empty($form['settings']['lock_after_submit'])) return [];

        if (in_array('link',$modes,true) && !empty($_GET['afe_edit'])) {
            $token=sanitize_text_field(wp_unslash($_GET['afe_edit']));
            $row=$this->submissions->findByToken($token,$form['slug']);
            return $row ? ['row'=>$row,'credentials'=>['token'=>$token]] : ['error'=>'لینک ویرایش معتبر نیست یا منقضی شده است.'];
        }

        if (in_array('tracking',$modes,true) && !empty($_GET['afe_tracking'])) {
            $tracking=sanitize_text_field(wp_unslash($_GET['afe_tracking']));
            $row=$this->submissions->findByTracking($tracking,$form['slug']);
            return $row ? ['row'=>$row,'credentials'=>['tracking'=>$tracking]] : ['error'=>'کد رهگیری پیدا نشد.'];
        }

        if (in_array('wordpress',$modes,true) && is_user_logged_in() && !empty($_GET['afe_submission'])) {
            $row=$this->submissions->find((int)$_GET['afe_submission']);
            if ($row && $row['form_slug']===$form['slug'] && (int)$row['user_id']===get_current_user_id()) return ['row'=>$row,'credentials'=>[]];
            return ['error'=>'اجازه دسترسی به این ثبت را ندارید.'];
        }
        return [];
    }
}
