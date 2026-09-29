<?php
/**
 * ویجت Hero صفحه اصلی بنیاد علوی.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

/**
 * Hero صفحه اصلی شامل حوزه‌های فعالیت، اسلایدر و Ticker پویا.
 */
final class Bonyad_Alavi_Home_Hero_Widget extends Widget_Base {

	public function get_name() {
		return 'bonyad_alavi_home_hero';
	}

	public function get_title() {
		return esc_html__( 'هیرو صفحه اصلی بنیاد علوی', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	public function get_style_depends() {
		return array( 'bonyad-alavi-home-hero-widget' );
	}

	public function get_script_depends() {
		return array( 'bonyad-alavi-home-hero-widget' );
	}

	protected function register_controls() {
		$this->register_slider_controls();
		$this->register_mission_controls();
		$this->register_ticker_controls();
		$this->register_ticker_query_controls();
		$this->register_layout_style_controls();
		$this->register_mission_style_controls();
		$this->register_slider_style_controls();
		$this->register_navigation_style_controls();
		$this->register_ticker_style_controls();
	}

	private function register_slider_controls() {
		$this->start_controls_section( 'content_slider', array( 'label' => 'اسلایدر اصلی', 'tab' => Controls_Manager::TAB_CONTENT ) );

		$repeater = new Repeater();
		$repeater->add_control( 'image', array( 'label' => 'تصویر', 'type' => Controls_Manager::MEDIA ) );
		$repeater->add_control( 'tag', array( 'label' => 'برچسب', 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$repeater->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXTAREA, 'rows' => 2 ) );
		$repeater->add_control( 'description', array( 'label' => 'توضیحات', 'type' => Controls_Manager::TEXTAREA, 'rows' => 3 ) );
		$repeater->add_control( 'button_text', array( 'label' => 'متن دکمه', 'type' => Controls_Manager::TEXT ) );
		$repeater->add_control(
			'link',
			array(
				'label'       => 'لینک',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://',
				'options'     => array( 'url', 'is_external', 'nofollow' ),
			)
		);

		$this->add_control(
			'slides',
			array(
				'label'       => 'اسلایدها',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title || tag || "اسلاید" }}}',
				'default'     => array(
					array(
						'tag'         => 'پویش ملی',
						'title'       => 'پویش مهر علوی؛ همراهی برای فرصت‌های برابر',
						'description' => 'گزارش‌ها و رویدادهای شاخص بنیاد علوی را در یک قاب شفاف و قابل دسترس دنبال کنید.',
						'button_text' => 'مشاهده جزئیات',
						'link'        => array( 'url' => '' ),
					),
					array(
						'tag'         => 'زیرساخت و آبادانی',
						'title'       => 'همراه مناطق هدف؛ از نیازسنجی تا اقدام ماندگار',
						'description' => 'تمرکز بر پروژه‌هایی که کیفیت زندگی و ظرفیت‌های محلی را به‌صورت پایدار ارتقا می‌دهند.',
						'button_text' => 'مشاهده اقدامات',
						'link'        => array( 'url' => '' ),
					),
					array(
						'tag'         => 'اخبار بنیاد',
						'title'       => 'روایت روشن از اثرگذاری اجتماعی و اقتصادی',
						'description' => 'آخرین خبرها، گزارش‌ها و دستاوردهای برنامه‌های بنیاد در سراسر کشور.',
						'button_text' => 'آخرین اخبار',
						'link'        => array( 'url' => '' ),
					),
				),
			)
		);

		$this->add_control(
			'slider_title_tag',
			array(
				'label'   => 'تگ HTML عنوان اسلاید',
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6', 'div' => 'DIV' ),
			)
		);
		$this->add_control( 'slider_autoplay', array( 'label' => 'پخش خودکار', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'slider_autoplay_delay', array( 'label' => 'فاصله تعویض اسلاید (ms)', 'type' => Controls_Manager::NUMBER, 'min' => 1500, 'max' => 30000, 'step' => 100, 'default' => 6200, 'condition' => array( 'slider_autoplay' => 'yes' ) ) );
		$this->add_control( 'slider_pause_on_hover', array( 'label' => 'توقف روی Hover / Focus', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'condition' => array( 'slider_autoplay' => 'yes' ) ) );
		$this->add_control( 'slider_show_arrows', array( 'label' => 'نمایش فلش‌ها', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'slider_show_dots', array( 'label' => 'نمایش Pagination', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->end_controls_section();
	}

	private function register_mission_controls() {
		$this->start_controls_section( 'content_missions', array( 'label' => 'حوزه‌های اصلی فعالیت', 'tab' => Controls_Manager::TAB_CONTENT ) );

		$repeater = new Repeater();
		$repeater->add_control( 'icon', array( 'label' => 'آیکون', 'type' => Controls_Manager::ICONS ) );
		$repeater->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$repeater->add_control( 'description', array( 'label' => 'توضیحات', 'type' => Controls_Manager::TEXTAREA, 'rows' => 2 ) );
		$repeater->add_control(
			'link',
			array(
				'label'       => 'لینک',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://',
				'options'     => array( 'url', 'is_external', 'nofollow' ),
			)
		);

		$this->add_control(
			'missions',
			array(
				'label'       => 'معاونت‌ها / حوزه‌ها',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title || "حوزه فعالیت" }}}',
				'default'     => array(
					array(
						'icon'        => array( 'value' => 'fas fa-briefcase', 'library' => 'fa-solid' ),
						'title'       => 'معاونت توانمندسازی اقتصادی',
						'description' => 'ایجاد فرصت‌های شغلی برای کسب درآمد پایدار',
						'link'        => array( 'url' => 'https://bonyadalavi.ir/eghtesadi/' ),
					),
					array(
						'icon'        => array( 'value' => 'fas fa-graduation-cap', 'library' => 'fa-solid' ),
						'title'       => 'معاونت آموزش، مهارت و پرورش',
						'description' => 'توسعه آموزش و مهارت برای شکوفایی استعدادها',
						'link'        => array( 'url' => 'https://bonyadalavi.ir/amoozesh/' ),
					),
					array(
						'icon'        => array( 'value' => 'fas fa-stethoscope', 'library' => 'fa-solid' ),
						'title'       => 'معاونت بهداشت و درمان',
						'description' => 'گسترش خدمات سلامت با هدف پیشگیری و درمان',
						'link'        => array( 'url' => 'https://bonyadalavi.ir/salamat/' ),
					),
					array(
						'icon'        => array( 'value' => 'fas fa-building', 'library' => 'fa-solid' ),
						'title'       => 'معاونت عمرانی و زیرساختی',
						'description' => 'توسعه زیرساخت‌های ضروری برای دسترسی بهتر و ایمن تر',
						'link'        => array( 'url' => 'https://bonyadalavi.ir/eskan/' ),
					),
				),
			)
		);
		$this->end_controls_section();
	}

