<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Security/Validators.php';

use BonyadAlavi\FormEngine\Security\Validators;

$cases = [
    ['iban-valid', Validators::iranIban('IR062960000000100324200001') === true],
    ['iban-invalid', Validators::iranIban('IR062960000000100324200002') === false],
    ['national-valid', Validators::nationalId('1234567891') === true],
    ['national-invalid-repeated', Validators::nationalId('1111111111') === false],
    ['mobile-valid-persian', Validators::mobile('۰۹۱۲۱۲۳۴۵۶۷') === true],
    ['digits', Validators::latinDigits('۱۲۳٤٥') === '12345'],
];

$failed = array_filter($cases, static fn(array $case): bool => !$case[1]);
foreach ($cases as [$name,$ok]) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
}
exit($failed ? 1 : 0);
