<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Context;

use WP_Error;

/**
 * Site integrations expose trusted, server-resolved context through this contract.
 *
 * Context providers belong to extensions/themes. The Form Engine only transports
 * their canonical data and never knows about a specific business domain.
 */
interface FormContextProviderInterface
{
    public function key(): string;

    public function label(): string;

    /**
     * @return list<string>
     */
    public function supportedSources(): array;

    /**
     * @return array<string,string> stable value => label
     */
    public function fixedOptions(): array;

    /**
     * Resolve a context from the configured source.
     *
     * @return array<string,mixed>|WP_Error
     */
    public function resolve(array $settings, string $formSlug): array|WP_Error;

    /**
     * Re-check and return canonical context data on every form submission.
     *
     * @return array<string,mixed>|WP_Error
     */
    public function validate(array $context, string $formSlug): array|WP_Error;
}
