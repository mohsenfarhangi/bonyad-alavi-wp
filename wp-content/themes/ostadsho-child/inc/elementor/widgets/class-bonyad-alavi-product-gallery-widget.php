<?php
/**
 * ویجت اختصاصی تصویر محصول برای Loopهای المنتور.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

/**
 * ویجت تصویر محصول که تصویر شاخص و گالری ووکامرس را بدون وابستگی به Lightbox نمایش می‌دهد.
 */
final class Bonyad_Alavi_Product_Gallery_Widget extends Widget_Base {

	/**
	 * شناسه داخلی ویجت در المنتور را برمی‌گرداند.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'bonyad_alavi_product_gallery';
	}

	/**
	 * عنوان فارسی ویجت در پنل المنتور را برمی‌گرداند.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'تصویر محصول (کاروسل)', 'bonyad-alavi-child' );
	}

	/**
	 * آیکون ویجت در پنل المنتور را مشخص می‌کند.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-push';
	}

	/**
	 * دسته اختصاصی بنیاد علوی را برای نمایش ویجت برمی‌گرداند.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	/**
	 * کلیدواژه‌های جست‌وجوی ویجت در پنل المنتور را برمی‌گرداند.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'محصول', 'تصویر', 'گالری', 'کاروسل', 'لوپ', 'product', 'gallery', 'carousel' );
	}

	/**
	 * استایل اختصاصی ویجت را فقط هنگام استفاده در صفحه درخواست می‌کند.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'bonyad-alavi-product-gallery-widget' );
	}

	/**
	 * اسکریپت اختصاصی ویجت را فقط هنگام استفاده در صفحه درخواست می‌کند.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'elementor-frontend', 'bonyad-alavi-product-gallery-widget' );
	}

	/**
	 * کنترل‌های محتوایی و ظاهری ویجت را در پنل المنتور ثبت می‌کند.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_image_style_controls();
		$this->register_navigation_style_controls();
	}

	/**
	 * تنظیمات فعال/غیرفعال بودن کاروسل، محدودیت تعداد و اندازه فایل تصویر را ثبت می‌کند.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'gallery_content',
			array(
				'label' => esc_html__( 'تصاویر محصول', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_carousel',
			array(
				'label'        => esc_html__( 'فعال‌سازی کاروسل', 'bonyad-alavi-child' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'بله', 'bonyad-alavi-child' ),
				'label_off'    => esc_html__( 'خیر', 'bonyad-alavi-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'image_limit',
			array(
				'label'       => esc_html__( 'حداکثر تعداد تصاویر', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 50,
				'step'        => 1,
				'default'     => 0,
				'description' => esc_html__( 'عدد ۰ یعنی همه تصاویر. تصویر شاخص نیز در این تعداد محاسبه می‌شود.', 'bonyad-alavi-child' ),
				'condition'   => array( 'enable_carousel' => 'yes' ),
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'   => esc_html__( 'اندازه فایل تصویر', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'medium_large',
				'options' => array(
					'thumbnail'    => esc_html__( 'Thumbnail', 'bonyad-alavi-child' ),
					'medium'       => esc_html__( 'Medium', 'bonyad-alavi-child' ),
					'medium_large' => esc_html__( 'Medium Large', 'bonyad-alavi-child' ),
					'large'        => esc_html__( 'Large', 'bonyad-alavi-child' ),
					'full'         => esc_html__( 'Full', 'bonyad-alavi-child' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * کنترل‌های نسبت تصویر، نحوه برش و گردی گوشه‌های تصویر را ثبت می‌کند.
	 */
	private function register_image_style_controls() {
		$this->start_controls_section(
			'image_style',
			array(
				'label' => esc_html__( 'تصویر', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'aspect_ratio',
			array(
				'label'   => esc_html__( 'نسبت تصویر', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4 / 3',
				'options' => array(
					'1 / 1'  => '1:1',
					'4 / 3'  => '4:3',
					'3 / 2'  => '3:2',
					'16 / 9' => '16:9',
				),
				'selectors' => array(
					'{{WRAPPER}} .ba-product-gallery' => '--ba-product-gallery-aspect-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'object_fit',
			array(
				'label'   => esc_html__( 'نحوه قرارگیری تصویر', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'cover',
				'options' => array(
					'cover'   => esc_html__( 'برش و پوشش کامل', 'bonyad-alavi-child' ),
					'contain' => esc_html__( 'نمایش کامل تصویر', 'bonyad-alavi-child' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .ba-product-gallery__image' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'border_radius',
			array(
				'label'      => esc_html__( 'گردی گوشه‌ها', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 14 ),
				'selectors'  => array(
					'{{WRAPPER}} .ba-product-gallery' => '--ba-product-gallery-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * کنترل اندازه فلش‌های هدایت کاروسل را ثبت می‌کند.
	 */
	private function register_navigation_style_controls() {
		$this->start_controls_section(
			'navigation_style',
			array(
				'label'     => esc_html__( 'فلش‌های کاروسل', 'bonyad-alavi-child' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'enable_carousel' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'navigation_size',
			array(
				'label'      => esc_html__( 'اندازه دکمه‌ها', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 24, 'max' => 56 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 34 ),
				'selectors'  => array(
					'{{WRAPPER}} .ba-product-gallery' => '--ba-product-gallery-nav-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * خروجی ویجت را بر اساس محصول جاری Loop و تنظیمات کاروسل تولید می‌کند.
	 */
	protected function render() {
		$service = BA_Product_Media_Service::instance();
		$product = $service->resolve_context_product();

		if ( ! $product ) {
			$this->render_editor_notice();
			return;
		}

		$settings         = $this->get_settings_for_display();
		$carousel_enabled = 'yes' === ( $settings['enable_carousel'] ?? 'yes' );
		$limit            = $carousel_enabled ? absint( $settings['image_limit'] ?? 0 ) : 1;
		$image_size       = $this->sanitize_image_size( $settings['image_size'] ?? 'medium_large' );
		$items            = $service->get_product_items( $product, $limit );

		if ( empty( $items ) ) {
			$items[] = $service->get_placeholder_item( $product );
		}

		$is_carousel = $carousel_enabled && count( $items ) > 1;
		$root_class  = 'ba-product-gallery ' . ( $is_carousel ? 'ba-product-gallery--carousel' : 'ba-product-gallery--static' );
		?>
		<div class="<?php echo esc_attr( $root_class ); ?>" data-carousel-enabled="<?php echo $is_carousel ? 'true' : 'false'; ?>">
			<div class="ba-product-gallery__viewport" data-carousel-viewport <?php echo $is_carousel ? 'tabindex="0" role="region" aria-label="' . esc_attr__( 'کاروسل تصاویر محصول', 'bonyad-alavi-child' ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<div class="ba-product-gallery__slides">
					<?php foreach ( $items as $index => $item ) : ?>
						<figure class="ba-product-gallery__slide<?php echo 0 === $index ? ' ba-product-gallery__slide--active' : ''; ?>" data-carousel-slide aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
							<?php
							$loading_strategy = $is_carousel && 0 === $index ? 'eager' : 'lazy';

							echo BA_Media_Helper::render_image(
								$item,
								$image_size,
								array(
									'class'    => 'ba-product-gallery__image',
									'loading'  => $loading_strategy,
									'decoding' => 'async',
								)
							); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</figure>
					<?php endforeach; ?>
				</div>

				<?php if ( $is_carousel ) : ?>
					<?php $this->render_navigation_button( 'prev', esc_html__( 'تصویر قبلی', 'bonyad-alavi-child' ) ); ?>
					<?php $this->render_navigation_button( 'next', esc_html__( 'تصویر بعدی', 'bonyad-alavi-child' ) ); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * دکمه قبلی یا بعدی کاروسل را با Markup مشترک و کلاس Modifier مناسب رندر می‌کند.
	 *
	 * @param string $direction جهت کنترل؛ prev یا next.
	 * @param string $label     برچسب دسترس‌پذیری دکمه.
	 */
	private function render_navigation_button( $direction, $label ) {
		$direction = 'prev' === $direction ? 'prev' : 'next';
		?>
		<button type="button" class="ba-product-gallery__nav ba-product-gallery__nav--<?php echo esc_attr( $direction ); ?>" data-carousel-<?php echo esc_attr( $direction ); ?> aria-label="<?php echo esc_attr( $label ); ?>">
			<?php echo BA_Media_Helper::get_chevron_svg( $direction ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
		<?php
	}

	/**
	 * مقدار اندازه تصویر را به گزینه‌های امن و پشتیبانی‌شده این ویجت محدود می‌کند.
	 *
	 * @param string $size اندازه انتخاب‌شده در کنترل المنتور.
	 *
	 * @return string
	 */
	private function sanitize_image_size( $size ) {
		$allowed = array( 'thumbnail', 'medium', 'medium_large', 'large', 'full' );

		return in_array( $size, $allowed, true ) ? $size : 'medium_large';
	}

	/**
	 * فقط در حالت ویرایش المنتور پیام راهنما برای نبود context محصول نمایش می‌دهد.
	 */
	private function render_editor_notice() {
		if ( ! class_exists( '\\Elementor\\Plugin' ) || ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}
		?>
		<div class="ba-product-gallery ba-product-gallery--empty">
			<p class="ba-product-gallery__notice"><?php echo esc_html__( 'برای پیش‌نمایش این ویجت، آن را داخل Loop محصول یا قالب محصول قرار دهید.', 'bonyad-alavi-child' ); ?></p>
		</div>
		<?php
	}
}
