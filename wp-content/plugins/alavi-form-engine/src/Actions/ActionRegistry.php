<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use BonyadAlavi\FormEngine\Actions\Sms\SmsAction;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderInterface;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderRegistry;
use BonyadAlavi\FormEngine\Actions\User\UserTargetResolver;
use BonyadAlavi\FormEngine\Actions\User\UserActionGuard;
use BonyadAlavi\FormEngine\Actions\User\CreateUserAction;
use BonyadAlavi\FormEngine\Actions\User\LoginUserAction;
use BonyadAlavi\FormEngine\Actions\User\UpdateUserAction;
use BonyadAlavi\FormEngine\Actions\User\AssignRoleAction;
use BonyadAlavi\FormEngine\Actions\User\UpdateUserMetaAction;
use BonyadAlavi\FormEngine\Actions\Pdf\PdfGenerator;
use BonyadAlavi\FormEngine\Actions\Pdf\GeneratePdfAction;
use BonyadAlavi\FormEngine\Actions\Pdf\EmailPdfAction;
use BonyadAlavi\FormEngine\Actions\Post\SavePostAction;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;

final class ActionRegistry
{
    /** @var array<string, ActionDefinition> */
    private array $definitions = [];

    /** @var array<string, ActionInterface|callable> */
    private array $handlers = [];

    public function register(ActionDefinition $definition, ActionInterface|callable $handler): void
    {
        $this->definitions[$definition->key] = $definition;
        $this->handlers[$definition->key] = $handler;
    }

    public function registerHandler(string $key, ActionInterface|callable $handler, ?ActionDefinition $definition = null): void
    {
        if ($definition === null) {
            $definition = $this->definitions[$key] ?? new ActionDefinition(
                $key,
                $key,
                'اکشن ثبت‌شده توسط توسعه‌دهنده.',
                'developer'
            );
        }
        $this->register($definition, $handler);
    }

    public function registerCore(TokenResolver $tokens): void
    {
        $this->register(new ActionDefinition(
            'email',
            'ارسال ایمیل',
            'ارسال ایمیل با پشتیبانی از Tokenهای فرم و Submission.',
            'communication',
            [
                'to' => ['type'=>'text','label'=>'گیرنده','required'=>true,'tokens'=>true],
                'subject' => ['type'=>'text','label'=>'موضوع','required'=>true,'tokens'=>true],
                'body' => ['type'=>'textarea','label'=>'متن ایمیل','required'=>true,'tokens'=>true],
                'headers' => ['type'=>'key_value','label'=>'Headerهای سفارشی','required'=>false,'tokens'=>true],
            ]
        ), new EmailAction($tokens));

        $this->register(new ActionDefinition(
            'webhook',
            'Webhook',
            'ارسال درخواست HTTP به سرویس خارجی با Payload قابل استفاده از Tokenها.',
            'integration',
            [
                'url' => ['type'=>'url','label'=>'URL','required'=>true,'tokens'=>true],
                'method' => ['type'=>'select','label'=>'Method','required'=>true,'default'=>'POST','options'=>['POST'=>'POST','PUT'=>'PUT','PATCH'=>'PATCH']],
                'headers' => ['type'=>'key_value','label'=>'Headerها','required'=>false,'tokens'=>true],
                'payload' => ['type'=>'json','label'=>'Payload','required'=>false,'tokens'=>true],
                'body' => ['type'=>'textarea','label'=>'Body خام','required'=>false,'tokens'=>true],
            ]
        ), new WebhookAction($tokens));
    }

