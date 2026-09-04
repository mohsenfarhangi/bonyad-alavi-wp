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
        if ($live) {
            $content = '<div class="afe-preview-value" data-afe-preview-value="'.esc_attr($name).'" data-preview-type="'.esc_attr($type).'">—</div>';
        } elseif ($type === 'file') {
            $files = $submissionId > 0 ? $this->submissions->filesForField($submissionId, $name) : [];
            if (!$files) $content = '<span class="afe-preview-empty">ثبت نشده</span>';
            else {
                $items = '';
                foreach ($files as $file) {
                    $items .= '<li><span class="afe-preview-file-icon">📎</span><span>'.esc_html((string)($file['original_name'] ?? 'فایل')).'</span><small>'.esc_html(size_format((int)($file['size'] ?? 0))).'</small></li>';
                }
                $content = '<ul class="afe-preview-files">'.$items.'</ul>';
            }
        } elseif ($type === 'repeater') {
            $rows=is_array($value)?$value:[];
            if (!$rows) $content='<span class="afe-preview-empty">ثبت نشده</span>';
            else {
                $children=array_values(array_filter((array)($field['fields']??[]),static fn(array $child): bool => ($child['type']??'')!=='html'));
                $head=''; foreach($children as $child) $head.='<th>'.esc_html($this->presenter->fieldLabel($child)).'</th>';
                $body=''; foreach(array_values($rows) as $i=>$row){ if(!is_array($row)) continue; $cells=''; foreach($children as $child){ $childName=(string)($child['name']??''); $cells.='<td>'.$this->presenter->displayField($child,$row[$childName]??'',array_merge($context,$row)).'</td>'; } $body.='<tr><td>'.($i+1).'</td>'.$cells.'</tr>'; }
                $content='<div class="afe-preview-repeater"><table><thead><tr><th>ردیف</th>'.$head.'</tr></thead><tbody>'.$body.'</tbody></table></div>';
            }
        } else {
            $content = $this->presenter->displayField($field, $value, $context);
        }
        $wide = in_array($type, ['textarea','repeater','file'], true) ? ' afe-preview-field--wide' : '';
        return '<div class="afe-preview-field'.$wide.'" data-preview-field="'.esc_attr($name).'"><span class="afe-preview-label">'.esc_html($label).'</span>'.$content.'</div>';
    }
}
