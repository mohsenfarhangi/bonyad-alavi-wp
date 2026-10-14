<?php
/**
 * ویجت گزارش برخط اقدامات صفحه اصلی بنیاد علوی.
 *
 * Visual reference:
 * bonyad-alavi-redesign/redesign/index.html -> ba-live-stats
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

/**
 * گزارش برخط اقدامات با markup و responsive layout طرح مرجع.
 */
final class Bonyad_Alavi_Home_Live_Stats_Widget extends Widget_Base {

	public function get_name() {
		return 'bonyad_alavi_home_live_stats';
	}

	public function get_title() {
		return esc_html__( 'گزارش برخط اقدامات صفحه اصلی', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-counter';
	}

	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	public function get_style_depends() {
		return array( 'swiper', 'bonyad-alavi-home-live-stats-widget' );
	}

	public function get_script_depends() {
		return array( 'elementor-frontend', 'swiper', 'bonyad-alavi-home-live-stats-widget' );
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_carousel_controls();
		$this->register_layout_style_controls();
		$this->register_title_style_controls();
		$this->register_stats_style_controls();
		$this->register_carousel_style_controls();
	}

	/**
	 * عنوان دوخطی و آمارهای reference را برای Elementor قابل مدیریت می‌کند.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'content_live_stats',
			array(
				'label' => 'گزارش برخط اقدامات',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'title_line_one',
			array(
				'label'       => 'خط اول عنوان',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'گزارش برخط',
				'label_block' => true,
			)
		);

		$this->add_control(
			'title_line_two',
			array(
				'label'       => 'خط دوم عنوان',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'اقدامات',
				'label_block' => true,
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'value',
			array(
				'label'       => 'مقدار',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'step'        => 1,
				'description' => 'عدد را بدون جداکننده وارد کنید؛ جداکننده هزارگان فقط در خروجی سایت اعمال می‌شود.',
			)
		);

		$repeater->add_control(
			'label',
			array(
				'label'       => 'عنوان آمار',
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => 'آمارها',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label || "آمار" }}}',
				'default'     => array(
					array(
						'value' => 266549,
						'label' => 'خدمات چشم‌پزشکی',
					),
					array(
						'value' => 348969,
						'label' => 'خدمات دندان‌پزشکی',
					),
					array(
						'value' => 109729,
						'label' => 'خدمات به مادران باردار',
					),
					array(
						'value' => 17939,
						'label' => 'غربالگری کودکان',
					),
					array(
						'value' => 25232,
						'label' => 'ویزیت برخط',
					),
					array(
						'value' => 1163646,
						'label' => 'آموزش و پیشگیری',
					),
				),
			)
		);

		$this->end_controls_section();
	}


	/**
	 * Swiper Grid: each column contains the configured number of rows.
	 * The defaults display six items at every breakpoint (6x1 / 3x2 / 2x3).
	 */
	private function register_carousel_controls() {
		$this->start_controls_section(
			'content_stats_carousel',
			array( 'label' => 'تنظیمات کاروسل', 'tab' => Controls_Manager::TAB_CONTENT )
		);

		$this->add_responsive_control(
			'carousel_columns',
			array(
				'label'          => 'تعداد ستون قابل نمایش',
				'type'           => Controls_Manager::NUMBER,
				'min'            => 1,
				'max'            => 12,
				'step'           => 1,
				'default'        => 6,
				'tablet_default' => 3,
				'mobile_default' => 2,
			)
		);
		$this->add_responsive_control(
			'carousel_rows',
			array(
				'label'          => 'تعداد ردیف',
				'type'           => Controls_Manager::NUMBER,
				'min'            => 1,
				'max'            => 6,
				'step'           => 1,
				'default'        => 1,
				'tablet_default' => 2,
				'mobile_default' => 3,
			)
		);
		$this->add_responsive_control(
			'carousel_group',
			array(
				'label'          => 'تعداد ستون در هر حرکت',
				'type'           => Controls_Manager::NUMBER,
				'min'            => 1,
				'max'            => 12,
				'step'           => 1,
				'default'        => 1,
				'tablet_default' => 1,
				'mobile_default' => 1,
			)
		);
		$this->add_responsive_control(
			'carousel_gap',
			array(
				'label'          => 'فاصله افقی آیتم‌ها',
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),
				'default'        => array( 'size' => 0, 'unit' => 'px' ),
				'tablet_default' => array( 'size' => 0, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 0, 'unit' => 'px' ),
			)
		);
		$this->add_responsive_control(
			'carousel_row_gap',
			array(
				'label'          => 'فاصله عمودی ردیف‌ها',
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),
				'default'        => array( 'size' => 0, 'unit' => 'px' ),
				'tablet_default' => array( 'size' => 0, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 0, 'unit' => 'px' ),
			)
		);
		$this->add_control(
			'carousel_navigation',
			array(
				'label' => 'فلش‌های ناوبری', 'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes', 'default' => 'yes',
			)
		);
		$this->add_control(
			'carousel_pagination',
			array(
				'label' => 'صفحه‌بندی',
				'type' => Controls_Manager::SELECT,
				'options' => array(
					'none' => 'بدون صفحه‌بندی',
					'bullets' => 'نقطه‌ای',
					'fraction' => 'شماره صفحات',
					'progressbar' => 'نوار پیشرفت',
				),
				'default' => 'none',
			)
		);
		$this->add_control(
			'carousel_autoplay',
			array(
				'label' => 'حرکت خودکار', 'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes', 'default' => '',
			)
		);
		$this->add_control(
			'carousel_delay',
			array(
				'label' => 'فاصله حرکت خودکار (میلی‌ثانیه)',
				'type' => Controls_Manager::NUMBER, 'min' => 500, 'max' => 20000,
				'step' => 100, 'default' => 5000,
				'condition' => array( 'carousel_autoplay' => 'yes' ),
			)
		);
		$this->add_control(
			'carousel_pause_hover',
			array(
				'label' => 'توقف هنگام قرارگیری ماوس', 'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes', 'default' => 'yes',
				'condition' => array( 'carousel_autoplay' => 'yes' ),
			)
		);
		$this->add_control(
			'carousel_disable_interaction',
			array(
				'label' => 'توقف خودکار پس از تعامل', 'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes', 'default' => 'yes',
				'condition' => array( 'carousel_autoplay' => 'yes' ),
			)
		);
		$this->add_control(
			'carousel_speed',
			array(
				'label' => 'سرعت تغییر اسلاید (میلی‌ثانیه)',
				'type' => Controls_Manager::NUMBER, 'min' => 100, 'max' => 4000,
				'step' => 50, 'default' => 450,
			)
		);
		// Loop and Free Mode are unsupported by Swiper Grid when rows > 1.
		$single_row_conditions = array(
			'terms' => array(
				array( 'name' => 'carousel_rows', 'operator' => '==', 'value' => 1 ),
				array( 'name' => 'carousel_rows_tablet', 'operator' => '==', 'value' => 1 ),
				array( 'name' => 'carousel_rows_mobile', 'operator' => '==', 'value' => 1 ),
			),
		);
		$this->add_control(
			'carousel_loop',
			array(
				'label' => 'چرخش بی‌نهایت (فقط تک‌ردیفه)',
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes', 'default' => '',
				'conditions' => $single_row_conditions,
			)
		);
		$this->add_control(
			'carousel_free_mode',
			array(
				'label' => 'حرکت آزاد (فقط تک‌ردیفه)',
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes', 'default' => '',
				'conditions' => $single_row_conditions,
			)
		);
		$this->add_control(
			'carousel_touch',
			array(
				'label' => 'کشیدن با لمس و ماوس', 'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes', 'default' => 'yes',
			)
		);
		$this->add_control(
			'carousel_grab_cursor',
			array(
				'label' => 'نشانگر کشیدن', 'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes', 'default' => '',
				'condition' => array( 'carousel_touch' => 'yes' ),
			)
		);
		$this->add_control(
			'carousel_keyboard',
			array(
				'label' => 'ناوبری با صفحه‌کلید', 'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes', 'default' => 'yes',
			)
		);
		$this->end_controls_section();
	}

	private function register_carousel_style_controls() {
		$this->start_controls_section(
			'style_stats_carousel',
			array( 'label' => 'ناوبری کاروسل', 'tab' => Controls_Manager::TAB_STYLE )
		);
		$this->add_control(
			'carousel_arrow_color',
			array(
				'label' => 'رنگ فلش‌ها', 'type' => Controls_Manager::COLOR,
				'condition' => array( 'carousel_navigation' => 'yes' ),
				'selectors' => array( '{{WRAPPER}} .ba-live-stats__arrow' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'carousel_arrow_background',
			array(
				'label' => 'پس‌زمینه فلش‌ها', 'type' => Controls_Manager::COLOR,
				'condition' => array( 'carousel_navigation' => 'yes' ),
				'selectors' => array( '{{WRAPPER}} .ba-live-stats__arrow' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'carousel_arrow_size',
			array(
				'label' => 'اندازه فلش‌ها', 'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 20, 'max' => 64 ) ),
				'condition' => array( 'carousel_navigation' => 'yes' ),
				'selectors' => array( '{{WRAPPER}} .ba-live-stats__arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'carousel_pagination_color',
			array(
				'label' => 'رنگ صفحه‌بندی', 'type' => Controls_Manager::COLOR,
				'condition' => array( 'carousel_pagination!' => 'none' ),
				'selectors' => array(
					'{{WRAPPER}} .ba-live-stats__pagination' => 'color: {{VALUE}}; --swiper-pagination-color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های layout بدون default؛ CSS مرجع تا زمان تغییر کاربر authoritative است.
	 */
	private function register_layout_style_controls() {
		$this->start_controls_section(
			'style_layout',
			array(
				'label' => 'چیدمان و پنل',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'ref_section_padding_top',
			array(
				'label'      => 'فاصله بالا',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-home-live-stats-widget' => 'padding-top: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_panel_min_height',
			array(
				'label'      => 'حداقل ارتفاع پنل',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 60, 'max' => 240 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-live-stats__panel' => 'min-height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_panel_gap',
			array(
				'label'      => 'فاصله عنوان و کارت آمار',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-live-stats__panel' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_panel_padding',
			array(
				'label'      => 'فاصله داخلی پوسته',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .ba-live-stats__panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_panel_radius',
			array(
				'label'      => 'گردی پوسته',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-live-stats__panel' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'ref_panel_background',
			array(
				'label'     => 'رنگ پوسته',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-live-stats__panel' => 'background: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'ref_panel_border',
				'selector' => '{{WRAPPER}} .ba-live-stats__panel',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'ref_panel_shadow',
				'selector' => '{{WRAPPER}} .ba-live-stats__panel',
			)
		);

		$this->add_responsive_control(
			'ref_title_column_width',
			array(
				'label'      => 'عرض ستون عنوان',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 80, 'max' => 320 ),
					'%'  => array( 'min' => 10, 'max' => 50 ),
				),
				'selectors'  => array( '{{WRAPPER}} .ba-live-stats__panel' => 'grid-template-columns: {{SIZE}}{{UNIT}} minmax(0, 1fr);' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_title_style_controls() {
		$this->start_controls_section(
			'style_title',
			array(
				'label' => 'عنوان گزارش',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_title_typography',
				'label'    => 'تایپوگرافی',
				'selector' => '{{WRAPPER}} .ba-live-stats__title-line',
			)
		);

		$this->add_control(
			'ref_title_color',
			array(
				'label'     => 'رنگ متن',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-live-stats__title' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'ref_title_background',
			array(
				'label'     => 'پس‌زمینه',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-live-stats__title' => 'background: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_title_padding',
			array(
				'label'      => 'فاصله داخلی',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .ba-live-stats__title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_title_gap',
			array(
				'label'      => 'فاصله دو خط',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-live-stats__title' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_stats_style_controls() {
		$this->start_controls_section(
			'style_stats',
			array(
				'label' => 'آمارها',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ref_grid_background',
			array(
				'label'     => 'پس‌زمینه کارت آمار',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-live-stats__grid' => 'background: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_grid_radius',
			array(
				'label'      => 'گردی کارت آمار',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-live-stats__grid' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_item_padding',
			array(
				'label'      => 'فاصله داخلی آیتم',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .ba-live-stats__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_value_typography',
				'label'    => 'تایپوگرافی عدد',
				'selector' => '{{WRAPPER}} .ba-live-stats__value',
			)
		);

		$this->add_control(
			'ref_value_color',
			array(
				'label'     => 'رنگ عدد',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-live-stats__value' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_label_typography',
				'label'    => 'تایپوگرافی عنوان آمار',
				'selector' => '{{WRAPPER}} .ba-live-stats__label',
			)
		);

		$this->add_control(
			'ref_label_color',
			array(
				'label'     => 'رنگ عنوان آمار',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-live-stats__label' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'ref_separator_color',
			array(
				'label'     => 'رنگ جداکننده',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-live-stats__item:not(:last-child)::after' => 'background: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings       = $this->get_settings_for_display();
		$title_line_one = trim( (string) ( $settings['title_line_one'] ?? '' ) );
		$title_line_two = trim( (string) ( $settings['title_line_two'] ?? '' ) );
		$items          = array_values( array_filter( (array) ( $settings['items'] ?? array() ), 'is_array' ) );
		// Elementor responsive values are stored under suffixes _tablet and _mobile.
		$carousel_sizes = array(
			'desktop' => array( '', 6, 1 ),
			'tablet'  => array( '_tablet', 3, 2 ),
			'mobile'  => array( '_mobile', 2, 3 ),
		);
		$carousel_config = array(
			'navigation'         => 'yes' === ( $settings['carousel_navigation'] ?? 'yes' ),
			'pagination'         => in_array( $settings['carousel_pagination'] ?? 'none', array( 'bullets', 'fraction', 'progressbar' ), true ) ? $settings['carousel_pagination'] : 'none',
			'autoplay'           => 'yes' === ( $settings['carousel_autoplay'] ?? '' ),
			'delay'              => max( 500, min( 20000, absint( $settings['carousel_delay'] ?? 5000 ) ) ),
			'speed'              => max( 100, min( 4000, absint( $settings['carousel_speed'] ?? 450 ) ) ),
			'loop'               => 'yes' === ( $settings['carousel_loop'] ?? '' ),
			'freeMode'           => 'yes' === ( $settings['carousel_free_mode'] ?? '' ),
			'pauseHover'         => 'yes' === ( $settings['carousel_pause_hover'] ?? 'yes' ),
			'disableInteraction' => 'yes' === ( $settings['carousel_disable_interaction'] ?? 'yes' ),
			'touch'              => 'yes' === ( $settings['carousel_touch'] ?? 'yes' ),
			'grabCursor'         => 'yes' === ( $settings['carousel_grab_cursor'] ?? '' ),
			'keyboard'           => 'yes' === ( $settings['carousel_keyboard'] ?? 'yes' ),
		);
		foreach ( $carousel_sizes as $device => $dimensions ) {
			list( $suffix, $fallback_columns, $fallback_rows ) = $dimensions;
			$columns = max( 1, min( 12, absint( $settings['carousel_columns' . $suffix] ?? $fallback_columns ) ) );
			$rows    = max( 1, min( 6, absint( $settings['carousel_rows' . $suffix] ?? $fallback_rows ) ) );
			$gap     = $settings['carousel_gap' . $suffix]['size'] ?? 0;
			$row_gap = $settings['carousel_row_gap' . $suffix]['size'] ?? 0;
			$carousel_config[ $device ] = array(
				'columns' => $columns,
				'rows'    => $rows,
				'group'   => max( 1, min( $columns, absint( $settings['carousel_group' . $suffix] ?? 1 ) ) ),
				'gap'     => max( 0, min( 48, (float) $gap ) ),
				'rowGap'  => max( 0, min( 48, (float) $row_gap ) ),
			);
		}

		if ( ! $items ) {
			$this->render_editor_notice();
			return;
		}
		?>
		<section aria-label="گزارش برخط اقدامات" class="ba-live-stats ba-home-live-stats-widget">
			<div class="ba-container">
				<div aria-label="گزارش برخط اقدامات" class="ba-live-stats__panel">
					<div class="ba-live-stats__title">
						<span class="ba-live-stats__title-line"><?php echo esc_html( $title_line_one ); ?></span>
						<span class="ba-live-stats__title-line"><?php echo esc_html( $title_line_two ); ?></span>
					</div>
					<div class="ba-live-stats__carousel">
						<div class="ba-live-stats__grid swiper" dir="rtl" data-ba-live-stats-carousel="<?php echo esc_attr( wp_json_encode( $carousel_config ) ); ?>">
							<div class="swiper-wrapper">
								<?php foreach ( $items as $item ) : ?>
									<div class="ba-live-stats__item swiper-slide">
										<b class="ba-live-stats__value"><?php echo esc_html( $this->format_stat_value( $item['value'] ?? '' ) ); ?></b>
										<span class="ba-live-stats__label"><?php echo esc_html( trim( (string) ( $item['label'] ?? '' ) ) ); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="ba-live-stats__controls">
							<button class="ba-live-stats__arrow" type="button" data-ba-stats-prev aria-label="آمار قبلی"><span aria-hidden="true">&#x276F;</span></button>
							<div class="ba-live-stats__pagination" data-ba-stats-pagination></div>
							<button class="ba-live-stats__arrow" type="button" data-ba-stats-next aria-label="آمار بعدی"><span aria-hidden="true">&#x276E;</span></button>
						</div>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * عدد خام Elementor را فقط در خروجی سایت به ارقام فارسی + جداکننده هزارگان مرجع تبدیل می‌کند.
	 *
	 * @param mixed $value مقدار NUMBER control.
	 */
	private function format_stat_value( $value ): string {
		$normalized = strtr(
			trim( (string) $value ),
			array(
				'۰' => '0',
				'۱' => '1',
				'۲' => '2',
				'۳' => '3',
				'۴' => '4',
				'۵' => '5',
				'۶' => '6',
				'۷' => '7',
				'۸' => '8',
				'۹' => '9',
				'٠' => '0',
				'١' => '1',
				'٢' => '2',
				'٣' => '3',
				'٤' => '4',
				'٥' => '5',
				'٦' => '6',
				'٧' => '7',
				'٨' => '8',
				'٩' => '9',
			)
		);

		$digits = preg_replace( '/\D+/', '', $normalized );
		if ( ! is_string( $digits ) || '' === $digits ) {
			return '';
		}

		$digits    = ltrim( $digits, '0' );
		$digits    = '' === $digits ? '0' : $digits;
		$formatted = preg_replace( '/\B(?=(\d{3})+(?!\d))/', '٬', $digits );
		$formatted = is_string( $formatted ) ? $formatted : $digits;

		return strtr(
			$formatted,
			array(
				'0' => '۰',
				'1' => '۱',
				'2' => '۲',
				'3' => '۳',
				'4' => '۴',
				'5' => '۵',
				'6' => '۶',
				'7' => '۷',
				'8' => '۸',
				'9' => '۹',
			)
		);
	}

	private function render_editor_notice() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}

		echo '<div class="elementor-alert elementor-alert-info">برای نمایش گزارش برخط حداقل یک آمار اضافه کنید.</div>';
	}
}
