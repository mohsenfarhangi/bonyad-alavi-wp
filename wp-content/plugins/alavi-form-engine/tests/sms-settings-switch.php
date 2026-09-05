<?php
declare(strict_types=1);

if (!function_exists('sanitize_key')) {
    function sanitize_key($key): string {
        $key = strtolower((string)$key);
        return preg_replace('/[^a-z0-9_\-]/', '', $key) ?: '';
    }
}

require_once __DIR__.'/../src/Actions/Sms/SmsProviderInterface.php';
require_once __DIR__.'/../src/Actions/Sms/SmsMessage.php';
require_once __DIR__.'/../src/Actions/Sms/SmsSettings.php';
require_once __DIR__.'/../src/Actions/Sms/SmsProviderRegistry.php';

use BonyadAlavi\FormEngine\Actions\Sms\SmsMessage;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderInterface;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderRegistry;
use BonyadAlavi\FormEngine\Actions\Sms\SmsSettings;

final class TestSmsProvider implements SmsProviderInterface
{
    public function send(SmsMessage $message): array { return ['ok'=>true]; }
}

$checks = [];
$checks['legacy-top-level-enabled'] = SmsSettings::enabled(['sms_enabled'=>1]) === true;
$checks['legacy-top-level-disabled'] = SmsSettings::enabled(['sms_enabled'=>0]) === false;
$checks['nested-enabled'] = SmsSettings::enabled(['sms'=>['enabled'=>true]]) === true;
$checks['nested-precedence'] = SmsSettings::enabled(['sms_enabled'=>1,'sms'=>['enabled'=>false]]) === false;
$checks['string-enabled'] = SmsSettings::enabled(['sms_enabled'=>'on']) === true;
$checks['default-provider'] = SmsSettings::defaultProvider(['sms'=>['default_provider'=>'persian_woocommerce_sms']]) === 'persian_woocommerce_sms';

$runtimeEnabled = false;
$registry = new SmsProviderRegistry('test', true);
$registry->register('test', 'Test', new TestSmsProvider(), ['free'], true);
$registry->setEnabledResolver(static function() use (&$runtimeEnabled): bool { return $runtimeEnabled; });
$blocked = false;
try { $registry->resolve('default', 'free'); } catch (RuntimeException $e) { $blocked = str_contains($e->getMessage(), 'غیرفعال'); }
$checks['runtime-resolver-blocks'] = $blocked;
$runtimeEnabled = true;
$checks['runtime-resolver-refreshes'] = $registry->resolve('default', 'free') instanceof TestSmsProvider;

$pluginSource = file_get_contents(__DIR__.'/../src/Core/Plugin.php') ?: '';
$settingsSource = file_get_contents(__DIR__.'/../src/Admin/SettingsPage.php') ?: '';
$checks['plugin-uses-normalized-settings'] = str_contains($pluginSource, 'SmsSettings::enabled($globalSettings)') && str_contains($pluginSource, 'setEnabledResolver');
$checks['save-syncs-legacy-switch'] = str_contains($settingsSource, "'sms_enabled'=>".'$smsEnabled');

$failed = array_keys(array_filter($checks, static fn(bool $ok): bool => !$ok));
if ($failed !== []) {
    fwrite(STDERR, 'FAIL: '.implode(', ', $failed).PHP_EOL);
    exit(1);
}

echo 'PASS sms-settings-switch'.PHP_EOL;
