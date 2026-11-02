<?php
/**
 * پایه مشترک ویجت‌های صفحه داخلی نشست (Theme Builder → Single).
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * تشخیص نشست جاری و رندر مشترک فهرست فایل‌ها.
 *
 * در ویرایشگر Theme Builder که پست جاری خود template است، آخرین مقاله منتشرشده دارای اطلاعات نشست
 * برای پیش‌نمایش استفاده می‌شود تا template قابل طراحی باشد.
 */
abstract class Bonyad_Alavi_Studies_Session_Widget_Base extends Widget_Base {

	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	public function get_style_depends() {
		return array( 'bonyad-alavi-studies-session' );
	}

	/**
	 * شناسه نشست جاری یا 0.
	 *
	 * @return int
	 */
	protected function get_session_id() {
		$post_id = (int) get_the_ID();

		if ( $post_id && in_array( get_post_type( $post_id ), array( BA_Studies_Content_Types::SESSION_HOST_TYPE, BA_Studies_Content_Types::SESSION_POST_TYPE ), true ) ) {
			return $post_id;
		}

		if ( ! $this->is_editor_context() ) {
			return 0;
		}

		$latest = get_posts(
			array(
				'post_type'      => BA_Studies_Content_Types::SESSION_HOST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => BA_Studies_Session_Meta::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- فقط پیش‌نمایش ویرایشگر.
			)
		);

		return $latest ? (int) $latest[0] : 0;
	}

