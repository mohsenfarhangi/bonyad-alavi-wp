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
        private readonly TokenResolver $tokens,
        private readonly UserActionGuard $guard
    ) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        $userId = $this->users->resolve($context, $config);
        $this->guard->assertTargetAllowed($userId, $config, 'بروزرسانی User Meta');
        $meta = $this->tokens->resolveValue((array)($config['meta'] ?? []), $context);
        if (!is_array($meta) || $meta === []) throw new RuntimeException('هیچ User Meta برای بروزرسانی تعریف نشده است.');

        // Validate the complete mapping before the first write so a protected
        // key cannot leave earlier keys partially updated.
        $clean = [];
        foreach ($meta as $key=>$value) {
            $key = sanitize_key((string)$key);
            if ($key === '') continue;
            $this->guard->assertMetaKeyAllowed($key);
            $clean[$key] = $value;
        }
        if ($clean === []) throw new RuntimeException('هیچ User Meta معتبر برای بروزرسانی تعریف نشده است.');

        foreach ($clean as $key=>$value) {
            update_user_meta($userId, $key, is_scalar($value) || $value === null ? (string)$value : $value);
        }
        $context->runtime?->set('user_id', $userId);
    }
}
