<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use RuntimeException;

final class RedirectAction implements ActionInterface
{
    public function __construct(private readonly TokenResolver $tokens) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        if (!$context->runtime) throw new RuntimeException('Action runtime is not available.');
        $raw = trim($this->tokens->resolve((string)($config['url'] ?? ''), $context));
        if ($raw === '') throw new RuntimeException('Redirect URL خالی است.');

        if (str_starts_with($raw, '/')) {
            $url = home_url($raw);
        } else {
            $url = esc_url_raw($raw);
        }
        if ($url === '') throw new RuntimeException('Redirect URL معتبر نیست.');

        $siteHost = strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST));
        $targetHost = strtolower((string)wp_parse_url($url, PHP_URL_HOST));
        $internal = $targetHost === '' || ($siteHost !== '' && hash_equals($siteHost, $targetHost));
        if (!$internal && empty($config['allow_external'])) {
            throw new RuntimeException('Redirect خارجی برای این Action مجاز نشده است.');
        }

        $context->runtime->setRedirect($url);
    }
}
