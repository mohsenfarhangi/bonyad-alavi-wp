<?php
declare(strict_types=1);

function get_option(string $name, mixed $default=null): mixed { return $default; }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Forms\JihadiGroupRegistrationForm;
use BonyadAlavi\FormEngine\Security\Validators;

$form=(new JihadiGroupRegistrationForm())->build()->toArray();
$fields=[];
foreach($form['steps'] as $step) foreach($step['items'] as $field) if(!empty($field['name'])) $fields[$field['name']]=$field;

$coverage=$fields['coverage_areas']??null;
$children=[];
foreach(($coverage['fields']??[]) as $child) $children[$child['name']]=$child;

$checks=[
    'title'=>str_contains($form['title'],'شناسنامه گروه‌های مردمی و جهادی') && str_contains($form['title'],'شهید رسول عالم باقری'),
    'slogan'=>!empty($form['settings']['header_slogan']),
    'mobile-strict'=>($fields['group_mobile']['rules']['mobile_09']??false)===true && ($fields['group_mobile']['attributes']['maxlength']??0)===11,
    'mobile-mask-default'=>($fields['group_mobile']['input_mask']['key']??'')==='mobile_ir' && ($fields['leader_mobile']['input_mask']['key']??'')==='mobile_ir',
    'national-id-mask-default'=>($fields['leader_national_id']['input_mask']['key']??'')==='national_id_ir',
    'iban-mask-default'=>($fields['legal_iban']['input_mask']['key']??'')==='iban_digits_ir',
    'iban-optional'=>empty($fields['legal_iban']['required']) && ($fields['legal_iban']['rules']['iban_digits']??false)===true && ($fields['legal_iban']['input_prefix']??'')==='IR',
    'national-id-10'=>($fields['leader_national_id']['attributes']['maxlength']??0)===10 && ($fields['deputy_national_id']['attributes']['maxlength']??0)===10,
    'council-label'=>($fields['central_council']['label']??'')==='اعضای شورای مرکزی (هسته اصلی)',
    'coverage-repeater'=>($coverage['type']??'')==='repeater' && ($coverage['min']??0)===1,
    'coverage-county-optional'=>isset($children['county']) && empty($children['county']['required']),
    'coverage-district-optional'=>isset($children['district']) && empty($children['district']['required']),
    'coverage-row-deps'=>($children['county']['depends_on']??'')==='province' && ($children['district']['depends_on']??'')==='county',
    'national-reject-letters'=>Validators::nationalId('abc2150338106')===false,
    'iban-digits-valid'=>Validators::iranIbanDigits('290570077700008623889001')===true,
    'mobile09-valid'=>Validators::mobile09('09360998864')===true,
    'mobile09-invalid'=>Validators::mobile09('989360998864')===false,
];
foreach($checks as $name=>$ok){ if(!$ok){fwrite(STDERR,"FAIL $name\n"); exit(1);} echo "PASS $name\n"; }
