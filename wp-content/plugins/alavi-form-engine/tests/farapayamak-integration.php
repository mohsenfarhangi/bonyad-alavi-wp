<?php
declare(strict_types=1);

function wp_salt(string $scheme='auth'): string { return 'fixed-test-salt-'.$scheme; }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Core\SecretStore;

$root=dirname(__DIR__);
$settings=file_get_contents($root.'/src/Admin/SettingsPage.php') ?: '';
$plugin=file_get_contents($root.'/src/Core/Plugin.php') ?: '';
$provider=file_get_contents($root.'/src/Actions/Sms/FarapayamakProvider.php') ?: '';
$entry=file_get_contents($root.'/alavi-form-engine.php') ?: '';
$store=new SecretStore();

$checks=[
    'provider-separate-from-melipayamak'=>str_contains($plugin, 'new FarapayamakProvider($farapayamakSettings)')
        && str_contains($plugin, 'FarapayamakProvider::KEY')
        && str_contains($plugin, 'new MeliPayamakProvider($smsSettings)'),
    'provider-supports-free-and-pattern'=>str_contains($plugin, "'فراپیامک — REST JSON'")
        && str_contains($plugin, "['free','pattern']"),
    'private-credentials-decrypted-only-in-runtime'=>str_contains($plugin, "\$farapayamakSettings['api_key'] = \$secretStore->decrypt("),
    'admin-has-independent-fields'=>str_contains($settings, 'name="sms_farapayamak_username"')
        && str_contains($settings, 'name="sms_farapayamak_api_key"')
        && str_contains($settings, 'name="sms_farapayamak_sender"'),
    'admin-api-key-never-prepopulated'=>str_contains($settings, 'name="sms_farapayamak_api_key" value="" autocomplete="new-password"'),
    'admin-encrypts-with-secret-store'=>str_contains($settings, '$this->secrets->encrypt($farapayamakInput)'),
    'admin-leaves-stored-key-when-blank'=>str_contains($settings, '$farapayamakKey=(string)($oldFarapayamak'),
    'admin-keeps-melipayamak-credentials'=>str_contains($settings, "'password'=>\$smsPassword")
        && str_contains($settings, "'api_key'=>\$smsApiKey")
        && str_contains($settings, "'farapayamak'=>["),
    'provider-uses-only-fixed-https-urls'=>str_contains($provider, "'https://rest.payamak-panel.com/api/SendSMS/SendSMS'")
        && str_contains($provider, "'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber'")
        && str_contains($provider, "'sslverify' => true"),
    'provider-never-logs-raw-api-response'=>!str_contains($provider, "'raw' =>") && !str_contains($provider, "'raw'=>"),
    'secret-store-available'=>$store->available(),
    'credential-encryption-roundtrip'=>$store->available()
        && $store->decrypt($store->encrypt('test-api-key'))==='test-api-key',
    'version-bumped-consistently'=>str_contains($entry, 'Version: 1.0.28-dev.3')
        && str_contains($entry, "define('AFE_VERSION', '1.0.28-dev.3')"),
];

foreach ($checks as $name=>$ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}
