<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Events;

final readonly class EventDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public string $description = '',
        public string $group = 'submission'
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'group' => $this->group,
        ];
    }
}
