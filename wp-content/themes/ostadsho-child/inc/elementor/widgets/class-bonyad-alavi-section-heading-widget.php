<?php
/**
 * ویجت عمومی عنوان سکشن بنیاد علوی.
 *
 * Visual reference:
 * bonyad-alavi-redesign/redesign/assets/css/components.css -> .ba-section-heading
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
 * Section Heading مستقل برای استفاده در هر بخش Elementor.
 */
final class Bonyad_Alavi_Section_Heading_Widget extends Widget_Base {

	public function get_name() {
		return 'bonyad_alavi_section_heading';
	}

	public function get_title() {
		return esc_html__( 'عنوان بخش بنیاد علوی', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-heading';
	}

	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	public function get_style_depends() {
		return array( 'bonyad-alavi-section-heading-widget' );
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_layout_style_controls();
		$this->register_eyebrow_style_controls();
		$this->register_title_style_controls();
		$this->register_lead_style_controls();
		$this->register_action_style_controls();
	}

	/**
	 * ساختار محتوایی و سه variant مرجع را ثبت می‌کند.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'content_heading',
			array(
				'label' => 'محتوای عنوان بخش',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => 'چیدمان',
				'type'    => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => array(
					'default' => 'Default',
					'stack'   => 'Stack',
					'card'    => 'Card',
				),
			)
		);

		$this->add_control(
			'eyebrow',
			array(
				'label'       => 'پیش‌عنوان',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'عنوان بخش',
				'label_block' => true,
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => 'عنوان',
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => 'عنوان اصلی بخش',
				'rows'        => 2,
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => 'تگ HTML عنوان',
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => array(
					'h1'  => 'H1',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'h5'  => 'H5',
					'h6'  => 'H6',
					'div' => 'DIV',
				),
			)
		);

		$this->add_control(
			'lead',
			array(
				'label'       => 'توضیحات',
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'placeholder' => 'توضیح کوتاه این بخش',
			)
		);

		$this->add_control(
			'action_text',
			array(
				'label'       => 'متن اکشن',
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'description' => 'اکشن فقط وقتی نمایش داده می‌شود که متن و لینک هر دو وارد شده باشند.',
			)
		);

		$this->add_control(
			'action_link',
			array(
				'label'       => 'لینک اکشن',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://',
				'options'     => array( 'url', 'is_external', 'nofollow' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * کنترل‌های layout بدون visual default؛ CSS مرجع authority است.
	 */
	private function register_layout_style_controls() {
		$this->start_controls_section(
			'style_layout',
			array(
				'label' => 'چیدمان',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'ref_margin_bottom',
			array(
				'label'      => 'فاصله پایین',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 120 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ba-section-heading-widget' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'ref_layout_gap',
			array(
				'label'      => 'فاصله متن و اکشن',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 80 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ba-section-heading-widget' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'ref_vertical_alignment',
			array(
				'label'   => 'تراز عمودی',
				'type'    => Controls_Manager::CHOOSE,
				'options' => array(
					'flex-start' => array(
						'title' => 'بالا',
						'icon'  => 'eicon-v-align-top',
					),
					'center'     => array(
						'title' => 'وسط',
						'icon'  => 'eicon-v-align-middle',
					),
					'flex-end'   => array(
						'title' => 'پایین',
						'icon'  => 'eicon-v-align-bottom',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .ba-section-heading-widget:not(.ba-section-heading--stack)' => 'align-items: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'ref_text_align',
			array(
				'label'   => 'تراز متن',
				'type'    => Controls_Manager::CHOOSE,
				'options' => array(
					'right'  => array(
						'title' => 'راست',
						'icon'  => 'eicon-text-align-right',
					),
					'center' => array(
						'title' => 'وسط',
						'icon'  => 'eicon-text-align-center',
					),
					'left'   => array(
						'title' => 'چپ',
						'icon'  => 'eicon-text-align-left',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__copy' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'ref_copy_max_width',
			array(
				'label'      => 'حداکثر عرض بخش متن',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 160, 'max' => 1400 ),
					'%'  => array( 'min' => 10, 'max' => 100 ),
				),
				'selectors' => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__copy' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function register_eyebrow_style_controls() {
		$this->start_controls_section(
			'style_eyebrow',
			array(
				'label' => 'پیش‌عنوان',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_eyebrow_typography',
				'label'    => 'تایپوگرافی',
				'selector' => '{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__eyebrow',
			)
		);

		$this->add_control(
			'ref_eyebrow_color',
			array(
				'label'     => 'رنگ متن',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__eyebrow' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'ref_eyebrow_line_color',
			array(
				'label'     => 'رنگ خط',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__eyebrow::before' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'ref_eyebrow_line_width',
			array(
				'label'      => 'عرض خط',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__eyebrow::before' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'ref_eyebrow_line_height',
			array(
				'label'      => 'ضخامت خط',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 1, 'max' => 10 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__eyebrow::before' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'ref_eyebrow_gap',
			array(
				'label'      => 'فاصله خط و متن',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__eyebrow' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function register_title_style_controls() {
		$this->start_controls_section(
			'style_title',
			array(
				'label' => 'عنوان',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_title_typography',
				'label'    => 'تایپوگرافی',
				'selector' => '{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__title',
			)
		);

		$this->add_control(
			'ref_title_color',
			array(
				'label'     => 'رنگ',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'ref_title_margin',
			array(
				'label'      => 'فاصله عنوان',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function register_lead_style_controls() {
		$this->start_controls_section(
			'style_lead',
			array(
				'label' => 'توضیحات',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_lead_typography',
				'label'    => 'تایپوگرافی',
				'selector' => '{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__lead',
			)
		);

		$this->add_control(
			'ref_lead_color',
			array(
				'label'     => 'رنگ',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__lead' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'ref_lead_max_width',
			array(
				'label'      => 'حداکثر عرض توضیحات',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 120, 'max' => 1400 ),
					'%'  => array( 'min' => 10, 'max' => 100 ),
				),
				'selectors' => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__lead' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function register_action_style_controls() {
		$this->start_controls_section(
			'style_action',
			array(
				'label' => 'اکشن',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_action_typography',
				'label'    => 'تایپوگرافی',
				'selector' => '{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__action',
			)
		);

		$this->add_control(
			'ref_action_color',
			array(
				'label'     => 'رنگ',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__action' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'ref_action_hover_color',
			array(
				'label'     => 'رنگ Hover',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__action:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'ref_action_gap',
			array(
				'label'      => 'فاصله متن و آیکون',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__action' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'ref_action_icon_size',
			array(
				'label'      => 'اندازه آیکون',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 48 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .ba-section-heading-widget .ba-section-heading__action svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings    = $this->get_settings_for_display();
		$layout      = $this->sanitize_layout( $settings['layout'] ?? 'default' );
		$eyebrow     = trim( (string) ( $settings['eyebrow'] ?? '' ) );
		$title       = trim( (string) ( $settings['title'] ?? '' ) );
		$title_tag   = $this->sanitize_heading_tag( $settings['title_tag'] ?? 'h2' );
		$lead        = trim( (string) ( $settings['lead'] ?? '' ) );
		$action_text = trim( (string) ( $settings['action_text'] ?? '' ) );
		$action_link = is_array( $settings['action_link'] ?? null ) ? $settings['action_link'] : array();
		$has_action  = '' !== $action_text && ! empty( $action_link['url'] );

		if ( '' === $eyebrow && '' === $title && '' === $lead && ! $has_action ) {
			$this->render_editor_notice();
			return;
		}

		$classes = array( 'ba-section-heading', 'ba-section-heading-widget' );

		if ( 'stack' === $layout ) {
			$classes[] = 'ba-section-heading--stack';
		} elseif ( 'card' === $layout ) {
			$classes[] = 'ba-section-heading--stack';
			$classes[] = 'ba-section-heading--card';
		}

		$this->add_render_attribute( 'heading_root', 'class', $classes );

		if ( $has_action ) {
			$this->add_link_attributes( 'action_link', $action_link );
		}
		?>
		<div <?php echo $this->get_render_attribute_string( 'heading_root' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="ba-section-heading__copy">
				<?php if ( '' !== $eyebrow ) : ?>
					<span class="ba-section-heading__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
				<?php endif; ?>

				<?php if ( '' !== $title ) : ?>
					<<?php echo esc_html( $title_tag ); ?> class="ba-section-heading__title"><?php echo esc_html( $title ); ?></<?php echo esc_html( $title_tag ); ?>>
				<?php endif; ?>

				<?php if ( '' !== $lead ) : ?>
					<p class="ba-section-heading__lead"><?php echo esc_html( $lead ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( $has_action ) : ?>
				<a class="ba-section-heading__action" <?php echo $this->get_render_attribute_string( 'action_link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php echo esc_html( $action_text ); ?>
					<svg aria-hidden="true" fill="none" height="18" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18">
						<path d="M19 12H5m6-6-6 6 6 6"></path>
					</svg>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}

	private function sanitize_layout( $layout ) {
		$layout = (string) $layout;

		return in_array( $layout, array( 'default', 'stack', 'card' ), true ) ? $layout : 'default';
	}

	private function sanitize_heading_tag( $tag ) {
		$tag = strtolower( (string) $tag );

		return in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div' ), true ) ? $tag : 'h2';
	}

	private function render_editor_notice() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}

		echo '<div class="elementor-alert elementor-alert-info">برای نمایش عنوان بخش، حداقل یکی از فیلدهای محتوایی را تکمیل کنید.</div>';
	}
}
