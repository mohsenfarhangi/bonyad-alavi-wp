<?php
/**
 * ویجت لید و گزارش کوتاه نشست.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

/**
 * لید پررنگ (متای نشست) و متن اصلی نشست.
 */
final class Bonyad_Alavi_Studies_Session_Content_Widget extends Bonyad_Alavi_Studies_Session_Widget_Base {

	/**
	 * جلوگیری از بازگشت بی‌پایان the_content داخل template.
	 *
	 * @var bool
	 */
	private static $rendering = false;

	public function get_name() {
		return 'bonyad_alavi_studies_session_content';
	}

	public function get_title() {
		return esc_html__( 'نشست: لید و گزارش', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-post-content';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_body',
			array(
				'label' => 'محتوا',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control( 'show_lead', array( 'label' => 'نمایش لید', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'show_body', array( 'label' => 'نمایش متن گزارش', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );

		$this->end_controls_section();

		$this->start_controls_section(
			'style_body',
			array(
				'label' => 'متن',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ref_lead_color',
			array(
				'label'     => 'رنگ لید',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-session-content__lead' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_lead_typography',
				'label'    => 'تایپوگرافی لید',
				'selector' => '{{WRAPPER}} .ba-session-content__lead',
			)
		);
		$this->add_control(
			'ref_body_color',
			array(
				'label'     => 'رنگ متن',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-session-content__body' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_body_typography',
				'label'    => 'تایپوگرافی متن',
				'selector' => '{{WRAPPER}} .ba-session-content__body',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$post_id = $this->get_session_id();

		if ( ! $post_id || self::$rendering ) {
			$this->render_editor_notice();
			return;
		}

		$settings = $this->get_settings_for_display();
		$data     = BA_Studies_Session_Meta::get_view_data( $post_id );
		?>
		<div class="ba-session-content">
			<?php if ( 'yes' === $settings['show_lead'] && '' !== $data['lead'] ) : ?>
				<p class="ba-session-content__lead"><?php echo esc_html( $data['lead'] ); ?></p>
			<?php endif; ?>
			<?php if ( 'yes' === $settings['show_body'] ) : ?>
				<div class="ba-session-content__body">
					<?php
					self::$rendering = true;
					echo apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core content filter.
					self::$rendering = false;
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
