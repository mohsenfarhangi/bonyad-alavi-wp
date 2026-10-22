<?php
declare(strict_types=1);

$base=dirname(__DIR__);
$plugin=file_get_contents($base.'/src/Core/Plugin.php') ?: '';
$service=file_get_contents($base.'/src/Submission/SubmissionService.php') ?: '';
$renderer=file_get_contents($base.'/src/Form/Renderer.php') ?: '';
$formAdmin=file_get_contents($base.'/src/Admin/FormsPage.php') ?: '';
$settings=file_get_contents($base.'/src/Admin/SettingsPage.php') ?: '';
$front=file_get_contents($base.'/assets/js/frontend.js') ?: '';
$script=file_get_contents($base.'/assets/js/admin-sms-test.js') ?: '';
$css=file_get_contents($base.'/assets/css/frontend.css') ?: '';
$recovery=file_get_contents($base.'/src/Security/SmsDraftRecovery.php') ?: '';
$entry=file_get_contents($base.'/alavi-form-engine.php') ?: '';
$checks=[
    'public-otp-start-and-verify-registered'=>str_contains($plugin, 'wp_ajax_nopriv_afe_otp_start')
        && str_contains($plugin, 'wp_ajax_nopriv_afe_otp_verify'),
    'nonce-protected-otp-endpoints'=>str_contains($plugin, "wp_verify_nonce(")
        && str_contains($plugin, "'afe_submit_'.\$slug")
        && str_contains($plugin, 'wp_ajax_nopriv_afe_otp_verify'),
    'server-normalizes-before-duplicate-lookup'=>str_contains($plugin, 'normalizeRecoveryInput($form')
        && str_contains($service, 'return $this->sanitizeBySchema($form, $data);'),
    'sms-recipient-is-persisted-mobile'=>str_contains($recovery, 'new SmsMessage(')
        && str_contains($recovery, '$stored, \'free\'')
        && !str_contains($recovery, 'new SmsMessage($mobile'),
    'challenge-hashed-and-bounded'=>str_contains($recovery, "hash_hmac('sha256'")
        && str_contains($recovery, '5*MINUTE_IN_SECONDS')
        && str_contains($recovery, '15*MINUTE_IN_SECONDS')
        && str_contains($recovery, 'HOUR_IN_SECONDS'),
    'existing-token-not-leaked'=>!str_contains($recovery, "'tracking_code'")
        && !str_contains($recovery, "'submission_id'"),
    'grant-validates-row-and-form'=>str_contains($recovery, "row_id")
        && str_contains($recovery, "'draft'"),
    'renderer-loads-otp-grant'=>str_contains($renderer, "'afe_recovery'")
        && str_contains($renderer, "name=\"_afe_recovery\"")
        && str_contains($renderer, 'data-afe-duplicate-otp'),
    'service-verifies-grant-on-submit'=>substr_count($service, "smsRecovery ?? null")>=2
        && str_contains($service, "'_afe_recovery'"),
    'form-admin-opt-in-and-phone-selector'=>str_contains($formAdmin, 'name="duplicate_otp_enabled"')
        && str_contains($formAdmin, 'name="duplicate_otp_mobile_field"')
        && str_contains($formAdmin, 'name="duplicate_otp_provider"'),
    'frontend-starts-and-verifies-otp'=>str_contains($front, "'afe_otp_start'")
        && str_contains($front, "'afe_otp_verify'")
        && str_contains($front, 'afe-otp-mobile')
        && str_contains($front, "'_afe_recovery'"),
    'admin-test-authentication-and-throttle'=>str_contains($settings, 'Capabilities::MANAGE_SETTINGS')
        && str_contains($settings, "check_ajax_referer('afe_test_sms'")
        && str_contains($settings, 'get_transient($rate)>=3'),
    'admin-test-does-not-return-raw-gateway-error'=>!str_contains($settings, "\$sent['error']")
        && !str_contains($settings, "\$sent['raw']"),
    'admin-test-uses-saved-provider'=>str_contains($settings, "\$this->smsProviders->resolve(\$providerKey,'free')")
        && str_contains($plugin, 'wp_ajax_afe_sms_test')
        && !str_contains($plugin, 'wp_ajax_nopriv_afe_sms_test'),
    'admin-test-ui-real-send-warning'=>str_contains($settings, 'data-afe-sms-test-button')
        && str_contains($settings, 'ارسال آزمایشی ممکن است هزینه داشته باشد'),
    'admin-script-uses-post-and-nonce'=>str_contains($script, "method:'POST'")
        && str_contains($script, 'window.afeSmsTest.nonce')
        && str_contains($settings, "wp_add_inline_script('afe-admin-sms-test'"),
    'ui-responsive-controls'=>str_contains($css,'.afe-form .afe-otp-mobile')
        && str_contains($css,'[data-afe-otp-verify-section][hidden]'),
    'development-assets-version'=>str_contains($entry,'Version: 1.0.28-dev.5')
        && str_contains($entry,"define('AFE_VERSION', '1.0.28-dev.5')"),
];
foreach($checks as $name=>$pass) {
    echo ($pass?'PASS ':'FAIL ').$name.PHP_EOL;
    if(!$pass) exit(1);
}
