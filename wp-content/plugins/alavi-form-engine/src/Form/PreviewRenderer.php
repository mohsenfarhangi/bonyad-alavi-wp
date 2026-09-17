<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Form;

use BonyadAlavi\FormEngine\Admin\FormDataPresenter;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use BonyadAlavi\FormEngine\Template\TemplateResolver;

final class PreviewRenderer
{
    public function __construct(
        private readonly FormDataPresenter $presenter,
        private readonly SubmissionRepository $submissions,
        private readonly TemplateResolver $templates
    ) {}

    public function render(array $form, array $values = [], int $submissionId = 0, bool $live = false): string
    {
        $template = $this->templates->resolvePreview($form);

        $blocks = [];
        $groups = '';
        foreach ($this->presenter->sections($form) as $section) {
            $title = (string)($section['step']['title'] ?? '');
            $fieldsHtml = '';
            foreach ($section['fields'] as $field) {
                $name = (string)($field['name'] ?? '');
                if ($name === '') continue;
                $block = $this->fieldBlock($field, $values[$name] ?? '', $values, $submissionId, $live);
                $blocks[$name] = $block;
                $fieldsHtml .= $block;
            }
            if ($fieldsHtml !== '') {
                $groups .= '<section class="afe-preview-group"><h3>'.esc_html($title).'</h3><div class="afe-preview-grid">'.$fieldsHtml.'</div></section>';
            }
        }

        $safe = wp_kses_post($template);
        $safe = strtr($safe, [
            '{{title}}' => esc_html((string)($form['title'] ?? '')),
            '{{description}}' => esc_html((string)($form['description'] ?? '')),
            '{{preview_title}}' => esc_html((string)($form['settings']['preview_title'] ?? 'پیش‌نمایش اطلاعات ارسالی')),
            '{{preview_description}}' => esc_html((string)($form['settings']['preview_description'] ?? 'اطلاعات زیر را با دقت بررسی کنید.')),
            '{{preview_fields}}' => $groups,
        ]);
        foreach ($blocks as $name => $block) {
            $safe = str_replace('{{field:'.$name.'}}', $block, $safe);
        }
        return preg_replace('/\{\{field:[^}]+\}\}/', '', $safe) ?: $safe;
    }

    private function fieldBlock(array $field, mixed $value, array $context, int $submissionId, bool $live): string
    {
        $name = (string)$field['name'];
        $label = $this->presenter->fieldLabel($field);
        $type = (string)($field['type'] ?? 'text');
        $ltrPreview = $this->usesLeftToRightPreview($field, $value);
        if ($live) {
            $ltrAttrs = $ltrPreview ? ' dir="ltr" data-afe-ltr="1"' : '';
            $content = '<div class="afe-preview-value" data-afe-preview-value="'.esc_attr($name).'" data-preview-type="'.esc_attr($type).'"'.$ltrAttrs.'>—</div>';
        } elseif ($type === 'file') {
            $files = $submissionId > 0 ? $this->submissions->filesForField($submissionId, $name) : [];
            if (!$files) $content = '<span class="afe-preview-empty">ثبت نشده</span>';
            else {
                $items = '';
                foreach ($files as $file) {
                    $fileName = (string)($file['original_name'] ?? 'فایل');
                    $fileUrl = $this->previewFileUrl($file, $submissionId);
                    $label = $fileUrl !== ''
                        ? '<a class="afe-preview-file-link" href="'.esc_url($fileUrl).'" target="_blank" rel="noopener noreferrer">'.esc_html($fileName).'</a>'
                        : '<span>'.esc_html($fileName).'</span>';
                    $items .= '<li><span class="afe-preview-file-icon">📎</span>'.$label.'<small>'.esc_html(size_format((int)($file['size'] ?? 0))).'</small></li>';
                }
                $content = '<ul class="afe-preview-files">'.$items.'</ul>';
            }
        } elseif ($type === 'repeater') {
            $rows=is_array($value)?$value:[];
            if (!$rows) $content='<span class="afe-preview-empty">ثبت نشده</span>';
            else {
                $children=array_values(array_filter((array)($field['fields']??[]),static fn(array $child): bool => ($child['type']??'')!=='html'));
                $head=''; foreach($children as $child) $head.='<th>'.esc_html($this->presenter->fieldLabel($child)).'</th>';
                $body=''; foreach(array_values($rows) as $i=>$row){ if(!is_array($row)) continue; $cells=''; foreach($children as $child){ $childName=(string)($child['name']??''); $childValue=$row[$childName]??''; $ltrCell=$this->usesLeftToRightPreview($child,$childValue); $cellAttrs=$ltrCell?' dir="ltr" data-afe-ltr="1"':''; $cells.='<td'.$cellAttrs.'>'.$this->presenter->displayField($child,$childValue,array_merge($context,$row),true).'</td>'; } $body.='<tr><td dir="ltr" data-afe-ltr="1">'.($i+1).'</td>'.$cells.'</tr>'; }
                $content='<div class="afe-preview-repeater"><table><thead><tr><th>ردیف</th>'.$head.'</tr></thead><tbody>'.$body.'</tbody></table></div>';
            }
        } else {
            $ltrAttrs = $ltrPreview ? ' dir="ltr" data-afe-ltr="1"' : '';
            $content = '<div class="afe-preview-value"'.$ltrAttrs.'>'.$this->presenter->displayField($field, $value, $context, true).'</div>';
        }
        $wide = in_array($type, ['textarea','repeater','file'], true) ? ' afe-preview-field--wide' : '';
        return '<div class="afe-preview-field'.$wide.'" data-preview-field="'.esc_attr($name).'"><span class="afe-preview-label">'.esc_html($label).'</span>'.$content.'</div>';
    }

    private function previewFileUrl(array $file, int $submissionId): string
    {
        $attachmentId = (int)($file['attachment_id'] ?? 0);
        if ($attachmentId > 0 && (int)get_post_meta($attachmentId, '_afe_submission_id', true) === $submissionId) {
            $attachmentUrl = wp_get_attachment_url($attachmentId);
            if (is_string($attachmentUrl) && $attachmentUrl !== '') return $attachmentUrl;
        }

        $url = trim((string)($file['url'] ?? ''));
        if ($url === '') return '';
        $scheme = strtolower((string)wp_parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $url : '';
    }

    private function usesLeftToRightPreview(array $field, mixed $value): bool
    {
        $type = (string)($field['type'] ?? '');
        if (in_array($type, ['tel', 'number', 'date'], true)) return true;
        if ((string)($field['character_mode'] ?? 'normal') === 'digits') return true;

        $attributes = (array)($field['attributes'] ?? []);
        $inputMode = strtolower(trim((string)($attributes['inputmode'] ?? '')));
        if (in_array($inputMode, ['numeric', 'decimal', 'tel'], true)) return true;

        $mask = $field['input_mask'] ?? null;
        if (is_array($mask)) {
            $maskInputMode = strtolower(trim((string)($mask['inputmode'] ?? '')));
            if (in_array($maskInputMode, ['numeric', 'decimal', 'tel'], true)) return true;
        }

        if (!is_scalar($value)) return false;
        $text = trim((string)$value);
        if ($text === '' || preg_match('/[0-9۰-۹٠-٩]/u', $text) !== 1) return false;

        return preg_match('/^[0-9۰-۹٠-٩\s+\-().\/:،,]+$/u', $text) === 1;
    }

}
