<?php
declare(strict_types=1);

$root = dirname(__DIR__);

define('ABSPATH', __DIR__ . '/');

$GLOBALS['afe_test_actions'] = [];

function plugin_dir_path(string $file): string { return dirname($file) . '/'; }
function plugin_dir_url(string $file): string { return 'https://example.test/plugins/alavi-form-engine/'; }
function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void {
    $GLOBALS['afe_test_actions'][$hook][] = [
        'callback' => $callback,
        'priority' => $priority,
        'accepted_args' => $acceptedArgs,
    ];
}
function register_activation_hook(string $file, callable|array|string $callback): void {}
function register_deactivation_hook(string $file, callable|array|string $callback): void {}

require $root . '/alavi-form-engine.php';

if (!empty($GLOBALS['afe_test_actions']['plugins_loaded'])) {
    fwrite(STDERR, "FAIL engine boot must not run from plugins_loaded when theme forms can register later\n");
    exit(1);
}

$hooks = $GLOBALS['afe_test_actions']['after_setup_theme'] ?? [];
if (count($hooks) !== 1 || (int)($hooks[0]['priority'] ?? 0) !== 20) {
    fwrite(STDERR, "FAIL engine boot must be scheduled once on after_setup_theme priority 20\n");
    exit(1);
}

echo "PASS theme form registration window is available before AFE boot\n";
