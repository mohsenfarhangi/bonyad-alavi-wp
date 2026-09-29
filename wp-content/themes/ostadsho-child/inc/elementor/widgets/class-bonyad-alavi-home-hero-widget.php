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
			'slider_title_tag_reference',
			array(
				'label'   => 'تگ HTML عنوان اسلاید',
				'type'    => Controls_Manager::SELECT,
				'default' => 'reference',
				'options' => array(
					'reference' => 'مطابق طرح مرجع (H1 برای اسلاید اول، H2 برای بقیه)',
					'h1'        => 'H1',
					'h2'        => 'H2',
					'h3'        => 'H3',
					'h4'        => 'H4',
					'h5'        => 'H5',
					'h6'        => 'H6',
					'div'       => 'DIV',
				),
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
		$repeater->add_control( 'icon', array( 'label' => 'آیکون جایگزین', 'type' => Controls_Manager::ICONS, 'description' => 'در صورت انتخاب آیکون، SVG پیش‌فرض این آیتم جایگزین می‌شود.' ) );
		$repeater->add_control( 'default_icon_key', array( 'type' => Controls_Manager::HIDDEN, 'default' => '' ) );
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
						'icon'             => array(),
						'default_icon_key' => 'economic',
						'title'            => 'معاونت توانمندسازی اقتصادی',
						'description'      => 'ایجاد فرصت‌های شغلی برای کسب درآمد پایدار',
						'link'             => array( 'url' => 'https://bonyadalavi.ir/eghtesadi/' ),
					),
					array(
						'icon'             => array(),
						'default_icon_key' => 'education',
						'title'            => 'معاونت آموزش، مهارت و پرورش',
						'description'      => 'توسعه آموزش و مهارت برای شکوفایی استعدادها',
						'link'             => array( 'url' => 'https://bonyadalavi.ir/amoozesh/' ),
					),
					array(
						'icon'             => array(),
						'default_icon_key' => 'health',
						'title'            => 'معاونت بهداشت و درمان',
						'description'      => 'گسترش خدمات سلامت با هدف پیشگیری و درمان',
						'link'             => array( 'url' => 'https://bonyadalavi.ir/salamat/' ),
					),
					array(
						'icon'             => array(),
						'default_icon_key' => 'infrastructure',
						'title'            => 'معاونت عمرانی و زیرساختی',
						'description'      => 'توسعه زیرساخت‌های ضروری برای دسترسی بهتر و ایمن تر',
						'link'             => array( 'url' => 'https://bonyadalavi.ir/eskan/' ),
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
				'selectors'  => array( '{{WRAPPER}} .ba-home-hero-widget' => '--max: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control( 'ref_grid_gap', array( 'label' => 'فاصله بین بخش‌ها', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ) ), 'selectors' => array( '{{WRAPPER}} .ba-hero__grid' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'ref_mission_column_width', array( 'label' => 'عرض ستون حوزه‌ها در دسکتاپ', 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => 18, 'max' => 45 ) ), 'selectors' => array( '{{WRAPPER}} .ba-home-hero-widget' => '--ba-mission-column-width: {{SIZE}}%;' ) ) );
		$this->add_responsive_control(
			'slider_height',
			array(
				'label'      => 'ارتفاع اسلایدر',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array( 'px' => array( 'min' => 240, 'max' => 900 ), 'vh' => array( 'min' => 20, 'max' => 100 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .ba-slider' => 'min-height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .ba-hero__grid' => '--ba-custom-slider-height: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->add_control(
			'mobile_aspect_ratio',
			array(
				'label'   => 'نسبت اسلایدر موبایل',
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''       => 'پیش‌فرض طرح مرجع (4:3)',
					'1 / 1'  => '1:1',
					'4 / 3'  => '4:3',
					'3 / 2'  => '3:2',
					'16 / 9' => '16:9',
					'3 / 4'  => '3:4',
				),
				'selectors' => array( '{{WRAPPER}} .ba-home-hero-widget' => '--ba-mobile-slider-aspect-ratio: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	private function register_mission_style_controls() {
		$this->start_controls_section( 'style_missions', array( 'label' => 'حوزه‌های فعالیت', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_color_control( 'ref_mission_panel_background', 'پس‌زمینه پنل', '{{WRAPPER}} .ba-mission-nav', 'background-color' );
		$this->add_color_control( 'ref_mission_panel_border_color', 'رنگ کادر پنل', '{{WRAPPER}} .ba-mission-nav', 'border-color' );
		$this->add_color_control( 'ref_mission_divider_color', 'رنگ جداکننده‌ها', '{{WRAPPER}} .ba-mission-nav__item', 'border-color' );
		$this->add_responsive_control( 'ref_mission_panel_radius', array( 'label' => 'گردی پنل', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-mission-nav' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'ref_mission_panel_shadow', 'selector' => '{{WRAPPER}} .ba-mission-nav' ) );
		$this->add_responsive_control( 'ref_mission_item_padding', array( 'label' => 'فاصله داخلی آیتم', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .ba-mission-nav__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'ref_mission_item_gap', array( 'label' => 'فاصله آیکون و متن', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'selectors' => array( '{{WRAPPER}} .ba-mission-nav__item' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_typography_control( 'ref_mission_title_typography', 'تایپوگرافی عنوان', '{{WRAPPER}} .ba-mission-nav__title' );
		$this->add_typography_control( 'ref_mission_description_typography', 'تایپوگرافی توضیحات', '{{WRAPPER}} .ba-mission-nav__description' );

		$this->start_controls_tabs( 'mission_state_tabs' );
		$this->start_controls_tab( 'mission_state_normal', array( 'label' => 'عادی' ) );
		$this->add_color_control( 'ref_mission_item_background', 'پس‌زمینه آیتم', '{{WRAPPER}} .ba-mission-nav__item', 'background-color' );
		$this->add_color_control( 'ref_mission_title_color', 'رنگ عنوان', '{{WRAPPER}} .ba-mission-nav__title' );
		$this->add_color_control( 'ref_mission_description_color', 'رنگ توضیحات', '{{WRAPPER}} .ba-mission-nav__description' );
		$this->add_color_control( 'ref_mission_icon_color', 'رنگ آیکون', '{{WRAPPER}} .ba-mission-nav__icon' );
		$this->add_color_control( 'ref_mission_icon_background', 'پس‌زمینه آیکون', '{{WRAPPER}} .ba-mission-nav__icon', 'background-color' );
		$this->add_color_control( 'ref_mission_icon_border_color', 'رنگ کادر آیکون', '{{WRAPPER}} .ba-mission-nav__icon', 'border-color' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'mission_state_hover', array( 'label' => 'هاور' ) );
		$this->add_color_control( 'ref_mission_item_hover_background', 'پس‌زمینه آیتم', '{{WRAPPER}} .ba-mission-nav__item:hover', 'background-color' );
		$this->add_color_control( 'ref_mission_title_hover_color', 'رنگ عنوان', '{{WRAPPER}} .ba-mission-nav__item:hover .ba-mission-nav__title' );
		$this->add_color_control( 'ref_mission_description_hover_color', 'رنگ توضیحات', '{{WRAPPER}} .ba-mission-nav__item:hover .ba-mission-nav__description' );
		$this->add_color_control( 'ref_mission_icon_hover_color', 'رنگ آیکون', '{{WRAPPER}} .ba-mission-nav__item:hover .ba-mission-nav__icon' );
		$this->add_color_control( 'ref_mission_icon_hover_background', 'پس‌زمینه آیکون', '{{WRAPPER}} .ba-mission-nav__item:hover .ba-mission-nav__icon', 'background-color' );
		$this->add_color_control( 'ref_mission_icon_hover_border_color', 'رنگ کادر آیکون', '{{WRAPPER}} .ba-mission-nav__item:hover .ba-mission-nav__icon', 'border-color' );
		$this->add_responsive_control( 'ref_mission_hover_shift', array( 'label' => 'فاصله راست در هاور', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ) ), 'selectors' => array( '{{WRAPPER}} .ba-mission-nav__item:hover' => 'padding-right: {{SIZE}}{{UNIT}};' ) ) );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control( 'ref_mission_icon_box_size', array( 'label' => 'اندازه کادر آیکون', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 24, 'max' => 100 ) ), 'selectors' => array( '{{WRAPPER}} .ba-mission-nav__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'ref_mission_icon_size', array( 'label' => 'اندازه آیکون', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 10, 'max' => 60 ) ), 'selectors' => array( '{{WRAPPER}} .ba-mission-nav__icon i' => 'font-size: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .ba-mission-nav__glyph' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'ref_mission_icon_radius', array( 'label' => 'گردی کادر آیکون', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ), '%' => array( 'min' => 0, 'max' => 50 ) ), 'selectors' => array( '{{WRAPPER}} .ba-mission-nav__icon' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->end_controls_section();
	}

	private function register_slider_style_controls() {
		$this->start_controls_section( 'style_slider', array( 'label' => 'اسلایدر و محتوا', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_color_control( 'ref_slider_background', 'پس‌زمینه اسلایدر', '{{WRAPPER}} .ba-slider', 'background-color' );
		$this->add_responsive_control( 'ref_slider_radius', array( 'label' => 'گردی اسلایدر', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-slider' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'ref_slider_shadow', 'selector' => '{{WRAPPER}} .ba-slider' ) );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'ref_slider_overlay_background', 'label' => 'Overlay', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .ba-slider__slide::after' ) );
		$this->add_control( 'ref_slider_image_fit', array( 'label' => 'نحوه نمایش تصویر', 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => array( '' => 'پیش‌فرض طرح مرجع (Cover)', 'cover' => 'Cover', 'contain' => 'Contain', 'fill' => 'Fill', 'none' => 'None', 'scale-down' => 'Scale Down' ), 'selectors' => array( '{{WRAPPER}} .ba-slider__slide img' => 'object-fit: {{VALUE}};' ) ) );
		$this->add_control( 'ref_slider_image_position', array( 'label' => 'موقعیت تصویر', 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => array( '' => 'پیش‌فرض طرح مرجع (وسط)', 'center center' => 'وسط', 'center top' => 'بالا', 'center bottom' => 'پایین', 'right center' => 'راست', 'left center' => 'چپ', 'right top' => 'بالا راست', 'left top' => 'بالا چپ', 'right bottom' => 'پایین راست', 'left bottom' => 'پایین چپ' ), 'selectors' => array( '{{WRAPPER}} .ba-slider__slide img' => 'object-position: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'ref_slider_content_width', array( 'label' => 'عرض محتوا', 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%', 'px' ), 'range' => array( '%' => array( 'min' => 20, 'max' => 100 ), 'px' => array( 'min' => 200, 'max' => 570 ) ), 'selectors' => array( '{{WRAPPER}} .ba-slider__content' => 'width: min(570px, {{SIZE}}{{UNIT}});' ) ) );
		$this->add_responsive_control( 'ref_slider_content_right', array( 'label' => 'فاصله محتوا از راست', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 140 ), '%' => array( 'min' => 0, 'max' => 30 ) ), 'selectors' => array( '{{WRAPPER}} .ba-slider__content' => 'right: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'ref_slider_content_bottom', array( 'label' => 'فاصله محتوا از پایین', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 180 ), '%' => array( 'min' => 0, 'max' => 40 ) ), 'selectors' => array( '{{WRAPPER}} .ba-slider__content' => 'bottom: {{SIZE}}{{UNIT}};' ) ) );

		$this->add_control( 'ref_slider_tag_heading', array( 'label' => 'برچسب اسلاید', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_typography_control( 'ref_slider_tag_typography', 'تایپوگرافی برچسب', '{{WRAPPER}} .ba-slider__tag' );
		$this->add_color_control( 'ref_slider_tag_color', 'رنگ متن', '{{WRAPPER}} .ba-slider__tag' );
		$this->add_color_control( 'ref_slider_tag_background', 'پس‌زمینه', '{{WRAPPER}} .ba-slider__tag', 'background-color' );
		$this->add_color_control( 'ref_slider_tag_border_color', 'رنگ کادر', '{{WRAPPER}} .ba-slider__tag', 'border-color' );

		$this->add_control( 'ref_slider_title_heading', array( 'label' => 'عنوان اسلاید', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_typography_control( 'ref_slider_title_typography', 'تایپوگرافی عنوان', '{{WRAPPER}} .ba-slider__title' );
		$this->add_color_control( 'ref_slider_title_color', 'رنگ عنوان', '{{WRAPPER}} .ba-slider__title' );

		$this->add_control( 'ref_slider_description_heading', array( 'label' => 'توضیحات اسلاید', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_typography_control( 'ref_slider_description_typography', 'تایپوگرافی توضیحات', '{{WRAPPER}} .ba-slider__text' );
		$this->add_color_control( 'ref_slider_description_color', 'رنگ توضیحات', '{{WRAPPER}} .ba-slider__text' );

		$this->add_control( 'ref_slider_button_heading', array( 'label' => 'دکمه اسلاید', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_typography_control( 'ref_slider_button_typography', 'تایپوگرافی دکمه', '{{WRAPPER}} .ba-slider__cta' );
		$this->add_responsive_control( 'ref_slider_button_padding', array( 'label' => 'فاصله داخلی دکمه', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .ba-slider__cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'ref_slider_button_radius', array( 'label' => 'گردی دکمه', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'selectors' => array( '{{WRAPPER}} .ba-slider__cta' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->start_controls_tabs( 'slider_button_tabs' );
		$this->start_controls_tab( 'slider_button_normal', array( 'label' => 'عادی' ) );
		$this->add_color_control( 'ref_slider_button_color', 'رنگ متن', '{{WRAPPER}} .ba-slider__cta' );
		$this->add_color_control( 'ref_slider_button_background', 'پس‌زمینه', '{{WRAPPER}} .ba-slider__cta', 'background-color' );
		$this->add_color_control( 'ref_slider_button_border_color', 'رنگ کادر', '{{WRAPPER}} .ba-slider__cta', 'border-color' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'slider_button_hover', array( 'label' => 'هاور' ) );
		$this->add_color_control( 'ref_slider_button_hover_color', 'رنگ متن', '{{WRAPPER}} .ba-slider__cta:hover' );
		$this->add_color_control( 'ref_slider_button_hover_background', 'پس‌زمینه', '{{WRAPPER}} .ba-slider__cta:hover', 'background-color' );
		$this->add_color_control( 'ref_slider_button_hover_border_color', 'رنگ کادر', '{{WRAPPER}} .ba-slider__cta:hover', 'border-color' );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
	}

	private function register_navigation_style_controls() {
		$this->start_controls_section( 'style_navigation', array( 'label' => 'ناوبری اسلایدر', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'ref_slider_arrow_heading', array( 'label' => 'فلش‌ها', 'type' => Controls_Manager::HEADING ) );
		$this->add_responsive_control( 'ref_slider_arrow_size', array( 'label' => 'اندازه دکمه', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 24, 'max' => 80 ) ), 'selectors' => array( '{{WRAPPER}} .ba-slider__arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'ref_slider_arrow_icon_size', array( 'label' => 'اندازه آیکون', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 8, 'max' => 40 ) ), 'selectors' => array( '{{WRAPPER}} .ba-slider__arrow svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->start_controls_tabs( 'slider_arrow_tabs' );
		$this->start_controls_tab( 'slider_arrow_normal', array( 'label' => 'عادی' ) );
		$this->add_color_control( 'ref_slider_arrow_color', 'رنگ آیکون', '{{WRAPPER}} .ba-slider__arrow' );
		$this->add_color_control( 'ref_slider_arrow_background', 'پس‌زمینه', '{{WRAPPER}} .ba-slider__arrow', 'background-color' );
		$this->add_color_control( 'ref_slider_arrow_border_color', 'رنگ کادر', '{{WRAPPER}} .ba-slider__arrow', 'border-color' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'slider_arrow_hover', array( 'label' => 'هاور' ) );
		$this->add_color_control( 'ref_slider_arrow_hover_color', 'رنگ آیکون', '{{WRAPPER}} .ba-slider__arrow:hover' );
		$this->add_color_control( 'ref_slider_arrow_hover_background', 'پس‌زمینه', '{{WRAPPER}} .ba-slider__arrow:hover', 'background-color' );
		$this->add_color_control( 'ref_slider_arrow_hover_border_color', 'رنگ کادر', '{{WRAPPER}} .ba-slider__arrow:hover', 'border-color' );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control( 'ref_slider_dots_heading', array( 'label' => 'Pagination', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_responsive_control( 'ref_slider_dot_size', array( 'label' => 'اندازه نقطه', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 3, 'max' => 20 ) ), 'selectors' => array( '{{WRAPPER}} .ba-slider__dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'ref_slider_dot_active_width', array( 'label' => 'عرض نقطه فعال', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 5, 'max' => 60 ) ), 'selectors' => array( '{{WRAPPER}} .ba-slider__dot.is-active' => 'width: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_color_control( 'ref_slider_dot_color', 'رنگ عادی', '{{WRAPPER}} .ba-slider__dot', 'background-color' );
		$this->add_color_control( 'ref_slider_dot_hover_color', 'رنگ هاور', '{{WRAPPER}} .ba-slider__dot:hover', 'background-color' );
		$this->add_color_control( 'ref_slider_dot_active_color', 'رنگ فعال', '{{WRAPPER}} .ba-slider__dot.is-active', 'background-color' );
		$this->end_controls_section();
	}

	private function register_ticker_style_controls() {
		$this->start_controls_section( 'style_ticker', array( 'label' => 'نوار اخبار مهم', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'ref_ticker_height', array( 'label' => 'ارتفاع', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 32, 'max' => 90 ) ), 'selectors' => array( '{{WRAPPER}} .ba-ticker' => 'height: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .ba-ticker__viewport' => 'height: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .ba-ticker__item' => 'height: {{SIZE}}{{UNIT}}; flex-basis: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_color_control( 'ref_ticker_background', 'پس‌زمینه نوار', '{{WRAPPER}} .ba-ticker', 'background-color' );
		$this->add_color_control( 'ref_ticker_border_color', 'رنگ کادر', '{{WRAPPER}} .ba-ticker', 'border-color' );
		$this->add_responsive_control( 'ref_ticker_radius', array( 'label' => 'گردی نوار', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ) ), 'selectors' => array( '{{WRAPPER}} .ba-ticker' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'ref_ticker_shadow', 'selector' => '{{WRAPPER}} .ba-ticker' ) );

		$this->add_control( 'ref_ticker_label_heading', array( 'label' => 'برچسب نوار', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_typography_control( 'ref_ticker_label_typography', 'تایپوگرافی برچسب', '{{WRAPPER}} .ba-ticker__label' );
		$this->add_color_control( 'ref_ticker_label_color', 'رنگ متن', '{{WRAPPER}} .ba-ticker__label' );
		$this->add_color_control( 'ref_ticker_label_background', 'پس‌زمینه', '{{WRAPPER}} .ba-ticker__label', 'background-color' );
		$this->add_responsive_control( 'ref_ticker_label_padding', array( 'label' => 'فاصله داخلی', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .ba-ticker__label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );

		$this->add_control( 'ref_ticker_item_heading', array( 'label' => 'عنوان خبر', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_typography_control( 'ref_ticker_item_typography', 'تایپوگرافی عنوان خبر', '{{WRAPPER}} .ba-ticker__item' );
		$this->start_controls_tabs( 'ticker_item_tabs' );
		$this->start_controls_tab( 'ticker_item_normal', array( 'label' => 'عادی' ) );
		$this->add_color_control( 'ref_ticker_item_color', 'رنگ متن', '{{WRAPPER}} .ba-ticker__item' );
		$this->add_color_control( 'ref_ticker_item_background', 'پس‌زمینه', '{{WRAPPER}} .ba-ticker__item', 'background-color' );
		$this->end_controls_tab();
		$this->start_controls_tab( 'ticker_item_hover', array( 'label' => 'هاور' ) );
		$this->add_color_control( 'ref_ticker_item_hover_color', 'رنگ متن', '{{WRAPPER}} .ba-ticker__item:hover' );
		$this->add_color_control( 'ref_ticker_item_hover_background', 'پس‌زمینه', '{{WRAPPER}} .ba-ticker__item:hover', 'background-color' );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->add_color_control( 'ref_ticker_bullet_color', 'رنگ نشانگر', '{{WRAPPER}} .ba-ticker__item::before', 'background-color' );
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

		$classes   = array( 'ba-hero', 'ba-home-hero-widget' );
		$classes[] = $posts ? 'ba-home-hero-widget--has-ticker' : 'ba-home-hero-widget--no-ticker';
		$classes[] = $missions ? 'ba-home-hero-widget--has-missions' : 'ba-home-hero-widget--no-missions';
		$classes[] = $slides ? 'ba-home-hero-widget--has-slider' : 'ba-home-hero-widget--no-slider';
		?>
		<section
			aria-label="دسترسی سریع و اخبار مهم"
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-ba-home-hero
			data-slider-autoplay="<?php echo 'yes' === ( $settings['slider_autoplay'] ?? 'yes' ) ? '1' : '0'; ?>"
			data-slider-delay="<?php echo esc_attr( (string) max( 1500, absint( $settings['slider_autoplay_delay'] ?? 6200 ) ) ); ?>"
			data-slider-pause-hover="<?php echo 'yes' === ( $settings['slider_pause_on_hover'] ?? 'yes' ) ? '1' : '0'; ?>"
			data-ticker-autoplay="<?php echo 'yes' === ( $settings['ticker_autoplay'] ?? 'yes' ) ? '1' : '0'; ?>"
			data-ticker-delay="<?php echo esc_attr( (string) max( 1500, absint( $settings['ticker_delay'] ?? 6000 ) ) ); ?>"
		>
			<div class="ba-container">
				<div class="ba-hero__grid">
					<?php if ( $missions ) : ?>
						<aside aria-label="حوزه‌های اصلی فعالیت" class="ba-mission-nav">
							<?php foreach ( $missions as $mission ) : ?>
								<?php $this->render_mission_item( $mission ); ?>
							<?php endforeach; ?>
						</aside>
					<?php endif; ?>

					<?php if ( $slides ) : ?>
						<div aria-label="اخبار و رویدادهای شاخص" aria-roledescription="carousel" class="ba-slider" data-js-slider>
							<div class="ba-slider__track">
								<?php foreach ( $slides as $index => $slide ) : ?>
									<?php $this->render_slide( $slide, $index, $settings ); ?>
								<?php endforeach; ?>
							</div>

							<?php if ( count( $slides ) > 1 && 'yes' === ( $settings['slider_show_dots'] ?? 'yes' ) ) : ?>
								<div aria-label="انتخاب اسلاید" class="ba-slider__pagination">
									<?php foreach ( array_keys( $slides ) as $index ) : ?>
										<button aria-label="<?php echo esc_attr( sprintf( 'اسلاید %d', $index + 1 ) ); ?>" class="ba-slider__dot<?php echo 0 === $index ? ' is-active' : ''; ?>" data-js-slider-dot type="button"></button>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php if ( count( $slides ) > 1 && 'yes' === ( $settings['slider_show_arrows'] ?? 'yes' ) ) : ?>
								<div class="ba-slider__controls">
									<button aria-label="اسلاید قبل" class="ba-slider__arrow" data-dir="prev" data-js-slider-arrow type="button"><?php echo $this->arrow_svg( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
									<button aria-label="اسلاید بعد" class="ba-slider__arrow" data-dir="next" data-js-slider-arrow type="button"><?php echo $this->arrow_svg( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $posts ) : ?>
						<div aria-label="اخبار مهم" class="ba-ticker<?php echo '' === trim( (string) ( $settings['ticker_label'] ?? '' ) ) ? ' ba-ticker--no-label' : ''; ?>" data-js-ticker>
							<?php if ( '' !== trim( (string) ( $settings['ticker_label'] ?? '' ) ) ) : ?>
								<span class="ba-ticker__label"><?php echo esc_html( $settings['ticker_label'] ); ?></span>
							<?php endif; ?>
							<div class="ba-ticker__viewport">
								<div class="ba-ticker__track" data-js-ticker-track>
									<?php foreach ( $posts as $post ) : ?>
										<a class="ba-ticker__item" data-js-ticker-item href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
									<?php endforeach; ?>
									<?php if ( $posts ) : ?>
										<a aria-hidden="true" class="ba-ticker__item ba-ticker__item--clone" href="<?php echo esc_url( get_permalink( $posts[0] ) ); ?>" tabindex="-1"><?php echo esc_html( get_the_title( $posts[0] ) ); ?></a>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
		wp_reset_postdata();
	}

	private function render_mission_item( array $mission ) {
		$title            = trim( (string) ( $mission['title'] ?? '' ) );
		$description      = trim( (string) ( $mission['description'] ?? '' ) );
		$icon             = is_array( $mission['icon'] ?? null ) ? $mission['icon'] : array();
		$default_icon_key = $this->resolve_mission_default_icon_key( $mission );
		$has_custom_icon  = ! empty( $icon['value'] ) && ! $this->is_legacy_default_mission_icon( $icon, $default_icon_key );
		$link             = is_array( $mission['link'] ?? null ) ? $mission['link'] : array();
		$has_link         = ! empty( $link['url'] );
		$tag              = $has_link ? 'a' : 'div';
		$attrs            = $has_link ? $this->build_link_attributes( $link ) : '';
		?>
		<<?php echo esc_attr( $tag ); ?> class="ba-mission-nav__item"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( $has_custom_icon || '' !== $default_icon_key ) : ?>
				<span class="ba-mission-nav__icon" aria-hidden="true">
					<?php if ( $has_custom_icon ) : ?>
						<?php Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true', 'class' => 'ba-mission-nav__glyph' ) ); ?>
					<?php else : ?>
						<?php echo $this->get_mission_default_svg( $default_icon_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				</span>
			<?php endif; ?>
			<?php if ( '' !== $title || '' !== $description ) : ?>
				<span class="ba-mission-nav__copy">
					<?php if ( '' !== $title ) : ?><strong class="ba-mission-nav__title"><?php echo esc_html( $title ); ?></strong><?php endif; ?>
					<?php if ( '' !== $description ) : ?><span class="ba-mission-nav__description"><?php echo esc_html( $description ); ?></span><?php endif; ?>
				</span>
			<?php endif; ?>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	/**
	 * کلید SVG پیش‌فرض آیتم Mission را برمی‌گرداند.
	 *
	 * default_icon_key برای آیتم‌های جدید مرجع است. map آیکون‌های Font Awesome قدیمی
	 * باعث می‌شود نمونه‌هایی که پیش از این تغییر ذخیره شده‌اند نیز بدون Migration دستی
	 * به SVGهای اصلی redesign برگردند.
	 */
	private function resolve_mission_default_icon_key( array $mission ): string {
		$allowed = array( 'economic', 'education', 'health', 'infrastructure' );
		$key     = sanitize_key( (string) ( $mission['default_icon_key'] ?? '' ) );

		if ( in_array( $key, $allowed, true ) ) {
			return $key;
		}

		$icon       = is_array( $mission['icon'] ?? null ) ? $mission['icon'] : array();
		$icon_value = is_string( $icon['value'] ?? null ) ? trim( $icon['value'] ) : '';
		$legacy     = array(
			'fas fa-briefcase'      => 'economic',
			'fas fa-graduation-cap' => 'education',
			'fas fa-stethoscope'    => 'health',
			'fas fa-building'       => 'infrastructure',
		);

		if ( isset( $legacy[ $icon_value ] ) ) {
			return $legacy[ $icon_value ];
		}

		if ( '' !== $icon_value ) {
			return '';
		}

		$title_map = array(
			'معاونت توانمندسازی اقتصادی'    => 'economic',
			'معاونت آموزش، مهارت و پرورش'   => 'education',
			'معاونت بهداشت و درمان'          => 'health',
			'معاونت عمرانی و زیرساختی'       => 'infrastructure',
		);

		return $title_map[ trim( (string) ( $mission['title'] ?? '' ) ) ] ?? '';
	}

	/**
	 * تشخیص می‌دهد آیکون ذخیره‌شده همان default قدیمی ویجت است یا انتخاب جدید کاربر.
	 */
	private function is_legacy_default_mission_icon( array $icon, string $default_icon_key ): bool {
		$icon_value = is_string( $icon['value'] ?? null ) ? trim( $icon['value'] ) : '';
		$legacy     = array(
			'economic'       => 'fas fa-briefcase',
			'education'      => 'fas fa-graduation-cap',
			'health'         => 'fas fa-stethoscope',
			'infrastructure' => 'fas fa-building',
		);

		return '' !== $default_icon_key
			&& isset( $legacy[ $default_icon_key ] )
			&& $legacy[ $default_icon_key ] === $icon_value;
	}

	/**
	 * SVGهای اصلی ba-mission-nav در reference redesign را بدون وابستگی خارجی برمی‌گرداند.
	 */
	private function get_mission_default_svg( string $key ): string {
		$icons = array(
			'economic' => '<svg aria-hidden="true" class="ba-mission-nav__glyph" data-icon="tabler:briefcase-2" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9"/><path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2"/></svg>',
			'education' => '<svg aria-hidden="true" class="ba-mission-nav__glyph" data-icon="tabler:school" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 9l-10 -4l-10 4l10 4l10 -4v6"/><path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4"/></svg>',
			'health' => '<svg aria-hidden="true" class="ba-mission-nav__glyph" data-icon="tabler:stethoscope" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h-1a2 2 0 0 0 -2 2v3.5a5.5 5.5 0 0 0 11 0v-3.5a2 2 0 0 0 -2 -2h-1"/><path d="M8 15a6 6 0 1 0 12 0v-3"/><path d="M11 3v2"/><path d="M6 3v2"/><path d="M18 10a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/></svg>',
			'infrastructure' => '<svg aria-hidden="true" class="ba-mission-nav__glyph" data-icon="tabler:building-community" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 9l5 5v7h-5v-4m0 4h-5v-7l5 -5m1 1v-6a1 1 0 0 1 1 -1h10a1 1 0 0 1 1 1v17h-8"/><path d="M13 7l0 .01"/><path d="M17 7l0 .01"/><path d="M17 11l0 .01"/><path d="M17 15l0 .01"/></svg>',
		);

		return $icons[ $key ] ?? '';
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
		$title_mode  = (string) ( $settings['slider_title_tag_reference'] ?? 'reference' );
		$title_tag   = 'reference' === $title_mode ? ( 0 === (int) $index ? 'h1' : 'h2' ) : $this->sanitize_heading_tag( $title_mode );
		$alt         = ! empty( $image['id'] ) ? (string) get_post_meta( absint( $image['id'] ), '_wp_attachment_image_alt', true ) : '';
		$item        = array( 'id' => absint( $image['id'] ?? 0 ), 'url' => (string) ( $image['url'] ?? '' ), 'alt' => $alt );
		$class       = 'ba-slider__slide' . ( 0 === (int) $index ? ' is-active' : '' );
		?>
		<article class="<?php echo esc_attr( $class ); ?>" data-js-slide>
			<?php echo BA_Media_Helper::render_image( $item, 'full', array( 'loading' => 0 === (int) $index ? 'eager' : 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( $whole_link ) : ?>
				<a class="ba-slider__whole-link"<?php echo $this->build_link_attributes( $link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $title ?: $tag ?: 'مشاهده اسلاید' ); ?>"></a>
			<?php endif; ?>

			<?php if ( '' !== $tag || '' !== $title || '' !== $description || $has_button ) : ?>
				<div class="ba-slider__content">
					<?php if ( '' !== $tag ) : ?><span class="ba-slider__tag"><?php echo esc_html( $tag ); ?></span><?php endif; ?>
					<?php if ( '' !== $title ) : ?><<?php echo esc_attr( $title_tag ); ?> class="ba-slider__title"><?php echo esc_html( $title ); ?></<?php echo esc_attr( $title_tag ); ?>><?php endif; ?>
					<?php if ( '' !== $description ) : ?><p class="ba-slider__text"><?php echo esc_html( $description ); ?></p><?php endif; ?>
					<?php if ( $has_button ) : ?><a class="ba-slider__cta ba-button ba-button--light"<?php echo $this->build_link_attributes( $link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $button_text ); ?> <span aria-hidden="true">←</span></a><?php endif; ?>
				</div>
			<?php endif; ?>
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
		return '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="' . esc_attr( $path ) . '"></path></svg>';
	}

	private function render_editor_notice() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}
		echo '<div class="elementor-alert elementor-alert-info">برای نمایش Hero حداقل یک اسلاید، حوزه فعالیت یا نتیجه Query اضافه کنید.</div>';
	}
}
