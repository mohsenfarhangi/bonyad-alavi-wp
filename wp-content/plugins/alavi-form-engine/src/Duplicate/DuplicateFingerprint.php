<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Duplicate;

final class DuplicateFingerprint
{
    public function make(string $formSlug, array $fields, array $data): string
    {
        $normalized = [];
        foreach ($fields as $field) {
            $key = (string)$field;
            $normalized[$key] = $this->normalize($this->valueForPath($data, $key));
        }
        ksort($normalized);

        return hash('sha256', $formSlug . '|' . wp_json_encode($normalized, JSON_UNESCAPED_UNICODE));
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
                if ($resolved !== null && $resolved !== '') $result[] = $resolved;
            }
            return $result;
        }

        if (!array_key_exists($part, $value)) return null;
        return $this->walkPath($value[$part], $parts);
    }

    private function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->normalize($item);
            }
            return $result;
        }

        $value = trim((string)$value);
        $value = strtr($value, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }
}
