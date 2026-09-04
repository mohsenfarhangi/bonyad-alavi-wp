<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\InputMask;

final class InputMaskDefinition
{
    /** @param list<string> $fieldTypes */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $pattern,
        public readonly array $fieldTypes = ['text','tel'],
        public readonly string $example = '',
        public readonly string $description = '',
        public readonly string $inputMode = 'text'
    ) {}

    public function supports(string $fieldType): bool
    {
        return in_array($fieldType, $this->fieldTypes, true);
    }
}
