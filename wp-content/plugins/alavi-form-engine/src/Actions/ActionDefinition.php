<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

final readonly class ActionDefinition
{
    /**
     * @param array<string, array<string, mixed>> $settingsSchema
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $description = '',
        public string $group = 'communication',
        public array $settingsSchema = [],
        public bool $supportsConditionalLogic = true,
        public bool $supportsExecutionPolicy = true
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'group' => $this->group,
            'settings_schema' => $this->settingsSchema,
            'supports_conditional_logic' => $this->supportsConditionalLogic,
            'supports_execution_policy' => $this->supportsExecutionPolicy,
        ];
    }
}
