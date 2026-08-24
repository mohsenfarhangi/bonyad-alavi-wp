<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Form;

use BonyadAlavi\FormEngine\Security\Validators;

final class Validator
{
    public function validate(array $form, array $data, bool $draft = false): array
    {
        $errors = [];
        $uniqueGroups = [];

        foreach ($form['steps'] as $step) {
            foreach ($step['items'] as $field) {
                $this->validateField($field, $data[$field['name']] ?? null, $data, $errors, $uniqueGroups, $draft);
            }
        }

        foreach ($uniqueGroups as $group => $values) {
            $clean = array_values(array_filter($values, static fn($v) => $v !== '' && $v !== null));
            if (count($clean) !== count(array_unique(array_map('strval',$clean)))) {
                $errors[$group] = 'انتخاب‌های این گروه نباید تکراری باشند.';
            }
        }

        return $errors;
    }

    private function validateField(array $field, mixed $value, array $data, array &$errors, array &$uniqueGroups, bool $draft): void
    {
        // HTML has no value to validate. File fields are intentionally excluded
        // from the generic data validator as uploaded files live in $_FILES, not
        // afe_data. FileUploader::validate() is the single source of truth for
        // required/count/size/MIME/existing-file validation. Keeping file fields
        // here would always see a null value and produce a false "required" error.
        if (in_array(($field['type'] ?? ''), ['html', 'file'], true)) return;

        $active = $this->isActive($field, $data);
        if (!$active) return;

        if (($field['type'] ?? '') === 'repeater') {
            $rows = is_array($value) ? $value : [];
            if (!$draft && $this->isRequired($field, $data) && count($rows) < (int)($field['min'] ?? 1)) {
                $errors[$field['name']] = ($field['label'] ?? $field['name']) . ' الزامی است.';
            }
            foreach ($rows as $i=>$row) {
                if (!is_array($row)) continue;
                foreach ($field['fields'] ?? [] as $child) {
                    $childValue = $row[$child['name']] ?? null;
                    $childErrors = [];
                    $dummyGroups = [];
                    $this->validateField($child, $childValue, $row, $childErrors, $dummyGroups, $draft);
                    foreach ($childErrors as $key=>$message) {
                        $errors[$field['name'].'.'.$i.'.'.$key] = $message;
                    }
                }
            }
            return;
        }

        $empty = $value === null || $value === '' || (is_array($value) && count($value)===0);
        if (!$draft && $this->isRequired($field, $data) && $empty) {
            $errors[$field['name']] = ($field['label'] ?? $field['name']) . ' الزامی است.';
            return;
        }
        if ($empty) return;

        $rules = $field['rules'] ?? [];
        $string = is_scalar($value) ? trim((string)$value) : '';

        if (!empty($rules['national_id']) && !Validators::nationalId($string)) {
            $errors[$field['name']] = 'کد ملی واردشده معتبر نیست.';
        }
        if (!empty($rules['iban']) && !Validators::iranIban($string)) {
            $errors[$field['name']] = 'شماره شبا باید با IR شروع شود و اعتبار IBAN صحیح داشته باشد.';
        }
        if (!empty($rules['iban_digits']) && !Validators::iranIbanDigits($string)) {
            $errors[$field['name']] = 'شماره شبا باید دقیقاً ۲۴ رقم و دارای اعتبار صحیح شبا باشد.';
        }
        if (!empty($rules['mobile_09']) && !Validators::mobile09($string)) {
            $errors[$field['name']] = 'شماره همراه باید دقیقاً ۱۱ رقم و با ۰۹ شروع شود.';
        }
        if (!empty($rules['mobile']) && !Validators::mobile($string)) {
            $errors[$field['name']] = 'شماره همراه معتبر نیست.';
        }
        if (!empty($rules['email']) && !is_email($string)) {
            $errors[$field['name']] = 'نشانی ایمیل معتبر نیست.';
        }
        if (($field['type'] ?? '') === 'date') {
            $calendar = (string)($field['calendar'] ?? 'gregorian');
            if ($calendar === 'jalali' && !Validators::jalaliDate($string)) {
                $errors[$field['name']] = 'تاریخ جلالی معتبر نیست. قالب صحیح مانند 1403/01/15 است.';
            } elseif ($calendar === 'gregorian' && !Validators::gregorianDate($string)) {
                $errors[$field['name']] = 'تاریخ میلادی معتبر نیست.';
            }
        }
        if (isset($rules['min_length']) && mb_strlen($string) < (int)$rules['min_length']) {
            $errors[$field['name']] = 'حداقل طول این فیلد رعایت نشده است.';
        }
        if (isset($rules['max_length']) && mb_strlen($string) > (int)$rules['max_length']) {
            $errors[$field['name']] = 'حداکثر طول این فیلد رعایت نشده است.';
        }

        if (!empty($field['unique_group'])) {
            $uniqueGroups[$field['unique_group']][] = $value;
        }
    }

    public function isRequired(array $field, array $data): bool
    {
        $required = !empty($field['required']);
        foreach ($field['conditions'] ?? [] as $condition) {
            $effect = $condition['effect'] ?? '';
            if (!in_array($effect, ['required','optional'], true)) continue;
            if ($this->match((array)($condition['when'] ?? []), $data)) {
                $required = $effect === 'required';
            }
        }
        return $required;
    }

    public function isActive(array $field, array $data): bool
    {
        if (empty($field['conditions'])) return true;
        $visible = true;
        foreach ($field['conditions'] as $condition) {
            $match = $this->match((array)($condition['when'] ?? []), $data);
            $effect = $condition['effect'] ?? 'show';
            if ($effect === 'show') $visible = $match;
            elseif ($effect === 'hide') $visible = !$match;
        }
        return $visible;
    }

    private function match(array $conditions, array $data): bool
    {
        foreach ($conditions as $condition) {
            if (!is_array($condition)) continue;
            $field = (string)($condition['field'] ?? '');
            $op = $condition['operator'] ?? '=';
            $expected = $condition['value'] ?? null;
            $actual = $data[$field] ?? null;
            $ok = match ($op) {
                '=', '==' => (string)$actual === (string)$expected,
                '!=' => (string)$actual !== (string)$expected,
                '>' => (float)$actual > (float)$expected,
                '>=' => (float)$actual >= (float)$expected,
                '<' => (float)$actual < (float)$expected,
                '<=' => (float)$actual <= (float)$expected,
                'in' => in_array((string)$actual, array_map('strval',(array)$expected), true),
                'contains' => is_array($actual) ? in_array((string)$expected,array_map('strval',$actual),true) : str_contains((string)$actual,(string)$expected),
                'empty' => empty($actual),
                'not_empty' => !empty($actual),
                default => false,
            };
            if (!$ok) return false;
        }
        return true;
    }
}
