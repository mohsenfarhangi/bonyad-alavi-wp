<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Form\Fields;

final class HtmlBlock
{
    public function __construct(private readonly string $html) {}

    public static function make(string $html): self { return new self($html); }

    public function toArray(): array
    {
        return ['type' => 'html', 'html' => $this->html, 'name' => 'html_' . md5($this->html)];
    }
}
