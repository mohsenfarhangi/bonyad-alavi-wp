<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\User;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionInterface;
use RuntimeException;

final class LoginUserAction implements ActionInterface
{
    public function __construct(private readonly UserTargetResolver $users) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        if ($context->runtime?->isRetry()) {
            throw new RuntimeException('Login User از صفحه مدیریت Retry نمی‌شود تا Session مدیر تغییر نکند.');
        }
        $userId = $this->users->resolve($context, $config);
        wp_set_current_user($userId);
        wp_set_auth_cookie($userId, !empty($config['remember']), is_ssl());
        $context->runtime?->set('user_id', $userId);
    }
}
