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
		$this->add_control(
			'lead_tag',
			array(
				'label'     => 'تگ HTML لید',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'p',
				'options'   => array( 'p' => 'P', 'h2' => 'H2', 'h3' => 'H3', 'div' => 'DIV' ),
				'condition' => array( 'show_lead' => 'yes' ),
			)
		);
		$this->add_control( 'show_body', array( 'label' => 'نمایش متن گزارش', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );

		$this->end_controls_section();

		$this->start_controls_section(
			'style_lead',
			array(
				'label'     => 'لید',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_lead' => 'yes' ),
			)
		);
		$this->add_color_control( 'ref_lead_color', 'رنگ لید', '.ba-session-content__lead' );
		$this->add_typography_control( 'ref_lead_typography', 'تایپوگرافی لید', '.ba-session-content__lead' );
		$this->add_responsive_control(
			'ref_lead_align',
			array(
				'label'     => 'تراز',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'right'   => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
					'center'  => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
					'left'    => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
					'justify' => array( 'title' => 'تراز دوطرفه', 'icon' => 'eicon-text-align-justify' ),
				),
				'selectors' => array( '{{WRAPPER}} .ba-session-content__lead' => 'text-align: {{VALUE}};' ),
			)
		);
		$this->add_size_control( 'ref_lead_gap', 'فاصله تا متن گزارش', '.ba-session-content__lead', 'margin-bottom', 80, true );
		$this->end_controls_section();

		$this->start_controls_section(
			'style_body',
			array(
				'label'     => 'متن گزارش',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_body' => 'yes' ),
			)
		);
		$this->add_color_control( 'ref_body_color', 'رنگ متن', '.ba-session-content__body' );
		$this->add_typography_control( 'ref_body_typography', 'تایپوگرافی متن', '.ba-session-content__body' );
		$this->add_responsive_control(
			'ref_body_align',
			array(
				'label'     => 'تراز',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'right'   => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
					'center'  => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
					'left'    => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
					'justify' => array( 'title' => 'تراز دوطرفه', 'icon' => 'eicon-text-align-justify' ),
				),
				'selectors' => array( '{{WRAPPER}} .ba-session-content__body' => 'text-align: {{VALUE}};' ),
			)
		);
		$this->add_size_control( 'ref_body_paragraph_gap', 'فاصله بین پاراگراف‌ها', '.ba-session-content__body p', 'margin-bottom', 60, true );
		$this->add_color_control( 'ref_body_link_color', 'رنگ پیوندها', '.ba-session-content__body a' );
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
		$lead_tag = in_array( $settings['lead_tag'] ?? 'p', array( 'p', 'h2', 'h3', 'div' ), true ) ? $settings['lead_tag'] : 'p';
		?>
		<div class="ba-session-content">
			<?php if ( 'yes' === $settings['show_lead'] && '' !== $data['lead'] ) : ?>
				<<?php echo $lead_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelist ?> class="ba-session-content__lead"><?php echo esc_html( $data['lead'] ); ?></<?php echo $lead_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelist ?>>
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
