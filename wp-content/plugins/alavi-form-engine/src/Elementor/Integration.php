<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Elementor;

use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Form\Renderer;

final class Integration
{
    public function __construct(private readonly FormRegistry $registry, private readonly Renderer $renderer) {}

    public function boot(): void
    {
        add_action('elementor/widgets/register', function($widgetsManager): void {
            if (!class_exists('\Elementor\Widget_Base')) return;
            $widgetsManager->register(new FormWidget());
        });

        add_action('elementor/dynamic_tags/register', function($dynamicTagsManager): void {
            if (!class_exists('\Elementor\Core\DynamicTags\Tag')) return;
            $dynamicTagsManager->register(new FormDynamicTag());
        });
    }
}
