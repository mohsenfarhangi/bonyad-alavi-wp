<?php
/**
 * ویجت Elementor صفحه مرکز هماهنگی حرکت‌های مردمی و جهادی.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

/**
 * ویجت یکپارچه صفحه مرکز با محتوای داشبورد، Queryهای پویا و کنترل‌های کامل استایل.
 */
final class Bonyad_Alavi_Jihadi_Center_Widget extends Widget_Base {

	/**
	 * نام فنی ویجت را برمی‌گرداند.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'bonyad_alavi_jihadi_center';
	}

	/**
	 * عنوان نمایشی ویجت در Elementor را برمی‌گرداند.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'مرکز حرکت‌های مردمی و جهادی';
	}

	/**
	 * آیکون ویجت در پنل Elementor را مشخص می‌کند.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-site-identity';
	}

	/**
	 * دسته ویجت را مشخص می‌کند.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	/**
	 * Handle استایل اختصاصی ویجت را اعلام می‌کند.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'bonyad-alavi-jihadi-center-widget' );
	}

	/**
	 * Handle اسکریپت اختصاصی ویجت را اعلام می‌کند.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'bonyad-alavi-jihadi-center-widget' );
	}

	/**
	 * همه کنترل‌های محتوایی و استایل ویجت را ثبت می‌کند.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_hero_controls();
		$this->register_stats_controls();
		$this->register_intro_controls();
		$this->register_news_controls();
		$this->register_media_controls();
		$this->register_partners_controls();
		$this->register_faq_controls();
		$this->register_global_style_controls();
		$this->register_hero_style_controls();
		$this->register_stats_style_controls();
		$this->register_intro_style_controls();
		$this->register_news_style_controls();
		$this->register_media_style_controls();
		$this->register_partners_style_controls();
		$this->register_faq_style_controls();
	}

	/**
	 * کنترل‌های محتوایی Hero را ثبت می‌کند؛ متن‌های اصلی از داشبورد اولویت می‌گیرند.
	 *
	 * @return void
	 */
	private function register_hero_controls() {
		$this->start_controls_section(
			'content_hero',
			array( 'label' => 'Hero', 'tab' => Controls_Manager::TAB_CONTENT )
		);
		$this->add_control( 'hero_enabled', array( 'label' => 'نمایش سکشن', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'hero_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => 'مقادیر ذخیره‌شده در تنظیمات بنیاد علوی بر کنترل‌های متناظر این بخش اولویت دارند.', 'content_classes' => 'elementor-panel-alert elementor-panel-alert-info' ) );
		$this->add_control( 'hero_eyebrow', array( 'label' => 'متن بالای عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'بنیاد علوی' ) );
		$this->add_control( 'hero_title', array( 'label' => 'عنوان هیرو', 'type' => Controls_Manager::TEXTAREA, 'default' => 'مرکز هماهنگی حرکت های مردمی و جهادی' ) );
		$this->add_control( 'hero_button_text', array( 'label' => 'متن دکمه ثبت‌نام', 'type' => Controls_Manager::TEXT, 'default' => 'ثبت‌نام گروه‌های مردمی و جهادی' ) );
		$this->add_control( 'hero_button_url', array( 'label' => 'لینک دکمه ثبت‌نام', 'type' => Controls_Manager::URL, 'placeholder' => 'https://', 'options' => array( 'url', 'is_external', 'nofollow' ) ) );
		$this->add_control( 'hero_background', array( 'label' => 'تصویر پس‌زمینه', 'type' => Controls_Manager::MEDIA, 'default' => array() ) );
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های محتوایی پنل آمار را با Repeater مستقل Elementor ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_stats_controls() {
		$this->start_controls_section( 'content_stats', array( 'label' => 'آمار مرکز', 'tab' => Controls_Manager::TAB_CONTENT ) );
		$this->add_control( 'stats_enabled', array( 'label' => 'نمایش سکشن', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'stats_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => 'در صورت ذخیره تنظیمات آمار در داشبورد، همان مقادیر بر این بخش اولویت دارند.', 'content_classes' => 'elementor-panel-alert elementor-panel-alert-info' ) );
		$this->add_control( 'stats_title', array( 'label' => 'عنوان پنل آمار', 'type' => Controls_Manager::TEXT, 'default' => 'گزارش برخط اقدامات' ) );
		$this->add_control( 'stats_subtitle', array( 'label' => 'زیرعنوان پنل آمار', 'type' => Controls_Manager::TEXT, 'default' => 'بر پایه آمارهای شاخص منتشرشده در سال ۱۴۰۵' ) );
		$repeater = new Repeater();
		$repeater->add_control( 'number', array( 'label' => 'عدد', 'type' => Controls_Manager::TEXT ) );
		$repeater->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT ) );
		$repeater->add_control( 'subtitle', array( 'label' => 'زیرعنوان', 'type' => Controls_Manager::TEXT ) );
		$this->add_control( 'stats', array( 'label' => 'آمار', 'type' => Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'title_field' => '{{{ number || title || "آمار" }}}', 'default' => BA_Center_Settings_Service::get_defaults()['stats'] ) );
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های کامل بخش معرفی را ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_intro_controls() {
		$this->start_controls_section( 'content_intro', array( 'label' => 'معرفی مرکز', 'tab' => Controls_Manager::TAB_CONTENT ) );
		$this->add_control( 'intro_enabled', array( 'label' => 'نمایش سکشن', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'intro_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => 'مقادیر معتبر ذخیره‌شده کارت‌ها در داشبورد بر فیلد متناظر Elementor اولویت دارند؛ فیلد خالی از Elementor استفاده می‌کند.', 'content_classes' => 'elementor-panel-alert elementor-panel-alert-info' ) );
		$this->add_control( 'intro_kicker', array( 'label' => 'Kicker', 'type' => Controls_Manager::TEXT, 'default' => 'معرفی مرکز' ) );
		$this->add_control( 'intro_title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'مرکز هماهنگی حرکت‌های مردمی و جهادی' ) );
		$this->add_control( 'intro_description', array( 'label' => 'توضیحات', 'type' => Controls_Manager::WYSIWYG, 'default' => BA_Center_Settings_Service::get_defaults()['intro_description'] ) );
		$repeater = new Repeater();
		$this->add_repeater_media_choice_controls( $repeater, 'تصویر', 'آیکون / SVG' );
		$repeater->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT ) );
		$repeater->add_control( 'url', array( 'label' => 'لینک', 'type' => Controls_Manager::URL, 'options' => array( 'url', 'is_external', 'nofollow' ) ) );
		$default_cards = array();
		foreach ( BA_Center_Settings_Service::get_defaults()['system_cards'] as $card ) {
			$default_cards[] = array( 'title' => $card['title'], 'use_svg' => '', 'image' => array(), 'icon' => array(), 'url' => array() );
		}
		$this->add_control( 'system_cards', array( 'label' => 'کارت‌های بخش‌های مرکز', 'type' => Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'title_field' => '{{{ title || "کارت مرکز" }}}', 'default' => $default_cards ) );
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های محتوایی و Query اخبار را ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_news_controls() {
		$this->start_controls_section(
			'content_news',
			array(
				'label' => 'اخبار مرکز',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control( 'news_enabled', array( 'label' => 'نمایش سکشن', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'news_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => 'محتوا و Query ذخیره‌شده در داشبورد بر کنترل‌های متناظر این بخش اولویت دارند.', 'content_classes' => 'elementor-panel-alert elementor-panel-alert-info' ) );
		$this->add_control( 'news_kicker', array( 'label' => 'Kicker', 'type' => Controls_Manager::TEXT, 'default' => 'اخبار مرکز' ) );
		$this->add_control( 'news_title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'آخرین اخبار و رویدادها' ) );
		$this->add_control( 'news_subtitle', array( 'label' => 'زیرعنوان', 'type' => Controls_Manager::TEXTAREA, 'default' => 'تازه‌ترین خبرها، گزارش‌ها و رویدادهای مرتبط با فعالیت‌های مرکز و گروه‌های مردمی.' ) );
		$this->add_control( 'news_all_text', array( 'label' => 'متن مشاهده همه', 'type' => Controls_Manager::TEXT, 'default' => 'مشاهده همه اخبار' ) );
		$this->add_control( 'news_all_url', array( 'label' => 'لینک مشاهده همه', 'type' => Controls_Manager::URL, 'placeholder' => 'https://', 'options' => array( 'url', 'is_external', 'nofollow' ) ) );
		$this->end_controls_section();

		$this->register_query_controls( 'news', 'Query اخبار', 4 );
	}

	/**
	 * کنترل‌های محتوایی و Query چندرسانه‌ای را ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_media_controls() {
		$this->start_controls_section(
			'content_media',
			array(
				'label' => 'چندرسانه‌ای',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control( 'media_enabled', array( 'label' => 'نمایش سکشن', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'media_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => 'محتوا و Query ذخیره‌شده در داشبورد بر کنترل‌های متناظر این بخش اولویت دارند.', 'content_classes' => 'elementor-panel-alert elementor-panel-alert-info' ) );
		$this->add_control( 'media_kicker', array( 'label' => 'Kicker', 'type' => Controls_Manager::TEXT, 'default' => 'روایت تصویری فعالیت‌ها' ) );
		$this->add_control( 'media_title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'چندرسانه‌ای' ) );
		$this->add_control( 'media_subtitle', array( 'label' => 'زیرعنوان', 'type' => Controls_Manager::TEXTAREA, 'default' => 'ویدیوها و گزارش‌های تصویری از فعالیت‌ها، اردوها و برنامه‌های اجراشده مرکز.' ) );
		$this->add_control( 'media_all_text', array( 'label' => 'متن مشاهده همه', 'type' => Controls_Manager::TEXT, 'default' => 'مشاهده همه ویدیوها' ) );
		$this->add_control( 'media_all_url', array( 'label' => 'لینک مشاهده همه', 'type' => Controls_Manager::URL, 'placeholder' => 'https://', 'options' => array( 'url', 'is_external', 'nofollow' ) ) );
		$this->add_control(
			'media_duration_meta_key',
			array(
				'label'       => 'کلید متای مدت ویدیو (اختیاری)',
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '_video_duration',
				'description' => 'اگر Post Type انتخابی مدت ویدیو را در متا ذخیره می‌کند، نام کلید را وارد کنید. در صورت خالی بودن چیزی نمایش داده نمی‌شود.',
			)
		);
		$this->end_controls_section();

		$this->register_query_controls( 'media', 'Query چندرسانه‌ای', 3 );
	}

	/**
	 * کنترل‌های محتوایی همراهان و Repeater جایگزین را ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_partners_controls() {
		$this->start_controls_section(
			'content_partners',
			array(
				'label' => 'همراهان مرکز',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control( 'partners_enabled', array( 'label' => 'نمایش سکشن', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );

		$this->add_control(
			'partners_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'پس از ذخیره تنظیمات این بخش در داشبورد، مقادیر داشبورد بر کنترل‌های متناظر Elementor اولویت دارند.',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control( 'partners_kicker', array( 'label' => 'Kicker', 'type' => Controls_Manager::TEXT, 'default' => 'همراهان مرکز' ) );
		$this->add_control( 'partners_title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'نهادها و مجموعه‌های همکار' ) );
		$this->add_control( 'partners_subtitle', array( 'label' => 'زیرعنوان', 'type' => Controls_Manager::TEXTAREA, 'default' => 'نهادها و مجموعه‌هایی که در مسیر برنامه‌های مرکز همراه هستند.' ) );

		$repeater = new Repeater();
		$this->add_repeater_media_choice_controls( $repeater, 'تصویر', 'آیکون / SVG' );
		$repeater->add_control( 'title', array( 'label' => 'عنوان (اختیاری)', 'type' => Controls_Manager::TEXT ) );
		$repeater->add_control( 'url', array( 'label' => 'لینک (اختیاری)', 'type' => Controls_Manager::URL, 'options' => array( 'url', 'is_external', 'nofollow' ) ) );

		$this->add_control(
			'partners',
			array(
				'label'       => 'همراهان',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title || "همراه" }}}',
				'default'     => array(),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * کنترل‌های مشترک انتخاب انحصاری تصویر یا Icon/SVG را به یک Repeater اضافه می‌کند.
	 * این قرارداد بین کارت‌های سامانه و همراهان مرکز مشترک است.
	 *
	 * @param Repeater $repeater    نمونه Repeater Elementor.
	 * @param string   $image_label برچسب حالت تصویر.
	 * @param string   $icon_label  برچسب حالت آیکون.
	 * @return void
	 */
	private function add_repeater_media_choice_controls( Repeater $repeater, $image_label, $icon_label ) {
		$repeater->add_control(
			'use_svg',
			array(
				'label'        => 'استفاده از آیکون/SVG',
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);
		$repeater->add_control(
			'image',
			array(
				'label'     => $image_label,
				'type'      => Controls_Manager::MEDIA,
				'condition' => array( 'use_svg!' => 'yes' ),
			)
		);
		$repeater->add_control(
			'icon',
			array(
				'label'     => $icon_label,
				'type'      => Controls_Manager::ICONS,
				'condition' => array( 'use_svg' => 'yes' ),
			)
		);
	}

	/**
	 * کنترل‌های محتوایی FAQ و Repeater جایگزین را ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_faq_controls() {
		$this->start_controls_section(
			'content_faq',
			array(
				'label' => 'پرسش‌های متداول',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control( 'faq_enabled', array( 'label' => 'نمایش سکشن', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );

		$this->add_control(
			'faq_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'پس از ذخیره تنظیمات این بخش در داشبورد، مقادیر داشبورد بر کنترل‌های متناظر Elementor اولویت دارند.',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control( 'faq_kicker', array( 'label' => 'Kicker', 'type' => Controls_Manager::TEXT, 'default' => 'پرسش‌های متداول' ) );
		$this->add_control( 'faq_title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'پاسخ به سوالات رایج' ) );
		$this->add_control( 'faq_subtitle', array( 'label' => 'زیرعنوان', 'type' => Controls_Manager::TEXTAREA, 'default' => 'پاسخ پرسش‌های رایج کاربران سامانه‌ها و مخاطبان مرکز در این بخش قرار می‌گیرد.' ) );

		$repeater = new Repeater();
		$repeater->add_control( 'question', array( 'label' => 'سؤال', 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$repeater->add_control( 'answer', array( 'label' => 'پاسخ', 'type' => Controls_Manager::WYSIWYG ) );
		$this->add_control(
			'faqs',
			array(
				'label'       => 'پرسش‌ها',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ question || "پرسش" }}}',
				'default'     => $this->get_default_faqs(),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های مشترک Query را برای اخبار یا چندرسانه‌ای ثبت می‌کند.
	 *
	 * @param string $prefix        پیشوند فیلدها.
	 * @param string $label         عنوان سکشن.
	 * @param int    $default_count تعداد پیش‌فرض.
	 * @return void
	 */
	private function register_query_controls( $prefix, $label, $default_count ) {
		$this->start_controls_section(
			'query_' . $prefix,
			array(
				'label' => $label,
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			$prefix . '_post_types',
			array(
				'label'       => 'Post Type',
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_post_type_options(),
				'default'     => array( 'post' ),
			)
		);
		$this->add_control( $prefix . '_posts_per_page', array( 'label' => 'تعداد نمایش', 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 30, 'default' => $default_count ) );
		$this->add_control( $prefix . '_categories', array( 'label' => 'دسته‌بندی‌ها', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'label_block' => true, 'options' => $this->get_term_options( 'category' ) ) );
		$this->add_control( $prefix . '_tags', array( 'label' => 'برچسب‌ها', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'label_block' => true, 'options' => $this->get_term_options( 'post_tag' ) ) );
		$this->add_control( $prefix . '_authors', array( 'label' => 'نویسندگان', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'label_block' => true, 'options' => $this->get_author_options() ) );
		$this->add_control( $prefix . '_include_ids', array( 'label' => 'فقط شناسه نوشته‌ها', 'type' => Controls_Manager::TEXT, 'description' => 'شناسه‌ها را با ویرگول جدا کنید.' ) );
		$this->add_control( $prefix . '_exclude_ids', array( 'label' => 'حذف شناسه نوشته‌ها', 'type' => Controls_Manager::TEXT, 'description' => 'شناسه‌ها را با ویرگول جدا کنید.' ) );
		$this->add_control(
			$prefix . '_orderby',
			array(
				'label'   => 'مرتب‌سازی بر اساس',
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array(
					'date'       => 'تاریخ انتشار',
					'modified'   => 'آخرین ویرایش',
					'title'      => 'عنوان',
					'menu_order' => 'ترتیب منو',
					'rand'       => 'تصادفی',
					'ID'         => 'شناسه',
				),
			)
		);
		$this->add_control( $prefix . '_order', array( 'label' => 'ترتیب', 'type' => Controls_Manager::SELECT, 'default' => 'DESC', 'options' => array( 'DESC' => 'نزولی', 'ASC' => 'صعودی' ) ) );
		$this->add_control( $prefix . '_offset', array( 'label' => 'Offset', 'type' => Controls_Manager::NUMBER, 'min' => 0, 'default' => 0 ) );
		$this->add_control( $prefix . '_ignore_sticky', array( 'label' => 'نادیده گرفتن Sticky Posts', 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( $prefix . '_date_after', array( 'label' => 'تاریخ از', 'type' => Controls_Manager::DATE_TIME ) );
		$this->add_control( $prefix . '_date_before', array( 'label' => 'تاریخ تا', 'type' => Controls_Manager::DATE_TIME ) );
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های استایل عمومی و عرض Container را ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_global_style_controls() {
		$this->start_controls_section( 'style_global', array( 'label' => 'عمومی', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control(
			'container_width',
			array(
				'label'      => 'حداکثر عرض محتوا',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vw' ),
				'range'      => array( 'px' => array( 'min' => 720, 'max' => 1600 ), 'vw' => array( 'min' => 60, 'max' => 100 ) ),
				'default'    => array( 'size' => 1220, 'unit' => 'px' ),
				'selectors'  => array( '{{WRAPPER}} .ba-jihadi-center' => '--ba-jc-container-width: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control( 'global_green', array( 'label' => 'سبز اصلی', 'type' => Controls_Manager::COLOR, 'default' => '#069043', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center' => '--ba-jc-green: {{VALUE}};' ) ) );
		$this->add_control( 'global_green_dark', array( 'label' => 'سبز تیره', 'type' => Controls_Manager::COLOR, 'default' => '#004421', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center' => '--ba-jc-green-dark: {{VALUE}};' ) ) );
		$this->add_control( 'global_gold', array( 'label' => 'طلایی', 'type' => Controls_Manager::COLOR, 'default' => '#f5b73b', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center' => '--ba-jc-gold: {{VALUE}};' ) ) );
		$this->add_control( 'global_ink', array( 'label' => 'رنگ متن اصلی', 'type' => Controls_Manager::COLOR, 'default' => '#243028', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center' => '--ba-jc-ink: {{VALUE}};' ) ) );
		$this->add_control( 'global_muted', array( 'label' => 'رنگ متن ثانویه', 'type' => Controls_Manager::COLOR, 'default' => '#67736b', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center' => '--ba-jc-muted: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های کامل استایل Hero را ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_hero_style_controls() {
		$this->start_controls_section( 'style_hero', array( 'label' => 'Hero', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'hero_min_height', array( 'label' => 'حداقل ارتفاع', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', 'vh' ), 'range' => array( 'px' => array( 'min' => 300, 'max' => 900 ), 'vh' => array( 'min' => 30, 'max' => 100 ) ), 'default' => array( 'size' => 600, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center' => '--ba-jc-hero-height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'hero_padding', array( 'label' => 'فاصله داخلی', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%', 'em' ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__hero-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_control( 'hero_overlay', array( 'label' => 'رنگ Overlay', 'type' => Controls_Manager::COLOR, 'default' => 'rgba(0,68,33,.78)', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center' => '--ba-jc-hero-overlay: {{VALUE}};' ) ) );
		$this->add_control( 'hero_image_position', array( 'label' => 'موقعیت تصویر', 'type' => Controls_Manager::SELECT, 'default' => 'center', 'options' => array( 'center' => 'وسط', 'top' => 'بالا', 'bottom' => 'پایین', 'right' => 'راست', 'left' => 'چپ' ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__hero-image' => 'object-position: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'hero_title_typography', 'label' => 'تایپوگرافی عنوان', 'selector' => '{{WRAPPER}} .ba-jihadi-center__hero-title' ) );
		$this->add_control( 'hero_title_color', array( 'label' => 'رنگ عنوان', 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__hero-title' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'hero_eyebrow_typography', 'label' => 'تایپوگرافی متن بالا', 'selector' => '{{WRAPPER}} .ba-jihadi-center__eyebrow' ) );
		$this->add_control( 'hero_eyebrow_color', array( 'label' => 'رنگ متن بالا', 'type' => Controls_Manager::COLOR, 'default' => '#e8f2ec', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__eyebrow' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'hero_button_bg', array( 'label' => 'پس‌زمینه دکمه', 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__hero-button' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'hero_button_color', array( 'label' => 'رنگ متن دکمه', 'type' => Controls_Manager::COLOR, 'default' => '#004421', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__hero-button' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'hero_button_hover_bg', array( 'label' => 'پس‌زمینه دکمه Hover', 'type' => Controls_Manager::COLOR, 'default' => '#f2f7f4', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__hero-button:hover' => 'background: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'hero_button_padding', array( 'label' => 'فاصله داخلی دکمه', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__hero-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_control( 'hero_button_radius', array( 'label' => 'گردی دکمه', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'default' => array( 'size' => 10 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__hero-button' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'hero_button_typography', 'label' => 'تایپوگرافی دکمه', 'selector' => '{{WRAPPER}} .ba-jihadi-center__hero-button' ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'hero_button_border', 'label' => 'حاشیه دکمه', 'selector' => '{{WRAPPER}} .ba-jihadi-center__hero-button' ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'hero_button_shadow', 'label' => 'سایه دکمه', 'selector' => '{{WRAPPER}} .ba-jihadi-center__hero-button' ) );
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های استایل پنل آمار و آیتم‌های آن را ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_stats_style_controls() {
		$this->start_controls_section( 'style_stats', array( 'label' => 'آمار مرکز', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'stats_margin_top', array( 'label' => 'فاصله از بالا', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => -250, 'max' => 120 ) ), 'default' => array( 'size' => -170, 'unit' => 'px' ), 'tablet_default' => array( 'size' => -70, 'unit' => 'px' ), 'mobile_default' => array( 'size' => 0, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__stats-wrap' => 'margin-top: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'stats_panel_bg', array( 'label' => 'پس‌زمینه پنل', 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__stats-panel' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'stats_intro_bg', array( 'label' => 'پس‌زمینه عنوان پنل', 'type' => Controls_Manager::COLOR, 'default' => '#069043', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__stats-intro' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'stats_radius', array( 'label' => 'گردی پنل', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 14 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__stats-panel' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'stats_shadow', 'selector' => '{{WRAPPER}} .ba-jihadi-center__stats-panel' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'stats_heading_typography', 'label' => 'تایپوگرافی عنوان پنل', 'selector' => '{{WRAPPER}} .ba-jihadi-center__stats-title' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'stats_value_typography', 'label' => 'تایپوگرافی عدد', 'selector' => '{{WRAPPER}} .ba-jihadi-center__stat-value' ) );
		$this->add_control( 'stats_value_color', array( 'label' => 'رنگ عدد', 'type' => Controls_Manager::COLOR, 'default' => '#004421', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__stat-value' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'stats_label_typography', 'label' => 'تایپوگرافی عنوان آمار', 'selector' => '{{WRAPPER}} .ba-jihadi-center__stat-title' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'stats_subtitle_typography', 'label' => 'تایپوگرافی زیرعنوان', 'selector' => '{{WRAPPER}} .ba-jihadi-center__stat-subtitle' ) );
		$this->add_responsive_control( 'stats_item_padding', array( 'label' => 'فاصله داخلی آیتم', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__stat, {{WRAPPER}} .ba-jihadi-center__stats-intro' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های استایل معرفی مرکز و کارت‌ها را ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_intro_style_controls() {
		$this->start_controls_section( 'style_intro', array( 'label' => 'معرفی و کارت‌های مرکز', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'intro_bg', array( 'label' => 'پس‌زمینه سکشن', 'type' => Controls_Manager::COLOR, 'default' => '#f6f9f7', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__intro-section' => 'background: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'intro_padding', array( 'label' => 'فاصله داخلی سکشن', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__intro-section' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'intro_kicker_typography', 'label' => 'تایپوگرافی Kicker', 'selector' => '{{WRAPPER}} .ba-jihadi-center__kicker' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'intro_title_typography', 'label' => 'تایپوگرافی عنوان', 'selector' => '{{WRAPPER}} .ba-jihadi-center__intro-title' ) );
		$this->add_control( 'intro_title_color', array( 'label' => 'رنگ عنوان', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__intro-title' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'intro_text_typography', 'label' => 'تایپوگرافی توضیحات', 'selector' => '{{WRAPPER}} .ba-jihadi-center__intro-description' ) );
		$this->add_control( 'intro_text_color', array( 'label' => 'رنگ توضیحات', 'type' => Controls_Manager::COLOR, 'default' => '#67736b', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__intro-description' => 'color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'system_columns', array( 'label' => 'تعداد ستون کارت‌ها', 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 6, 'default' => 3, 'tablet_default' => 2, 'mobile_default' => 1, 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__systems-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0,1fr));' ) ) );
		$this->add_responsive_control( 'system_gap', array( 'label' => 'فاصله کارت‌ها', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'default' => array( 'size' => 18 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__systems-grid' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'system_card_bg', array( 'label' => 'پس‌زمینه کارت', 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__system-card' => 'background: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'system_card_border', 'selector' => '{{WRAPPER}} .ba-jihadi-center__system-card' ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'system_card_shadow', 'selector' => '{{WRAPPER}} .ba-jihadi-center__system-card' ) );
		$this->add_responsive_control( 'system_card_padding', array( 'label' => 'فاصله داخلی کارت', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__system-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_control( 'system_card_radius', array( 'label' => 'گردی کارت', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 13 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__system-card' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'system_card_min_height', array( 'label' => 'حداقل ارتفاع کارت', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 100, 'max' => 420 ) ), 'default' => array( 'size' => 205 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__system-card' => 'min-height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'system_card_hover_bg', array( 'label' => 'پس‌زمینه Hover', 'type' => Controls_Manager::COLOR, 'default' => '#fbfefc', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__system-card[href]:hover, {{WRAPPER}} .ba-jihadi-center__system-card[href]:focus-visible' => 'background: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'system_icon_size', array( 'label' => 'اندازه باکس آیکون', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 24, 'max' => 100 ) ), 'default' => array( 'size' => 54 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__system-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'system_icon_glyph_size', array( 'label' => 'اندازه خود آیکون', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 12, 'max' => 72 ) ), 'default' => array( 'size' => 30 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__system-icon img' => 'max-width: {{SIZE}}{{UNIT}}; max-height: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .ba-jihadi-center__system-icon i' => 'font-size: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .ba-jihadi-center__system-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'system_icon_bg', array( 'label' => 'پس‌زمینه آیکون', 'type' => Controls_Manager::COLOR, 'default' => '#eaf7ef', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__system-icon' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'system_icon_color', array( 'label' => 'رنگ آیکون', 'type' => Controls_Manager::COLOR, 'default' => '#069043', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__system-icon i' => 'color: {{VALUE}};', '{{WRAPPER}} .ba-jihadi-center__system-icon svg' => 'fill: {{VALUE}}; color: {{VALUE}};' ) ) );
		$this->add_control( 'system_icon_radius', array( 'label' => 'گردی باکس آیکون', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ) ), 'default' => array( 'size' => 12 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__system-icon' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'system_title_typography', 'label' => 'تایپوگرافی عنوان کارت', 'selector' => '{{WRAPPER}} .ba-jihadi-center__system-title' ) );
		$this->add_control( 'system_title_color', array( 'label' => 'رنگ عنوان کارت', 'type' => Controls_Manager::COLOR, 'default' => '#243028', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__system-title' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'system_title_hover_color', array( 'label' => 'رنگ عنوان Hover', 'type' => Controls_Manager::COLOR, 'default' => '#075433', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__system-card[href]:hover .ba-jihadi-center__system-title' => 'color: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های استایل سکشن اخبار را ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_news_style_controls() {
		$this->start_controls_section( 'style_news', array( 'label' => 'اخبار', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'news_bg', array( 'label' => 'پس‌زمینه سکشن', 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__news-section' => 'background: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'news_padding', array( 'label' => 'فاصله داخلی سکشن', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__news-section' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->register_section_heading_style_controls( 'news', '.ba-jihadi-center__news-section' );
		$this->add_responsive_control( 'news_feature_height', array( 'label' => 'ارتفاع خبر شاخص', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 240, 'max' => 700 ) ), 'default' => array( 'size' => 455 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__news-feature' => 'min-height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'news_card_radius', array( 'label' => 'گردی تصویر/کارت', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 13 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__news-feature, {{WRAPPER}} .ba-jihadi-center__news-item-image' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'news_feature_title_typography', 'label' => 'تایپوگرافی خبر شاخص', 'selector' => '{{WRAPPER}} .ba-jihadi-center__news-feature-title' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'news_item_title_typography', 'label' => 'تایپوگرافی لیست اخبار', 'selector' => '{{WRAPPER}} .ba-jihadi-center__news-item-title' ) );
		$this->add_responsive_control( 'news_list_image_width', array( 'label' => 'عرض تصویر لیست', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 70, 'max' => 240 ) ), 'default' => array( 'size' => 128 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__news-item' => 'grid-template-columns: {{SIZE}}{{UNIT}} minmax(0,1fr);', '{{WRAPPER}} .ba-jihadi-center__news-item-image' => 'width: {{SIZE}}{{UNIT}};' ) ) );
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های استایل سکشن چندرسانه‌ای را ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_media_style_controls() {
		$this->start_controls_section( 'style_media', array( 'label' => 'چندرسانه‌ای', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'media_bg', array( 'label' => 'پس‌زمینه سکشن', 'type' => Controls_Manager::COLOR, 'default' => '#075433', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__media-section' => 'background: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'media_padding', array( 'label' => 'فاصله داخلی سکشن', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__media-section' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->register_section_heading_style_controls( 'media', '.ba-jihadi-center__media-section', true );
		$this->add_responsive_control( 'media_feature_height', array( 'label' => 'ارتفاع آیتم شاخص', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 240, 'max' => 700 ) ), 'default' => array( 'size' => 455 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__media-feature' => 'min-height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'media_card_radius', array( 'label' => 'گردی کارت‌ها', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 13 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__media-feature, {{WRAPPER}} .ba-jihadi-center__media-item, {{WRAPPER}} .ba-jihadi-center__media-thumb' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'media_feature_title_typography', 'label' => 'تایپوگرافی آیتم شاخص', 'selector' => '{{WRAPPER}} .ba-jihadi-center__media-feature-title' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'media_item_title_typography', 'label' => 'تایپوگرافی آیتم‌های کوچک', 'selector' => '{{WRAPPER}} .ba-jihadi-center__media-item-title' ) );
		$this->add_control( 'media_play_bg', array( 'label' => 'پس‌زمینه آیکون پخش', 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__media-play' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'media_play_color', array( 'label' => 'رنگ آیکون پخش', 'type' => Controls_Manager::COLOR, 'default' => '#069043', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__media-play' => 'color: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های استایل همراهان را ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_partners_style_controls() {
		$this->start_controls_section( 'style_partners', array( 'label' => 'همراهان', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'partners_bg', array( 'label' => 'پس‌زمینه سکشن', 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__partners-section' => 'background: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'partners_padding', array( 'label' => 'فاصله داخلی سکشن', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__partners-section' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->register_section_heading_style_controls( 'partners', '.ba-jihadi-center__partners-section' );
		$this->add_responsive_control( 'partners_columns', array( 'label' => 'تعداد ستون', 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 8, 'default' => 6, 'tablet_default' => 3, 'mobile_default' => 2, 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__partners-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0,1fr));' ) ) );
		$this->add_responsive_control( 'partners_gap', array( 'label' => 'فاصله لوگوها', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ) ), 'default' => array( 'size' => 12 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__partners-grid' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'partner_height', array( 'label' => 'ارتفاع باکس', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 60, 'max' => 220 ) ), 'default' => array( 'size' => 104 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__partner' => 'min-height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'partner_bg', array( 'label' => 'پس‌زمینه باکس', 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__partner' => 'background: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'partner_border', 'selector' => '{{WRAPPER}} .ba-jihadi-center__partner' ) );
		$this->add_control( 'partner_radius', array( 'label' => 'گردی باکس', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 10 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__partner' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'partner_media_size', array( 'label' => 'حداکثر اندازه لوگو/آیکون', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 20, 'max' => 160 ), '%' => array( 'min' => 20, 'max' => 100 ) ), 'default' => array( 'size' => 74, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__partner-media img, {{WRAPPER}} .ba-jihadi-center__partner-media svg, {{WRAPPER}} .ba-jihadi-center__partner-media i' => 'max-width: {{SIZE}}{{UNIT}}; max-height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'partner_title_typography', 'label' => 'تایپوگرافی عنوان لوگو', 'selector' => '{{WRAPPER}} .ba-jihadi-center__partner-title' ) );
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های استایل FAQ را ثبت می‌کند.
	 *
	 * @return void
	 */
	private function register_faq_style_controls() {
		$this->start_controls_section( 'style_faq', array( 'label' => 'پرسش‌های متداول', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'faq_bg', array( 'label' => 'پس‌زمینه سکشن', 'type' => Controls_Manager::COLOR, 'default' => '#f5f7f6', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__faq-section' => 'background: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'faq_padding', array( 'label' => 'فاصله داخلی سکشن', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__faq-section' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->register_section_heading_style_controls( 'faq', '.ba-jihadi-center__faq-copy' );
		$this->add_responsive_control( 'faq_columns_gap', array( 'label' => 'فاصله دو ستون', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 10, 'max' => 140 ) ), 'default' => array( 'size' => 72 ), 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__faq-grid' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'faq_question_typography', 'label' => 'تایپوگرافی سؤال', 'selector' => '{{WRAPPER}} .ba-jihadi-center__faq-question' ) );
		$this->add_control( 'faq_question_color', array( 'label' => 'رنگ سؤال', 'type' => Controls_Manager::COLOR, 'default' => '#243028', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__faq-question' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'faq_answer_typography', 'label' => 'تایپوگرافی پاسخ', 'selector' => '{{WRAPPER}} .ba-jihadi-center__faq-answer-content' ) );
		$this->add_control( 'faq_answer_color', array( 'label' => 'رنگ پاسخ', 'type' => Controls_Manager::COLOR, 'default' => '#67736b', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__faq-answer-content' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'faq_symbol_color', array( 'label' => 'رنگ علامت', 'type' => Controls_Manager::COLOR, 'default' => '#069043', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__faq-symbol' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'faq_line_color', array( 'label' => 'رنگ جداکننده', 'type' => Controls_Manager::COLOR, 'default' => '#d8e0db', 'selectors' => array( '{{WRAPPER}} .ba-jihadi-center__faq-list, {{WRAPPER}} .ba-jihadi-center__faq-item' => 'border-color: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	/**
	 * کنترل‌های تیتر مشترک سکشن‌ها را با پیشوند مستقل ثبت می‌کند.
	 *
	 * @param string $prefix        پیشوند کنترل.
	 * @param string $scope         Selector سکشن.
	 * @param bool   $inverse       آیا رنگ‌های روشن پیش‌فرض استفاده شوند.
	 * @return void
	 */
	private function register_section_heading_style_controls( $prefix, $scope, $inverse = false ) {
		$title_color = $inverse ? '#ffffff' : '#243028';
		$text_color  = $inverse ? '#c7d9cd' : '#67736b';
		$kicker      = $inverse ? '#ddecdf' : '#069043';

		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $prefix . '_heading_title_typography', 'label' => 'تایپوگرافی عنوان سکشن', 'selector' => '{{WRAPPER}} ' . $scope . ' .ba-jihadi-center__section-title' ) );
		$this->add_control( $prefix . '_heading_title_color', array( 'label' => 'رنگ عنوان سکشن', 'type' => Controls_Manager::COLOR, 'default' => $title_color, 'selectors' => array( '{{WRAPPER}} ' . $scope . ' .ba-jihadi-center__section-title' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $prefix . '_heading_subtitle_typography', 'label' => 'تایپوگرافی زیرعنوان', 'selector' => '{{WRAPPER}} ' . $scope . ' .ba-jihadi-center__section-subtitle' ) );
		$this->add_control( $prefix . '_heading_subtitle_color', array( 'label' => 'رنگ زیرعنوان', 'type' => Controls_Manager::COLOR, 'default' => $text_color, 'selectors' => array( '{{WRAPPER}} ' . $scope . ' .ba-jihadi-center__section-subtitle' => 'color: {{VALUE}};' ) ) );
		$this->add_control( $prefix . '_heading_kicker_color', array( 'label' => 'رنگ Kicker', 'type' => Controls_Manager::COLOR, 'default' => $kicker, 'selectors' => array( '{{WRAPPER}} ' . $scope . ' .ba-jihadi-center__kicker' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $prefix . '_heading_link_typography', 'label' => 'تایپوگرافی لینک مشاهده همه', 'selector' => '{{WRAPPER}} ' . $scope . ' .ba-jihadi-center__all-link' ) );
		$this->add_control( $prefix . '_heading_link_color', array( 'label' => 'رنگ لینک مشاهده همه', 'type' => Controls_Manager::COLOR, 'default' => $inverse ? '#ffffff' : '#075433', 'selectors' => array( '{{WRAPPER}} ' . $scope . ' .ba-jihadi-center__all-link' => 'color: {{VALUE}};' ) ) );
	}

	/**
	 * گزینه‌های Post Type عمومی را برای کنترل Query می‌سازد.
	 *
	 * @return array
	 */
	private function get_post_type_options() {
		$options = array();
		$types   = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $types as $type ) {
			$options[ $type->name ] = $type->labels->singular_name ?: $type->label;
		}

		return $options;
	}

	/**
	 * گزینه‌های یک Taxonomy را برای Select2 می‌سازد.
	 *
	 * @param string $taxonomy نام Taxonomy.
	 * @return array
	 */
	private function get_term_options( $taxonomy ) {
		$options = array();
		$terms   = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );

		if ( is_wp_error( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = $term->name;
		}

		return $options;
	}

	/**
	 * فهرست نویسندگان را برای Query Builder آماده می‌کند.
	 *
	 * @return array
	 */
	private function get_author_options() {
		$options = array();
		$users   = get_users( array( 'who' => 'authors', 'fields' => array( 'ID', 'display_name' ) ) );

		foreach ( $users as $user ) {
			$options[ $user->ID ] = $user->display_name;
		}

		return $options;
	}

	/**
	 * FAQهای پیش‌فرض Elementor را بر اساس طرح اولیه برمی‌گرداند.
	 *
	 * @return array
	 */
	private function get_default_faqs() {
		return array(
			array( 'question' => 'چه گروه‌هایی می‌توانند در سامانه ثبت‌نام کنند؟', 'answer' => 'شرایط ثبت گروه‌های مردمی و جهادی و اطلاعات موردنیاز در این بخش قرار می‌گیرد.' ),
			array( 'question' => 'ثبت اطلاعات گروه چه مراحلی دارد؟', 'answer' => 'مراحل ورود، تکمیل اطلاعات و ثبت نهایی گروه در این قسمت توضیح داده می‌شود.' ),
			array( 'question' => 'بعد از ثبت اطلاعات گروه چه اتفاقی می‌افتد؟', 'answer' => 'فرایند بررسی اطلاعات و نحوه ادامه ارتباط مرکز با گروه در این بخش مشخص خواهد شد.' ),
		);
	}

	/**
	 * خروجی نهایی همه سکشن‌ها را رندر می‌کند.
	 *
	 * @return void
	 */
	protected function render() {
		$elementor = $this->get_settings_for_display();
		$settings  = BA_Center_Settings_Service::get_effective_settings( $elementor );
		?>
		<div class="ba-jihadi-center" data-ba-jihadi-center>
			<?php if ( BA_Center_Settings_Service::is_section_enabled( $settings, 'hero' ) ) { $this->render_hero( $settings ); } ?>
			<?php if ( BA_Center_Settings_Service::is_section_enabled( $settings, 'stats' ) ) { $this->render_stats( $settings ); } ?>
			<?php if ( BA_Center_Settings_Service::is_section_enabled( $settings, 'intro' ) ) { $this->render_intro( $settings ); } ?>
			<?php if ( BA_Center_Settings_Service::is_section_enabled( $settings, 'news' ) ) { $this->render_news( $settings ); } ?>
			<?php if ( BA_Center_Settings_Service::is_section_enabled( $settings, 'media' ) ) { $this->render_media( $settings ); } ?>
			<?php if ( BA_Center_Settings_Service::is_section_enabled( $settings, 'partners' ) ) { $this->render_partners( $settings ); } ?>
			<?php if ( BA_Center_Settings_Service::is_section_enabled( $settings, 'faq' ) ) { $this->render_faq( $settings ); } ?>
		</div>
		<?php
	}

	/**
	 * Hero را از تنظیمات مؤثر با اولویت داشبورد رندر می‌کند.
	 *
	 * @param array $settings تنظیمات مؤثر پس از اعمال اولویت.
	 * @return void
	 */
	private function render_hero( array $settings ) {
		$background = $this->resolve_hero_background( $settings );
		$title      = (string) ( $settings['hero_title'] ?? '' );
		$button     = (string) ( $settings['hero_button_text'] ?? '' );
		$link       = is_array( $settings['hero_button_url'] ?? null ) ? $settings['hero_button_url'] : array( 'url' => (string) ( $settings['hero_button_url'] ?? '' ) );
		?>
		<section class="ba-jihadi-center__hero">
			<div class="ba-jihadi-center__hero-media">
				<?php if ( $background ) : ?><img class="ba-jihadi-center__hero-image" src="<?php echo esc_url( $background ); ?>" alt="" fetchpriority="high"><?php endif; ?>
			</div>
			<div class="ba-jihadi-center__container ba-jihadi-center__hero-inner">
				<div class="ba-jihadi-center__hero-copy">
					<?php if ( ! empty( $settings['hero_eyebrow'] ) ) : ?><span class="ba-jihadi-center__eyebrow"><?php echo esc_html( $settings['hero_eyebrow'] ); ?></span><?php endif; ?>
					<?php if ( $title ) : ?><h1 class="ba-jihadi-center__hero-title"><?php echo esc_html( $title ); ?></h1><?php endif; ?>
					<?php if ( $button && ! empty( $link['url'] ) ) : ?><div class="ba-jihadi-center__hero-actions"><a class="ba-jihadi-center__hero-button"<?php echo $this->build_elementor_link_attributes( $link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $button ); ?></a></div><?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * تصویر Hero را بر اساس اولویت داشبورد، Elementor و فایل پیش‌فرض قالب پیدا می‌کند.
	 *
	 * @param array $settings تنظیمات مؤثر ویجت.
	 * @return string
	 */
	private function resolve_hero_background( array $settings ) {
		if ( ! empty( $settings['hero_background_id'] ) ) {
			$url = wp_get_attachment_image_url( absint( $settings['hero_background_id'] ), 'full' );
			if ( $url ) {
				return $url;
			}
		}

		if ( ! empty( $settings['hero_background']['url'] ) ) {
			return $settings['hero_background']['url'];
		}

		return get_stylesheet_directory_uri() . '/assets/images/jihadi-center/hero.jpg';
	}

	/**
	 * پنل آمار پویا را از Repeater داشبورد رندر می‌کند.
	 *
	 * @param array $settings تنظیمات مؤثر ویجت.
	 * @return void
	 */
	private function render_stats( array $settings ) {
		$stats = array_values( array_filter( (array) $settings['stats'], array( $this, 'has_stat_content' ) ) );
		if ( ! $stats && ! $settings['stats_title'] && ! $settings['stats_subtitle'] ) {
			return;
		}
		?>
		<div class="ba-jihadi-center__stats-wrap">
			<div class="ba-jihadi-center__container">
				<div class="ba-jihadi-center__stats-panel" style="--ba-jc-stat-count:<?php echo esc_attr( max( 1, count( $stats ) ) ); ?>" aria-label="آمار فعالیت‌های مرکز">
					<div class="ba-jihadi-center__stats-intro">
						<?php if ( $settings['stats_title'] ) : ?><strong class="ba-jihadi-center__stats-title"><?php echo esc_html( $settings['stats_title'] ); ?></strong><?php endif; ?>
						<?php if ( $settings['stats_subtitle'] ) : ?><span class="ba-jihadi-center__stats-subtitle"><?php echo esc_html( $settings['stats_subtitle'] ); ?></span><?php endif; ?>
					</div>
					<?php foreach ( $stats as $stat ) : ?>
						<div class="ba-jihadi-center__stat">
							<?php if ( ! empty( $stat['number'] ) ) : ?><b class="ba-jihadi-center__stat-value" data-ba-jc-counter="<?php echo esc_attr( $stat['number'] ); ?>"><?php echo esc_html( $stat['number'] ); ?></b><?php endif; ?>
							<?php if ( ! empty( $stat['title'] ) ) : ?><span class="ba-jihadi-center__stat-title"><?php echo esc_html( $stat['title'] ); ?></span><?php endif; ?>
							<?php if ( ! empty( $stat['subtitle'] ) ) : ?><small class="ba-jihadi-center__stat-subtitle"><?php echo esc_html( $stat['subtitle'] ); ?></small><?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * مشخص می‌کند یک ردیف آمار حداقل یک مقدار قابل نمایش دارد یا خیر.
	 *
	 * @param mixed $stat ردیف آمار.
	 * @return bool
	 */
	private function has_stat_content( $stat ) {
		return is_array( $stat ) && ( ! empty( $stat['number'] ) || ! empty( $stat['title'] ) || ! empty( $stat['subtitle'] ) );
	}

	/**
	 * بخش معرفی و کارت‌های سامانه را رندر می‌کند.
	 *
	 * @param array $settings تنظیمات مؤثر ویجت.
	 * @return void
	 */
	private function render_intro( array $settings ) {
		$cards = array_values( array_filter( (array) ( $settings['system_cards'] ?? array() ), array( $this, 'has_system_card_content' ) ) );
		?>
		<section class="ba-jihadi-center__intro-section" id="ba-jihadi-center-systems">
			<div class="ba-jihadi-center__container">
				<div class="ba-jihadi-center__intro-head">
					<?php $this->render_kicker( $settings['intro_kicker'] ?? '' ); ?>
					<?php if ( ! empty( $settings['intro_title'] ) ) : ?><h2 class="ba-jihadi-center__intro-title"><?php echo esc_html( $settings['intro_title'] ); ?></h2><?php endif; ?>
					<?php if ( ! empty( $settings['intro_description'] ) ) : ?><div class="ba-jihadi-center__intro-description"><?php echo wp_kses_post( wpautop( $settings['intro_description'] ) ); ?></div><?php endif; ?>
				</div>
				<?php if ( $cards ) : ?><div class="ba-jihadi-center__systems-grid"><?php foreach ( $cards as $card ) { $this->render_system_card( $card ); } ?></div><?php endif; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * مشخص می‌کند کارت سامانه قابل رندر است یا خیر.
	 *
	 * @param mixed $card داده کارت.
	 * @return bool
	 */
	private function has_system_card_content( $card ) {
		if ( ! is_array( $card ) ) {
			return false;
		}
		$has_media = ! empty( $card['image_id'] ) || ! empty( $card['svg_id'] ) || ! empty( $card['image']['url'] ) || ! empty( $card['icon']['value'] );
		$has_url   = ! empty( $card['url'] ) && ( is_string( $card['url'] ) || ! empty( $card['url']['url'] ) );
		return $has_media || ! empty( $card['title'] ) || $has_url;
	}

	/**
	 * یک کارت سامانه را با Tag مناسب لینک یا div رندر می‌کند.
	 *
	 * @param array $card داده مؤثر کارت پس از Resolve داشبورد و Elementor.
	 * @return void
	 */
	private function render_system_card( array $card ) {
		$link = is_array( $card['url'] ?? null ) ? $card['url'] : array( 'url' => (string) ( $card['url'] ?? '' ) );
		$tag  = ! empty( $link['url'] ) ? 'a' : 'div';
		?>
		<<?php echo esc_attr( $tag ); ?> class="ba-jihadi-center__system-card"<?php echo 'a' === $tag ? $this->build_elementor_link_attributes( $link ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php $this->render_system_card_icon( $card ); ?>
			<?php if ( ! empty( $card['title'] ) ) : ?><h3 class="ba-jihadi-center__system-title"><?php echo esc_html( $card['title'] ); ?></h3><?php endif; ?>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	/**
	 * آیکون کارت سامانه را از رسانه داشبورد یا Icon Control استاندارد Elementor رندر می‌کند.
	 * برای داده‌های قدیمی MEDIA در Elementor نیز fallback تصویری حفظ شده است.
	 *
	 * @param array $card داده کارت.
	 * @return void
	 */
	private function render_system_card_icon( array $card ) {
		$media_type = 'svg' === ( $card['media_type'] ?? 'image' ) ? 'svg' : 'image';

		if ( 'image' === $media_type ) {
			$image_id = absint( $card['image_id'] ?? 0 );
			if ( $image_id ) {
				$image = wp_get_attachment_image( $image_id, 'thumbnail', false, array( 'loading' => 'lazy', 'alt' => BA_Media_Helper::get_attachment_alt( $image_id ) ) );
				if ( $image ) {
					echo '<span class="ba-jihadi-center__system-icon ba-jihadi-center__system-icon--image">' . $image . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				return;
			}

			if ( ! empty( $card['image']['url'] ) ) {
				printf( '<span class="ba-jihadi-center__system-icon ba-jihadi-center__system-icon--image"><img src="%s" alt="" loading="lazy"></span>', esc_url( $card['image']['url'] ) );
			}
			return;
		}

		$svg_id = absint( $card['svg_id'] ?? 0 );
		if ( $svg_id ) {
			$svg = BA_Media_Helper::get_inline_svg_attachment( $svg_id );
			if ( $svg ) {
				echo '<span class="ba-jihadi-center__system-icon ba-jihadi-center__system-icon--svg">' . $svg . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			return;
		}

		$icon = is_array( $card['icon'] ?? null ) ? $card['icon'] : array();
		if ( ! empty( $icon['value'] ) ) {
			echo '<span class="ba-jihadi-center__system-icon ba-jihadi-center__system-icon--svg">';
			$this->render_elementor_system_icon( $icon );
			echo '</span>';
		}
	}

	/**
	 * آیکون Elementor کارت سامانه را رندر می‌کند و برای SVG آپلودی خروجی Inline تولید می‌کند.
	 * آیکون‌های کتابخانه‌ای Elementor برای سازگاری از Icons Manager عبور می‌کنند.
	 *
	 * @param array $icon تنظیم Icon Control Elementor.
	 * @return void
	 */
	private function render_elementor_system_icon( array $icon ) {
		$value = $icon['value'] ?? null;
		if ( 'svg' === ( $icon['library'] ?? '' ) && is_array( $value ) ) {
			$attachment_id = absint( $value['id'] ?? 0 );
			if ( $attachment_id ) {
				$svg = BA_Media_Helper::get_inline_svg_attachment( $attachment_id );
				if ( $svg ) {
					echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					return;
				}
			}
		}

		Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) );
	}

	/**
	 * سکشن اخبار را از Query مستقل Elementor رندر می‌کند.
	 *
	 * @param array $settings تنظیمات Elementor.
	 * @return void
	 */
	private function render_news( array $settings ) {
		$query = ( new BA_Content_Query_Service() )->create_query( $settings, 'news' );
		$posts = $query->posts;
		?>
		<section class="ba-jihadi-center__news-section" id="ba-jihadi-center-news">
			<div class="ba-jihadi-center__container">
				<?php $this->render_section_heading( $settings['news_kicker'], $settings['news_title'], $settings['news_subtitle'], $settings['news_all_text'], $settings['news_all_url'] ); ?>
				<?php if ( $posts ) : ?>
					<div class="ba-jihadi-center__news-grid">
						<?php $this->render_news_feature( $posts[0] ); ?>
						<?php if ( count( $posts ) > 1 ) : ?><div class="ba-jihadi-center__news-list"><?php foreach ( array_slice( $posts, 1 ) as $post ) { $this->render_news_item( $post ); } ?></div><?php endif; ?>
					</div>
				<?php else : ?>
					<?php $this->render_empty_query_message(); ?>
				<?php endif; ?>
			</div>
		</section>
		<?php
		wp_reset_postdata();
	}

	/**
	 * خبر شاخص را رندر می‌کند.
	 *
	 * @param WP_Post $post نوشته.
	 * @return void
	 */
	private function render_news_feature( $post ) {
		?>
		<a class="ba-jihadi-center__news-feature" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
			<?php $this->render_post_image( $post, 'large', 'ba-jihadi-center__news-feature-image' ); ?>
			<div class="ba-jihadi-center__news-feature-content"><time class="ba-jihadi-center__post-date"><?php echo esc_html( get_the_date( '', $post ) ); ?></time><h3 class="ba-jihadi-center__news-feature-title"><?php echo esc_html( get_the_title( $post ) ); ?></h3></div>
		</a>
		<?php
	}

	/**
	 * یک خبر کوچک در لیست کناری را رندر می‌کند.
	 *
	 * @param WP_Post $post نوشته.
	 * @return void
	 */
	private function render_news_item( $post ) {
		?>
		<a class="ba-jihadi-center__news-item" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
			<?php $this->render_post_image( $post, 'medium', 'ba-jihadi-center__news-item-image' ); ?>
			<div class="ba-jihadi-center__news-item-copy"><time class="ba-jihadi-center__post-date"><?php echo esc_html( get_the_date( '', $post ) ); ?></time><h3 class="ba-jihadi-center__news-item-title"><?php echo esc_html( get_the_title( $post ) ); ?></h3></div>
		</a>
		<?php
	}

	/**
	 * سکشن چندرسانه‌ای را از Query مستقل Elementor رندر می‌کند.
	 *
	 * @param array $settings تنظیمات Elementor.
	 * @return void
	 */
	private function render_media( array $settings ) {
		$query = ( new BA_Content_Query_Service() )->create_query( $settings, 'media' );
		$posts = $query->posts;
		?>
		<section class="ba-jihadi-center__media-section" id="ba-jihadi-center-media">
			<div class="ba-jihadi-center__container">
				<?php $this->render_section_heading( $settings['media_kicker'], $settings['media_title'], $settings['media_subtitle'], $settings['media_all_text'], $settings['media_all_url'] ); ?>
				<?php if ( $posts ) : ?>
					<div class="ba-jihadi-center__media-grid">
						<?php $this->render_media_feature( $posts[0], $settings ); ?>
						<?php if ( count( $posts ) > 1 ) : ?><div class="ba-jihadi-center__media-stack"><?php foreach ( array_slice( $posts, 1 ) as $post ) { $this->render_media_item( $post, $settings ); } ?></div><?php endif; ?>
					</div>
				<?php else : ?>
					<?php $this->render_empty_query_message( true ); ?>
				<?php endif; ?>
			</div>
		</section>
		<?php
		wp_reset_postdata();
	}

	/**
	 * آیتم شاخص چندرسانه‌ای را رندر می‌کند.
	 *
	 * @param WP_Post $post     نوشته.
	 * @param array   $settings تنظیمات.
	 * @return void
	 */
	private function render_media_feature( $post, array $settings ) {
		$duration = $this->get_media_duration( $post->ID, $settings );
		?>
		<a class="ba-jihadi-center__media-feature" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
			<?php $this->render_post_image( $post, 'large', 'ba-jihadi-center__media-feature-image' ); ?>
			<span class="ba-jihadi-center__media-play" aria-hidden="true"><?php echo $this->get_play_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<div class="ba-jihadi-center__media-feature-content"><?php if ( $duration ) : ?><span class="ba-jihadi-center__duration"><?php echo esc_html( $duration ); ?></span><?php endif; ?><h3 class="ba-jihadi-center__media-feature-title"><?php echo esc_html( get_the_title( $post ) ); ?></h3></div>
		</a>
		<?php
	}

	/**
	 * یک آیتم کوچک چندرسانه‌ای را رندر می‌کند.
	 *
	 * @param WP_Post $post     نوشته.
	 * @param array   $settings تنظیمات.
	 * @return void
	 */
	private function render_media_item( $post, array $settings ) {
		$duration = $this->get_media_duration( $post->ID, $settings );
		?>
		<a class="ba-jihadi-center__media-item" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
			<div class="ba-jihadi-center__media-thumb"><?php $this->render_post_image( $post, 'medium_large', 'ba-jihadi-center__media-item-image' ); ?><span class="ba-jihadi-center__media-play ba-jihadi-center__media-play--small" aria-hidden="true"><?php echo $this->get_play_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></div>
			<div class="ba-jihadi-center__media-item-copy"><?php if ( $duration ) : ?><span class="ba-jihadi-center__duration"><?php echo esc_html( $duration ); ?></span><?php endif; ?><h3 class="ba-jihadi-center__media-item-title"><?php echo esc_html( get_the_title( $post ) ); ?></h3><span class="ba-jihadi-center__media-date"><?php echo esc_html( get_the_date( '', $post ) ); ?></span></div>
		</a>
		<?php
	}

	/**
	 * مدت ویدیو را از کلید متای اختیاری تنظیم‌شده می‌خواند.
	 *
	 * @param int   $post_id  شناسه نوشته.
	 * @param array $settings تنظیمات.
	 * @return string
	 */
	private function get_media_duration( $post_id, array $settings ) {
		$key = isset( $settings['media_duration_meta_key'] ) ? sanitize_key( $settings['media_duration_meta_key'] ) : '';
		return $key ? sanitize_text_field( (string) get_post_meta( $post_id, $key, true ) ) : '';
	}

	/**
	 * SVG ساده آیکون پخش را برمی‌گرداند.
	 *
	 * @return string
	 */
	private function get_play_icon() {
		return '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="m9 7 8 5-8 5V7Z"></path></svg>';
	}

	/**
	 * سکشن همراهان را با اولویت سطح سکشن برای داشبورد رندر می‌کند.
	 *
	 * @param array $settings تنظیمات مؤثر ویجت.
	 * @return void
	 */
	private function render_partners( array $settings ) {
		$use_dashboard = BA_Center_Settings_Service::has_dashboard_override( 'partners' ) && BA_Center_Settings_Service::has_dashboard_partners();
		$partners      = (array) ( $settings['partners'] ?? array() );
		$kicker        = $settings['partners_kicker'] ?? '';
		$title         = $settings['partners_title'] ?? '';
		$subtitle      = $settings['partners_subtitle'] ?? '';
		$partners      = array_values( array_filter( $partners, $use_dashboard ? array( $this, 'is_dashboard_partner_valid' ) : array( $this, 'is_elementor_partner_valid' ) ) );

		if ( ! $partners && ! $title && ! $subtitle && ! $kicker ) {
			return;
		}
		?>
		<section class="ba-jihadi-center__partners-section" id="ba-jihadi-center-partners">
			<div class="ba-jihadi-center__container">
				<?php $this->render_section_heading( $kicker, $title, $subtitle ); ?>
				<?php if ( $partners ) : ?><div class="ba-jihadi-center__partners-grid"><?php foreach ( $partners as $partner ) { $this->render_partner( $partner, $use_dashboard ); } ?></div><?php endif; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * اعتبار همراه داشبورد را بر اساس رسانه یا عنوان بررسی می‌کند.
	 *
	 * @param mixed $partner داده همراه.
	 * @return bool
	 */
	private function is_dashboard_partner_valid( $partner ) {
		return is_array( $partner ) && ( ! empty( $partner['image_id'] ) || ! empty( $partner['svg_id'] ) || ! empty( $partner['title'] ) );
	}

	/**
	 * اعتبار همراه Elementor را بر اساس رسانه یا عنوان بررسی می‌کند.
	 *
	 * @param mixed $partner داده همراه.
	 * @return bool
	 */
	private function is_elementor_partner_valid( $partner ) {
		if ( ! is_array( $partner ) ) {
			return false;
		}

		$has_image = empty( $partner['use_svg'] ) && ! empty( $partner['image']['url'] );
		$has_icon  = 'yes' === ( $partner['use_svg'] ?? '' ) && ! empty( $partner['icon']['value'] );
		return $has_image || $has_icon || ! empty( $partner['title'] );
	}

	/**
	 * یک همراه را با لینک اختیاری و بدون خروجی خالی رندر می‌کند.
	 *
	 * @param array $partner       داده همراه.
	 * @param bool  $from_dashboard منبع داده داشبورد است یا خیر.
	 * @return void
	 */
	private function render_partner( array $partner, $from_dashboard ) {
		$url = $from_dashboard ? ( $partner['url'] ?? '' ) : ( $partner['url']['url'] ?? '' );
		$tag = $url ? 'a' : 'div';
		$attrs = '';
		if ( ! $from_dashboard && $url ) {
			$attrs = $this->build_elementor_link_attributes( $partner['url'] );
		} elseif ( $url ) {
			$attrs = ' href="' . esc_url( $url ) . '"';
		}
		?>
		<<?php echo esc_attr( $tag ); ?> class="ba-jihadi-center__partner"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( $from_dashboard ) : ?>
				<?php $this->render_dashboard_partner_media( $partner ); ?>
			<?php else : ?>
				<?php $this->render_elementor_partner_media( $partner ); ?>
			<?php endif; ?>
			<?php if ( ! empty( $partner['title'] ) ) : ?><span class="ba-jihadi-center__partner-title"><?php echo esc_html( $partner['title'] ); ?></span><?php endif; ?>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
	}

	/**
	 * رسانه یک همراه داشبورد را رندر می‌کند.
	 *
	 * @param array $partner داده همراه.
	 * @return void
	 */
	private function render_dashboard_partner_media( array $partner ) {
		$id = 'svg' === ( $partner['media_type'] ?? 'image' ) ? absint( $partner['svg_id'] ?? 0 ) : absint( $partner['image_id'] ?? 0 );
		if ( ! $id ) {
			return;
		}
		$url = wp_get_attachment_url( $id );
		if ( $url ) {
			printf( '<span class="ba-jihadi-center__partner-media"><img src="%s" alt="" loading="lazy"></span>', esc_url( $url ) );
		}
	}

	/**
	 * رسانه یک همراه Elementor را بر اساس Switcher تصویر یا آیکون رندر می‌کند.
	 *
	 * @param array $partner داده همراه.
	 * @return void
	 */
	private function render_elementor_partner_media( array $partner ) {
		if ( 'yes' === ( $partner['use_svg'] ?? '' ) && ! empty( $partner['icon']['value'] ) ) {
			echo '<span class="ba-jihadi-center__partner-media">';
			Icons_Manager::render_icon( $partner['icon'], array( 'aria-hidden' => 'true' ) );
			echo '</span>';
			return;
		}

		if ( ! empty( $partner['image']['url'] ) ) {
			printf( '<span class="ba-jihadi-center__partner-media"><img src="%s" alt="" loading="lazy"></span>', esc_url( $partner['image']['url'] ) );
		}
	}

	/**
	 * ویژگی‌های لینک Elementor را به رشته امن HTML تبدیل می‌کند.
	 *
	 * @param array $link تنظیم لینک Elementor.
	 * @return string
	 */
	private function build_elementor_link_attributes( array $link ) {
		$attrs = ' href="' . esc_url( $link['url'] ?? '' ) . '"';
		if ( ! empty( $link['is_external'] ) ) {
			$attrs .= ' target="_blank"';
		}
		$rel = array();
		if ( ! empty( $link['nofollow'] ) ) {
			$rel[] = 'nofollow';
		}
		if ( ! empty( $link['is_external'] ) ) {
			$rel[] = 'noopener';
		}
		if ( $rel ) {
			$attrs .= ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"';
		}
		return $attrs;
	}

	/**
	 * FAQ را با اولویت سطح سکشن برای تنظیمات داشبورد رندر می‌کند.
	 *
	 * @param array $settings تنظیمات مؤثر ویجت.
	 * @return void
	 */
	private function render_faq( array $settings ) {
		$faqs     = (array) ( $settings['faqs'] ?? array() );
		$kicker   = $settings['faq_kicker'] ?? '';
		$title    = $settings['faq_title'] ?? '';
		$subtitle = $settings['faq_subtitle'] ?? '';
		$faqs          = array_values( array_filter( $faqs, array( $this, 'is_faq_valid' ) ) );

		if ( ! $faqs && ! $title && ! $subtitle && ! $kicker ) {
			return;
		}
		?>
		<section class="ba-jihadi-center__faq-section" id="ba-jihadi-center-faq">
			<div class="ba-jihadi-center__container ba-jihadi-center__faq-grid">
				<div class="ba-jihadi-center__faq-copy"><?php $this->render_section_heading( $kicker, $title, $subtitle ); ?></div>
				<?php if ( $faqs ) : ?>
					<div class="ba-jihadi-center__faq-list">
						<?php foreach ( $faqs as $index => $faq ) : ?>
							<div class="ba-jihadi-center__faq-item<?php echo 0 === $index ? ' ba-jihadi-center__faq-item--open' : ''; ?>" data-ba-jc-faq-item>
								<button class="ba-jihadi-center__faq-question" type="button" aria-expanded="<?php echo 0 === $index ? 'true' : 'false'; ?>" data-ba-jc-faq-button><span><?php echo esc_html( $faq['question'] ?? '' ); ?></span><span class="ba-jihadi-center__faq-symbol" aria-hidden="true"><?php echo 0 === $index ? '−' : '+'; ?></span></button>
								<div class="ba-jihadi-center__faq-answer"><div class="ba-jihadi-center__faq-answer-inner"><div class="ba-jihadi-center__faq-answer-content"><?php echo wp_kses_post( wpautop( $faq['answer'] ?? '' ) ); ?></div></div></div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * اعتبار یک پرسش متداول را بررسی می‌کند.
	 *
	 * @param mixed $faq داده FAQ.
	 * @return bool
	 */
	private function is_faq_valid( $faq ) {
		return is_array( $faq ) && ( ! empty( $faq['question'] ) || ! empty( $faq['answer'] ) );
	}

	/**
	 * سرصفحه مشترک سکشن را با لینک مشاهده همه اختیاری رندر می‌کند.
	 *
	 * @param string $kicker    Kicker.
	 * @param string $title     عنوان.
	 * @param string $subtitle  زیرعنوان.
	 * @param string $link_text متن لینک.
	 * @param array  $link      تنظیم لینک Elementor.
	 * @return void
	 */
	private function render_section_heading( $kicker, $title, $subtitle, $link_text = '', $link = array() ) {
		?>
		<div class="ba-jihadi-center__section-head">
			<div class="ba-jihadi-center__section-head-copy">
				<?php $this->render_kicker( $kicker ); ?>
				<?php if ( $title ) : ?><h2 class="ba-jihadi-center__section-title"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
				<?php if ( $subtitle ) : ?><p class="ba-jihadi-center__section-subtitle"><?php echo esc_html( $subtitle ); ?></p><?php endif; ?>
			</div>
			<?php if ( $link_text && ! empty( $link['url'] ) ) : ?><a class="ba-jihadi-center__all-link"<?php echo $this->build_elementor_link_attributes( $link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $link_text ); ?><span aria-hidden="true">←</span></a><?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Kicker مشترک را رندر می‌کند.
	 *
	 * @param string $text متن Kicker.
	 * @return void
	 */
	private function render_kicker( $text ) {
		if ( $text ) {
			printf( '<span class="ba-jihadi-center__kicker">%s</span>', esc_html( $text ) );
		}
	}

	/**
	 * تصویر شاخص نوشته یا Placeholder داخلی را رندر می‌کند.
	 *
	 * @param WP_Post $post  نوشته.
	 * @param string  $size  اندازه تصویر.
	 * @param string  $class کلاس BEM.
	 * @return void
	 */
	private function render_post_image( $post, $size, $class ) {
		if ( has_post_thumbnail( $post ) ) {
			echo get_the_post_thumbnail( $post->ID, $size, array( 'class' => $class, 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}
		printf( '<span class="%s ba-jihadi-center__post-placeholder" aria-hidden="true"></span>', esc_attr( $class ) );
	}

	/**
	 * پیام خالی بودن Query را فقط در ویرایشگر Elementor نمایش می‌دهد.
	 *
	 * @param bool $inverse آیا سکشن پس‌زمینه تیره دارد.
	 * @return void
	 */
	private function render_empty_query_message( $inverse = false ) {
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			printf( '<div class="ba-jihadi-center__query-empty%s">موردی برای Query فعلی پیدا نشد.</div>', $inverse ? ' ba-jihadi-center__query-empty--inverse' : '' );
		}
	}
}