    public function registerExtended(TokenResolver $tokens, SubmissionRepository $submissions, PdfGenerator $pdf): void
    {
        $userTargets = new UserTargetResolver($tokens);
        $userGuard = new UserActionGuard();

        $this->register(new ActionDefinition(
            'redirect',
            'هدایت کاربر',
            'URL پس از اجرای همه Actionهای سمت سرور در پاسخ Ajax برگردانده می‌شود؛ اولین Redirect مؤثر برنده است.',
            'response',
            [
                'url'=>['type'=>'text','label'=>'URL مقصد','required'=>true,'tokens'=>true],
                'allow_external'=>['type'=>'boolean','label'=>'اجازه Redirect به دامنه خارجی','required'=>false,'default'=>false,'capability'=>'afe_manage_settings'],
            ],
            true,
            true,
            false
        ), new RedirectAction($tokens));

        $this->register(new ActionDefinition(
            'create_user',
            'ایجاد کاربر وردپرس',
            'ایجاد کاربر با mapping داده‌های فرم؛ نقش کاربر در Action جداگانه Assign Role تعیین می‌شود.',
            'wordpress_user',
            [
                'user_login'=>['type'=>'text','label'=>'user_login','required'=>true,'tokens'=>true],
                'user_email'=>['type'=>'text','label'=>'user_email','required'=>false,'tokens'=>true],
                'display_name'=>['type'=>'text','label'=>'display_name','required'=>false,'tokens'=>true],
                'first_name'=>['type'=>'text','label'=>'first_name','required'=>false,'tokens'=>true],
                'last_name'=>['type'=>'text','label'=>'last_name','required'=>false,'tokens'=>true],
                'password'=>['type'=>'text','label'=>'رمز عبور (اختیاری؛ خالی = تولید امن)','required'=>false,'tokens'=>true],
                'user_meta'=>['type'=>'key_value','label'=>'User Meta','required'=>false,'tokens'=>true],
                'on_existing'=>['type'=>'select','label'=>'اگر username/email موجود بود','required'=>true,'default'=>'fail','options'=>[
                    'fail'=>'Fail action','use'=>'Use existing user','update'=>'Update existing user','skip'=>'Skip create / use existing target',
                ]],
                'allow_privileged_existing'=>['type'=>'boolean','label'=>'اجازه استفاده/ویرایش کاربر مدیریتی موجود','required'=>false,'default'=>false,'capability'=>'afe_manage_settings'],
            ]
        ), new CreateUserAction($tokens, $userGuard));

        $userSourceSchema = [
            'user_source'=>['type'=>'select','label'=>'کاربر هدف','required'=>true,'default'=>'runtime','options'=>[
                'runtime'=>'کاربر ساخته/انتخاب‌شده در زنجیره','submission'=>'کاربر مالک Submission','current'=>'کاربر فعلی وردپرس','manual'=>'User ID / Token',
            ]],
            'user_id'=>['type'=>'text','label'=>'User ID / Token','required'=>false,'tokens'=>true,'show_when'=>['user_source'=>'manual']],
            'allow_privileged_user'=>['type'=>'boolean','label'=>'اجازه عملیات روی کاربر دارای دسترسی مدیریتی','required'=>false,'default'=>false,'capability'=>'afe_manage_settings'],
        ];

        $this->register(new ActionDefinition(
            'login_user',
            'ورود کاربر',
            'Session وردپرس را برای کاربر هدف ایجاد می‌کند. برای جلوگیری از تغییر Session مدیر، Retry مدیریتی ندارد.',
            'wordpress_user',
            $userSourceSchema + [
                'remember'=>['type'=>'boolean','label'=>'مرا به خاطر بسپار','required'=>false,'default'=>false],
            ],
            true,
            true,
            false
        ), new LoginUserAction($userTargets, $userGuard));

        $this->register(new ActionDefinition(
            'update_user',
            'بروزرسانی کاربر',
            'فیلدهای اصلی پروفایل کاربر هدف را با Tokenها بروزرسانی می‌کند.',
            'wordpress_user',
            $userSourceSchema + [
                'user_email'=>['type'=>'text','label'=>'user_email','required'=>false,'tokens'=>true],
                'display_name'=>['type'=>'text','label'=>'display_name','required'=>false,'tokens'=>true],
                'first_name'=>['type'=>'text','label'=>'first_name','required'=>false,'tokens'=>true],
                'last_name'=>['type'=>'text','label'=>'last_name','required'=>false,'tokens'=>true],
            ]
        ), new UpdateUserAction($userTargets, $tokens, $userGuard));

        $this->register(new ActionDefinition(
            'assign_role',
            'اختصاص نقش کاربر',
            'نقش WordPress کاربر هدف را تغییر می‌دهد. نقش administrator نیازمند مجوز صریح تنظیمات است.',
            'wordpress_user',
            $userSourceSchema + [
                'role'=>['type'=>'role_select','label'=>'نقش','required'=>true],
                'allow_privileged_role'=>['type'=>'boolean','label'=>'اجازه اختصاص نقش مدیریتی (Administrator / manage_options)','required'=>false,'default'=>false,'capability'=>'afe_manage_settings'],
            ]
        ), new AssignRoleAction($userTargets, $userGuard));

        $this->register(new ActionDefinition(
            'update_user_meta',
            'بروزرسانی User Meta',
            'یک یا چند meta key را برای کاربر هدف بروزرسانی می‌کند.',
            'wordpress_user',
            $userSourceSchema + [
                'meta'=>['type'=>'key_value','label'=>'User Meta','required'=>true,'tokens'=>true],
            ]
        ), new UpdateUserMetaAction($userTargets, $tokens, $userGuard));

        $this->register(new ActionDefinition(
            'change_status',
            'تغییر وضعیت Submission',
            'وضعیت Submission را به یکی از وضعیت‌های Workflow فرم تغییر می‌دهد و Event تغییر وضعیت را ایجاد می‌کند.',
            'submission',
            [
                'status'=>['type'=>'workflow_select','label'=>'وضعیت مقصد','required'=>true],
            ]
        ), new ChangeStatusAction($submissions));

        $this->register(new ActionDefinition(
            'add_note',
            'افزودن یادداشت داخلی',
            'یک یادداشت مدیریتی روی Submission ثبت می‌کند.',
            'submission',
            [
                'note'=>['type'=>'textarea','label'=>'متن یادداشت','required'=>true,'tokens'=>true],
                'user_id'=>['type'=>'user','label'=>'نویسنده یادداشت (اختیاری)','required'=>false,'default'=>0],
            ]
        ), new AddNoteAction($submissions, $tokens));

        $this->register(new ActionDefinition(
            'generate_pdf',
            'تولید PDF',
            'PDF سمت سرور با قالب خروجی همان فرم و پکیج tc-lib-pdf تولید می‌کند و مسیر آن را برای Actionهای بعدی همان زنجیره در Runtime قرار می‌دهد.',
            'document',
            [
                'filename'=>['type'=>'text','label'=>'نام فایل (اختیاری)','required'=>false,'tokens'=>true],
            ]
        ), new GeneratePdfAction($pdf, $tokens));

        $this->register(new ActionDefinition(
            'email_pdf',
            'ارسال PDF با Email',
            'PDF تولیدشده در Runtime را استفاده می‌کند یا در صورت نیاز PDF جدید می‌سازد و به Email پیوست می‌کند.',
            'document',
            [
                'to'=>['type'=>'text','label'=>'گیرنده','required'=>true,'tokens'=>true],
                'subject'=>['type'=>'text','label'=>'موضوع','required'=>true,'tokens'=>true],
                'body'=>['type'=>'textarea','label'=>'متن Email','required'=>true,'tokens'=>true],
                'reuse_generated'=>['type'=>'boolean','label'=>'در صورت وجود از PDF تولیدشده قبلی در همین زنجیره استفاده شود','required'=>false,'default'=>true],
                'filename'=>['type'=>'text','label'=>'نام فایل در صورت تولید جدید','required'=>false,'tokens'=>true],
            ]
        ), new EmailPdfAction($pdf, $tokens));

        $this->register(new ActionDefinition(
            'save_post',
            'ایجاد/بروزرسانی Post/CPT',
            'یک نوشته یا CPT را ایجاد، بروزرسانی یا Upsert می‌کند و post_id را در Runtime قرار می‌دهد.',
            'wordpress_content',
            [
                'operation'=>['type'=>'select','label'=>'عملیات','required'=>true,'default'=>'create','options'=>['create'=>'ایجاد','update'=>'بروزرسانی','upsert'=>'Upsert']],
                'post_type'=>['type'=>'post_type_select','label'=>'Post Type','required'=>true,'default'=>'post'],
                'post_id'=>['type'=>'text','label'=>'post_id برای Update/Upsert','required'=>false,'tokens'=>true],
                'post_status'=>['type'=>'post_status_select','label'=>'وضعیت نوشته','required'=>true,'default'=>'draft'],
                'allow_publish'=>['type'=>'boolean','label'=>'اجازه انتشار مستقیم (publish)','required'=>false,'default'=>false,'capability'=>'afe_manage_settings'],
                'post_title'=>['type'=>'text','label'=>'عنوان','required'=>false,'tokens'=>true],
                'post_content'=>['type'=>'textarea','label'=>'محتوا','required'=>false,'tokens'=>true],
                'post_excerpt'=>['type'=>'textarea','label'=>'خلاصه','required'=>false,'tokens'=>true],
                'post_name'=>['type'=>'text','label'=>'Slug','required'=>false,'tokens'=>true],
                'author_source'=>['type'=>'select','label'=>'نویسنده','required'=>false,'default'=>'none','options'=>[
                    'none'=>'بدون Override','runtime'=>'کاربر Runtime','submission'=>'مالک Submission','current'=>'کاربر فعلی','manual'=>'User ID / Token',
                ]],
                'author_user_id'=>['type'=>'text','label'=>'Author User ID / Token','required'=>false,'tokens'=>true,'show_when'=>['author_source'=>'manual']],
                'post_meta'=>['type'=>'key_value','label'=>'Post Meta','required'=>false,'tokens'=>true],
            ]
        ), new SavePostAction($tokens));
    }