	private function register_ticker_controls() {
		$this->start_controls_section( 'content_ticker', array( 'label' => 'اخبار مهم', 'tab' => Controls_Manager::TAB_CONTENT ) );
		$this->add_control( 'ticker_label', array( 'label' => 'برچسب', 'type' => Controls_Manager::TEXT, 'default' => 'اخبار مهم' ) );
		$this->add_control( 'ticker_autoplay', array( 'label' => 'گردش خودکار', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'ticker_delay', array( 'label' => 'فاصله تعویض خبر (ms)', 'type' => Controls_Manager::NUMBER, 'min' => 1500, 'max' => 30000, 'step' => 100, 'default' => 6000, 'condition' => array( 'ticker_autoplay' => 'yes' ) ) );
		$this->end_controls_section();
	}

	private function register_ticker_query_controls() {
		$this->start_controls_section( 'query_ticker', array( 'label' => 'Query اخبار مهم', 'tab' => Controls_Manager::TAB_CONTENT ) );
		$this->add_control( 'ticker_post_types', array( 'label' => 'Post Type', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'label_block' => true, 'options' => $this->get_post_type_options(), 'default' => array( 'post' ) ) );
		$this->add_control( 'ticker_posts_per_page', array( 'label' => 'تعداد نمایش', 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 30, 'default' => 3 ) );
		$this->add_control( 'ticker_categories', array( 'label' => 'دسته‌بندی‌ها', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'label_block' => true, 'options' => $this->get_term_options( 'category' ) ) );
		$this->add_control( 'ticker_tags', array( 'label' => 'برچسب‌ها', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'label_block' => true, 'options' => $this->get_term_options( 'post_tag' ) ) );
		$this->add_control( 'ticker_authors', array( 'label' => 'نویسندگان', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'label_block' => true, 'options' => $this->get_author_options() ) );
		$this->add_control( 'ticker_include_ids', array( 'label' => 'فقط شناسه نوشته‌ها', 'type' => Controls_Manager::TEXT, 'description' => 'شناسه‌ها را با ویرگول جدا کنید.' ) );
		$this->add_control( 'ticker_exclude_ids', array( 'label' => 'حذف شناسه نوشته‌ها', 'type' => Controls_Manager::TEXT, 'description' => 'شناسه‌ها را با ویرگول جدا کنید.' ) );
		$this->add_control(
			'ticker_orderby',
			array(
				'label'   => 'مرتب‌سازی بر اساس',
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array( 'date' => 'تاریخ انتشار', 'modified' => 'آخرین ویرایش', 'title' => 'عنوان', 'menu_order' => 'ترتیب منو', 'rand' => 'تصادفی', 'ID' => 'شناسه' ),
			)
		);
		$this->add_control( 'ticker_order', array( 'label' => 'ترتیب', 'type' => Controls_Manager::SELECT, 'default' => 'DESC', 'options' => array( 'DESC' => 'نزولی', 'ASC' => 'صعودی' ) ) );
		$this->add_control( 'ticker_offset', array( 'label' => 'Offset', 'type' => Controls_Manager::NUMBER, 'min' => 0, 'default' => 0 ) );
		$this->add_control( 'ticker_ignore_sticky', array( 'label' => 'نادیده گرفتن Sticky Posts', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'ticker_date_after', array( 'label' => 'تاریخ از', 'type' => Controls_Manager::DATE_TIME ) );
		$this->add_control( 'ticker_date_before', array( 'label' => 'تاریخ تا', 'type' => Controls_Manager::DATE_TIME ) );
		$this->end_controls_section();
	}

	private function register_layout_style_controls() {
		$this->start_controls_section( 'style_layout', array( 'label' => 'چیدمان و ابعاد', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control(
			'container_width',
			array(
				'label'      => 'حداکثر عرض ویجت',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 600, 'max' => 1800 ), '%' => array( 'min' => 50, 'max' => 100 ) ),
				'default'    => array( 'size' => 1280, 'unit' => 'px' ),
				'selectors'  => array( '{{WRAPPER}} .ba-home-hero' => '--ba-home-hero-max-width: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control( 'grid_gap', array( 'label' => 'فاصله بین بخش‌ها', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ) ), 'default' => array( 'size' => 10, 'unit' => 'px' ), 'mobile_default' => array( 'size' => 8, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero' => '--ba-home-hero-gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'mission_column_width', array( 'label' => 'عرض ستون حوزه‌ها در دسکتاپ', 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => 18, 'max' => 45 ) ), 'default' => array( 'size' => 27, 'unit' => '%' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero' => '--ba-home-hero-mission-width: {{SIZE}}%;' ) ) );
		$this->add_responsive_control(
			'slider_height',
			array(
				'label'          => 'ارتفاع اسلایدر',
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px', 'vh' ),
				'range'          => array( 'px' => array( 'min' => 240, 'max' => 900 ), 'vh' => array( 'min' => 20, 'max' => 100 ) ),
				'default'        => array( 'size' => 410, 'unit' => 'px' ),
				'tablet_default' => array( 'size' => 390, 'unit' => 'px' ),
				'selectors'      => array( '{{WRAPPER}} .ba-home-hero' => '--ba-home-hero-slider-height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control( 'mobile_aspect_ratio', array( 'label' => 'نسبت اسلایدر موبایل', 'type' => Controls_Manager::SELECT, 'default' => '4 / 3', 'options' => array( '1 / 1' => '1:1', '4 / 3' => '4:3', '3 / 2' => '3:2', '16 / 9' => '16:9', '3 / 4' => '3:4' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero' => '--ba-home-hero-mobile-aspect: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	private function register_mission_style_controls() {
		$this->start_controls_section( 'style_missions', array( 'label' => 'حوزه‌های فعالیت', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_color_control( 'mission_panel_background', 'پس‌زمینه پنل', '{{WRAPPER}} .ba-home-hero__mission-nav', 'background-color', '#ffffff' );
		$this->add_color_control( 'mission_panel_border_color', 'رنگ کادر پنل', '{{WRAPPER}} .ba-home-hero__mission-nav', 'border-color', '#a9cdb8' );
		$this->add_color_control( 'mission_divider_color', 'رنگ جداکننده‌ها', '{{WRAPPER}} .ba-home-hero__mission-item', 'border-color', '#bdd9c7' );
		$this->add_responsive_control( 'mission_panel_radius', array( 'label' => 'گردی پنل', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px' ), 'default' => array( 'top' => 18, 'right' => 7, 'bottom' => 7, 'left' => 18, 'unit' => 'px', 'isLinked' => false ), 'tablet_default' => array( 'top' => 18, 'right' => 18, 'bottom' => 18, 'left' => 18, 'unit' => 'px', 'isLinked' => true ), 'mobile_default' => array( 'top' => 18, 'right' => 18, 'bottom' => 18, 'left' => 18, 'unit' => 'px', 'isLinked' => true ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__mission-nav' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'mission_panel_shadow', 'selector' => '{{WRAPPER}} .ba-home-hero__mission-nav' ) );
		$this->add_responsive_control( 'mission_item_padding', array( 'label' => 'فاصله داخلی آیتم', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'default' => array( 'top' => 16, 'right' => 20, 'bottom' => 16, 'left' => 20, 'unit' => 'px', 'isLinked' => false ), 'tablet_default' => array( 'top' => 14, 'right' => 18, 'bottom' => 14, 'left' => 18, 'unit' => 'px', 'isLinked' => false ), 'mobile_default' => array( 'top' => 13, 'right' => 13, 'bottom' => 13, 'left' => 13, 'unit' => 'px', 'isLinked' => true ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__mission-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'mission_item_gap', array( 'label' => 'فاصله آیکون و متن', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 14, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__mission-item' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_typography_control( 'mission_title_typography', 'تایپوگرافی عنوان', '{{WRAPPER}} .ba-home-hero__mission-title' );
		$this->add_typography_control( 'mission_description_typography', 'تایپوگرافی توضیحات', '{{WRAPPER}} .ba-home-hero__mission-description' );

		$this->start_controls_tabs( 'mission_state_tabs' );
		$this->start_controls_tab( 'mission_state_normal', array( 'label' => 'عادی' ) );
		$this->add_color_control( 'mission_item_background', 'پس‌زمینه آیتم', '{{WRAPPER}} .ba-home-hero__mission-item', 'background-color' );
		$this->add_color_control( 'mission_title_color', 'رنگ عنوان', '{{WRAPPER}} .ba-home-hero__mission-title', 'color', '#173329' );
		$this->add_color_control( 'mission_description_color', 'رنگ توضیحات', '{{WRAPPER}} .ba-home-hero__mission-description', 'color', '#67736b' );
		$this->add_color_control( 'mission_icon_color', 'رنگ آیکون', '{{WRAPPER}} .ba-home-hero__mission-icon', 'color', '#078541' );
		$this->add_color_control( 'mission_icon_background', 'پس‌زمینه آیکون', '{{WRAPPER}} .ba-home-hero__mission-icon', 'background-color', '#eef8f2' );
		$this->add_color_control( 'mission_icon_border_color', 'رنگ کادر آیکون', '{{WRAPPER}} .ba-home-hero__mission-icon', 'border-color', '#cae3d3' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'mission_state_hover', array( 'label' => 'هاور' ) );
		$this->add_color_control( 'mission_item_hover_background', 'پس‌زمینه آیتم', '{{WRAPPER}} .ba-home-hero__mission-item:hover', 'background-color', '#f0f8f3' );
		$this->add_color_control( 'mission_title_hover_color', 'رنگ عنوان', '{{WRAPPER}} .ba-home-hero__mission-item:hover .ba-home-hero__mission-title' );
		$this->add_color_control( 'mission_description_hover_color', 'رنگ توضیحات', '{{WRAPPER}} .ba-home-hero__mission-item:hover .ba-home-hero__mission-description' );
		$this->add_color_control( 'mission_icon_hover_color', 'رنگ آیکون', '{{WRAPPER}} .ba-home-hero__mission-item:hover .ba-home-hero__mission-icon' );
		$this->add_color_control( 'mission_icon_hover_background', 'پس‌زمینه آیکون', '{{WRAPPER}} .ba-home-hero__mission-item:hover .ba-home-hero__mission-icon', 'background-color' );
		$this->add_color_control( 'mission_icon_hover_border_color', 'رنگ کادر آیکون', '{{WRAPPER}} .ba-home-hero__mission-item:hover .ba-home-hero__mission-icon', 'border-color' );
		$this->add_responsive_control( 'mission_hover_shift', array( 'label' => 'جابجایی افقی', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => -20, 'max' => 20 ) ), 'default' => array( 'size' => -5, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__mission-item:hover' => 'transform: translateX({{SIZE}}{{UNIT}});' ) ) );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control( 'mission_icon_box_size', array( 'label' => 'اندازه کادر آیکون', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 24, 'max' => 100 ) ), 'default' => array( 'size' => 47, 'unit' => 'px' ), 'tablet_default' => array( 'size' => 42, 'unit' => 'px' ), 'mobile_default' => array( 'size' => 39, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__mission-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'mission_icon_size', array( 'label' => 'اندازه آیکون', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 10, 'max' => 60 ) ), 'default' => array( 'size' => 28, 'unit' => 'px' ), 'mobile_default' => array( 'size' => 26, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__mission-icon i' => 'font-size: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .ba-home-hero__mission-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'mission_icon_radius', array( 'label' => 'گردی کادر آیکون', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ), '%' => array( 'min' => 0, 'max' => 50 ) ), 'default' => array( 'size' => 12, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__mission-icon' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->end_controls_section();
	}

	private function register_slider_style_controls() {
		$this->start_controls_section( 'style_slider', array( 'label' => 'اسلایدر و محتوا', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_color_control( 'slider_background', 'پس‌زمینه اسلایدر', '{{WRAPPER}} .ba-home-hero__slider', 'background-color', '#073d27' );
		$this->add_responsive_control( 'slider_radius', array( 'label' => 'گردی اسلایدر', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px' ), 'default' => array( 'top' => 7, 'right' => 18, 'bottom' => 18, 'left' => 7, 'unit' => 'px', 'isLinked' => false ), 'tablet_default' => array( 'top' => 18, 'right' => 18, 'bottom' => 18, 'left' => 18, 'unit' => 'px', 'isLinked' => true ), 'mobile_default' => array( 'top' => 18, 'right' => 18, 'bottom' => 18, 'left' => 18, 'unit' => 'px', 'isLinked' => true ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__slider' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'slider_shadow', 'selector' => '{{WRAPPER}} .ba-home-hero__slider' ) );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'slider_overlay_background', 'label' => 'Overlay', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .ba-home-hero__slide::after' ) );
		$this->add_control( 'slider_image_fit', array( 'label' => 'نحوه نمایش تصویر', 'type' => Controls_Manager::SELECT, 'default' => 'cover', 'options' => array( 'cover' => 'Cover', 'contain' => 'Contain', 'fill' => 'Fill', 'none' => 'None', 'scale-down' => 'Scale Down' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__slide-image' => 'object-fit: {{VALUE}};' ) ) );
		$this->add_control( 'slider_image_position', array( 'label' => 'موقعیت تصویر', 'type' => Controls_Manager::SELECT, 'default' => 'center center', 'options' => array( 'center center' => 'وسط', 'center top' => 'بالا', 'center bottom' => 'پایین', 'right center' => 'راست', 'left center' => 'چپ', 'right top' => 'بالا راست', 'left top' => 'بالا چپ', 'right bottom' => 'پایین راست', 'left bottom' => 'پایین چپ' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__slide-image' => 'object-position: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'slider_content_width', array( 'label' => 'عرض محتوا', 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%', 'px' ), 'range' => array( '%' => array( 'min' => 20, 'max' => 100 ), 'px' => array( 'min' => 200, 'max' => 570 ) ), 'default' => array( 'size' => 75, 'unit' => '%' ), 'mobile_default' => array( 'size' => 90, 'unit' => '%' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__slider-content' => 'width: min(570px, {{SIZE}}{{UNIT}});' ) ) );
		$this->add_responsive_control( 'slider_content_right', array( 'label' => 'فاصله محتوا از راست', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 140 ), '%' => array( 'min' => 0, 'max' => 30 ) ), 'default' => array( 'size' => 42, 'unit' => 'px' ), 'mobile_default' => array( 'size' => 22, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__slider-content' => 'right: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'slider_content_bottom', array( 'label' => 'فاصله محتوا از پایین', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 180 ), '%' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 48, 'unit' => 'px' ), 'mobile_default' => array( 'size' => 70, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__slider-content' => 'bottom: {{SIZE}}{{UNIT}};' ) ) );

		$this->add_control( 'slider_tag_heading', array( 'label' => 'برچسب اسلاید', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_typography_control( 'slider_tag_typography', 'تایپوگرافی برچسب', '{{WRAPPER}} .ba-home-hero__slider-tag' );
		$this->add_color_control( 'slider_tag_color', 'رنگ متن', '{{WRAPPER}} .ba-home-hero__slider-tag', 'color', '#ffffff' );
		$this->add_color_control( 'slider_tag_background', 'پس‌زمینه', '{{WRAPPER}} .ba-home-hero__slider-tag', 'background-color', 'rgba(255,255,255,.14)' );
		$this->add_color_control( 'slider_tag_border_color', 'رنگ کادر', '{{WRAPPER}} .ba-home-hero__slider-tag', 'border-color', 'rgba(255,255,255,.25)' );

		$this->add_control( 'slider_title_heading', array( 'label' => 'عنوان اسلاید', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_typography_control( 'slider_title_typography', 'تایپوگرافی عنوان', '{{WRAPPER}} .ba-home-hero__slider-title' );
		$this->add_color_control( 'slider_title_color', 'رنگ عنوان', '{{WRAPPER}} .ba-home-hero__slider-title', 'color', '#ffffff' );

		$this->add_control( 'slider_description_heading', array( 'label' => 'توضیحات اسلاید', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_typography_control( 'slider_description_typography', 'تایپوگرافی توضیحات', '{{WRAPPER}} .ba-home-hero__slider-description' );
		$this->add_color_control( 'slider_description_color', 'رنگ توضیحات', '{{WRAPPER}} .ba-home-hero__slider-description', 'color', 'rgba(255,255,255,.82)' );

		$this->add_control( 'slider_button_heading', array( 'label' => 'دکمه اسلاید', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_typography_control( 'slider_button_typography', 'تایپوگرافی دکمه', '{{WRAPPER}} .ba-home-hero__slider-button' );
		$this->add_responsive_control( 'slider_button_padding', array( 'label' => 'فاصله داخلی دکمه', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'default' => array( 'top' => 9, 'right' => 14, 'bottom' => 9, 'left' => 14, 'unit' => 'px', 'isLinked' => false ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__slider-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'slider_button_radius', array( 'label' => 'گردی دکمه', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'default' => array( 'size' => 10, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__slider-button' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->start_controls_tabs( 'slider_button_tabs' );
		$this->start_controls_tab( 'slider_button_normal', array( 'label' => 'عادی' ) );
		$this->add_color_control( 'slider_button_color', 'رنگ متن', '{{WRAPPER}} .ba-home-hero__slider-button', 'color', '#075433' );
		$this->add_color_control( 'slider_button_background', 'پس‌زمینه', '{{WRAPPER}} .ba-home-hero__slider-button', 'background-color', '#ffffff' );
		$this->add_color_control( 'slider_button_border_color', 'رنگ کادر', '{{WRAPPER}} .ba-home-hero__slider-button', 'border-color', '#b9d7c5' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'slider_button_hover', array( 'label' => 'هاور' ) );
		$this->add_color_control( 'slider_button_hover_color', 'رنگ متن', '{{WRAPPER}} .ba-home-hero__slider-button:hover', 'color', '#ffffff' );
		$this->add_color_control( 'slider_button_hover_background', 'پس‌زمینه', '{{WRAPPER}} .ba-home-hero__slider-button:hover', 'background-color', '#078541' );
		$this->add_color_control( 'slider_button_hover_border_color', 'رنگ کادر', '{{WRAPPER}} .ba-home-hero__slider-button:hover', 'border-color', '#078541' );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
	}

	private function register_navigation_style_controls() {
		$this->start_controls_section( 'style_navigation', array( 'label' => 'ناوبری اسلایدر', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'slider_arrow_heading', array( 'label' => 'فلش‌ها', 'type' => Controls_Manager::HEADING ) );
		$this->add_responsive_control( 'slider_arrow_size', array( 'label' => 'اندازه دکمه', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 24, 'max' => 80 ) ), 'default' => array( 'size' => 37, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__slider-arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'slider_arrow_icon_size', array( 'label' => 'اندازه آیکون', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 8, 'max' => 40 ) ), 'default' => array( 'size' => 17, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__slider-arrow svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->start_controls_tabs( 'slider_arrow_tabs' );
		$this->start_controls_tab( 'slider_arrow_normal', array( 'label' => 'عادی' ) );
		$this->add_color_control( 'slider_arrow_color', 'رنگ آیکون', '{{WRAPPER}} .ba-home-hero__slider-arrow', 'color', '#ffffff' );
		$this->add_color_control( 'slider_arrow_background', 'پس‌زمینه', '{{WRAPPER}} .ba-home-hero__slider-arrow', 'background-color', 'rgba(5,30,20,.34)' );
		$this->add_color_control( 'slider_arrow_border_color', 'رنگ کادر', '{{WRAPPER}} .ba-home-hero__slider-arrow', 'border-color', 'rgba(255,255,255,.35)' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'slider_arrow_hover', array( 'label' => 'هاور' ) );
		$this->add_color_control( 'slider_arrow_hover_color', 'رنگ آیکون', '{{WRAPPER}} .ba-home-hero__slider-arrow:hover', 'color', '#073d27' );
		$this->add_color_control( 'slider_arrow_hover_background', 'پس‌زمینه', '{{WRAPPER}} .ba-home-hero__slider-arrow:hover', 'background-color', '#ffffff' );
		$this->add_color_control( 'slider_arrow_hover_border_color', 'رنگ کادر', '{{WRAPPER}} .ba-home-hero__slider-arrow:hover', 'border-color', '#ffffff' );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control( 'slider_dots_heading', array( 'label' => 'Pagination', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_responsive_control( 'slider_dot_size', array( 'label' => 'اندازه نقطه', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 3, 'max' => 20 ) ), 'default' => array( 'size' => 7, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__slider-dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'slider_dot_active_width', array( 'label' => 'عرض نقطه فعال', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 5, 'max' => 60 ) ), 'default' => array( 'size' => 27, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__slider-dot.is-active' => 'width: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_color_control( 'slider_dot_color', 'رنگ عادی', '{{WRAPPER}} .ba-home-hero__slider-dot', 'background-color', 'rgba(255,255,255,.5)' );
		$this->add_color_control( 'slider_dot_hover_color', 'رنگ هاور', '{{WRAPPER}} .ba-home-hero__slider-dot:hover', 'background-color', 'rgba(255,255,255,.8)' );
		$this->add_color_control( 'slider_dot_active_color', 'رنگ فعال', '{{WRAPPER}} .ba-home-hero__slider-dot.is-active', 'background-color', '#ffffff' );
		$this->end_controls_section();
	}

	private function register_ticker_style_controls() {
		$this->start_controls_section( 'style_ticker', array( 'label' => 'نوار اخبار مهم', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'ticker_height', array( 'label' => 'ارتفاع', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 32, 'max' => 90 ) ), 'default' => array( 'size' => 48, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero' => '--ba-home-hero-ticker-height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_color_control( 'ticker_background', 'پس‌زمینه نوار', '{{WRAPPER}} .ba-home-hero__ticker', 'background-color', '#ffffff' );
		$this->add_color_control( 'ticker_border_color', 'رنگ کادر', '{{WRAPPER}} .ba-home-hero__ticker', 'border-color', '#dce8e1' );
		$this->add_responsive_control( 'ticker_radius', array( 'label' => 'گردی نوار', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ) ), 'default' => array( 'size' => 12, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__ticker' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'ticker_shadow', 'selector' => '{{WRAPPER}} .ba-home-hero__ticker' ) );

		$this->add_control( 'ticker_label_heading', array( 'label' => 'برچسب نوار', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_typography_control( 'ticker_label_typography', 'تایپوگرافی برچسب', '{{WRAPPER}} .ba-home-hero__ticker-label' );
		$this->add_color_control( 'ticker_label_color', 'رنگ متن', '{{WRAPPER}} .ba-home-hero__ticker-label', 'color', '#ffffff' );
		$this->add_color_control( 'ticker_label_background', 'پس‌زمینه', '{{WRAPPER}} .ba-home-hero__ticker-label', 'background-color', '#078541' );
		$this->add_responsive_control( 'ticker_label_padding', array( 'label' => 'فاصله داخلی', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'default' => array( 'top' => 0, 'right' => 16, 'bottom' => 0, 'left' => 16, 'unit' => 'px', 'isLinked' => false ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero__ticker-label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );

		$this->add_control( 'ticker_item_heading', array( 'label' => 'عنوان خبر', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_typography_control( 'ticker_item_typography', 'تایپوگرافی عنوان خبر', '{{WRAPPER}} .ba-home-hero__ticker-item' );
		$this->start_controls_tabs( 'ticker_item_tabs' );
		$this->start_controls_tab( 'ticker_item_normal', array( 'label' => 'عادی' ) );
		$this->add_color_control( 'ticker_item_color', 'رنگ متن', '{{WRAPPER}} .ba-home-hero__ticker-item', 'color', '#30483d' );
		$this->add_color_control( 'ticker_item_background', 'پس‌زمینه', '{{WRAPPER}} .ba-home-hero__ticker-item', 'background-color' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'ticker_item_hover', array( 'label' => 'هاور' ) );
		$this->add_color_control( 'ticker_item_hover_color', 'رنگ متن', '{{WRAPPER}} .ba-home-hero__ticker-item:hover', 'color', '#075433' );
		$this->add_color_control( 'ticker_item_hover_background', 'پس‌زمینه', '{{WRAPPER}} .ba-home-hero__ticker-item:hover', 'background-color' );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->add_color_control( 'ticker_bullet_color', 'رنگ نشانگر', '{{WRAPPER}} .ba-home-hero__ticker-item::before', 'background-color', '#f5b73b' );
		$this->end_controls_section();
	}

	private function add_typography_control( $name, $label, $selector ) {
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $name, 'label' => $label, 'selector' => $selector ) );
	}

	private function add_color_control( $id, $label, $selector, $property = 'color', $default = '' ) {
		$config = array( 'label' => $label, 'type' => Controls_Manager::COLOR, 'selectors' => array( $selector => $property . ': {{VALUE}};' ) );
		if ( '' !== $default ) {
			$config['default'] = $default;
		}
		$this->add_control( $id, $config );
	}

	private function get_post_type_options() {
		$options = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			$options[ $type->name ] = $type->labels->singular_name ?: $type->label;
		}
		return $options;
	}

	private function get_term_options( $taxonomy ) {
		$options = array();
		$terms   = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			return $options;
		}
		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = $term->name;
		}
		return $options;
	}

	private function get_author_options() {
		$options = array();
		foreach ( get_users( array( 'who' => 'authors', 'fields' => array( 'ID', 'display_name' ) ) ) as $user ) {
			$options[ $user->ID ] = $user->display_name;
		}
		return $options;
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$slides   = array_values( array_filter( (array) ( $settings['slides'] ?? array() ), 'is_array' ) );
		$missions = array_values( array_filter( (array) ( $settings['missions'] ?? array() ), 'is_array' ) );
		$query    = ( new BA_Content_Query_Service() )->create_query( $settings, 'ticker' );
		$posts    = is_array( $query->posts ) ? $query->posts : array();

		if ( ! $slides && ! $missions && ! $posts ) {
			$this->render_editor_notice();
			return;
		}

		$classes   = array( 'ba-home-hero' );
		$classes[] = $posts ? 'ba-home-hero--has-ticker' : 'ba-home-hero--no-ticker';
		$classes[] = $missions ? 'ba-home-hero--has-missions' : 'ba-home-hero--no-missions';
		$classes[] = $slides ? 'ba-home-hero--has-slider' : 'ba-home-hero--no-slider';
		?>
		<section
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-ba-home-hero
			data-slider-autoplay="<?php echo 'yes' === ( $settings['slider_autoplay'] ?? 'yes' ) ? '1' : '0'; ?>"
			data-slider-delay="<?php echo esc_attr( (string) max( 1500, absint( $settings['slider_autoplay_delay'] ?? 6200 ) ) ); ?>"
			data-slider-pause-hover="<?php echo 'yes' === ( $settings['slider_pause_on_hover'] ?? 'yes' ) ? '1' : '0'; ?>"
			data-ticker-autoplay="<?php echo 'yes' === ( $settings['ticker_autoplay'] ?? 'yes' ) ? '1' : '0'; ?>"
			data-ticker-delay="<?php echo esc_attr( (string) max( 1500, absint( $settings['ticker_delay'] ?? 6000 ) ) ); ?>"
		>
			<div class="ba-home-hero__grid">
				<?php if ( $missions ) : ?>
					<aside class="ba-home-hero__mission-nav" aria-label="حوزه‌های اصلی فعالیت">
						<?php foreach ( $missions as $mission ) : ?>
							<?php $this->render_mission_item( $mission ); ?>
						<?php endforeach; ?>
					</aside>
				<?php endif; ?>

				<?php if ( $slides ) : ?>
					<div class="ba-home-hero__slider" data-ba-home-hero-slider role="region" aria-roledescription="carousel" aria-label="اخبار و رویدادهای شاخص" tabindex="0">
						<div class="ba-home-hero__slider-track">
							<?php foreach ( $slides as $index => $slide ) : ?>
								<?php $this->render_slide( $slide, $index, $settings ); ?>
							<?php endforeach; ?>
						</div>

						<?php if ( count( $slides ) > 1 && 'yes' === ( $settings['slider_show_dots'] ?? 'yes' ) ) : ?>
							<div class="ba-home-hero__slider-pagination" aria-label="انتخاب اسلاید">
								<?php foreach ( array_keys( $slides ) as $index ) : ?>
									<button type="button" class="ba-home-hero__slider-dot<?php echo 0 === $index ? ' is-active' : ''; ?>" data-ba-home-hero-dot aria-label="<?php echo esc_attr( sprintf( 'اسلاید %d', $index + 1 ) ); ?>" aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>"></button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( count( $slides ) > 1 && 'yes' === ( $settings['slider_show_arrows'] ?? 'yes' ) ) : ?>
							<div class="ba-home-hero__slider-controls">
								<button type="button" class="ba-home-hero__slider-arrow" data-ba-home-hero-arrow data-dir="prev" aria-label="اسلاید قبل"><?php echo $this->arrow_svg( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
								<button type="button" class="ba-home-hero__slider-arrow" data-ba-home-hero-arrow data-dir="next" aria-label="اسلاید بعد"><?php echo $this->arrow_svg( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $posts ) : ?>
					<div class="ba-home-hero__ticker<?php echo '' === trim( (string) ( $settings['ticker_label'] ?? '' ) ) ? ' ba-home-hero__ticker--no-label' : ''; ?>" data-ba-home-hero-ticker aria-label="اخبار مهم">
						<?php if ( '' !== trim( (string) ( $settings['ticker_label'] ?? '' ) ) ) : ?>
							<span class="ba-home-hero__ticker-label"><?php echo esc_html( $settings['ticker_label'] ); ?></span>
						<?php endif; ?>
						<div class="ba-home-hero__ticker-viewport" data-ba-home-hero-ticker-viewport>
							<div class="ba-home-hero__ticker-track" data-ba-home-hero-ticker-track>
								<?php foreach ( $posts as $post ) : ?>
									<a class="ba-home-hero__ticker-item" data-ba-home-hero-ticker-item href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
		wp_reset_postdata();
	}

	private function render_mission_item( array $mission ) {
		$title       = trim( (string) ( $mission['title'] ?? '' ) );
		$description = trim( (string) ( $mission['description'] ?? '' ) );
		$icon        = is_array( $mission['icon'] ?? null ) ? $mission['icon'] : array();
		$link        = is_array( $mission['link'] ?? null ) ? $mission['link'] : array();
		$has_link    = ! empty( $link['url'] );
		$tag         = $has_link ? 'a' : 'div';
		$attrs       = $has_link ? $this->build_link_attributes( $link ) : '';
		?>
		<<?php echo esc_attr( $tag ); ?> class="ba-home-hero__mission-item"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( ! empty( $icon['value'] ) ) : ?>
				<span class="ba-home-hero__mission-icon" aria-hidden="true"><?php Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $title || '' !== $description ) : ?>
				<span class="ba-home-hero__mission-copy">
					<?php if ( '' !== $title ) : ?><strong class="ba-home-hero__mission-title"><?php echo esc_html( $title ); ?></strong><?php endif; ?>
					<?php if ( '' !== $description ) : ?><span class="ba-home-hero__mission-description"><?php echo esc_html( $description ); ?></span><?php endif; ?>
				</span>
			<?php endif; ?>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	private function render_slide( array $slide, $index, array $settings ) {
		$tag         = trim( (string) ( $slide['tag'] ?? '' ) );
		$title       = trim( (string) ( $slide['title'] ?? '' ) );
		$description = trim( (string) ( $slide['description'] ?? '' ) );
		$button_text = trim( (string) ( $slide['button_text'] ?? '' ) );
		$link        = is_array( $slide['link'] ?? null ) ? $slide['link'] : array();
		$link_url    = trim( (string) ( $link['url'] ?? '' ) );
		$image       = is_array( $slide['image'] ?? null ) ? $slide['image'] : array();
		$has_button  = '' !== $button_text && '' !== $link_url;
		$whole_link  = '' !== $link_url && ! $has_button;
		$title_tag   = $this->sanitize_heading_tag( $settings['slider_title_tag'] ?? 'h2' );
		$alt         = ! empty( $image['id'] ) ? (string) get_post_meta( absint( $image['id'] ), '_wp_attachment_image_alt', true ) : '';
		$item        = array( 'id' => absint( $image['id'] ?? 0 ), 'url' => (string) ( $image['url'] ?? '' ), 'alt' => $alt );
		$class       = 'ba-home-hero__slide' . ( 0 === (int) $index ? ' is-active' : '' );
		?>
		<article class="<?php echo esc_attr( $class ); ?>" data-ba-home-hero-slide aria-hidden="<?php echo 0 === (int) $index ? 'false' : 'true'; ?>">
			<?php if ( $whole_link ) : ?><a class="ba-home-hero__slide-whole-link"<?php echo $this->build_link_attributes( $link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $title ?: $tag ?: 'مشاهده اسلاید' ); ?>"><?php endif; ?>

			<?php echo BA_Media_Helper::render_image( $item, 'full', array( 'class' => 'ba-home-hero__slide-image', 'loading' => 0 === (int) $index ? 'eager' : 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php if ( '' !== $tag || '' !== $title || '' !== $description || $has_button ) : ?>
				<div class="ba-home-hero__slider-content">
					<?php if ( '' !== $tag ) : ?><span class="ba-home-hero__slider-tag"><?php echo esc_html( $tag ); ?></span><?php endif; ?>
					<?php if ( '' !== $title ) : ?><<?php echo esc_attr( $title_tag ); ?> class="ba-home-hero__slider-title"><?php echo esc_html( $title ); ?></<?php echo esc_attr( $title_tag ); ?>><?php endif; ?>
					<?php if ( '' !== $description ) : ?><p class="ba-home-hero__slider-description"><?php echo esc_html( $description ); ?></p><?php endif; ?>
					<?php if ( $has_button ) : ?><a class="ba-home-hero__slider-button"<?php echo $this->build_link_attributes( $link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $button_text ); ?><span aria-hidden="true">←</span></a><?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $whole_link ) : ?></a><?php endif; ?>
		</article>
		<?php
	}

	private function build_link_attributes( array $link ) {
		$url = trim( (string) ( $link['url'] ?? '' ) );
		if ( '' === $url ) {
			return '';
		}

		$attrs = ' href="' . esc_url( $url ) . '"';
		$rel   = array();

		if ( ! empty( $link['is_external'] ) ) {
			$attrs .= ' target="_blank"';
			$rel[]  = 'noopener';
		}
		if ( ! empty( $link['nofollow'] ) ) {
			$rel[] = 'nofollow';
		}
		if ( $rel ) {
			$attrs .= ' rel="' . esc_attr( implode( ' ', array_unique( $rel ) ) ) . '"';
		}
		return $attrs;
	}

	private function sanitize_heading_tag( $tag ) {
		$tag = strtolower( (string) $tag );
		return in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div' ), true ) ? $tag : 'h2';
	}

	private function arrow_svg( $direction ) {
		$path = 'next' === $direction ? 'm10 6 6 6-6 6' : 'm14 6-6 6 6 6';
		return '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24"><path d="' . esc_attr( $path ) . '"></path></svg>';
	}

	private function render_editor_notice() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}
		echo '<div class="elementor-alert elementor-alert-info">برای نمایش Hero حداقل یک اسلاید، حوزه فعالیت یا نتیجه Query اضافه کنید.</div>';
	}
}
