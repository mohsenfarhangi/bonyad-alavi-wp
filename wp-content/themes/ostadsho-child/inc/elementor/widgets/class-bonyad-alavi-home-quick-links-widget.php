<?php
/**
 * ویجت دسترسی‌های سریع صفحه اصلی بنیاد علوی.
 *
 * Visual reference:
 * bonyad-alavi-redesign/redesign/index.html -> ba-quick-links
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

/**
 * دسترسی‌های سریع صفحه اصلی با رفتار overflow همان طرح مرجع.
 */
final class Bonyad_Alavi_Home_Quick_Links_Widget extends Widget_Base {

	public function get_name() {
		return 'bonyad_alavi_home_quick_links';
	}

	public function get_title() {
		return esc_html__( 'دسترسی‌های سریع صفحه اصلی', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-apps';
	}

	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	public function get_style_depends() {
		return array( 'bonyad-alavi-home-quick-links-widget' );
	}

	public function get_script_depends() {
		return array( 'bonyad-alavi-home-quick-links-widget' );
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_layout_style_controls();
		$this->register_label_style_controls();
		$this->register_media_style_controls();
		$this->register_more_style_controls();
	}

	/**
	 * آیتم‌های Quick Links را با Repeater قابل مدیریت می‌کند.
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'content_quick_links',
			array(
				'label' => 'دسترسی‌های سریع',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'label',
			array(
				'label'       => 'عنوان',
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'link',
			array(
				'label'       => 'لینک',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://',
				'options'     => array( 'url', 'is_external', 'nofollow' ),
			)
		);

		$repeater->add_control(
			'icon',
			array(
				'label'       => 'آیکون جایگزین',
				'type'        => Controls_Manager::ICONS,
				'description' => 'در صورت انتخاب آیکون، SVG پیش‌فرض طرح مرجع برای همین آیتم جایگزین می‌شود.',
			)
		);

		$repeater->add_control(
			'media_color',
			array(
				'label'   => 'رنگ دایره آیکون',
				'type'    => Controls_Manager::COLOR,
				'default' => '#5f6d66',
			)
		);

		$repeater->add_control(
			'default_icon_key',
			array(
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => 'آیتم‌ها',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label || "دسترسی سریع" }}}',
				'default'     => $this->get_reference_items(),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * کنترل‌های layout بدون default؛ تا زمان تغییر کاربر CSS مرجع دست‌نخورده می‌ماند.
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
			'ref_section_padding_top',
			array(
				'label'      => 'فاصله بالا',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-home-quick-links-widget' => 'padding-top: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_list_gap',
			array(
				'label'      => 'فاصله بین آیتم‌ها',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-quick-links__list' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_item_width',
			array(
				'label'      => 'عرض آیتم',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 60, 'max' => 200 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-quick-links__item' => 'width: {{SIZE}}{{UNIT}}; flex-basis: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_item_gap',
			array(
				'label'      => 'فاصله آیکون و عنوان',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-quick-links__item' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_item_padding',
			array(
				'label'      => 'فاصله داخلی آیتم',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .ba-quick-links__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_item_radius',
			array(
				'label'      => 'گردی آیتم',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-quick-links__item' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_item_hover_shift',
			array(
				'label'      => 'جابجایی عمودی در هاور',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => -20, 'max' => 20 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-quick-links__item:hover' => 'transform: translateY({{SIZE}}{{UNIT}});' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_label_style_controls() {
		$this->start_controls_section(
			'style_label',
			array(
				'label' => 'عنوان آیتم',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ref_label_typography',
				'label'    => 'تایپوگرافی',
				'selector' => '{{WRAPPER}} .ba-quick-links__label',
			)
		);

		$this->add_control(
			'ref_label_color',
			array(
				'label'     => 'رنگ متن',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-quick-links__label' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_media_style_controls() {
		$this->start_controls_section(
			'style_media',
			array(
				'label' => 'دایره و آیکون',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'ref_media_size',
			array(
				'label'      => 'اندازه دایره',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 30, 'max' => 120 ) ),
				'selectors'  => array( '{{WRAPPER}} .ba-quick-links__media' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_media_radius',
			array(
				'label'      => 'گردی دایره',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 100 ),
					'%'  => array( 'min' => 0, 'max' => 50 ),
				),
				'selectors'  => array( '{{WRAPPER}} .ba-quick-links__media' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ref_icon_size',
			array(
				'label'      => 'اندازه آیکون',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 10, 'max' => 60 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .ba-quick-links__icon'   => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} i.ba-quick-links__icon' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'ref_media_shadow',
				'selector' => '{{WRAPPER}} .ba-quick-links__media',
			)
		);

		$this->end_controls_section();
	}

	private function register_more_style_controls() {
		$this->start_controls_section(
			'style_more',
			array(
				'label' => 'دکمه بیشتر',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->start_controls_tabs( 'more_state_tabs' );

		$this->start_controls_tab(
			'more_state_normal',
			array( 'label' => 'عادی' )
		);
		$this->add_control(
			'ref_more_media_background',
			array(
				'label'     => 'رنگ دایره',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-quick-links__item--more .ba-quick-links__media' => 'background: {{VALUE}}; border-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_more_media_color',
			array(
				'label'     => 'رنگ علامت',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-quick-links__item--more .ba-quick-links__media' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab(
			'more_state_expanded',
			array( 'label' => 'بازشده' )
		);
		$this->add_control(
			'ref_more_expanded_background',
			array(
				'label'     => 'رنگ دایره',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-quick-links__list.is-expanded .ba-quick-links__item--more .ba-quick-links__media' => 'background: {{VALUE}}; border-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'ref_more_expanded_color',
			array(
				'label'     => 'رنگ علامت',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .ba-quick-links__list.is-expanded .ba-quick-links__item--more .ba-quick-links__media' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();
		$this->end_controls_section();
	}

	protected function render() {
		$items = array_values( array_filter( (array) ( $this->get_settings_for_display()['items'] ?? array() ), 'is_array' ) );

		if ( ! $items ) {
			$this->render_editor_notice();
			return;
		}
		?>
		<section aria-label="دسترسی‌های سریع" class="ba-quick-links ba-home-quick-links-widget" data-ba-home-quick-links>
			<div class="ba-container">
				<div class="ba-quick-links__inner" id="services">
					<div aria-label="دسترسی‌های سریع" class="ba-quick-links__list" data-js-quick-list>
						<?php foreach ( $items as $item ) : ?>
							<?php $this->render_item( $item ); ?>
						<?php endforeach; ?>
						<button aria-expanded="false" class="ba-quick-links__item ba-quick-links__item--more" type="button" data-js-quick-more>
							<span aria-hidden="true" class="ba-quick-links__media ba-quick-links__media--more">•••</span>
							<span class="ba-quick-links__label" data-js-quick-more-text>بیشتر</span>
						</button>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * یک آیتم Repeater را با markup طرح مرجع رندر می‌کند.
	 *
	 * @param array $item تنظیمات آیتم.
	 */
	private function render_item( array $item ) {
		$label            = trim( (string) ( $item['label'] ?? '' ) );
		$link             = is_array( $item['link'] ?? null ) ? $item['link'] : array();
		$icon             = is_array( $item['icon'] ?? null ) ? $item['icon'] : array();
		$default_icon_key = sanitize_key( (string) ( $item['default_icon_key'] ?? '' ) );
		$has_custom_icon  = ! empty( $icon['value'] );
		$has_link         = '' !== trim( (string) ( $link['url'] ?? '' ) );
		$tag              = $has_link ? 'a' : 'div';
		$attrs            = $has_link ? $this->build_link_attributes( $link ) : '';
		$theme_class      = isset( $this->get_reference_colors()[ $default_icon_key ] ) ? ' ba-quick-links__media--' . $default_icon_key : '';
		$custom_color     = $this->get_custom_media_color( $item, $default_icon_key );
		$style_attr       = $custom_color ? ' style="background-color:' . esc_attr( $custom_color ) . ';border-color:' . esc_attr( $custom_color ) . ';"' : '';
		?>
		<<?php echo esc_attr( $tag ); ?> class="ba-quick-links__item"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-js-quick-item>
			<span class="ba-quick-links__media<?php echo esc_attr( $theme_class ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php if ( $has_custom_icon ) : ?>
					<?php Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true', 'class' => 'ba-quick-links__icon' ) ); ?>
				<?php elseif ( '' !== $default_icon_key ) : ?>
					<?php echo $this->get_reference_svg( $default_icon_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
			</span>
			<?php if ( '' !== $label ) : ?><span class="ba-quick-links__label"><?php echo esc_html( $label ); ?></span><?php endif; ?>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	/**
	 * فقط وقتی رنگ Repeater با رنگ semantic مرجع تفاوت دارد inline override می‌سازد.
	 */
	private function get_custom_media_color( array $item, string $default_icon_key ): string {
		$color = sanitize_hex_color( (string) ( $item['media_color'] ?? '' ) );
		if ( ! $color ) {
			return '';
		}

		$reference_colors = $this->get_reference_colors();
		if ( isset( $reference_colors[ $default_icon_key ] ) && strtolower( $reference_colors[ $default_icon_key ] ) === strtolower( $color ) ) {
			return '';
		}

		return $color;
	}

	/**
	 * رنگ‌های semantic دقیق reference.
	 */
	private function get_reference_colors(): array {
		return array(
			'people'       => '#0f8a57',
			'organization' => '#2f6fa3',
			'auction'      => '#b7791f',
			'jihadi'       => '#b44c5e',
			'studies'      => '#6658a6',
			'habib'        => '#247a70',
			'award'        => '#b57b0d',
			'hamgam'       => '#3f7eaf',
			'camp'         => '#678a43',
		);
	}

	/**
	 * ۹ آیتم پیش‌فرض دقیقاً از index.html طرح مرجع گرفته شده‌اند.
	 */
	private function get_reference_items(): array {
		$colors = $this->get_reference_colors();

		return array(
			array(
				'label'            => 'مشارکت مردمی',
				'link'             => array( 'url' => 'https://bonyadalavi.ir/%d9%85%d8%b4%d8%a7%d8%b1%da%a9%d8%aa-%d9%85%d8%b1%d8%af%d9%85%db%8c/' ),
				'default_icon_key' => 'people',
				'media_color'      => $colors['people'],
			),
			array(
				'label'            => 'مشارکت سازمانی',
				'link'             => array( 'url' => 'https://bonyadalavi.ir/%d8%a8%d8%b1%d9%86%d8%a7%d9%85%d9%87-%d9%88-%d8%a8%d9%88%d8%af%d8%ac%d9%87/' ),
				'default_icon_key' => 'organization',
				'media_color'      => $colors['organization'],
			),
			array(
				'label'            => 'مزایده و مناقصه',
				'link'             => array( 'url' => 'https://bonyadalavi.ir/auctions/' ),
				'default_icon_key' => 'auction',
				'media_color'      => $colors['auction'],
			),
			array(
				'label'            => 'گروه های جهادی',
				'link'             => array( 'url' => 'https://bonyadalavi.ir/grassroots-and-jihadist-movements/' ),
				'default_icon_key' => 'jihadi',
				'media_color'      => $colors['jihadi'],
			),
			array(
				'label'            => 'مطالعات راهبردی',
				'link'             => array( 'url' => 'https://bonyadalavi.ir/%d9%85%d8%b7%d8%a7%d9%84%d8%b9%d8%a7%d8%aa-%d9%88-%d8%a2%d8%a8%d8%a7%d8%af%d8%a7%d9%86%db%8c/' ),
				'default_icon_key' => 'studies',
				'media_color'      => $colors['studies'],
			),
			array(
				'label'            => 'طرح حبیب',
				'link'             => array( 'url' => 'https://bonyadalavi.ir/%d8%b7%d8%b1%d8%ad-%d8%ad%d8%a8%db%8c%d8%a8/' ),
				'default_icon_key' => 'habib',
				'media_color'      => $colors['habib'],
			),
			array(
				'label'            => 'جایزه ملی علوی',
				'link'             => array( 'url' => 'https://bonyadalavi.ir/alaviprize/' ),
				'default_icon_key' => 'award',
				'media_color'      => $colors['award'],
			),
			array(
				'label'            => 'طرح همگام',
				'link'             => array( 'url' => '#' ),
				'default_icon_key' => 'hamgam',
				'media_color'      => $colors['hamgam'],
			),
			array(
				'label'            => 'مجتمع اردوگاهی',
				'link'             => array( 'url' => 'https://bonyadalavi.ir/emam/' ),
				'default_icon_key' => 'camp',
				'media_color'      => $colors['camp'],
			),
		);
	}

	/**
	 * SVGهای Tabler عین reference و بدون وابستگی خارجی.
	 */
	private function get_reference_svg( string $key ): string {
		$icons = array(
			'people'       => '<svg aria-hidden="true" class="ba-quick-links__icon" data-icon="tabler:users-group" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1"/><path d="M15 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M17 10h2a2 2 0 0 1 2 2v1"/><path d="M5 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M3 13v-1a2 2 0 0 1 2 -2h2"/></svg>',
			'organization' => '<svg aria-hidden="true" class="ba-quick-links__icon" data-icon="tabler:building-community" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 9l5 5v7h-5v-4m0 4h-5v-7l5 -5m1 1v-6a1 1 0 0 1 1 -1h10a1 1 0 0 1 1 1v17h-8"/><path d="M13 7l0 .01"/><path d="M17 7l0 .01"/><path d="M17 11l0 .01"/><path d="M17 15l0 .01"/></svg>',
			'auction'      => '<svg aria-hidden="true" class="ba-quick-links__icon" data-icon="tabler:gavel" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10l7.383 7.418c.823 .82 .823 2.148 0 2.967a2.11 2.11 0 0 1 -2.976 0l-7.407 -7.385"/><path d="M6 9l4 4"/><path d="M13 10l-4 -4"/><path d="M3 21h7"/><path d="M6.793 15.793l-3.586 -3.586a1 1 0 0 1 0 -1.414l2.293 -2.293l.5 .5l3 -3l-.5 -.5l2.293 -2.293a1 1 0 0 1 1.414 0l3.586 3.586a1 1 0 0 1 0 1.414l-2.293 2.293l-.5 -.5l-3 3l.5 .5l-2.293 2.293a1 1 0 0 1 -1.414 0"/></svg>',
			'jihadi'       => '<svg aria-hidden="true" class="ba-quick-links__icon" data-icon="tabler:heart-handshake" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572"/><path d="M12 6l-3.293 3.293a1 1 0 0 0 0 1.414l.543 .543c.69 .69 1.81 .69 2.5 0l1 -1a3.182 3.182 0 0 1 4.5 0l2.25 2.25"/><path d="M12.5 15.5l2 2"/><path d="M15 13l2 2"/></svg>',
			'studies'      => '<svg aria-hidden="true" class="ba-quick-links__icon" data-icon="tabler:report-analytics" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"/><path d="M9 5a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2"/><path d="M9 17v-5"/><path d="M12 17v-1"/><path d="M15 17v-3"/></svg>',
			'habib'        => '<svg aria-hidden="true" class="ba-quick-links__icon" data-icon="tabler:mosque" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 5.49a1.764 1.764 0 0 1 -2.5 -2.49"/><path d="M12 6v3"/><path d="M19 21a8.9 8.9 0 0 0 1 -3.67c0 -2 -.92 -3.25 -3.24 -4.51a17.4 17.4 0 0 1 -4.76 -3.82a17.4 17.4 0 0 1 -4.76 3.82c-2.32 1.26 -3.24 2.55 -3.24 4.51a8.9 8.9 0 0 0 1 3.67h14"/></svg>',
			'award'        => '<svg aria-hidden="true" class="ba-quick-links__icon" data-icon="tabler:award" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9a6 6 0 1 0 12 0a6 6 0 1 0 -12 0"/><path d="M12 15l3.4 5.89l1.598 -3.233l3.598 .232l-3.4 -5.889"/><path d="M6.802 12l-3.4 5.89l3.598 -.233l1.598 3.232l3.4 -5.889"/></svg>',
			'hamgam'       => '<svg aria-hidden="true" class="ba-quick-links__icon" data-icon="tabler:route" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 19a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M19 7a2 2 0 1 0 0 -4a2 2 0 0 0 0 4"/><path d="M11 19h5.5a3.5 3.5 0 0 0 0 -7h-8a3.5 3.5 0 0 1 0 -7h4.5"/></svg>',
			'camp'         => '<svg aria-hidden="true" class="ba-quick-links__icon" data-icon="tabler:tent" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 14l4 6h6l-9 -16l-9 16h6l4 -6"/></svg>',
		);

		return $icons[ $key ] ?? '';
	}

	/**
	 * ویژگی‌های امن URL کنترل Elementor را می‌سازد.
	 */
	private function build_link_attributes( array $link ): string {
		$url = trim( (string) ( $link['url'] ?? '' ) );
		if ( '' === $url ) {
			return '';
		}

		$attrs = ' href="' . esc_url( $url ) . '"';
		$rel   = array();

		if ( ! empty( $link['is_external'] ) ) {
			$attrs .= ' target="_blank"';
			$rel[]  = 'noopener';
		}

		if ( ! empty( $link['nofollow'] ) ) {
			$rel[] = 'nofollow';
		}

		if ( $rel ) {
			$attrs .= ' rel="' . esc_attr( implode( ' ', array_unique( $rel ) ) ) . '"';
		}

		return $attrs;
	}

	private function render_editor_notice() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}

		echo '<div class="elementor-alert elementor-alert-info">برای نمایش دسترسی‌های سریع حداقل یک آیتم اضافه کنید.</div>';
	}
}
