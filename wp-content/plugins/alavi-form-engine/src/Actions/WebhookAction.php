<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use RuntimeException;

final class WebhookAction implements ActionInterface
{
    public function __construct(private readonly TokenResolver $tokens) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        $url = esc_url_raw($this->tokens->resolve((string)($config['url'] ?? ''), $context));
        if ($url === '') {
            throw new RuntimeException('Webhook URL is empty or invalid.');
        }

        $method = strtoupper((string)($config['method'] ?? 'POST'));
        if (!in_array($method, ['POST','PUT','PATCH'], true)) {
            $method = 'POST';
        }

        $headers = $this->tokens->resolveValue((array)($config['headers'] ?? []), $context);
        $headers = array_merge(['Content-Type'=>'application/json; charset=utf-8'], is_array($headers) ? $headers : []);

        $body = '';
        if (array_key_exists('body', $config) && is_string($config['body']) && trim($config['body']) !== '') {
            $body = $this->tokens->resolve($config['body'], $context);
        } elseif (array_key_exists('payload', $config)) {
            $payload = $this->tokens->resolveValue($config['payload'], $context);
            $body = wp_json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '';
        } else {
            $body = wp_json_encode([
                'event'=>$context->event,
                'submission_id'=>$context->submissionId,
                'form'=>$context->formSlug,
                'data'=>$context->data,
            ], JSON_UNESCAPED_UNICODE) ?: '';
        }

        $response = wp_remote_request($url, [
            'method'=>$method,
            'timeout'=>15,
            'headers'=>$headers,
            'body'=>$body,
        ]);
        if (is_wp_error($response)) {
            throw new RuntimeException('Webhook failed: ' . $response->get_error_message());
        }
        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            throw new RuntimeException('Webhook failed with HTTP status ' . $code . '.');
        }
    }
}
