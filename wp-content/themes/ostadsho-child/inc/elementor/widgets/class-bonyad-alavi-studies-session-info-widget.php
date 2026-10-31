<?php
/**
 * ویجت کارت اطلاعات نشست (ستون کناری) به‌همراه فایل‌ها.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

/**
 * کارت با حاشیه سبز: عنوان، جدول اطلاعات و (اختیاری) «فایل‌های نشست».
 */
final class Bonyad_Alavi_Studies_Session_Info_Widget extends Bonyad_Alavi_Studies_Session_Widget_Base {

	public function get_name() {
		return 'bonyad_alavi_studies_session_info';
	}

	public function get_title() {
		return esc_html__( 'نشست: کارت اطلاعات و فایل‌ها', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-info-box';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_info',
			array(
				'label' => 'اطلاعات',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control( 'show_title', array( 'label' => 'نمایش عنوان نشست', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control(
			'title_tag',
			array(
				'label'   => 'تگ HTML عنوان',
				'type'    => Controls_Manager::SELECT,
				'default' => 'h1',
				'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'div' => 'DIV' ),
			)
		);
		$this->add_control( 'label_type', array( 'label' => 'برچسب نوع رویداد', 'type' => Controls_Manager::TEXT, 'default' => 'نوع رویداد' ) );
		$this->add_control( 'label_date', array( 'label' => 'برچسب تاریخ', 'type' => Controls_Manager::TEXT, 'default' => 'تاریخ برگزاری' ) );
		$this->add_control( 'label_time', array( 'label' => 'برچسب ساعت', 'type' => Controls_Manager::TEXT, 'default' => 'ساعت' ) );
		$this->add_control( 'time_separator', array( 'label' => 'جداکننده ساعت', 'type' => Controls_Manager::TEXT, 'default' => 'تا' ) );
		$this->add_control( 'label_speakers', array( 'label' => 'برچسب سخنرانان', 'type' => Controls_Manager::TEXT, 'default' => 'سخنرانان' ) );
		$this->add_control( 'label_area', array( 'label' => 'برچسب حوزه', 'type' => Controls_Manager::TEXT, 'default' => 'حوزه' ) );
		$this->add_control( 'label_location', array( 'label' => 'برچسب مکان', 'type' => Controls_Manager::TEXT, 'default' => 'مکان' ) );
		$this->add_control( 'date_format', array( 'label' => 'فرمت تاریخ', 'type' => Controls_Manager::TEXT, 'default' => 'j F Y' ) );

		$this->end_controls_section();

		$this->start_controls_section(
			'content_files',
			array(
				'label' => 'فایل‌های نشست',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control( 'show_files', array( 'label' => 'نمایش فایل‌ها داخل کارت', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'files_title', array( 'label' => 'عنوان بخش فایل‌ها', 'type' => Controls_Manager::TEXT, 'default' => 'فایل‌های نشست' ) );
		$this->add_control( 'download_text', array( 'label' => 'متن دکمه دانلود', 'type' => Controls_Manager::TEXT, 'default' => 'دانلود' ) );

		$this->end_controls_section();

		$this->start_controls_section(
			'style_card',
			array(
				'label' => 'کارت',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ref_border_color',
			array(
				'label'     => 'رنگ حاشیه',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-session-info' => 'border-color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_title_typography',
				'label'    => 'تایپوگرافی عنوان',
				'selector' => '{{WRAPPER}} .ba-session-info__title',
			)
		);
		$this->add_control(
			'ref_button_bg',
			array(
				'label'     => 'رنگ دکمه دانلود',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-session-file__download' => 'background-color: {{VALUE}};' ),
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
		$format   = '' !== trim( (string) $settings['date_format'] ) ? $settings['date_format'] : 'j F Y';
		$data     = BA_Studies_Session_Meta::get_view_data( $post_id, $format );
		$tag      = in_array( $settings['title_tag'], array( 'h1', 'h2', 'h3', 'div' ), true ) ? $settings['title_tag'] : 'h1';
		$time     = implode( ' ' . trim( (string) $settings['time_separator'] ) . ' ', array_filter( array( $data['time_start'], $data['time_end'] ) ) );
		$rows     = array(
			array( $settings['label_type'], $data['event_type'] ),
			array( $settings['label_date'], $data['event_date'] ),
			array( $settings['label_time'], $time ),
			array( $settings['label_speakers'], $data['speakers'] ),
			array( $settings['label_area'], implode( '، ', $data['areas'] ) ),
			array( $settings['label_location'], $data['location'] ),
		);
		?>
		<aside class="ba-session-info">
			<?php if ( 'yes' === $settings['show_title'] ) : ?>
				<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelist ?> class="ba-session-info__title"><?php echo esc_html( $data['title'] ); ?></<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelist ?>>
			<?php endif; ?>
			<dl class="ba-session-info__table">
				<?php foreach ( $rows as $row ) : ?>
					<?php if ( '' === trim( (string) $row[1] ) ) { continue; } ?>
					<div class="ba-session-info__row">
						<dt><?php echo esc_html( (string) $row[0] ); ?></dt>
						<dd><?php echo nl2br( esc_html( (string) $row[1] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
			<?php
			if ( 'yes' === $settings['show_files'] ) {
				echo self::render_files( $data['files'], (string) $settings['files_title'], (string) $settings['download_text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in renderer
			}
			?>
		</aside>
		<?php
	}
}
