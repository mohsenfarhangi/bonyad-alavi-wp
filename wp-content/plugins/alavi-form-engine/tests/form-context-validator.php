<?php
declare(strict_types=1);

spl_autoload_register(static function(string $class): void {
    $prefix = 'BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Form\Validator;
use BonyadAlavi\FormEngine\Validation\ValidatorDefinition;
use BonyadAlavi\FormEngine\Validation\ValidatorRegistry;

$registry = new ValidatorRegistry();
$registry->register(new ValidatorDefinition(
    'context_amount',
    'Context amount',
    ['number'],
    'Amount outside trusted context limits.',
    static function(string $value, array $field, array $config, array $data, array $context): bool {
        if (empty($context['category_id'])) return false;
        $amount = (float)$value;
        $minimum = (float)($context['minimum_amount'] ?? 0);
        $maximum = (float)($context['maximum_amount'] ?? 0);
        return $amount > 0
            && ($minimum <= 0 || $amount >= $minimum)
            && ($maximum <= 0 || $amount <= $maximum);
    }
));

$form = [
    'steps' => [[
        'items' => [[
            'name'=>'amount',
            'type'=>'number',
            'required'=>true,
            'validators'=>[['key'=>'context_amount']],
        ]],
    ]],
];
$validator = new Validator($registry);
$checks = [
    'valid-context-amount-accepted' => $validator->validate($form, ['amount'=>'500'], false, [
        'category_id'=>12,'minimum_amount'=>100,'maximum_amount'=>1000,
    ]) === [],
    'minimum-enforced' => isset($validator->validate($form, ['amount'=>'50'], false, [
        'category_id'=>12,'minimum_amount'=>100,'maximum_amount'=>1000,
    ])['amount']),
    'maximum-enforced' => isset($validator->validate($form, ['amount'=>'1500'], false, [
        'category_id'=>12,'minimum_amount'=>100,'maximum_amount'=>1000,
    ])['amount']),
    'missing-context-rejected' => isset($validator->validate($form, ['amount'=>'500'])['amount']),
    'legacy-call-without-context-remains-valid-api' => is_array($validator->validate($form, ['amount'=>'500'])),
];

foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}
