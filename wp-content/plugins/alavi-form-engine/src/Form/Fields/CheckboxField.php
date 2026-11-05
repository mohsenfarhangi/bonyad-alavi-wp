<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Form\Fields;

/**
 * Standalone opt-in checkbox, with canonical checked value "1".
 * Missing data stays empty so required field validation can reject unchecked consent.
 */
final class CheckboxField extends AbstractField
{
    public const TYPE = 'checkbox';
}
