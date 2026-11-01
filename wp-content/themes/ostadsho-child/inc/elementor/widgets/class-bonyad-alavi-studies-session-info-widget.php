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
use Elementor\Group_Control_Box_Shadow;

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
				'label'     => 'تگ HTML عنوان',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h1',
				'options'   => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'div' => 'DIV' ),
				'condition' => array( 'show_title' => 'yes' ),
			)
		);
		$this->add_control( 'date_format', array( 'label' => 'فرمت تاریخ', 'type' => Controls_Manager::TEXT, 'default' => 'j F Y' ) );
		$this->add_control( 'time_separator', array( 'label' => 'جداکننده ساعت', 'type' => Controls_Manager::TEXT, 'default' => 'تا' ) );
		$this->add_control( 'area_separator', array( 'label' => 'جداکننده حوزه‌ها', 'type' => Controls_Manager::TEXT, 'default' => '،' ) );

		$this->end_controls_section();

		$this->start_controls_section(
			'content_rows',
			array(
				'label' => 'ردیف‌های جدول',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		foreach ( self::get_rows_schema() as $key => $row ) {
			$this->add_control( 'show_row_' . $key, array( 'label' => 'نمایش «' . $row['default'] . '»', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
			$this->add_control( $row['control'], array( 'label' => 'برچسب «' . $row['default'] . '»', 'type' => Controls_Manager::TEXT, 'default' => $row['default'], 'condition' => array( 'show_row_' . $key => 'yes' ) ) );
		}

		$this->end_controls_section();

		$this->start_controls_section(
			'content_files',
			array(
				'label' => 'فایل‌های نشست',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control( 'show_files', array( 'label' => 'نمایش فایل‌ها داخل کارت', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->register_files_content_controls();

		$this->end_controls_section();

		$this->register_card_style_controls();
		$this->register_files_style_controls();
	}

	/**
	 * ردیف‌های جدول اطلاعات: کلید، کنترل برچسب و برچسب پیش‌فرض.
	 *
	 * @return array
	 */
	private static function get_rows_schema() {
		return array(
			'type'     => array( 'control' => 'label_type', 'default' => 'نوع رویداد' ),
			'date'     => array( 'control' => 'label_date', 'default' => 'تاریخ برگزاری' ),
			'time'     => array( 'control' => 'label_time', 'default' => 'ساعت' ),
			'speakers' => array( 'control' => 'label_speakers', 'default' => 'سخنرانان' ),
			'area'     => array( 'control' => 'label_area', 'default' => 'حوزه' ),
			'location' => array( 'control' => 'label_location', 'default' => 'مکان' ),
		);
	}

	/**
	 * بخش‌های Style کارت، عنوان، جدول، برچسب و مقدار.
	 *
	 * @return void
	 */
	private function register_card_style_controls() {
		$this->start_controls_section( 'style_card', array( 'label' => 'کارت', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_color_control( 'ref_border_color', 'رنگ حاشیه', '.ba-session-info', 'border-color' );
		$this->add_size_control( 'ref_border_width', 'ضخامت حاشیه', '.ba-session-info', 'border-width', 8 );
		$this->add_size_control( 'ref_card_radius', 'گردی گوشه', '.ba-session-info', 'border-radius', 40 );
		$this->add_color_control( 'ref_card_bg', 'رنگ پس‌زمینه', '.ba-session-info', 'background-color' );
		$this->add_responsive_control(
			'ref_card_padding',
			array(
				'label'      => 'فاصله داخلی',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'selectors'  => array( '{{WRAPPER}} .ba-session-info' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'ref_card_shadow',
				'selector' => '{{WRAPPER}} .ba-session-info',
			)
		);
		$this->end_controls_section();

		$this->start_controls_section( 'style_title', array( 'label' => 'عنوان نشست', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_typography_control( 'ref_title_typography', 'تایپوگرافی', '.ba-session-info__title' );
		$this->add_color_control( 'ref_title_color', 'رنگ', '.ba-session-info__title' );
		$this->add_responsive_control(
			'ref_title_align',
			array(
				'label'     => 'تراز',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'right'  => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
					'center' => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
					'left'   => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
				),
				'selectors' => array( '{{WRAPPER}} .ba-session-info__title' => 'text-align: {{VALUE}};' ),
			)
		);
		$this->add_size_control( 'ref_title_gap', 'فاصله تا جدول', '.ba-session-info__title', 'margin-bottom', 80, true );
		$this->end_controls_section();

		$this->start_controls_section( 'style_table', array( 'label' => 'جدول اطلاعات', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_color_control( 'ref_table_bg', 'رنگ پس‌زمینه', '.ba-session-info__table', 'background-color' );
		$this->add_color_control( 'ref_table_border', 'رنگ حاشیه', '.ba-session-info__table', 'border-color' );
		$this->add_size_control( 'ref_table_radius', 'گردی گوشه', '.ba-session-info__table', 'border-radius', 40 );
		$this->add_responsive_control(
			'ref_table_padding',
			array(
				'label'      => 'فاصله داخلی',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'selectors'  => array( '{{WRAPPER}} .ba-session-info__table' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_color_control( 'ref_row_divider', 'رنگ خط بین ردیف‌ها', '.ba-session-info__row', 'border-bottom-color' );
		$this->add_size_control( 'ref_row_padding', 'فاصله عمودی ردیف‌ها', '.ba-session-info__row', 'padding-block', 40, true );
		$this->add_responsive_control(
			'ref_label_width',
			array(
				'label'      => 'عرض ستون برچسب',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 60, 'max' => 240 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-session-info__row' => 'grid-template-columns: {{SIZE}}{{UNIT}} minmax(0, 1fr);' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section( 'style_labels', array( 'label' => 'برچسب‌ها و مقدارها', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_typography_control( 'ref_label_typography', 'تایپوگرافی برچسب', '.ba-session-info__row dt' );
		$this->add_color_control( 'ref_label_color', 'رنگ برچسب', '.ba-session-info__row dt' );
		$this->add_typography_control( 'ref_value_typography', 'تایپوگرافی مقدار', '.ba-session-info__row dd' );
		$this->add_color_control( 'ref_value_color', 'رنگ مقدار', '.ba-session-info__row dd' );
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
		$area_sep = trim( (string) ( $settings['area_separator'] ?? '،' ) );
		$values   = array(
			'type'     => $data['event_type'],
			'date'     => $data['event_date'],
			'time'     => $time,
			'speakers' => $data['speakers'],
			'area'     => implode( '' !== $area_sep ? $area_sep . ' ' : ' ', $data['areas'] ),
			'location' => $data['location'],
		);
		$rows     = array();

		foreach ( self::get_rows_schema() as $key => $schema ) {
			if ( 'yes' !== ( $settings[ 'show_row_' . $key ] ?? 'yes' ) ) {
				continue;
			}

			$rows[] = array( (string) ( $settings[ $schema['control'] ] ?? $schema['default'] ), (string) $values[ $key ] );
		}
		?>
		<aside class="ba-session-info">
			<?php if ( 'yes' === $settings['show_title'] ) : ?>
				<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelist ?> class="ba-session-info__title"><?php echo esc_html( $data['title'] ); ?></<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelist ?>>
			<?php endif; ?>
			<dl class="ba-session-info__table">
				<?php foreach ( $rows as $row ) : ?>
					<?php if ( '' === trim( $row[1] ) ) { continue; } ?>
					<div class="ba-session-info__row">
						<dt><?php echo esc_html( $row[0] ); ?></dt>
						<dd><?php echo nl2br( esc_html( $row[1] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
			<?php
			if ( 'yes' === $settings['show_files'] ) {
				$files_title = 'yes' === ( $settings['show_files_title'] ?? 'yes' ) ? (string) $settings['files_title'] : '';

				echo self::render_files( $data['files'], $files_title, (string) $settings['download_text'], self::files_render_options( $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in renderer
			}
			?>
		</aside>
		<?php
	}
}
