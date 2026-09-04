<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

use BonyadAlavi\FormEngine\Events\EventRegistry;

/**
 * Sanitizes admin Action Builder payloads using registry metadata.
 * FormsPage never needs per-action save branches.
 */
final class ActionConfigSanitizer
{
    private const POLICIES = ['always','once_per_submission','first_in_cycle'];
    private const ERROR_BEHAVIORS = ['continue','stop'];
    private const OPERATORS = ['=','!=','>','>=','<','<=','in','contains','empty','not_empty'];

    public function __construct(
        private readonly ActionRegistry $actions,
        private readonly EventRegistry $events
    ) {}

    public function sanitizeGroups(array $rawGroups, array $form): array
    {
        $fieldKeys = array_keys($this->fieldLabels($form));
        $groups = [];

        foreach ($rawGroups as $group) {
            if (!is_array($group)) continue;
            $event = trim((string)($group['event'] ?? ''));
            if (!$this->events->has($event)) continue;

            $cleanActions = [];
            foreach ((array)($group['actions'] ?? []) as $rawAction) {
                if (!is_array($rawAction)) continue;
                $type = sanitize_key((string)($rawAction['type'] ?? ''));
                $definition = $this->actions->get($type);
                if (!$definition) continue;

                $actionKey = sanitize_key((string)($rawAction['action_key'] ?? ''));
                if ($actionKey === '') {
                    try {
                        $actionKey = 'action_' . $type . '_' . bin2hex(random_bytes(6));
                    } catch (\Throwable) {
                        $actionKey = 'action_' . $type . '_' . substr(hash('sha256', wp_json_encode($rawAction) ?: uniqid('', true)), 0, 12);
                    }
                }
                $actionKey = substr($actionKey, 0, 80);

                $policy = sanitize_key((string)($rawAction['execution_policy'] ?? 'always'));
                if (!in_array($policy, self::POLICIES, true)) $policy = 'always';
                $onError = sanitize_key((string)($rawAction['on_error'] ?? 'continue'));
                if (!in_array($onError, self::ERROR_BEHAVIORS, true)) $onError = 'continue';

                $clean = [
                    'action_key'=>$actionKey,
                    'type'=>$type,
                    'enabled'=>!empty($rawAction['enabled']),
                    'execution_policy'=>$policy,
                    'on_error'=>$onError,
                    'config'=>$this->sanitizeConfig((array)($rawAction['config'] ?? []), $definition, $fieldKeys, $form),
                ];

                if ($definition->supportsConditionalLogic) {
                    $conditions = $this->sanitizeConditions((array)($rawAction['when'] ?? []), $fieldKeys);
                    if ($conditions !== []) $clean['when'] = $conditions;
                }

                $cleanActions[] = $clean;
            }

            if ($cleanActions !== []) {
                $groups[] = ['event'=>$event,'actions'=>$cleanActions];
            }
        }

        return $groups;
    }

    /** @param list<string> $fieldKeys */
    private function sanitizeConfig(array $raw, ActionDefinition $definition, array $fieldKeys, array $form): array
    {
        $clean = [];
        foreach ($definition->settingsSchema as $key => $schema) {
            if (!is_array($schema)) continue;
            $key = sanitize_key((string)$key);
            if ($key === '') continue;
            $type = sanitize_key((string)($schema['type'] ?? 'text'));
            $value = $raw[$key] ?? ($schema['default'] ?? null);
            $capability = (string)($schema['capability'] ?? '');
            if ($capability !== '' && (!function_exists('current_user_can') || !current_user_can($capability))) {
                $value = $schema['default'] ?? false;
            }

            $clean[$key] = match ($type) {
                'textarea' => sanitize_textarea_field(wp_unslash((string)$value)),
                'url' => esc_url_raw(wp_unslash((string)$value)),
                'select' => $this->sanitizeSelect($value, (array)($schema['options'] ?? [])),
                'field_select' => in_array((string)$value, $fieldKeys, true) ? (string)$value : '',
                'user' => max(0, (int)$value),
                'boolean' => !empty($value),
                'role_select' => $this->sanitizeRole($value),
                'workflow_select' => $this->sanitizeWorkflowStatus($value, $form),
                'post_type_select' => $this->sanitizePostType($value),
                'post_status_select' => $this->sanitizePostStatus($value),
                'json' => $this->sanitizeJson($value),
                'key_value' => $this->sanitizeKeyValue($value),
                'repeater_text' => $this->sanitizeTextList($value),
                default => sanitize_text_field(wp_unslash((string)$value)),
            };
        }
        return $clean;
    }


    private function sanitizeRole(mixed $value): string
    {
        $role = sanitize_key((string)$value);
        if ($role === '') return '';
        if (function_exists('wp_roles')) {
            $roles = wp_roles()->roles;
            if (!isset($roles[$role])) return '';
        }
        return $role;
    }

