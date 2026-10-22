<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$settings=file_get_contents($root.'/src/Admin/SettingsPage.php')?:'';
$plugin=file_get_contents($root.'/src/Core/Plugin.php')?:'';
$debug=file_get_contents($root.'/src/Actions/Sms/SmsDebugLog.php')?:'';
$action=file_get_contents($root.'/src/Actions/Sms/SmsAction.php')?:'';
$otp=file_get_contents($root.'/src/Security/SmsDraftRecovery.php')?:'';
$provider=file_get_contents($root.'/src/Actions/Sms/FarapayamakProvider.php')?:'';
$js=file_get_contents($root.'/assets/js/admin-sms-test.js')?:'';
$css=file_get_contents($root.'/assets/css/admin.css')?:'';
$version=file_get_contents($root.'/alavi-form-engine.php')?:'';

$checks=[
    'six-settings-tabs'=>substr_count($settings,'data-afe-settings-panel="')===6
        && substr_count($settings,'role="tabpanel"')===6
        && str_contains($settings,"'debug'=>'دیباگ پیامک'")
        && str_contains($settings,"'forms'=>'دسترسی فرم‌ها'"),
    'one-global-settings-form'=>substr_count($settings,"echo '<form method=")===1
        && substr_count($settings,"echo '</div></form></div>'")===1,
    'sticky-save'=>str_contains($settings,'class="afe-settings-save"')
        && str_contains($css,'.afe-settings-save { position:sticky'),
    'tab-persisted-through-save'=>str_contains($settings,'name="afe_settings_tab"')
        && str_contains($settings, "&tab='.\$tab"),
    'tab-JS-keeps-other-inputs-enabled'=>str_contains($js,'panel.hidden =')
        && !str_contains($js,'panel.disabled =')
        && str_contains($js,'aria-selected'),
    'debug-opt-in-setting'=>str_contains($settings,'name="sms_debug_enabled"')
        && str_contains($settings,"'debug_enabled'=>\$smsDebugEnabled"),
    'debug-management-secured'=>str_contains($settings,"check_ajax_referer('afe_sms_debug'")
        && str_contains($settings,'Capabilities::MANAGE_SETTINGS')
        && str_contains($plugin,"wp_ajax_afe_sms_debug")
        && !str_contains($plugin,"wp_ajax_nopriv_afe_sms_debug"),
    'debug-action-and-otp-integration'=>str_contains($action,"SmsDebugLog::record('action'")
        && str_contains($otp,"SmsDebugLog::record('otp'")
        && str_contains($settings,"SmsDebugLog::record('test'"),
    'never-logs-provider-errors'=>!str_contains($debug,"\$result['error']")
        && !str_contains($debug,"\$result['raw']")
        && !str_contains($debug,"\$result['body']"),
    'farapayamak-numeric-status-codes'=>str_contains($provider,"'ret_status'=>(int)\$status")
        && str_contains($provider,"'http_status'=>\$http"),
    'debug-capped-and-clear'=>str_contains($debug,'MAX_EVENTS = 40')
        && str_contains($debug,'delete_option(self::OPTION)'),
    'ui-escape-and-ajax-preconditions'=>str_contains($settings,'esc_html($cell)')
        && str_contains($js,'td.textContent')
        && str_contains($js,'settings.debugNonce'),
    'asset-version'=>str_contains($version,'Version: 1.0.28-dev.6')
        && str_contains($version,"define('AFE_VERSION', '1.0.28-dev.6')"),
];
foreach($checks as $name=>$ok) {
    echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
    if(!$ok) exit(1);
}
