<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Form/Form.php';

use BonyadAlavi\FormEngine\Form\Form;

$default=Form::make('default')->toArray();
$hidden=Form::make('hidden')->hideBrandMark()->toArray();
$image=Form::make('image')->brandMarkImage('https://example.test/mark.png','نشان تست')->toArray();
$reset=Form::make('reset')->brandMark('none')->brandMark('default')->toArray();

$cases=[
    'default-mode'=>(($default['settings']['brand_mark_mode']??'')==='default'),
    'hidden-mode'=>(($hidden['settings']['brand_mark_mode']??'')==='none'),
    'image-mode'=>(($image['settings']['brand_mark_mode']??'')==='image'),
    'image-url'=>(($image['settings']['brand_mark_image_url']??'')==='https://example.test/mark.png'),
    'image-alt'=>(($image['settings']['brand_mark_alt']??'')==='نشان تست'),
    'reset-default'=>(($reset['settings']['brand_mark_mode']??'')==='default'),
];

$failed=false;
foreach($cases as $name=>$ok){
    echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
    if(!$ok) $failed=true;
}
exit($failed?1:0);
