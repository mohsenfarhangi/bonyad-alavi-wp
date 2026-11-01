<?php
/**
 * ویجت مستقل فهرست فایل‌های نشست.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

/**
 * برای چیدمان‌هایی که فایل‌ها بیرون از کارت اطلاعات قرار می‌گیرند.
 */
final class Bonyad_Alavi_Studies_Session_Files_Widget extends Bonyad_Alavi_Studies_Session_Widget_Base {

	public function get_name() {
		return 'bonyad_alavi_studies_session_files';
	}

	public function get_title() {
		return esc_html__( 'نشست: فایل‌ها', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-download-button';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_files',
			array(
				'label' => 'فایل‌ها',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->register_files_content_controls();

		$this->end_controls_section();

		$this->register_files_style_controls();
	}

	protected function render() {
		$post_id = $this->get_session_id();

		if ( ! $post_id ) {
			$this->render_editor_notice();
			return;
		}

		$settings = $this->get_settings_for_display();
		$data     = BA_Studies_Session_Meta::get_view_data( $post_id );

		$title    = 'yes' === ( $settings['show_files_title'] ?? 'yes' ) ? (string) $settings['files_title'] : '';

		echo self::render_files( $data['files'], $title, (string) $settings['download_text'], self::files_render_options( $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in renderer
	}
}
