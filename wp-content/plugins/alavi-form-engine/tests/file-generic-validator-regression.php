<?php
declare(strict_types=1);

// Regression: required FileField must NOT be validated by the generic afe_data validator.
// FileUploader owns validation for uploaded/existing files.

function is_email(string $value): bool { return filter_var($value, FILTER_VALIDATE_EMAIL) !== false; }

require_once __DIR__ . '/../src/Security/Validators.php';
require_once __DIR__ . '/../src/Form/Validator.php';

use BonyadAlavi\FormEngine\Form\Validator;

$form = [
    'steps' => [[
        'items' => [
            ['type' => 'file', 'name' => 'leader_photo', 'label' => 'عکس مسئول', 'required' => true],
            ['type' => 'file', 'name' => 'licenses', 'label' => 'مجوزها', 'required' => true],
            ['type' => 'text', 'name' => 'group_name', 'label' => 'نام گروه', 'required' => true],
        ],
    ]],
];

$validator = new Validator();
$errors = $validator->validate($form, [], false);

if (isset($errors['leader_photo']) || isset($errors['licenses'])) {
    fwrite(STDERR, "FAIL: generic validator produced a file-field error\n");
    var_export($errors);
    exit(1);
}
if (($errors['group_name'] ?? '') === '') {
    fwrite(STDERR, "FAIL: generic validator stopped validating normal fields\n");
    exit(1);
}

echo "PASS: generic validator excludes FileField while normal required validation still works.\n";
