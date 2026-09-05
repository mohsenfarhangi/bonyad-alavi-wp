<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Admin;

use BonyadAlavi\FormEngine\DataSource\DataSourceManager;
use BonyadAlavi\FormEngine\Localization\LocaleDateService;
use BonyadAlavi\FormEngine\InputMask\InputMaskPattern;

/**
 * Turns a resolved form schema into readable/admin-editable submission output.
 * All admin surfaces (detail, print, PDF, Excel) should use this class instead
 * of exposing internal field slugs or raw repeater JSON.
 */
final class FormDataPresenter
{
    /** @var array<string,array<string,string>> */
    private array $optionCache=[];

    public function __construct(private readonly DataSourceManager $sources, private readonly LocaleDateService $dates) {}

    /** @return array<int,array{step:array,fields:array}> */
    public function sections(array $form): array
    {
        $sections=[];
        foreach ((array)($form['steps'] ?? []) as $step) {
            $fields=[];
            foreach ((array)($step['items'] ?? []) as $field) {
                if (($field['type'] ?? '') === 'html' || empty($field['name'])) continue;
                $fields[]=$field;
            }
            if ($fields) $sections[]=['step'=>$step,'fields'=>$fields];
        }
        return $sections;
    }

    public function fieldLabel(array $field): string
    {
        $label=trim((string)($field['label'] ?? ''));
        return $label !== '' ? $label : (string)($field['name'] ?? 'فیلد');
    }

    public function fieldByName(array $form,string $name): ?array
    {
        foreach ($this->sections($form) as $section) {
            foreach ($section['fields'] as $field) {
                if (($field['name'] ?? '') === $name) return $field;
            }
        }
        return null;
    }

    public function displayField(array $field,mixed $value,array $context=[]): string
    {
        if (($field['type'] ?? '') === 'repeater') {
            $rows=is_array($value)?$value:[];
            if (!$rows) $rows=$this->legacyRepeaterRows($field,$context);
            return $this->displayRepeater($field,$rows,$context);
        }
        return $this->displayScalar($field,$value,$context);
    }

    public function plainField(array $field,mixed $value,array $context=[]): string
    {
        if (($field['type'] ?? '') === 'repeater') {
            $value=is_array($value)?$value:[];
            if (!$value) $value=$this->legacyRepeaterRows($field,$context);
            if (!$value) return '—';
            $lines=[];
            $children=$this->children($field);
            foreach (array_values($value) as $index=>$row) {
                if (!is_array($row)) continue;
                $parts=[];
                foreach ($children as $child) {
                    $childName=(string)($child['name'] ?? '');
                    if ($childName==='') continue;
                    $parts[]=$this->fieldLabel($child).': '.$this->plainField($child,$row[$childName]??'',array_merge($context,$row));
                }
                $lines[]='ردیف '.($index+1).': '.implode(' | ',$parts);
            }
            return $lines ? implode("\n",$lines) : '—';
        }

        if (is_array($value)) {
            return implode('، ',array_map(static fn($v)=>(string)$v,$value));
        }
        if ($value === null || $value === '') return '—';

        if (($field['type'] ?? '') === 'date') {
            return $this->dates->formatFormDate($value,(string)($field['calendar']??'gregorian'));
        }

        $options=$this->optionsFor($field,$context);
        $key=(string)$value;
        $text=isset($options[$key]) ? (string)$options[$key] : $key;
        $mask=$field['input_mask']??null;
        if (is_array($mask) && InputMaskPattern::isValid((string)($mask['pattern']??''))) {
            $text=InputMaskPattern::format($text,(string)$mask['pattern']);
        }
        $prefix=trim((string)($field['display_prefix']??''));
        if ($prefix !== '' && !str_starts_with(strtoupper($text),strtoupper($prefix))) $text=$prefix.$text;
        return $text;
    }

