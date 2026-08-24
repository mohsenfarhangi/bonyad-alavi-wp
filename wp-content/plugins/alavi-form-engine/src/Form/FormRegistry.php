<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Form;

use InvalidArgumentException;

final class FormRegistry
{
    /** @var array<string, Form> */
    private array $forms = [];

    public function register(Form $form): void
    {
        $this->forms[$form->slug()] = $form;
    }

    public function get(string $slug): Form
    {
        if (!isset($this->forms[$slug])) {
            throw new InvalidArgumentException("Unknown form: {$slug}");
        }
        return $this->forms[$slug];
    }

    public function has(string $slug): bool { return isset($this->forms[$slug]); }

    /** @return array<string, Form> */
    public function all(): array { return $this->forms; }
}
