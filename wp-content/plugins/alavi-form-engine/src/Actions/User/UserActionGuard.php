<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\User;

use RuntimeException;

/**
 * Runtime guard for WordPress-user mutation actions.
 *
 * Form configuration can be delegated per form, so user actions must not be
 * able to mutate/login privileged accounts or write capability/session meta
 * unless a site administrator explicitly opted into the privileged target.
 */
final class UserActionGuard
{
    public function assertTargetAllowed(int $userId, array $config, string $operation = 'تغییر کاربر'): void
    {
        if ($userId <= 0 || !$this->isPrivileged($userId)) return;
        if (!empty($config['allow_privileged_user']) || !empty($config['allow_privileged_existing'])) return;

        throw new RuntimeException($operation . ' برای کاربر دارای دسترسی مدیریتی نیازمند مجوز صریح تنظیمات است.');
    }

    public function assertMetaKeyAllowed(string $key): void
    {
        $key = sanitize_key($key);
        if ($key === '') throw new RuntimeException('کلید User Meta معتبر نیست.');

        if ($this->isProtectedMetaKey($key)) {
            throw new RuntimeException('نوشتن User Meta امنیتی وردپرس از Action Builder مجاز نیست: ' . $key);
        }
    }

    public function isProtectedMetaKey(string $key): bool
    {
        $key = sanitize_key($key);
        if ($key === '') return true;

        if (in_array($key, ['session_tokens', '_application_passwords'], true)) return true;
        if (preg_match('/(?:^|_)capabilities$/', $key) === 1) return true;
        if (preg_match('/(?:^|_)user_level$/', $key) === 1) return true;

        return false;
    }

    private function isPrivileged(int $userId): bool
    {
        $user = get_userdata($userId);
        if (!$user) return false;

        $roles = is_array($user->roles ?? null) ? $user->roles : [];
        if (in_array('administrator', $roles, true)) return true;

        return function_exists('user_can') && user_can($userId, 'manage_options');
    }
}
