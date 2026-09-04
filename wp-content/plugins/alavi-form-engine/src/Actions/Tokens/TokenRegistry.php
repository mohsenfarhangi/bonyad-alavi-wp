<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Tokens;

final class TokenRegistry
{
    /** @var array<string, TokenDefinition> */
    private array $tokens = [];

    public function __construct()
    {
        $this->register(new TokenDefinition('{{tracking_code}}', 'کد رهگیری', 'کد رهگیری ثبت فرم.'));
        $this->register(new TokenDefinition('{{submission_id}}', 'شناسه ثبت', 'شناسه داخلی Submission.'));
        $this->register(new TokenDefinition('{{form_title}}', 'عنوان فرم', 'عنوان resolved فرم.'));
        $this->register(new TokenDefinition('{{form_slug}}', 'شناسه فنی فرم', 'Slug فرم؛ برای استفاده‌های فنی.'));
        $this->register(new TokenDefinition('{{status}}', 'وضعیت ثبت', 'وضعیت فعلی Submission.'));
        $this->register(new TokenDefinition('{{field:*}}', 'مقدار فیلد فرم', 'به‌جای * کلید فیلد قرار می‌گیرد؛ نمونه: {{field:leader_mobile}}.', 'fields'));
    }

    public function register(TokenDefinition $definition): void
    {
        $this->tokens[$definition->token] = $definition;
    }

    /** @return array<string, TokenDefinition> */
    public function all(): array
    {
        return $this->tokens;
    }

    /**
     * Returns the global tokens plus concrete field tokens derived from the
     * resolved form definition. These are safe to expose in the future admin
     * Token Palette because only real form fields are included.
     *
     * @return array<string, TokenDefinition>
     */
    public function forForm(array $form): array
    {
        $tokens = $this->tokens;
        foreach ($this->fieldDefinitions((array)($form['steps'] ?? [])) as $path => $label) {
            $token = '{{field:' . $path . '}}';
            $tokens[$token] = new TokenDefinition($token, $label, 'مقدار فیلد «' . $label . '».', 'fields');
        }
        return $tokens;
    }

    /** @return array<string, string> */
    private function fieldDefinitions(array $steps): array
    {
        $fields = [];
        foreach ($steps as $step) {
            foreach ((array)($step['items'] ?? []) as $field) {
                $this->collectField($field, '', $fields);
            }
        }
        return $fields;
    }

    /** @param array<string, string> $fields */
    private function collectField(array $field, string $prefix, array &$fields): void
    {
        $name = (string)($field['name'] ?? '');
        if ($name === '' || ($field['type'] ?? '') === 'html') {
            return;
        }
        $path = $prefix === '' ? $name : $prefix . '.' . $name;
        $fields[$path] = (string)($field['label'] ?? $path);

        if (($field['type'] ?? '') === 'repeater') {
            foreach ((array)($field['fields'] ?? []) as $child) {
                $this->collectField((array)$child, $path, $fields);
            }
        }
    }
}
