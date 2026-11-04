<?php
/**
 * ویجت اطلاعات تماس (آدرس، کد پستی، تلفن، ایمیل) صفحه «ارتباط با ما».
 *
 * پیش‌فرض‌ها برابر محتوا و markup فعلی صفحه (alavi-info-list) هستند.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;

final class Bonyad_Alavi_Contact_Info_Widget extends Bonyad_Alavi_Contact_Page_Widget {

	public function get_name() {
		return 'bonyad_alavi_contact_info';
	}

	public function get_title() {
		return esc_html__( 'اطلاعات تماس بنیاد علوی', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-map-pin';
	}

	/**
	 * محتوای فعلی صفحه ارتباط با ما.
	 */
	public static function default_items() {
		return array(
			array( '_id' => 'ci_addr', 'icon' => '📍', 'label' => 'آدرس:', 'text' => 'تهران، خیابان کریم‌خان زند، بعد از تقاطع حافظ، خیابان شهید گلابی، پلاک ۱۲', 'link' => '', 'text_dir' => 'rtl' ),
			array( '_id' => 'ci_post', 'icon' => '📮', 'label' => 'کد پستی:', 'text' => '۱۵۹۴۷۱۶۳۱۳', 'link' => '', 'text_dir' => 'ltr' ),
			array( '_id' => 'ci_tel', 'icon' => '📞', 'label' => 'تلفن:', 'text' => '۰۲۱-۸۳۳۵۲۰۰۰', 'link' => 'tel:02183352000', 'text_dir' => 'ltr' ),
			array( '_id' => 'ci_mail', 'icon' => '✉️', 'label' => 'ایمیل:', 'text' => 'info@bonyadalavi.ir', 'link' => 'mailto:info@bonyadalavi.ir', 'text_dir' => 'rtl' ),
		);
	}

	protected function register_controls() {
		$this->start_controls_section( 'content_items', array( 'label' => 'اطلاعات تماس', 'tab' => Controls_Manager::TAB_CONTENT ) );

		$repeater = new Repeater();
		$repeater->add_control( 'icon', array( 'label' => 'آیکون / ایموجی', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$repeater->add_control( 'label', array( 'label' => 'برچسب', 'type' => Controls_Manager::TEXT, 'default' => '', 'label_block' => true ) );
		$repeater->add_control( 'text', array( 'label' => 'متن', 'type' => Controls_Manager::TEXTAREA, 'default' => '', 'rows' => 2 ) );
		$repeater->add_control(
			'link',
			array(
				'label'       => 'لینک (اختیاری)',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'description' => 'فقط http، https، tel و mailto پذیرفته می‌شود؛ مثل tel:02112345678',
			)
		);
		$repeater->add_control(
			'text_dir',
			array(
				'label'   => 'جهت متن',
				'type'    => Controls_Manager::SELECT,
				'default' => 'rtl',
				'options' => array(
					'rtl' => 'راست‌به‌چپ',
					'ltr' => 'چپ‌به‌راست (اعداد، تلفن)',
				),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => 'آیتم‌ها',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => self::default_items(),
				'title_field' => '{{{ label }}}',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'style_info', array( 'label' => 'استایل', 'tab' => Controls_Manager::TAB_STYLE ) );

		$base = '{{WRAPPER}} .ba-contact-info ';

		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'ci_label_typography', 'label' => 'تایپوگرافی برچسب', 'selector' => $base . '.ba-contact-info__label' ) );
		$this->add_control( 'ci_label_color', array( 'label' => 'رنگ برچسب', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-info__label' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'ci_text_typography', 'label' => 'تایپوگرافی متن', 'selector' => $base . '.ba-contact-info__text' ) );
		$this->add_control( 'ci_text_color', array( 'label' => 'رنگ متن', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-info__text' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'ci_link_color', array( 'label' => 'رنگ لینک', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-info__text a' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'ci_divider_color', array( 'label' => 'رنگ خط جداکننده', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-info__item' => 'border-bottom-color: {{VALUE}};' ) ) );
		$this->add_responsive_control(
			'ci_item_padding',
			array(
				'label'      => 'فاصله عمودی آیتم‌ها',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( $base . '.ba-contact-info__item' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'ci_icon_size',
			array(
				'label'      => 'اندازه آیکون',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 10, 'max' => 60 ) ),
				'selectors'  => array( $base . '.ba-contact-info__icon' => 'font-size: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$items    = array();

		foreach ( $this->get_rows( $settings, 'items' ) as $row ) {
			$label = trim( (string) ( $row['label'] ?? '' ) );
			$text  = trim( (string) ( $row['text'] ?? '' ) );
			$icon  = trim( (string) ( $row['icon'] ?? '' ) );

			if ( '' === $label && '' === $text ) {
				continue;
			}

			$items[] = array(
				'icon'  => $icon,
				'label' => $label,
				'text'  => $text,
				'link'  => self::safe_url( $row['link'] ?? '' ),
				'dir'   => self::safe_dir( $row['text_dir'] ?? 'rtl' ),
			);
		}

		if ( ! $items ) {
			return;
		}
		?>
		<div class="ba-contact-info" dir="rtl">
			<ul class="ba-contact-info__list">
				<?php foreach ( $items as $item ) : ?>
					<li class="ba-contact-info__item">
						<?php if ( '' !== $item['icon'] ) : ?>
							<span class="ba-contact-info__icon"><?php echo esc_html( $item['icon'] ); ?></span>
						<?php endif; ?>
						<div>
							<?php if ( '' !== $item['label'] ) : ?>
								<strong class="ba-contact-info__label"><?php echo esc_html( $item['label'] ); ?></strong>
							<?php endif; ?>
							<?php if ( '' !== $item['text'] ) : ?>
								<p class="ba-contact-info__text"<?php echo 'ltr' === $item['dir'] ? ' dir="ltr"' : ''; ?>>
									<?php if ( '' !== $item['link'] ) : ?>
										<a href="<?php echo esc_url( $item['link'] ); ?>"><?php echo esc_html( $item['text'] ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $item['text'] ); ?>
									<?php endif; ?>
								</p>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
