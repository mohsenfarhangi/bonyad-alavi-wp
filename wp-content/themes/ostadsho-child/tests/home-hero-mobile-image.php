<?php
declare( strict_types=1 );

namespace Elementor {
	class Widget_Base {}
}

namespace {
	define( 'ABSPATH', __DIR__ );

	function absint( $value ): int {
		return abs( (int) $value );
	}
	function esc_attr( $value ): string {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}
	function esc_html( $value ): string {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}
	function esc_url( $value ): string {
		$url = (string) $value;
		return preg_match( '~^https?://~', $url ) ? htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' ) : '';
	}
	function get_post_meta( $id, $key, $single ): string {
		return 10 === (int) $id ? 'متن جایگزین دسکتاپ' : '';
	}
	function wp_get_attachment_image( $id, $size, $icon, $attributes ): string {
		$url = wp_get_attachment_image_url( (int) $id, (string) $size );
		if ( false === $url ) {
			return '';
		}
		return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $attributes['alt'] ?? '' )
			. '" loading="' . esc_attr( $attributes['loading'] ?? '' )
			. '" decoding="' . esc_attr( $attributes['decoding'] ?? '' ) . '">';
	}
	function wp_get_attachment_image_url( $id, $size ) {
		return array(
			10 => 'https://example.test/desktop-attachment.jpg',
			20 => 'https://example.test/mobile-attachment.jpg',
		)[(int) $id] ?? false;
	}
	function wp_get_attachment_image_srcset( $id, $size ) {
		return 20 === (int) $id
			? 'https://example.test/mobile-320.jpg 320w, https://example.test/mobile-760.jpg 760w'
			: false;
	}

	require_once __DIR__ . '/../inc/helpers/class-ba-media-helper.php';
	require_once __DIR__ . '/../inc/elementor/widgets/class-bonyad-alavi-home-hero-widget.php';

	$widget = ( new \ReflectionClass( \Bonyad_Alavi_Home_Hero_Widget::class ) )->newInstanceWithoutConstructor();
	$method = new \ReflectionMethod( $widget, 'render_slide' );

	$render = static function ( array $slide, int $index = 0 ) use ( $widget, $method ): string {
		ob_start();
		$method->invoke( $widget, $slide, $index, array( 'slider_title_tag_reference' => 'reference' ) );
		return (string) ob_get_clean();
	};

	$desktop = array(
		'image' => array( 'url' => 'https://example.test/desktop-url.jpg' ),
		'title' => 'عنوان اسلاید',
		'button_text' => '',
		'link' => array( 'url' => '' ),
	);

	$legacy = $render( $desktop );
	$mobile_url = $desktop;
	$mobile_url['mobile_image'] = array( 'url' => 'https://example.test/mobile-url.jpg' );
	$responsive = $render( $mobile_url );
	$with_attachment = $desktop;
	$with_attachment['image'] = array( 'id' => 10, 'url' => 'https://example.test/desktop-attachment.jpg' );
	$with_attachment['mobile_image'] = array( 'id' => 20, 'url' => 'https://example.test/mobile-attachment.jpg' );
	$attachment_output = $render( $with_attachment, 1 );

	$empty_mobile = $desktop;
	$empty_mobile['mobile_image'] = array( 'id' => 0, 'url' => '' );
	$without_mobile = $render( $empty_mobile );
	$no_desktop = $render( array( 'mobile_image' => array( 'url' => 'https://example.test/mobile-url.jpg' ) ) );

	$checks = array(
		'legacy-desktop-only-image-preserved' => str_contains( $legacy, '<img src="https://example.test/desktop-url.jpg"' )
			&& ! str_contains( $legacy, '<picture' )
			&& ! str_contains( $legacy, '<source' ),
		'optional-empty-mobile-preserves-legacy-output' => $without_mobile === $legacy,
		'mobile-source-paired-with-desktop-fallback' => str_contains( $responsive, '<picture class="ba-slider__picture">' )
			&& str_contains( $responsive, '<source media="(max-width: 760px)" srcset="https://example.test/mobile-url.jpg">' )
			&& str_contains( $responsive, '<img src="https://example.test/desktop-url.jpg"' ),
		'attachment-mobile-responsive-srcset' => str_contains( $attachment_output, 'mobile-320.jpg 320w' )
			&& str_contains( $attachment_output, 'mobile-760.jpg 760w' )
			&& str_contains( $attachment_output, 'sizes="100vw"' ),
		'attachment-desktop-image-keeps-wordpress-helper' => str_contains( $attachment_output, '<img src="https://example.test/desktop-attachment.jpg"' )
			&& str_contains( $attachment_output, 'alt="متن جایگزین دسکتاپ"' ),
		'eager-and-lazy-loading-preserved' => str_contains( $legacy, 'loading="eager"' )
			&& str_contains( $attachment_output, 'loading="lazy"' )
			&& str_contains( $attachment_output, 'decoding="async"' ),
		'swiper-slide-and-title-preserved' => str_contains( $responsive, 'ba-slider__slide swiper-slide' )
			&& str_contains( $responsive, '<h1 class="ba-slider__title">عنوان اسلاید</h1>' ),
		'no-empty-picture-without-desktop' => ! str_contains( $no_desktop, '<picture' )
			&& ! str_contains( $no_desktop, '<source' ),
	);

	foreach ( $checks as $name => $passed ) {
		echo ( $passed ? 'PASS ' : 'FAIL ' ) . $name . PHP_EOL;
		if ( ! $passed ) {
			exit( 1 );
		}
	}
}