	/**
	 * آیا در ویرایشگر/پیش‌نمایش Elementor هستیم؟
	 *
	 * @return bool
	 */
	protected function is_editor_context() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}

		$plugin = \Elementor\Plugin::$instance;

		return ( isset( $plugin->editor ) && $plugin->editor->is_edit_mode() )
			|| ( isset( $plugin->preview ) && $plugin->preview->is_preview_mode() );
	}

	/**
	 * پیام راهنما فقط در ویرایشگر.
	 *
	 * @return void
	 */
	protected function render_editor_notice() {
		if ( $this->is_editor_context() ) {
			echo '<div class="ba-studies-session-notice">برای پیش‌نمایش این ویجت حداقل یک نشست منتشرشده لازم است.</div>';
		}
	}

	/**
	 * کنترل رنگ با selector نسبی به ریشه ویجت.
	 *
	 * @param string $id       شناسه کنترل.
	 * @param string $label    برچسب.
	 * @param string $selector selector نسبی.
	 * @param string $property ویژگی CSS.
	 * @return void
	 */
	protected function add_color_control( $id, $label, $selector, $property = 'color' ) {
		$this->add_control(
			$id,
			array(
				'label'     => $label,
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} ' . $selector => $property . ': {{VALUE}};' ),
			)
		);
	}

	/**
	 * کنترل تایپوگرافی.
	 *
	 * @param string $name     نام گروه.
	 * @param string $label    برچسب.
	 * @param string $selector selector نسبی.
	 * @return void
	 */
	protected function add_typography_control( $name, $label, $selector ) {
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => $name,
				'label'    => $label,
				'selector' => '{{WRAPPER}} ' . $selector,
			)
		);
	}

	/**
	 * کنترل اسلایدر عددی (px) با selector و ویژگی CSS.
	 *
	 * @param string $id       شناسه.
	 * @param string $label    برچسب.
	 * @param string $selector selector نسبی.
	 * @param string $property ویژگی CSS.
	 * @param int    $max      حداکثر.
	 * @param bool   $responsive آیا واکنش‌گرا باشد.
	 * @return void
	 */
	protected function add_size_control( $id, $label, $selector, $property, $max = 100, $responsive = false ) {
		$args = array(
			'label'      => $label,
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => $max ) ),
			'selectors'  => array( '{{WRAPPER}} ' . $selector => $property . ': {{SIZE}}{{UNIT}};' ),
		);

		if ( $responsive ) {
			$this->add_responsive_control( $id, $args );
			return;
		}

		$this->add_control( $id, $args );
	}

	/**
	 * کنترل‌های محتوای فایل‌ها (مشترک ویجت کارت اطلاعات و ویجت فایل‌ها).
	 *
	 * @return void
	 */
	protected function register_files_content_controls() {
		$this->add_control( 'show_files_title', array( 'label' => 'نمایش عنوان بخش', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'files_title', array( 'label' => 'عنوان بخش فایل‌ها', 'type' => Controls_Manager::TEXT, 'default' => 'فایل‌های نشست', 'condition' => array( 'show_files_title' => 'yes' ) ) );
		$this->add_control( 'files_title_tag', array( 'label' => 'تگ HTML عنوان', 'type' => Controls_Manager::SELECT, 'default' => 'h2', 'options' => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'div' => 'DIV' ), 'condition' => array( 'show_files_title' => 'yes' ) ) );
		$this->add_control( 'download_text', array( 'label' => 'متن دکمه دانلود', 'type' => Controls_Manager::TEXT, 'default' => 'دانلود' ) );
		$this->add_control( 'show_file_icon', array( 'label' => 'نمایش آیکون فایل', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'show_file_meta', array( 'label' => 'نمایش مشخصات فایل (حجم/مدت)', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
	}

	/**
	 * بخش‌های Style فایل‌ها (مشترک ویجت کارت اطلاعات و ویجت فایل‌ها).
	 *
	 * @return void
	 */
	protected function register_files_style_controls() {
		$this->start_controls_section( 'style_files_title', array( 'label' => 'عنوان فایل‌ها', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_typography_control( 'ref_files_title_typography', 'تایپوگرافی', '.ba-session-files__title' );
		$this->add_color_control( 'ref_files_title_color', 'رنگ', '.ba-session-files__title' );
		$this->add_responsive_control(
			'ref_files_title_align',
			array(
				'label'     => 'تراز',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'right'  => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
					'center' => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
					'left'   => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
				),
				'selectors' => array( '{{WRAPPER}} .ba-session-files__title' => 'text-align: {{VALUE}};' ),
			)
		);
		$this->add_size_control( 'ref_files_title_gap', 'فاصله تا فهرست', '.ba-session-files__title', 'margin-bottom', 60, true );
		$this->end_controls_section();

		$this->start_controls_section( 'style_files_item', array( 'label' => 'ردیف فایل', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_size_control( 'ref_file_gap', 'فاصله بین ردیف‌ها', '.ba-session-files__list', 'gap', 40, true );
		$this->add_color_control( 'ref_file_bg', 'رنگ پس‌زمینه', '.ba-session-file', 'background-color' );
		$this->add_color_control( 'ref_file_border', 'رنگ حاشیه', '.ba-session-file', 'border-color' );
		$this->add_size_control( 'ref_file_radius', 'گردی گوشه', '.ba-session-file', 'border-radius', 40 );
		$this->add_responsive_control(
			'ref_file_padding',
			array(
				'label'      => 'فاصله داخلی',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'selectors'  => array( '{{WRAPPER}} .ba-session-file' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_typography_control( 'ref_file_name_typography', 'تایپوگرافی نام فایل', '.ba-session-file__name' );
		$this->add_color_control( 'ref_file_name_color', 'رنگ نام فایل', '.ba-session-file__name' );
		$this->add_typography_control( 'ref_file_meta_typography', 'تایپوگرافی مشخصات', '.ba-session-file__meta' );
		$this->add_color_control( 'ref_file_meta_color', 'رنگ مشخصات', '.ba-session-file__meta' );
		$this->add_color_control( 'ref_file_icon_color', 'رنگ آیکون', '.ba-session-file__icon' );
		$this->add_size_control( 'ref_file_icon_size', 'اندازه آیکون', '.ba-session-file__icon', 'font-size', 48 );
		$this->end_controls_section();

		$this->start_controls_section( 'style_files_button', array( 'label' => 'دکمه دانلود', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_typography_control( 'ref_button_typography', 'تایپوگرافی', '.ba-session-file__download' );
		$this->add_color_control( 'ref_button_color', 'رنگ متن', '.ba-session-file__download', 'color' );
		$this->add_color_control( 'ref_button_bg', 'رنگ پس‌زمینه', '.ba-session-file__download', 'background-color' );
		$this->add_color_control( 'ref_button_bg_hover', 'رنگ پس‌زمینه هنگام هاور', '.ba-session-file__download:hover, {{WRAPPER}} .ba-session-file__download:focus-visible', 'background-color' );
		$this->add_size_control( 'ref_button_radius', 'گردی گوشه', '.ba-session-file__download', 'border-radius', 40 );
		$this->add_size_control( 'ref_button_width', 'حداقل عرض', '.ba-session-file__download', 'min-width', 200, true );
		$this->add_size_control( 'ref_button_height', 'ارتفاع', '.ba-session-file__download', 'height', 80, true );
		$this->end_controls_section();
	}

	/**
	 * فهرست فایل‌های نشست.
	 *
	 * @param array  $files         فایل‌های توصیف‌شده.
	 * @param string $title         عنوان بخش.
	 * @param string $download_text متن دکمه.
	 * @param array  $options       show_icon, show_meta, title_tag.
	 * @return string
	 */
	protected static function render_files( array $files, $title, $download_text, array $options = array() ) {
		if ( ! $files ) {
			return '';
		}

		$options = wp_parse_args(
			$options,
			array(
				'show_icon' => true,
				'show_meta' => true,
				'title_tag' => 'h2',
			)
		);
		$tag     = in_array( $options['title_tag'], array( 'h2', 'h3', 'h4', 'div' ), true ) ? $options['title_tag'] : 'h2';
		$html    = '<div class="ba-session-files">';

		if ( '' !== (string) $title ) {
			$html .= '<' . $tag . ' class="ba-session-files__title">' . esc_html( $title ) . '</' . $tag . '>';
		}

		$html .= '<ul class="ba-session-files__list">';

		foreach ( $files as $file ) {
			$icon  = 'video' === $file['kind'] ? 'video' : ( in_array( $file['kind'], array( 'audio', 'image', 'archive' ), true ) ? $file['kind'] : 'document' );
			$html .= '<li class="ba-session-file ba-session-file--' . esc_attr( $file['kind'] ) . '">'
				. ( $options['show_icon'] ? '<span class="ba-session-file__icon">' . BA_Studies_Formatter::icon( $icon ) . '</span>' : '' )
				. '<span class="ba-session-file__text"><span class="ba-session-file__name">' . esc_html( $file['title'] ) . '</span>'
				. ( $options['show_meta'] && '' !== $file['meta'] ? '<span class="ba-session-file__meta">' . esc_html( $file['meta'] ) . '</span>' : '' )
				. '</span>'
				. '<a class="ba-session-file__download" href="' . esc_url( $file['url'] ) . '" download>' . esc_html( $download_text ) . '</a>'
				. '</li>';
		}

		return $html . '</ul></div>';
	}

	/**
	 * گزینه‌های رندر فایل از تنظیمات ویجت.
	 *
	 * @param array $settings تنظیمات ویجت.
	 * @return array
	 */
	protected static function files_render_options( array $settings ) {
		return array(
			'show_icon' => 'yes' === ( $settings['show_file_icon'] ?? 'yes' ),
			'show_meta' => 'yes' === ( $settings['show_file_meta'] ?? 'yes' ),
			'title_tag' => (string) ( $settings['files_title_tag'] ?? 'h2' ),
		);
	}
}
