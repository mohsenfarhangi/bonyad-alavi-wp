<?php
/**
 * ویجت Hero مرکز مطالعات: اسلایدر آخرین نشست‌ها + سه کارت آمار.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

/**
 * اسلایدر خودکار/دستی با Swiper داخلی Elementor و آمار واقعی محتوا.
 */
final class Bonyad_Alavi_Studies_Hero_Widget extends Widget_Base {

	public function get_name() {
		return 'bonyad_alavi_studies_hero';
	}

	public function get_title() {
		return esc_html__( 'Hero مرکز مطالعات', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
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

	protected function register_controls() {
		$this->register_slider_controls();
		$this->register_behavior_controls();
		$this->register_stats_controls();
		$this->register_style_controls();
	}

	/**
	 * منبع و محتوای اسلایدها.
	 */
	private function register_slider_controls() {
		$this->start_controls_section(
			'content_slider',
			array(
				'label' => 'اسلایدر',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'slider_source',
			array(
				'label'   => 'منبع اسلایدها',
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'   => 'خودکار (آخرین محتوا)',
					'manual' => 'دستی',
				),
			)
		);

		$this->add_control(
			'auto_type',
			array(
				'label'     => 'نوع محتوای خودکار',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'sessions',
				'options'   => array(
					'sessions' => 'نشست‌ها',
					'research' => 'پژوهش‌ها',
					'books'    => 'کتاب‌ها',
				),
				'condition' => array( 'slider_source' => 'auto' ),
			)
		);

		$this->add_control(
			'auto_count',
			array(
				'label'     => 'تعداد اسلاید',
				'type'      => Controls_Manager::NUMBER,
				'default'   => 4,
				'min'       => 1,
				'max'       => 12,
				'condition' => array( 'slider_source' => 'auto' ),
			)
		);

		$this->add_control(
			'auto_label',
			array(
				'label'       => 'برچسب اسلاید خودکار',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'آخرین نشست',
				'label_block' => true,
				'condition'   => array( 'slider_source' => 'auto' ),
			)
		);

		$this->add_control(
			'meta_text',
			array(
				'label'       => 'متن خط متا',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'مرکز مطالعات راهبردی و پیشرفت مناطق محروم',
				'label_block' => true,
				'description' => 'بعد از تاریخ با «·» نمایش داده می‌شود. خالی = فقط تاریخ.',
			)
		);

		$this->add_control(
			'date_format',
			array(
				'label'   => 'فرمت تاریخ',
				'type'    => Controls_Manager::TEXT,
				'default' => 'j F Y',
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'image',
			array(
				'label' => 'تصویر',
				'type'  => Controls_Manager::MEDIA,
			)
		);
		$repeater->add_control(
			'label',
			array(
				'label'   => 'برچسب',
				'type'    => Controls_Manager::TEXT,
				'default' => 'آخرین نشست',
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'       => 'عنوان',
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => 'عنوان اسلاید',
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'date_text',
			array(
				'label' => 'تاریخ',
				'type'  => Controls_Manager::TEXT,
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'   => 'لینک',
				'type'    => Controls_Manager::URL,
				'options' => array( 'url', 'is_external', 'nofollow' ),
			)
		);

		$this->add_control(
			'manual_slides',
			array(
				'label'       => 'اسلایدهای دستی',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(),
				'condition'   => array( 'slider_source' => 'manual' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * رفتار Swiper.
	 */
	private function register_behavior_controls() {
		$this->start_controls_section(
			'content_behavior',
			array(
				'label' => 'رفتار اسلایدر',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control( 'show_arrows', array( 'label' => 'فلش قبلی/بعدی', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'show_dots', array( 'label' => 'نقطه‌های صفحه‌بندی', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'autoplay', array( 'label' => 'پخش خودکار', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control(
			'autoplay_delay',
			array(
				'label'     => 'فاصله پخش (میلی‌ثانیه)',
				'type'      => Controls_Manager::NUMBER,
				'default'   => 6000,
				'min'       => 2000,
				'step'      => 500,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);
		$this->add_control( 'loop', array( 'label' => 'حلقه', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );

		$this->end_controls_section();
	}

	/**
	 * کارت‌های آمار.
	 */
	private function register_stats_controls() {
		$this->start_controls_section(
			'content_stats',
			array(
				'label' => 'کارت‌های آمار',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'source',
			array(
				'label'       => 'منبع عدد',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'books',
				'options'     => array(
					'books'    => 'تعداد کتاب‌ها',
					'sessions' => 'تعداد نشست‌ها',
					'research' => 'تعداد پژوهش‌ها',
					'manual'   => 'عدد دستی',
				),
				'description' => 'عدد خودکار از محتوای منتشرشده یا override تب «مرکز مطالعات» خوانده می‌شود.',
			)
		);
		$repeater->add_control(
			'number',
			array(
				'label'     => 'عدد',
				'type'      => Controls_Manager::NUMBER,
				'default'   => 0,
				'condition' => array( 'source' => 'manual' ),
			)
		);
		$repeater->add_control(
			'label',
			array(
				'label'       => 'عنوان',
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'icon',
			array(
				'label'   => 'آیکون',
				'type'    => Controls_Manager::SELECT,
				'default' => 'folder',
				'options' => array(
					'folder'   => 'پوشه',
					'camera'   => 'دوربین',
					'document' => 'سند',
					'none'     => 'بدون آیکون',
				),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'       => 'لینک',
				'type'        => Controls_Manager::URL,
				'options'     => array( 'url', 'is_external', 'nofollow' ),
				'description' => 'مثلاً #studies-books برای پرش به سکشن. خالی = آرشیو تعریف‌شده در تب تنظیمات.',
			)
		);

		$this->add_control(
			'stats',
			array(
				'label'       => 'آمار',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => array(
					array( 'source' => 'books', 'label' => 'کتاب‌ها و انتشارات', 'icon' => 'folder', 'link' => array( 'url' => '#studies-books' ) ),
					array( 'source' => 'sessions', 'label' => 'نشست‌ها و رویدادهای تخصصی', 'icon' => 'camera', 'link' => array( 'url' => '#studies-sessions' ) ),
					array( 'source' => 'research', 'label' => 'پژوهش‌ها و گزارش‌های تحلیلی', 'icon' => 'document', 'link' => array( 'url' => '#studies-research' ) ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * کنترل‌های استایل بدون default ظاهری؛ baseline در CSS است.
	 */
	private function register_style_controls() {
		$this->start_controls_section(
			'style_slider',
			array(
				'label' => 'اسلایدر',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'ref_slider_height',
			array(
				'label'      => 'ارتفاع اسلایدر',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array( 'px' => array( 'min' => 160, 'max' => 720 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-studies-hero' => '--ba-studies-hero-height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'ref_overlay_color',
			array(
				'label'     => 'رنگ overlay',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-studies-hero' => '--ba-studies-hero-overlay: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_slide_title_typography',
				'label'    => 'تایپوگرافی عنوان',
				'selector' => '{{WRAPPER}} .ba-studies-hero__title',
			)
		);

		$this->add_control(
			'ref_label_bg',
			array(
				'label'     => 'پس‌زمینه برچسب',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-studies-hero__label' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_stats',
			array(
				'label' => 'کارت‌های آمار',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ref_stat_number_color',
			array(
				'label'     => 'رنگ عدد و آیکون',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-studies-stat__value' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_stat_number_typography',
				'label'    => 'تایپوگرافی عدد',
				'selector' => '{{WRAPPER}} .ba-studies-stat__number',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_stat_label_typography',
				'label'    => 'تایپوگرافی عنوان',
				'selector' => '{{WRAPPER}} .ba-studies-stat__label',
			)
		);

		$this->add_responsive_control(
			'ref_stats_width',
			array(
				'label'      => 'عرض ستون آمار',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 180, 'max' => 420 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-studies-hero' => '--ba-studies-stats-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * اسلایدهای نهایی (خودکار یا دستی) به‌صورت آرایه نرمال.
	 *
	 * @param array $settings تنظیمات.
	 * @return array
	 */
	private function get_slides( array $settings ) {
		$date_format = '' !== trim( (string) $settings['date_format'] ) ? $settings['date_format'] : 'j F Y';

		if ( 'manual' === $settings['slider_source'] ) {
			$slides = array();

			foreach ( (array) $settings['manual_slides'] as $slide ) {
				$slides[] = array(
					'image'    => isset( $slide['image']['url'] ) ? $slide['image']['url'] : '',
					'alt'      => isset( $slide['image']['alt'] ) ? $slide['image']['alt'] : '',
					'label'    => (string) ( $slide['label'] ?? '' ),
					'title'    => (string) ( $slide['title'] ?? '' ),
					'date'     => BA_Studies_Formatter::fa_digits( (string) ( $slide['date_text'] ?? '' ) ),
					'url'      => isset( $slide['link']['url'] ) ? $slide['link']['url'] : '',
					'external' => ! empty( $slide['link']['is_external'] ),
				);
			}

			return $slides;
		}

		$result = BA_Studies_Query_Service::get_items(
			$settings['auto_type'],
			array( 'limit' => max( 1, (int) $settings['auto_count'] ) ),
			$date_format
		);
		$slides = array();

		foreach ( $result['items'] as $item ) {
			$slides[] = array(
				'image'    => $item['image'],
				'alt'      => $item['image_alt'],
				'label'    => (string) $settings['auto_label'],
				'title'    => $item['title'],
				'date'     => $item['date'],
				'url'      => $item['url'],
				'external' => false,
			);
		}

		return $slides;
	}

	/**
	 * عدد یک کارت آمار.
	 *
	 * @param array $stat آیتم.
	 * @return int
	 */
	private function get_stat_value( array $stat ) {
		if ( 'manual' === ( $stat['source'] ?? '' ) ) {
			return (int) ( $stat['number'] ?? 0 );
		}

		return BA_Studies_Query_Service::stat( (string) ( $stat['source'] ?? 'books' ) );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$slides   = $this->get_slides( $settings );
		$meta     = trim( (string) $settings['meta_text'] );
		$config   = array(
			'autoplay' => 'yes' === $settings['autoplay'],
			'delay'    => max( 2000, (int) $settings['autoplay_delay'] ),
			'loop'     => 'yes' === $settings['loop'] && count( $slides ) > 1,
		);
		?>
		<section class="ba-studies-hero ba-studies-widget">
			<div class="ba-studies-hero__grid">
				<div class="ba-studies-hero__slider swiper" dir="rtl" data-ba-studies-hero="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
					<div class="swiper-wrapper">
						<?php foreach ( $slides as $index => $slide ) : ?>
							<?php
							$tag   = '' !== $slide['url'] ? 'a' : 'div';
							$attrs = 'a' === $tag ? ' href="' . esc_url( $slide['url'] ) . '"' . ( $slide['external'] ? ' target="_blank" rel="noopener"' : '' ) : '';
							?>
							<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a|div ?> class="swiper-slide ba-studies-hero__slide"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?>>
								<?php if ( '' !== $slide['image'] ) : ?>
									<img class="ba-studies-hero__image" src="<?php echo esc_url( $slide['image'] ); ?>" alt="<?php echo esc_attr( $slide['alt'] ); ?>" <?php echo 0 === $index ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
								<?php endif; ?>
								<span class="ba-studies-hero__overlay" aria-hidden="true"></span>
								<span class="ba-studies-hero__content">
									<?php if ( '' !== $slide['label'] ) : ?>
										<span class="ba-studies-hero__label"><?php echo esc_html( $slide['label'] ); ?></span>
									<?php endif; ?>
									<span class="ba-studies-hero__title"><?php echo esc_html( $slide['title'] ); ?></span>
									<?php if ( '' !== $slide['date'] || '' !== $meta ) : ?>
										<span class="ba-studies-hero__meta"><?php echo esc_html( implode( ' · ', array_filter( array( $slide['date'], $meta ) ) ) ); ?></span>
									<?php endif; ?>
								</span>
							</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a|div ?>>
						<?php endforeach; ?>
						<?php if ( ! $slides ) : ?>
							<div class="swiper-slide ba-studies-hero__slide ba-studies-hero__slide--empty"><span class="ba-studies-hero__overlay" aria-hidden="true"></span></div>
						<?php endif; ?>
					</div>
					<?php if ( 'yes' === $settings['show_arrows'] && count( $slides ) > 1 ) : ?>
						<button type="button" class="ba-studies-hero__arrow ba-studies-hero__arrow--prev" data-ba-studies-prev aria-label="اسلاید قبلی"><?php echo BA_Studies_Formatter::icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
						<button type="button" class="ba-studies-hero__arrow ba-studies-hero__arrow--next" data-ba-studies-next aria-label="اسلاید بعدی"><?php echo BA_Studies_Formatter::icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					<?php endif; ?>
					<?php if ( 'yes' === $settings['show_dots'] && count( $slides ) > 1 ) : ?>
						<div class="ba-studies-hero__dots" data-ba-studies-dots></div>
					<?php endif; ?>
				</div>

				<div class="ba-studies-hero__stats">
					<?php foreach ( (array) $settings['stats'] as $stat ) : ?>
						<?php
						$source = (string) ( $stat['source'] ?? 'books' );
						$url    = isset( $stat['link']['url'] ) ? trim( (string) $stat['link']['url'] ) : '';
						$url    = '' !== $url ? $url : BA_Studies_Center_Settings_Service::get_archive_url( $source );
						$tag    = '' !== $url ? 'a' : 'div';
						$icon   = (string) ( $stat['icon'] ?? 'none' );
						?>
						<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a|div ?> class="ba-studies-stat"<?php echo 'a' === $tag ? ' href="' . esc_url( $url ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<span class="ba-studies-stat__value">
								<span class="ba-studies-stat__number"><?php echo esc_html( BA_Studies_Formatter::number( $this->get_stat_value( $stat ) ) ); ?></span>
								<?php echo 'none' !== $icon ? BA_Studies_Formatter::icon( $icon ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<span class="ba-studies-stat__label"><?php echo esc_html( (string) ( $stat['label'] ?? '' ) ); ?></span>
						</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a|div ?>>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
