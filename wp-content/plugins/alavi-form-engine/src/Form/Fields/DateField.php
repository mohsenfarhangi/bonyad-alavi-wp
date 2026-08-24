<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Form\Fields;

use InvalidArgumentException;

final class DateField extends AbstractField
{
    public const TYPE = 'date';

    public function calendar(string $calendar): static
    {
        $calendar = strtolower(trim($calendar));
        if (!in_array($calendar, ['gregorian','jalali'], true)) {
            throw new InvalidArgumentException('DateField calendar must be gregorian or jalali.');
        }
        $this->config['calendar'] = $calendar;
        return $this;
    }

    public function jalali(): static
    {
        return $this->calendar('jalali');
    }

    public function gregorian(): static
    {
        return $this->calendar('gregorian');
    }

    public function toArray(): array
    {
        return array_replace(parent::toArray(), [
            'calendar' => $this->config['calendar'] ?? 'gregorian',
        ]);
    }
}
