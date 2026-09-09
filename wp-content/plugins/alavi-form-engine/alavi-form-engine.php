<?php
/**
 * Plugin Name: Alavi Form Engine
 * Description: Code-first extensible form engine for WordPress with submissions, workflows, data sources, conditional logic and Elementor integration.
 * Version: 1.0.28-dev
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

define('AFE_VERSION', '1.0.28-dev');
define('AFE_DB_VERSION', '1.0.5-dev.2');
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
    $afeComposerLoader = require $vendor;
    if (is_object($afeComposerLoader) && method_exists($afeComposerLoader, 'loadClass')) {
        $GLOBALS['afe_composer_loader'] = $afeComposerLoader;
    }
}

// Fail early with a useful WordPress error instead of an opaque class-not-found
// fatal if a deployment is incomplete. These are the classes instantiated during
// the initial boot path before extension hooks can meaningfully recover anything.
$criticalBootstrapClasses = [
    'BonyadAlavi\\FormEngine\\Core\\Plugin' => 'src/Core/Plugin.php',
    'BonyadAlavi\\FormEngine\\Database\\Migrator' => 'src/Database/Migrator.php',
    'BonyadAlavi\\FormEngine\\Duplicate\\DuplicateRepository' => 'src/Duplicate/DuplicateRepository.php',
    'BonyadAlavi\\FormEngine\\Duplicate\\DuplicatePolicy' => 'src/Duplicate/DuplicatePolicy.php',
    'BonyadAlavi\\FormEngine\\Duplicate\\DuplicateFingerprint' => 'src/Duplicate/DuplicateFingerprint.php',
    'BonyadAlavi\\FormEngine\\Actions\\ActionRegistry' => 'src/Actions/ActionRegistry.php',
    'BonyadAlavi\\FormEngine\\Actions\\ActionManager' => 'src/Actions/ActionManager.php',
    'BonyadAlavi\\FormEngine\\Actions\\ActionExecutionRepository' => 'src/Actions/ActionExecutionRepository.php',
    'BonyadAlavi\\FormEngine\\Actions\\Tokens\\TokenRegistry' => 'src/Actions/Tokens/TokenRegistry.php',
    'BonyadAlavi\\FormEngine\\Actions\\Tokens\\TokenResolver' => 'src/Actions/Tokens/TokenResolver.php',
    'BonyadAlavi\\FormEngine\\Actions\\Sms\\SmsProviderRegistry' => 'src/Actions/Sms/SmsProviderRegistry.php',
    'BonyadAlavi\\FormEngine\\Actions\\Sms\\PersianWooCommerceSmsProvider' => 'src/Actions/Sms/PersianWooCommerceSmsProvider.php',
    'BonyadAlavi\\FormEngine\\Validation\\ValidatorRegistry' => 'src/Validation/ValidatorRegistry.php',
    'BonyadAlavi\\FormEngine\\InputMask\\InputMaskRegistry' => 'src/InputMask/InputMaskRegistry.php',
    'BonyadAlavi\\FormEngine\\InputMask\\InputMaskPattern' => 'src/InputMask/InputMaskPattern.php',
    'BonyadAlavi\\FormEngine\\Submission\\SubmissionService' => 'src/Submission/SubmissionService.php',
    'BonyadAlavi\\FormEngine\\Export\\ExportProfile' => 'src/Export/ExportProfile.php',
    'BonyadAlavi\\FormEngine\\Export\\ExportPackageStatus' => 'src/Export/ExportPackageStatus.php',
    'BonyadAlavi\\FormEngine\\Export\\ExportAutoloadScope' => 'src/Export/ExportAutoloadScope.php',
    'BonyadAlavi\\FormEngine\\Export\\BinaryDownload' => 'src/Export/BinaryDownload.php',
    'BonyadAlavi\\FormEngine\\Export\\PdfExporter' => 'src/Export/PdfExporter.php',
    'BonyadAlavi\\FormEngine\\Export\\ExcelExporter' => 'src/Export/ExcelExporter.php',
];
$bootstrapErrors = [];
foreach ($criticalBootstrapClasses as $criticalClass => $relativeFile) {
    $absoluteFile = AFE_PATH . $relativeFile;
    if (!is_readable($absoluteFile)) {
        $bootstrapErrors[] = sprintf('missing or unreadable: %s', $relativeFile);
        continue;
    }
    if (!class_exists($criticalClass)) {
        $bootstrapErrors[] = sprintf('class is not autoloadable: %s (%s)', $criticalClass, $relativeFile);
    }
}

if ($bootstrapErrors !== []) {
    $message = 'Alavi Form Engine bootstrap preflight failed. The plugin installation is incomplete or unreadable. '
        . implode('; ', $bootstrapErrors)
        . '. Reinstall the complete plugin package instead of copying only changed files.';
    error_log($message);
    add_action('admin_notices', static function () use ($message): void {
        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
    });
    return;
}


register_activation_hook(__FILE__, [\BonyadAlavi\FormEngine\Core\Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [\BonyadAlavi\FormEngine\Core\Plugin::class, 'deactivate']);

add_action('plugins_loaded', static function (): void {
    \BonyadAlavi\FormEngine\Core\Plugin::instance()->boot();
});
