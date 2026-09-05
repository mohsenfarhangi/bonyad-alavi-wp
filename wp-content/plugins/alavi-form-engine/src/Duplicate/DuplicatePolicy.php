<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Duplicate;

final class DuplicatePolicy
{
    public const BEHAVIORS = ['block','reference','message','allow'];

    public function __construct(
        private readonly DuplicateFingerprint $fingerprints,
        private readonly DuplicateRepository $repository
    ) {}

    public function evaluate(array $form, array $data, int $currentSubmissionId = 0): DuplicateDecision
    {
        $config = $this->config($form);
        if (!$config['enabled'] || $config['fields'] === []) {
            return new DuplicateDecision(false, '', null, $config['behavior'], $config['message']);
        }

        // A duplicate combination is meaningful only when every configured
        // fingerprint component has a value. This mirrors the live preflight
        // and avoids treating two incomplete submissions as duplicates.
        if ($this->missingFields($form, $data) !== []) {
            return new DuplicateDecision(false, '', null, $config['behavior'], $config['message']);
        }

        $fingerprint = $this->fingerprints->make((string)$form['slug'], $config['fields'], $data);
        $duplicateId = $this->repository->find((string)$form['slug'], $fingerprint, $currentSubmissionId);

        return new DuplicateDecision(
            true,
            $fingerprint,
            $duplicateId,
            $config['behavior'],
            $config['message']
        );
    }

    /** @return array{enabled:bool,fields:list<string>,behavior:string,message:string} */
    public function config(array $form): array
    {
        $raw = (array)($form['settings']['duplicate'] ?? []);
        $allowedFields = array_keys($this->fieldLabels($form));
        $fields = array_values(array_unique(array_filter(
            array_map(static fn($one): string => sanitize_key_path((string)$one), (array)($raw['fields'] ?? [])),
            static fn(string $one): bool => $one !== '' && in_array($one, $allowedFields, true)
        )));
        $behavior = sanitize_key((string)($raw['behavior'] ?? 'block'));
        if (!in_array($behavior, self::BEHAVIORS, true)) $behavior = 'block';
        $message = sanitize_textarea_field((string)($raw['message'] ?? ''));
        if ($message === '') {
            $message = $behavior === 'reference'
                ? 'ثبت مشابهی قبلاً برای این فرم وجود دارد.'
                : 'این اطلاعات قبلاً ثبت شده است.';
        }

        return [
            'enabled'=>!empty($raw['enabled']),
            'fields'=>$fields,
            'behavior'=>$behavior,
            'message'=>$message,
        ];
    }

    /**
     * Return duplicate fingerprint fields that do not yet have a meaningful
     * value. This is used by the public live duplicate preflight so the DB is
     * queried only after every configured fingerprint component is complete.
     *
     * @return list<string>
     */
    public function missingFields(array $form, array $data): array
    {
        $config = $this->config($form);
        if (!$config['enabled'] || $config['fields'] === []) return [];

        $missing = [];
        foreach ($config['fields'] as $path) {
            if (!$this->hasMeaningfulValue($this->valueForPath($data, $path))) $missing[] = $path;
        }
        return $missing;
    }

    private function valueForPath(mixed $value, string $path): mixed
    {
        $parts = array_values(array_filter(explode('.', $path), static fn(string $part): bool => $part !== ''));
        return $this->walkPath($value, $parts);
    }

    /** @param list<string> $parts */
    private function walkPath(mixed $value, array $parts): mixed
    {
        if ($parts === []) return $value;
        $part = array_shift($parts);
        if (!is_array($value)) return null;

        if (array_is_list($value)) {
            $result = [];
            foreach ($value as $row) {
                $resolved = $this->walkPath($row, array_merge([$part], $parts));
                if ($this->hasMeaningfulValue($resolved)) $result[] = $resolved;
            }
            return $result;
        }

        if (!array_key_exists($part, $value)) return null;
        return $this->walkPath($value[$part], $parts);
    }

    private function hasMeaningfulValue(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $one) if ($this->hasMeaningfulValue($one)) return true;
            return false;
        }
        if ($value === null) return false;
        return trim((string)$value) !== '';
    }

    /** @return array<string,string> */
    public function fieldLabels(array $form): array
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
        $type = (string)($field['type'] ?? 'text');
        if ($type === 'html' || $type === 'file') return;
        $name = sanitize_key((string)($field['name'] ?? ''));
        if ($name === '') return;
        $path = $prefix === '' ? $name : $prefix.'.'.$name;
        $label = sanitize_text_field((string)($field['label'] ?? $name));
        if ($type === 'repeater') {
            foreach ((array)($field['fields'] ?? []) as $child) {
                $this->collectField((array)$child, $path, $labels);
            }
            return;
        }
        $labels[$path] = $label;
    }
}

if (!function_exists(__NAMESPACE__.'\\sanitize_key_path')) {
    function sanitize_key_path(string $value): string
    {
        $parts = array_filter(explode('.', $value), static fn(string $part): bool => $part !== '');
        $parts = array_map('sanitize_key', $parts);
        return implode('.', array_filter($parts, static fn(string $part): bool => $part !== ''));
    }
}
