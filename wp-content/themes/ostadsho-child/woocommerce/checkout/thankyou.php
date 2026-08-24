<?php
/**
 * Participation thank-you page.
 *
 * @package BonyadAlaviChild
 * @version 8.1.0
 */

defined( 'ABSPATH' ) || exit;

$shop_url = wc_get_page_permalink( 'shop' );
?>

<div
	id="bap-thankyou"
	class="bap-thankyou"
	data-order-number="<?php echo esc_attr( $order ? $order->get_order_number() : '' ); ?>"
	data-shop-url="<?php echo esc_url( $shop_url ); ?>"
>
	<div class="bap-thankyou-container">
		<?php if ( $order ) : ?>
			<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

			<?php if ( $order->has_status( 'failed' ) ) : ?>
				<section class="bap-failed">
					<span class="bap-failed-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg></span>
					<h1>پرداخت با موفقیت انجام نشد</h1>
					<p>تراکنش از طرف بانک یا درگاه تأیید نشده است. مبلغی از حساب شما کسر شده باشد، مطابق روال بانک بازگردانده خواهد شد.</p>
					<div class="bap-failed-actions"><a class="bap-failed-pay" href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>">تلاش دوباره برای پرداخت</a><a class="bap-failed-back" href="<?php echo esc_url( wc_get_cart_url() ); ?>">بازگشت به سبد مشارکت</a></div>
				</section>
			<?php else :
				$billing_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
				$order_date   = $order->get_date_created() ? wc_format_datetime( $order->get_date_created(), 'Y/m/d' ) : '—';
			?>
				<div class="bap-thankyou-steps" aria-label="مراحل مشارکت"><span class="bap-thankyou-step"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>انتخاب پروژه</span><i class="bap-thankyou-step-line"></i><span class="bap-thankyou-step"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>سبد مشارکت</span><i class="bap-thankyou-step-line"></i><span class="bap-thankyou-step"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>پرداخت</span></div>

				<section class="bap-success-hero">
					<span class="bap-success-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-8 8.5-4.5-1-8-3.5-8-8.5V6l8-3 8 3v7Z"/><path d="m9 12 2 2 4-4"/></svg></span>
					<h1><?php echo $order->is_paid() ? 'مشارکت شما با موفقیت ثبت شد' : 'درخواست مشارکت شما ثبت شد'; ?></h1>
					<p><?php if ( $billing_name ) : ?><span class="bap-success-name"><?php echo esc_html( $billing_name ); ?> عزیز، </span><?php endif; ?>از همراهی شما برای ساختن فرصتی بهتر سپاسگزاریم. حضور شما بخشی از مسیر به‌ثمررسیدن این پروژه‌هاست.</p>
					<div class="bap-order-facts">
						<div class="bap-order-fact"><span>شماره پیگیری</span><strong><?php echo esc_html( $order->get_order_number() ); ?></strong></div>
						<div class="bap-order-fact"><span>تاریخ ثبت</span><strong><?php echo esc_html( $order_date ); ?></strong></div>
						<div class="bap-order-fact"><span>مبلغ مشارکت</span><strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong></div>
						<div class="bap-order-fact"><span>روش پرداخت</span><strong><?php echo wp_kses_post( $order->get_payment_method_title() ?: '—' ); ?></strong></div>
					</div>
				</section>

				<div class="bap-thankyou-grid">
					<section class="bap-thankyou-panel">
						<div class="bap-thankyou-panel-head"><h2>جزئیات مشارکت شما</h2><span>پروژه‌ها و مبلغ ثبت‌شده</span></div>
						<div class="bap-thankyou-native"><?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?></div>
					</section>
					<aside class="bap-thankyou-panel bap-next-panel">
						<h2>همراهی شما از همین‌جا ادامه پیدا می‌کند</h2><p>با نگهداری شماره پیگیری، اطلاعات این مشارکت همیشه در دسترس شما خواهد بود.</p>
						<ul class="bap-next-list">
							<li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1V2l-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M16 8h-6M16 12h-6M13 16h-3"/></svg><div><strong>شماره پیگیری را نگه دارید</strong><span>برای مراجعه و پیگیری‌های بعدی</span></div></li>
							<li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg><div><strong>پیشرفت پروژه را دنبال کنید</strong><span>تصاویر جدید در صفحه پروژه منتشر می‌شوند</span></div></li>
							<li><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4M8.6 13.5l6.8 4"/></svg><div><strong>این فرصت را معرفی کنید</strong><span>دیگران را با پروژه‌های مشارکت آشنا کنید</span></div></li>
						</ul>
						<div class="bap-thankyou-actions"><a class="bap-thankyou-primary" href="<?php echo esc_url( $shop_url ); ?>">مشاهده پروژه‌های دیگر</a><button class="bap-thankyou-secondary" id="bap-copy-order" type="button"><svg viewBox="0 0 24 24" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>کپی شماره پیگیری</button><button class="bap-thankyou-secondary" id="bap-share-projects" type="button"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4M8.6 13.5l6.8 4"/></svg>معرفی پروژه‌ها به دیگران</button></div>
						<div class="bap-thankyou-status" aria-live="polite"></div>
					</aside>
				</div>
				<div class="bap-gateway-message"><?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?></div>
			<?php endif; ?>
		<?php else : ?>
			<section class="bap-order-missing"><h1>مشارکت شما ثبت شد</h1><p>از همراهی ارزشمند شما سپاسگزاریم.</p><a class="bap-thankyou-primary" href="<?php echo esc_url( $shop_url ); ?>">مشاهده پروژه‌ها</a></section>
		<?php endif; ?>
	</div>
</div>
