<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Elementor;

use BonyadAlavi\FormEngine\Core\Plugin;
use BonyadAlavi\FormEngine\Context\FormContextRegistry;
use BonyadAlavi\FormEngine\Context\FormContextManager;
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

        $contextRegistry = $this->contextRegistry();
        $providerOptions = $contextRegistry->providerOptions();
        if ($providerOptions !== []) {
            $this->start_controls_section('form_context', ['label'=>'Context فرم']);

            $this->add_control('context_provider', [
                'label'=>'ارائه‌دهنده Context',
                'type'=>\Elementor\Controls_Manager::SELECT,
                'options'=>[''=>'بدون Context'] + $providerOptions,
                'default'=>'',
            ]);

            $this->add_control('context_source', [
                'label'=>'منبع Context',
                'type'=>\Elementor\Controls_Manager::SELECT,
                'options'=>[
                    'current_page'=>'صفحه جاری',
                    'fixed'=>'انتخاب ثابت',
                    'query_parameter'=>'پارامتر URL',
                ],
                'default'=>'current_page',
                'condition'=>['context_provider!'=>''],
            ]);

            $this->add_control('context_identifier_type', [
                'label'=>'نوع شناسه در پارامتر URL',
                'type'=>\Elementor\Controls_Manager::SELECT,
                'options'=>['slug'=>'Slug','id'=>'ID'],
                'default'=>'slug',
                'condition'=>['context_source'=>'query_parameter'],
            ]);

            $this->add_control('context_query_parameter', [
                'label'=>'نام پارامتر URL',
                'type'=>\Elementor\Controls_Manager::TEXT,
                'default'=>'support_category',
                'condition'=>['context_source'=>'query_parameter'],
            ]);

            $this->add_control('context_fixed_value', [
                'label'=>'مقدار ثابت Context',
                'type'=>\Elementor\Controls_Manager::SELECT,
                'options'=>[''=> 'انتخاب کنید'] + $contextRegistry->fixedOptions(),
                'default'=>'',
                'condition'=>['context_source'=>'fixed'],
            ]);

            $this->end_controls_section();
        }
    }

    protected function render(): void
    {
        $settings=$this->get_settings_for_display();
        $slug = sanitize_key((string)($settings['form_slug'] ?? ''));
        $contextToken = '';
        $formContext = [];

        $provider = sanitize_key((string)($settings['context_provider'] ?? ''));
        if ($provider !== '') {
            $issued = $this->contextManager()->issue($slug, [
                'provider'=>$provider,
                'source'=>(string)($settings['context_source'] ?? 'current_page'),
                'identifier_type'=>(string)($settings['context_identifier_type'] ?? 'slug'),
                'query_parameter'=>(string)($settings['context_query_parameter'] ?? 'support_category'),
                'fixed_value'=>(string)($settings['context_fixed_value'] ?? ''),
            ]);

            if (is_wp_error($issued)) {
                echo '<div class="afe-notice afe-notice-error">'.esc_html($issued->get_error_message()).'</div>';
                return;
            }

            $contextToken = (string)$issued['token'];
            $formContext = (array)$issued['context'];
        }

        echo $this->renderer()->render($slug, $contextToken, $formContext);
    }

    /**
     * Elementor owns Widget_Base construction and may instantiate widgets later
     * with its native ($data, $args) constructor signature. Dependencies are
     * therefore resolved lazily at the integration boundary instead of being
     * injected through this widget's constructor.
     */
    private function contextRegistry(): FormContextRegistry
    {
        $service = Plugin::instance()->container()->get(FormContextRegistry::class);
        if (!$service instanceof FormContextRegistry) {
            throw new RuntimeException('Alavi Form Engine FormContextRegistry service is unavailable.');
        }
        return $service;
    }

    private function contextManager(): FormContextManager
    {
        $service = Plugin::instance()->container()->get(FormContextManager::class);
        if (!$service instanceof FormContextManager) {
            throw new RuntimeException('Alavi Form Engine FormContextManager service is unavailable.');
        }
        return $service;
    }

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
