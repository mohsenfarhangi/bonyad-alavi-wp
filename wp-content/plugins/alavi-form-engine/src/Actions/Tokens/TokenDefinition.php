<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Tokens;

final readonly class TokenDefinition
{
    public function __construct(
        public string $token,
        public string $label,
        public string $description = '',
        public string $group = 'submission'
    ) {}

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'label' => $this->label,
            'description' => $this->description,
            'group' => $this->group,
        ];
    }
}
