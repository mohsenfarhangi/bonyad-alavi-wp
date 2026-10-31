<?php
/**
 * پایه مشترک ویجت‌های صفحه داخلی نشست (Theme Builder → Single).
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;

/**
 * تشخیص نشست جاری و رندر مشترک فهرست فایل‌ها.
 *
 * در ویرایشگر Theme Builder که پست جاری خود template است، آخرین نشست منتشرشده
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

		if ( $post_id && BA_Studies_Content_Types::SESSION_POST_TYPE === get_post_type( $post_id ) ) {
			return $post_id;
		}

		if ( ! $this->is_editor_context() ) {
			return 0;
		}

		$latest = get_posts(
			array(
				'post_type'      => BA_Studies_Content_Types::SESSION_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
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
	 * فهرست فایل‌های نشست.
	 *
	 * @param array  $files         فایل‌های توصیف‌شده.
	 * @param string $title         عنوان بخش.
	 * @param string $download_text متن دکمه.
	 * @return string
	 */
	protected static function render_files( array $files, $title, $download_text ) {
		if ( ! $files ) {
			return '';
		}

		$html = '<div class="ba-session-files">';

		if ( '' !== (string) $title ) {
			$html .= '<h2 class="ba-session-files__title">' . esc_html( $title ) . '</h2>';
		}

		$html .= '<ul class="ba-session-files__list">';

		foreach ( $files as $file ) {
			$icon  = 'video' === $file['kind'] ? 'video' : ( in_array( $file['kind'], array( 'audio', 'image', 'archive' ), true ) ? $file['kind'] : 'document' );
			$html .= '<li class="ba-session-file ba-session-file--' . esc_attr( $file['kind'] ) . '">'
				. '<span class="ba-session-file__icon">' . BA_Studies_Formatter::icon( $icon ) . '</span>'
				. '<span class="ba-session-file__text"><span class="ba-session-file__name">' . esc_html( $file['title'] ) . '</span>'
				. ( '' !== $file['meta'] ? '<span class="ba-session-file__meta">' . esc_html( $file['meta'] ) . '</span>' : '' )
				. '</span>'
				. '<a class="ba-session-file__download" href="' . esc_url( $file['url'] ) . '" download>' . esc_html( $download_text ) . '</a>'
				. '</li>';
		}

		return $html . '</ul></div>';
	}
}
