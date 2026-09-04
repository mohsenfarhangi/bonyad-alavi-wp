<?php
declare(strict_types=1);

require_once __DIR__.'/../src/Form/Fields/AbstractField.php';
require_once __DIR__.'/../src/Form/Fields/DateField.php';

use BonyadAlavi\FormEngine\Form\Fields\DateField;

$default=DateField::make('d')->jalali()->toArray();
$picker=DateField::make('p')->gregorian()->pickerOnly()->toArray();
$manual=DateField::make('m')->jalali()->manualOnly()->toArray();
$checks=[
    'default-combined'=>($default['date_input_mode']??'')==='combined',
    'picker-mode'=>($picker['date_input_mode']??'')==='picker' && ($picker['calendar']??'')==='gregorian',
    'manual-mode'=>($manual['date_input_mode']??'')==='manual' && ($manual['calendar']??'')==='jalali',
];
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok) exit(1); }
