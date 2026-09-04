<?php
declare(strict_types=1);

function wp_salt(string $scheme='auth'): string { return 'fixed-test-salt-'.$scheme; }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Core\SecretStore;

$store=new SecretStore();
$encrypted=$store->available()?$store->encrypt('P@ssw0rd!'):'';
$checks=[
    'openssl-available'=>$store->available(),
    'not-plaintext'=>$encrypted!=='' && !str_contains($encrypted,'P@ssw0rd!'),
    'marked-encrypted'=>$store->isEncrypted($encrypted),
    'roundtrip'=>$store->decrypt($encrypted)==='P@ssw0rd!',
    'plaintext-compat'=>$store->decrypt('legacy-value')==='legacy-value',
];
$failed=false;
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok)$failed=true; }
exit($failed?1:0);
