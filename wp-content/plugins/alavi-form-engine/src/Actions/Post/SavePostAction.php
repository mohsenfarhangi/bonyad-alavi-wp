<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Post;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionInterface;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use RuntimeException;

final class SavePostAction implements ActionInterface
{
    public function __construct(private readonly TokenResolver $tokens) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        if (!$context->runtime) throw new RuntimeException('Action runtime is not available.');

        $operation = sanitize_key((string)($config['operation'] ?? 'create'));
        if (!in_array($operation, ['create','update','upsert'], true)) $operation = 'create';
        $postType = sanitize_key((string)($config['post_type'] ?? 'post'));
        $object = get_post_type_object($postType);
        if (!$object || in_array($postType, ['attachment','revision','nav_menu_item'], true)) {
            throw new RuntimeException('Post Type انتخاب‌شده معتبر نیست.');
        }

        $postId = (int)$this->tokens->resolve((string)($config['post_id'] ?? ''), $context);
        if ($postId <= 0) $postId = (int)$context->runtime->get('post_id', 0);
        if ($operation === 'update' && $postId <= 0) throw new RuntimeException('برای Update باید post_id معتبر مشخص شود.');
        if ($operation === 'upsert') $operation = $postId > 0 ? 'update' : 'create';

        $status = sanitize_key((string)($config['post_status'] ?? 'draft'));
        if (!in_array($status, ['draft','pending','private','publish'], true)) $status = 'draft';
        if ($status === 'publish' && empty($config['allow_publish'])) {
            throw new RuntimeException('Publish مستقیم برای این Action مجاز نشده است.');
        }

        $postarr = [
            'post_type'=>$postType,
            'post_status'=>$status,
            'post_title'=>$this->tokens->resolve((string)($config['post_title'] ?? ''), $context),
            'post_content'=>$this->tokens->resolve((string)($config['post_content'] ?? ''), $context),
            'post_excerpt'=>$this->tokens->resolve((string)($config['post_excerpt'] ?? ''), $context),
        ];
        $slug = sanitize_title($this->tokens->resolve((string)($config['post_name'] ?? ''), $context));
        if ($slug !== '') $postarr['post_name'] = $slug;
        $author = $this->resolveAuthor($context, $config);
        if ($author > 0) $postarr['post_author'] = $author;

        if ($operation === 'update') {
            $existing = get_post($postId);
            if (!$existing) throw new RuntimeException('Post هدف برای Update پیدا نشد.');
            $postarr['ID'] = $postId;
        }

        $result = wp_insert_post($postarr, true);
        if (is_wp_error($result)) throw new RuntimeException('ذخیره Post/CPT ناموفق بود: '.$result->get_error_message());
        $postId = (int)$result;

        $meta = $this->tokens->resolveValue((array)($config['post_meta'] ?? []), $context);
        foreach ((array)$meta as $key=>$value) {
            $key = sanitize_key((string)$key);
            if ($key === '') continue;
            update_post_meta($postId, $key, is_scalar($value) || $value === null ? (string)$value : $value);
        }

        $context->runtime->set('post_id', $postId);
        $context->runtime->set('post_url', (string)get_permalink($postId));
    }

    private function resolveAuthor(ActionContext $context, array $config): int
    {
        $source = sanitize_key((string)($config['author_source'] ?? 'none'));
        return match ($source) {
            'runtime' => (int)($context->runtime?->get('user_id', 0) ?? 0),
            'submission' => (int)($context->submission['user_id'] ?? 0),
            'current' => get_current_user_id(),
            'manual' => (int)$this->tokens->resolve((string)($config['author_user_id'] ?? ''), $context),
            default => 0,
        };
    }
}
