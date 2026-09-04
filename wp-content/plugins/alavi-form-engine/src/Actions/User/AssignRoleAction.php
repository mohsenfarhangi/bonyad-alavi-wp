<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\User;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionInterface;
use RuntimeException;

final class AssignRoleAction implements ActionInterface
{
    public function __construct(
        private readonly UserTargetResolver $users,
        private readonly UserActionGuard $guard
    ) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        $userId = $this->users->resolve($context, $config);
        $this->guard->assertTargetAllowed($userId, $config, 'تغییر نقش کاربر');
        $role = sanitize_key((string)($config['role'] ?? ''));
        $roles = wp_roles()->roles;
        if ($role === '' || !isset($roles[$role])) throw new RuntimeException('نقش وردپرس معتبر نیست.');
        $roleCaps = is_array($roles[$role]['capabilities'] ?? null) ? $roles[$role]['capabilities'] : [];
        $privilegedRole = $role === 'administrator' || !empty($roleCaps['manage_options']);
        if ($privilegedRole && empty($config['allow_privileged_role'])) {
            throw new RuntimeException('اختصاص نقش مدیریتی نیاز به مجوز صریح دارد.');
        }
        $user = get_userdata($userId);
        if (!$user) throw new RuntimeException('کاربر هدف پیدا نشد.');
        $user->set_role($role);
        $context->runtime?->set('user_id', $userId);
    }
}
