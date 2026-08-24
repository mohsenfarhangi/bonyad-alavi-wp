<?php
declare(strict_types=1);

$GLOBALS['afe_test_settings'] = [];
function get_option(string $key, mixed $default = false): mixed {
    if ($key === 'afe_settings') return $GLOBALS['afe_test_settings'] ?: $default;
    return $default;
}

require_once __DIR__ . '/../src/Style/StyleIsolationManager.php';
use BonyadAlavi\FormEngine\Style\StyleIsolationManager;

$manager = new StyleIsolationManager();
$cases = [];
$cases[] = ['default-is-strong', $manager->modeForForm([]) === 'strong'];
$GLOBALS['afe_test_settings'] = ['style_isolation' => 'default'];
$cases[] = ['global-default', $manager->modeForForm([]) === 'default'];
$cases[] = ['form-overrides-global', $manager->modeForForm(['settings'=>['style_isolation'=>'disabled']]) === 'disabled'];
$cases[] = ['invalid-form-falls-global', $manager->modeForForm(['settings'=>['style_isolation'=>'broken']]) === 'default'];
$cases[] = ['shell-class', $manager->shellClass(['settings'=>['style_isolation'=>'strong']]) === 'afe-shell afe-isolation-strong'];

$failed = array_filter($cases, static fn(array $case): bool => !$case[1]);
foreach ($cases as [$name,$ok]) echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
exit($failed ? 1 : 0);
