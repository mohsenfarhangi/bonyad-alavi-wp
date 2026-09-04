<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Forms;

use BonyadAlavi\FormEngine\Form\Form;
use BonyadAlavi\FormEngine\Form\Step;
use BonyadAlavi\FormEngine\Form\Fields\{
    TextField, NumberField, TextareaField, SelectField, DateField, TelField,
    FileField, RadioField, EmailField, UrlField, RepeaterField, HtmlBlock
};

final class JihadiGroupRegistrationForm
{
    public function build(): Form
    {
        $groupTypes = [
            'ngo'=>'سازمان مردم نهاد (NGO)',
            'jihadi_hq'=>'قرارگاه جهادی',
            'jihadi_group'=>'گروه جهادی',
            'religious_board'=>'هیئت مذهبی',
            'charity_center'=>'مرکز نیکوکاری',
            'charity_religious'=>'خیریه (اماکن مذهبی و زیارتی)',
            'culture_art'=>'کانون فرهنگی هنری',
            'mokeb'=>'موکب',
            'mosque_culture'=>'کانون فرهنگی تربیتی (مسجد)',
            'science_park'=>'پارک علم و فناوری',
            'creative_house'=>'خانه خلاق',
            'growth_center'=>'مرکز رشد',
            'knowledge_based'=>'مجموعه دانش‌بنیان',
            'basij_base'=>'پایگاه بسیج',
            'other'=>'سایر',
        ];

        $licenseIssuers = [
            'basij_sazandegi'=>'بسیج سازندگی',
            'interior'=>'وزارت کشور',
            'basij_tollab'=>'بسیج طلاب',
            'islamic_propagation'=>'سازمان تبلیغات اسلامی',
            'emdad'=>'کمیته امداد',
            'sports_youth'=>'وزارت ورزش و جوانان',
            'seminary_management'=>'مرکز مدیریت حوزه‌های علمیه',
            'culture'=>'وزارت فرهنگ و ارشاد اسلامی',
            'behzisti'=>'سازمان بهزیستی',
            'mosques'=>'مرکز رسیدگی به امور مساجد',
            'atabat'=>'ستاد بازسازی عتبات',
            'awqaf'=>'سازمان اوقاف',
            'student_basij'=>'بسیج دانشجویی',
            'setad'=>'ستاد اجرایی فرمان حضرت امام',
            'razavi'=>'آستان قدس رضوی (بنیاد کرامت)',
            'science_ministry'=>'وزارت علوم',
            'science_vp'=>'معاونت علمی ریاست جمهوری',
            'other'=>'سایر',
        ];

        $activityAreas = [
            'social_harms'=>'آسیب‌های اجتماعی',
            'employment'=>'اشتغال و کارآفرینی',
            'construction'=>'عمرانی و سازندگی',
            'education'=>'تعلیم و تربیت',
            'marriage_family'=>'ازدواج و خانواده',
            'health'=>'سلامت و درمان',
            'culture_art'=>'فرهنگی و هنری',
            'promotion'=>'تبلیغ و تبیین',
            'media'=>'رسانه و فضای مجازی',
            'rescue'=>'امداد و نجات',
            'empowerment'=>'آموزش و توانمندسازی',
        ];

        $education = [
            'under_diploma'=>'زیر دیپلم',
            'diploma'=>'دیپلم',
            'bachelor'=>'کارشناسی',
            'master'=>'کارشناسی ارشد',
            'phd'=>'دکترا',
        ];

        $province = static function(string $name='province', bool $required=true): SelectField {
            $field=SelectField::make($name)->label('استان')->source(['type'=>'geo','level'=>'province']);
            return $required ? $field->required() : $field;
        };

        $county = static function(string $name='county', string $parent='province', bool $required=true): SelectField {
            $field=SelectField::make($name)->label('شهرستان')
                ->source(['type'=>'geo','level'=>'county','parent'=>$parent])->dependsOn($parent);
            return $required ? $field->required() : $field;
        };

        $district = static function(string $name='district', string $parent='county', bool $required=true): SelectField {
            $field=SelectField::make($name)->label('بخش')
                ->source(['type'=>'geo','level'=>'district','parent'=>$parent])->dependsOn($parent);
            return $required ? $field->required() : $field;
        };

        return Form::make('jihadi-group-registration')
            ->title('شناسنامه گروه‌های مردمی و جهادی | طرح جهادگر شهید رسول عالم باقری')
            ->description('برای همکاری با بنیاد علوی در محرومیت‌زدایی')
            ->steps([
                Step::make('identity', '۱. اطلاعات هویتی')
                    ->fields([
                    HtmlBlock::make('<div class="afe-section-intro"><strong>مشخصات ثبتی و هویتی گروه</strong><span>اطلاعات مطابق مدارک رسمی وارد شود.</span></div>'),
                    TextField::make('group_name')->label('نام گروه')->required()->width(6),
                    SelectField::make('group_nature')->label('ماهیت گروه')->options($groupTypes)->required()->width(6),
                    TextField::make('group_nature_other')->label('توضیحات ماهیت گروه')
                        ->condition([['field'=>'group_nature','operator'=>'=','value'=>'other']], 'show')->required()->width(12),
                    SelectField::make('license_issuer')->label('مرجع صدور مجوز')->options($licenseIssuers)->required()->width(6),
                    TextField::make('license_issuer_other')->label('توضیحات مرجع صدور مجوز')
                        ->condition([['field'=>'license_issuer','operator'=>'=','value'=>'other']], 'show')->required()->width(6),
                    TextField::make('registration_code')->label('کد ثبت گروه')->required()->width(4),
                    TextField::make('short_name')->label('نام اختصاری مجموعه')->width(4),
                    DateField::make('established_at')->jalali()->label('تاریخ تأسیس')->required()->width(4),
                    HtmlBlock::make('<div class="afe-inline-title">نشانی گروه</div>'),
                    $province()->width(4),
                    $county()->width(4),
                    $district()->width(4),
                    TextField::make('village_neighborhood')->label('روستا / محله')->placeholder('نام روستا یا محله را تایپ کنید')->required()->width(12),
                    TelField::make('group_mobile')->label('تلفن همراه گروه')->default('09')->rule('mobile_09')->required()->width(6)
                        ->attributes(['maxlength'=>11,'minlength'=>11,'pattern'=>'09[0-9]{9}','inputmode'=>'numeric','autocomplete'=>'tel','data-afe-digits-only'=>'1','data-afe-fixed-prefix'=>'09','data-afe-invalid-message'=>'شماره همراه باید دقیقاً ۱۱ رقم و با ۰۹ شروع شود.']),
                    TelField::make('group_landline')->label('تلفن ثابت گروه')->width(6),
                    TextField::make('legal_iban')->label('شماره شبای حقوقی')->placeholder('۲۴ رقم شماره شبا')->rule('iban_digits')->width(12)
                        ->attributes(['maxlength'=>24,'minlength'=>24,'pattern'=>'[0-9]{24}','inputmode'=>'numeric','dir'=>'ltr','autocomplete'=>'off','data-afe-digits-only'=>'1','data-afe-invalid-message'=>'شماره شبا باید دقیقاً ۲۴ رقم باشد.'])
                        ->meta('input_prefix','IR')->meta('display_prefix','IR')->meta('normalize_input_prefix','IR'),
                ]),

                Step::make('officials', '۲. مسئولین')->fields([
                    HtmlBlock::make('<div class="afe-inline-title">مسئول گروه</div>'),
                    TextField::make('leader_full_name')->label('نام و نام خانوادگی مسئول گروه')->required()->width(6),
                    TextField::make('leader_national_id')->label('کد ملی مسئول گروه')->rule('national_id')->required()->width(6)
                        ->attributes(['maxlength'=>10,'minlength'=>10,'pattern'=>'[0-9]{10}','inputmode'=>'numeric','autocomplete'=>'off','data-afe-digits-only'=>'1','data-afe-invalid-message'=>'کد ملی باید دقیقاً ۱۰ رقم باشد.']),
                    DateField::make('leader_birth_date')->jalali()->label('تاریخ تولد مسئول گروه')->required()->width(6),
                    TelField::make('leader_mobile')->label('تلفن همراه مسئول گروه')->rule('mobile')->required()->width(6),
                    FileField::make('leader_photo')->label('عکس پرسنلی مسئول گروه')->required()->multiple(false)->maxFiles(1)->maxSizeMb(5)
                        ->accept(['image/jpeg','image/png','image/webp'])->width(12),
                    HtmlBlock::make('<div class="afe-inline-title">جانشین گروه</div>'),
                    TextField::make('deputy_full_name')->label('نام و نام خانوادگی جانشین')->required()->width(6),
                    TextField::make('deputy_national_id')->label('کد ملی جانشین')->rule('national_id')->required()->width(6)
                        ->attributes(['maxlength'=>10,'minlength'=>10,'pattern'=>'[0-9]{10}','inputmode'=>'numeric','autocomplete'=>'off','data-afe-digits-only'=>'1','data-afe-invalid-message'=>'کد ملی باید دقیقاً ۱۰ رقم باشد.']),
                    DateField::make('deputy_birth_date')->jalali()->label('تاریخ تولد جانشین')->required()->width(6),
                    TelField::make('deputy_mobile')->label('تلفن همراه جانشین')->rule('mobile')->required()->width(6),
                    FileField::make('deputy_photo')->label('عکس پرسنلی جانشین')->multiple(false)->maxFiles(1)->maxSizeMb(5)
                        ->accept(['image/jpeg','image/png','image/webp'])->width(12),
                    RepeaterField::make('central_council')->label('اعضای شورای مرکزی (هسته اصلی)')->required()->min(1)->max(30)->addButton('افزودن عضو شورای مرکزی')
                        ->fields([
                            TextField::make('full_name')->label('نام و نام خانوادگی')->required()->width(6),
                            TelField::make('mobile')->label('شماره تماس')->rule('mobile')->required()->width(6),
                        ]),
                ]),

                Step::make('mission', '۳. مأموریت و هویت')->fields([
                    TextareaField::make('mission_statement')->label('بیانیه مأموریت')->placeholder('ماموریت اصلی گروه، مسئله‌ای که حل می‌کند و جامعه‌ای که برای آن فعالیت می‌کند را توضیح دهید.')->required(),
                    TextareaField::make('vision_goals')->label('اهداف و چشم‌انداز')->placeholder('اهداف میان‌مدت و بلندمدت و چشم‌انداز گروه را توضیح دهید.')->required(),
                ]),

                Step::make('structure', '۴. ساختار')->description('سه عرصه فعالیت را به ترتیب اولویت انتخاب کنید.')->fields([
                    SelectField::make('activity_priority_1')->label('اولویت اول عرصه فعالیت')->options($activityAreas)->required()->uniqueGroup('activity_priorities')->width(4),
                    SelectField::make('activity_priority_2')->label('اولویت دوم عرصه فعالیت')->options($activityAreas)->required()->uniqueGroup('activity_priorities')->width(4),
                    SelectField::make('activity_priority_3')->label('اولویت سوم عرصه فعالیت')->options($activityAreas)->required()->uniqueGroup('activity_priorities')->width(4),
                ]),

                Step::make('members', '۵. اعضا')->fields([
                    RepeaterField::make('members')->label('اعضای گروه')->required()->min(1)->max(300)->addButton('افزودن عضو')
                        ->fields([
                            TextField::make('full_name')->label('نام و نام خانوادگی')->required()->width(6),
                            TelField::make('mobile')->label('شماره تماس')->rule('mobile')->width(6),
                            DateField::make('birth_date')->jalali()->label('تاریخ تولد')->required()->width(4),
                            RadioField::make('gender')->label('جنسیت')->options(['male'=>'مرد','female'=>'زن'])->required()->width(4),
                            SelectField::make('education')->label('تحصیلات')->options($education)->required()->width(4),
                            TextField::make('seminary_education')->label('تحصیلات حوزوی')->placeholder('در صورت وجود')->width(6),
                            TextField::make('skills')->label('مهارت‌ها و تخصص‌ها')->width(6),
                        ]),
                    TextareaField::make('skills_bank')->label('بانک مهارت‌ها و تخصص‌ها')->placeholder('جمع‌بندی ظرفیت‌های تخصصی اعضای گروه را توضیح دهید.'),
                ]),

                Step::make('target', '۶. جامعه هدف')->fields([
                    RepeaterField::make('coverage_areas')->label('مناطق تحت پوشش')->required()->min(1)->max(50)->addButton('افزودن منطقه تحت پوشش')
                        ->meta('legacy_row_map',[
                            'target_province'=>'province',
                            'target_county'=>'county',
                            'target_district'=>'district',
                            'target_area_type'=>'area_type',
                            'target_area_detail'=>'area_detail',
                        ])
                        ->fields([
                            $province('province',true)->width(4),
                            $county('county','province',false)->width(4),
                            $district('district','county',false)->width(4),
                            SelectField::make('area_type')->label('نوع منطقه')->options([
                                'province'=>'استان','county'=>'شهرستان','city'=>'شهر','suburb'=>'حاشیه شهر','village'=>'روستا','regional_project'=>'پروژه خاص منطقه‌ای'
                            ])->required()->width(6),
                            TextField::make('area_detail')->label('نام/توضیح تکمیلی منطقه یا پروژه')->placeholder('در صورت نیاز توضیح تکمیلی وارد کنید')->width(6),
                        ]),
                    TextareaField::make('target_groups')->label('گروه‌های هدف')->placeholder('اقشار و گروه‌های هدف را توضیح دهید.')->required(),
                    SelectField::make('activity_scope')->label('گستره فعالیت')->options([
                        'local'=>'محلی','rural'=>'روستایی','urban'=>'شهری','county'=>'شهرستانی','province'=>'استانی','national'=>'ملی'
                    ])->required(),
                ]),

                Step::make('records', '۷. سوابق')->fields([
                    TextareaField::make('flagship_projects')->label('پروژه‌های شاخص')
                        ->placeholder('برنامه‌های فرهنگی و آموزشی، اشتغال و کسب‌وکار، سلامت، عمرانی و زیرساخت و سایر پروژه‌های شاخص را همراه با نتیجه توضیح دهید.')
                        ->required(),
                ]),

                Step::make('capacity', '۸. ظرفیت عملیاتی')->fields([
                    NumberField::make('max_deployable_personnel')->label('حداکثر نیروی قابل اعزام')->required()->rule('min',0)->width(6),
                    RadioField::make('accommodation_food_capacity')->label('ظرفیت اسکان و تغذیه')->options(['yes'=>'داریم','no'=>'نداریم'])->required()->width(6),
                    TextareaField::make('specialist_teams')->label('تیم‌های تخصصی')->placeholder('تیم‌های تخصصی، تعداد و حوزه توانمندی هر تیم را توضیح دهید.')->required(),
                ]),

                Step::make('equipment', '۹. تجهیزات')->fields([
                    RepeaterField::make('vehicles')->label('خودروها')->max(50)->addButton('افزودن خودرو')
                        ->fields([
                            SelectField::make('class')->label('کلاس خودرو')->options(['light'=>'سبک','heavy'=>'سنگین'])->required()->width(3),
                            TextField::make('type')->label('نوع خودرو')->placeholder('مثلاً وانت، کامیون')->required()->width(3),
                            NumberField::make('count')->label('تعداد')->required()->width(2),
                            TextField::make('usage')->label('کاربرد')->required()->width(4),
                        ]),
                    TextareaField::make('construction_tools')->label('ابزار عمرانی')->placeholder('ابزار و تجهیزات عمرانی قابل استفاده را توضیح دهید.'),
                    TextareaField::make('medical_equipment')->label('تجهیزات پزشکی و امدادی')->placeholder('نوع و تعداد تجهیزات پزشکی و امدادی را توضیح دهید.'),
                    TextareaField::make('media_equipment')->label('تجهیزات رسانه‌ای و ارتباطی')->placeholder('دوربین، سیستم صوتی، رایانه و سایر تجهیزات...'),
                    TextareaField::make('kitchen')->label('آشپزخانه')->placeholder('تجهیزات، فضا، آشپز، نانوا و ظرفیت پخت...'),
                    TextareaField::make('warehouse')->label('انبار')->placeholder('فضا، متراژ، موقعیت و ظرفیت انبار را توضیح دهید.'),
                ]),

                Step::make('finance', '۱۰. منابع مالی')->fields([
                    TextareaField::make('funding_sources')->label('منابع تأمین مالی و حامیان')->required(),
                    TextareaField::make('participation_methods')->label('روش جذب مشارکت')->required(),
                ]),

                Step::make('cooperations', '۱۱. همکاری‌ها')->fields([
                    TextareaField::make('partner_institutions')->label('نهادهای همکار')->placeholder('نام نهادها و نوع همکاری را توضیح دهید.'),
                    TextareaField::make('grassroots_networks')->label('شبکه‌های مردمی')->placeholder('شبکه‌ها، گروه‌ها و حلقه‌های مردمی مرتبط را توضیح دهید.'),
                ]),

                Step::make('media', '۱۲. رسانه و ارتباطات')->fields([
                    TextareaField::make('social_networks')->label('شبکه‌های اجتماعی')->placeholder('نام شبکه و نشانی/شناسه صفحه را وارد کنید.')->width(12),
                    TextField::make('media_manager')->label('مسئول رسانه')->width(6),
                    TelField::make('media_manager_mobile')->label('شماره تماس مسئول رسانه')->rule('mobile')->width(6),
                    UrlField::make('website')->label('وب‌سایت')->placeholder('https://example.ir')->width(6),
                    TextareaField::make('messenger_ids')->label('آی‌دی پیام‌رسان‌ها')->placeholder('ایتا، بله، روبیکا، تلگرام و ...')->width(6),
                ]),

                Step::make('documents', '۱۳. مستندات')->fields([
                    FileField::make('licenses')->label('مجوزها')->multiple(true)->maxFiles(10)->maxSizeMb(10)
                        ->accept(['image/jpeg','image/png','image/webp','application/pdf'])->required(),
                    FileField::make('performance_report')->label('گزارش عملکرد')->description('فایل گزارش عملکرد ترجیحاً در قالب PDF بارگذاری شود.')
                        ->multiple(true)->maxFiles(5)->maxSizeMb(20)->accept(['application/pdf','image/jpeg','image/png','image/webp','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document']),
                    FileField::make('group_resume')->label('رزومه گروه')->multiple(true)->maxFiles(5)->maxSizeMb(20)
                        ->accept(['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document']),
                    HtmlBlock::make('<div class="afe-final-note">با ثبت نهایی، اطلاعات برای بررسی کارشناسان بنیاد علوی ارسال می‌شود. کد رهگیری و لینک ویرایش را نگهداری کنید.</div>'),
                ]),
            ])
            ->workflow([
                'draft'=>'پیش‌نویس',
                'new'=>'جدید',
                'review'=>'در حال بررسی',
                'revision'=>'نیاز به اصلاح',
                'approved'=>'تأیید شده',
                'rejected'=>'رد شده',
            ])
            ->settings([
                'wizard'=>true,
                'header_slogan'=>'هر گروه یک نقطه‌ی آغاز و هر حرکت یک قدم به سوی آینده. (خانه نوآوری جهاد)',
                'save_draft'=>true,
                'show_progress'=>true,
                'editing_enabled'=>true,
                'editing_modes'=>['wordpress','link','tracking'],
                'preview_enabled'=>true,
                'preview_title'=>'پیش‌نمایش اطلاعات ارسالی',
                'preview_description'=>'پیش از ثبت نهایی، اطلاعات واردشده را بررسی کنید. برای اصلاح هر بخش به مرحله مربوطه بازگردید.',
                'preview_template'=>'<div class="afe-preview-jihadi"><div class="afe-preview-jihadi__head"><span>بازبینی نهایی</span><h3>{{preview_title}}</h3><p>{{preview_description}}</p></div>{{preview_fields}}</div>',
                'lock_after_submit'=>true,
                'show_edit_request_button'=>true,
                'lock_warning'=>'پس از ثبت نهایی این فرم قفل می‌شود و امکان ویرایش مستقیم وجود ندارد. در صورت نیاز باید درخواست ویرایش ارسال کنید و پس از تأیید مدیر، فرم دوباره برای ویرایش باز می‌شود.',
                'captcha'=>'custom',
                'rate_limit'=>20,
                'storage'=>'shared',
            ])
            ->actions([
                [
                    'action_key'=>'notify_admin_email_on_submit',
                    'type'=>'email',
                    'on'=>['submission.submitted'],
                    'execution_policy'=>'once_per_submission',
                    'on_error'=>'continue',
                    'config'=>[
                        'to'=>get_option('admin_email'),
                        'subject'=>'ثبت گروه مردمی/جهادی جدید',
                        'body'=>'ثبت جدیدی در فرم گروه‌های مردمی و جهادی ایجاد شد. کد رهگیری: {{tracking_code}}',
                    ],
                ],
            ]);
    }
}
