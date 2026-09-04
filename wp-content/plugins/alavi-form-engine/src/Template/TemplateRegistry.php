<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Template;

use InvalidArgumentException;

final class TemplateRegistry
{
    /** @var array<string,TemplateDefinition> */
    private array $definitions = [];

    public function __construct()
    {
        $this->register(new TemplateDefinition(
            'form',
            'قالب کل فرم',
            'ساختار اصلی فرم شامل هدر، نشان، نوار مراحل و Stepها.',
            <<<'HTML'
<header class="afe-form-header">
    {{brand_mark}}
    <div>
        <h1>{{title}}</h1>
        <p>{{description}}</p>
        {{slogan}}
    </div>
</header>
{{progress}}
{{steps}}
HTML,
            ['{{title}}','{{description}}','{{brand_mark}}','{{slogan}}','{{progress}}','{{steps}}']
        ));

        $this->register(new TemplateDefinition(
            'preview',
            'قالب پیش‌نمایش',
            'ساختار مرحله پیش‌نمایش قبل از ثبت نهایی یا نمای خوانای اطلاعات ثبت‌شده.',
            <<<'HTML'
<div class="afe-preview-layout">
    <div class="afe-preview-intro">
        <h3>{{preview_title}}</h3>
        <p>{{preview_description}}</p>
    </div>
    {{preview_fields}}
</div>
HTML,
            ['{{title}}','{{description}}','{{preview_title}}','{{preview_description}}','{{preview_fields}}','{{field:field_name}}']
        ));

        $this->register(new TemplateDefinition(
            'step',
            'قالب محتوای مرحله',
            'این قالب داخل شبکه فیلدهای هر Step رندر می‌شود. {{items}} همان چیدمان پیش‌فرض تمام آیتم‌های مرحله است.',
            '{{items}}',
            ['{{items}}','{{field:field_name}}']
        ));
    }

    public function register(TemplateDefinition $definition): void
    {
        $this->definitions[$definition->key()] = $definition;
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    public function get(string $key): TemplateDefinition
    {
        if (!$this->has($key)) {
            throw new InvalidArgumentException("Unknown AFE template definition: {$key}");
        }
        return $this->definitions[$key];
    }

    /** @return array<string,TemplateDefinition> */
    public function all(): array
    {
        return $this->definitions;
    }
}
