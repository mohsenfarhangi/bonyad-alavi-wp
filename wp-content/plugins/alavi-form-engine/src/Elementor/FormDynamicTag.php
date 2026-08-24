<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Elementor;

use BonyadAlavi\FormEngine\Core\Plugin;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use RuntimeException;

final class FormDynamicTag extends \Elementor\Core\DynamicTags\Tag
{
    public function get_name(): string { return 'alavi-form-data'; }
    public function get_title(): string { return 'Alavi Form'; }
    public function get_group(): string { return 'site'; }
    public function get_categories(): array { return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY]; }

    protected function register_controls(): void
    {
        $options=[];
        foreach($this->registry()->all() as $slug=>$form) $options[$slug]=$form->toArray()['title'];
        $this->add_control('form_slug',[
            'label'=>'فرم',
            'type'=>\Elementor\Controls_Manager::SELECT,
            'options'=>$options,
            'default'=>array_key_first($options),
        ]);
        $this->add_control('value',[
            'label'=>'خروجی',
            'type'=>\Elementor\Controls_Manager::SELECT,
            'options'=>['title'=>'عنوان فرم','shortcode'=>'Shortcode'],
            'default'=>'title',
        ]);
    }

    public function render(): void
    {
        $slug=(string)$this->get_settings('form_slug');
        if(!$this->registry()->has($slug)) return;
        $form=$this->registry()->get($slug)->toArray();
        echo esc_html($this->get_settings('value')==='shortcode' ? '[alavi_form id="'.$slug.'"]' : $form['title']);
    }

    private function registry(): FormRegistry
    {
        $service = Plugin::instance()->container()->get(FormRegistry::class);
        if (!$service instanceof FormRegistry) {
            throw new RuntimeException('Alavi Form Engine FormRegistry service is unavailable.');
        }
        return $service;
    }
}