    public function editField(array $field,mixed $value,array $context=[],string $base='afe_admin_data'): string
    {
        if (($field['type'] ?? '') === 'repeater') {
            $rows=is_array($value)?$value:[];
            if (!$rows) $rows=$this->legacyRepeaterRows($field,$context);
            return $this->editRepeater($field,$rows,$context,$base);
        }
        $name=(string)($field['name'] ?? '');
        return $this->editScalar($field,$value,$context,$base.'['.$name.']');
    }

    /**
     * Sanitize the structured admin edit payload using the resolved form schema.
     * Existing unknown keys are preserved for forward compatibility.
     */
    public function sanitizeSubmitted(array $form,array $input,array $existing=[]): array
    {
        $clean=$existing;
        foreach ($this->sections($form) as $section) {
            foreach ($section['fields'] as $field) {
                $name=(string)($field['name'] ?? '');
                if ($name==='' || ($field['type']??'')==='file') continue;
                if (($field['type']??'')==='repeater') {
                    $rows=[];
                    foreach ((array)($input[$name]??[]) as $row) {
                        if (!is_array($row)) continue;
                        $cleanRow=[];
                        foreach ($this->children($field) as $child) {
                            $childName=(string)($child['name']??'');
                            if ($childName==='' || ($child['type']??'')==='html' || ($child['type']??'')==='file') continue;
                            $cleanRow[$childName]=$this->sanitizeScalar($child,$row[$childName]??'');
                        }
                        if ($this->rowHasValue($cleanRow)) $rows[]=$cleanRow;
                    }
                    $clean[$name]=$rows;
                    foreach (array_keys((array)($field['legacy_row_map']??[])) as $legacyKey) unset($clean[(string)$legacyKey]);
                    continue;
                }
                $clean[$name]=$this->sanitizeScalar($field,$input[$name]??'');
            }
        }
        return $clean;
    }

    /** @return array<string,array> */
    public function fieldMap(array $form): array
    {
        $map=[];
        foreach ($this->sections($form) as $section) {
            foreach ($section['fields'] as $field) {
                $name=(string)($field['name']??'');
                if ($name!=='') $map[$name]=$field;
            }
        }
        return $map;
    }

    private function legacyRepeaterRows(array $field,array $context): array
    {
        $map=(array)($field['legacy_row_map']??[]);
        if (!$map) return [];
        $row=[]; $has=false;
        foreach ($map as $legacy=>$child) {
            $value=$context[(string)$legacy]??'';
            $row[(string)$child]=$value;
            if ($value !== '' && $value !== null) $has=true;
        }
        return $has ? [$row] : [];
    }

    private function displayRepeater(array $field,array $rows,array $context): string
    {
        if (!$rows) return '<span class="afe-admin-empty">ثبت نشده</span>';
        $children=$this->children($field);
        if (!$children) return '<span class="afe-admin-empty">ثبت نشده</span>';
        $html='<div class="afe-admin-repeater-read"><table><thead><tr><th>ردیف</th>';
        foreach ($children as $child) $html.='<th>'.esc_html($this->fieldLabel($child)).'</th>';
        $html.='</tr></thead><tbody>';
        foreach (array_values($rows) as $index=>$row) {
            if (!is_array($row)) continue;
            $rowContext=array_merge($context,$row);
            $html.='<tr><td class="afe-admin-repeater-index">'.($index+1).'</td>';
            foreach ($children as $child) {
                $name=(string)($child['name']??'');
                $html.='<td>'.$this->displayField($child,$row[$name]??'',$rowContext).'</td>';
            }
            $html.='</tr>';
        }
        return $html.'</tbody></table></div>';
    }

    private function displayScalar(array $field,mixed $value,array $context): string
    {
        if (is_array($value)) {
            if (!$value) return '<span class="afe-admin-empty">—</span>';
            $labels=[];
            $options=$this->optionsFor($field,$context);
            foreach ($value as $one) $labels[]=$options[(string)$one]??(string)$one;
            return esc_html(implode('، ',$labels));
        }
        if ($value === null || $value === '') return '<span class="afe-admin-empty">—</span>';
        $text=$this->plainField($field,$value,$context);
        return nl2br(esc_html($text));
    }

