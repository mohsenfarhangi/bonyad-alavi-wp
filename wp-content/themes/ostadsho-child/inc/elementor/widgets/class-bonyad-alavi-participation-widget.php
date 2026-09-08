<?php
/**
 * ویجت اختصاصی صفحه مشارکت مردمی بنیاد علوی.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

/**
 * ویجت صفحه مشارکت مردمی با اطلاعات مالی، فرم مشارکت و رسانه‌های محصول ووکامرس.
 */
class Bonyad_Alavi_Participation_Widget extends Widget_Base {

	public function get_name() {
		return 'bonyad_alavi_participation';
	}

	public function get_title() {
		return esc_html__( 'صفحه مشارکت مردمی', 'bonyad-alavi-child' );
	}

	public function get_icon() {
		return 'eicon-heart-o';
	}

	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	public function get_keywords() {
		return array( 'مشارکت', 'همیاری', 'پروژه', 'بنیاد علوی', 'donation', 'crowdfunding' );
	}

	public function get_style_depends() {
		return array( 'bonyad-alavi-participation-widget' );
	}

	public function get_script_depends() {
		return array( 'elementor-frontend', 'bonyad-alavi-participation-widget' );
	}

	protected function register_controls() {
		$this->register_project_controls();
		$this->register_about_controls();
		$this->register_funding_controls();
		$this->register_donation_controls();
		$this->register_noncash_controls();
		$this->register_share_controls();
		$this->register_faq_controls();
		$this->register_layout_style_controls();
		$this->register_color_style_controls();
		$this->register_card_style_controls();
		$this->register_typography_style_controls();
		$this->register_progress_style_controls();
		$this->register_button_style_controls();
		$this->register_mobile_style_controls();
	}

