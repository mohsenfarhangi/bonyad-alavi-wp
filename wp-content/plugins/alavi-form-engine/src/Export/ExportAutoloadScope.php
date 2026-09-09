<?php

declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Export;

use RuntimeException;

/**
 * Keeps export-package class resolution inside AFE's Composer vendor while an
 * export is being generated. WordPress plugins commonly register independent
 * Composer autoloaders; without a scope, PhpSpreadsheet from one plugin can be
 * combined with ZipStream from another plugin in the same request.
 */
final class ExportAutoloadScope
{
    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public static function run(callable $callback): mixed
    {
        $ownLoader = $GLOBALS['afe_composer_loader'] ?? null;
        if (!is_object($ownLoader) || !method_exists($ownLoader, 'register') || !method_exists($ownLoader, 'unregister')) {
            return $callback();
        }

        self::assertNoForeignExportClassesLoaded();

        $removed = [];
        foreach (spl_autoload_functions() ?: [] as $autoload) {
            $loader = self::composerLoaderFromCallable($autoload);
            if ($loader === null || $loader === $ownLoader) {
                continue;
            }
            if (spl_autoload_unregister($autoload)) {
                $removed[] = $loader;
            }
        }

        // Composer loaders register with prepend support. Put AFE first again in
        // case another plugin registered its loader after AFE during plugins_loaded.
        $ownLoader->unregister();
        $ownLoader->register(true);

        try {
            return $callback();
        } finally {
            // A successful binary download terminates the request before this point;
            // on an exception/admin error restore the other plugin loaders.
            foreach ($removed as $loader) {
                if (is_object($loader) && method_exists($loader, 'register')) {
                    $loader->register(false);
                }
            }
        }
    }

    public static function assertOwnExcelRuntime(): void
    {
        $classes = [
            'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
            'PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx',
            'PhpOffice\\PhpSpreadsheet\\Writer\\ZipStream0',
            'ZipStream\\ZipStream',
            'ZipStream\\OperationMode',
        ];

        foreach ($classes as $class) {
            if (!class_exists($class) && !enum_exists($class)) {
                throw new RuntimeException('وابستگی Excel در vendor افزونه ناقص است: ' . $class);
            }
            self::assertClassFromOwnVendor($class);
        }

        // ZipStream v2 exposes Option\\Archive. PhpSpreadsheet uses its presence as
        // the v2/v3 switch. If another plugin already loaded that marker while AFE
        // uses ZipStream v3, ZipStream0 will select the wrong constructor.
        if (class_exists('ZipStream\\Option\\Archive', false)) {
            $path = self::loadedClassPath('ZipStream\\Option\\Archive');
            throw new RuntimeException(
                'تداخل Composer برای Excel شناسایی شد: ZipStream\\Option\\Archive از نسخه قدیمی ZipStream قبلاً لود شده است'
                . ($path !== null ? ' (' . $path . ')' : '')
                . '. این request را نمی‌توان به‌صورت امن با ZipStream v3 ادامه داد.'
            );
        }
    }

    public static function assertNoForeignExportClassesLoaded(): void
    {
        $classes = [
            'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
            'PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx',
            'PhpOffice\\PhpSpreadsheet\\Writer\\ZipStream0',
            'PhpOffice\\PhpSpreadsheet\\Writer\\ZipStream2',
            'PhpOffice\\PhpSpreadsheet\\Writer\\ZipStream3',
            'ZipStream\\ZipStream',
            'ZipStream\\OperationMode',
            'ZipStream\\Option\\Archive',
        ];

        foreach ($classes as $class) {
            if (!class_exists($class, false) && !enum_exists($class, false)) {
                continue;
            }
            $path = self::loadedClassPath($class);
            if ($path === null || self::isOwnVendorPath($path)) {
                continue;
            }
            throw new RuntimeException(
                'تداخل Composer برای خروجی Excel شناسایی شد. کلاس ' . $class
                . ' قبل از اجرای Alavi Form Engine از این مسیر لود شده است: ' . $path
                . '. AFE برای جلوگیری از ترکیب نسخه‌های ناسازگار PhpSpreadsheet/ZipStream خروجی را متوقف کرد.'
            );
        }
    }

    public static function loadedClassPath(string $class): ?string
    {
        try {
            $ref = new \ReflectionClass($class);
            $file = $ref->getFileName();
            return is_string($file) && $file !== '' ? self::normalizePath($file) : null;
        } catch (\ReflectionException) {
            return null;
        }
    }

    private static function assertClassFromOwnVendor(string $class): void
    {
        $path = self::loadedClassPath($class);
        if ($path === null || !self::isOwnVendorPath($path)) {
            throw new RuntimeException(
                'وابستگی Excel از vendor افزونه دیگری resolve شده است: ' . $class
                . ($path !== null ? ' (' . $path . ')' : '')
                . '. پوشه vendor خود Alavi Form Engine را با Composer نصب/به‌روزرسانی کنید.'
            );
        }
    }

    private static function isOwnVendorPath(string $path): bool
    {
        $base = defined('AFE_PATH') ? (string) AFE_PATH : dirname(__DIR__, 2) . '/';
        $vendor = self::normalizePath(rtrim($base, '/\\') . '/vendor/');
        return str_starts_with(self::normalizePath($path), $vendor);
    }

    private static function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private static function composerLoaderFromCallable(mixed $autoload): ?object
    {
        if (!is_array($autoload) || count($autoload) < 2 || $autoload[1] !== 'loadClass' || !is_object($autoload[0])) {
            return null;
        }
        $loader = $autoload[0];
        return is_a($loader, 'Composer\\Autoload\\ClassLoader') ? $loader : null;
    }
}
