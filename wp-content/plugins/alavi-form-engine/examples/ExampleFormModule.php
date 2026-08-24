<?php
/**
 * Example extension. Do not edit the main plugin to add forms.
 */
declare(strict_types=1);

use BonyadAlavi\FormEngine\Form\Form;
use BonyadAlavi\FormEngine\Form\Step;
use BonyadAlavi\FormEngine\Form\Fields\TextField;
use BonyadAlavi\FormEngine\Form\Fields\SelectField;
use BonyadAlavi\FormEngine\Form\Fields\HtmlBlock;

add_action('afe_register_forms', static function ($registry): void {
    $registry->register(
        Form::make('example-project-request')
            ->title('درخواست پروژه نمونه')
            ->steps([
                Step::make('main','اطلاعات اصلی')->fields([
                    HtmlBlock::make('<div class="my-layout">این HTML بخشی از تعریف فرم است.</div>'),
                    TextField::make('title')->label('عنوان')->required(),
                    SelectField::make('priority')->label('اولویت')->options([
                        'normal'=>'عادی','high'=>'زیاد',
                    ])->required(),
                ]),
            ])
            ->settings([
                'editing_enabled'=>true,
                'editing_modes'=>['wordpress','link'],
                'captcha'=>'custom',
            ])
    );
});
