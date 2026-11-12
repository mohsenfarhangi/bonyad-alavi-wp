<?php
declare(strict_types=1);

use BonyadAlavi\FormEngine\Actions\ActionDefinition;
use BonyadAlavi\FormEngine\Actions\ActionRegistry;
use BonyadAlavi\FormEngine\DataSource\DataSourceManager;
use BonyadAlavi\FormEngine\Form\Form;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Form\Step;
use BonyadAlavi\FormEngine\Form\Fields\EmailField;
use BonyadAlavi\FormEngine\Form\Fields\NumberField;
use BonyadAlavi\FormEngine\Form\Fields\SelectField;
use BonyadAlavi\FormEngine\Form\Fields\TelField;
use BonyadAlavi\FormEngine\Form\Fields\TextField;
use BonyadAlavi\FormEngine\Validation\ValidatorDefinition;
use BonyadAlavi\FormEngine\Validation\ValidatorRegistry;

defined('ABSPATH') || exit;

/**
 * Shared code-defined AFE form for every Quick Support category.
 */
final class BA_Quick_Support_Form
{
    public const SLUG = 'quick-support-payment';

    public static function register(FormRegistry $registry): void
    {
        $registry->register(self::build());
    }

    public static function registerContextProvider($registry): void
    {
        if ($registry instanceof \BonyadAlavi\FormEngine\Context\FormContextRegistry) {
            $registry->register(new BA_Quick_Support_Category_Context_Provider());
        }
    }

    public static function registerDataSources(DataSourceManager $sources): void
    {
        $sources->register('ba_quick_support_gateways', static function(array $values): array {
            $context = is_array($values['__afe_context'] ?? null) ? $values['__afe_context'] : [];
            return BA_Quick_Support_Payment_Service::gateway_choices_for_context($context);
        });
    }

