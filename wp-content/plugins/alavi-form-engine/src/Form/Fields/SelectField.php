<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Form\Fields;

final class SelectField extends AbstractField
{
    public const TYPE = 'select';

    /**
     * Render this field using Alavi Form Engine's isolated custom select UI.
     * The underlying native <select> remains the source of truth for forms,
     * validation and submission.
     */
    public function custom(bool $enabled = true): static
    {
        $this->config['select_mode'] = $enabled ? 'custom' : 'native';
        return $this;
    }

    /** Render the browser-native select while still blocking theme Select2. */
    public function native(): static
    {
        $this->config['select_mode'] = 'native';
        return $this;
    }

    /** Force search on/off. Null/auto uses the option-count threshold. */
    public function searchable(bool $searchable = true): static
    {
        $this->config['searchable'] = $searchable;
        return $this;
    }

    /** Enable search automatically when option count reaches this value. */
    public function searchThreshold(int $count): static
    {
        $this->config['search_threshold'] = max(1, $count);
        return $this;
    }

    public function toArray(): array
    {
        return array_replace(parent::toArray(), [
            'select_mode' => $this->config['select_mode'] ?? 'custom',
            'searchable' => $this->config['searchable'] ?? null,
            'search_threshold' => $this->config['search_threshold'] ?? 7,
        ]);
    }
}
