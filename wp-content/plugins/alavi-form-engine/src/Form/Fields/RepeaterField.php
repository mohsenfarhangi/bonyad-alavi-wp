<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Form\Fields;

final class RepeaterField extends AbstractField
{
    public const TYPE = 'repeater';

    /** @var array<int, AbstractField|HtmlBlock> */
    private array $children = [];

    public function fields(array $fields): static
    {
        $this->children = $fields;
        return $this;
    }

    public function min(int $min): static { $this->config['min'] = max(0, $min); return $this; }
    public function max(int $max): static { $this->config['max'] = max(1, $max); return $this; }
    public function addButton(string $label): static { $this->config['add_button'] = $label; return $this; }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['fields'] = array_map(static fn($field) => $field->toArray(), $this->children);
        $array['min'] = $array['min'] ?? 0;
        $array['max'] = $array['max'] ?? 50;
        return $array;
    }
}
