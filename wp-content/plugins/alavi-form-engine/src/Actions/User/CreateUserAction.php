<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\User;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionInterface;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use RuntimeException;

final class CreateUserAction implements ActionInterface
{
    public function __construct(private readonly TokenResolver $tokens) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        if (!$context->runtime) throw new RuntimeException('Action runtime is not available.');
        $login = sanitize_user($this->tokens->resolve((string)($config['user_login'] ?? ''), $context), true);
        $email = sanitize_email($this->tokens->resolve((string)($config['user_email'] ?? ''), $context));
        if ($login === '') throw new RuntimeException('user_login پس از Resolve کردن Tokenها خالی یا نامعتبر است.');

        $loginId = username_exists($login);
        $emailId = $email !== '' ? email_exists($email) : false;
        if ($loginId && $emailId && (int)$loginId !== (int)$emailId) {
            throw new RuntimeException('نام کاربری و ایمیل به دو کاربر متفاوت تعلق دارند.');
        }
        $existingId = (int)($loginId ?: $emailId ?: 0);
        $behavior = sanitize_key((string)($config['on_existing'] ?? 'fail'));
        if (!in_array($behavior, ['fail','use','update','skip'], true)) $behavior = 'fail';

        $userdata = [
            'user_login'=>$login,
            'user_email'=>$email,
            'display_name'=>$this->tokens->resolve((string)($config['display_name'] ?? ''), $context),
            'first_name'=>$this->tokens->resolve((string)($config['first_name'] ?? ''), $context),
            'last_name'=>$this->tokens->resolve((string)($config['last_name'] ?? ''), $context),
        ];
        $userdata = array_filter($userdata, static fn($value)=>$value !== '');

        if ($existingId > 0) {
            if ($behavior === 'fail') throw new RuntimeException('کاربری با این نام کاربری یا ایمیل از قبل وجود دارد.');
            if ($behavior === 'update') {
                $userdata['ID'] = $existingId;
                $result = wp_update_user($userdata);
                if (is_wp_error($result)) throw new RuntimeException('بروزرسانی کاربر موجود ناموفق بود: '.$result->get_error_message());
                $this->updateMeta($existingId, (array)($config['user_meta'] ?? []), $context);
            }
            $context->runtime->set('user_id', $existingId);
            $context->runtime->set('created_user_id', 0);
            $context->runtime->set('user_was_existing', true);
            return;
        }

        $password = $this->tokens->resolve((string)($config['password'] ?? ''), $context);
        if ($password === '') $password = wp_generate_password(24, true, true);
        $userdata['user_pass'] = $password;
        $result = wp_insert_user($userdata);
        if (is_wp_error($result)) throw new RuntimeException('ایجاد کاربر ناموفق بود: '.$result->get_error_message());
        $userId = (int)$result;
        $this->updateMeta($userId, (array)($config['user_meta'] ?? []), $context);
        $context->runtime->set('user_id', $userId);
        $context->runtime->set('created_user_id', $userId);
        $context->runtime->set('user_was_existing', false);
    }

    private function updateMeta(int $userId, array $meta, ActionContext $context): void
    {
        $resolved = $this->tokens->resolveValue($meta, $context);
        foreach ((array)$resolved as $key=>$value) {
            $key = sanitize_key((string)$key);
            if ($key === '') continue;
            update_user_meta($userId, $key, is_scalar($value) || $value === null ? (string)$value : $value);
        }
    }
}