    private function editRepeater(array $field,array $rows,array $context,string $base): string
    {
        $name=(string)($field['name']??'');
        $children=$this->children($field);
        $min=max(0,(int)($field['min']??0));
        if (!$rows && $min>0) $rows=array_fill(0,$min,[]);

        $html='<div class="afe-admin-repeater-edit" data-afe-admin-repeater data-name="'.esc_attr($name).'">';
        $html.='<div class="afe-admin-repeater-toolbar"><span>'.esc_html(count($rows).' ردیف').'</span><button type="button" class="button" data-afe-admin-repeater-add>+ افزودن ردیف</button></div>';
        $html.='<div class="afe-admin-repeater-rows" data-afe-admin-repeater-rows>';
        foreach (array_values($rows) as $index=>$row) {
            $html.=$this->repeaterEditRow($field,$children,is_array($row)?$row:[],$context,$base,$index);
        }
        $html.='</div>';
        $template=$this->repeaterEditRow($field,$children,[],$context,$base,'__INDEX__');
        $html.='<template data-afe-admin-repeater-template>'.str_replace('</template>','',$template).'</template></div>';
        return $html;
    }

    private function repeaterEditRow(array $field,array $children,array $row,array $context,string $base,int|string $index): string
    {
        $fieldName=(string)($field['name']??'');
        $rowContext=array_merge($context,$row);
        $html='<div class="afe-admin-repeater-row" data-afe-admin-repeater-row><div class="afe-admin-repeater-row-head"><strong>ردیف <span data-afe-admin-row-number>'.(is_int($index)?$index+1:'').'</span></strong><button type="button" class="button-link-delete" data-afe-admin-repeater-remove>حذف ردیف</button></div><div class="afe-admin-repeater-fields">';
        foreach ($children as $child) {
            if (($child['type']??'')==='html' || ($child['type']??'')==='file') continue;
            $childName=(string)($child['name']??'');
            $inputName=$base.'['.$fieldName.']['.$index.']['.$childName.']';
            $html.='<label class="afe-admin-inline-field"><span>'.esc_html($this->fieldLabel($child)).'</span>'.$this->editScalar($child,$row[$childName]??'',$rowContext,$inputName).'</label>';
        }
        return $html.'</div></div>';
    }

    private function editScalar(array $field,mixed $value,array $context,string $inputName): string
    {
        $normalizePrefix=trim((string)($field['normalize_input_prefix']??''));
        if ($normalizePrefix !== '' && is_scalar($value)) {
            $raw=(string)$value;
            if (str_starts_with(strtoupper($raw),strtoupper($normalizePrefix))) $value=substr($raw,strlen($normalizePrefix));
        }
        $mask=$field['input_mask']??null;
        $maskPattern=is_array($mask)?trim((string)($mask['pattern']??'')):'';
        if ($maskPattern!=='' && is_scalar($value) && InputMaskPattern::isValid($maskPattern)) {
            $value=InputMaskPattern::format((string)$value,$maskPattern);
        }
        $type=(string)($field['type']??'text');
        $attrs=' name="'.esc_attr($inputName).'" class="afe-admin-control"';
        $inputMode='';
        if ($maskPattern!=='' && InputMaskPattern::isValid($maskPattern) && in_array($type,['text','tel'],true)) {
            $attrs.=' data-afe-input-mask="'.esc_attr($maskPattern).'" maxlength="'.esc_attr((string)InputMaskPattern::displayLength($maskPattern)).'"';
            $inputMode=(string)($mask['inputmode']??InputMaskPattern::suggestedInputMode($maskPattern));
            if ($inputMode!=='') $attrs.=' inputmode="'.esc_attr($inputMode).'"';
        }
        if (in_array($type,['tel','number','date'],true) || in_array(strtolower($inputMode),['numeric','decimal','tel'],true)) {
            $attrs.=' dir="ltr" data-afe-ltr="1"';
        }
        if ($type==='textarea') {
            return '<textarea'.$attrs.' rows="4">'.esc_textarea((string)$value).'</textarea>';
        }
        if (in_array($type,['select','radio'],true)) {
            $options=$this->optionsFor($field,$context);
            $multiple=!empty($field['multiple']);
            $selectedValues=$multiple?(array)$value:[(string)$value];
            $html='<select'.$attrs.($multiple?' multiple':'').'>';
            if (!$multiple) $html.='<option value="">— انتخاب کنید —</option>';
            foreach ($options as $k=>$label) {
                $sel=in_array((string)$k,array_map('strval',$selectedValues),true)?' selected':'';
                $html.='<option value="'.esc_attr((string)$k).'"'.$sel.'>'.esc_html((string)$label).'</option>';
            }
            return $html.'</select>';
        }
        if ($type==='date') {
            $isJalali=$this->dates->isJalali();
            $dateValue=$this->dates->editValue($value,(string)($field['calendar']??'gregorian'));
            $dateAttrs=' inputmode="numeric" autocomplete="off" data-afe-admin-date="1"';
            if($isJalali) $dateAttrs.=' data-jdp';
            return '<input type="'.($isJalali?'text':'date').'"'.$attrs.$dateAttrs.' placeholder="'.esc_attr($this->dates->filterPlaceholder()).'" value="'.esc_attr($dateValue).'">';
        }
        $htmlType=match($type){'number'=>'number','email'=>'email','url'=>'url','tel'=>'tel',default=>'text'};
        return '<input type="'.$htmlType.'"'.$attrs.' value="'.esc_attr((string)$value).'">';
    }

