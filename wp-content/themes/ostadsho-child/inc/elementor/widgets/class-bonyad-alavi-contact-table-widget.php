<?php
/**
 * ویجت جدول داخلی‌های واحدها در صفحه «ارتباط با ما».
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;

final class Bonyad_Alavi_Contact_Table_Widget extends Bonyad_Alavi_Contact_Page_Widget {

	public function get_name() {
		return 'bonyad_alavi_contact_table';
	}

	public function get_title() {
		return esc_html__( 'جدول داخلی‌ها بنیاد علوی', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-table';
	}

	/**
	 * ردیف‌های فعلی صفحه ارتباط با ما.
	 */
	public static function default_rows() {
		$rows = array(
			array( 'معاونت آموزش، مهارت و پرورش', '۳۶۸ – ۳۷۰ – ۳۳۵' ),
			array( 'معاونت توانمندسازی اقتصادی', '۱۸۱' ),
			array( 'مرکز هماهنگی حرکت‌های مردمی و جهادی', '۱۲۰ – ۱۹۹' ),
			array( 'مشارکت‌های مردمی و سازمانی', '۴۸۵' ),
			array( 'امور مزایده‌ها و مناقصه‌ها', '۵۸۸' ),
			array( 'جایزه ملی علوی', '۴۴۱' ),
			array( 'طرح حبیب', '۴۵۹' ),
			array( 'مرکز فناوری اطلاعات', '۲۲۲' ),
			array( 'مرکز مطالعات راهبردی', '۳۰۳' ),
			array( 'مرکز رسانه و روابط عمومی', '۳۹۷' ),
		);

		$out = array();
		foreach ( $rows as $i => $row ) {
			$out[] = array( '_id' => 'ct_row' . $i, 'title' => $row[0], 'value' => $row[1], 'value_dir' => 'ltr' );
		}

		return $out;
	}

	protected function register_controls() {
		$this->start_controls_section( 'content_table', array( 'label' => 'جدول', 'tab' => Controls_Manager::TAB_CONTENT ) );

		$this->add_control( 'head_title', array( 'label' => 'سرستون اول', 'type' => Controls_Manager::TEXT, 'default' => 'عنوان واحد' ) );
		$this->add_control( 'head_value', array( 'label' => 'سرستون دوم', 'type' => Controls_Manager::TEXT, 'default' => 'داخلی' ) );

		$repeater = new Repeater();
		$repeater->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => '', 'label_block' => true ) );
		$repeater->add_control( 'value', array( 'label' => 'مقدار', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$repeater->add_control(
			'value_dir',
			array(
				'label'   => 'جهت مقدار',
				'type'    => Controls_Manager::SELECT,
				'default' => 'ltr',
				'options' => array(
					'ltr' => 'چپ‌به‌راست',
					'rtl' => 'راست‌به‌چپ',
				),
			)
		);

		$this->add_control(
			'rows',
			array(
				'label'       => 'ردیف‌ها',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => self::default_rows(),
				'title_field' => '{{{ title }}}',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'style_table', array( 'label' => 'استایل', 'tab' => Controls_Manager::TAB_STYLE ) );

		$base = '{{WRAPPER}} .ba-contact-table ';

		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'ct_head_typography', 'label' => 'تایپوگرافی سرستون', 'selector' => $base . '.ba-contact-table__th' ) );
		$this->add_control( 'ct_head_color', array( 'label' => 'رنگ متن سرستون', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-table__th' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'ct_head_bg', array( 'label' => 'پس‌زمینه سرستون', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-table__th' => 'background-color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'ct_cell_typography', 'label' => 'تایپوگرافی سلول', 'selector' => $base . '.ba-contact-table__td' ) );
		$this->add_control( 'ct_cell_color', array( 'label' => 'رنگ متن سلول', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-table__td' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'ct_stripe_bg', array( 'label' => 'پس‌زمینه ردیف‌های زوج', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-table__row:nth-child(even) .ba-contact-table__td' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'ct_border_color', array( 'label' => 'رنگ خطوط', 'type' => Controls_Manager::COLOR, 'selectors' => array( $base . '.ba-contact-table__td' => 'border-bottom-color: {{VALUE}};', '{{WRAPPER}} .ba-contact-table' => 'border-color: {{VALUE}};' ) ) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings   = $this->get_settings_for_display();
		$head_title = trim( (string) ( $settings['head_title'] ?? '' ) );
		$head_value = trim( (string) ( $settings['head_value'] ?? '' ) );
		$rows       = array();

		foreach ( $this->get_rows( $settings, 'rows' ) as $row ) {
			$title = trim( (string) ( $row['title'] ?? '' ) );
			$value = trim( (string) ( $row['value'] ?? '' ) );

			if ( '' === $title && '' === $value ) {
				continue;
			}

			$rows[] = array(
				'title' => $title,
				'value' => $value,
				'dir'   => self::safe_dir( $row['value_dir'] ?? 'ltr' ),
			);
		}

		if ( ! $rows ) {
			return;
		}
		?>
		<div class="ba-contact-table">
			<table class="ba-contact-table__table" dir="rtl">
				<?php if ( '' !== $head_title || '' !== $head_value ) : ?>
					<thead>
						<tr>
							<th class="ba-contact-table__th"><?php echo esc_html( $head_title ); ?></th>
							<th class="ba-contact-table__th"><?php echo esc_html( $head_value ); ?></th>
						</tr>
					</thead>
				<?php endif; ?>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr class="ba-contact-table__row">
							<td class="ba-contact-table__td"><?php echo esc_html( $row['title'] ); ?></td>
							<td class="ba-contact-table__td"<?php echo 'ltr' === $row['dir'] ? ' dir="ltr"' : ''; ?>><?php echo esc_html( $row['value'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
