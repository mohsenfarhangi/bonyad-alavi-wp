<?php
declare( strict_types=1 );

const ARRAY_A     = 'ARRAY_A';
const MB_IN_BYTES = 1048576;

function sanitize_key( string $key ): string {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ) ?? '';
}

function sanitize_text_field( string $value ): string {
	return trim( $value );
}

function wp_unslash( mixed $value ): mixed {
	return $value;
}

final class FakeWpdb {
	public string $prefix = 'wp_';
	public array $rows = [
		[ 'id' => 11, 'submission_id' => 7, 'field_key' => 'leader_photo', 'attachment_id' => 101 ],
		[ 'id' => 22, 'submission_id' => 7, 'field_key' => 'deputy_photo', 'attachment_id' => 202 ],
	];

	public function prepare( string $sql, mixed ...$args ): array {
		return [ 'sql' => $sql, 'args' => $args ];
	}

	public function get_results( array $prepared, mixed $mode = null ): array {
		$args = $prepared['args'];
		if ( str_contains( $prepared['sql'], 'submission_id=%d AND field_key=%s' ) ) {
			[ $sid, $field ] = $args;

			return array_values( array_filter( $this->rows, fn( $r ) => $r['submission_id'] === $sid && $r['field_key'] === $field ) );
		}

		return [];
	}
}

$wpdb = new FakeWpdb();

require dirname( __DIR__ ) . '/src/Submission/FileUploader.php';
$uploader = new BonyadAlavi\FormEngine\Submission\FileUploader();
$form     = [
	'steps' => [
		[
			'items' => [
				[
					'type'        => 'file',
					'name'        => 'leader_photo',
					'label'       => 'leader',
					'required'    => true,
					'max_files'   => 1,
					'max_size_mb' => 5,
					'accept'      => []
				],
				[
					'type'        => 'file',
					'name'        => 'deputy_photo',
					'label'       => 'deputy',
					'required'    => false,
					'max_files'   => 1,
					'max_size_mb' => 5,
					'accept'      => []
				],
			]
		]
	]
];

// Exact field ownership: keeping deputy id under leader must NOT satisfy leader.
$_FILES = [];
$_POST  = [
	'afe_existing_manifest' => [ 'leader_photo' => '1', 'deputy_photo' => '1' ],
	'afe_keep_files'        => [ 'leader_photo' => [ '22' ] ]
];
$errors = $uploader->validate( $form, 7, false );
if ( ! isset( $errors['leader_photo'] ) ) {
	fwrite( STDERR, "cross-field id was incorrectly accepted\n" );
	exit( 1 );
}

// Correct leader file satisfies required field; deputy remains independent.
$_POST  = [
	'afe_existing_manifest' => [ 'leader_photo' => '1', 'deputy_photo' => '1' ],
	'afe_keep_files'        => [ 'leader_photo' => [ '11' ] ]
];
$errors = $uploader->validate( $form, 7, false );
if ( $errors ) {
	fwrite( STDERR, "valid exact-field existing file rejected: " . json_encode( $errors ) . "\n" );
	exit( 1 );
}

// Single-file replacement: old unchecked + one new file is exactly one effective file.
$_POST  = [ 'afe_existing_manifest' => [ 'leader_photo' => '1', 'deputy_photo' => '1' ], 'afe_keep_files' => [] ];
$_FILES = [
	'afe_files' => [
		'name'     => [ 'leader_photo' => [ 'new.jpg' ] ],
		'type'     => [ 'leader_photo' => [ 'image/jpeg' ] ],
		'tmp_name' => [ 'leader_photo' => [ '/tmp/fake' ] ],
		'error'    => [ 'leader_photo' => [ 0 ] ],
		'size'     => [ 'leader_photo' => [ 1234 ] ],
	]
];
$errors = $uploader->validate( $form, 7, false );
if ( $errors ) {
	fwrite( STDERR, "replacement incorrectly rejected: " . json_encode( $errors ) . "\n" );
	exit( 1 );
}

// Manipulated request keeping old + adding new must be rejected as max_files=1.
$_POST  = [ 'afe_existing_manifest' => [ 'leader_photo' => '1' ], 'afe_keep_files' => [ 'leader_photo' => [ '11' ] ] ];
$errors = $uploader->validate( $form, 7, false );
if ( ! isset( $errors['leader_photo'] ) ) {
	fwrite( STDERR, "old+new overflow was not rejected\n" );
	exit( 1 );
}

echo "file ownership tests passed\n";
