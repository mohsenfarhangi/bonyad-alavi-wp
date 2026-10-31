<?php
/**
 * ویجت گرید آرشیو مرکز مطالعات (پیش‌فرض: نشست‌ها) با پشتیبانی از ?area=.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

/**
 * صفحه آرشیو ساده با همان کارت‌های کاروسل و صفحه‌بندی سمت سرور.
 */
final class Bonyad_Alavi_Studies_Grid_Widget extends Widget_Base {

	const PAGE_VAR = 'ba_page';

	public function get_name() {
		return 'bonyad_alavi_studies_grid';
	}

	public function get_title() {
		return esc_html__( 'آرشیو مرکز مطالعات', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	public function get_style_depends() {
		return array( 'bonyad-alavi-studies-center' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_grid',
			array(
				'label' => 'آرشیو',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'content_type',
			array(
				'label'   => 'نوع خروجی',
				'type'    => Controls_Manager::SELECT,
				'default' => 'sessions',
				'options' => array(
					'sessions' => 'نشست‌ها و رویدادهای تخصصی',
					'books'    => 'کتاب‌ها و انتشارات',
					'research' => 'پژوهش‌ها و گزارش‌های تحلیلی',
				),
			)
		);
		$this->add_control( 'per_page', array( 'label' => 'تعداد در هر صفحه', 'type' => Controls_Manager::NUMBER, 'default' => 9, 'min' => 1, 'max' => 48 ) );
		$this->add_responsive_control(
			'columns',
			array(
				'label'          => 'ستون‌ها',
				'type'           => Controls_Manager::NUMBER,
				'min'            => 1,
				'max'            => 6,
				'default'        => 3,
				'tablet_default' => 2,
				'mobile_default' => 1,
				'selectors'      => array( '{{WRAPPER}} .ba-studies-grid__list' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
			)
		);
		$this->add_control( 'card_more_text', array( 'label' => 'متن لینک کارت', 'type' => Controls_Manager::TEXT, 'default' => 'مطالعه بیشتر' ) );
		$this->add_control( 'date_format', array( 'label' => 'فرمت تاریخ', 'type' => Controls_Manager::TEXT, 'default' => 'j F Y' ) );
		$this->add_control( 'empty_text', array( 'label' => 'پیام نبود نتیجه', 'type' => Controls_Manager::TEXT, 'default' => 'موردی یافت نشد', 'label_block' => true ) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$type     = BA_Studies_Query_Service::sanitize_type( $settings['content_type'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- صفحه‌بندی عمومی.
		$paged    = isset( $_GET[ self::PAGE_VAR ] ) ? max( 1, absint( $_GET[ self::PAGE_VAR ] ) ) : 1;
		$area     = BA_Studies_Query_Service::get_requested_area();
		$result   = BA_Studies_Query_Service::get_items(
			$type,
			array(
				'area'  => $area,
				'limit' => max( 1, (int) $settings['per_page'] ),
				'paged' => $paged,
			),
			'' !== trim( (string) $settings['date_format'] ) ? $settings['date_format'] : 'j F Y'
		);
		$opts     = array( 'more_text' => (string) $settings['card_more_text'] );
		?>
		<section class="ba-studies-grid ba-studies-section--<?php echo esc_attr( $type ); ?> ba-studies-widget">
			<?php if ( $result['items'] ) : ?>
				<div class="ba-studies-grid__list">
					<?php foreach ( $result['items'] as $item ) : ?>
						<?php echo BA_Studies_Card_Renderer::render_card( $item, $opts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in renderer ?>
					<?php endforeach; ?>
				</div>
				<?php if ( $result['pages'] > 1 ) : ?>
					<nav class="ba-studies-grid__pagination" aria-label="صفحه‌بندی">
						<?php
						$links = (string) paginate_links(
							array(
								'base'      => add_query_arg( self::PAGE_VAR, '%#%' ),
								'format'    => '',
								'current'   => $paged,
								'total'     => $result['pages'],
								'prev_text' => 'قبلی',
								'next_text' => 'بعدی',
							)
						);
						// فقط متن لینک‌ها فارسی شود، نه آدرس‌ها.
						$links = preg_replace_callback(
							'/>([^<]+)</',
							static function ( $match ) {
								return '>' . BA_Studies_Formatter::fa_digits( $match[1] ) . '<';
							},
							$links
						);
						echo wp_kses_post( $links );
						?>
					</nav>
				<?php endif; ?>
			<?php else : ?>
				<?php echo BA_Studies_Card_Renderer::render_empty( (string) $settings['empty_text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</section>
		<?php
	}
}
