<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Template;

use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Repository\FormRepository;

final class TemplateOverrideNormalizer
{
    private const OPTION = 'afe_template_overrides_normalized_1_0_27';

    public function __construct(
        private readonly FormRepository $forms,
        private readonly TemplateResolver $templates
    ) {}

    public function run(FormRegistry $registry): void
    {
        if (get_option(self::OPTION, false)) return;

        foreach ($registry->all() as $formObject) {
            $slug=$formObject->slug();
            $row=$this->forms->row($slug);
            if (!$row) continue;

            $codeForm=$formObject->toArray();
            $overrides=$this->forms->overrides($slug);
            $settings=$this->forms->adminSettings($slug);
            $formTemplate=(string)($row->template_html??'');
            $changed=false;

            $formDefault=wp_kses_post($this->templates->defaultForm($codeForm));
            if (trim($formTemplate)!=='' && !$this->templates->hasOverride($formTemplate,$formDefault)) {
                $formTemplate='';
                $changed=true;
            }

            if (array_key_exists('preview_template',$settings)) {
                $storedPreview=(string)$settings['preview_template'];
                $previewDefault=wp_kses_post($this->templates->defaultPreview($codeForm));
                if (trim($storedPreview)==='' || !$this->templates->hasOverride($storedPreview,$previewDefault)) {
                    unset($settings['preview_template']);
                    $changed=true;
                }
            }

            $codeSteps=[];
            foreach ((array)($codeForm['steps']??[]) as $step) $codeSteps[(string)($step['key']??'')]=$step;
            foreach ((array)($overrides['steps']??[]) as $stepKey=>$stepOverride) {
                if (!isset($codeSteps[$stepKey]) || !is_array($stepOverride) || !array_key_exists('template',$stepOverride)) continue;
                $storedStep=(string)$stepOverride['template'];
                $stepDefault=wp_kses_post($this->templates->defaultStep($codeSteps[$stepKey]));
                if (trim($storedStep)==='' || !$this->templates->hasOverride($storedStep,$stepDefault)) {
                    unset($overrides['steps'][$stepKey]['template']);
                    if (empty($overrides['steps'][$stepKey])) unset($overrides['steps'][$stepKey]);
                    $changed=true;
                }
            }
            if (isset($overrides['steps']) && empty($overrides['steps'])) unset($overrides['steps']);

            if ($changed) {
                $this->forms->saveAdminConfig(
                    $slug,
                    $overrides,
                    $formTemplate,
                    (string)($row->custom_css??''),
                    (string)($row->custom_js??''),
                    $settings
                );
            }
        }

        update_option(self::OPTION,1,false);
    }
}
