<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Template;

final class TemplateDefinition
{
    /** @param list<string> $tokens */
    public function __construct(
        private readonly string $key,
        private readonly string $label,
        private readonly string $description,
        private readonly string $defaultHtml,
        private readonly array $tokens = []
    ) {}

    public function key(): string { return $this->key; }
    public function label(): string { return $this->label; }
    public function description(): string { return $this->description; }
    public function defaultHtml(): string { return $this->defaultHtml; }
    /** @return list<string> */
    public function tokens(): array { return $this->tokens; }
}
