<?php
declare(strict_types=1);

$sourceRoot = dirname(__DIR__);
$tempRoot = sys_get_temp_dir() . '/afe-preflight-' . bin2hex(random_bytes(5));
$pluginRoot = $tempRoot . '/alavi-form-engine';

$copyTree = static function (string $src, string $dst) use (&$copyTree): void {
    if (is_dir($src)) {
        if (!is_dir($dst) && !mkdir($dst, 0777, true) && !is_dir($dst)) {
            throw new RuntimeException('Unable to create test directory: ' . $dst);
        }
        $items = scandir($src);
        if ($items === false) throw new RuntimeException('Unable to scan: ' . $src);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $copyTree($src . '/' . $item, $dst . '/' . $item);
        }
        return;
    }
    if (!copy($src, $dst)) throw new RuntimeException('Unable to copy: ' . $src);
};

$removeTree = static function (string $path) use (&$removeTree): void {
    if (!file_exists($path)) return;
    if (is_dir($path) && !is_link($path)) {
        $items = scandir($path) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $removeTree($path . '/' . $item);
        }
        rmdir($path);
        return;
    }
    unlink($path);
};

try {
    mkdir($pluginRoot, 0777, true);
    copy($sourceRoot . '/alavi-form-engine.php', $pluginRoot . '/alavi-form-engine.php');
    $copyTree($sourceRoot . '/src', $pluginRoot . '/src');
    $copyTree($sourceRoot . '/vendor', $pluginRoot . '/vendor');
    unlink($pluginRoot . '/src/Duplicate/DuplicateRepository.php');

    $runner = $tempRoot . '/runner.php';
    $pluginFile = var_export($pluginRoot . '/alavi-form-engine.php', true);
    file_put_contents($runner, <<<PHP_RUNNER
<?php
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', '/dev/null');
define('ABSPATH', __DIR__ . '/');
function plugin_dir_path(string \$file): string { return dirname(\$file) . '/'; }
function plugin_dir_url(string \$file): string { return 'https://example.test/plugins/alavi-form-engine/'; }
function add_action(string \$hook, callable \$callback, int \$priority = 10, int \$acceptedArgs = 1): void { \$GLOBALS['afe_test_actions'][\$hook][] = \$callback; }
function register_activation_hook(string \$file, callable|array|string \$callback): void {}
function register_deactivation_hook(string \$file, callable|array|string \$callback): void {}
include {$pluginFile};
if (!isset(\$GLOBALS['afe_test_actions']['admin_notices'])) { fwrite(STDERR, 'Expected bootstrap admin notice was not registered' . PHP_EOL); exit(2); }
if (isset(\$GLOBALS['afe_test_actions']['plugins_loaded'])) { fwrite(STDERR, 'Plugin boot hook must not be registered after failed preflight' . PHP_EOL); exit(3); }
echo 'bootstrap preflight stops incomplete deployment safely' . PHP_EOL;
PHP_RUNNER
    );

    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($runner);
    passthru($cmd, $exitCode);
    if ($exitCode !== 0) exit($exitCode);
} finally {
    $removeTree($tempRoot);
}
