<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\User;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionInterface;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use RuntimeException;

final class UpdateUserAction implements ActionInterface
{
    public function __construct(
        private readonly UserTargetResolver $users,
        private readonly TokenResolver $tokens
    ) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        $userId = $this->users->resolve($context, $config);
        $userdata = ['ID'=>$userId];
        foreach (['user_email','display_name','first_name','last_name'] as $key) {
            $value = trim($this->tokens->resolve((string)($config[$key] ?? ''), $context));
            if ($key === 'user_email' && $value !== '') $value = sanitize_email($value);
            if ($value !== '') $userdata[$key] = $value;
        }
        if (count($userdata) > 1) {
            $result = wp_update_user($userdata);
            if (is_wp_error($result)) throw new RuntimeException('بروزرسانی کاربر ناموفق بود: '.$result->get_error_message());
        }
        $context->runtime?->set('user_id', $userId);
    }
}
