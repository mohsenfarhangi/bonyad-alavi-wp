<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Sms;

use RuntimeException;

/**
 * MeliPayamak adapter supporting both account generations:
 * - legacy REST: username/password on rest.payamak-panel.com
 * - console API: token in URL on console.melipayamak.com
 *
 * Endpoints are fixed to documented service hosts; developers may override them
 * via filters for staging/proxy tests. No endpoint is accepted from admin UI.
 */
final class MeliPayamakProvider implements SmsProviderInterface
{
    private const LEGACY_FREE_ENDPOINT = 'https://rest.payamak-panel.com/api/SendSMS/SendSMS';
    private const LEGACY_PATTERN_ENDPOINT = 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber';
    private const CONSOLE_BASE = 'https://console.melipayamak.com/api';

    public function __construct(private readonly array $settings) {}

    public function send(SmsMessage $message): array
    {
        if (empty($this->settings['enabled'])) {
            return ['success'=>false, 'error'=>'MeliPayamak is disabled in Alavi Form Engine settings.'];
        }

        $authMode = sanitize_key((string)($this->settings['auth_mode'] ?? 'legacy'));
        return $authMode === 'api_key'
            ? $this->sendConsole($message)
            : $this->sendLegacy($message);
    }

    private function sendLegacy(SmsMessage $message): array
    {
        $username = trim((string)($this->settings['username'] ?? ''));
        $password = (string)($this->settings['password'] ?? '');
        if ($username === '' || $password === '') {
            return ['success'=>false, 'error'=>'MeliPayamak legacy username/password is not configured.'];
        }

        $endpoint = $message->mode === 'pattern'
            ? self::LEGACY_PATTERN_ENDPOINT
            : self::LEGACY_FREE_ENDPOINT;
        $filter = $message->mode === 'pattern'
            ? 'afe_melipayamak_legacy_pattern_endpoint'
            : 'afe_melipayamak_legacy_free_endpoint';
        $endpoint = (string)apply_filters($filter, $endpoint);
        $this->assertEndpoint($endpoint, ['rest.payamak-panel.com']);

        $payload = [
            'username'=>$username,
            'password'=>$password,
            'to'=>$message->recipient,
        ];

        if ($message->mode === 'pattern') {
            if (!ctype_digit($message->patternCode) || (int)$message->patternCode <= 0) {
                return ['success'=>false, 'error'=>'MeliPayamak legacy pattern/body ID must be a positive number.'];
            }
            $payload['text'] = implode(';', array_values($message->patternValues));
            $payload['bodyId'] = (int)$message->patternCode;
        } else {
            $sender = $this->sender($message);
            if ($sender === '') return ['success'=>false, 'error'=>'MeliPayamak sender number is not configured.'];
            $payload['from'] = $sender;
            $payload['text'] = $message->body;
            $payload['isFlash'] = false;
        }

        $response = wp_remote_post($endpoint, [
            'timeout'=>$this->timeout(),
            'headers'=>['Content-Type'=>'application/x-www-form-urlencoded; charset=utf-8'],
            'body'=>$payload,
            'redirection'=>0,
        ]);

        if (is_wp_error($response)) {
            return ['success'=>false, 'error'=>'MeliPayamak connection error: '.$response->get_error_message()];
        }

        $httpCode = wp_remote_retrieve_response_code($response);
        $rawBody = wp_remote_retrieve_body($response);
        $decoded = json_decode($rawBody, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            return ['success'=>false, 'error'=>'MeliPayamak HTTP error: '.$httpCode, 'raw'=>$this->safeRaw($decoded, $rawBody)];
        }
        if (!is_array($decoded)) {
            return ['success'=>false, 'error'=>'MeliPayamak returned an invalid legacy response.', 'raw'=>$this->safeRaw($decoded, $rawBody)];
        }

        $retStatus = (int)($decoded['RetStatus'] ?? $decoded['retStatus'] ?? 0);
        if ($retStatus !== 1) {
            $error = trim((string)($decoded['StrRetStatus'] ?? $decoded['strRetStatus'] ?? ''));
            if ($error === '') $error = 'MeliPayamak legacy request failed (RetStatus '.$retStatus.').';
            return ['success'=>false, 'error'=>$error, 'raw'=>$this->safeRaw($decoded, $rawBody)];
        }

        $messageId = (string)($decoded['Value'] ?? $decoded['value'] ?? '');
        return [
            'success'=>true,
            'message_id'=>$messageId,
            'raw'=>$this->safeRaw($decoded, $rawBody),
        ];
    }

