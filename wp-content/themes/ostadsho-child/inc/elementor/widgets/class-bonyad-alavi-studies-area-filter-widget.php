<?php
/**
 * ویجت فیلتر حوزه‌های مرکز مطالعات.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * Dropdown حوزه‌ها؛ در حالت live رویداد `ba:studies-area-change` را برای کاروسل‌ها
 * منتشر می‌کند و در حالت url صفحه را با ?area= باز می‌کند.
 */
final class Bonyad_Alavi_Studies_Area_Filter_Widget extends Widget_Base {

	public function get_name() {
		return 'bonyad_alavi_studies_area_filter';
	}

	public function get_title() {
		return esc_html__( 'فیلتر حوزه‌های مرکز مطالعات', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-select';
	}

	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	public function get_style_depends() {
		return array( 'bonyad-alavi-studies-center' );
	}

	public function get_script_depends() {
		return array( 'elementor-frontend', 'bonyad-alavi-studies-center' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_filter',
			array(
				'label' => 'فیلتر حوزه',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'دسته‌بندی', 'label_block' => true ) );
		$this->add_control( 'all_label', array( 'label' => 'گزینه پیش‌فرض', 'type' => Controls_Manager::TEXT, 'default' => 'همه حوزه‌ها', 'label_block' => true ) );
		$this->add_control(
			'help',
			array(
				'label'   => 'راهنما',
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => 'با انتخاب هر حوزه، فقط کتاب‌ها، نشست‌ها و پژوهش‌های همان حوزه نمایش داده می‌شود.',
			)
		);
		$this->add_control(
			'mode',
			array(
				'label'       => 'رفتار',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'live',
				'options'     => array(
					'live' => 'فیلتر سکشن‌های همین صفحه بدون reload',
					'url'  => 'باز کردن صفحه با ?area=',
				),
				'description' => 'حالت url برای صفحه آرشیو نشست‌ها مناسب است.',
			)
		);
		$this->add_control(
			'target_url',
			array(
				'label'       => 'صفحه مقصد',
				'type'        => Controls_Manager::URL,
				'options'     => false,
				'description' => 'خالی = همین صفحه.',
				'condition'   => array( 'mode' => 'url' ),
			)
		);
		$this->add_control(
			'orderby',
			array(
				'label'   => 'ترتیب حوزه‌ها',
				'type'    => Controls_Manager::SELECT,
				'default' => 'term_id',
				'options' => array(
					'term_id'    => 'ترتیب ایجاد',
					'name'       => 'نام',
					'term_order' => 'ترتیب دستی (در صورت پشتیبانی)',
				),
			)
		);
		$this->add_control( 'hide_empty', array( 'label' => 'پنهان کردن حوزه‌های خالی', 'type' => Controls_Manager::SWITCHER, 'default' => '' ) );

		$this->end_controls_section();

		$this->start_controls_section(
			'style_filter',
			array(
				'label' => 'فیلتر',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'ref_panel_bg',
			array(
				'label'     => 'پس‌زمینه',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-studies-filter' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_filter_title_typography',
				'label'    => 'تایپوگرافی عنوان',
				'selector' => '{{WRAPPER}} .ba-studies-filter__title',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * حوزه‌ها.
	 *
	 * @param array $settings تنظیمات.
	 * @return WP_Term[]
	 */
	private function get_areas( array $settings ) {
		$orderby = in_array( $settings['orderby'], array( 'term_id', 'name', 'term_order' ), true ) ? $settings['orderby'] : 'term_id';
		$terms   = get_terms(
			array(
				'taxonomy'   => BA_Studies_Content_Types::AREA_TAXONOMY,
				'hide_empty' => 'yes' === $settings['hide_empty'],
				'orderby'    => $orderby,
				'order'      => 'ASC',
			)
		);

		return is_array( $terms ) ? $terms : array();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$current  = BA_Studies_Query_Service::get_requested_area();
		$id       = 'ba-studies-area-' . $this->get_id();
		$config   = array(
			'mode'   => 'url' === $settings['mode'] ? 'url' : 'live',
			'target' => 'url' === $settings['mode'] && ! empty( $settings['target_url']['url'] ) ? esc_url_raw( $settings['target_url']['url'] ) : '',
		);
		?>
		<div class="ba-studies-filter ba-studies-widget" data-ba-studies-filter="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
			<?php if ( '' !== (string) $settings['title'] ) : ?>
				<label class="ba-studies-filter__title" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $settings['title'] ); ?></label>
			<?php endif; ?>
			<div class="ba-studies-filter__control">
				<select id="<?php echo esc_attr( $id ); ?>" class="ba-studies-filter__select" data-ba-studies-filter-select>
					<option value=""><?php echo esc_html( $settings['all_label'] ); ?></option>
					<?php foreach ( $this->get_areas( $settings ) as $term ) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<span class="ba-studies-filter__chevron" aria-hidden="true"></span>
			</div>
			<?php if ( '' !== (string) $settings['help'] ) : ?>
				<p class="ba-studies-filter__help"><?php echo esc_html( $settings['help'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
