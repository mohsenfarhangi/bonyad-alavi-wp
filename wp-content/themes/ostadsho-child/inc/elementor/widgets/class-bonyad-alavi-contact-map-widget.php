<?php
/**
 * ویجت نقشه Google Maps (فقط آدرس embed معتبر) برای صفحه «ارتباط با ما».
 *
 * HTML خام نمی‌پذیرد؛ فقط آدرس https://www.google.com/maps/embed... قبول و iframe از آن ساخته می‌شود.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

final class Bonyad_Alavi_Contact_Map_Widget extends Bonyad_Alavi_Contact_Page_Widget {

	const DEFAULT_EMBED = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d864.5!2d51.4138!3d35.7065!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzXCsDQyJzIzLjQiTiA1McKwMjQnNDkuNyJF!5e0!3m2!1sfa!2sir!4v1';

	public function get_name() {
		return 'bonyad_alavi_contact_map';
	}

	public function get_title() {
		return esc_html__( 'نقشه بنیاد علوی', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-google-maps';
	}

	/**
	 * فقط آدرس‌های https://www.google.com/maps/embed قابل قبول‌اند؛ در غیر این‌صورت رشته خالی.
	 */
	public static function safe_embed_url( $url ) {
		$url = trim( (string) $url );

		if ( ! preg_match( '#^https://www\.google\.com/maps/embed(\?|/)#i', $url ) ) {
			return '';
		}

		return (string) esc_url_raw( $url );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content_map', array( 'label' => 'نقشه', 'tab' => Controls_Manager::TAB_CONTENT ) );

		$this->add_control(
			'embed_url',
			array(
				'label'       => 'آدرس Embed گوگل مپ',
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => self::DEFAULT_EMBED,
				'rows'        => 4,
				'description' => 'فقط آدرس‌هایی که با https://www.google.com/maps/embed شروع می‌شوند (مقدار src داخل کد Embed).',
			)
		);

		$this->add_control(
			'map_title',
			array(
				'label'       => 'عنوان نقشه (دسترسی‌پذیری)',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'نقشه موقعیت بنیاد علوی',
				'label_block' => true,
			)
		);

		$this->add_responsive_control(
			'map_height',
			array(
				'label'      => 'ارتفاع',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 150, 'max' => 900 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 350 ),
				'selectors'  => array( '{{WRAPPER}} .ba-contact-map .ba-contact-map__frame' => 'height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'style_map', array( 'label' => 'استایل', 'tab' => Controls_Manager::TAB_STYLE ) );

		$this->add_control( 'map_border_color', array( 'label' => 'رنگ کادر', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .ba-contact-map' => 'border-color: {{VALUE}};' ) ) );
		$this->add_responsive_control(
			'map_radius',
			array(
				'label'      => 'گردی گوشه‌ها',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-contact-map' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$src      = self::safe_embed_url( $settings['embed_url'] ?? '' );
		$title    = trim( (string) ( $settings['map_title'] ?? '' ) );

		if ( '' === $src ) {
			if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info">آدرس Embed معتبر گوگل مپ (https://www.google.com/maps/embed...) وارد کنید.</div>';
			}
			return;
		}
		?>
		<div class="ba-contact-map">
			<iframe class="ba-contact-map__frame" src="<?php echo esc_url( $src ); ?>" title="<?php echo esc_attr( '' !== $title ? $title : 'نقشه' ); ?>" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
		</div>
		<?php
	}
}
