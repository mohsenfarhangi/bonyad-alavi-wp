<?php
declare(strict_types=1);

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Validation\ValidatorRegistry;
use BonyadAlavi\FormEngine\Security\Validators;

$registry=new ValidatorRegistry();
$checks=[
    'registry-core-keys'=>count(array_intersect(['national_id','mobile','iban','email','url','postal_code','bank_card','landline','date','custom_regex'],array_keys($registry->all())))===10,
    'text-supports-national'=>isset($registry->forFieldType('text')['national_id']),
    'date-supports-date'=>isset($registry->forFieldType('date')['date']),
    'date-no-bank-card'=>!isset($registry->forFieldType('date')['bank_card']),
    'postal-valid'=>Validators::iranPostalCode('۱۲۳۴۵۶۷۸۹۰'),
    'postal-invalid'=>!Validators::iranPostalCode('12345'),
    'card-valid'=>Validators::iranBankCard('6037991234567893'),
    'card-invalid'=>!Validators::iranBankCard('6037991234567894'),
    'landline-valid'=>Validators::iranLandline('021-88776655'),
    'landline-mobile-reject'=>!Validators::iranLandline('09121234567'),
    'regex-flags'=>ValidatorRegistry::sanitizeRegexFlags('MIXq')==='mixu',
    'regex-compile'=>ValidatorRegistry::compileRegex('^[A-Z]{2}[0-9]+$','iu')!==null,
    'regex-too-long'=>ValidatorRegistry::compileRegex(str_repeat('a',ValidatorRegistry::CUSTOM_REGEX_MAX_LENGTH+1),'u')===null,
];
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok) exit(1); }
