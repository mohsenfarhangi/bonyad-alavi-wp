<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Template;

final class TemplateResolver
{
    public function __construct(private readonly TemplateRegistry $registry) {}

    public function definition(string $key): TemplateDefinition
    {
        return $this->registry->get($key);
    }

    public function defaultForm(array $form): string
    {
        $code = trim((string)($form['template'] ?? ''));
        return $code !== '' ? $code : $this->registry->get('form')->defaultHtml();
    }

    public function defaultPreview(array $form): string
    {
        $code = trim((string)($form['settings']['preview_template'] ?? ''));
        return $code !== '' ? $code : $this->registry->get('preview')->defaultHtml();
    }

    public function defaultStep(array $step): string
    {
        $code = trim((string)($step['template'] ?? ''));
        return $code !== '' ? $code : $this->registry->get('step')->defaultHtml();
    }

    public function resolveForm(array $form, string $adminOverride = ''): string
    {
        return $this->hasOverride($adminOverride, $this->defaultForm($form))
            ? $adminOverride
            : $this->defaultForm($form);
    }

    public function resolvePreview(array $form): string
    {
        $candidate = (string)($form['settings']['preview_template'] ?? '');
        return trim($candidate) !== '' ? $candidate : $this->registry->get('preview')->defaultHtml();
    }

    public function resolveStep(array $step): string
    {
        $candidate = (string)($step['template'] ?? '');
        return trim($candidate) !== '' ? $candidate : $this->registry->get('step')->defaultHtml();
    }

    public function hasOverride(string $stored, string $default): bool
    {
        if (trim($stored) === '') return false;
        return $this->canonical($stored) !== $this->canonical($default);
    }

    public function editorValue(string $stored, string $default): string
    {
        return $this->hasOverride($stored, $default) ? $stored : $default;
    }

    public function normalizeOverride(string $submitted, string $default): string
    {
        return $this->canonical($submitted) === $this->canonical($default) ? '' : $submitted;
    }

    private function canonical(string $html): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $html));
    }
}
