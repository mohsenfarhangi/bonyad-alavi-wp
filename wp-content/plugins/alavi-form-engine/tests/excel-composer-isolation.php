<?php

declare(strict_types=1);

namespace Composer\Autoload {
    if (!class_exists(ClassLoader::class, false)) {
        final class ClassLoader
        {
            /** @param array<string,string> $map */
            public function __construct(private array $map = []) {}
            public function register(bool $prepend = false): void { spl_autoload_register([$this, 'loadClass'], true, $prepend); }
            public function unregister(): void { @spl_autoload_unregister([$this, 'loadClass']); }
            public function loadClass(string $class): ?bool
            {
                foreach ($this->map as $prefix => $dir) {
                    if (!str_starts_with($class, $prefix)) { continue; }
                    $relative = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                    $file = rtrim($dir, '/\\') . '/' . $relative;
                    if (is_file($file)) { require $file; return true; }
                }
                return null;
            }
        }
    }
}

namespace {
    use BonyadAlavi\FormEngine\Export\ExportAutoloadScope;
    use Composer\Autoload\ClassLoader;

    $tmp = sys_get_temp_dir() . '/afe-export-isolation-' . getmypid();
    $own = $tmp . '/alavi-form-engine/vendor';
    $foreign = $tmp . '/pinova/vendor';
    @mkdir($own . '/phpoffice', 0777, true);
    @mkdir($own . '/zipstream', 0777, true);
    @mkdir($foreign . '/phpoffice', 0777, true);
    @mkdir($foreign . '/zipstream/Option', 0777, true);

    $write = static function (string $path, string $code): void {
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, "<?php\n" . $code);
    };

    $write($own . '/phpoffice/Spreadsheet.php', 'namespace PhpOffice\\PhpSpreadsheet; class Spreadsheet {}');
    $write($own . '/phpoffice/Writer/Xlsx.php', 'namespace PhpOffice\\PhpSpreadsheet\\Writer; class Xlsx {}');
    $write($own . '/phpoffice/Writer/ZipStream0.php', 'namespace PhpOffice\\PhpSpreadsheet\\Writer; class ZipStream0 {}');
    $write($own . '/zipstream/ZipStream.php', 'namespace ZipStream; class ZipStream {}');
    $write($own . '/zipstream/OperationMode.php', 'namespace ZipStream; enum OperationMode { case NORMAL; }');

    $write($foreign . '/phpoffice/Spreadsheet.php', 'namespace PhpOffice\\PhpSpreadsheet; class Spreadsheet {}');
    $write($foreign . '/phpoffice/Writer/Xlsx.php', 'namespace PhpOffice\\PhpSpreadsheet\\Writer; class Xlsx {}');
    $write($foreign . '/phpoffice/Writer/ZipStream0.php', 'namespace PhpOffice\\PhpSpreadsheet\\Writer; class ZipStream0 {}');
    $write($foreign . '/zipstream/ZipStream.php', 'namespace ZipStream; class ZipStream {}');
    $write($foreign . '/zipstream/Option/Archive.php', 'namespace ZipStream\\Option; class Archive {}');

    if (!defined('AFE_PATH')) { define('AFE_PATH', $tmp . '/alavi-form-engine/'); }
    require_once dirname(__DIR__) . '/src/Export/ExportAutoloadScope.php';

    $ownLoader = new ClassLoader([
        'PhpOffice\\PhpSpreadsheet\\' => $own . '/phpoffice',
        'ZipStream\\' => $own . '/zipstream',
    ]);
    $foreignLoader = new ClassLoader([
        'PhpOffice\\PhpSpreadsheet\\' => $foreign . '/phpoffice',
        'ZipStream\\' => $foreign . '/zipstream',
    ]);
    $ownLoader->register(true);
    $foreignLoader->register(true); // would win without AFE isolation
    $GLOBALS['afe_composer_loader'] = $ownLoader;

    ExportAutoloadScope::run(static function (): void {
        ExportAutoloadScope::assertOwnExcelRuntime();
    });

    $path = ExportAutoloadScope::loadedClassPath('PhpOffice\\PhpSpreadsheet\\Spreadsheet');
    $ok = is_string($path) && str_contains(str_replace('\\', '/', $path), '/alavi-form-engine/vendor/');
    echo ($ok ? 'PASS ' : 'FAIL ') . "AFE vendor wins over foreign Composer loader\n";
    if (!$ok) { exit(1); }
    if (class_exists('ZipStream\\Option\\Archive', false)) {
        echo "FAIL foreign ZipStream v2 marker was loaded\n";
        exit(1);
    }
    echo "PASS foreign ZipStream v2 marker stayed unloaded\n";

    $foreignLoader->unregister();
    $ownLoader->unregister();
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iter as $file) { $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname()); }
    @rmdir($tmp);
    echo "excel composer isolation ok\n";
}
