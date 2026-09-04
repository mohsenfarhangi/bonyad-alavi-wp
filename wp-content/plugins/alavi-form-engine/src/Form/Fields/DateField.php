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


    public function inputMode(string $mode): static
    {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['combined','picker','manual'], true)) {
            throw new InvalidArgumentException('DateField input mode must be combined, picker or manual.');
        }
        $this->config['date_input_mode'] = $mode;
        return $this;
    }

    public function combined(): static { return $this->inputMode('combined'); }
    public function pickerOnly(): static { return $this->inputMode('picker'); }
    public function manualOnly(): static { return $this->inputMode('manual'); }

    public function toArray(): array
    {
        return array_replace(parent::toArray(), [
            'calendar' => $this->config['calendar'] ?? 'gregorian',
            'date_input_mode' => $this->config['date_input_mode'] ?? 'combined',
        ]);
    }
}
