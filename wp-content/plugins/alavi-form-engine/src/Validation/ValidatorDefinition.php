<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Validation;

use Closure;

final class ValidatorDefinition
{
    /** @param list<string> $fieldTypes */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $fieldTypes,
        public readonly string $defaultMessage,
        private readonly Closure $callback
    ) {}

    public function supports(string $fieldType): bool
    {
        return $this->fieldTypes === [] || in_array($fieldType, $this->fieldTypes, true);
    }

    public function validate(string $value, array $field, array $config = []): bool
    {
        return (bool)($this->callback)($value, $field, $config);
    }
}
