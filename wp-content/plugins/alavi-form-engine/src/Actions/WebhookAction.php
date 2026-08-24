<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

use RuntimeException;

final class WebhookAction implements ActionInterface
{
    public function handle(ActionContext $context, array $config = []): void
    {
        $url = esc_url_raw((string)($config['url'] ?? ''));
        if ($url === '') return;

        $response = wp_remote_post($url, [
            'timeout'=>15,
            'headers'=>array_merge(['Content-Type'=>'application/json'], (array)($config['headers'] ?? [])),
            'body'=>wp_json_encode([
                'event'=>'submission.created',
                'submission_id'=>$context->submissionId,
                'form'=>$context->formSlug,
                'data'=>$context->data,
            ], JSON_UNESCAPED_UNICODE),
        ]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
            throw new RuntimeException('Webhook failed.');
        }
    }
}