    /** @return array<string,string> */
    private function optionsFor(array $field,array $context): array
    {
        $options=(array)($field['options']??[]);
        if (!empty($field['source'])) {
            $source=$field['source'];
            $cacheKey='';
            if (is_array($source) && ($source['type']??'')==='geo') {
                $parent=(string)($source['parent']??'');
                $cacheKey='geo:'.(string)($source['level']??'').':'.($parent!==''?(string)($context[$parent]??''):'all');
            }
            if ($cacheKey!=='' && isset($this->optionCache[$cacheKey])) $resolved=$this->optionCache[$cacheKey];
            else {
                $resolved=$this->sources->resolve($source,$context);
                if ($cacheKey!=='' && $resolved) $this->optionCache[$cacheKey]=$resolved;
            }
            if ($resolved) $options=$resolved;
        }
        $out=[];
        $optionsAreList=array_is_list($options);
        foreach ($options as $k=>$v) {
            if ($optionsAreList) $k=$v;
            $out[(string)$k]=(string)$v;
        }
        return $out;
    }

    /** @return array<int,array> */
    private function children(array $field): array
    {
        return array_values(array_filter((array)($field['fields']??[]),static fn($child)=>is_array($child)&&($child['type']??'')!=='html'));
    }

    private function sanitizeScalar(array $field,mixed $value): mixed
    {
        if (is_array($value)) return array_values(array_map(static fn($v)=>sanitize_text_field(wp_unslash((string)$v)),$value));
        $value=wp_unslash((string)$value);
        $mask=$field['input_mask']??null;
        if (is_array($mask) && InputMaskPattern::isValid((string)($mask['pattern']??''))) {
            $value=InputMaskPattern::normalize($value,(string)$mask['pattern']);
        }
        $normalizePrefix=trim((string)($field['normalize_input_prefix']??''));
        if ($normalizePrefix !== '' && str_starts_with(strtoupper($value),strtoupper($normalizePrefix))) {
            $value=substr($value,strlen($normalizePrefix));
        }
        return match((string)($field['type']??'text')) {
            'textarea' => sanitize_textarea_field($value),
            'email' => sanitize_email($value),
            'url' => esc_url_raw($value),
            'date' => $this->dates->normalizeEditedFormDate($value,(string)($field['calendar']??'gregorian')),
            default => sanitize_text_field($value),
        };
    }

    private function rowHasValue(array $row): bool
    {
        foreach ($row as $value) {
            if (is_array($value) ? !empty($value) : trim((string)$value)!=='') return true;
        }
        return false;
    }
}
