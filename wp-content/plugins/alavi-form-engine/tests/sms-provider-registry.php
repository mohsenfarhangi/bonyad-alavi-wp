<?php
declare(strict_types=1);

function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-\.]/i','',$key)); }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\Sms\SmsMessage;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderInterface;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderRegistry;

final class RegistryProvider implements SmsProviderInterface {
    public function __construct(public string $name) {}
    public function send(SmsMessage $message): array { return ['success'=>true,'message_id'=>$this->name]; }
}

$first=new RegistryProvider('first');
$second=new RegistryProvider('second');
$available=false;
$registry=new SmsProviderRegistry('first',true);
$registry->register('first','اول',$first,['free','pattern']);
$registry->register('second','دوم',$second,static fn(): array=>['free'],static fn(): bool=>$GLOBALS['registry_available']??false);
$GLOBALS['registry_available']=$available;

$defaultResolved=$registry->resolve('default','pattern');
$unavailableCaught=false;
try { $registry->resolve('second','free'); } catch (RuntimeException) { $unavailableCaught=true; }
$GLOBALS['registry_available']=true;
$unsupportedCaught=false;
try { $registry->resolve('second','pattern'); } catch (RuntimeException) { $unsupportedCaught=true; }

$checks=[
    'default-routing'=>$defaultResolved===$first,
    'unavailable-rejected'=>$unavailableCaught,
    'unsupported-mode-rejected'=>$unsupportedCaught,
    'available-free'=>$registry->resolve('second','free')===$second,
    'default-mode-map'=>($registry->actionModeMap()['default']??[])===['free','pattern'],
    'action-options-default'=>str_contains((string)($registry->actionOptions()['default']??''),'اول'),
];
$failed=false;
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok)$failed=true; }
exit($failed?1:0);
