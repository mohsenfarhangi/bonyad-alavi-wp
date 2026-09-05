<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

$critical = [
    BonyadAlavi\FormEngine\Core\Plugin::class,
    BonyadAlavi\FormEngine\Database\Migrator::class,
    BonyadAlavi\FormEngine\Duplicate\DuplicateRepository::class,
    BonyadAlavi\FormEngine\Duplicate\DuplicatePolicy::class,
    BonyadAlavi\FormEngine\Duplicate\DuplicateFingerprint::class,
    BonyadAlavi\FormEngine\Actions\ActionRegistry::class,
    BonyadAlavi\FormEngine\Actions\ActionManager::class,
    BonyadAlavi\FormEngine\Actions\ActionExecutionRepository::class,
    BonyadAlavi\FormEngine\Actions\Tokens\TokenRegistry::class,
    BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver::class,
    BonyadAlavi\FormEngine\Actions\Sms\SmsProviderRegistry::class,
    BonyadAlavi\FormEngine\Actions\Sms\PersianWooCommerceSmsProvider::class,
    BonyadAlavi\FormEngine\Validation\ValidatorRegistry::class,
    BonyadAlavi\FormEngine\Submission\SubmissionService::class,
];

foreach ($critical as $class) {
    if (!class_exists($class)) {
        fwrite(STDERR, "Critical bootstrap class is not autoloadable: {$class}\n");
        exit(1);
    }
}

echo "bootstrap autoload ok\n";
