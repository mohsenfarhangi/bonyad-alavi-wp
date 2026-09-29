<?php
declare( strict_types=1 );

use BonyadAlavi\FormEngine\Form\Form;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Form\Step;
use BonyadAlavi\FormEngine\Form\Fields\{
	TextField,
	TextareaField,
	SelectField,
	TelField,
	EmailField
};

/**
 * تعریف فرم تماس با ما برای سایت بنیاد علوی.
 *
 * موتور فرم، ذخیره‌سازی، اعتبارسنجی، مدیریت و Actionها در Alavi Form Engine قرار دارند؛
 * این کلاس فقط Source Definition اختصاصی سایت را در child theme نگه می‌دارد.
 */
final class BA_Contact_Form {
	public static function register( FormRegistry $registry ): void {
		$registry->register( ( new self() )->build() );
	}

	public function build(): Form {
		$province = SelectField::make( 'province' )
			->label( 'استان' )
			->source( [
				'type'  => 'geo',
				'level' => 'province',
			] )
			->required()
			->width( 6 );

		return Form::make( 'contact-us' )
			->title( 'تماس با ما' )
			->description( 'برای ارسال پرسش، پیشنهاد یا درخواست خود فرم زیر را تکمیل کنید.' )
			->steps( [
				Step::make( 'message', 'ارسال پیام' )->fields( [
					TextField::make( 'full_name' )
						->label( 'نام و نام خانوادگی' )
						->required()
						->width( 6 ),
					TelField::make( 'mobile' )
						->label( 'شماره همراه' )
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
							'data-afe-invalid-message' => 'شماره همراه باید دقیقاً ۱۱ رقم و با ۰۹ شروع شود.',
						] )
						->required()
						->width( 6 ),
					EmailField::make( 'email' )
						->label( 'ایمیل' )
						->placeholder( 'name@example.com' )
						->attributes( [
							'autocomplete' => 'email',
							'dir'          => 'ltr',
						] )
						->width( 6 ),
					$province,
					TextField::make( 'subject' )
						->label( 'موضوع پیام' )
						->required()
						->width( 12 ),
					TextareaField::make( 'message' )
						->label( 'متن پیام' )
						->placeholder( 'متن پیام خود را بنویسید.' )
						->attributes( [ 'rows' => 6 ] )
						->required()
						->width( 12 ),
				] ),
			] )
			->settings( [
				'wizard'                   => false,
				'save_draft'               => false,
				'show_progress'            => false,
				'editing_enabled'          => false,
				'preview_enabled'          => false,
				'lock_after_submit'        => false,
				'show_edit_request_button' => false,
				'captcha'                  => 'custom',
				'rate_limit'               => 5,
			] )
			->storage( 'shared' )
			->actions( [
				[
					'action_key'       => 'notify_admin_email_on_submit',
					'type'             => 'email',
					'on'               => [ 'submission.submitted' ],
					'execution_policy' => 'once_per_submission',
					'on_error'         => 'continue',
					'config'           => [
						'to'      => get_option( 'admin_email' ),
						'subject' => 'پیام جدید تماس با ما: {{field:subject}}',
						'body'    => "پیام جدیدی از فرم تماس با ما ثبت شد.\n\nنام: {{field:full_name}}\nشماره همراه: {{field:mobile}}\nایمیل: {{field:email}}\nموضوع: {{field:subject}}\n\nمتن پیام:\n{{field:message}}\n\nکد رهگیری: {{tracking_code}}",
					],
				],
			] );
	}
}