    private function sendConsole(SmsMessage $message): array
    {
        $apiKey = trim((string)($this->settings['api_key'] ?? ''));
        if ($apiKey === '') {
            return ['success'=>false, 'error'=>'MeliPayamak API key/token is not configured.'];
        }
        if (!preg_match('/^[A-Za-z0-9._~-]+$/', $apiKey)) {
            return ['success'=>false, 'error'=>'MeliPayamak API key/token contains unsupported URL characters.'];
        }

        $route = $message->mode === 'pattern' ? 'send/shared' : 'send/simple';
        $base = (string)apply_filters('afe_melipayamak_console_base', self::CONSOLE_BASE);
        $this->assertEndpoint($base, ['console.melipayamak.com']);
        $endpoint = rtrim($base, '/') . '/' . $route . '/' . rawurlencode($apiKey);

        if ($message->mode === 'pattern') {
            if (!ctype_digit($message->patternCode) || (int)$message->patternCode <= 0) {
                return ['success'=>false, 'error'=>'MeliPayamak pattern/body ID must be a positive number.'];
            }
            $payload = [
                'to'=>$message->recipient,
                'bodyId'=>(int)$message->patternCode,
                'args'=>array_values($message->patternValues),
            ];
        } else {
            $sender = $this->sender($message);
            if ($sender === '') return ['success'=>false, 'error'=>'MeliPayamak sender number is not configured.'];
            $payload = [
                'from'=>$sender,
                'to'=>$message->recipient,
                'text'=>$message->body,
            ];
        }

        $response = wp_remote_post($endpoint, [
            'timeout'=>$this->timeout(),
            'headers'=>[
                'Content-Type'=>'application/json; charset=utf-8',
                'Cache-Control'=>'no-cache',
            ],
            'body'=>wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'redirection'=>0,
        ]);

        if (is_wp_error($response)) {
            return ['success'=>false, 'error'=>'MeliPayamak connection error: '.$response->get_error_message()];
        }

        $httpCode = wp_remote_retrieve_response_code($response);
        $rawBody = wp_remote_retrieve_body($response);
        $decoded = json_decode($rawBody, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            return ['success'=>false, 'error'=>'MeliPayamak HTTP error: '.$httpCode, 'raw'=>$this->safeRaw($decoded, $rawBody)];
        }
        if (!is_array($decoded)) {
            return ['success'=>false, 'error'=>'MeliPayamak returned an invalid console API response.', 'raw'=>$this->safeRaw($decoded, $rawBody)];
        }

        $status = trim((string)($decoded['status'] ?? ''));
        $messageId = trim((string)($decoded['recId'] ?? ''));
        if ($status !== '' || $messageId === '') {
            return [
                'success'=>false,
                'error'=>$status !== '' ? $status : 'MeliPayamak console API did not return a receipt ID.',
                'raw'=>$this->safeRaw($decoded, $rawBody),
            ];
        }

        return ['success'=>true, 'message_id'=>$messageId, 'raw'=>$this->safeRaw($decoded, $rawBody)];
    }

    private function sender(SmsMessage $message): string
    {
        return trim($message->sender !== '' ? $message->sender : (string)($this->settings['sender'] ?? ''));
    }

    private function timeout(): int
    {
        $timeout = (int)($this->settings['timeout'] ?? 20);
        return max(5, min(60, $timeout));
    }

    private function assertEndpoint(string $url, array $allowedHosts): void
    {
        $parts = wp_parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = strtolower((string)($parts['host'] ?? ''));
        if ($scheme !== 'https' || !in_array($host, $allowedHosts, true)) {
            throw new RuntimeException('Unsafe MeliPayamak endpoint override rejected.');
        }
    }

    private function safeRaw(mixed $decoded, string $rawBody): mixed
    {
        // Provider responses do not contain request credentials. Limit any
        // unexpected raw body before it can be written to an execution log.
        if (is_array($decoded)) return $decoded;
        return function_exists('mb_substr') ? mb_substr($rawBody, 0, 2000) : substr($rawBody, 0, 2000);
    }
}
