<?php
declare(strict_types=1);

function get_locale(){return 'fa_IR';}
function apply_filters($hook,$value){return $value;}
function wp_timezone(){return new DateTimeZone('Asia/Tehran');}
function get_option($key,$default=null){return $default;}
function wp_date($format,$timestamp,$timezone){return (new DateTimeImmutable('@'.$timestamp))->setTimezone($timezone)->format($format);}
function sanitize_text_field($value){return trim((string)$value);}

require_once __DIR__.'/../src/Localization/LocaleDateService.php';
use BonyadAlavi\FormEngine\Localization\LocaleDateService;

$d=new LocaleDateService();
$cases=[
    ['calendar', $d->calendar()==='jalali'],
    ['gregorian-to-jalali', $d->formatFormDate('2026-08-23','gregorian')==='1405/06/01'],
    ['jalali-edit-to-gregorian-storage', $d->normalizeEditedFormDate('1405/06/01','gregorian')==='2026-08-23'],
    ['local-day-start-to-utc', $d->filterBoundary('1405/06/01',false)==='2026-08-22 20:30:00'],
    ['invalid-jalali-rejected', $d->filterBoundary('1405/13/01',false)===null],
];
$failed=array_filter($cases,static fn($case)=>!$case[1]);
foreach($cases as [$name,$ok]) echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
exit($failed?1:0);
