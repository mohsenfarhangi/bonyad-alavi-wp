<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Elementor;

use BonyadAlavi\FormEngine\Core\Plugin;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Form\Renderer;
use RuntimeException;

final class FormWidget extends \Elementor\Widget_Base
{
    public function get_name(): string { return 'alavi_form_engine_form'; }
    public function get_title(): string { return 'فرم علوی'; }
    public function get_icon(): string { return 'eicon-form-horizontal'; }
    public function get_categories(): array { return ['general']; }
    public function get_style_depends(): array { return ['afe-frontend']; }
    public function get_script_depends(): array { return ['afe-frontend']; }

    protected function register_controls(): void
    {
        $this->start_controls_section('content',['label'=>'فرم']);
        $options=[];
        foreach($this->registry()->all() as $slug=>$form) $options[$slug]=$form->toArray()['title'];
        $this->add_control('form_slug',[
            'label'=>'انتخاب فرم',
            'type'=>\Elementor\Controls_Manager::SELECT,
            'options'=>$options,
            'default'=>array_key_first($options),
        ]);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings=$this->get_settings_for_display();
        echo $this->renderer()->render((string)($settings['form_slug']??''));
    }

    /**
     * Elementor owns Widget_Base construction and may instantiate widgets later
     * with its native ($data, $args) constructor signature. Dependencies are
     * therefore resolved lazily at the integration boundary instead of being
     * injected through this widget's constructor.
     */
    private function registry(): FormRegistry
    {
        $service = Plugin::instance()->container()->get(FormRegistry::class);
        if (!$service instanceof FormRegistry) {
            throw new RuntimeException('Alavi Form Engine FormRegistry service is unavailable.');
        }
        return $service;
    }

    private function renderer(): Renderer
    {
        $service = Plugin::instance()->container()->get(Renderer::class);
        if (!$service instanceof Renderer) {
            throw new RuntimeException('Alavi Form Engine Renderer service is unavailable.');
        }
        return $service;
    }
}
