<?php
/**
 * ویجت آخرین اخبار صفحه اصلی بنیاد علوی.
 *
 * Visual reference:
 * bonyad-alavi-redesign/redesign/index.html -> #news / .ba-news
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
 * سکشن اخبار Query-driven با markup و responsive layout طرح مرجع.
 */
final class Bonyad_Alavi_Home_News_Widget extends Widget_Base {

	public function get_name() {
		return 'bonyad_alavi_home_news';
	}

	public function get_title() {
		return esc_html__( 'آخرین اخبار صفحه اصلی بنیاد علوی', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	public function get_style_depends() {
		return array( 'bonyad-alavi-home-news-widget' );
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_query_controls();
		$this->register_layout_style_controls();
		$this->register_heading_style_controls();
		$this->register_card_style_controls();
		$this->register_card_text_style_controls();
	}

	/**
	 * متن‌های سکشن، فرمت تاریخ و محتوای قابل تنظیم کارت را ثبت می‌کند.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'content_news',
			array(
				'label' => 'محتوای سکشن اخبار',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'eyebrow',
			array(
				'label'       => 'پیش‌عنوان',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'روایت اقدامات',
				'label_block' => true,
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => 'عنوان',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'آخرین اخبار',
				'label_block' => true,
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => 'تگ HTML عنوان',
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'h5'  => 'H5',
					'h6'  => 'H6',
					'div' => 'DIV',
				),
			)
		);

		$this->add_control(
			'lead',
			array(
				'label'       => 'توضیحات',
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => 'تازه‌ترین خبرها و گزارش‌ها، با تفکیک حوزه مأموریتی و اولویت خوانایی.',
				'label_block' => true,
			)
		);

		$this->add_control(
			'all_news_text',
			array(
				'label'       => 'متن لینک همه اخبار',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'همه اخبار',
				'label_block' => true,
				'description' => 'اگر متن یا لینک خالی باشد، این اکشن نمایش داده نمی‌شود.',
			)
		);

		$this->add_control(
			'all_news_link',
			array(
				'label'       => 'لینک همه اخبار',
				'type'        => Controls_Manager::URL,
				'default'     => array( 'url' => 'https://bonyadalavi.ir/category/news/' ),
				'placeholder' => 'https://',
				'options'     => array( 'url', 'is_external', 'nofollow' ),
			)
		);

		$this->add_control(
			'card_taxonomy',
			array(
				'label'       => 'Taxonomy برچسب کارت',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'category',
				'options'     => array_merge( array( '' => 'بدون برچسب' ), $this->get_taxonomy_options() ),
				'description' => 'اولین term نوشته از Taxonomy انتخابی نمایش داده می‌شود.',
			)
		);

		$this->add_control(
			'date_format',
			array(
				'label'       => 'فرمت تاریخ',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'F Y',
				'placeholder' => 'F Y',
				'description' => 'فرمت استاندارد تاریخ وردپرس/PHP. افزونه فارسی‌ساز سایت تبدیل شمسی را روی خروجی اعمال می‌کند. خالی = عدم نمایش تاریخ.',
			)
		);

		$this->add_control(
			'card_more_text',
			array(
				'label'       => 'متن لینک کارت',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'مشاهده',
				'label_block' => true,
				'description' => 'اگر خالی باشد، لینک متنی پایین کارت نمایش داده نمی‌شود؛ تصویر و عنوان همچنان قابل کلیک هستند.',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * کنترل‌های کاربردی و امن WP_Query را با prefix news ثبت می‌کند.
	 */
	private function register_query_controls() {
		$this->start_controls_section(
			'query_news',
			array(
				'label' => 'WP Query اخبار',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'news_post_types',
			array(
				'label'       => 'Post Type',
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_post_type_options(),
				'default'     => array( 'post' ),
			)
		);

		$this->add_control(
			'news_posts_per_page',
			array(
				'label'   => 'تعداد نمایش',
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 48,
				'step'    => 1,
				'default' => 4,
			)
		);

		$this->add_control(
			'news_categories',
			array(
				'label'       => 'دسته‌بندی‌ها',
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_term_options( 'category' ),
			)
		);

		$this->add_control(
			'news_tags',
			array(
				'label'       => 'برچسب‌ها',
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_term_options( 'post_tag' ),
			)
		);

		$this->add_control(
			'news_authors',
			array(
				'label'       => 'نویسندگان',
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_author_options(),
			)
		);

		$this->add_control(
			'news_search',
			array(
				'label'       => 'جستجو در محتوا',
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$this->add_control(
			'news_include_ids',
			array(
				'label'       => 'فقط شناسه نوشته‌ها',
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'description' => 'شناسه‌ها را با ویرگول یا فاصله جدا کنید.',
			)
		);

		$this->add_control(
			'news_exclude_ids',
			array(
				'label'       => 'حذف شناسه نوشته‌ها',
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'description' => 'شناسه‌ها را با ویرگول یا فاصله جدا کنید.',
			)
		);

		$this->add_control(
			'news_orderby',
			array(
				'label'   => 'مرتب‌سازی بر اساس',
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array(
					'date'       => 'تاریخ انتشار',
					'modified'   => 'آخرین ویرایش',
					'title'      => 'عنوان',
					'menu_order' => 'ترتیب منو',
					'rand'       => 'تصادفی',
					'ID'         => 'شناسه',
				),
			)
		);

		$this->add_control(
			'news_order',
			array(
				'label'   => 'ترتیب',
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => array(
					'DESC' => 'نزولی',
					'ASC'  => 'صعودی',
				),
			)
		);

		$this->add_control(
			'news_offset',
			array(
				'label'   => 'Offset',
				'type'    => Controls_Manager::NUMBER,
				'min'     => 0,
				'step'    => 1,
				'default' => 0,
			)
		);

		$this->add_control(
			'news_ignore_sticky',
			array(
				'label'        => 'نادیده گرفتن Sticky Posts',
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'news_date_after',
			array(
				'label' => 'تاریخ از',
				'type'  => Controls_Manager::DATE_TIME,
			)
		);

		$this->add_control(
			'news_date_before',
			array(
				'label' => 'تاریخ تا',
				'type'  => Controls_Manager::DATE_TIME,
			)
		);

		$this->add_control(
			'news_tax_relation',
			array(
				'label'   => 'رابطه Taxonomy Query',
				'type'    => Controls_Manager::SELECT,
				'default' => 'AND',
				'options' => array(
					'AND' => 'AND',
					'OR'  => 'OR',
				),
			)
		);

		$taxonomy_repeater = new Repeater();
		$taxonomy_repeater->add_control(
			'taxonomy',
			array(
				'label'   => 'Taxonomy',
				'type'    => Controls_Manager::SELECT,
				'options' => $this->get_taxonomy_options(),
			)
		);
		$taxonomy_repeater->add_control(
			'field',
			array(
				'label'   => 'نوع مقدار',
				'type'    => Controls_Manager::SELECT,
				'default' => 'term_id',
				'options' => array(
					'term_id' => 'Term ID',
					'slug'    => 'Slug',
					'name'    => 'Name',
				),
			)
		);
		$taxonomy_repeater->add_control(
			'terms',
			array(
				'label'       => 'Termها',
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'description' => 'مقادیر را با ویرگول جدا کنید؛ نوع مقدار از گزینه بالا تعیین می‌شود.',
			)
		);
		$taxonomy_repeater->add_control(
			'operator',
			array(
				'label'   => 'Operator',
				'type'    => Controls_Manager::SELECT,
				'default' => 'IN',
				'options' => array(
					'IN'     => 'IN',
					'NOT IN' => 'NOT IN',
					'AND'    => 'AND',
				),
			)
		);
		$taxonomy_repeater->add_control(
			'include_children',
			array(
				'label'        => 'شامل زیرمجموعه‌ها',
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'news_tax_query',
			array(
				'label'       => 'Taxonomy Query پیشرفته',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $taxonomy_repeater->get_controls(),
				'title_field' => '{{{ taxonomy || "Taxonomy شرط" }}}',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * کنترل‌های Layout بدون visual default؛ CSS مرجع تا زمان override کاربر authoritative است.
	 */
	private function register_layout_style_controls() {
		$this->start_controls_section(
			'style_layout',
			array(
				'label' => 'چیدمان سکشن',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ref_section_background',
			array(
				'label'     => 'پس‌زمینه سکشن',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget' => 'background: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'ref_section_border_color',
			array(
				'label'     => 'رنگ خط بالای سکشن',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget' => 'border-top-color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_section_padding',
			array(
				'label'      => 'فاصله داخلی سکشن',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .ba-home-news-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_container_max',
			array(
				'label'      => 'حداکثر عرض محتوا',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 720, 'max' => 1800 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-home-news-widget' => '--ba-news-container-max: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_heading_gap',
			array(
				'label'      => 'فاصله عنوان تا گرید',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-home-news-widget .ba-section-heading' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_grid_columns',
			array(
				'label'   => 'تعداد ستون‌ها',
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-news__grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
			)
		);

		$this->add_responsive_control(
			'ref_grid_gap',
			array(
				'label'      => 'فاصله کارت‌ها',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-home-news-widget .ba-news__grid' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_heading_style_controls() {
		$this->start_controls_section(
			'style_heading',
			array(
				'label' => 'عنوان سکشن',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_eyebrow_typography',
				'label'    => 'تایپوگرافی پیش‌عنوان',
				'selector' => '{{WRAPPER}} .ba-home-news-widget .ba-section-heading__eyebrow',
			)
		);
		$this->add_control(
			'ref_eyebrow_color',
			array(
				'label'     => 'رنگ پیش‌عنوان',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-section-heading__eyebrow' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_eyebrow_line_color',
			array(
				'label'     => 'رنگ خط پیش‌عنوان',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-section-heading__eyebrow::before' => 'background: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_heading_title_typography',
				'label'    => 'تایپوگرافی عنوان',
				'selector' => '{{WRAPPER}} .ba-home-news-widget .ba-section-heading__title',
			)
		);
		$this->add_control(
			'ref_heading_title_color',
			array(
				'label'     => 'رنگ عنوان',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-section-heading__title' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_heading_lead_typography',
				'label'    => 'تایپوگرافی توضیحات',
				'selector' => '{{WRAPPER}} .ba-home-news-widget .ba-section-heading__lead',
			)
		);
		$this->add_control(
			'ref_heading_lead_color',
			array(
				'label'     => 'رنگ توضیحات',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-section-heading__lead' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_heading_action_typography',
				'label'    => 'تایپوگرافی لینک همه اخبار',
				'selector' => '{{WRAPPER}} .ba-home-news-widget .ba-section-heading__action',
			)
		);
		$this->add_control(
			'ref_heading_action_color',
			array(
				'label'     => 'رنگ لینک همه اخبار',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-section-heading__action' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_heading_action_hover_color',
			array(
				'label'     => 'رنگ Hover لینک همه اخبار',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-section-heading__action:hover' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_card_style_controls() {
		$this->start_controls_section(
			'style_cards',
			array(
				'label' => 'کارت خبر',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ref_card_background',
			array(
				'label'     => 'پس‌زمینه کارت',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'ref_card_border',
				'selector' => '{{WRAPPER}} .ba-home-news-widget .ba-news-card',
			)
		);
		$this->add_responsive_control(
			'ref_card_radius',
			array(
				'label'      => 'گردی کارت',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'ref_card_shadow',
				'label'    => 'سایه کارت',
				'selector' => '{{WRAPPER}} .ba-home-news-widget .ba-news-card',
			)
		);
		$this->add_control(
			'ref_card_hover_border_color',
			array(
				'label'     => 'رنگ Border در Hover',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card:hover' => 'border-color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'ref_card_hover_shadow',
				'label'    => 'سایه Hover',
				'selector' => '{{WRAPPER}} .ba-home-news-widget .ba-news-card:hover',
			)
		);

		$this->add_responsive_control(
			'ref_media_aspect_ratio',
			array(
				'label'   => 'نسبت تصویر',
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'16 / 9' => '16:9',
					'3 / 2'  => '3:2',
					'4 / 3'  => '4:3',
					'1 / 1'  => '1:1',
				),
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card__media' => 'aspect-ratio: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_media_background',
			array(
				'label'     => 'پس‌زمینه تصویر/Placeholder',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card__media' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_placeholder_color',
			array(
				'label'     => 'رنگ آیکون Placeholder',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card__placeholder' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_image_hover_scale',
			array(
				'label'      => 'Zoom تصویر در Hover',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array(),
				'range'      => array( 'px' => array( 'min' => 1, 'max' => 1.2, 'step' => 0.01 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card:hover .ba-news-card__media img' => 'transform: scale({{SIZE}});' ),
			)
		);
		$this->add_responsive_control(
			'ref_card_body_padding',
			array(
				'label'      => 'فاصله داخلی متن کارت',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_card_text_style_controls() {
		$this->start_controls_section(
			'style_card_text',
			array(
				'label' => 'متن کارت خبر',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_category_typography',
				'label'    => 'تایپوگرافی دسته',
				'selector' => '{{WRAPPER}} .ba-home-news-widget .ba-news-card__category',
			)
		);
		$this->add_control(
			'ref_category_color',
			array(
				'label'     => 'رنگ دسته',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card__category' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_card_title_typography',
				'label'    => 'تایپوگرافی عنوان خبر',
				'selector' => '{{WRAPPER}} .ba-home-news-widget .ba-news-card__title',
			)
		);
		$this->add_control(
			'ref_card_title_color',
			array(
				'label'     => 'رنگ عنوان خبر',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card__title' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_card_title_hover_color',
			array(
				'label'     => 'رنگ Hover عنوان',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card__title a:hover' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_meta_typography',
				'label'    => 'تایپوگرافی تاریخ',
				'selector' => '{{WRAPPER}} .ba-home-news-widget .ba-news-card__date',
			)
		);
		$this->add_control(
			'ref_meta_color',
			array(
				'label'     => 'رنگ تاریخ',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card__date' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_more_typography',
				'label'    => 'تایپوگرافی لینک مشاهده',
				'selector' => '{{WRAPPER}} .ba-home-news-widget .ba-news-card__more',
			)
		);
		$this->add_control(
			'ref_more_color',
			array(
				'label'     => 'رنگ لینک مشاهده',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card__more' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_more_hover_color',
			array(
				'label'     => 'رنگ Hover لینک مشاهده',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-home-news-widget .ba-news-card__more:hover' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$query    = ( new BA_Content_Query_Service() )->create_query( $settings, 'news' );
		$posts    = is_array( $query->posts ) ? $query->posts : array();

		if ( ! $posts ) {
			$this->render_editor_notice();
			return;
		}

		$eyebrow       = trim( (string) ( $settings['eyebrow'] ?? '' ) );
		$title         = trim( (string) ( $settings['title'] ?? '' ) );
		$lead          = trim( (string) ( $settings['lead'] ?? '' ) );
		$all_news_text = trim( (string) ( $settings['all_news_text'] ?? '' ) );
		$all_news_link = is_array( $settings['all_news_link'] ?? null ) ? $settings['all_news_link'] : array();
		$has_all_news  = '' !== $all_news_text && ! empty( $all_news_link['url'] );
		$has_heading   = '' !== $eyebrow || '' !== $title || '' !== $lead || $has_all_news;
		$title_tag     = $this->sanitize_heading_tag( $settings['title_tag'] ?? 'h2' );
		$date_format   = trim( (string) ( $settings['date_format'] ?? 'F Y' ) );
		$more_text     = trim( (string) ( $settings['card_more_text'] ?? '' ) );
		$taxonomy      = sanitize_key( (string) ( $settings['card_taxonomy'] ?? 'category' ) );

		if ( $has_all_news ) {
			$this->add_link_attributes( 'all_news_link', $all_news_link );
		}
		?>
		<section class="ba-news ba-home-news-widget" id="news" aria-label="<?php echo esc_attr( '' !== $title ? $title : 'اخبار' ); ?>">
			<div class="ba-container">
				<?php if ( $has_heading ) : ?>
					<div class="ba-section-heading">
						<div class="ba-section-heading__copy">
							<?php if ( '' !== $eyebrow ) : ?>
								<span class="ba-section-heading__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
							<?php endif; ?>

							<?php if ( '' !== $title ) : ?>
								<<?php echo esc_html( $title_tag ); ?> class="ba-section-heading__title"><?php echo esc_html( $title ); ?></<?php echo esc_html( $title_tag ); ?>>
							<?php endif; ?>

							<?php if ( '' !== $lead ) : ?>
								<p class="ba-section-heading__lead"><?php echo esc_html( $lead ); ?></p>
							<?php endif; ?>
						</div>

						<?php if ( $has_all_news ) : ?>
							<a class="ba-section-heading__action" <?php echo $this->get_render_attribute_string( 'all_news_link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<?php echo esc_html( $all_news_text ); ?>
								<svg aria-hidden="true" fill="none" height="18" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18">
									<path d="M19 12H5m6-6-6 6 6 6"></path>
								</svg>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="ba-news__grid">
					<?php foreach ( $posts as $post ) : ?>
						<?php $this->render_news_card( $post, $taxonomy, $date_format, $more_text ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * یک کارت خبر را با تصویر شاخص یا placeholder ثابت رندر می‌کند.
	 *
	 * @param WP_Post $post        نوشته جاری.
	 * @param string  $taxonomy    Taxonomy برچسب کارت.
	 * @param string  $date_format فرمت تاریخ.
	 * @param string  $more_text   متن لینک مشاهده.
	 */
	private function render_news_card( $post, $taxonomy, $date_format, $more_text ) {
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$permalink    = get_permalink( $post );
		$title        = trim( wp_strip_all_tags( get_the_title( $post ) ) );
		$category     = $this->get_first_term_name( $post->ID, $taxonomy );
		$date         = '' !== $date_format ? get_the_date( $date_format, $post ) : '';
		$thumbnail_id = get_post_thumbnail_id( $post );
		$aria_label   = '' !== $title ? $title : 'مشاهده خبر';
		?>
		<article class="ba-news-card ba-card ba-card--news">
			<a class="ba-news-card__media" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>">
				<?php if ( $thumbnail_id ) : ?>
					<?php
					echo wp_kses_post(
						BA_Media_Helper::render_image(
							array(
								'id'  => $thumbnail_id,
								'alt' => BA_Media_Helper::get_attachment_alt( $thumbnail_id, $title ),
							),
							'medium_large',
							array(
								'loading'  => 'lazy',
								'decoding' => 'async',
							)
						)
					);
					?>
				<?php else : ?>
					<span class="ba-news-card__placeholder" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
							<rect x="3" y="4" width="18" height="16" rx="2"></rect>
							<circle cx="8.5" cy="9" r="1.5"></circle>
							<path d="m5 17 4.5-4.5 3.2 3.2 2.2-2.2L19 17"></path>
						</svg>
					</span>
				<?php endif; ?>
			</a>

			<div class="ba-news-card__body">
				<?php if ( '' !== $category ) : ?>
					<span class="ba-news-card__category"><?php echo esc_html( $category ); ?></span>
				<?php endif; ?>

				<?php if ( '' !== $title ) : ?>
					<h3 class="ba-news-card__title"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h3>
				<?php endif; ?>

				<?php if ( '' !== $date || '' !== $more_text ) : ?>
					<div class="ba-news-card__meta">
						<?php if ( '' !== $date ) : ?>
							<span class="ba-news-card__date"><?php echo esc_html( $date ); ?></span>
						<?php endif; ?>

						<?php if ( '' !== $more_text ) : ?>
							<a class="ba-news-card__more" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $more_text . ': ' . $aria_label ); ?>"><?php echo esc_html( $more_text ); ?> <span aria-hidden="true">←</span></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * اولین term قابل نمایش از taxonomy انتخابی را برمی‌گرداند.
	 */
	private function get_first_term_name( $post_id, $taxonomy ) {
		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return '';
		}

		$terms = get_the_terms( absint( $post_id ), $taxonomy );
		if ( is_wp_error( $terms ) || ! is_array( $terms ) || ! $terms ) {
			return '';
		}

		$first = reset( $terms );

		return $first instanceof WP_Term ? (string) $first->name : '';
	}

	private function sanitize_heading_tag( $tag ) {
		$allowed = array( 'h2', 'h3', 'h4', 'h5', 'h6', 'div' );
		$tag     = strtolower( (string) $tag );

		return in_array( $tag, $allowed, true ) ? $tag : 'h2';
	}

	private function get_post_type_options() {
		$options = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			$options[ $type->name ] = $type->labels->singular_name ?: $type->label;
		}

		return $options;
	}

	private function get_taxonomy_options() {
		$options = array();
		foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) {
			$options[ $taxonomy->name ] = $taxonomy->labels->singular_name ?: $taxonomy->label;
		}

		return $options;
	}

	private function get_term_options( $taxonomy ) {
		$options = array();
		$terms   = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

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

	private function render_editor_notice() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}

		echo '<div class="elementor-alert elementor-alert-info">هیچ نوشته‌ای با تنظیمات Query فعلی پیدا نشد.</div>';
	}
}
