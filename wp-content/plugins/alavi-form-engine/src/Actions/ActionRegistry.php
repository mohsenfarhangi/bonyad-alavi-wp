<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use BonyadAlavi\FormEngine\Actions\Sms\SmsAction;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderInterface;

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

    public function registerSms(TokenResolver $tokens, SmsProviderInterface $provider): void
    {
        $this->register(new ActionDefinition(
            'sms',
            'ارسال پیامک',
            'ارسال پیامک آزاد یا Pattern با یک گیرنده و پشتیبانی از Tokenهای فرم.',
            'communication',
            [
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
                'mode'=>['type'=>'select','label'=>'روش ارسال','required'=>true,'default'=>'free','options'=>['free'=>'ارسال آزاد','pattern'=>'Pattern']],
                'sender'=>['type'=>'text','label'=>'شماره فرستنده (اختیاری؛ جایگزین تنظیم سراسری)','required'=>false,'tokens'=>true,'show_when'=>['mode'=>'free']],
                'body'=>['type'=>'textarea','label'=>'متن پیامک','required'=>false,'tokens'=>true,'show_when'=>['mode'=>'free']],
                'pattern_code'=>['type'=>'text','label'=>'Pattern / Body ID','required'=>false,'tokens'=>true,'show_when'=>['mode'=>'pattern']],
                'pattern_values'=>['type'=>'repeater_text','label'=>'پارامترهای Pattern به ترتیب','required'=>false,'tokens'=>true,'show_when'=>['mode'=>'pattern']],
            ]
        ), new SmsAction($provider, $tokens));
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
