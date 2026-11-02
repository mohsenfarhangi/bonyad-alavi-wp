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

		$this->add_control( 'show_play', array( 'label' => 'نمایش دکمه پخش', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'show_badge', array( 'label' => 'نمایش برچسب', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'badge_text', array( 'label' => 'متن برچسب', 'type' => Controls_Manager::TEXT, 'default' => 'کلیپ کوتاه نشست', 'condition' => array( 'show_badge' => 'yes' ) ) );
		$this->add_control( 'show_duration', array( 'label' => 'نمایش مدت', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'condition' => array( 'show_badge' => 'yes' ) ) );
		$this->add_control(
			'badge_position',
			array(
				'label'                => 'جای برچسب',
				'type'                 => Controls_Manager::SELECT,
				'default'              => '',
				'options'              => array(
					''      => 'پایین چپ (پیش‌فرض طرح)',
					'right' => 'پایین راست',
				),
				'selectors_dictionary' => array( 'right' => 'right: var(--ba-session-badge-offset, 12px); left: auto;' ),
				'selectors'            => array( '{{WRAPPER}} .ba-session-video__badge' => '{{VALUE}}' ),
				'condition'            => array( 'show_badge' => 'yes' ),
			)
		);
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
		$this->add_size_control( 'ref_radius', 'گردی گوشه', '.ba-session-video', 'border-radius', 40 );
		$this->add_color_control( 'ref_video_bg', 'رنگ پس‌زمینه (بدون پوستر)', '.ba-session-video', 'background-color' );
		$this->end_controls_section();

		$this->start_controls_section(
			'style_play',
			array(
				'label'     => 'دکمه پخش',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_play' => 'yes' ),
			)
		);
		$this->add_responsive_control(
			'ref_play_size',
			array(
				'label'      => 'اندازه دکمه',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 32, 'max' => 140 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-session-video__play' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_size_control( 'ref_play_icon', 'اندازه آیکون', '.ba-session-video__play', 'font-size', 64, true );
		$this->add_color_control( 'ref_play_bg', 'رنگ پس‌زمینه', '.ba-session-video__play', 'background-color' );
		$this->add_color_control( 'ref_play_color', 'رنگ آیکون', '.ba-session-video__play', 'color' );
		$this->end_controls_section();

		$this->start_controls_section(
			'style_badge',
			array(
				'label'     => 'برچسب',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_badge' => 'yes' ),
			)
		);
		$this->add_typography_control( 'ref_badge_typography', 'تایپوگرافی', '.ba-session-video__badge' );
		$this->add_color_control( 'ref_badge_color', 'رنگ متن', '.ba-session-video__badge', 'color' );
		$this->add_color_control( 'ref_badge_bg', 'رنگ پس‌زمینه', '.ba-session-video__badge', 'background-color' );
		$this->add_size_control( 'ref_badge_radius', 'گردی گوشه', '.ba-session-video__badge', 'border-radius', 30 );
		$this->add_responsive_control(
			'ref_badge_offset',
			array(
				'label'      => 'فاصله از لبه‌ها',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-session-video' => '--ba-session-badge-offset: {{SIZE}}{{UNIT}};' ),
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
		$show_badge = 'yes' === ( $settings['show_badge'] ?? 'yes' );
		$show_play  = 'yes' === ( $settings['show_play'] ?? 'yes' );
		$badge    = trim( (string) $settings['badge_text'] );
		$duration = 'yes' === $settings['show_duration'] ? $data['duration'] : '';
		$label    = $show_badge ? implode( ' · ', array_filter( array( $badge, $duration ) ) ) : '';

		// بدون ویدئو و بدون تصویر شاخص جعبه خالی نمایش داده نمی‌شود (در ویرایشگر برای طراحی می‌ماند).
		if ( ! $data['video'] && ! $data['poster'] && ! $this->is_editor_context() ) {
			return;
		}
		?>
		<div class="ba-session-video<?php echo $data['video'] ? '' : ' ba-session-video--poster'; ?>" data-ba-session-video>
			<?php if ( $data['video'] ) : ?>
				<video class="ba-session-video__media" preload="none" playsinline <?php echo $data['poster'] ? 'poster="' . esc_url( $data['poster'] ) . '"' : ''; ?> src="<?php echo esc_url( $data['video'] ); ?>" data-ba-session-video-media></video>
				<?php if ( $show_play ) : ?>
				<button type="button" class="ba-session-video__play" data-ba-session-video-play aria-label="<?php echo esc_attr( 'پخش ' . ( '' !== $badge ? $badge : 'ویدئو' ) ); ?>"><?php echo BA_Studies_Formatter::icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				<?php endif; ?>
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
