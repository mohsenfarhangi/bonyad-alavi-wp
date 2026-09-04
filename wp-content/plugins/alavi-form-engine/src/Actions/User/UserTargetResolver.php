<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\User;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use RuntimeException;

final class UserTargetResolver
{
    public function __construct(private readonly TokenResolver $tokens) {}

    public function resolve(ActionContext $context, array $config): int
    {
        $source = sanitize_key((string)($config['user_source'] ?? 'runtime'));
        $id = match ($source) {
            'runtime' => (int)($context->runtime?->get('user_id', 0) ?? 0),
            'submission' => (int)($context->submission['user_id'] ?? 0),
            'current' => get_current_user_id(),
            'manual' => (int)$this->tokens->resolve((string)($config['user_id'] ?? ''), $context),
            default => 0,
        };
        if ($id <= 0 || !get_userdata($id)) throw new RuntimeException('کاربر هدف برای Action پیدا نشد.');
        return $id;
    }
}
