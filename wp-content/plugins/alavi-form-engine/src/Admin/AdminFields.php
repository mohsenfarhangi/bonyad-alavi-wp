<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Admin;

use InvalidArgumentException;

/**
 * Per-form, admin-only submission fields. Definitions never become form steps,
 * public input schemas, REST fields, or ordinary submission.data_json.
 */
final class AdminFields
{
    public const EXPORT_PREFIX = 'admin:';
    public const MAX_FIELDS = 30;

    /** @return list<array{key:string,label:string,type:string,options:array<string,string>}> */
    public static function definitions(array $form): array
    {
        return self::normalize((array)($form['settings']['admin_fields'] ?? []), $form, false);
    }

    /** Admin submitted definition rows, with option lines "value|label". */
    public static function sanitizeDefinitions(array $rows, array $form): array
    {
        return self::normalize($rows, $form, true);
    }

    private static function normalize(array $rows, array $form, bool $strict): array
    {
        if (count($rows) > self::MAX_FIELDS) {
            if ($strict) throw new InvalidArgumentException('حداکثر ۳۰ فیلد مدیریتی برای هر فرم مجاز است.');
            $rows=array_slice($rows,0,self::MAX_FIELDS);
        }
        $taken=[];
        foreach ((array)($form['steps']??[]) as $step) {
            foreach ((array)($step['items']??[]) as $field) {
                $name=(string)($field['name']??'');
                if ($name!=='') $taken[$name]=true;
            }
        }
        $out=[];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $key=(string)($row['key']??'');
            $label=trim((string)($row['label']??''));
            if($strict) $label=sanitize_text_field($label);
            $type=(string)($row['type']??'text');
            if ($key==='' && $label==='') continue;
            if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/D',$key)
                || isset($taken[$key]) || !in_array($type,['text','textarea','select'],true)
                || $label==='' || strlen($label)>300) {
                if ($strict) throw new InvalidArgumentException('شناسه، عنوان یا نوع فیلد مدیریتی نامعتبر یا تکراری است.');
                continue;
            }
            $options=[];
            $raw=$row['options']??[];
            if ($type==='select') {
                if (is_string($raw)) {
                    $raw=preg_split('/\r\n|\r|\n/',$raw)?:[];
                    $parsed=[];
                    foreach ($raw as $line) {
                        $line=trim($line);
                        if ($line==='') continue;
                        $parts=explode('|',$line,2);
                        $parsed[trim($parts[0])]=trim($parts[1]??$parts[0]);
                    }
                    $raw=$parsed;
                }
                foreach ((array)$raw as $value=>$text) {
                    if (is_array($text)) continue;
                    $value=(string)$value; $text=trim((string)$text);
                    if($strict) $text=sanitize_text_field($text);
                    if (!preg_match('/^[a-zA-Z0-9_\-]{1,64}$/D',$value)
                        || $text==='' || strlen($text)>300 || count($options)>=50) {
                        if ($strict) throw new InvalidArgumentException('گزینه‌های فیلد مدیریتی نامعتبر هستند. از الگوی value|label استفاده کنید.');
                        continue;
                    }
                    $options[$value]=$text;
                }
                if (!$options) {
                    if ($strict) throw new InvalidArgumentException('برای فیلد انتخابی، حداقل یک گزینه الزامی است.');
                    continue;
                }
            }
            $taken[$key]=true;
            $out[]=['key'=>$key,'label'=>$label,'type'=>$type,'options'=>$options];
        }
        return $out;
    }

    /** A FormDataPresenter-compatible field for admin-only rendering. */
    public static function presentationField(array $definition): array
    {
        return [
            'name'=>$definition['key'],
            'label'=>$definition['label'],
            'type'=>$definition['type'],
            'options'=>$definition['options'],
        ];
    }

    public static function optionLines(array $definition): string
    {
        $out=[];
        foreach ((array)($definition['options']??[]) as $key=>$label) $out[]=$key.'|'.$label;
        return implode("\n",$out);
    }

    /** @return array<string,string> Only known definitions; reject unknown selections. */
    public static function sanitizeValues(array $form, array $input): array
    {
        $out=[];
        foreach (self::definitions($form) as $field) {
            $key=$field['key'];
            if (isset($input[$key]) && !is_scalar($input[$key])) {
                throw new InvalidArgumentException('مقدار فیلد مدیریتی نامعتبر است.');
            }
            $raw=isset($input[$key])?wp_unslash((string)$input[$key]):'';
            if ($field['type']==='textarea') {
                $value=sanitize_textarea_field($raw);
            } else {
                $value=sanitize_text_field($raw);
            }
            if (strlen($value)>10000) throw new InvalidArgumentException('مقدار فیلد مدیریتی بیش از حد طولانی است.');
            if ($field['type']==='select' && $value!=='' && !array_key_exists($value,$field['options'])) {
                throw new InvalidArgumentException('گزینه انتخاب‌شده برای «'.$field['label'].'» معتبر نیست.');
            }
            $out[$key]=$value;
        }
        return $out;
    }

    /** Prefix prevents collisions with regular Excel columns. */
    public static function exportKey(string $key): string { return self::EXPORT_PREFIX.$key; }

    public static function fromExportKey(string $field): string
    {
        return str_starts_with($field,self::EXPORT_PREFIX) ? substr($field,strlen(self::EXPORT_PREFIX)) : '';
    }
}
