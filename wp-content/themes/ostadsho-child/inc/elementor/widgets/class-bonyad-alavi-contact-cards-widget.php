<?php
/**
 * ویجت کارت‌های مراکز وابسته در صفحه «ارتباط با ما».
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;

final class Bonyad_Alavi_Contact_Cards_Widget extends Bonyad_Alavi_Contact_Page_Widget {

	public function get_name() {
		return 'bonyad_alavi_contact_cards';
	}

	public function get_title() {
		return esc_html__( 'کارت مراکز وابسته بنیاد علوی', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	/**
	 * کارت‌های فعلی صفحه ارتباط با ما.
	 */
	public static function default_cards() {
		return array(
			array(
				'_id'        => 'cc_camp',
				'title'      => 'مجتمع اردوگاهی امام خمینی (ره)',
				'title_link' => '',
				'phone'      => '۰۲۱۲۳۵۶۷۷۰۳-۴ | ۰۹۰۵۳۵۵۱۰۹۲',
				'address'    => 'تهران، لواسان، خیابان امام خمینی (ره)',
				'email'      => 'ordogah@bonyadalavi.ir',
			),
			array(
				'_id'        => 'cc_salamat',
				'title'      => 'موسسه سلامت',
				'title_link' => '',
				'phone'      => '۰۲۱۸۳۳۹۵۰۰۰',
				'address'    => 'تهران، ولیعصر، بالاتر از میرداماد، خیابان تابان غربی، پلاک ۷۲',
				'email'      => 'salamat@bonyadalavi.ir',
			),
			array(
				'_id'        => 'cc_eskan',
				'title'      => 'شرکت اسکان',
				'title_link' => '',
				'phone'      => '۰۲۱۸۸۶۵۶۴۵۱',
				'address'    => 'تهران، خیابان میرداماد، برج اسکان',
				'email'      => 'eskan@bonyadalavi.ir',
			),
		);
	}

	protected function register_controls() {
		$this->start_controls_section( 'content_cards', array( 'label' => 'کارت‌ها', 'tab' => Controls_Manager::TAB_CONTENT ) );

		$this->add_control( 'phone_icon', array( 'label' => 'آیکون تلفن', 'type' => Controls_Manager::TEXT, 'default' => '📞' ) );
		$this->add_control( 'address_icon', array( 'label' => 'آیکون آدرس', 'type' => Controls_Manager::TEXT, 'default' => '📍' ) );
		$this->add_control( 'email_icon', array( 'label' => 'آیکون ایمیل', 'type' => Controls_Manager::TEXT, 'default' => '✉️' ) );

		$repeater = new Repeater();
		$repeater->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => '', 'label_block' => true ) );
		$repeater->add_control( 'title_link', array( 'label' => 'لینک عنوان (اختیاری)', 'type' => Controls_Manager::TEXT, 'default' => '', 'label_block' => true ) );
		$repeater->add_control(
			'phone',
			array(
				'label'       => 'تلفن',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'description' => 'چند شماره را با | جدا کنید.',
			)
		);
		$repeater->add_control( 'address', array( 'label' => 'آدرس', 'type' => Controls_Manager::TEXTAREA, 'default' => '', 'rows' => 2 ) );
		$repeater->add_control( 'email', array( 'label' => 'ایمیل', 'type' => Controls_Manager::TEXT, 'default' => '', 'label_block' => true ) );

		$this->add_control(
			'cards',
			array(
				'label'       => 'کارت‌ها',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => self::default_cards(),
				'title_field' => '{{{ title }}}',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'style_cards', array( 'label' => 'استایل', 'tab' => Controls_Manager::TAB_STYLE ) );

		$base = '{{WRAPPER}} .ba-contact-cards ';

		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'cc_title_typography', 'label' => 'تایپوگرافی عنوان', 'selector' => $base . '.ba-contact-cards__title' ) );
		$this->add_control( 'cc_title_color', array( 'label' => 'رنگ عنوان', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-cards__title, ' . $base . '.ba-contact-cards__title a' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'cc_line_typography', 'label' => 'تایپوگرافی خطوط', 'selector' => $base . '.ba-contact-cards__line' ) );
		$this->add_control( 'cc_line_color', array( 'label' => 'رنگ متن خطوط', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-cards__line' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'cc_link_color', array( 'label' => 'رنگ لینک', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-cards__line a' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'cc_card_bg', array( 'label' => 'پس‌زمینه کارت', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-cards__card' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'cc_accent_color', array( 'label' => 'رنگ خط بالای کارت', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-cards__card' => 'border-top-color: {{VALUE}};' ) ) );
		$this->add_responsive_control(
			'cc_gap',
			array(
				'label'      => 'فاصله کارت‌ها',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array( $base . '.ba-contact-cards__grid' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$icons    = array(
			'phone'   => trim( (string) ( $settings['phone_icon'] ?? '' ) ),
			'address' => trim( (string) ( $settings['address_icon'] ?? '' ) ),
			'email'   => trim( (string) ( $settings['email_icon'] ?? '' ) ),
		);
		$cards    = array();

		foreach ( $this->get_rows( $settings, 'cards' ) as $row ) {
			$title   = trim( (string) ( $row['title'] ?? '' ) );
			$phones  = array_values( array_filter( array_map( 'trim', explode( '|', (string) ( $row['phone'] ?? '' ) ) ), 'strlen' ) );
			$address = trim( (string) ( $row['address'] ?? '' ) );
			$email   = trim( (string) ( $row['email'] ?? '' ) );

			if ( '' === $title && ! $phones && '' === $address && '' === $email ) {
				continue;
			}

			$cards[] = array(
				'title'      => $title,
				'title_link' => self::safe_url( $row['title_link'] ?? '' ),
				'phones'     => $phones,
				'address'    => $address,
				'email'      => $email,
			);
		}

		if ( ! $cards ) {
			return;
		}
		?>
		<div class="ba-contact-cards" dir="rtl">
			<div class="ba-contact-cards__grid">
				<?php foreach ( $cards as $card ) : ?>
					<div class="ba-contact-cards__card">
						<?php if ( '' !== $card['title'] ) : ?>
							<h4 class="ba-contact-cards__title">
								<?php if ( '' !== $card['title_link'] ) : ?>
									<a href="<?php echo esc_url( $card['title_link'] ); ?>"><?php echo esc_html( $card['title'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $card['title'] ); ?>
								<?php endif; ?>
							</h4>
						<?php endif; ?>
						<ul class="ba-contact-cards__list">
							<?php if ( $card['phones'] ) : ?>
								<li class="ba-contact-cards__line"><?php echo esc_html( $icons['phone'] ); ?> <?php
								foreach ( $card['phones'] as $i => $phone ) {
									echo $i ? ' | ' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo '<span dir="ltr">' . esc_html( $phone ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?></li>
							<?php endif; ?>
							<?php if ( '' !== $card['address'] ) : ?>
								<li class="ba-contact-cards__line"><?php echo esc_html( $icons['address'] ); ?> <?php echo esc_html( $card['address'] ); ?></li>
							<?php endif; ?>
							<?php if ( '' !== $card['email'] ) : ?>
								<li class="ba-contact-cards__line"><?php echo esc_html( $icons['email'] ); ?> <a href="<?php echo esc_url( 'mailto:' . $card['email'] ); ?>"><?php echo esc_html( $card['email'] ); ?></a></li>
							<?php endif; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
