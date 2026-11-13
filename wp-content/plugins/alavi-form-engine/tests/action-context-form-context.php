<?php
declare(strict_types=1);

spl_autoload_register(static function(string $class): void {
    $prefix = 'BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionRuntime;

$contextData = [
    'category_id' => 123,
    'category_slug' => 'education-skills',
];
$context = new ActionContext(
    55,
    'quick-support-payment',
    ['amount'=>'50000'],
    ['id'=>55],
    'submission.submitted',
    'Quick Support',
    ['amount'],
    [],
    null,
    $contextData
);
$withRuntime = $context->withRuntime(new ActionRuntime());

$checks = [
    'context-is-separate-from-applicant-data' => !isset($context->data['category_id']) && $context->context === $contextData,
    'runtime-copy-preserves-context' => $withRuntime->context === $contextData,
    'runtime-copy-preserves-submission' => $withRuntime->submissionId === 55 && $withRuntime->formSlug === 'quick-support-payment',
];

foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}
