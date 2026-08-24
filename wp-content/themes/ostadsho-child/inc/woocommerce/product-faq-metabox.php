<?php
/**
 * Product-specific FAQ metabox for WooCommerce products.
 * Place in: /wp-content/themes/YOUR-CHILD-THEME/inc/product-faq-metabox.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BAP_PRODUCT_FAQ_META_KEY' ) ) {
	define( 'BAP_PRODUCT_FAQ_META_KEY', '_bap_product_faqs' );
}

/**
 * Register the metabox on WooCommerce products.
 */
function bap_register_product_faq_metabox() {
	add_meta_box(
		'bap-product-faqs',
		esc_html__( 'پرسش‌های متداول اختصاصی پروژه', 'bonyad-alavi-child' ),
		'bap_render_product_faq_metabox',
		'product',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes_product', 'bap_register_product_faq_metabox' );

/**
 * Read the saved FAQ list from the WooCommerce product.
 */
function bap_get_product_faqs_for_admin( $post_id ) {
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : false;
	$items   = $product
		? $product->get_meta( BAP_PRODUCT_FAQ_META_KEY, true )
		: get_post_meta( $post_id, BAP_PRODUCT_FAQ_META_KEY, true );

	return is_array( $items ) ? $items : array();
}

/**
 * Render one FAQ row. The index may be an integer or the __INDEX__ template token.
 */
function bap_render_product_faq_row( $index, $item = array() ) {
	$question = isset( $item['question'] ) ? (string) $item['question'] : '';
	$answer   = isset( $item['answer'] ) ? (string) $item['answer'] : '';
	?>
	<tr data-bap-faq-row>
		<td style="width:34%;vertical-align:top">
			<label class="screen-reader-text" for="bap-faq-question-<?php echo esc_attr( $index ); ?>">
				<?php esc_html_e( 'سؤال', 'bonyad-alavi-child' ); ?>
			</label>
			<input
				id="bap-faq-question-<?php echo esc_attr( $index ); ?>"
				class="widefat"
				type="text"
				name="bap_product_faqs[<?php echo esc_attr( $index ); ?>][question]"
				value="<?php echo esc_attr( $question ); ?>"
				placeholder="<?php echo esc_attr__( 'متن سؤال', 'bonyad-alavi-child' ); ?>"
			>
		</td>
		<td style="vertical-align:top">
			<label class="screen-reader-text" for="bap-faq-answer-<?php echo esc_attr( $index ); ?>">
				<?php esc_html_e( 'پاسخ', 'bonyad-alavi-child' ); ?>
			</label>
			<textarea
				id="bap-faq-answer-<?php echo esc_attr( $index ); ?>"
				class="widefat"
				rows="4"
				name="bap_product_faqs[<?php echo esc_attr( $index ); ?>][answer]"
				placeholder="<?php echo esc_attr__( 'پاسخ سؤال؛ استفاده از HTML امن مجاز است.', 'bonyad-alavi-child' ); ?>"
			><?php echo esc_textarea( $answer ); ?></textarea>
		</td>
		<td style="width:132px;vertical-align:top;white-space:nowrap">
			<button type="button" class="button button-small" data-bap-faq-up aria-label="<?php echo esc_attr__( 'انتقال به بالا', 'bonyad-alavi-child' ); ?>">↑</button>
			<button type="button" class="button button-small" data-bap-faq-down aria-label="<?php echo esc_attr__( 'انتقال به پایین', 'bonyad-alavi-child' ); ?>">↓</button>
			<button type="button" class="button button-small button-link-delete" data-bap-faq-remove>
				<?php esc_html_e( 'حذف', 'bonyad-alavi-child' ); ?>
			</button>
		</td>
	</tr>
	<?php
}

/**
 * Render the complete metabox.
 */
function bap_render_product_faq_metabox( $post ) {
	$items = bap_get_product_faqs_for_admin( $post->ID );

	wp_nonce_field( 'bap_save_product_faqs', 'bap_product_faqs_nonce' );
	?>
	<div id="bap-product-faqs" data-next-index="<?php echo esc_attr( count( $items ) ); ?>">
		<p>
			<?php esc_html_e( 'این سؤال‌ها در صفحه مشارکت مردمی نمایش داده میشوند', 'bonyad-alavi-child' ); ?>
		</p>

		<input type="hidden" name="bap_product_faqs_present" value="1">

		<table class="widefat striped">
			<thead>
			<tr>
				<th><?php esc_html_e( 'سؤال', 'bonyad-alavi-child' ); ?></th>
				<th><?php esc_html_e( 'پاسخ', 'bonyad-alavi-child' ); ?></th>
				<th><?php esc_html_e( 'عملیات', 'bonyad-alavi-child' ); ?></th>
			</tr>
			</thead>
			<tbody data-bap-faq-rows>
			<?php foreach ( $items as $index => $item ) : ?>
				<?php bap_render_product_faq_row( $index, $item ); ?>
			<?php endforeach; ?>
			</tbody>
		</table>

		<p>
			<button type="button" class="button button-primary" data-bap-faq-add>
				<?php esc_html_e( 'افزودن سؤال اختصاصی', 'bonyad-alavi-child' ); ?>
			</button>
		</p>

		<template data-bap-faq-template>
			<?php bap_render_product_faq_row( '__INDEX__' ); ?>
		</template>
	</div>
	<?php
}

/**
 * Save, sanitize or remove the product-specific FAQ list.
 */
function bap_save_product_faqs( $post_id, $post ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['bap_product_faqs_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['bap_product_faqs_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'bap_save_product_faqs' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['bap_product_faqs_present'] ) ) {
		return;
	}

	$raw_items = isset( $_POST['bap_product_faqs'] )
		? wp_unslash( $_POST['bap_product_faqs'] )
		: array();

	$items = array();

	if ( is_array( $raw_items ) ) {
		foreach ( $raw_items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$question = isset( $item['question'] )
				? sanitize_text_field( $item['question'] )
				: '';

			$answer = isset( $item['answer'] )
				? wp_kses_post( $item['answer'] )
				: '';

			if ( '' === $question || '' === trim( wp_strip_all_tags( $answer ) ) ) {
				continue;
			}

			$items[] = array(
				'question' => $question,
				'answer'   => $answer,
			);
		}
	}

	$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : false;

	if ( $product ) {
		if ( $items ) {
			$product->update_meta_data( BAP_PRODUCT_FAQ_META_KEY, $items );
		} else {
			$product->delete_meta_data( BAP_PRODUCT_FAQ_META_KEY );
		}

		$product->save_meta_data();
		return;
	}

	if ( $items ) {
		update_post_meta( $post_id, BAP_PRODUCT_FAQ_META_KEY, $items );
	} else {
		delete_post_meta( $post_id, BAP_PRODUCT_FAQ_META_KEY );
	}
}
add_action( 'save_post_product', 'bap_save_product_faqs', 10, 2 );

/**
 * Load the repeater script only on add/edit product screens.
 */
function bap_enqueue_product_faq_admin_script( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || 'product' !== $screen->post_type ) {
		return;
	}

	$relative_path = 'assets/js/bonyad-alavi-product-faq-admin.js';
	$file_path     = trailingslashit( get_stylesheet_directory() ) . $relative_path;
	$version       = file_exists( $file_path ) ? (string) filemtime( $file_path ) : '1.0.0';

	wp_enqueue_script(
		'bonyad-alavi-product-faq-admin',
		trailingslashit( get_stylesheet_directory_uri() ) . $relative_path,
		array(),
		$version,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'bap_enqueue_product_faq_admin_script' );