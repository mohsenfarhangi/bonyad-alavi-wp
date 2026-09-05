<?php
declare( strict_types=1 );

$root      = dirname( __DIR__ );
$frontend  = (string) file_get_contents( $root . '/assets/css/frontend.css' );
$admin     = (string) file_get_contents( $root . '/assets/css/admin.css' );
$renderer  = (string) file_get_contents( $root . '/src/Form/Renderer.php' );
$presenter = (string) file_get_contents( $root . '/src/Admin/FormDataPresenter.php' );

$checks = [
	'frontend-numeric-selector' => str_contains( $frontend, '[inputmode="numeric"]' ) && str_contains( $frontend, '[type="tel"]' ) && str_contains( $frontend, '.afe-date' ),
	'frontend-ltr'              => str_contains( $frontend, 'direction: ltr' ) && str_contains( $frontend, 'text-align: left' ),
	'strong-isolation-ltr'      => preg_match( '/afe-isolation-strong[^\{]*\.afe-control:is\([^\)]*inputmode="numeric"[^\)]*\)[^\{]*\{[^\}]*direction:\s*ltr\s*!important;[^\}]*text-align:\s*left\s*!important;/s', $frontend ) === 1,
	'admin-ltr-selector'        => str_contains( $admin, '.afe-admin-control:is([dir="ltr"], [type="tel"], [type="number"], [inputmode="numeric"]' ),
	'renderer-semantic-dir'     => str_contains( $renderer, "\$attributes['dir']='ltr';" ),
	'admin-semantic-dir'        => str_contains( $presenter, "\$attrs.=' dir=\"ltr\"';" ),
];

$failed = false;
foreach ( $checks as $name => $ok ) {
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $name . PHP_EOL;
	if ( ! $ok ) {
		$failed = true;
	}
}
exit( $failed ? 1 : 0 );
