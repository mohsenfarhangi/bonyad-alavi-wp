<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Tokens;

use BonyadAlavi\FormEngine\Actions\ActionContext;

final class TokenResolver
{
    public function resolve(string $template, ActionContext $context): string
    {
        $submission = $context->submission;
        $replacements = [
            '{{tracking_code}}' => (string)($submission['tracking_code'] ?? ''),
            '{{submission_id}}' => (string)$context->submissionId,
            '{{form_title}}' => $context->formTitle,
            '{{form_slug}}' => $context->formSlug,
            '{{status}}' => (string)($context->runtime?->get('status', $submission['status'] ?? '') ?? ($submission['status'] ?? '')),
        ];

        $resolved = strtr($template, $replacements);
        $allowed = array_fill_keys($context->fieldKeys, true);

        return (string)preg_replace_callback('/\{\{field:([a-zA-Z0-9_.-]+)\}\}/', function (array $matches) use ($context, $allowed): string {
            $path = (string)$matches[1];
            if ($allowed !== [] && !isset($allowed[$path])) {
                return '';
            }
            $value = $this->valueByPath($context->data, $path);
            if (is_scalar($value) || $value === null) {
                return (string)$value;
            }
            return wp_json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }, $resolved);
    }

    public function resolveValue(mixed $value, ActionContext $context): mixed
    {
        if (is_string($value)) {
            return $this->resolve($value, $context);
        }
        if (!is_array($value)) {
            return $value;
        }

        $resolved = [];
        foreach ($value as $key => $item) {
            $resolved[$key] = $this->resolveValue($item, $context);
        }
        return $resolved;
    }

    private function valueByPath(array $data, string $path): mixed
    {
        return $this->walkPath($data, explode('.', $path));
    }

    private function walkPath(mixed $value, array $segments): mixed
    {
        if ($segments === []) return $value;
        if (!is_array($value)) return null;

        $segment = (string)$segments[0];
        $remaining = array_slice($segments, 1);
        if (array_key_exists($segment, $value)) {
            return $this->walkPath($value[$segment], $remaining);
        }

        // Repeater rows are stored as a list. Resolving `{{field:members.mobile}}`
        // returns the list of matching child values, later serialized as JSON.
        if (array_is_list($value)) {
            $collected = [];
            foreach ($value as $row) {
                $resolved = $this->walkPath($row, $segments);
                if ($resolved !== null) $collected[] = $resolved;
            }
            return $collected;
        }

        return null;
    }
}