    public function registerSms(TokenResolver $tokens, SmsProviderInterface|SmsProviderRegistry $provider): void
    {
        $providers = $provider instanceof SmsProviderRegistry ? $provider : SmsProviderRegistry::single($provider);
        $this->register(new ActionDefinition(
            'sms',
            'ارسال پیامک',
            'ارسال پیامک آزاد یا Pattern با یک گیرنده، Provider پیش‌فرض سراسری یا Override اختصاصی و پشتیبانی از Tokenهای فرم.',
            'communication',
            [
                'provider'=>[
                    'type'=>'select','label'=>'Provider / درگاه ارسال','required'=>true,'default'=>SmsProviderRegistry::USE_DEFAULT,
                    'options'=>$providers->actionOptions(),
                    'disabled_options'=>$providers->unavailableKeys(),
                    'ui_role'=>'sms_provider',
                    'provider_modes'=>$providers->actionModeMap(),
                    'default_provider'=>$providers->defaultProvider(),
                    'description'=>'پیش‌فرض سراسری از تنظیمات AFE خوانده می‌شود. Persian WooCommerce SMS در صورت فعال بودن، Credential و Gateway خودش را استفاده می‌کند.',
                ],
                'recipient_source'=>[
                    'type'=>'select','label'=>'نوع گیرنده','required'=>true,'default'=>'field',
                    'options'=>[
                        'field'=>'فیلد فرم',
                        'manual'=>'شماره دستی',
                        'user'=>'کاربر/مدیر وردپرس',
                        'token'=>'Token داینامیک',
                    ],
                ],
                'recipient_field'=>['type'=>'field_select','label'=>'فیلد شماره موبایل','required'=>false,'show_when'=>['recipient_source'=>'field']],
                'recipient_value'=>['type'=>'text','label'=>'شماره دستی','required'=>false,'tokens'=>true,'show_when'=>['recipient_source'=>'manual']],
                'recipient_token'=>['type'=>'text','label'=>'Token گیرنده','required'=>false,'tokens'=>true,'show_when'=>['recipient_source'=>'token']],
                'recipient_user_id'=>['type'=>'user','label'=>'کاربر وردپرس','required'=>false,'tokens'=>true,'show_when'=>['recipient_source'=>'user']],
                'recipient_user_meta'=>['type'=>'text','label'=>'کلید متای شماره موبایل کاربر','required'=>false,'default'=>'billing_phone','show_when'=>['recipient_source'=>'user']],
                'mode'=>['type'=>'select','label'=>'روش ارسال','required'=>true,'default'=>'free','options'=>['free'=>'ارسال آزاد','pattern'=>'Pattern'],'ui_role'=>'sms_mode','description'=>'Pattern فقط برای Provider/Gatewayهایی فعال است که Integration آن را پشتیبانی کند.'],
                'sender'=>['type'=>'text','label'=>'شماره فرستنده (اختیاری)','required'=>false,'tokens'=>true,'show_when'=>['mode'=>'free'],'description'=>'فقط برای Providerهایی که Sender را از Action می‌پذیرند (از جمله ملی پیامک داخلی AFE). Persian WooCommerce SMS از Sender تنظیم‌شده در همان افزونه استفاده می‌کند.'],
                'body'=>['type'=>'textarea','label'=>'متن پیامک','required'=>false,'tokens'=>true,'show_when'=>['mode'=>'free']],
                'pattern_code'=>['type'=>'text','label'=>'Pattern / Body ID','required'=>false,'tokens'=>true,'show_when'=>['mode'=>'pattern']],
                'pattern_values'=>['type'=>'repeater_text','label'=>'پارامترهای Pattern به ترتیب','required'=>false,'tokens'=>true,'show_when'=>['mode'=>'pattern']],
            ]
        ), new SmsAction($providers, $tokens));
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key], $this->handlers[$key]);
    }

    public function get(string $key): ?ActionDefinition
    {
        return $this->definitions[$key] ?? null;
    }

    public function handler(string $key): ActionInterface|callable|null
    {
        return $this->handlers[$key] ?? null;
    }

    /** @return array<string, ActionDefinition> */
    public function all(): array
    {
        return $this->definitions;
    }

    /** @return array<string, string> */
    public function labels(): array
    {
        $labels = [];
        foreach ($this->definitions as $key => $definition) {
            $labels[$key] = $definition->label;
        }
        return $labels;
    }
}
