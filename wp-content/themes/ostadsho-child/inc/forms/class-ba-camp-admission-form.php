<?php
declare( strict_types=1 );

use BonyadAlavi\FormEngine\Form\Form;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Form\Step;
use BonyadAlavi\FormEngine\Form\Fields\{
	DateField,
	HtmlBlock,
	NumberField,
	TelField,
	TextField
};
use BonyadAlavi\FormEngine\Localization\LocaleDateService;
use BonyadAlavi\FormEngine\Security\Validators;
use BonyadAlavi\FormEngine\Validation\ValidatorDefinition;
use BonyadAlavi\FormEngine\Validation\ValidatorRegistry;

/**
 * Source Definition فرم پذیرش مجتمع اردوگاهی امام خمینی (ره).
 *
 * ساختار و اعتبارسنجی اختصاصی فرم در قالب نگهداری می‌شود؛ رندر، ثبت درخواست،
 * ذخیره‌سازی و مدیریت Actionها بر عهده Alavi Form Engine است.
 */
final class BA_Camp_Admission_Form {
	public const SLUG = 'imam-khomeini-camp-admission';

	public static function register( FormRegistry $registry ): void {
		$registry->register( ( new self() )->build() );
	}

	/**
	 * اعتبارسنج‌های بین‌فیلدی فرم را در Registry عمومی AFE ثبت می‌کند.
	 *
	 * AFE در callback اعتبارسنج، داده نرمال‌شده تمام فیلدهای فرم را نیز می‌فرستد؛
	 * بنابراین محاسبه و کنترل تاریخ‌ها فقط به JavaScript مرورگر متکی نیست.
	 */
	public static function registerValidators( ValidatorRegistry $registry ): void {
		$registry->register( new ValidatorDefinition(
			'ba_camp_positive_integer',
			'عدد صحیح مثبت برای فرم پذیرش اردوگاه',
			[ 'number' ],
			'تعداد مهمانان باید یک عدد صحیح و حداقل یک نفر باشد.',
			static function ( string $value ): bool {
				$value = Validators::latinDigits( trim( $value ) );

				return 1 === preg_match( '/^[0-9]+$/D', $value ) && (int) $value > 0;
			}
		) );

		$registry->register( new ValidatorDefinition(
			'ba_camp_end_date_after_start',
			'تاریخ پایان بعد از تاریخ شروع',
			[ 'date' ],
			'تاریخ پایان باید حداقل یک روز بعد از تاریخ شروع باشد.',
			static function ( string $value, array $field, array $config, array $data ): bool {
				$start_field = (string) ( $config['start_field'] ?? 'from_date' );
				$duration    = self::durationBetweenJalaliDates(
					(string) ( $data[ $start_field ] ?? '' ),
					$value
				);

				return null !== $duration && $duration > 0;
			}
		) );

		$registry->register( new ValidatorDefinition(
			'ba_camp_duration_matches_dates',
			'تطابق مدت اقامت با بازه تاریخ',
			[ 'number' ],
			'مدت اقامت با تاریخ شروع و پایان مطابقت ندارد. تاریخ‌ها را دوباره بررسی کنید.',
			static function ( string $value, array $field, array $config, array $data ): bool {
				$duration = self::durationBetweenJalaliDates(
					(string) ( $data['from_date'] ?? '' ),
					(string) ( $data['to_date'] ?? '' )
				);
				$value    = Validators::latinDigits( trim( $value ) );

				return null !== $duration
					&& $duration > 0
					&& 1 === preg_match( '/^[0-9]+$/D', $value )
					&& (int) $value > 0
					&& (int) $value === $duration;
			}
		) );
	}

	/**
	 * اختلاف دو تاریخ شمسی را بر حسب شب برمی‌گرداند.
	 *
	 * مقدار منفی یعنی تاریخ پایان قبل از شروع و صفر یعنی اقامت بدون شب؛
	 * تاریخ نامعتبر نیز null برمی‌گرداند.
	 */
	public static function durationBetweenJalaliDates( string $start, string $end ): ?int {
		$start_day = self::jalaliDayNumber( $start );
		$end_day   = self::jalaliDayNumber( $end );

		if ( null === $start_day || null === $end_day ) {
			return null;
		}

		return $end_day - $start_day;
	}

	private static function jalaliDayNumber( string $value ): ?int {
		$value = Validators::latinDigits( trim( $value ) );

		if ( ! Validators::jalaliDate( $value ) ) {
			return null;
		}

		[ $year, $month, $day ] = array_map( 'intval', explode( '/', $value ) );
		[ $gy, $gm, $gd ]       = ( new LocaleDateService() )->jalaliToGregorian( $year, $month, $day );
		$timestamp              = gmmktime( 0, 0, 0, $gm, $gd, $gy );

		return false === $timestamp ? null : intdiv( $timestamp, 86400 );
	}

