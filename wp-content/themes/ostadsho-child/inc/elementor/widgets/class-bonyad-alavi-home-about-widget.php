<?php
/**
 * ویجت معرفی بنیاد / اثرگذاری صفحه اصلی.
 *
 * Visual reference:
 * bonyad-alavi-redesign/redesign/index.html -> #about / .ba-impact
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
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

/**
 * سکشن About صفحه اصلی با دونات منابع، کارت‌های منبع و Summary داینامیک.
 */
final class Bonyad_Alavi_Home_About_Widget extends Widget_Base {

	public function get_name() {
		return 'bonyad_alavi_home_about';
	}

	public function get_title() {
		return esc_html__( 'معرفی بنیاد صفحه اصلی', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-info-circle-o';
	}

	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	public function get_style_depends() {
		return array( 'bonyad-alavi-home-about-widget' );
	}

	public function get_script_depends() {
		return array( 'bonyad-alavi-home-about-widget' );
	}

	protected function register_controls() {
		$this->register_intro_controls();
		$this->register_actions_controls();
		$this->register_funding_controls();
		$this->register_summary_controls();

		$this->register_section_style_controls();
		$this->register_intro_style_controls();
		$this->register_actions_style_controls();
		$this->register_funding_style_controls();
		$this->register_chart_label_style_controls();
		$this->register_summary_style_controls();
	}

	/**
	 * متن معرفی reference.
	 */
	private function register_intro_controls() {
		$this->start_controls_section(
			'content_intro',
			array(
				'label' => 'معرفی بنیاد',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'eyebrow',
			array(
				'label'       => 'پیش‌عنوان',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'بنیاد علوی در یک نگاه',
				'label_block' => true,
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => 'عنوان',
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => 'توانمندسازی پایدار؛ با تمرکز بر ظرفیت‌های واقعی هر منطقه',
				'rows'        => 2,
			)
		);

		$this->add_control(
			'description',
			array(
				'label'   => 'توضیحات',
				'type'    => Controls_Manager::TEXTAREA,
				'default' => 'بنیاد علوی به‌عنوان بازوی محرومیت‌زدایی بنیاد مستضعفان، با تمرکز بر آبادانی مناطق کم‌برخوردار، توانمندسازی اقشار هدف و ارتقای عدالت اجتماعی و اقتصادی فعالیت می‌کند. در این بازطراحی، داده‌های اثرگذاری به‌جای پراکندگی در صفحه، در یک نقطه قابل اسکن ارائه شده‌اند.',
				'rows'    => 5,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * دکمه‌ها آزاد هستند اما سه دکمه reference به‌صورت پیش‌فرض حفظ می‌شوند.
	 */
	private function register_actions_controls() {
		$this->start_controls_section(
			'content_actions',
			array(
				'label' => 'دکمه‌ها',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'label',
			array(
				'label'       => 'عنوان',
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'       => 'لینک',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://',
				'options'     => array( 'url', 'is_external', 'nofollow' ),
			)
		);
		$repeater->add_control(
			'type',
			array(
				'label'   => 'نوع دکمه',
				'type'    => Controls_Manager::SELECT,
				'default' => 'secondary',
				'options' => array(
					'primary'   => 'Primary',
					'secondary' => 'Secondary',
				),
			)
		);

		$this->add_control(
			'actions',
			array(
				'label'       => 'دکمه‌ها',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label || "دکمه" }}}',
				'default'     => array(
					array(
						'label' => 'هدف و ماموریت',
						'link'  => array( 'url' => '#' ),
						'type'  => 'primary',
					),
					array(
						'label' => 'برنامه راهبردی و عملیات',
						'link'  => array( 'url' => '#' ),
						'type'  => 'secondary',
					),
					array(
						'label' => 'مشاهده مناطق هدف',
						'link'  => array( 'url' => '#' ),
						'type'  => 'secondary',
					),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * منابع نمودار؛ تعداد، عنوان، مقدار و رنگ کاملاً از Repeater می‌آیند.
	 */
	private function register_funding_controls() {
		$this->start_controls_section(
			'content_funding',
			array(
				'label' => 'ترکیب منابع اثرگذاری',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'funding_kicker',
			array(
				'label'       => 'عنوان پنل',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'ترکیب منابع اثرگذاری',
				'label_block' => true,
			)
		);

		$this->add_control(
			'funding_note',
			array(
				'label'       => 'یادداشت',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'مقادیر فعلی آزمایشی هستند',
				'label_block' => true,
			)
		);

		$this->add_control(
			'funding_unit',
			array(
				'label'   => 'واحد منابع',
				'type'    => Controls_Manager::TEXT,
				'default' => 'همت',
			)
		);

		$this->add_control(
			'funding_decimals',
			array(
				'label'   => 'تعداد رقم اعشار نمایشی',
				'type'    => Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 3,
				'step'    => 1,
				'default' => 1,
			)
		);

		$this->add_control(
			'donut_caption',
			array(
				'label'   => 'متن مرکز نمودار',
				'type'    => Controls_Manager::TEXT,
				'default' => 'مجموع منابع',
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'label',
			array(
				'label'       => 'عنوان منبع',
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'value',
			array(
				'label'       => 'مقدار',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'step'        => 0.1,
				'description' => 'اندازه سهم این منبع در نمودار از همین مقدار محاسبه می‌شود.',
			)
		);
		$repeater->add_control(
			'color',
			array(
				'label'   => 'رنگ',
				'type'    => Controls_Manager::COLOR,
				'default' => '#06783a',
			)
		);
		$this->add_control(
			'funding_sources',
			array(
				'label'       => 'منابع',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label || "منبع" }}}',
				'default'     => array(
					array(
						'label'         => 'منابع بنیاد علوی',
						'value'         => 14.8,
						'color'         => '#06783a',
					),
					array(
						'label'         => 'منابع بانک‌ها',
						'value'         => 9.6,
						'color'         => '#128a70',
					),
					array(
						'label'         => 'منابع سازمان‌ها',
						'value'         => 5.4,
						'color'         => '#487e72',
					),
					array(
						'label'         => 'منابع خیرین و ذی‌نفعان',
						'value'         => 3.2,
						'color'         => '#b28a42',
					),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Summary مستقل، قابل مخفی‌کردن و قابل افزودن/حذف‌کردن است.
	 */
	private function register_summary_controls() {
		$this->start_controls_section(
			'content_summary',
			array(
				'label' => 'جمع‌بندی',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_summary',
			array(
				'label'        => 'نمایش بخش جمع‌بندی',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'نمایش',
				'label_off'    => 'مخفی',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'is_visible',
			array(
				'label'        => 'نمایش آیتم',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'نمایش',
				'label_off'    => 'مخفی',
				'return_value' => 'yes',
				'default'      => 'yes',
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
				'label'       => 'آیکون جایگزین',
				'type'        => Controls_Manager::ICONS,
				'description' => 'در صورت انتخاب، SVG پیش‌فرض reference جایگزین می‌شود.',
			)
		);
		$repeater->add_control(
			'default_icon_key',
			array(
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			)
		);
		$repeater->add_control(
			'value_type',
			array(
				'label'   => 'نوع مقدار',
				'type'    => Controls_Manager::SELECT,
				'default' => 'manual',
				'options' => array(
					'funding_total' => 'مجموع خودکار منابع دونات',
					'manual'        => 'عدد دستی',
				),
			)
		);
		$repeater->add_control(
			'manual_value',
			array(
				'label'     => 'عدد دستی',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'step'      => 1,
				'condition' => array( 'value_type' => 'manual' ),
			)
		);
		$repeater->add_control(
			'decimals',
			array(
				'label'   => 'رقم اعشار',
				'type'    => Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 3,
				'step'    => 1,
				'default' => 0,
			)
		);
		$repeater->add_control(
			'suffix',
			array(
				'label'       => 'پسوند',
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'مثلاً نفر',
			)
		);

		$this->add_control(
			'summary_items',
			array(
				'label'       => 'آیتم‌های جمع‌بندی',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label || "آیتم جمع‌بندی" }}}',
				'condition'   => array( 'show_summary' => 'yes' ),
				'default'     => array(
					array(
						'is_visible'       => 'yes',
						'label'            => 'مجموع منابع (همت)',
						'default_icon_key' => 'funding',
						'value_type'       => 'funding_total',
						'decimals'         => 1,
						'suffix'           => '',
					),
					array(
						'is_visible'       => 'yes',
						'label'            => 'تعداد خدمات‌گیرندگان مستقیم',
						'default_icon_key' => 'beneficiaries',
						'value_type'       => 'manual',
						'manual_value'     => 3250000,
						'decimals'         => 0,
						'suffix'           => '',
					),
				),
			)
		);

		$this->end_controls_section();
	}

	private function register_section_style_controls() {
		$this->start_controls_section(
			'style_section',
			array(
				'label' => 'سکشن و قاب',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'ref_section_padding',
			array(
				'label'      => 'فاصله عمودی سکشن',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .ba-home-about-widget' => 'padding: {{TOP}}{{UNIT}} 0 {{BOTTOM}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'ref_section_background',
			array(
				'label'     => 'پس‌زمینه سکشن',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-about-widget' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_frame_background',
			array(
				'label'     => 'پس‌زمینه قاب',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-impact__grid' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'ref_frame_radius',
			array(
				'label'      => 'گردی قاب',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-impact__grid' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'ref_frame_border',
				'selector' => '{{WRAPPER}} .ba-impact__grid',
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'ref_frame_shadow',
				'selector' => '{{WRAPPER}} .ba-impact__grid',
			)
		);

		$this->end_controls_section();
	}

	private function register_intro_style_controls() {
		$this->start_controls_section(
			'style_intro',
			array(
				'label' => 'متن معرفی',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'ref_content_padding',
			array(
				'label'      => 'فاصله داخلی',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .ba-impact__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_eyebrow_typography',
				'label'    => 'تایپوگرافی پیش‌عنوان',
				'selector' => '{{WRAPPER}} .ba-section-heading__eyebrow',
			)
		);
		$this->add_control(
			'ref_eyebrow_color',
			array(
				'label'     => 'رنگ پیش‌عنوان',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-section-heading__eyebrow' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_title_typography',
				'label'    => 'تایپوگرافی عنوان',
				'selector' => '{{WRAPPER}} .ba-section-heading__title',
			)
		);
		$this->add_control(
			'ref_title_color',
			array(
				'label'     => 'رنگ عنوان',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-section-heading__title' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_description_typography',
				'label'    => 'تایپوگرافی توضیحات',
				'selector' => '{{WRAPPER}} .ba-impact__text',
			)
		);
		$this->add_control(
			'ref_description_color',
			array(
				'label'     => 'رنگ توضیحات',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-impact__text' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_actions_style_controls() {
		$this->start_controls_section(
			'style_actions',
			array(
				'label' => 'دکمه‌ها',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'ref_actions_gap',
			array(
				'label'      => 'فاصله',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-impact__actions' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_button_typography',
				'label'    => 'تایپوگرافی',
				'selector' => '{{WRAPPER}} .ba-impact__actions .ba-button',
			)
		);
		$this->add_control(
			'ref_primary_background',
			array(
				'label'     => 'پس‌زمینه Primary',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-button--primary' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_primary_color',
			array(
				'label'     => 'متن Primary',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-button--primary' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_secondary_background',
			array(
				'label'     => 'پس‌زمینه Secondary',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-button--secondary' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_secondary_color',
			array(
				'label'     => 'متن Secondary',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-button--secondary' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_funding_style_controls() {
		$this->start_controls_section(
			'style_funding',
			array(
				'label' => 'پنل منابع و نمودار',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ref_funding_background',
			array(
				'label'     => 'پس‌زمینه پنل',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-impact__funding' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'ref_funding_padding',
			array(
				'label'      => 'فاصله داخلی',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .ba-impact__funding' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_funding_kicker_typography',
				'label'    => 'تایپوگرافی عنوان پنل',
				'selector' => '{{WRAPPER}} .ba-impact__funding-kicker',
			)
		);
		$this->add_control(
			'ref_funding_kicker_color',
			array(
				'label'     => 'رنگ عنوان پنل',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-impact__funding-kicker' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_funding_note_typography',
				'label'    => 'تایپوگرافی یادداشت',
				'selector' => '{{WRAPPER}} .ba-impact__funding-note',
			)
		);
		$this->add_control(
			'ref_funding_note_color',
			array(
				'label'     => 'رنگ یادداشت',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-impact__funding-note' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_donut_track_color',
			array(
				'label'     => 'رنگ Track نمودار',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-impact__funding' => '--ba-impact-chart-track: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_donut_center_background',
			array(
				'label'     => 'پس‌زمینه مرکز نمودار',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-impact__donut-center' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_donut_total_typography',
				'label'    => 'تایپوگرافی مجموع',
				'selector' => '{{WRAPPER}} .ba-impact__donut-total',
			)
		);

		$this->end_controls_section();
	}

	private function register_chart_label_style_controls() {
		$this->start_controls_section(
			'style_sources',
			array(
				'label' => 'لیبل‌های نمودار',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ref_chart_label_background',
			array(
				'label'     => 'پس‌زمینه لیبل',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-impact__funding' => '--ba-impact-label-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'ref_chart_label_border',
			array(
				'label'     => 'رنگ حاشیه لیبل',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-impact__funding' => '--ba-impact-label-border: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'ref_chart_label_name_color',
			array(
				'label'     => 'رنگ عنوان منبع',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-impact__funding' => '--ba-impact-label-name: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_chart_label_width',
			array(
				'label'      => 'عرض لیبل',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 90, 'max' => 220 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-impact__funding' => '--ba-impact-label-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_chart_label_radius',
			array(
				'label'      => 'گردی لیبل',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-impact__funding' => '--ba-impact-label-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_chart_label_name_size',
			array(
				'label'      => 'اندازه عنوان منبع',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 9, 'max' => 20 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-impact__funding' => '--ba-impact-label-name-size: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_chart_label_value_size',
			array(
				'label'      => 'اندازه مقدار منبع',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 11, 'max' => 26 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-impact__funding' => '--ba-impact-label-value-size: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_chart_label_line_length',
			array(
				'label'      => 'طول خط اتصال',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-impact__funding' => '--ba-impact-label-line-length: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_summary_style_controls() {
		$this->start_controls_section(
			'style_summary',
			array(
				'label' => 'جمع‌بندی',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ref_summary_background',
			array(
				'label'     => 'پس‌زمینه',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-impact__summary' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'ref_summary_radius',
			array(
				'label'      => 'گردی',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-impact__summary' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_summary_label_typography',
				'label'    => 'تایپوگرافی عنوان',
				'selector' => '{{WRAPPER}} .ba-impact__summary-label',
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_summary_value_typography',
				'label'    => 'تایپوگرافی مقدار',
				'selector' => '{{WRAPPER}} .ba-impact__summary-value',
			)
		);
		$this->add_control(
			'ref_summary_icon_color',
			array(
				'label'     => 'رنگ آیکون',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-impact__summary-icon' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$sources  = $this->normalize_sources( (array) ( $settings['funding_sources'] ?? array() ) );
		$total    = array_sum( array_column( $sources, 'value' ) );
		$decimals = $this->sanitize_decimals( $settings['funding_decimals'] ?? 1 );
		$unit     = trim( (string) ( $settings['funding_unit'] ?? 'همت' ) );

		$chart_payload = array(
			'unit'     => $unit,
			'decimals' => $decimals,
			'sources'  => array_map(
				static function ( $source ) {
					return array(
						'name'  => (string) $source['label'],
						'value' => (float) $source['value'],
						'color' => (string) $source['color'],
					);
				},
				$sources
			),
		);
		?>
		<section class="ba-impact ba-home-about-widget" id="about" data-ba-home-about>
			<div class="ba-container ba-impact__grid">
				<div class="ba-impact__content">
					<div class="ba-section-heading ba-section-heading--stack">
						<?php if ( '' !== trim( (string) ( $settings['eyebrow'] ?? '' ) ) ) : ?>
							<span class="ba-section-heading__eyebrow"><?php echo esc_html( trim( (string) $settings['eyebrow'] ) ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== trim( (string) ( $settings['title'] ?? '' ) ) ) : ?>
							<h2 class="ba-section-heading__title"><?php echo esc_html( trim( (string) $settings['title'] ) ); ?></h2>
						<?php endif; ?>
					</div>

					<?php if ( '' !== trim( (string) ( $settings['description'] ?? '' ) ) ) : ?>
						<p class="ba-impact__text"><?php echo esc_html( trim( (string) $settings['description'] ) ); ?></p>
					<?php endif; ?>

					<?php $this->render_actions( (array) ( $settings['actions'] ?? array() ) ); ?>
				</div>

				<div class="ba-impact__funding" data-js-impact-funding>
					<div class="ba-impact__funding-head">
						<span class="ba-impact__funding-kicker"><?php echo esc_html( trim( (string) ( $settings['funding_kicker'] ?? '' ) ) ); ?></span>
						<?php if ( '' !== trim( (string) ( $settings['funding_note'] ?? '' ) ) ) : ?>
							<span class="ba-impact__funding-note"><?php echo esc_html( trim( (string) $settings['funding_note'] ) ); ?></span>
						<?php endif; ?>
					</div>

					<div class="ba-impact__funding-visual">
						<div class="ba-impact__chart-wrap">
							<div
								class="ba-impact__chart"
								data-js-impact-chart
								role="img"
								aria-label="<?php echo esc_attr( trim( (string) ( $settings['funding_kicker'] ?? 'ترکیب منابع اثرگذاری' ) ) ); ?>"
							></div>
						</div>

						<div class="ba-impact__donut-center" aria-hidden="true">
							<strong class="ba-impact__donut-total"><?php echo esc_html( $this->format_number( $total, $decimals ) ); ?></strong>
							<?php if ( '' !== $unit ) : ?><span class="ba-impact__donut-unit"><?php echo esc_html( $unit ); ?></span><?php endif; ?>
							<span class="ba-impact__donut-caption"><?php echo esc_html( trim( (string) ( $settings['donut_caption'] ?? '' ) ) ); ?></span>
						</div>

						<script type="application/json" data-js-impact-chart-data><?php echo wp_json_encode( $chart_payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
					</div>

					<?php
					if ( 'yes' === ( $settings['show_summary'] ?? '' ) ) {
						$this->render_summary( (array) ( $settings['summary_items'] ?? array() ), $total );
					}
					?>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * دکمه‌های معرفی را با کلاس‌های reference رندر می‌کند.
	 */
	private function render_actions( array $actions ) {
		$actions = array_values( array_filter( $actions, 'is_array' ) );
		if ( ! $actions ) {
			return;
		}
		?>
		<div class="ba-impact__actions">
			<?php foreach ( $actions as $action ) : ?>
				<?php
				$label = trim( (string) ( $action['label'] ?? '' ) );
				$link  = is_array( $action['link'] ?? null ) ? $action['link'] : array();
				$type  = 'primary' === ( $action['type'] ?? '' ) ? 'primary' : 'secondary';
				if ( '' === $label ) {
					continue;
				}
				?>
				<a class="ba-button ba-button--<?php echo esc_attr( $type ); ?>"<?php echo $this->build_link_attributes( $link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Summary قابل افزایش/کاهش و قابل مخفی‌کردن است.
	 */
	private function render_summary( array $items, float $funding_total ) {
		$visible = array_values(
			array_filter(
				$items,
				static function ( $item ) {
					return is_array( $item ) && 'yes' === ( $item['is_visible'] ?? '' );
				}
			)
		);

		if ( ! $visible ) {
			return;
		}
		?>
		<div class="ba-impact__summary" aria-label="جمع‌بندی منابع و خدمات‌گیرندگان">
			<?php foreach ( $visible as $item ) : ?>
				<?php
				$decimals      = $this->sanitize_decimals( $item['decimals'] ?? 0 );
				$is_auto_total = 'funding_total' === ( $item['value_type'] ?? '' );
				$value         = $is_auto_total ? $funding_total : max( 0, (float) ( $item['manual_value'] ?? 0 ) );
				$suffix        = trim( (string) ( $item['suffix'] ?? '' ) );
				$icon          = is_array( $item['icon'] ?? null ) ? $item['icon'] : array();
				$key           = sanitize_key( (string) ( $item['default_icon_key'] ?? '' ) );
				?>
				<div class="ba-impact__summary-item">
					<?php if ( ! empty( $icon['value'] ) || '' !== $this->get_summary_default_svg( $key ) ) : ?>
						<span class="ba-impact__summary-icon" aria-hidden="true">
							<?php if ( ! empty( $icon['value'] ) ) : ?>
								<?php Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) ); ?>
							<?php else : ?>
								<?php echo $this->get_summary_default_svg( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
						</span>
					<?php endif; ?>
					<span class="ba-impact__summary-copy">
						<span class="ba-impact__summary-label"><?php echo esc_html( trim( (string) ( $item['label'] ?? '' ) ) ); ?></span>
						<strong class="ba-impact__summary-value"><?php echo esc_html( $this->format_number( $value, $decimals ) . ( '' !== $suffix ? ' ' . $suffix : '' ) ); ?></strong>
					</span>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * ورودی Repeater منابع را به داده عددی امن و شناسه یکتا تبدیل می‌کند.
	 */
	private function normalize_sources( array $sources ): array {
		$normalized = array();

		foreach ( array_values( array_filter( $sources, 'is_array' ) ) as $source ) {
			$label = trim( (string) ( $source['label'] ?? '' ) );
			$value = max( 0, (float) ( $source['value'] ?? 0 ) );
			$color = sanitize_hex_color( (string) ( $source['color'] ?? '' ) );

			$normalized[] = array(
				'label' => $label,
				'value' => $value,
				'color' => $color ?: '#06783a',
			);
		}

		return $normalized;
	}

	private function sanitize_decimals( $value ): int {
		return max( 0, min( 3, (int) $value ) );
	}

	/**
	 * نمایش عدد فارسی با جداکننده‌های reference.
	 */
	private function format_number( $value, int $decimals ): string {
		$formatted = number_format( (float) $value, $decimals, '٫', '٬' );

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

	private function get_summary_default_svg( string $key ): string {
		$icons = array(
			'funding'       => '<svg viewBox="0 0 24 24"><path d="M4 19h16M6 17V9m4 8V5m4 12v-6m4 6V7"></path></svg>',
			'beneficiaries' => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path></svg>',
		);

		return $icons[ $key ] ?? '';
	}

	/**
	 * ویژگی‌های امن URL کنترل Elementor.
	 */
	private function build_link_attributes( array $link ): string {
		$url   = trim( (string) ( $link['url'] ?? '' ) );
		$attrs = ' href="' . esc_url( '' !== $url ? $url : '#' ) . '"';
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
}
