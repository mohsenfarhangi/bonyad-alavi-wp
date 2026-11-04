<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = dirname(__DIR__) . '/src/' . str_replace('\\\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\\FormEngine\\Form\\Validator;
use BonyadAlavi\\FormEngine\\Validation\\ValidatorDefinition;
use BonyadAlavi\\FormEngine\\Validation\\ValidatorRegistry;

$registry = new ValidatorRegistry();
$registry->register(new ValidatorDefinition(
    'matches_source',
    'Matches another field',
    ['text'],
    'مقدار فیلد با مقدار مرجع یکسان نیست.',
    static function (string $value, array $field, array $config, array $data): bool {
        return $value === (string)($data[(string)($config['source_field'] ?? '')] ?? '');
    }
));
$registry->register(new ValidatorDefinition(
    'legacy_three_arguments',
    'Legacy callback compatibility',
    ['text'],
    'نامعتبر',
    static fn(string $value, array $field, array $config): bool => $value === 'ok'
));

$form = ['steps' => [[
    'items' => [
        ['name' => 'source', 'type' => 'text', 'required' => true],
        ['name' => 'confirm', 'type' => 'text', 'required' => true, 'validators' => [
            ['key' => 'matches_source', 'source_field' => 'source'],
        ]],
        ['name' => 'legacy', 'type' => 'text', 'required' => true, 'validators' => ['legacy_three_arguments']],
    ],
]]];
$validator = new Validator($registry);
$valid = $validator->validate($form, ['source' => 'same', 'confirm' => 'same', 'legacy' => 'ok']);
$invalid = $validator->validate($form, ['source' => 'same', 'confirm' => 'different', 'legacy' => 'ok']);

$checks = [
    'cross-field-context-accepted' => $valid === [],
    'cross-field-context-rejected' => isset($invalid['confirm']),
    'legacy-three-argument-callback-compatible' => !isset($valid['legacy']),
];
foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}
