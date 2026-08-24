<?php
defined( 'ABSPATH' ) || exit;

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html__( 'برای ادامه پرداخت باید وارد حساب کاربری شوید.', 'woocommerce' );
	return;
}
do_action( 'woocommerce_before_checkout_form', $checkout );
?>
<div class="bap-checkout-page" dir="rtl">
  <div class="bap-checkout-container">
    <header class="bap-checkout-heading"><div><span class="bap-eyebrow">آخرین گام برای همراهی</span><h1>تکمیل پرداخت</h1><p>اطلاعات خود را وارد کنید تا مشارکت شما ثبت شود.</p></div><a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="bap-back-link">← بازگشت به سبد مشارکت</a></header>
    <div class="bap-steps"><span class="is-done"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>انتخاب پروژه</span><i></i><span class="is-done"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>سبد مشارکت</span><i></i><span class="is-active"><b>۳</b> پرداخت</span></div>
    <form name="checkout" method="post" class="checkout woocommerce-checkout bap-checkout-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
      <?php if ( $checkout->get_checkout_fields() ) : ?>
        <div class="bap-checkout-grid">
          <section class="bap-checkout-fields bap-panel">
            <div class="bap-panel-head"><h2>اطلاعات پرداخت‌کننده</h2><span>اطلاعات شما نزد ما محفوظ می‌ماند</span></div>
            <div class="bap-fields-inner">
              <div class="bap-checkout-intro"><span class="bap-intro-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 21a8 8 0 0 1 13.292-6"/><circle cx="10" cy="8" r="5"/><path d="m16 19 2 2 4-4"/></svg></span><div><strong>برای ثبت مشارکت، فقط اطلاعات تماس کافی است</strong><p>رسید و نتیجه مشارکت به اطلاعات واردشده ارسال می‌شود. نیازی به واردکردن نشانی ندارید.</p></div></div>
              <?php do_action( 'woocommerce_checkout_billing' ); ?>
              <?php do_action( 'woocommerce_checkout_shipping' ); ?>
              <div class="bap-impact-row"><div><span class="bap-impact-icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 13c0 5-3.5 7.5-8 8.5-4.5-1-8-3.5-8-8.5V6l8-3 8 3v7Z"/><path d="m9 12 2 2 4-4"/></svg></span><b>پرداخت امن</b><span>اتصال مستقیم به درگاه بانکی</span></div><div><span class="bap-impact-icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1V2l-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M16 8h-6M16 12h-6M13 16h-3"/></svg></span><b>رسید مشارکت</b><span>ارسال پس از ثبت موفق</span></div><div><span class="bap-impact-icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg></span><b>گزارش شفاف</b><span>پیگیری اثرگذاری پروژه</span></div></div>
            </div>
          </section>
          <aside class="bap-order-panel bap-panel">
            <div class="bap-panel-head"><h2>خلاصه مشارکت</h2><a href="<?php echo esc_url( wc_get_cart_url() ); ?>">ویرایش سبد</a></div>
            <div class="bap-order-inner"><?php do_action( 'woocommerce_checkout_order_review' ); ?></div>
          </aside>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>
<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
