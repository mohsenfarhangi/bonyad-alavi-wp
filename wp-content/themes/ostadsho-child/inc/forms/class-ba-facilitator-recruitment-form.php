<?php
declare( strict_types=1 );

use BonyadAlavi\FormEngine\Form\Form;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Form\Step;
use BonyadAlavi\FormEngine\Form\Fields\{
	TextField,
	TelField,
	SelectField,
	TextareaField,
	FileField
};

/**
 * Source Definition فرم جذب تسهیلگر بنیاد علوی.
 *
 * Validation, Geo Data Source, duplicate prevention, submission storage, file
 * handling, Admin and exports are provided by Alavi Form Engine.
 */
final class BA_Facilitator_Recruitment_Form {
	public static function register( FormRegistry $registry ): void {
		$registry->register( ( new self() )->build() );
	}

	public function build(): Form {
		return Form::make( 'facilitator-recruitment' )
			->title( 'جذب تسهیلگر' )
			->description( 'برای اعلام آمادگی همکاری، اطلاعات خود را به‌دقت تکمیل و ثبت کنید.' )
			->template( <<<'HTML'
<section class="ba-facilitator">
	<header class="ba-facilitator__header">
		<span class="ba-facilitator__eyebrow">فرصت همکاری با بنیاد علوی</span>
		<h2>{{title}}</h2>
		<p>{{description}}</p>
	</header>
	<div class="ba-facilitator__content">{{steps}}</div>
</section>
HTML
			)
			->steps( [
				Step::make( 'applicant', 'مشخصات متقاضی' )->fields( [
					TextField::make( 'full_name' )
						->label( 'نام و نام خانوادگی' )
						->attributes( [ 'autocomplete' => 'name' ] )
						->required()->width( 6 ),

					TextField::make( 'national_id' )
						->label( 'کد ملی' )
						->rule( 'national_id' )
						->inputMask( 'national_id_ir' )
						->attributes( [
							'maxlength' => 10,
							'minlength' => 10,
							'pattern' => '[0-9]{10}',
							'inputmode' => 'numeric',
							'autocomplete' => 'off',
							'data-afe-digits-only' => '1',
							'data-afe-invalid-message' => 'کد ملی باید دقیقاً ۱۰ رقم و معتبر باشد.',
						] )
						->required()->width( 6 ),

					SelectField::make( 'gender' )
						->label( 'جنسیت' )
						->options( [ 'male' => 'مرد', 'female' => 'زن' ] )
						->required()->width( 6 ),

					TelField::make( 'mobile' )
						->label( 'شماره تماس همراه' )
						->default( '09' )
						->rule( 'mobile_09' )
						->inputMask( 'mobile_ir' )
						->attributes( [
							'maxlength' => 11,
							'minlength' => 11,
							'pattern' => '09[0-9]{9}',
							'inputmode' => 'numeric',
							'autocomplete' => 'tel',
							'data-afe-digits-only' => '1',
							'data-afe-fixed-prefix' => '09',
							'data-afe-invalid-message' => 'شماره همراه باید ۱۱ رقم و با ۰۹ شروع شود.',
						] )
						->required()->width( 6 ),

					TelField::make( 'landline' )
						->label( 'شماره تماس ثابت' )
						->placeholder( 'مثلاً ۰۲۱۱۲۳۴۵۶۷۸' )
						->inputMask( 'landline_ir' )
						->meta( 'validators', [
							[ 'key' => 'landline', 'message' => 'شماره تلفن ثابت با کد شهر معتبر نیست.' ],
							[ 'key' => 'custom_regex', 'pattern' => '^0(?!9)[0-9]{10}$', 'flags' => 'u', 'message' => 'شماره تلفن ثابت باید ۱۱ رقم و شامل کد شهر باشد.' ],
						] )
						->attributes( [
							'pattern' => '0(?!9)[0-9]{10}',
							'inputmode' => 'numeric',
							'autocomplete' => 'tel-national',
							'data-afe-digits-only' => '1',
							'data-afe-invalid-message' => 'شماره تلفن ثابت را با کد شهر وارد کنید.',
						] )
						->required()->width( 6 ),

					SelectField::make( 'education' )
						->label( 'تحصیلات' )
						->options( [
							'under_diploma' => 'زیر دیپلم',
							'diploma' => 'دیپلم',
							'associate' => 'کاردانی',
							'bachelor' => 'کارشناسی',
							'master' => 'کارشناسی ارشد',
							'phd_plus' => 'دکتری و بالاتر',
						] )
						->required()->width( 6 ),

					SelectField::make( 'province' )
						->label( 'استان مورد تقاضا' )
						->source( [ 'type' => 'geo', 'level' => 'province' ] )
						->required()->width( 6 ),

					SelectField::make( 'county' )
						->label( 'شهرستان مورد تقاضا' )
						->source( [ 'type' => 'geo', 'level' => 'county', 'parent' => 'province' ] )
						->dependsOn( 'province' )
						->required()->width( 6 ),

					TextField::make( 'residential_address' )
						->label( 'آدرس محل سکونت' )
						->attributes( [ 'autocomplete' => 'street-address' ] )
						->required()->width( 12 ),

					TextareaField::make( 'description' )
						->label( 'توضیحات' )
						->placeholder( 'سوابق شغلی، علایق و اولویت‌های خود را در صورت تمایل بنویسید.' )
						->attributes( [ 'rows' => 5 ] )
						->width( 12 ),

					FileField::make( 'resume' )
						->label( 'فایل رزومه' )
						->description( 'اختیاری | PDF، Word (DOC/DOCX) | حداکثر ۵ مگابایت' )
						->multiple( false )
						->maxFiles( 1 )
						->maxSizeMb( 5 )
						->accept( [
							'application/pdf',
							'application/msword',
							'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
						] )
						->width( 12 ),
				] ),
			] )
			->settings( [
				'wizard' => false,
				'save_draft' => false,
				'show_progress' => false,
				'editing_enabled' => false,
				'editing_modes' => [],
				'preview_enabled' => false,
				'lock_after_submit' => false,
				'show_edit_request_button' => false,
				'captcha' => 'custom',
				'rate_limit' => 5,
				'duplicate' => [
					'enabled' => true,
					'fields' => [ 'national_id' ],
					'behavior' => 'block',
					'message' => 'این کد ملی قبلاً در فرم جذب تسهیلگر ثبت شده است. امکان ثبت‌نام مجدد وجود ندارد.',
				],
			] )
			->storage( 'shared' );
	}
}