    public static function registerValidators(ValidatorRegistry $validators): void
    {
        $validators->register(new ValidatorDefinition(
            'ba_qs_county_in_province',
            'تطبیق شهرستان با استان',
            ['select'],
            'شهرستان انتخاب‌شده متعلق به استان انتخاب‌شده نیست.',
            static function(string $value, array $field, array $config, array $data, array $context = []): bool {
                $countyId = absint($value);
                if ($countyId <= 0) return true;

                $provinceId = absint($data['province'] ?? 0);
                if ($provinceId <= 0) return false;

                global $wpdb;
                $table = $wpdb->prefix . 'afe_geo_counties';
                if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;

                $matched = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$table} WHERE id=%d AND province_id=%d LIMIT 1",
                    $countyId,
                    $provinceId
                ));

                return (int)$matched === $countyId;
            }
        ));

        $validators->register(new ValidatorDefinition(
            'ba_qs_amount_limits',
            'محدوده مبلغ دسته پرداخت سریع',
            ['number'],
            'مبلغ واردشده خارج از محدوده مجاز این دسته است یا دسته معتبر به فرم متصل نیست.',
            static function(string $value, array $field, array $config, array $data, array $context = []): bool {
                if (empty($context['category_id'])) return false;

                $amount = (float)BA_Quick_Support_Payment_Service::normalizeAmount($value);
                $minimum = (float)($context['minimum_amount'] ?? 0);
                $maximum = (float)($context['maximum_amount'] ?? 0);

                if ($amount <= 0) return false;
                if ($minimum > 0 && $amount < $minimum) return false;
                if ($maximum > 0 && $amount > $maximum) return false;
                return true;
            }
        ));
    }

    public static function registerActionDefinition(ActionRegistry $actions): void
    {
        $actions->register(
            new ActionDefinition(
                'ba_quick_support_payment',
                'پرداخت سریع مشارکت',
                'سفارش مستقل WooCommerce را برای دسته معتبر Context ایجاد می‌کند و کاربر را به درگاه Hosted/Redirect منتقل می‌کند.',
                'payment',
                [],
                false,
                false,
                true
            ),
            new BA_Quick_Support_Payment_Action(new BA_Quick_Support_Payment_Service())
        );
    }

    public static function build(): Form
    {
        $fields = [
            TelField::make('mobile')
                ->label('شماره همراه')
                ->placeholder('0912XXXXXXX')
                ->rule('mobile_09')
                ->attributes([
                    'maxlength' => 11,
                    'minlength' => 11,
                    'pattern' => '09[0-9]{9}',
                    'inputmode' => 'numeric',
                    'autocomplete' => 'tel',
                    'data-afe-digits-only' => '1',
                    'data-afe-invalid-message' => 'شماره همراه باید ۱۱ رقم و با ۰۹ شروع شود.',
                ])
                ->required()
                ->width(6),

            TextField::make('first_name')
                ->label('نام')
                ->attributes(['autocomplete'=>'given-name'])
                ->required()
                ->width(6),

            TextField::make('last_name')
                ->label('نام خانوادگی')
                ->attributes(['autocomplete'=>'family-name'])
                ->required()
                ->width(6),

            SelectField::make('province')
                ->label('استان')
                ->source(['type'=>'geo','level'=>'province'])
                ->native()
                ->width(6),

            SelectField::make('county')
                ->label('شهرستان')
                ->source(['type'=>'geo','level'=>'county','parent'=>'province'])
                ->dependsOn('province')
                ->native()
                ->meta('validators', [[
                    'key'=>'ba_qs_county_in_province',
                    'message'=>'ابتدا استان را انتخاب کنید و سپس شهرستان همان استان را انتخاب کنید.',
                ]])
                ->width(6),

            TextField::make('national_id')
                ->label('کد ملی')
                ->rule('national_id')
                ->attributes([
                    'maxlength'=>10,
                    'minlength'=>10,
                    'pattern'=>'[0-9]{10}',
                    'inputmode'=>'numeric',
                    'autocomplete'=>'off',
                    'data-afe-digits-only'=>'1',
                    'data-afe-invalid-message'=>'کد ملی واردشده معتبر نیست.',
                ])
                ->width(6),

            EmailField::make('email')
                ->label('ایمیل')
                ->rule('email')
                ->attributes(['autocomplete'=>'email'])
                ->width(6),

            NumberField::make('amount')
                ->label('مبلغ حمایت')
                ->placeholder('مبلغ را وارد کنید')
                ->attributes([
                    'min'=>'1',
                    'step'=>'1',
                    'inputmode'=>'decimal',
                    'autocomplete'=>'off',
                    'data-afe-ltr'=>'1',
                ])
                ->description('مبلغ را به واحد پول فعال در فروشگاه وارد کنید.')
                ->meta('validators', [[
                    'key'=>'ba_qs_amount_limits',
                    'message'=>'مبلغ واردشده خارج از محدوده مجاز این دسته است.',
                ]])
                ->required()
                ->width(12),
        ];

        if (BA_Quick_Support_Payment_Service::customer_gateway_selection_enabled()) {
            $fields[] = SelectField::make('payment_gateway')
                ->label('درگاه پرداخت')
                ->source('ba_quick_support_gateways')
                ->placeholder('انتخاب درگاه پرداخت')
                ->native()
                ->hideWhenSingleOption()
                ->required()
                ->width(12);
        }

        return Form::make(self::SLUG)
            ->title('پرداخت سریع مشارکت')
            ->description('اطلاعات خود را وارد کنید تا به درگاه پرداخت منتقل شوید.')
            ->settings([
                'wizard'=>false,
                'save_draft'=>false,
                'show_progress'=>false,
                'show_tracking_code'=>false,
                'editing_enabled'=>false,
                'editing_modes'=>[],
                'preview_enabled'=>false,
                'captcha'=>'custom',
                'rate_limit'=>5,
                'success_message'=>'اطلاعات ثبت شد؛ در حال انتقال به درگاه پرداخت هستیم.',
            ])
            ->steps([
                Step::make('payment', 'اطلاعات پرداخت')
                    ->fields($fields),
            ])
            ->actions([
                [
                    'action_key'=>'quick_support_payment',
                    'type'=>'ba_quick_support_payment',
                    'on'=>['submission.submitted'],
                    'execution_policy'=>'once_per_submission',
                    'on_error'=>'stop',
                    'config'=>[],
                ],
            ]);
    }
}
