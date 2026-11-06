<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Admin;

/**
 * Per-form admin-only submission fields. These are NOT frontend form fields:
 * definitions are server-managed and stored values use a reserved namespace.
 */
final class AdminSubmissionFields
{
    public const PREFIX = '__afe_admin_';
    public const MAX = 30;

    /** @return list<array{name:string,label:string,type:string,options:array<string,string>}> */
    public static function definitions(array $form): array
    {
        return self::sanitizeDefinitions((array)($form['settings']['admin_fields'] ?? []));
    }

    /** @return list<array{name:string,label:string,type:string,options:array<string,string>}> */
    public static function sanitizeDefinitions(array $raw): array
    {
        $out=[];
        foreach ($raw as $definition) {
            if (!is_array($definition) || count($out) >= self::MAX) continue;
            $name = sanitize_key((string)($definition['name'] ?? ''));
            $label = sanitize_text_field((string)($definition['label'] ?? ''));
            $type = sanitize_key((string)($definition['type'] ?? 'text'));
            if (!preg_match('/^[a-z][a-z0-9_]{0,48}$/D', $name) || $label==='' || isset($out[$name])) continue;
            if (!in_array($type,['text','textarea','number','select'],true)) $type='text';
            $options = [];
            if ($type === 'select') {
                $values = $definition['options'] ?? [];
                if (is_string($values)) $values = preg_split('/\r\n|\r|\n/', $values);
                if (is_array($values)) foreach ($values as $key=>$labelValue) {
                    if (count($options) >= 50) break;
                    if (is_int($key)) {
                        $parts=explode('|',(string)$labelValue,2);
                        $optionKey=trim((string)$parts[0]);
                        $optionLabel=trim((string)($parts[1]??$parts[0]));
                    } else {
                        $optionKey=(string)$key;
                        $optionLabel=(string)$labelValue;
                    }
                    $optionKey=sanitize_text_field($optionKey);
                    $optionLabel=sanitize_text_field($optionLabel);
                    if ($optionKey!=='' && $optionLabel!=='') $options[$optionKey]=$optionLabel;
                }
            }
            $out[$name]=['name'=>$name,'label'=>(function_exists('mb_substr')?mb_substr($label,0,120):substr($label,0,120)),'type'=>$type,'options'=>$options];
        }
        return array_values($out);
    }

    /** @return array<string,array<string,mixed>> */
    public static function fieldMap(array $form): array
    {
        $fields=[];
        foreach (self::definitions($form) as $one) {
            $name = self::PREFIX.$one['name'];
            $fields[$name]=[
                'name'=>$name,
                'label'=>$one['label'],
                'type'=>$one['type'],
                'options'=>$one['options'],
                'admin_only'=>true,
            ];
        }
        return $fields;
    }

    /** @return array<string,mixed> */
    public static function sanitizeInput(array $form,array $submitted,array $existing): array
    {
        $clean=$existing;
        foreach(self::fieldMap($form) as $name=>$definition) {
            $raw=$submitted[$name]??'';
            if (!is_scalar($raw)) $raw='';
            $value=(string)$raw;
            if ($definition['type']==='select') {
                // A selected value is valid only when defined by an administrator.
                $value=array_key_exists($value,$definition['options']) ? $value : '';
            } elseif ($definition['type']==='textarea') {
                $value=substr(sanitize_textarea_field($value),0,6000);
            } elseif ($definition['type']==='number') {
                $value=is_numeric($value) ? (string)$value : '';
            } else {
                $value=substr(sanitize_text_field($value),0,1000);
            }
            $clean[$name]=$value;
        }
        return $clean;
    }

    /** Preserve existing admin-owned data through applicant draft/final updates. */
    public static function preserve(array $existing,array $new): array
    {
        foreach ($existing as $key=>$value) {
            if (is_string($key) && str_starts_with($key,self::PREFIX)) $new[$key]=$value;
        }
        return $new;
    }
}
