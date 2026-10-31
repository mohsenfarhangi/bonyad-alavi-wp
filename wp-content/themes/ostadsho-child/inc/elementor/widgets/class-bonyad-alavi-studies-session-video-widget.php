<?php
/**
 * ویجت ویدئوی کوتاه نشست.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

/**
 * ویدئو با پوستر (تصویر شاخص)، دکمه play وسط و برچسب «کلیپ کوتاه نشست · مدت».
 * بدون ویدئو فقط پوستر نمایش داده می‌شود.
 */
final class Bonyad_Alavi_Studies_Session_Video_Widget extends Bonyad_Alavi_Studies_Session_Widget_Base {

	public function get_name() {
		return 'bonyad_alavi_studies_session_video';
	}

	public function get_title() {
		return esc_html__( 'نشست: ویدئوی کوتاه', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-youtube';
	}

	public function get_script_depends() {
		return array( 'bonyad-alavi-studies-session' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_video',
			array(
				'label' => 'ویدئو',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control( 'badge_text', array( 'label' => 'متن برچسب', 'type' => Controls_Manager::TEXT, 'default' => 'کلیپ کوتاه نشست' ) );
		$this->add_control( 'show_duration', array( 'label' => 'نمایش مدت', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_responsive_control(
			'ref_ratio',
			array(
				'label'     => 'نسبت تصویر',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''       => 'پیش‌فرض (16:9)',
					'16 / 9' => '16:9',
					'4 / 3'  => '4:3',
					'21 / 9' => '21:9',
					'1 / 1'  => '1:1',
				),
				'selectors' => array( '{{WRAPPER}} .ba-session-video' => 'aspect-ratio: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_video',
			array(
				'label' => 'ویدئو',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ref_radius',
			array(
				'label'      => 'گردی گوشه',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-session-video' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$post_id = $this->get_session_id();

		if ( ! $post_id ) {
			$this->render_editor_notice();
			return;
		}

		$settings = $this->get_settings_for_display();
		$data     = BA_Studies_Session_Meta::get_view_data( $post_id );
		$badge    = trim( (string) $settings['badge_text'] );
		$duration = 'yes' === $settings['show_duration'] ? $data['duration'] : '';
		$label    = implode( ' · ', array_filter( array( $badge, $duration ) ) );
		?>
		<div class="ba-session-video<?php echo $data['video'] ? '' : ' ba-session-video--poster'; ?>" data-ba-session-video>
			<?php if ( $data['video'] ) : ?>
				<video class="ba-session-video__media" preload="none" playsinline <?php echo $data['poster'] ? 'poster="' . esc_url( $data['poster'] ) . '"' : ''; ?> src="<?php echo esc_url( $data['video'] ); ?>" data-ba-session-video-media></video>
				<button type="button" class="ba-session-video__play" data-ba-session-video-play aria-label="<?php echo esc_attr( 'پخش ' . ( '' !== $badge ? $badge : 'ویدئو' ) ); ?>"><?php echo BA_Studies_Formatter::icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			<?php elseif ( $data['poster'] ) : ?>
				<img class="ba-session-video__media" src="<?php echo esc_url( $data['poster'] ); ?>" alt="<?php echo esc_attr( $data['title'] ); ?>" fetchpriority="high">
			<?php endif; ?>
			<?php if ( $data['video'] && '' !== $label ) : ?>
				<span class="ba-session-video__badge"><?php echo BA_Studies_Formatter::icon( 'video' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $label ); ?></span></span>
			<?php endif; ?>
		</div>
		<?php
	}
}
