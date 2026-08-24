<?php
/**
 * Plugin Name: Alavi Form Engine
 * Description: Code-first extensible form engine for WordPress with submissions, workflows, data sources, conditional logic and Elementor integration.
 * Version: 1.0.23
 * Requires at least: 6.4
 * Requires PHP: 8.3
 * Author: Bonyad Alavi
 * Text Domain: alavi-form-engine
 * Domain Path: /languages
 * License: GPL-3.0-or-later
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('AFE_VERSION', '1.0.23');
define('AFE_DB_VERSION', '1.0.4');
define('AFE_FILE', __FILE__);
define('AFE_PATH', plugin_dir_path(__FILE__));
define('AFE_URL', plugin_dir_url(__FILE__));

// Always register the plugin's own PSR-4 fallback. Do not make it conditional on
// Composer: a stale or partially deployed vendor/autoload.php must never prevent
// plugin classes from being resolved from /src on production sites.
spl_autoload_register(static function (string $class): void {
    $prefix = 'BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = AFE_PATH . 'src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_readable($file)) {
        require_once $file;
    }
});

$vendor = AFE_PATH . 'vendor/autoload.php';
if (is_readable($vendor)) {
    require_once $vendor;
}

// Fail early with a useful WordPress error instead of an opaque class-not-found
// fatal if a deployment is incomplete.
$criticalFiles = [
    AFE_PATH . 'src/Core/Plugin.php',
    AFE_PATH . 'src/Database/Migrator.php',
];
foreach ($criticalFiles as $criticalFile) {
    if (!is_readable($criticalFile)) {
        add_action('admin_notices', static function () use ($criticalFile): void {
            echo '<div class="notice notice-error"><p>' . esc_html(
                sprintf('Alavi Form Engine: required file is missing or unreadable: %s', $criticalFile)
            ) . '</p></div>';
        });
        return;
    }
}


register_activation_hook(__FILE__, [\BonyadAlavi\FormEngine\Core\Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [\BonyadAlavi\FormEngine\Core\Plugin::class, 'deactivate']);

add_action('plugins_loaded', static function (): void {
    \BonyadAlavi\FormEngine\Core\Plugin::instance()->boot();
});
