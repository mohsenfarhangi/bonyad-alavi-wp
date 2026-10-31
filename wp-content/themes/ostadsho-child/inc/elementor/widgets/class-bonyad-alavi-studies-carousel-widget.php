<?php
/**
 * ویجت کاروسل کتاب‌ها، نشست‌ها یا پژوهش‌های مرکز مطالعات.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * یک ویجت با سه حالت کارت؛ هدر سکشن (عنوان، «بیشتر»، قبلی/بعدی) داخل ویجت است
 * چون دکمه‌ها کاروسل همین ویجت را کنترل می‌کنند.
 */
final class Bonyad_Alavi_Studies_Carousel_Widget extends Widget_Base {

	public function get_name() {
		return 'bonyad_alavi_studies_carousel';
	}

	public function get_title() {
		return esc_html__( 'کاروسل مرکز مطالعات', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-posts-carousel';
	}

	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	public function get_style_depends() {
		return array( 'swiper', 'bonyad-alavi-studies-center' );
	}

	public function get_script_depends() {
		return array( 'elementor-frontend', 'swiper', 'bonyad-alavi-studies-center' );
	}

	/**
	 * پیش‌فرض‌های وابسته به نوع محتوا.
	 *
	 * @return array
	 */
	public static function get_type_defaults() {
		return array(
			'books'    => array( 'title' => 'کتاب‌ها و انتشارات', 'desktop' => 4, 'tablet' => 3, 'mobile' => 2.3 ),
			'sessions' => array( 'title' => 'نشست‌ها و رویدادهای تخصصی', 'desktop' => 3, 'tablet' => 2, 'mobile' => 1.25 ),
			'research' => array( 'title' => 'پژوهش‌ها و گزارش‌های تحلیلی', 'desktop' => 3, 'tablet' => 2, 'mobile' => 1.12 ),
		);
	}

	/**
	 * فهرست جست‌وجوی Termهای همه Taxonomyهای ثبت‌شده سایت.
	 * شناسه‌های عددی فعلی Elementor برای سازگاری بدون migration حفظ می‌شوند.
	 *
	 * @return array
	 */
	private static function get_category_options() {
		$taxonomies = get_taxonomies( array(), 'objects' );
		if ( ! is_array( $taxonomies ) || ! $taxonomies ) {
			return array();
		}
		$terms = get_terms(
			array(
				'taxonomy'   => array_keys( $taxonomies ),
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}
		$options = array();
		foreach ( $terms as $term ) {
			if ( ! isset( $term->term_id, $term->taxonomy, $term->name ) ) {
				continue;
			}
			$taxonomy = $taxonomies[ $term->taxonomy ] ?? null;
			$label = $taxonomy->label ?? $term->taxonomy;
			$options[ (string) $term->term_id ] = $label . ' — ' . $term->name . ' (' . $term->taxonomy . ')';
		}
		return $options;
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => 'محتوا',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'content_type',
			array(
				'label'   => 'نوع خروجی',
				'type'    => Controls_Manager::SELECT,
				'default' => 'books',
				'options' => array(
					'books'    => 'کتاب‌ها و انتشارات',
					'sessions' => 'نشست‌ها و رویدادهای تخصصی',
					'research' => 'پژوهش‌ها و گزارش‌های تحلیلی',
				),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => 'عنوان سکشن',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'خالی = عنوان پیش‌فرض همان نوع',
				'label_block' => true,
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => 'تگ HTML عنوان',
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'div' => 'DIV' ),
			)
		);

		$this->add_control(
			'section_id',
			array(
				'label'       => 'شناسه سکشن (anchor)',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'خالی = studies-books / studies-sessions / studies-research',
			)
		);

		$this->add_control( 'more_text', array( 'label' => 'متن لینک بیشتر', 'type' => Controls_Manager::TEXT, 'default' => 'بیشتر' ) );
		$this->add_control(
			'more_link',
			array(
				'label'       => 'لینک بیشتر',
				'type'        => Controls_Manager::URL,
				'options'     => array( 'url', 'is_external', 'nofollow' ),
				'description' => 'خالی = آرشیو تعریف‌شده در تب «مرکز مطالعات» (پیش‌فرض: آرشیو دسته‌های انتشارات).',
			)
		);
		$this->add_control( 'show_nav', array( 'label' => 'دکمه‌های قبلی/بعدی', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control(
			'show_dots',
			array(
				'label'   => 'نقطه‌های صفحه‌بندی',
				'type'    => Controls_Manager::SELECT,
				'default' => 'mobile',
				'options' => array(
					'mobile' => 'فقط موبایل',
					'always' => 'همیشه',
					'never'  => 'هرگز',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'content_query',
			array(
				'label' => 'Query',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'publication_categories',
			array(
				'label'       => 'انتخاب از همه تکسونومی‌ها',
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'default'     => array(),
				'label_block' => true,
				'options'     => self::get_category_options(),
				'condition'   => array( 'content_type' => array( 'books', 'research' ) ),
				'description' => 'در میان Termهای همه تکسونومی‌های سایت جست‌وجو کنید. خالی = پیش‌فرض. تنظیمات غیرخالی بنیاد علوی اولویت بالاتری دارند. تکسونومی نامرتبط با نوع محتوا ممکن است خروجی نداشته باشد.',
			)
		);
		$this->add_control(
			'session_categories',
			array(
				'label'       => 'انتخاب از همه تکسونومی‌ها',
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'default'     => array(),
				'label_block' => true,
				'options'     => self::get_category_options(),
				'condition'   => array( 'content_type' => 'sessions' ),
				'description' => 'جست‌وجو در Termهای تمام تکسونومی‌های سایت. خالی = همه نشست‌ها؛ فیلتر حوزه نیز اعمال می‌شود. تکسونومی نامرتبط ممکن است نتیجه‌ای نداشته باشد.',
			)
		);

		$this->add_control( 'limit', array( 'label' => 'حداکثر تعداد', 'type' => Controls_Manager::NUMBER, 'default' => 8, 'min' => 1, 'max' => 24 ) );
		$this->add_control(
			'orderby',
			array(
				'label'   => 'مرتب‌سازی',
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array( 'date' => 'تاریخ', 'title' => 'عنوان', 'menu_order' => 'ترتیب دستی', 'modified' => 'آخرین ویرایش', 'rand' => 'تصادفی' ),
			)
		);
		$this->add_control(
			'order',
			array(
				'label'   => 'جهت',
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => array( 'DESC' => 'نزولی', 'ASC' => 'صعودی' ),
			)
		);
		$this->add_control(
			'respond_to_filter',
			array(
				'label'       => 'پیروی از فیلتر حوزه',
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => 'با تغییر فیلتر حوزه یا ?area= در آدرس، فقط محتوای همان حوزه نمایش داده می‌شود.',
			)
		);
		$this->add_control( 'empty_text', array( 'label' => 'پیام نبود نتیجه', 'type' => Controls_Manager::TEXT, 'default' => 'موردی یافت نشد', 'label_block' => true ) );

		$this->end_controls_section();

		$this->start_controls_section(
			'content_card',
			array(
				'label' => 'کارت',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control( 'card_more_text', array( 'label' => 'متن لینک کارت', 'type' => Controls_Manager::TEXT, 'default' => 'مطالعه بیشتر' ) );
		$this->add_control( 'date_format', array( 'label' => 'فرمت تاریخ', 'type' => Controls_Manager::TEXT, 'default' => 'j F Y' ) );
		$this->add_control( 'show_date', array( 'label' => 'نمایش تاریخ', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'show_excerpt', array( 'label' => 'نمایش خلاصه (نشست/پژوهش)', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'show_badge', array( 'label' => 'نمایش نوع پژوهش', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );

		$this->end_controls_section();

		$this->start_controls_section(
			'content_layout',
			array(
				'label' => 'چیدمان کاروسل',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'slides_per_view',
			array(
				'label'       => 'تعداد کارت در هر نما',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 6,
				'step'        => 0.05,
				'description' => 'خالی = پیش‌فرض نوع (کتاب ۴/۳/۲٫۳، نشست ۳/۲/۱٫۲۵، پژوهش ۳/۲/۱٫۱۲).',
			)
		);
		$this->add_responsive_control(
			'space_between',
			array(
				'label'          => 'فاصله کارت‌ها (px)',
				'type'           => Controls_Manager::NUMBER,
				'default'        => 24,
				'tablet_default' => 18,
				'mobile_default' => 14,
			)
		);
		$this->add_control( 'autoplay', array( 'label' => 'پخش خودکار', 'type' => Controls_Manager::SWITCHER, 'default' => '' ) );

		$this->end_controls_section();

		$this->register_style_controls();
	}

	/**
	 * کنترل‌های استایل بدون default ظاهری.
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'style_heading',
			array(
				'label' => 'هدر سکشن',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ref_title_color',
			array(
				'label'     => 'رنگ عنوان',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-studies-section__title' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_title_typography',
				'label'    => 'تایپوگرافی عنوان',
				'selector' => '{{WRAPPER}} .ba-studies-section__title',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_card',
			array(
				'label' => 'کارت',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ref_card_bg',
			array(
				'label'     => 'پس‌زمینه کارت',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-studies-card' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_card_radius',
			array(
				'label'      => 'گردی گوشه کارت',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-studies-card' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'ref_card_title_color',
			array(
				'label'     => 'رنگ عنوان کارت',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-studies-card__title' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_card_title_typography',
				'label'    => 'تایپوگرافی عنوان کارت',
				'selector' => '{{WRAPPER}} .ba-studies-card__title',
			)
		);
		$this->add_control(
			'ref_card_link_color',
			array(
				'label'     => 'رنگ لینک کارت',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-studies-card__more' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * مقدار responsive با fallback پیش‌فرض نوع.
	 *
	 * @param array  $settings تنظیمات.
	 * @param string $key      کلید پایه.
	 * @param string $device   desktop|tablet|mobile.
	 * @param float  $fallback پیش‌فرض.
	 * @return float
	 */
	private function responsive_number( array $settings, $key, $device, $fallback ) {
		$full = 'desktop' === $device ? $key : $key . '_' . $device;
		$raw  = $settings[ $full ] ?? '';

		return '' === $raw || null === $raw ? (float) $fallback : (float) $raw;
	}

	protected function render() {
		$settings  = $this->get_settings_for_display();
		$type      = BA_Studies_Query_Service::sanitize_type( $settings['content_type'] );
		$defaults  = self::get_type_defaults()[ $type ];
		$title     = '' !== trim( (string) $settings['title'] ) ? $settings['title'] : $defaults['title'];
		$title_tag = in_array( $settings['title_tag'], array( 'h2', 'h3', 'h4', 'div' ), true ) ? $settings['title_tag'] : 'h2';
		$section   = '' !== trim( (string) $settings['section_id'] ) ? sanitize_html_class( $settings['section_id'] ) : 'studies-' . $type;
		$follow    = 'yes' === $settings['respond_to_filter'];
		$area      = $follow ? BA_Studies_Query_Service::get_requested_area() : '';
		$date_fmt  = '' !== trim( (string) $settings['date_format'] ) ? $settings['date_format'] : 'j F Y';
		$card_opts = array(
			'more_text'    => (string) $settings['card_more_text'],
			'show_date'    => 'yes' === $settings['show_date'],
			'show_excerpt' => 'yes' === $settings['show_excerpt'],
			'show_badge'   => 'yes' === $settings['show_badge'],
			'title_tag'    => 'h2' === $title_tag ? 'h3' : 'h4',
		);
		$query     = array(
			'limit'   => max( 1, min( 24, (int) $settings['limit'] ) ),
			'orderby' => $settings['orderby'],
			'order'   => $settings['order'],
		);
		$selected_categories = 'sessions' === $type
			? ( $settings['session_categories'] ?? array() )
			: ( $settings['publication_categories'] ?? array() );
		$query['categories'] = BA_Studies_Query_Service::sanitize_category_csv( $selected_categories );
		$result    = BA_Studies_Query_Service::get_items( $type, array_merge( $query, array( 'area' => $area ) ), $date_fmt );
		$more_url  = isset( $settings['more_link']['url'] ) && '' !== trim( (string) $settings['more_link']['url'] )
			? $settings['more_link']['url']
			: BA_Studies_Center_Settings_Service::get_archive_url( $type );
		$more_ext  = ! empty( $settings['more_link']['is_external'] );

		$config = array(
			'type'     => $type,
			'follow'   => $follow,
			'endpoint' => $follow ? BA_Studies_Rest_Controller::get_url() : '',
			'query'    => array_merge(
				$query,
				array(
					'type'         => $type,
					'more_text'    => $card_opts['more_text'],
					'date_format'  => $date_fmt,
					'show_date'    => $card_opts['show_date'] ? 1 : 0,
					'show_excerpt' => $card_opts['show_excerpt'] ? 1 : 0,
					'show_badge'   => $card_opts['show_badge'] ? 1 : 0,
					'title_tag'    => $card_opts['title_tag'],
				)
			),
			'swiper'   => array(
				'desktop'  => array( 'perView' => $this->responsive_number( $settings, 'slides_per_view', 'desktop', $defaults['desktop'] ), 'gap' => $this->responsive_number( $settings, 'space_between', 'desktop', 24 ) ),
				'tablet'   => array( 'perView' => $this->responsive_number( $settings, 'slides_per_view', 'tablet', $defaults['tablet'] ), 'gap' => $this->responsive_number( $settings, 'space_between', 'tablet', 18 ) ),
				'mobile'   => array( 'perView' => $this->responsive_number( $settings, 'slides_per_view', 'mobile', $defaults['mobile'] ), 'gap' => $this->responsive_number( $settings, 'space_between', 'mobile', 14 ) ),
				'autoplay' => 'yes' === $settings['autoplay'],
			),
		);

		$dots = in_array( $settings['show_dots'], array( 'mobile', 'always', 'never' ), true ) ? $settings['show_dots'] : 'mobile';
		?>
		<section id="<?php echo esc_attr( $section ); ?>" class="ba-studies-section ba-studies-section--<?php echo esc_attr( $type ); ?> ba-studies-widget ba-studies-section--dots-<?php echo esc_attr( $dots ); ?>" data-ba-studies-carousel="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
			<div class="ba-studies-section__head">
				<<?php echo $title_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelist ?> class="ba-studies-section__title"><?php echo esc_html( $title ); ?></<?php echo $title_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelist ?>>
				<div class="ba-studies-section__actions">
					<?php if ( '' !== (string) $settings['more_text'] && '' !== (string) $more_url ) : ?>
						<a class="ba-studies-section__more" href="<?php echo esc_url( $more_url ); ?>"<?php echo $more_ext ? ' target="_blank" rel="noopener"' : ''; ?>><span><?php echo esc_html( $settings['more_text'] ); ?></span><?php echo BA_Studies_Formatter::icon( 'arrow-start' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php endif; ?>
					<?php if ( 'yes' === $settings['show_nav'] ) : ?>
						<button type="button" class="ba-studies-section__nav ba-studies-section__nav--prev" data-ba-studies-prev aria-label="قبلی"><?php echo BA_Studies_Formatter::icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
						<button type="button" class="ba-studies-section__nav ba-studies-section__nav--next" data-ba-studies-next aria-label="بعدی"><?php echo BA_Studies_Formatter::icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					<?php endif; ?>
				</div>
			</div>
			<div class="ba-studies-carousel swiper" dir="rtl" data-ba-studies-swiper <?php echo $result['items'] ? '' : 'hidden'; ?>>
				<div class="swiper-wrapper" data-ba-studies-track aria-live="polite">
					<?php echo BA_Studies_Card_Renderer::render_slides( $result['items'], $card_opts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in renderer ?>
				</div>
			</div>
			<?php if ( 'never' !== $dots ) : ?>
				<div class="ba-studies-section__dots" data-ba-studies-dots></div>
			<?php endif; ?>
			<?php echo BA_Studies_Card_Renderer::render_empty( (string) $settings['empty_text'], (bool) $result['items'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</section>
		<?php
	}
}
