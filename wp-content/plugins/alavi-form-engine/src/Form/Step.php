<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Form;

use BonyadAlavi\FormEngine\Form\Fields\AbstractField;
use BonyadAlavi\FormEngine\Form\Fields\HtmlBlock;

final class Step
{
    private array $items = [];
    private ?string $template = null;
    private ?string $description = null;

    public function __construct(private readonly string $key, private string $title) {}

    public static function make(string $key, string $title): self { return new self($key, $title); }

    public function fields(array $items): self { $this->items = $items; return $this; }
    public function template(string $html): self { $this->template = $html; return $this; }
    public function description(string $description): self { $this->description = $description; return $this; }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'template' => $this->template,
            'items' => array_map(
                static fn(AbstractField|HtmlBlock $item) => $item->toArray(),
                $this->items
            ),
        ];
    }
}
