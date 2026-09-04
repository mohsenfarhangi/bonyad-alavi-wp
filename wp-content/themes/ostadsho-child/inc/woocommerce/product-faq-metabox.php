<?php
/**
 * متاباکس پرسش‌های متداول اختصاصی محصولات ووکامرس.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BAP_PRODUCT_FAQ_META_KEY' ) ) {
	define( 'BAP_PRODUCT_FAQ_META_KEY', '_bap_product_faqs' );
}

/**
 * ثبت، رندر و ذخیره FAQ محصول را با استفاده از Repeater عمومی مدیریت می‌کند.
 */
final class BA_Product_FAQ_Metabox {

	const NONCE_ACTION = 'bap_save_product_faqs';
	const NONCE_NAME   = 'bap_product_faqs_nonce';
	const FIELD_NAME   = 'bap_product_faqs';
	const PRESENT_NAME = 'bap_product_faqs_present';

	/**
	 * هوک‌های متاباکس، ذخیره داده و Assetهای مدیریت را ثبت می‌کند.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'add_meta_boxes_product', array( __CLASS__, 'register_metabox' ) );
		add_action( 'save_post_product', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * متاباکس FAQ را به صفحه ویرایش محصول اضافه می‌کند.
	 *
	 * @return void
	 */
	public static function register_metabox() {
		add_meta_box(
			'bap-product-faqs',
			esc_html__( 'پرسش‌های متداول اختصاصی پروژه', 'bonyad-alavi-child' ),
			array( __CLASS__, 'render' ),
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Assetهای Repeater و ظاهر متاباکس را فقط در صفحه ویرایش محصول بارگذاری می‌کند.
	 *
	 * @param string $hook_suffix شناسه صفحه جاری مدیریت.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		BA_Admin_Repeater_Component::enqueue_assets();

		$relative_path = 'assets/css/bonyad-alavi-product-faq-admin.css';
		$file_path = trailingslashit( get_stylesheet_directory() ) . $relative_path;
		$version = file_exists( $file_path ) ? (string) filemtime( $file_path ) : wp_get_theme()->get( 'Version' );

		wp_enqueue_style(
			'bonyad-alavi-product-faq-admin',
			trailingslashit( get_stylesheet_directory_uri() ) . $relative_path,
			array( BA_Admin_Repeater_Component::STYLE_HANDLE ),
			$version
		);
	}

	/**
	 * FAQهای ذخیره‌شده محصول را برای رابط مدیریت برمی‌گرداند.
	 *
	 * @param int $post_id شناسه محصول.
	 * @return array
	 */
	public static function get_items( $post_id ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : false;
		$items = $product
			? $product->get_meta( BAP_PRODUCT_FAQ_META_KEY, true )
			: get_post_meta( $post_id, BAP_PRODUCT_FAQ_META_KEY, true );

		return is_array( $items ) ? $items : array();
	}

	/**
	 * متاباکس کامل FAQ محصول را رندر می‌کند.
	 *
	 * @param WP_Post $post محصول جاری.
	 * @return void
	 */
	public static function render( $post ) {
		$items = self::get_items( $post->ID );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<div class="ba-product-faq-admin">
			<p class="ba-product-faq-admin__lead">
				<?php esc_html_e( 'این سؤال‌ها در صفحه مشارکت مردمی نمایش داده می‌شوند.', 'bonyad-alavi-child' ); ?>
			</p>
			<input type="hidden" name="<?php echo esc_attr( self::PRESENT_NAME ); ?>" value="1">
			<?php
			BA_Admin_Repeater_Component::render(
				array(
					'id'           => 'bap-product-faq-repeater',
					'items'        => $items,
					'item_label'   => 'پرسش اختصاصی',
					'add_label'    => 'افزودن سؤال اختصاصی',
					'empty_label'  => 'هنوز پرسش اختصاصی برای این محصول ثبت نشده است.',
					'row_renderer' => array( __CLASS__, 'render_row_fields' ),
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * فیلدهای سؤال و پاسخ یک ردیف FAQ را برای Repeater عمومی رندر می‌کند.
	 *
	 * @param string|int $index   اندیس ردیف.
	 * @param array      $item    داده ردیف.
	 * @param mixed      $context Context رزروشده برای قرارداد عمومی Component.
	 * @return void
	 */
	public static function render_row_fields( $index, array $item, $context = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$item = wp_parse_args(
			$item,
			array(
				'question' => '',
				'answer'   => '',
			)
		);
		$question_name = BA_Admin_Repeater_Component::field_name( self::FIELD_NAME, '', $index, 'question' );
		$answer_name = BA_Admin_Repeater_Component::field_name( self::FIELD_NAME, '', $index, 'answer' );
		?>
		<div class="ba-product-faq-admin__fields">
			<label class="ba-product-faq-admin__field">
				<span class="ba-product-faq-admin__label"><?php esc_html_e( 'سؤال', 'bonyad-alavi-child' ); ?></span>
				<input type="text" name="<?php echo esc_attr( $question_name ); ?>" value="<?php echo esc_attr( $item['question'] ); ?>" placeholder="<?php echo esc_attr__( 'متن سؤال', 'bonyad-alavi-child' ); ?>">
			</label>
			<label class="ba-product-faq-admin__field">
				<span class="ba-product-faq-admin__label"><?php esc_html_e( 'پاسخ', 'bonyad-alavi-child' ); ?></span>
				<textarea rows="5" name="<?php echo esc_attr( $answer_name ); ?>" placeholder="<?php echo esc_attr__( 'پاسخ سؤال؛ استفاده از HTML امن مجاز است.', 'bonyad-alavi-child' ); ?>"><?php echo esc_textarea( $item['answer'] ); ?></textarea>
			</label>
		</div>
		<?php
	}

	/**
	 * داده FAQ محصول را اعتبارسنجی، پاک‌سازی و ذخیره می‌کند.
	 *
	 * @param int     $post_id شناسه محصول.
	 * @param WP_Post $post    شیء محصول جاری.
	 * @return void
	 */
	public static function save( $post_id, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST[ self::PRESENT_NAME ] ) ) {
			return;
		}

		$raw_items = isset( $_POST[ self::FIELD_NAME ] )
			? wp_unslash( $_POST[ self::FIELD_NAME ] )
			: array();
		$items = self::sanitize_items( $raw_items );
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
			return;
		}

		delete_post_meta( $post_id, BAP_PRODUCT_FAQ_META_KEY );
	}

	/**
	 * ردیف‌های خام FAQ را پاک‌سازی می‌کند و ردیف ناقص را کنار می‌گذارد.
	 *
	 * @param mixed $raw_items داده خام فرم.
	 * @return array
	 */
	private static function sanitize_items( $raw_items ) {
		$items = array();

		if ( ! is_array( $raw_items ) ) {
			return $items;
		}

		foreach ( $raw_items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$question = isset( $item['question'] ) ? sanitize_text_field( $item['question'] ) : '';
			$answer = isset( $item['answer'] ) ? wp_kses_post( $item['answer'] ) : '';

			if ( '' === $question || '' === trim( wp_strip_all_tags( $answer ) ) ) {
				continue;
			}

			$items[] = array(
				'question' => $question,
				'answer'   => $answer,
			);
		}

		return $items;
	}
}

BA_Product_FAQ_Metabox::register_hooks();