	/**
	 * کنترل‌های اطلاعات اصلی پروژه و رفتار گالری رسانه را ثبت می‌کند.
	 */
	private function register_project_controls() {
		$this->start_controls_section(
			'project_content',
			array(
				'label' => esc_html__( 'اطلاعات اصلی پروژه', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'project_subtitle',
			array(
				'label'       => esc_html__( 'متن کوتاه زیر عنوان', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => esc_html__( 'با مشارکت شما، مدرسه‌ای ایمن برای دانش‌آموزان روستای ایل‌آباد ساخته می‌شود و با تکمیل سهم مردمی، باقی هزینه را بنیاد علوی تأمین می‌کند.', 'bonyad-alavi-child' ),
				'dynamic'     => array( 'active' => true ),
			)
		);


		$this->add_control(
			'project_category',
			array(
				'label'       => esc_html__( 'دسته‌بندی', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'آموزش و پرورش', 'bonyad-alavi-child' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'project_location',
			array(
				'label'       => esc_html__( 'موقعیت پروژه', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'استان فارس، شهرستان ممسنی', 'bonyad-alavi-child' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'show_image_badges',
			array(
				'label'        => esc_html__( 'نمایش برچسب روی تصویر', 'bonyad-alavi-child' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'بله', 'bonyad-alavi-child' ),
				'label_off'    => esc_html__( 'خیر', 'bonyad-alavi-child' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'kicker_text',
			array(
				'label'       => esc_html__( 'برچسب بالای عنوان', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'فرصت مشارکت مردمی', 'bonyad-alavi-child' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'show_breadcrumb',
			array(
				'label'        => esc_html__( 'نمایش مسیر صفحه', 'bonyad-alavi-child' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'home_label',
			array(
				'label'     => esc_html__( 'عنوان صفحه نخست', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'صفحه نخست', 'bonyad-alavi-child' ),
				'condition' => array( 'show_breadcrumb' => 'yes' ),
			)
		);

		$this->add_control(
			'enable_lightbox',
			array(
				'label'        => esc_html__( 'نمایش بزرگ تصویر', 'bonyad-alavi-child' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	private function register_about_controls() {
		$this->start_controls_section(
			'about_content',
			array(
				'label' => esc_html__( 'درباره و اثر پروژه', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_about',
			array(
				'label'        => esc_html__( 'نمایش بخش درباره پروژه', 'bonyad-alavi-child' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'about_eyebrow',
			array(
				'label'     => esc_html__( 'روتیتر', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'درباره پروژه', 'bonyad-alavi-child' ),
				'condition' => array( 'show_about' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_funding_controls() {
		$this->start_controls_section(
			'funding_content',
			array(
				'label' => esc_html__( 'اطلاعات مالی', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'collected_amount',
			array(
				'label'              => esc_html__( 'مبلغ جمع‌آوری‌شده (تومان)', 'bonyad-alavi-child' ),
				'type'               => Controls_Manager::NUMBER,
				'default'            => 0,
				'min'                => 0,
				'step'               => 1000,
				'frontend_available' => true,
				'dynamic'            => array( 'active' => true ),
			)
		);

		$this->add_control(
			'public_target',
			array(
				'label'              => esc_html__( 'هدف مشارکت مردمی (تومان)', 'bonyad-alavi-child' ),
				'type'               => Controls_Manager::NUMBER,
				'default'            => 1000000000,
				'min'                => 0,
				'step'               => 1000,
				'frontend_available' => true,
				'dynamic'            => array( 'active' => true ),
			)
		);

		$this->add_control(
			'total_budget',
			array(
				'label'              => esc_html__( 'بودجه کل پروژه (تومان)', 'bonyad-alavi-child' ),
				'type'               => Controls_Manager::NUMBER,
				'default'            => 5000000000,
				'min'                => 0,
				'step'               => 1000,
				'frontend_available' => true,
				'dynamic'            => array( 'active' => true ),
			)
		);

		$this->add_control(
			'minimum_amount',
			array(
				'label'              => esc_html__( 'حداقل مشارکت (تومان)', 'bonyad-alavi-child' ),
				'type'               => Controls_Manager::NUMBER,
				'default'            => 200000,
				'min'                => 0,
				'step'               => 1000,
				'frontend_available' => true,
				'dynamic'            => array( 'active' => true ),
			)
		);

		$this->add_control(
			'currency_label',
			array(
				'label'   => esc_html__( 'واحد پول', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'تومان', 'bonyad-alavi-child' ),
			)
		);

		$this->add_control(
			'progress_label',
			array(
				'label'   => esc_html__( 'عنوان نوار پیشرفت', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'پیشرفت تأمین مالی', 'bonyad-alavi-child' ),
			)
		);

		$this->add_control(
			'collected_label',
			array(
				'label'   => esc_html__( 'عنوان مبلغ جمع‌شده', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'مبلغ جمع‌آوری‌شده', 'bonyad-alavi-child' ),
			)
		);

		$this->add_control(
			'target_label',
			array(
				'label'   => esc_html__( 'عنوان هدف مردمی', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'هدف مشارکت مردمی', 'bonyad-alavi-child' ),
			)
		);

        $this->add_control(
                'match_badge_notice',
                array(
                        'type'            => Controls_Manager::RAW_HTML,
                        'raw'             => esc_html__(
                                'نشان هم‌افزایی به‌صورت خودکار از فرمول «(بودجه کل پروژه − هدف مشارکت مردمی) ÷ هدف مشارکت مردمی» محاسبه می‌شود.',
                                'bonyad-alavi-child'
                        ),
                        'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                )
        );

		$this->add_control(
			'match_description',
			array(
				'label'   => esc_html__( 'توضیح پیام هم‌افزایی', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'با تکمیل سهم مردمی، بنیاد علوی باقی بودجه موردنیاز اجرای پروژه را تأمین می‌کند.', 'bonyad-alavi-child' ),
			)
		);

		$this->add_control(
			'show_funding_section',
			array(
				'label'        => esc_html__( 'نمایش بخش شفافیت مالی', 'bonyad-alavi-child' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'funding_eyebrow',
			array(
				'label'     => esc_html__( 'روتیتر شفافیت مالی', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'شفافیت تأمین مالی', 'bonyad-alavi-child' ),
				'condition' => array( 'show_funding_section' => 'yes' ),
			)
		);

		$this->add_control(
			'funding_title',
			array(
				'label'       => esc_html__( 'عنوان شفافیت مالی', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'سهم مردم، محرک تکمیل پروژه', 'bonyad-alavi-child' ),
				'label_block' => true,
				'condition'   => array( 'show_funding_section' => 'yes' ),
			)
		);

		$this->add_control(
			'funding_description',
			array(
				'label'     => esc_html__( 'توضیح شفافیت مالی', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::WYSIWYG,
                'default'     => 'با تأمین {public_target} تومان توسط مردم، بنیاد علوی {foundation_share} تومان دیگر به پروژه اضافه می‌کند.',
				'condition' => array( 'show_funding_section' => 'yes' ),
                'description' => wp_kses_post(
                        'تگ‌های قابل استفاده:<br>
                                <code>{collected}</code> مبلغ جمع‌آوری‌شده<br>
                                <code>{public_target}</code> هدف مشارکت مردمی<br>
                                <code>{remaining}</code> مبلغ باقی‌مانده از هدف مردمی<br>
                                <code>{foundation_share}</code> سهم بنیاد علوی<br>
                                <code>{total_budget}</code> بودجه کل پروژه<br>
                                <code>{progress_percent}</code> درصد پیشرفت تأمین مالی<br>
                                <code>{match_badge}</code> ضریب هم‌افزایی مانند ×۴<br>
                                <code>{match_words}</code> ضریب هم‌افزایی به حروف مانند چهار'
                ),
			)
		);

		$this->add_control(
			'public_share_label',
			array(
				'label'     => esc_html__( 'عنوان سهم مردم', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'هدف مشارکت مردمی', 'bonyad-alavi-child' ),
				'condition' => array( 'show_funding_section' => 'yes' ),
			)
		);

		$this->add_control(
			'foundation_share_label',
			array(
				'label'     => esc_html__( 'عنوان سهم بنیاد', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'تعهد بنیاد علوی', 'bonyad-alavi-child' ),
				'condition' => array( 'show_funding_section' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_donation_controls() {
		$this->start_controls_section(
			'donation_content',
			array(
				'label' => esc_html__( 'فرم مشارکت', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'verified_title',
			array(
				'label'       => esc_html__( 'عنوان اعتبارسنجی', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'پروژه ارزیابی‌شده بنیاد علوی', 'bonyad-alavi-child' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'verified_description',
			array(
				'label'   => esc_html__( 'توضیح اعتبارسنجی', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'تأمین مالی و اجرای پروژه تحت نظارت بنیاد', 'bonyad-alavi-child' ),
			)
		);

		$preset_repeater = new Repeater();
		$preset_repeater->add_control(
			'label',
			array(
				'label'   => esc_html__( 'برچسب مبلغ', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( '۲۰۰ هزار', 'bonyad-alavi-child' ),
			)
		);
		$preset_repeater->add_control(
			'amount',
			array(
				'label'   => esc_html__( 'مبلغ عددی', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 200000,
				'min'     => 0,
				'step'    => 1000,
			)
		);

		$this->add_control(
			'preset_amounts',
			array(
				'label'       => esc_html__( 'مبالغ پیشنهادی', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $preset_repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => array(
					array( 'label' => esc_html__( '۲۰۰ هزار', 'bonyad-alavi-child' ), 'amount' => 200000 ),
					array( 'label' => esc_html__( '۵۰۰ هزار', 'bonyad-alavi-child' ), 'amount' => 500000 ),
					array( 'label' => esc_html__( '۱ میلیون', 'bonyad-alavi-child' ), 'amount' => 1000000 ),
					array( 'label' => esc_html__( '۲ میلیون', 'bonyad-alavi-child' ), 'amount' => 2000000 ),
				),
			)
		);

		$this->add_control(
			'preset_legend',
			array(
				'label'   => esc_html__( 'عنوان مبالغ پیشنهادی', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'مبلغ مشارکت خود را انتخاب کنید', 'bonyad-alavi-child' ),
			)
		);

		$this->add_control(
			'custom_amount_label',
			array(
				'label'   => esc_html__( 'عنوان مبلغ دلخواه', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'یا مبلغ دلخواه را وارد کنید', 'bonyad-alavi-child' ),
			)
		);

		$this->add_control(
			'amount_placeholder',
			array(
				'label'   => esc_html__( 'متن نمونه ورودی', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'مثلاً ۵۰۰٬۰۰۰', 'bonyad-alavi-child' ),
			)
		);

		$this->add_control(
			'submit_text',
			array(
				'label'       => esc_html__( 'متن دکمه', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'افزودن به سبد همیاری', 'bonyad-alavi-child' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'form_mode',
			array(
				'label'       => esc_html__( 'رفتار ارسال فرم', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'event',
				'options'     => array(
					'event' => esc_html__( 'رویداد JavaScript (برای اتصال سفارشی)', 'bonyad-alavi-child' ),
					'post'  => esc_html__( 'ارسال فرم به آدرس مشخص', 'bonyad-alavi-child' ),
				),
				'description' => esc_html__( 'در حالت رویداد، bonyad-alavi:donation-submit منتشر می‌شود و صفحه تغییر نمی‌کند.', 'bonyad-alavi-child' ),
			)
		);

		$this->add_control(
			'form_action',
			array(
				'label'       => esc_html__( 'آدرس ارسال فرم', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => home_url( '/' ),
				'condition'   => array( 'form_mode' => 'post' ),
			)
		);

		$this->add_control(
			'form_method',
			array(
				'label'     => esc_html__( 'متد فرم', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'post',
				'options'   => array( 'post' => 'POST', 'get' => 'GET' ),
				'condition' => array( 'form_mode' => 'post' ),
			)
		);

		$this->add_control(
			'success_message',
			array(
				'label'   => esc_html__( 'پیام موفقیت حالت رویداد', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'مبلغ انتخاب‌شده به سبد همیاری افزوده شد.', 'bonyad-alavi-child' ),
			)
		);

		$trust_repeater = new Repeater();
		$trust_repeater->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'آیکون', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::ICONS,
				'default' => array( 'value' => 'fas fa-shield-alt', 'library' => 'fa-solid' ),
			)
		);
		$trust_repeater->add_control(
			'text',
			array(
				'label'   => esc_html__( 'متن', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'پرداخت امن', 'bonyad-alavi-child' ),
			)
		);

		$this->add_control(
			'trust_items',
			array(
				'label'       => esc_html__( 'مزیت‌های مشارکت', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $trust_repeater->get_controls(),
				'title_field' => '{{{ text }}}',
				'default'     => array(
					array( 'icon' => array( 'value' => 'fas fa-shield-alt', 'library' => 'fa-solid' ), 'text' => esc_html__( 'پرداخت امن', 'bonyad-alavi-child' ) ),
					array( 'icon' => array( 'value' => 'fas fa-receipt', 'library' => 'fa-solid' ), 'text' => esc_html__( 'رسید مشارکت', 'bonyad-alavi-child' ) ),
					array( 'icon' => array( 'value' => 'fas fa-chart-line', 'library' => 'fa-solid' ), 'text' => esc_html__( 'گزارش پیشرفت', 'bonyad-alavi-child' ) ),
				),
			)
		);

		$this->add_control(
			'show_mobile_dock',
			array(
				'label'        => esc_html__( 'نوار ثابت مشارکت در موبایل', 'bonyad-alavi-child' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'mobile_dock_label',
			array(
				'label'     => esc_html__( 'عنوان مبلغ در نوار موبایل', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'مبلغ انتخابی', 'bonyad-alavi-child' ),
				'condition' => array( 'show_mobile_dock' => 'yes' ),
			)
		);

		$this->add_control(
			'mobile_dock_button',
			array(
				'label'     => esc_html__( 'متن دکمه نوار موبایل', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'مشارکت در پروژه', 'bonyad-alavi-child' ),
				'condition' => array( 'show_mobile_dock' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_noncash_controls() {
		$this->start_controls_section(
			'noncash_content',
			array(
				'label' => esc_html__( 'مشارکت غیرنقدی', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_noncash',
			array(
				'label'        => esc_html__( 'نمایش بخش', 'bonyad-alavi-child' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'noncash_eyebrow',
			array(
				'label'     => esc_html__( 'روتیتر', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'مشارکت غیرنقدی', 'bonyad-alavi-child' ),
				'condition' => array( 'show_noncash' => 'yes' ),
			)
		);

		$this->add_control(
			'noncash_title',
			array(
				'label'       => esc_html__( 'عنوان بخش', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'تخصص یا تجهیزات خود را به پروژه پیوند بزنید', 'bonyad-alavi-child' ),
				'label_block' => true,
				'condition'   => array( 'show_noncash' => 'yes' ),
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'آیکون', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::ICONS,
				'default' => array( 'value' => 'fas fa-users', 'library' => 'fa-solid' ),
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'       => esc_html__( 'عنوان', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'همکاری تخصصی', 'bonyad-alavi-child' ),
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'description',
			array(
				'label'   => esc_html__( 'توضیح', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'مهارت، دانش تخصصی یا همراهی داوطلبانه', 'bonyad-alavi-child' ),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'       => esc_html__( 'لینک فرم یا پاپ‌آپ', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => '#',
			)
		);

		$this->add_control(
			'noncash_items',
			array(
				'label'       => esc_html__( 'گزینه‌ها', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'condition'   => array( 'show_noncash' => 'yes' ),
				'default'     => array(
					array(
						'icon'        => array( 'value' => 'fas fa-users', 'library' => 'fa-solid' ),
						'title'       => esc_html__( 'همکاری به‌عنوان نیروی انسانی', 'bonyad-alavi-child' ),
						'description' => esc_html__( 'مهارت، دانش تخصصی یا همراهی داوطلبانه', 'bonyad-alavi-child' ),
						'link'        => array( 'url' => '#' ),
					),
					array(
						'icon'        => array( 'value' => 'fas fa-tools', 'library' => 'fa-solid' ),
						'title'       => esc_html__( 'تأمین ماشین‌آلات و تجهیزات', 'bonyad-alavi-child' ),
						'description' => esc_html__( 'تجهیزات عمرانی، آموزشی یا خدمات تخصصی', 'bonyad-alavi-child' ),
						'link'        => array( 'url' => '#' ),
					),
				),
			)
		);

		$this->end_controls_section();
	}


	private function register_share_controls() {
		$this->start_controls_section(
			'share_content',
			array(
				'label' => esc_html__( 'اشتراک‌گذاری پروژه', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_share',
			array(
				'label'        => esc_html__( 'نمایش بخش', 'bonyad-alavi-child' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'share_title',
			array(
				'label'       => esc_html__( 'عنوان باکس', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'این فرصت مشارکت را با دیگران به اشتراک بگذارید', 'bonyad-alavi-child' ),
				'label_block' => true,
				'condition'   => array( 'show_share' => 'yes' ),
			)
		);

		$this->add_control(
			'share_description',
			array(
				'label'     => esc_html__( 'توضیح باکس', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::TEXTAREA,
				'default'   => esc_html__( 'با معرفی این پروژه، افراد بیشتری می‌توانند در تکمیل آن سهیم شوند.', 'bonyad-alavi-child' ),
				'condition' => array( 'show_share' => 'yes' ),
			)
		);

		$this->add_control(
			'share_button_text',
			array(
				'label'     => esc_html__( 'متن دکمه اشتراک‌گذاری', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'اشتراک‌گذاری', 'bonyad-alavi-child' ),
				'condition' => array( 'show_share' => 'yes' ),
			)
		);

		$this->add_control(
			'copy_link_text',
			array(
				'label'     => esc_html__( 'متن دکمه کپی لینک', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'کپی لینک', 'bonyad-alavi-child' ),
				'condition' => array( 'show_share' => 'yes' ),
			)
		);

		$this->add_control(
			'copy_success_message',
			array(
				'label'     => esc_html__( 'پیام کپی موفق', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'لینک پروژه کپی شد.', 'bonyad-alavi-child' ),
				'condition' => array( 'show_share' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_faq_controls() {
		$this->start_controls_section(
			'faq_content',
			array(
				'label' => esc_html__( 'پرسش‌های متداول', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_faq',
			array(
				'label'        => esc_html__( 'نمایش بخش', 'bonyad-alavi-child' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'faq_eyebrow',
			array(
				'label'     => esc_html__( 'روتیتر', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'پرسش‌های متداول', 'bonyad-alavi-child' ),
				'condition' => array( 'show_faq' => 'yes' ),
			)
		);

		$this->add_control(
			'faq_title',
			array(
				'label'       => esc_html__( 'عنوان بخش', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'پیش از مشارکت بدانید', 'bonyad-alavi-child' ),
				'label_block' => true,
				'condition'   => array( 'show_faq' => 'yes' ),
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'question',
			array(
				'label'       => esc_html__( 'سؤال', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'سؤال متداول', 'bonyad-alavi-child' ),
				'label_block' => true,
			)
		);
		$repeater->add_control(
			'answer',
			array(
				'label'   => esc_html__( 'پاسخ', 'bonyad-alavi-child' ),
				'type'    => Controls_Manager::WYSIWYG,
				'default' => esc_html__( 'پاسخ این سؤال را در این بخش وارد کنید.', 'bonyad-alavi-child' ),
			)
		);

        $this->add_control(
                'product_faq_notice',
                array(
                        'type'            => Controls_Manager::RAW_HTML,
                        'raw'             => esc_html__(
                                'سؤال‌های اختصاصی از متاباکس محصول ووکامرس خوانده می‌شوند و پیش از سؤال‌های ثابت زیر نمایش داده خواهند شد.',
                                'bonyad-alavi-child'
                        ),
                        'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                        'condition'       => array(
                                'show_faq' => 'yes',
                        ),
                )
        );

		$this->add_control(
			'faq_items',
			array(
				'label'       => esc_html__( 'سؤال‌های ثابت مشترک همه پروژه‌ها', 'bonyad-alavi-child' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ question }}}',
				'condition'   => array( 'show_faq' => 'yes' ),
				'default'     => array(
					array(
						'question' => esc_html__( 'حداقل مبلغ مشارکت چقدر است؟', 'bonyad-alavi-child' ),
						'answer'   => esc_html__( 'حداقل مبلغ مشارکت از بخش اطلاعات مالی همین ویجت قابل تنظیم است.', 'bonyad-alavi-child' ),
					),
					array(
						'question' => esc_html__( 'گزارش پیشرفت پروژه چگونه منتشر می‌شود؟', 'bonyad-alavi-child' ),
						'answer'   => esc_html__( 'گزارش‌های اجرایی و مالی می‌تواند در همین صفحه یا بخش گزارش عملکرد بنیاد منتشر شود.', 'bonyad-alavi-child' ),
					),
					array(
						'question' => esc_html__( 'برای همکاری غیرنقدی چه اقدامی لازم است؟', 'bonyad-alavi-child' ),
						'answer'   => esc_html__( 'یکی از گزینه‌های همکاری را انتخاب کنید تا فرم مربوط به آن باز شود.', 'bonyad-alavi-child' ),
					),
				),
			)
		);

		$this->end_controls_section();
	}

	private function register_layout_style_controls() {
		$this->start_controls_section(
			'layout_style',
			array(
				'label' => esc_html__( 'چیدمان و فاصله‌ها', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'container_width',
			array(
				'label'      => esc_html__( 'حداکثر عرض محتوا', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vw' ),
				'range'      => array(
					'px' => array( 'min' => 760, 'max' => 1600, 'step' => 10 ),
					'vw' => array( 'min' => 70, 'max' => 100, 'step' => 1 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 1180 ),
				'selectors'  => array( '{{WRAPPER}} .bap__shell' => 'max-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'sidebar_width',
			array(
				'label'      => esc_html__( 'عرض ستون فرم', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 300, 'max' => 520, 'step' => 5 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 390 ),
				'selectors'  => array( '{{WRAPPER}} .bap' => '--bap-sidebar-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'column_gap',
			array(
				'label'      => esc_html__( 'فاصله دو ستون', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 20 ),
				'selectors'  => array( '{{WRAPPER}} .bap__hero' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'section_gap',
			array(
				'label'      => esc_html__( 'فاصله کارت‌های محتوا', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 18 ),
				'selectors'  => array( '{{WRAPPER}} .bap__content-column' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'outer_padding',
			array(
				'label'      => esc_html__( 'فاصله داخلی بدنه ویجت', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array( 'top' => 22, 'right' => 16, 'bottom' => 80, 'left' => 16, 'unit' => 'px', 'isLinked' => false ),
				'selectors'  => array( '{{WRAPPER}} .bap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'sticky_offset',
			array(
				'label'      => esc_html__( 'فاصله پنل ثابت از بالای صفحه', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 250 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 94 ),
				'selectors'  => array( '{{WRAPPER}} .bap__donation-column' => '--bap-sticky-top-offset: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'image_ratio',
			array(
				'label'     => esc_html__( 'نسبت تصویر', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '16 / 9',
				'options'   => array(
					'16 / 9' => '16:9',
					'3 / 2'  => '3:2',
					'4 / 3'  => '4:3',
					'1 / 1'  => '1:1',
				),
				'selectors' => array( '{{WRAPPER}} .bap__main-image' => 'aspect-ratio: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_color_style_controls() {
		$this->start_controls_section(
			'color_style',
			array(
				'label' => esc_html__( 'رنگ‌ها', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$colors = array(
			'primary_color'      => array( 'رنگ اصلی', '#079447', '--bap-green' ),
			'primary_dark_color' => array( 'رنگ اصلی تیره', '#056b35', '--bap-green-dark' ),
			'accent_color'       => array( 'رنگ تأکیدی', '#f1b63b', '--bap-gold' ),
			'heading_color'      => array( 'رنگ عنوان‌ها', '#173329', '--bap-ink' ),
			'text_color'         => array( 'رنگ متن', '#3f5148', '--bap-text' ),
			'muted_color'        => array( 'رنگ متن فرعی', '#74837b', '--bap-muted' ),
			'page_bg_color'      => array( 'پس‌زمینه بدنه', '#f6f9f7', '--bap-bg' ),
			'card_bg_color'      => array( 'پس‌زمینه کارت', '#ffffff', '--bap-white' ),
			'border_color'       => array( 'رنگ خط و کادر', '#dce9e1', '--bap-line' ),
		);

		foreach ( $colors as $control_id => $config ) {
			$this->add_control(
				$control_id,
				array(
					'label'     => esc_html__( $config[0], 'bonyad-alavi-child' ),
					'type'      => Controls_Manager::COLOR,
					'default'   => $config[1],
					'selectors' => array( '{{WRAPPER}} .bap' => $config[2] . ': {{VALUE}};' ),
				)
			);
		}

		$this->end_controls_section();
	}

	private function register_card_style_controls() {
		$this->start_controls_section(
			'card_style',
			array(
				'label' => esc_html__( 'کارت‌ها و تصویر', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'فاصله داخلی کارت‌ها', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'default'    => array( 'top' => 24, 'right' => 24, 'bottom' => 24, 'left' => 24, 'unit' => 'px', 'isLinked' => true ),
				'selectors'  => array(
					'{{WRAPPER}} .bap__story-card, {{WRAPPER}} .bap__funding-card, {{WRAPPER}} .bap__noncash-card, {{WRAPPER}} .bap__share-card, {{WRAPPER}} .bap__faq-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				)
			)
		);

		$this->add_responsive_control(
			'donation_padding',
			array(
				'label'      => esc_html__( 'فاصله داخلی پنل مشارکت', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'default'    => array( 'top' => 22, 'right' => 22, 'bottom' => 22, 'left' => 22, 'unit' => 'px', 'isLinked' => true ),
				'selectors'  => array( '{{WRAPPER}} .bap__donation-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'card_radius',
			array(
				'label'      => esc_html__( 'گردی گوشه کارت‌ها', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 20 ),
				'selectors'  => array(
					'{{WRAPPER}} .bap__media-card, {{WRAPPER}} .bap__story-card, {{WRAPPER}} .bap__funding-card, {{WRAPPER}} .bap__noncash-card, {{WRAPPER}} .bap__share-card, {{WRAPPER}} .bap__faq-card, {{WRAPPER}} .bap__donation-card' => 'border-radius: {{SIZE}}{{UNIT}};',
				)
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .bap__media-card, {{WRAPPER}} .bap__story-card, {{WRAPPER}} .bap__funding-card, {{WRAPPER}} .bap__noncash-card, {{WRAPPER}} .bap__share-card, {{WRAPPER}} .bap__faq-card, {{WRAPPER}} .bap__donation-card',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .bap__media-card, {{WRAPPER}} .bap__story-card, {{WRAPPER}} .bap__funding-card, {{WRAPPER}} .bap__noncash-card, {{WRAPPER}} .bap__share-card, {{WRAPPER}} .bap__faq-card',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'donation_shadow',
				'selector' => '{{WRAPPER}} .bap__donation-card',
			)
		);

		$this->add_responsive_control(
			'image_radius',
			array(
				'label'      => esc_html__( 'گردی تصویر', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 14 ),
				'selectors'  => array( '{{WRAPPER}} .bap__main-image' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_typography_style_controls() {
		$this->start_controls_section(
			'typography_style',
			array(
				'label' => esc_html__( 'تایپوگرافی', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'عنوان پروژه', 'bonyad-alavi-child' ),
				'selector' => '{{WRAPPER}} .bap__desktop-heading h1, {{WRAPPER}} .bap__mobile-heading h1',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'subtitle_typography',
				'label'    => esc_html__( 'متن زیر عنوان', 'bonyad-alavi-child' ),
				'selector' => '{{WRAPPER}} .bap__desktop-heading p, {{WRAPPER}} .bap__mobile-heading p',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'section_title_typography',
				'label'    => esc_html__( 'عنوان بخش‌ها', 'bonyad-alavi-child' ),
				'selector' => '{{WRAPPER}} .bap__section-heading h2',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'body_typography',
				'label'    => esc_html__( 'متن بدنه', 'bonyad-alavi-child' ),
				'selector' => '{{WRAPPER}} .bap__story-text, {{WRAPPER}} .bap__funding-description, {{WRAPPER}} .bap__accordion, {{WRAPPER}} .bap__noncash-option, {{WRAPPER}} .bap__share-card',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'amount_typography',
				'label'    => esc_html__( 'مبالغ و درصد', 'bonyad-alavi-child' ),
				'selector' => '{{WRAPPER}} .bap__amount-summary strong, {{WRAPPER}} .bap__progress-head strong, {{WRAPPER}} .bap__funding-legend strong',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'label'    => esc_html__( 'دکمه مشارکت', 'bonyad-alavi-child' ),
				'selector' => '{{WRAPPER}} .bap__submit, {{WRAPPER}} .bap__mobile-dock button',
			)
		);

		$this->end_controls_section();
	}

	private function register_progress_style_controls() {
		$this->start_controls_section(
			'progress_style',
			array(
				'label' => esc_html__( 'نوارهای پیشرفت و مالی', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'progress_track_color',
			array(
				'label'     => esc_html__( 'رنگ زمینه پیشرفت', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e7efea',
				'selectors' => array( '{{WRAPPER}} .bap__progress' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'progress_fill_background',
				'label'    => esc_html__( 'رنگ بخش تکمیل‌شده', 'bonyad-alavi-child' ),
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .bap__progress-fill',
			)
		);

		$this->add_responsive_control(
			'progress_height',
			array(
				'label'      => esc_html__( 'ارتفاع نوار پیشرفت', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 4, 'max' => 30 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 10 ),
				'selectors'  => array( '{{WRAPPER}} .bap__progress' => 'height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'public_share_color',
			array(
				'label'     => esc_html__( 'رنگ سهم مردم', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f1b63b',
				'selectors' => array( '{{WRAPPER}} .bap__funding-public, {{WRAPPER}} .bap__funding-legend .is-public' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'foundation_share_color',
			array(
				'label'     => esc_html__( 'رنگ سهم بنیاد', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#079447',
				'selectors' => array( '{{WRAPPER}} .bap__funding-foundation, {{WRAPPER}} .bap__funding-legend .is-foundation' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_button_style_controls() {
		$this->start_controls_section(
			'button_style',
			array(
				'label' => esc_html__( 'دکمه‌ها و ورودی مبلغ', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->start_controls_tabs( 'submit_button_tabs' );
		$this->start_controls_tab(
			'submit_button_normal',
			array( 'label' => esc_html__( 'عادی', 'bonyad-alavi-child' ) )
		);
		$this->add_control(
			'submit_text_color',
			array(
				'label'     => esc_html__( 'رنگ متن', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .bap__submit' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'submit_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .bap__submit',
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab(
			'submit_button_hover',
			array( 'label' => esc_html__( 'هاور', 'bonyad-alavi-child' ) )
		);
		$this->add_control(
			'submit_hover_text_color',
			array(
				'label'     => esc_html__( 'رنگ متن', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .bap__submit:hover' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'submit_hover_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .bap__submit:hover',
			)
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'submit_radius',
			array(
				'label'      => esc_html__( 'گردی دکمه اصلی', 'bonyad-alavi-child' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 12 ),
				'selectors'  => array( '{{WRAPPER}} .bap__submit' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'submit_shadow',
				'selector' => '{{WRAPPER}} .bap__submit',
			)
		);

		$this->add_control(
			'preset_active_color',
			array(
				'label'     => esc_html__( 'رنگ مبلغ انتخاب‌شده', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#079447',
				'selectors' => array(
					'{{WRAPPER}} .bap__preset-grid button:hover, {{WRAPPER}} .bap__preset-grid button.is-active' => 'border-color: {{VALUE}}; color: {{VALUE}};',
				)
			)
		);

		$this->add_control(
			'input_focus_color',
			array(
				'label'     => esc_html__( 'رنگ فوکوس ورودی', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#079447',
				'selectors' => array( '{{WRAPPER}} .bap__input-wrap:focus-within' => 'border-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_mobile_style_controls() {
		$this->start_controls_section(
			'mobile_dock_style',
			array(
				'label' => esc_html__( 'نوار مشارکت موبایل', 'bonyad-alavi-child' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'mobile_dock_background',
			array(
				'label'     => esc_html__( 'پس‌زمینه نوار', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .bap__mobile-dock' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'mobile_button_background',
			array(
				'label'     => esc_html__( 'پس‌زمینه دکمه', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#079447',
				'selectors' => array( '{{WRAPPER}} .bap__mobile-dock button' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'mobile_button_color',
			array(
				'label'     => esc_html__( 'رنگ متن دکمه', 'bonyad-alavi-child' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .bap__mobile-dock button' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

    private function format_match_ratio( $value ) {
        $rounded = round( $this->positive_number( $value ), 2 );

        if ( abs( $rounded - round( $rounded ) ) < 0.00001 ) {
            $decimals = 0;
        } elseif ( abs( ( $rounded * 10 ) - round( $rounded * 10 ) ) < 0.00001 ) {
            $decimals = 1;
        } else {
            $decimals = 2;
        }

        return '×' . number_format_i18n( $rounded, $decimals );
    }
	private function positive_number( $value ) {
		return max( 0, (float) $value );
	}

	private function format_money( $value, $currency ) {
		return number_format_i18n( $this->positive_number( $value ), 0 ) . ' ' . $currency;
	}

	private function render_icon( $icon, $attributes = array() ) {
		if ( empty( $icon['value'] ) ) {
			return;
		}

		Icons_Manager::render_icon( $icon, array_merge( array( 'aria-hidden' => 'true' ), $attributes ) );
	}

	private function get_link_attributes( $link ) {
		$attributes = '';

		if ( empty( $link['url'] ) ) {
			return $attributes;
		}

		$attributes .= ' href="' . esc_url( $link['url'] ) . '"';
		if ( ! empty( $link['is_external'] ) ) {
			$attributes .= ' target="_blank"';
		}
		if ( ! empty( $link['nofollow'] ) ) {
			$attributes .= ' rel="nofollow"';
		}

		return $attributes;
	}

    private function get_context_product_id( $fallback_id = 0 ) {
        $candidates = array();

        if ( function_exists( 'wc_get_product' ) ) {
            global $product;

            if ( $product instanceof \WC_Product ) {
                $candidates[] = $product->get_id();
            }
        }

        $candidates[] = get_queried_object_id();
        $candidates[] = get_the_ID();
        $candidates[] = absint( $fallback_id );

        $candidates = array_unique(
                array_filter(
                        array_map( 'absint', $candidates )
                )
        );

        foreach ( $candidates as $candidate ) {
            if ( 'product' === get_post_type( $candidate ) ) {
                return $candidate;
            }
        }

        return 0;
    }

    private function get_product_faq_items( $fallback_id = 0 ) {
        $product_id = $this->get_context_product_id( $fallback_id );

        if ( ! $product_id ) {
            return array();
        }

        $meta_key = defined( 'BAP_PRODUCT_FAQ_META_KEY' ) ? BAP_PRODUCT_FAQ_META_KEY : '_bap_product_faqs';

        $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;

        $items = $product ? $product->get_meta( $meta_key, true ) : get_post_meta( $product_id, $meta_key, true );

        if ( ! is_array( $items ) ) {
            return array();
        }

        $normalized = array();

        foreach ( $items as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $question = isset( $item['question'] ) ? sanitize_text_field( $item['question'] ) : '';

            $answer = isset( $item['answer'] ) ? wp_kses_post( $item['answer'] ) : '';

            if ( '' === $question || '' === trim( wp_strip_all_tags( $answer ) ) ) {
                continue;
            }

            $normalized[] = array(
                    'question' => $question,
                    'answer'   => wpautop( $answer ),
            );
        }

        return $normalized;
    }

	/**
	 * خروجی صفحه مشارکت را با محصول جاری و رسانه‌های استاندارد ووکامرس تولید می‌کند.
	 */
	protected function render() {
		$media_service = BA_Product_Media_Service::instance();
		$product       = $media_service->resolve_context_product();

		if ( ! $product ) {
			echo '<h1>' . esc_html__( 'این ویجت فقط در صفحه محصول قابل نمایش است.', 'bonyad-alavi-child' ) . '</h1>';
			return;
		}

		$product_id = $product->get_id();

		$settings          = $this->get_settings_for_display();
		$uid               = 'bap-' . $this->get_id();
		$collected         = $this->positive_number( $settings['collected_amount'] );
		$minimum_amount    = $this->positive_number( $settings['minimum_amount'] );

		$currency          = ! empty( $settings['currency_label'] ) ? $settings['currency_label'] : esc_html__( 'تومان', 'bonyad-alavi-child' );
		$form_mode         = in_array( $settings['form_mode'], array( 'event', 'post' ), true ) ? $settings['form_mode'] : 'event';
		$form_method       = 'get' === $settings['form_method'] ? 'get' : 'post';
		$form_action       = ( 'post' === $form_mode && ! empty( $settings['form_action']['url'] ) ) ? $settings['form_action']['url'] : '';
		$product_media_items = $media_service->get_product_items( $product );
		$has_product_gallery = $media_service->has_gallery( $product );
		$display_media_items = ! empty( $product_media_items )
			? $product_media_items
			: array( $media_service->get_placeholder_item( $product ) );
		$show_media_carousel = $has_product_gallery && count( $display_media_items ) > 1;
		$lightbox_items      = $product_media_items;
		$lightbox_count      = count( $lightbox_items );

        //آمار

        $public_target = class_exists(
                'Bonyad_Alavi_WooCommerce_Participation'
        )
                ? Bonyad_Alavi_WooCommerce_Participation::get_goal_amount(
                        $product_id
                )
                : $this->positive_number(
                        get_post_meta(
                                $product_id,
                                'goal_amount',
                                true
                        )
                );
        $total_budget  = $this->positive_number( $settings['total_budget'] );
        $foundation_share = max( 0, $total_budget - $public_target );
        $progress          = $public_target > 0 ? min( 100, max( 0, ( $collected / $public_target ) * 100 ) ) : 0;
        $public_percentage = $total_budget > 0 ? min( 100, max( 0, ( $public_target / $total_budget ) * 100 ) ) : 0;
        $foundation_pct    = max( 0, 100 - $public_percentage );

        $match_ratio = $public_target > 0 ? $foundation_share / $public_target : 0;

        $match_badge = $this->format_match_ratio( $match_ratio );
        $match_badge_words = $this->match_badge_to_words( $match_badge );

        $remaining        = max( 0, $public_target - $collected );

        $funding_description = $this->replace_funding_tags(
                $settings['funding_description'],
                array(
                        'collected'        => $collected,
                        'public_target'    => $public_target,
                        'remaining'        => $remaining,
                        'foundation_share' => $foundation_share,
                        'total_budget'     => $total_budget,
                        'progress_percent' => $progress,
                        'match_badge'      => $match_badge,
                        'match_words'      => $match_badge_words,
                )
        );

        //سوالات متداول
        $product_faq_items = $this->get_product_faq_items( $settings['project_id'] ?? 0 );

        $static_faq_items = ( ! empty( $settings['faq_items'] ) && is_array( $settings['faq_items'] ) ) ? $settings['faq_items'] : array();

        /*
         * ترتیب نمایش:
         * ۱. سؤال‌های اختصاصی محصول
         * ۲. سؤال‌های ثابت ویجت
         */
        $faq_items = array_merge( $product_faq_items, $static_faq_items );

        // اطلاعات AJAX پرداخت سریع مستقل از سبد فعلی کاربر.
        $quick_prepare_endpoint = class_exists( 'Bonyad_Alavi_WooCommerce_Participation' ) ? Bonyad_Alavi_WooCommerce_Participation::get_quick_prepare_endpoint() : '';
        $quick_payment_endpoint = class_exists( 'Bonyad_Alavi_WooCommerce_Participation' ) ? Bonyad_Alavi_WooCommerce_Participation::get_quick_payment_endpoint() : '';
        $cart_nonce             = class_exists( 'Bonyad_Alavi_WooCommerce_Participation' ) ? Bonyad_Alavi_WooCommerce_Participation::create_nonce() : '';
        ?>
		<div class="bap"
			dir="rtl"
             data-quick-prepare-endpoint="<?php echo esc_url( $quick_prepare_endpoint ); ?>"
             data-quick-payment-endpoint="<?php echo esc_url( $quick_payment_endpoint ); ?>"
             data-cart-nonce="<?php echo esc_attr( $cart_nonce ); ?>"
             data-goal-amount="<?php echo esc_attr( $public_target ); ?>"
			data-project-id="<?php echo esc_attr( $product_id ); ?>"
			data-project-title="<?php echo esc_attr( $product->get_title() ); ?>"
			data-collected="<?php echo esc_attr( $collected ); ?>"
			data-public-target="<?php echo esc_attr( $public_target ); ?>"
			data-total-budget="<?php echo esc_attr( $total_budget ); ?>"
			data-minimum="<?php echo esc_attr( $minimum_amount ); ?>"
			data-currency="<?php echo esc_attr( $currency ); ?>"
			data-success-message="<?php echo esc_attr( $settings['success_message'] ); ?>"
			data-share-url="<?php echo esc_url( get_permalink( $product_id ) ); ?>"
			data-share-title="<?php echo esc_attr( $product->get_title() ); ?>"
			data-share-text="<?php echo esc_attr( $settings['project_subtitle'] ); ?>"
			data-copy-message="<?php echo esc_attr( $settings['copy_success_message'] ?? esc_html__( 'لینک پروژه کپی شد.', 'bonyad-alavi-child' ) ); ?>">
			<div class="bap__shell">
				<?php if ( 'yes' === $settings['show_breadcrumb'] ) : ?>
                    <?php
                        woocommerce_breadcrumb([
                                'delimiter'   => '<span aria-hidden="true">/</span>',
                                'wrap_before' => '<nav class="bap__breadcrumb " aria-label="'.esc_attr__( 'مسیر صفحه', 'bonyad-alavi-child' ).'">',
                                'wrap_after'  => '</nav>',
                                'before'      => '',
                                'after'       => '',
                                'home'        =>  !empty($settings['home_label']) ? esc_html( $settings['home_label'] ) : _x( 'Home', 'breadcrumb', 'woocommerce' ),
                        ]);
                    ?>
				<?php endif; ?>

				<section class="bap__hero" aria-labelledby="<?php echo esc_attr( $uid . '-title' ); ?>">
					<div class="bap__content-column">
						<div class="bap__media-card">
							<?php if ( 'yes' === $settings['show_image_badges'] ) : ?>
								<div class="bap__media-badges" aria-label="<?php echo esc_attr__( 'مشخصات پروژه', 'bonyad-alavi-child' ); ?>">
									<?php if ( $settings['project_category'] ) : ?><span><?php echo sanitize_text_field( $settings['project_category'] ); ?></span><?php endif; ?>
									<?php if ( $settings['project_location'] ) : ?><span><?php echo sanitize_text_field($settings['project_location'] ); ?></span><?php endif; ?>
								</div>
							<?php endif; ?>

							<div class="bap__media-carousel<?php echo $show_media_carousel ? ' bap__media-carousel--enabled' : ' bap__media-carousel--static'; ?>" data-carousel-enabled="<?php echo $show_media_carousel ? 'true' : 'false'; ?>">
								<div class="bap__media-viewport" data-carousel-viewport <?php echo $show_media_carousel ? 'tabindex="0" role="region" aria-label="' . esc_attr__( 'کاروسل تصاویر پروژه', 'bonyad-alavi-child' ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
									<div class="bap__media-slides">
										<?php foreach ( $display_media_items as $index => $item ) : ?>
											<figure class="bap__media-slide<?php echo 0 === $index ? ' bap__media-slide--active' : ''; ?>" data-carousel-slide aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
												<?php
												echo BA_Media_Helper::render_image(
													$item,
													'full',
													array(
														'class'         => 'bap__main-image',
														'loading'       => 0 === $index ? 'eager' : 'lazy',
														'fetchpriority' => 0 === $index ? 'high' : 'auto',
														'decoding'      => 'async',
													)
												); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												?>
											</figure>
										<?php endforeach; ?>
									</div>

									<?php if ( $show_media_carousel ) : ?>
										<button type="button" class="bap__media-nav bap__media-nav--prev" data-carousel-prev aria-label="<?php echo esc_attr__( 'تصویر قبلی', 'bonyad-alavi-child' ); ?>">
											<?php echo BA_Media_Helper::get_chevron_svg( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</button>
										<button type="button" class="bap__media-nav bap__media-nav--next" data-carousel-next aria-label="<?php echo esc_attr__( 'تصویر بعدی', 'bonyad-alavi-child' ); ?>">
											<?php echo BA_Media_Helper::get_chevron_svg( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</button>
									<?php endif; ?>
								</div>
							</div>

							<?php if ( 'yes' === $settings['enable_lightbox'] && $lightbox_count > 0 ) : ?>
								<?php $zoom_label = $has_product_gallery ? esc_html__( 'نمایش گالری تصاویر', 'bonyad-alavi-child' ) : esc_html__( 'نمایش بزرگ تصویر', 'bonyad-alavi-child' ); ?>
								<button class="bap__zoom<?php echo $has_product_gallery ? ' bap__zoom--gallery' : ' bap__zoom--zoom'; ?>" type="button" aria-label="<?php echo esc_attr( $zoom_label ); ?>" aria-controls="<?php echo esc_attr( $uid . '-lightbox' ); ?>">
									<?php echo BA_Media_Helper::get_gallery_action_svg( $has_product_gallery ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</button>
							<?php endif; ?>
						</div>

						<header class="bap__mobile-heading">
							<div class="bap__kicker"><span aria-hidden="true"></span><?php echo esc_html( $settings['kicker_text'] ); ?></div>
							<h1><?php echo esc_html( $product->get_title() ); ?></h1>
							<p><?php echo esc_html( $settings['project_subtitle'] ); ?></p>
						</header>

						<?php if ( 'yes' === $settings['show_about'] ) : ?>
							<div class="bap__story-card">
								<?php $this->render_section_heading( '01', $settings['about_eyebrow'], 'درباره این پروژه و تأثیری که می‌سازد' ); ?>
								<div class="bap__desktop-heading">

									<p><?php echo esc_html( $settings['project_subtitle'] ); ?></p>
								</div>
								<div class="bap__story-text">
                                    <?php echo apply_filters('the_content', $product->get_description() ); ?>
                                </div>

							</div>
						<?php endif; ?>

						<?php if ( 'yes' === $settings['show_funding_section'] ) : ?>
							<section class="bap__funding-card">
								<?php $this->render_section_heading( '02', $settings['funding_eyebrow'], $settings['funding_title'] ); ?>
                                <?php if ( ! empty( $funding_description ) ) : ?>
                                    <div class="bap__funding-description">
                                        <?php echo wp_kses_post( $funding_description ); ?>
                                    </div>
                                <?php endif; ?>
								<div class="bap__funding-bar" aria-label="<?php echo esc_attr( round( $public_percentage ) . '% ' . $settings['public_share_label'] . '، ' . round( $foundation_pct ) . '% ' . $settings['foundation_share_label'] ); ?>">
									<span class="bap__funding-public" style="width:<?php echo esc_attr( $public_percentage ); ?>%"></span>
									<span class="bap__funding-foundation" style="width:<?php echo esc_attr( $foundation_pct ); ?>%"></span>
								</div>
								<div class="bap__funding-legend">
									<div><i class="is-public"></i><span><?php echo esc_html( $settings['public_share_label'] ); ?></span><strong><?php echo esc_html( $this->format_money( $public_target, $currency ) ); ?></strong></div>
									<div><i class="is-foundation"></i><span><?php echo esc_html( $settings['foundation_share_label'] ); ?></span><strong><?php echo esc_html( $this->format_money( $foundation_share, $currency ) ); ?></strong></div>
								</div>
							</section>
						<?php endif; ?>


                <?php if ( 'yes' === $settings['show_faq'] && ! empty( $faq_items ) ) : ?>
					<section class="bap__faq-card">
						<?php $this->render_section_heading( '03', $settings['faq_eyebrow'], $settings['faq_title'] ); ?>
						<div class="bap__accordion">
                            <?php foreach ( $faq_items as $item ) : ?>
								<details>
									<summary><?php echo esc_html( $item['question'] ); ?></summary>
									<div class="bap__accordion-answer"><?php echo wp_kses_post( $item['answer'] ); ?></div>
								</details>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>
			</div>

			<aside class="bap__donation-column" aria-label="<?php echo esc_attr__( 'فرم مشارکت در پروژه', 'bonyad-alavi-child' ); ?>">

                <div class="bap__donation-card">
                    <header class="bap__desktop-heading">
                        <div class="bap__kicker"><span aria-hidden="true"></span><?php echo esc_html( $settings['kicker_text'] ); ?></div>
                        <h1 id="<?php echo esc_attr( $uid . '-title' ); ?>"><?php echo esc_html( $product->get_title() ); ?></h1>
                    </header>
                    <div class="bap__verified">
                        <svg viewBox="0 0 24 24">
                            <path d="M0 0h24v24H0z" fill="none" />
                            <path fill="currentColor" d="m23 12l-2.44-2.79l.34-3.69l-3.61-.82l-1.89-3.2L12 2.96L8.6 1.5L6.71 4.69L3.1 5.5l.34 3.7L1 12l2.44 2.79l-.34 3.7l3.61.82L8.6 22.5l3.4-1.47l3.4 1.46l1.89-3.19l3.61-.82l-.34-3.69zM9.38 16.01L7 13.61a.996.996 0 0 1 0-1.41l.07-.07c.39-.39 1.03-.39 1.42 0l1.61 1.62l5.15-5.16c.39-.39 1.03-.39 1.42 0l.07.07c.39.39.39 1.02 0 1.41l-5.92 5.94c-.41.39-1.04.39-1.44 0" />
                        </svg>
                        <span><strong><?php echo esc_html( $settings['verified_title'] ); ?></strong><small><?php echo esc_html( $settings['verified_description'] ); ?></small></span>
					</div>

					<div class="bap__progress-head"><span><?php echo esc_html( $settings['progress_label'] ); ?></span><strong class="bap__progress-percent"><?php echo esc_html( number_format_i18n( round( $progress ) ) ); ?>٪</strong></div>
					<div class="bap__progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( round( $progress ) ); ?>">
						<span class="bap__progress-fill" style="width:<?php echo esc_attr( $progress ); ?>%"></span>
					</div>

					<div class="bap__amount-summary">
						<div><span><?php echo esc_html( $settings['collected_label'] ); ?></span><strong class="bap__collected"><?php echo esc_html( $this->format_money( $collected, $currency ) ); ?></strong></div>
						<div><span><?php echo esc_html( $settings['target_label'] ); ?></span><strong class="bap__target"><?php echo esc_html( $this->format_money( $public_target, $currency ) ); ?></strong></div>
					</div>

                    <?php if ( $match_ratio > 0 ) : ?>
                        <div class="bap__match-note">
                            <span class="bap__match-icon"
                                    aria-label="<?php
                                    echo esc_attr(
                                            sprintf(
                                                    esc_html__( 'ضریب هم‌افزایی %s', 'bonyad-alavi-child' ),
                                                    $match_badge
                                            )
                                    );
                                    ?>">
                                <?php echo esc_html( $match_badge ); ?>
                            </span>

                            <p>
                                <strong>
                                    <?php
                                    printf(
                                            esc_html__( 'اثر مشارکت شما %s برابر می‌شود.', 'bonyad-alavi-child' ),
                                            esc_html( $match_badge_words )
                                    );
                                    ?>
                                </strong>
                                <?php echo esc_html( $settings['match_description'] ); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <form class="bap__donation-form" action="" method="post" data-mode="woocommerce" novalidate>
                        <input type="hidden" name="project_id" value="<?php echo esc_attr( $product_id ); ?>">
						<fieldset>
							<legend><?php echo esc_html( $settings['preset_legend'] ); ?></legend>
							<div class="bap__preset-grid">
								<?php foreach ( $settings['preset_amounts'] as $item ) : ?>
									<button type="button" data-amount="<?php echo esc_attr( $this->positive_number( $item['amount'] ) ); ?>"><?php echo esc_html( $item['label'] ); ?></button>
								<?php endforeach; ?>
							</div>
						</fieldset>

						<label class="bap__custom-amount" for="<?php echo esc_attr( $uid . '-amount' ); ?>">
							<span><?php echo esc_html( $settings['custom_amount_label'] ); ?></span>
							<span class="bap__input-wrap">
                                <input id="<?php echo esc_attr( $uid . '-amount' ); ?>" class="bap__amount-input" name="amount" type="text" inputmode="numeric" autocomplete="off" placeholder="<?php echo esc_attr( $settings['amount_placeholder'] ); ?>" aria-describedby="<?php echo esc_attr( $uid . '-minimum ' . $uid . '-error' ); ?>">
                                <b><?php echo esc_html( $currency ); ?></b>
                            </span>
						</label>

						<div class="bap__form-help">
							<span id="<?php echo esc_attr( $uid . '-minimum' ); ?>"><?php echo esc_html__( 'حداقل مبلغ مشارکت:', 'bonyad-alavi-child' ); ?> <strong class="bap__minimum"><?php echo esc_html( $this->format_money( $minimum_amount, $currency ) ); ?></strong></span>
							<span class="bap__amount-error" id="<?php echo esc_attr( $uid . '-error' ); ?>" role="alert"></span>
						</div>

                        <div class="bap__quick-checkout" hidden aria-live="polite"></div>

						<button class="bap__submit" type="submit">
							<span><?php echo esc_html( $settings['submit_text'] ); ?></span>
                            <svg viewBox="0 0 24 24">
                                <path d="M0 0h24v24H0z" fill="none" />
                                <g fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1.5">
                                    <path d="M16 6.28a2.28 2.28 0 0 1-.662 1.606c-.976.984-1.923 2.01-2.936 2.958a.597.597 0 0 1-.822-.017l-2.918-2.94a2.28 2.28 0 0 1 0-3.214a2.277 2.277 0 0 1 3.232 0L12 4.78l.106-.107A2.276 2.276 0 0 1 16 6.28Z" />
                                    <path stroke-linecap="round" d="m18 20l3.824-3.824a.6.6 0 0 0 .176-.424V10.5A1.5 1.5 0 0 0 20.5 9v0a1.5 1.5 0 0 0-1.5 1.5V15" />
                                    <path stroke-linecap="round" d="m18 16l.858-.858a.48.48 0 0 0 .142-.343v0a.49.49 0 0 0-.268-.433l-.443-.221a2 2 0 0 0-2.308.374l-.895.895a2 2 0 0 0-.586 1.414V20M6 20l-3.824-3.824A.6.6 0 0 1 2 15.752V10.5A1.5 1.5 0 0 1 3.5 9v0A1.5 1.5 0 0 1 5 10.5V15" />
                                    <path stroke-linecap="round" d="m6 16l-.858-.858A.5.5 0 0 1 5 14.799v0c0-.183.104-.35.268-.433l.443-.221a2 2 0 0 1 2.308.374l.895.895a2 2 0 0 1 .586 1.414V20" />
                                </g>
                            </svg>
                        </button>
					</form>


					<?php if ( ! empty( $settings['trust_items'] ) ) : ?>
						<div class="bap__trust-row" aria-label="<?php echo esc_attr__( 'مزیت‌های مشارکت', 'bonyad-alavi-child' ); ?>">
							<?php foreach ( $settings['trust_items'] as $item ) : ?>
								<span><?php $this->render_icon( $item['icon'] ); ?><?php echo esc_html( $item['text'] ); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

				</div>

				<?php if ( 'yes' === $settings['show_noncash'] && ! empty( $settings['noncash_items'] ) ) : ?>
					<section class="bap__noncash-card bap__sidebar-card">
						<?php
                        $nocash_icon = '<svg viewBox="0 0 24 24">
                                        <path d="M0 0h24v24H0z" fill="none" />
                                        <path fill="currentColor" d="m15.692 12.385l-3.765-3.608q-.66-.635-1.101-1.42t-.441-1.71q0-1.103.772-1.875Q11.928 3 13.03 3q.819 0 1.49.443t1.171 1.076q.5-.633 1.172-1.076q.67-.443 1.49-.443q1.103 0 1.874.772T21 5.646q0 .926-.438 1.711q-.439.785-1.099 1.42zm0-1.377l2.995-2.867q.532-.514.923-1.138q.39-.624.39-1.357q0-.684-.48-1.165T18.353 4q-.6 0-1.076.359q-.476.358-.855.845l-.73.915l-.731-.915q-.38-.487-.855-.845Q13.63 4 13.03 4q-.685 0-1.165.48q-.481.482-.481 1.166q0 .733.39 1.357t.923 1.138zm-9.288 7.646l7.565 2.207l5.989-1.85q-.03-.455-.273-.656t-.55-.201H14.39q-.634 0-1.15-.05t-1.055-.238l-2.19-.718l.338-.988l2.025.732q.482.183 1.096.22q.613.036 1.68.042q0-.468-.172-.756t-.493-.402l-5.754-2.112q-.057-.019-.106-.028t-.105-.01h-2.1zm-4 2.346v-8.154H8.48q.14 0 .288.032t.275.074l5.779 2.117q.537.204.924.733q.388.529.388 1.352h3q.904 0 1.384.565q.481.566.481 1.435v.615l-6.98 2.154l-7.616-2.22V21zm1-1h2v-6.154h-2zM15.692 6.12" />
                                    </svg>
                                    ';
                        $this->render_section_heading( $nocash_icon, $settings['noncash_eyebrow'], $settings['noncash_title'] ); ?>
						<div class="bap__noncash-grid">
							<?php foreach ( $settings['noncash_items'] as $index => $item ) : ?>
								<?php
								$has_link = ! empty( $item['link']['url'] );
								$tag      = $has_link ? 'a' : 'button';
								?>
								<<?php echo esc_html( $tag ); ?> class="bap__noncash-option"<?php echo $has_link ? $this->get_link_attributes( $item['link'] ) : ' type="button" data-noncash="' . esc_attr( $index ) . '"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
									<span class="bap__noncash-symbol"><?php $this->render_icon( $item['icon'] ); ?></span>
									<span><strong><?php echo esc_html( $item['title'] ); ?></strong><small><?php echo esc_html( $item['description'] ); ?></small></span>
									<span class="bap__noncash-arrow" aria-hidden="true">←</span>
								</<?php echo esc_html( $tag ); ?>>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( 'yes' === ( $settings['show_share'] ?? 'yes' ) ) : ?>
					<section class="bap__share-card bap__sidebar-card" aria-label="<?php echo esc_attr__( 'اشتراک‌گذاری پروژه', 'bonyad-alavi-child' ); ?>">
                        <?php
                        $section_3_icon = '<svg viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none" />
                                    <circle cx="18" cy="5" r="1" fill="currentColor" opacity=".3" />
                                    <circle cx="6" cy="12" r="1" fill="currentColor" opacity=".3" />
                                    <circle cx="18" cy="19.02" r="1" fill="currentColor" opacity=".3" />
                                    <path fill="currentColor" d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81c1.66 0 3-1.34 3-3s-1.34-3-3-3s-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65c0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92M18 4c.55 0 1 .45 1 1s-.45 1-1 1s-1-.45-1-1s.45-1 1-1M6 13c-.55 0-1-.45-1-1s.45-1 1-1s1 .45 1 1s-.45 1-1 1m12 7.02c-.55 0-1-.45-1-1s.45-1 1-1s1 .45 1 1s-.45 1-1 1" />
                                </svg>';
                        $this->render_section_heading( $section_3_icon, $settings['share_description'], $settings['share_title'] ); ?>

						<div class="bap__share-actions">
							<button type="button" class="bap__share-button">
                                <svg viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none" />
                                    <circle cx="18" cy="5" r="1" fill="currentColor" opacity=".3" />
                                    <circle cx="6" cy="12" r="1" fill="currentColor" opacity=".3" />
                                    <circle cx="18" cy="19.02" r="1" fill="currentColor" opacity=".3" />
                                    <path fill="currentColor" d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81c1.66 0 3-1.34 3-3s-1.34-3-3-3s-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65c0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92M18 4c.55 0 1 .45 1 1s-.45 1-1 1s-1-.45-1-1s.45-1 1-1M6 13c-.55 0-1-.45-1-1s.45-1 1-1s1 .45 1 1s-.45 1-1 1m12 7.02c-.55 0-1-.45-1-1s.45-1 1-1s1 .45 1 1s-.45 1-1 1" />
                                </svg>								<span><?php echo esc_html( $settings['share_button_text'] ); ?></span>
							</button>
							<button type="button" class="bap__copy-link">
                                <svg viewBox="0 0 24 24">
                                    <path d="M0 0h24v24H0z" fill="none" />
                                    <path fill="currentColor" fill-rule="evenodd" d="M15 1.25h-4.056c-1.838 0-3.294 0-4.433.153c-1.172.158-2.121.49-2.87 1.238c-.748.749-1.08 1.698-1.238 2.87c-.153 1.14-.153 2.595-.153 4.433V16a3.75 3.75 0 0 0 3.166 3.705c.137.764.402 1.416.932 1.947c.602.602 1.36.86 2.26.982c.867.116 1.97.116 3.337.116h3.11c1.367 0 2.47 0 3.337-.116c.9-.122 1.658-.38 2.26-.982s.86-1.36.982-2.26c.116-.867.116-1.97.116-3.337v-5.11c0-1.367 0-2.47-.116-3.337c-.122-.9-.38-1.658-.982-2.26c-.531-.53-1.183-.795-1.947-.932A3.75 3.75 0 0 0 15 1.25m2.13 3.021A2.25 2.25 0 0 0 15 2.75h-4c-1.907 0-3.261.002-4.29.14c-1.005.135-1.585.389-2.008.812S4.025 4.705 3.89 5.71c-.138 1.029-.14 2.383-.14 4.29v6a2.25 2.25 0 0 0 1.521 2.13c-.021-.61-.021-1.3-.021-2.075v-5.11c0-1.367 0-2.47.117-3.337c.12-.9.38-1.658.981-2.26c.602-.602 1.36-.86 2.26-.981c.867-.117 1.97-.117 3.337-.117h3.11c.775 0 1.464 0 2.074.021M7.408 6.41c.277-.277.665-.457 1.4-.556c.754-.101 1.756-.103 3.191-.103h3c1.435 0 2.436.002 3.192.103c.734.099 1.122.28 1.399.556c.277.277.457.665.556 1.4c.101.754.103 1.756.103 3.191v5c0 1.435-.002 2.436-.103 3.192c-.099.734-.28 1.122-.556 1.399c-.277.277-.665.457-1.4.556c-.755.101-1.756.103-3.191.103h-3c-1.435 0-2.437-.002-3.192-.103c-.734-.099-1.122-.28-1.399-.556c-.277-.277-.457-.665-.556-1.4c-.101-.755-.103-1.756-.103-3.191v-5c0-1.435.002-2.437.103-3.192c.099-.734.28-1.122.556-1.399" clip-rule="evenodd" />
                                </svg>
								<span><?php echo esc_html( $settings['copy_link_text'] ); ?></span>
							</button>
						</div>
					</section>
				<?php endif; ?>
			</aside>
			</section>
			</div>

			<?php if ( 'yes' === $settings['show_mobile_dock'] ) : ?>
				<div class="bap__mobile-dock" aria-label="<?php echo esc_attr__( 'دسترسی سریع به مشارکت', 'bonyad-alavi-child' ); ?>">
					<div><span><?php echo esc_html( $settings['mobile_dock_label'] ); ?></span><strong class="bap__dock-amount"><?php echo esc_html__( 'انتخاب نشده', 'bonyad-alavi-child' ); ?></strong></div>
					<button type="button" class="bap__dock-button"><?php echo esc_html( $settings['mobile_dock_button'] ); ?></button>
				</div>
			<?php endif; ?>

			<?php if ( 'yes' === $settings['enable_lightbox'] && $lightbox_count > 0 ) : ?>
				<div class="bap__lightbox" id="<?php echo esc_attr( $uid . '-lightbox' ); ?>" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'گالری تصاویر پروژه', 'bonyad-alavi-child' ); ?>" hidden>
					<button type="button" class="bap__lightbox-close" aria-label="<?php echo esc_attr__( 'بستن گالری', 'bonyad-alavi-child' ); ?>">×</button>

					<div class="bap__lightbox-carousel">
						<div class="bap__lightbox-stage">
							<?php if ( $lightbox_count > 1 ) : ?>
								<button type="button" class="bap__lightbox-prev" aria-label="<?php echo esc_attr__( 'تصویر قبلی', 'bonyad-alavi-child' ); ?>">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M0 0h24v24H0z" fill="none" />
                                        <path fill="currentColor" d="M12.6 12L8.7 8.1q-.275-.275-.275-.7t.275-.7t.7-.275t.7.275l4.6 4.6q.15.15.213.325t.062.375t-.062.375t-.213.325l-4.6 4.6q-.275.275-.7.275t-.7-.275t-.275-.7t.275-.7z" />
                                    </svg>
                                </button>
							<?php endif; ?>

							<div class="bap__lightbox-slides">
								<?php foreach ( $lightbox_items as $index => $item ) : ?>
									<figure class="bap__lightbox-slide<?php echo 0 === $index ? ' is-active' : ''; ?>" data-lightbox-index="<?php echo esc_attr( $index ); ?>" aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
										<?php
										echo BA_Media_Helper::render_image(
											$item,
											'large',
											array(
												'loading'  => 0 === $index ? 'eager' : 'lazy',
												'decoding' => 'async',
											)
										); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
									</figure>
								<?php endforeach; ?>
							</div>

							<?php if ( $lightbox_count > 1 ) : ?>
								<button type="button" class="bap__lightbox-next" aria-label="<?php echo esc_attr__( 'تصویر بعدی', 'bonyad-alavi-child' ); ?>">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M0 0h24v24H0z" fill="none" />
                                        <path fill="currentColor" d="m10.8 12l3.9 3.9q.275.275.275.7t-.275.7t-.7.275t-.7-.275l-4.6-4.6q-.15-.15-.212-.325T8.425 12t.063-.375t.212-.325l4.6-4.6q.275-.275.7-.275t.7.275t.275.7t-.275.7z" />
                                    </svg>
                                </button>
							<?php endif; ?>
						</div>

						<?php if ( $lightbox_count > 1 ) : ?>
							<div class="bap__lightbox-meta">
								<div class="bap__lightbox-counter" aria-live="polite">
									<span class="bap__lightbox-current">۱</span>
									<span aria-hidden="true">/</span>
									<span><?php echo esc_html( number_format_i18n( $lightbox_count ) ); ?></span>
								</div>

								<div class="bap__lightbox-thumbs" aria-label="<?php echo esc_attr__( 'انتخاب تصویر', 'bonyad-alavi-child' ); ?>">
									<?php foreach ( $lightbox_items as $index => $item ) : ?>
										<button type="button" class="bap__lightbox-thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-lightbox-index="<?php echo esc_attr( $index ); ?>" aria-label="<?php echo esc_attr( sprintf( esc_html__( 'تصویر %d', 'bonyad-alavi-child' ), $index + 1 ) ); ?>" aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>">
											<?php
											echo BA_Media_Helper::render_image(
												$item,
												'thumbnail',
												array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' )
											); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?>
										</button>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="bap__toast" role="status" aria-live="polite"></div>
		</div>
		<?php
	}

	private function render_section_heading( $number, $eyebrow, $title ) {
		?>
		<div class="bap__section-heading">
			<span class="bap__section-icon" aria-hidden="true"><?php echo $number; ?></span>
			<div><p><?php echo esc_html( $eyebrow ); ?></p><h2><?php echo esc_html( $title ); ?></h2></div>
		</div>
		<?php
	}
    private function replace_funding_tags( $content, $data ) {
        if ( empty( $content ) ) {
            return '';
        }

        $tags = array(
                '{collected}'        => number_format_i18n( $data['collected'] ),
                '{public_target}'    => number_format_i18n( $data['public_target'] ),
                '{remaining}'        => number_format_i18n( $data['remaining'] ),
                '{foundation_share}' => number_format_i18n( $data['foundation_share'] ),
                '{total_budget}'     => number_format_i18n( $data['total_budget'] ),
                '{progress_percent}' => number_format_i18n( $data['progress_percent'], 0 ) . '٪',
                '{match_badge}'      => $data['match_badge'],
                '{match_words}'      => $data['match_words'],
        );

        return strtr( $content, $tags );
    }
    private function match_badge_to_words( $match_badge ) {
        $persian_digits = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
        $arabic_digits  = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
        $english_digits = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );

        $value = str_replace( $persian_digits, $english_digits, $match_badge );
        $value = str_replace( $arabic_digits, $english_digits, $value );
        $value = str_replace(
                array( '×', '٫', '٬', ',', ' ' ),
                array( '', '.', '', '', '' ),
                $value
        );

        if ( ! is_numeric( $value ) ) {
            return '';
        }

        $number = round( (float) $value, 2 );

        $ones = array(
                0 => 'صفر',
                1 => 'یک',
                2 => 'دو',
                3 => 'سه',
                4 => 'چهار',
                5 => 'پنج',
                6 => 'شش',
                7 => 'هفت',
                8 => 'هشت',
                9 => 'نه',
                10 => 'ده',
                11 => 'یازده',
                12 => 'دوازده',
                13 => 'سیزده',
                14 => 'چهارده',
                15 => 'پانزده',
                16 => 'شانزده',
                17 => 'هفده',
                18 => 'هجده',
                19 => 'نوزده',
        );

        $tens = array(
                20 => 'بیست',
                30 => 'سی',
                40 => 'چهل',
                50 => 'پنجاه',
                60 => 'شصت',
                70 => 'هفتاد',
                80 => 'هشتاد',
                90 => 'نود',
        );

        $hundreds = array(
                100 => 'صد',
                200 => 'دویست',
                300 => 'سیصد',
                400 => 'چهارصد',
                500 => 'پانصد',
                600 => 'ششصد',
                700 => 'هفتصد',
                800 => 'هشتصد',
                900 => 'نهصد',
        );

        $integer_to_words = function ( $integer ) use ( &$integer_to_words, $ones, $tens, $hundreds ) {
            $integer = (int) $integer;

            if ( $integer < 20 ) {
                return $ones[ $integer ];
            }

            if ( $integer < 100 ) {
                $ten       = (int) floor( $integer / 10 ) * 10;
                $remainder = $integer % 10;

                return $tens[ $ten ] . ( $remainder ? ' و ' . $ones[ $remainder ] : '' );
            }

            if ( $integer < 1000 ) {
                $hundred   = (int) floor( $integer / 100 ) * 100;
                $remainder = $integer % 100;

                return $hundreds[ $hundred ] . (
                        $remainder ? ' و ' . $integer_to_words( $remainder ) : ''
                        );
            }

            $thousands = (int) floor( $integer / 1000 );
            $remainder = $integer % 1000;

            return $integer_to_words( $thousands ) . ' هزار' . (
                    $remainder ? ' و ' . $integer_to_words( $remainder ) : ''
                    );
        };

        $formatted = rtrim( rtrim( number_format( $number, 2, '.', '' ), '0' ), '.' );
        $parts     = explode( '.', $formatted );

        $result = $integer_to_words( (int) $parts[0] );

        if ( ! empty( $parts[1] ) ) {
            $decimal_words = array();

            foreach ( str_split( $parts[1] ) as $digit ) {
                $decimal_words[] = $ones[ (int) $digit ];
            }

            $result .= ' ممیز ' . implode( ' ', $decimal_words );
        }

        return $result;
    }
}