	public function build(): Form {
		return Form::make( self::SLUG )
			->title( 'فرم پذیرش مجتمع اردوگاهی امام خمینی (ره)' )
			->description( 'برای ثبت درخواست پذیرش، اطلاعات سازمان، نماینده و بازه حضور مهمانان را تکمیل کنید.' )
			->template( <<<'HTML'
<section class="ba-camp-admission">
	<header class="ba-camp-admission__header">
		<div class="ba-camp-admission__brand">
			{{brand_mark}}
			<span>بنیاد علوی <i aria-hidden="true"></i> مجتمع اردوگاهی امام خمینی (ره)</span>
		</div>
		<span class="ba-camp-admission__eyebrow">درخواست پذیرش و میزبانی</span>
		<h2>{{title}}</h2>
		<p>{{description}}</p>
	</header>
	<div class="ba-camp-admission__content">{{steps}}</div>
</section>
HTML
			)
			->steps( [
				Step::make( 'admission', 'مشخصات درخواست پذیرش' )->fields( [
					HtmlBlock::make(
						'<div class="ba-camp-admission__section-heading"><span>۰۱</span><div><strong>اطلاعات سازمان و نماینده</strong><small>مشخصات مجموعه متقاضی و فرد پاسخ‌گو برای هماهنگی پذیرش</small></div></div>'
					),
					TextField::make( 'organization_name' )
						->label( 'نام سازمان' )
						->placeholder( 'نام سازمان یا مجموعه متقاضی' )
						->required()
						->width( 12 ),
					TextField::make( 'representative_name' )
						->label( 'نام و نام خانوادگی نماینده' )
						->placeholder( 'نام و نام خانوادگی نماینده' )
						->attributes( [ 'autocomplete' => 'name' ] )
						->required()
						->width( 6 ),
					TelField::make( 'representative_mobile' )
						->label( 'شماره تماس نماینده' )
						->default( '09' )
						->rule( 'mobile_09' )
						->inputMask( 'mobile_ir' )
						->attributes( [
							'maxlength'                => 11,
							'minlength'                => 11,
							'pattern'                  => '09[0-9]{9}',
							'inputmode'                => 'numeric',
							'autocomplete'             => 'tel',
							'data-afe-digits-only'     => '1',
							'data-afe-fixed-prefix'    => '09',
							'data-afe-invalid-message' => 'شماره تماس باید دقیقاً ۱۱ رقم و با ۰۹ شروع شود.',
						] )
						->required()
						->width( 6 ),

					HtmlBlock::make(
						'<div class="ba-camp-admission__section-heading ba-camp-admission__section-heading--request"><span>۰۲</span><div><strong>مشخصات اقامت</strong><small>تعداد مهمانان و تاریخ ورود و خروج را مشخص کنید</small></div></div>'
					),
					NumberField::make( 'guest_count' )
						->label( 'تعداد مهمانان' )
						->placeholder( 'تعداد افراد' )
						->attributes( [ 'min' => 1, 'step' => 1, 'data-afe-invalid-message' => 'تعداد مهمانان باید حداقل یک نفر باشد.' ] )
						->meta( 'validators', [
							[
								'key'     => 'ba_camp_positive_integer',
								'message' => 'تعداد مهمانان باید یک عدد صحیح و حداقل یک نفر باشد.',
							],
						] )
						->required()
						->width( 12 ),
					DateField::make( 'from_date' )
						->label( 'از تاریخ' )
						->placeholder( 'انتخاب تاریخ شروع' )
						->jalali()
						->combined()
						->required()
						->width( 6 ),
					DateField::make( 'to_date' )
						->label( 'تا تاریخ' )
						->placeholder( 'انتخاب تاریخ پایان' )
						->jalali()
						->combined()
						->meta( 'validators', [
							[
								'key'        => 'ba_camp_end_date_after_start',
								'start_field' => 'from_date',
								'message'    => 'تاریخ پایان باید حداقل یک روز بعد از تاریخ شروع باشد.',
							],
						] )
						->required()
						->width( 6 ),
					NumberField::make( 'stay_duration' )
						->label( 'مدت اقامت (شب)' )
						->description( 'این مقدار پس از انتخاب تاریخ شروع و پایان به‌صورت خودکار محاسبه می‌شود.' )
						->attributes( [
							'min'                       => 1,
							'step'                      => 1,
							'data-afe-camp-duration'    => '1',
							'data-afe-invalid-message'  => 'مدت اقامت باید با تاریخ‌های انتخاب‌شده مطابقت داشته باشد.',
						] )
						->meta( 'validators', [
							[
								'key'     => 'ba_camp_duration_matches_dates',
								'message' => 'مدت اقامت با تاریخ شروع و پایان مطابقت ندارد. تاریخ‌ها را دوباره بررسی کنید.',
							],
						] )
						->required()
						->width( 12 ),
				] ),
			] )
			->settings( [
				'wizard'                   => false,
				'save_draft'               => false,
				'show_progress'            => false,
				'editing_enabled'          => false,
				'editing_modes'            => [],
				'preview_enabled'          => false,
				'lock_after_submit'        => false,
				'show_edit_request_button' => false,
				'captcha'                  => 'custom',
				'rate_limit'               => 5,
			] )
			->styleIsolation( 'strong' )
			->storage( 'shared' )
			->actions( [] );
	}
}
