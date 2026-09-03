<?php
/**
 * سرویس مشترک تصاویر محصول برای ویجت‌های المنتور قالب.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * سرویس خواندن و نرمال‌سازی تصویر شاخص و گالری تصاویر محصولات ووکامرس.
 *
 * این کلاس تنها مسئول استخراج داده رسانه از محصول است و رندر HTML را به Helper رسانه واگذار می‌کند.
 */
final class BA_Product_Media_Service {

	/**
	 * نمونه یکتای سرویس در طول درخواست جاری.
	 *
	 * @var BA_Product_Media_Service|null
	 */
	private static $instance = null;

	/**
	 * سازنده خصوصی برای پیاده‌سازی Singleton و جلوگیری از نمونه‌های تکراری سرویس.
	 */
	private function __construct() {}

	/**
	 * نمونه مشترک سرویس را برمی‌گرداند.
	 *
	 * @return BA_Product_Media_Service
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * محصول فعال را از context صفحه، لوپ المنتور یا شناسه fallback پیدا می‌کند.
	 *
	 * @param int $fallback_id شناسه محصول جایگزین.
	 *
	 * @return WC_Product|false
	 */
	public function resolve_context_product( $fallback_id = 0 ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return false;
		}

		$candidate_ids   = array();
		$candidate_ids[] = get_the_ID();

		global $product;
		if ( $product instanceof \WC_Product ) {
			$candidate_ids[] = $product->get_id();
		}

		$candidate_ids[] = get_queried_object_id();
		$candidate_ids[] = absint( $fallback_id );

		$candidate_ids = array_unique( array_filter( array_map( 'absint', $candidate_ids ) ) );

		foreach ( $candidate_ids as $candidate_id ) {
			$candidate = wc_get_product( $candidate_id );
			if ( $candidate instanceof \WC_Product ) {
				return $candidate;
			}
		}

		return false;
	}

	/**
	 * تصویر شاخص را در ابتدای آرایه و تصاویر گالری را بعد از آن برمی‌گرداند.
	 *
	 * مقدار صفر برای limit یعنی همه تصاویر قابل استفاده هستند. محدودیت روی کل خروجی، شامل تصویر شاخص، اعمال می‌شود.
	 *
	 * @param WC_Product $product محصول ووکامرس.
	 * @param int        $limit   حداکثر تعداد تصاویر؛ صفر یعنی بدون محدودیت.
	 *
	 * @return array<int,array{id:int,url:string,alt:string,is_featured:bool}>
	 */
	public function get_product_items( \WC_Product $product, $limit = 0 ) {
		$featured_id = absint( $product->get_image_id() );
		$featured_id = $featured_id && wp_attachment_is_image( $featured_id ) ? $featured_id : 0;
		$gallery_ids = $this->get_valid_attachment_ids( $product->get_gallery_image_ids() );
		$media_ids   = array_unique( array_filter( array_merge( array( $featured_id ), $gallery_ids ) ) );
		$items       = array();

		foreach ( $media_ids as $media_id ) {
			$items[] = $this->create_attachment_item( $media_id, $product->get_name(), $media_id === $featured_id );
		}

		$limit = absint( $limit );
		if ( $limit > 0 ) {
			$items = array_slice( $items, 0, $limit );
		}

		return $items;
	}

	/**
	 * مشخص می‌کند آیا محصول حداقل یک تصویر در Product Gallery دارد یا خیر.
	 *
	 * @param WC_Product $product محصول ووکامرس.
	 *
	 * @return bool
	 */
	public function has_gallery( \WC_Product $product ) {
		return ! empty( $this->get_valid_attachment_ids( $product->get_gallery_image_ids() ) );
	}

	/**
	 * شناسه‌های رسانه را به Attachmentهای تصویری معتبر محدود می‌کند.
	 *
	 * @param array $attachment_ids شناسه‌های خام رسانه.
	 *
	 * @return array
	 */
	private function get_valid_attachment_ids( $attachment_ids ) {
		$attachment_ids = array_unique( array_filter( array_map( 'absint', (array) $attachment_ids ) ) );

		return array_values(
			array_filter(
				$attachment_ids,
				static function ( $attachment_id ) {
					return wp_attachment_is_image( $attachment_id );
				}
			)
		);
	}

	/**
	 * آیتم جایگزین ووکامرس را برای محصول بدون تصویر می‌سازد.
	 *
	 * @param WC_Product $product محصول ووکامرس.
	 *
	 * @return array{id:int,url:string,alt:string,is_featured:bool}
	 */
	public function get_placeholder_item( \WC_Product $product ) {
		$url = function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_single' ) : '';

		return array(
			'id'          => 0,
			'url'         => $url,
			'alt'         => $product->get_name(),
			'is_featured' => true,
		);
	}

	/**
	 * یک Attachment را به قرارداد داده مشترک بین ویجت‌ها تبدیل می‌کند.
	 *
	 * @param int    $attachment_id شناسه تصویر.
	 * @param string $fallback_alt  متن جایگزین پیش‌فرض.
	 * @param bool   $is_featured   آیا تصویر شاخص محصول است.
	 *
	 * @return array{id:int,url:string,alt:string,is_featured:bool}
	 */
	private function create_attachment_item( $attachment_id, $fallback_alt, $is_featured ) {
		return array(
			'id'          => absint( $attachment_id ),
			'url'         => (string) wp_get_attachment_image_url( absint( $attachment_id ), 'full' ),
			'alt'         => BA_Media_Helper::get_attachment_alt( $attachment_id, $fallback_alt ),
			'is_featured' => (bool) $is_featured,
		);
	}
}
