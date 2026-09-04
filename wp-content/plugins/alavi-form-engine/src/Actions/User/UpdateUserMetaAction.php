<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\User;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionInterface;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use RuntimeException;

final class UpdateUserMetaAction implements ActionInterface
{
    public function __construct(
        private readonly UserTargetResolver $users,
        private readonly TokenResolver $tokens
    ) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        $userId = $this->users->resolve($context, $config);
        $meta = $this->tokens->resolveValue((array)($config['meta'] ?? []), $context);
        if (!is_array($meta) || $meta === []) throw new RuntimeException('هیچ User Meta برای بروزرسانی تعریف نشده است.');
        foreach ($meta as $key=>$value) {
            $key = sanitize_key((string)$key);
            if ($key === '') continue;
            update_user_meta($userId, $key, is_scalar($value) || $value === null ? (string)$value : $value);
        }
        $context->runtime?->set('user_id', $userId);
    }
}
