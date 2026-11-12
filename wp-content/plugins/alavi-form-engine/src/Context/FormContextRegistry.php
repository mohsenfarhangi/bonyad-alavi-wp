<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Context;

use InvalidArgumentException;

final class FormContextRegistry
{
    /** @var array<string,FormContextProviderInterface> */
    private array $providers = [];

    public function register(FormContextProviderInterface $provider): void
    {
        $key = $provider->key();
        if ($key === '' || sanitize_key($key) !== $key) {
            throw new InvalidArgumentException('Form Context provider key must be a lowercase WordPress key.');
        }

        $this->providers[$key] = $provider;
    }

    public function get(string $key): ?FormContextProviderInterface
    {
        return $this->providers[sanitize_key($key)] ?? null;
    }

    /** @return array<string,FormContextProviderInterface> */
    public function all(): array
    {
        return $this->providers;
    }

    /** @return array<string,string> */
    public function providerOptions(): array
    {
        $options = [];
        foreach ($this->providers as $key => $provider) {
            $options[$key] = $provider->label();
        }
        return $options;
    }

    /**
     * Elementor controls use provider-prefixed values to avoid collisions when
     * multiple providers expose the same fixed identifier.
     *
     * @return array<string,string>
     */
    public function fixedOptions(): array
    {
        $options = [];
        foreach ($this->providers as $key => $provider) {
            foreach ($provider->fixedOptions() as $value => $label) {
                $value = (string)$value;
                if ($value === '') continue;
                $options[$key . '|' . $value] = $provider->label() . ' — ' . (string)$label;
            }
        }
        return $options;
    }
}
