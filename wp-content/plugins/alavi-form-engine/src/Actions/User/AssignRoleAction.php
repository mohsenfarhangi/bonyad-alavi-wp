<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\User;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionInterface;
use RuntimeException;

final class AssignRoleAction implements ActionInterface
{
    public function __construct(private readonly UserTargetResolver $users) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        $userId = $this->users->resolve($context, $config);
        $role = sanitize_key((string)($config['role'] ?? ''));
        $roles = wp_roles()->roles;
        if ($role === '' || !isset($roles[$role])) throw new RuntimeException('نقش وردپرس معتبر نیست.');
        if ($role === 'administrator' && empty($config['allow_privileged_role'])) {
            throw new RuntimeException('اختصاص نقش administrator نیاز به مجوز صریح دارد.');
        }
        $user = get_userdata($userId);
        if (!$user) throw new RuntimeException('کاربر هدف پیدا نشد.');
        $user->set_role($role);
        $context->runtime?->set('user_id', $userId);
    }
}
