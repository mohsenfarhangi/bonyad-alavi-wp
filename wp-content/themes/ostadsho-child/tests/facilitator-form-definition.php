<?php
declare( strict_types=1 );

$pluginRoot = dirname( __DIR__, 3 ) . '/plugins/alavi-form-engine';

spl_autoload_register( static function ( string $class ) use ( $pluginRoot ): void {
	$prefix = 'BonyadAlavi\\FormEngine\\';
	if ( ! str_starts_with( $class, $prefix ) ) {
		return;
	}
	$file = $pluginRoot . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
	if ( is_readable( $file ) ) {
		require_once $file;
	}
} );

require_once dirname( __DIR__ ) . '/inc/forms/class-ba-facilitator-recruitment-form.php';

use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Form\Validator;
use BonyadAlavi\FormEngine\Validation\ValidatorRegistry;
use BonyadAlavi\FormEngine\Security\Validators;

$registry = new FormRegistry();
BA_Facilitator_Recruitment_Form::register( $registry );
$form = $registry->get( 'facilitator-recruitment' )->toArray();

$fields = [];
foreach ( $form['steps'] as $step ) {
	foreach ( $step['items'] as $field ) {
		$fields[ $field['name'] ] = $field;
	}
}

$required = [ 'full_name', 'national_id', 'gender', 'mobile', 'landline', 'education', 'province', 'county', 'residential_address' ];
$optional = [ 'description', 'resume' ];
$valid = [
	'full_name' => 'علی رضایی',
	'national_id' => '2150338106',
	'gender' => 'male',
	'mobile' => '09123456789',
	'landline' => '02112345678',
	'education' => 'bachelor',
	'province' => 'tehran',
	'county' => 'tehran',
	'residential_address' => 'تهران، خیابان نمونه',
	'description' => '',
];
$validator = new Validator( new ValidatorRegistry() );
$validErrors = $validator->validate( $form, $valid );
$missingErrors = $validator->validate( $form, [] );
$invalid = $valid;
$invalid['national_id'] = '1234567890';
$invalid['mobile'] = '08123456789';
$invalid['landline'] = '09123456789';
$invalidErrors = $validator->validate( $form, $invalid );

$themeRoot = dirname( __DIR__ );
$functions = file_get_contents( $themeRoot . '/functions.php' );
$css = file_get_contents( $themeRoot . '/assets/css/ba-facilitator-recruitment-form.css' );
$js = file_get_contents( $themeRoot . '/assets/js/ba-facilitator-recruitment-form.js' );
$duplicate = $form['settings']['duplicate'] ?? [];

$checks = [
	'correct-slug-and-title' => $form['slug'] === 'facilitator-recruitment' && $form['title'] === 'جذب تسهیلگر',
	'single-step-eleven-fields' => count( $form['steps'] ) === 1 && count( $fields ) === 11,
	'nine-required' => count( array_filter( $required, static fn( string $k ): bool => !empty( $fields[$k]['required'] ) ) ) === 9,
	'optional-description-and-resume' => count( array_filter( $optional, static fn( string $k ): bool => empty( $fields[$k]['required'] ) ) ) === 2,
	'national-id-rule-and-mask' => !empty( $fields['national_id']['rules']['national_id'] ) && ( $fields['national_id']['input_mask']['key'] ?? '' ) === 'national_id_ir',
	'mobile-rule-and-mask' => !empty( $fields['mobile']['rules']['mobile_09'] ) && ( $fields['mobile']['input_mask']['key'] ?? '' ) === 'mobile_ir',
	'landline-registered-validator' => ( $fields['landline']['validators'][0]['key'] ?? '' ) === 'landline' && ( $fields['landline']['validators'][1]['key'] ?? '' ) === 'custom_regex',
	'gender-options' => ( $fields['gender']['options'] ?? [] ) === [ 'male' => 'مرد', 'female' => 'زن' ],
	'education-options' => count( $fields['education']['options'] ?? [] ) === 6,
	'province-source' => ( $fields['province']['source']['type'] ?? '' ) === 'geo' && ( $fields['province']['source']['level'] ?? '' ) === 'province',
	'county-dependency' => ( $fields['county']['source']['level'] ?? '' ) === 'county' && ( $fields['county']['source']['parent'] ?? '' ) === 'province' && ( $fields['county']['depends_on'] ?? '' ) === 'province',
	'optional-resume-pdf-word-5mb' => ( $fields['resume']['max_files'] ?? 0 ) === 1 && ( $fields['resume']['max_size_mb'] ?? 0 ) === 5 && count( $fields['resume']['accept'] ?? [] ) === 3,
	'duplicate-by-national-id-block' => !empty( $duplicate['enabled'] ) && $duplicate['fields'] === [ 'national_id' ] && $duplicate['behavior'] === 'block',
	'no-drafts-or-public-edit' => empty( $form['settings']['wizard'] ) && empty( $form['settings']['save_draft'] ) && empty( $form['settings']['editing_enabled'] ) && empty( $form['settings']['preview_enabled'] ),
	'engine-shared-storage' => $form['storage'] === 'shared',
	'persian-national-and-telephone-values' => Validators::nationalId( '۲۱۵۰۳۳۸۱۰۶' ) && Validators::mobile09( '۰۹۱۲۳۴۵۶۷۸۹' ) && Validators::iranLandline( '۰۲۱۱۲۳۴۵۶۷۸' ),
	'valid-fields-accepted' => $validErrors === [],
	'missing-fields-rejected' => count( $missingErrors ) === 9,
	'invalid-identifiers-rejected' => isset( $invalidErrors['national_id'], $invalidErrors['mobile'], $invalidErrors['landline'] ),
	'no-tracking-in-form-template' => !str_contains( $form['template'] ?? '', '{{tracking_code}}' ),
	'tracking-hidden-in-public-success' => str_contains( (string)$css, '.afe-success > span' ) && str_contains( (string)$js, 'success.replaceChildren()' ),
	'theme-hook-registration' => is_string( $functions ) && str_contains( $functions, 'BA_Facilitator_Recruitment_Form::class' ) && str_contains( $functions, 'inc/forms/class-ba-facilitator-recruitment-form.php' ),
	'theme-assets-loaded' => is_string( $functions ) && str_contains( $functions, 'ba-facilitator-recruitment-form.css' ) && str_contains( $functions, 'ba-facilitator-recruitment-form.js' ),
];

foreach ( $checks as $name => $ok ) {
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $name . PHP_EOL;
	if ( ! $ok ) {
		exit( 1 );
	}
}