    private function sanitizeWorkflowStatus(mixed $value, array $form): string
    {
        $status = sanitize_key((string)$value);
        return $status !== '' && array_key_exists($status, (array)($form['workflow'] ?? [])) ? $status : '';
    }

    private function sanitizePostType(mixed $value): string
    {
        $postType = sanitize_key((string)$value);
        if ($postType === '' || in_array($postType, ['attachment','revision','nav_menu_item'], true)) return '';
        if (function_exists('post_type_exists') && !post_type_exists($postType)) return '';
        return $postType;
    }

    private function sanitizePostStatus(mixed $value): string
    {
        $status = sanitize_key((string)$value);
        return in_array($status, ['draft','pending','private','publish'], true) ? $status : 'draft';
    }

    private function sanitizeSelect(mixed $value, array $options): string
    {
        $value = sanitize_key((string)$value);
        $allowed = array_map('strval', array_keys($options));
        if (in_array($value, $allowed, true)) return $value;
        return $allowed[0] ?? '';
    }

    private function sanitizeJson(mixed $value): array
    {
        if (is_array($value)) return $this->sanitizeRecursive($value);
        $decoded = json_decode(wp_unslash((string)$value), true);
        return is_array($decoded) ? $this->sanitizeRecursive($decoded) : [];
    }

    private function sanitizeKeyValue(mixed $value): array
    {
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $key=>$one) {
                $key = sanitize_text_field((string)$key);
                if ($key === '') continue;
                $clean[$key] = sanitize_text_field(wp_unslash((string)$one));
            }
            return $clean;
        }

        $clean = [];
        foreach (preg_split('/\R/u', wp_unslash((string)$value)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (str_contains($line, '|')) [$key,$one] = array_map('trim', explode('|',$line,2));
            elseif (str_contains($line, ':')) [$key,$one] = array_map('trim', explode(':',$line,2));
            else continue;
            $key = sanitize_text_field($key);
            if ($key === '') continue;
            $clean[$key] = sanitize_text_field($one);
        }
        return $clean;
    }

    private function sanitizeTextList(mixed $value): array
    {
        if (!is_array($value)) $value = preg_split('/\R/u', wp_unslash((string)$value)) ?: [];
        $out = [];
        foreach ($value as $one) {
            $one = sanitize_text_field(wp_unslash((string)$one));
            if ($one !== '') $out[] = $one;
        }
        return $out;
    }

    /** @param list<string> $fieldKeys */
    private function sanitizeConditions(array $rawConditions, array $fieldKeys): array
    {
        $conditions = [];
        foreach ($rawConditions as $raw) {
            if (!is_array($raw)) continue;
            $field = sanitize_text_field((string)($raw['field'] ?? ''));
            if (!in_array($field, $fieldKeys, true)) continue;
            $operator = (string)($raw['operator'] ?? '=');
            if (!in_array($operator, self::OPERATORS, true)) $operator = '=';

            $condition = ['field'=>$field,'operator'=>$operator];
            if (!in_array($operator, ['empty','not_empty'], true)) {
                $value = $raw['value'] ?? '';
                if ($operator === 'in') {
                    $parts = is_array($value) ? $value : preg_split('/[,\n]+/u', (string)$value);
                    $condition['value'] = array_values(array_filter(array_map(
                        static fn($one)=>sanitize_text_field(wp_unslash(trim((string)$one))),
                        (array)$parts
                    ), static fn($one)=>$one!==''));
                } else {
                    $condition['value'] = sanitize_text_field(wp_unslash((string)$value));
                }
            }
            $conditions[] = $condition;
        }
        return $conditions;
    }

    private function sanitizeRecursive(array $value): array
    {
        $out = [];
        foreach ($value as $key=>$one) {
            $cleanKey = is_int($key) ? $key : sanitize_text_field((string)$key);
            if (is_array($one)) {
                $out[$cleanKey] = $this->sanitizeRecursive($one);
            } elseif ($one === null || is_bool($one) || is_int($one) || is_float($one)) {
                $out[$cleanKey] = $one;
            } elseif (is_string($one)) {
                $out[$cleanKey] = sanitize_text_field(wp_unslash($one));
            }
        }
        return $out;
    }

    /** @return array<string,string> */
    private function fieldLabels(array $form): array
    {
        $labels = [];
        foreach ((array)($form['steps'] ?? []) as $step) {
            foreach ((array)($step['items'] ?? []) as $field) {
                $this->collectField((array)$field, '', $labels);
            }
        }
        return $labels;
    }

    /** @param array<string,string> $labels */
    private function collectField(array $field, string $prefix, array &$labels): void
    {
        $name = (string)($field['name'] ?? '');
        if ($name === '' || ($field['type'] ?? '') === 'html') return;
        $path = $prefix === '' ? $name : $prefix.'.'.$name;
        $labels[$path] = (string)($field['label'] ?? $path);
        if (($field['type'] ?? '') === 'repeater') {
            foreach ((array)($field['fields'] ?? []) as $child) $this->collectField((array)$child, $path, $labels);
        }
    }
}
