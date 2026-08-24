<?php
declare(strict_types=1);

function get_locale(){return 'en_US';}
function apply_filters($hook,$value){return $value;}
function wp_timezone(){return new DateTimeZone('Asia/Tehran');}
function get_option($key,$default=null){if($key==='date_format')return 'Y-m-d';if($key==='time_format')return 'H:i';return $default;}
function wp_date($format,$timestamp,$timezone){return (new DateTimeImmutable('@'.$timestamp))->setTimezone($timezone)->format($format);}
function sanitize_text_field($value){return trim((string)$value);}

require_once __DIR__.'/../src/Localization/LocaleDateService.php';
use BonyadAlavi\FormEngine\Localization\LocaleDateService;

$d=new LocaleDateService();
$cases=[
    ['calendar', $d->calendar()==='gregorian'],
    ['jalali-source-to-gregorian', $d->formatFormDate('1405/06/01','jalali')==='2026-08-23'],
    ['gregorian-edit-to-jalali-storage', $d->normalizeEditedFormDate('2026-08-23','jalali')==='1405/06/01'],
    ['local-day-start-to-utc', $d->filterBoundary('2026-08-23',false)==='2026-08-22 20:30:00'],
];
$failed=array_filter($cases,static fn($case)=>!$case[1]);
foreach($cases as [$name,$ok]) echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
exit($failed?1:0);
