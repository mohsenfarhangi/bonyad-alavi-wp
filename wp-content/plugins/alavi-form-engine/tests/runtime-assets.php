<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$plugin=(string)file_get_contents($root.'/src/Core/Plugin.php');
$files=[
    $root.'/assets/vendor/jalalidatepicker/jalalidatepicker.min.js',
    $root.'/assets/vendor/jalalidatepicker/jalalidatepicker.min.css',
    $root.'/assets/js/frontend.js',
    $root.'/assets/css/frontend.css',
];
$checks=[
    'local-jalali-js'=>is_file($files[0]) && filesize($files[0])>1000,
    'local-jalali-css'=>is_file($files[1]) && filesize($files[1])>1000,
    'frontend-js-local'=>is_file($files[2]) && filesize($files[2])>1000,
    'frontend-css-local'=>is_file($files[3]) && filesize($files[3])>1000,
    'no-jalali-cdn-registration'=>!preg_match('~(?:unpkg|jsdelivr|cdnjs)[^\n]*jalali~i',$plugin),
    'registered-from-plugin-url'=>str_contains($plugin,"AFE_URL.'assets/vendor/jalalidatepicker/'"),
];
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok) exit(1); }
