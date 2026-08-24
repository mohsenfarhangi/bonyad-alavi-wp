<?php
/**
 * Dynamic tags exposing participation settings to Elementor.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module;

/**
 * Shared base for numeric participation settings.
 */
abstract class BA_Elementor_Participation_Number_Tag extends Data_Tag {

	/**
	 * Option key inside ba_participation_settings.
	 *
	 * @return string
	 */
	abstract protected function get_option_key();

	/**
	 * Whether thousand formatting should be enabled by default.
	 *
	 * @return bool
	 */
	protected function default_thousands_formatting() {
		return false;
	}

	/**
	 * Dynamic tag group.
	 *
	 * @return array
	 */
	public function get_group() {
		return array( 'bonyad-alavi-settings' );
	}

	/**
	 * Make the tag available to both text and numeric dynamic controls.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array(
			Module::TEXT_CATEGORY,
			Module::NUMBER_CATEGORY,
		);
	}

	/**
	 * Optional display formatting.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'format_thousands',
			array(
				'label'        => esc_html__( 'جداکننده هزارگان', 'ostadsho-child' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'فعال', 'ostadsho-child' ),
				'label_off'    => esc_html__( 'غیرفعال', 'ostadsho-child' ),
				'return_value' => 'yes',
				'default'      => $this->default_thousands_formatting() ? 'yes' : '',
			)
		);
	}

	/**
	 * Get the raw setting value and optionally format it for display.
	 *
	 * @param array $options Elementor value options.
	 * @return int|string
	 */
	public function get_value( array $options = array() ) {
		$settings = function_exists( 'ba_get_participation_settings' )
			? ba_get_participation_settings()
			: get_option( 'ba_participation_settings', array() );

		$key   = $this->get_option_key();
		$value = isset( $settings[ $key ] ) ? absint( $settings[ $key ] ) : 0;

		if ( 'yes' === $this->get_settings( 'format_thousands' ) ) {
			return number_format( $value, 0, '.', ',' );
		}

		return $value;
	}
}

/**
 * Total amount donated by benefactors.
 */
class BA_Elementor_Donated_Amount_Tag extends BA_Elementor_Participation_Number_Tag {

	public function get_name() {
		return 'ba-participation-donated-amount';
	}

	public function get_title() {
		return esc_html__( 'مبلغ واریز شده توسط خیرین', 'ostadsho-child' );
	}

	protected function get_option_key() {
		return 'donated_amount';
	}

	protected function default_thousands_formatting() {
		return true;
	}
}

/**
 * Number of completed participation projects.
 */
class BA_Elementor_Completed_Projects_Tag extends BA_Elementor_Participation_Number_Tag {

	public function get_name() {
		return 'ba-participation-completed-projects';
	}

	public function get_title() {
		return esc_html__( 'تعداد پروژه‌های انجام شده با مشارکت مردم', 'ostadsho-child' );
	}

	protected function get_option_key() {
		return 'completed_projects';
	}
}

/**
 * Total value of projects executed with benefactors.
 */
class BA_Elementor_Executed_Projects_Value_Tag extends BA_Elementor_Participation_Number_Tag {

	public function get_name() {
		return 'ba-participation-executed-projects-value';
	}

	public function get_title() {
		return esc_html__( 'ارزش پروژه‌های اجرا شده با همکاری خیرین', 'ostadsho-child' );
	}

	protected function get_option_key() {
		return 'executed_projects_value';
	}

	protected function default_thousands_formatting() {
		return true;
	}
}

/**
 * Gallery tag backed by the slider image IDs stored in participation settings.
 */
class BA_Elementor_Participation_Slider_Gallery_Tag extends Data_Tag {

	public function get_name() {
		return 'ba-participation-slider-images';
	}

	public function get_title() {
		return esc_html__( 'تصاویر اسلایدر مشارکت مردمی', 'ostadsho-child' );
	}

	public function get_group() {
		return array( 'bonyad-alavi-settings' );
	}

	public function get_categories() {
		return array( Module::GALLERY_CATEGORY );
	}

	/**
	 * Return Elementor gallery data preserving the admin-defined image order.
	 *
	 * @param array $options Elementor value options.
	 * @return array
	 */
	public function get_value( array $options = array() ) {
		$settings = function_exists( 'ba_get_participation_settings' )
			? ba_get_participation_settings()
			: get_option( 'ba_participation_settings', array() );

		$image_ids = isset( $settings['slider_images'] ) && is_array( $settings['slider_images'] )
			? $settings['slider_images']
			: array();

		$gallery = array();

		foreach ( $image_ids as $image_id ) {
			$image_id = absint( $image_id );

			if ( ! $image_id || ! wp_attachment_is_image( $image_id ) ) {
				continue;
			}

			$image_url = wp_get_attachment_image_url( $image_id, 'full' );

			if ( ! $image_url ) {
				continue;
			}

			$gallery[] = array(
				'id'  => $image_id,
				'url' => $image_url,
			);
		}

		return $gallery;
	}
}
