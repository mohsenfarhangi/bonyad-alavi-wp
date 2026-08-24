<?php
defined( 'ABSPATH' ) || exit;
do_action( 'woocommerce_before_cart' );
?>
<form class="woocommerce-cart-form bap-cart-page" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post"
      dir="rtl">
    <div class="bap-cart-container">
        <header class="bap-cart-heading">
            <div><span class="bap-eyebrow">همراهی شما، اثر ماندگار</span>
                <h1>سبد مشارکت</h1>
                <p>پروژه‌هایی که برای حمایت انتخاب کرده‌اید</p></div>
            <a class="bap-back-link" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">← بازگشت به
                پروژه‌ها</a></header>
        <div class="bap-steps"><span class="is-done">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>انتخاب پروژه</span><i></i><span class="is-active"><b>۲</b> سبد مشارکت</span><i></i><span><b>۳</b> پرداخت</span></div>
        <div class="bap-cart-grid">
            <section class="bap-cart-panel">
                <div class="bap-panel-head">
                    <h2>پروژه‌های انتخاب‌شده</h2>
                    <span><?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?> پروژه</span>
                </div>
                <div class="bap-cart-items">
                    <?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) : $product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                        if ( ! $product || ! $product->exists() || $cart_item['quantity'] <= 0 ) {
                            continue;
                        }
                        $project_amount = isset( $cart_item['bap_participation_amount'] ) ? (float) $cart_item['bap_participation_amount'] : (float) $product->get_price();
                        $minimum_amount = Bonyad_Alavi_WooCommerce_Participation::get_minimum_amount( $product->get_id() ); ?>
                        <article class="bap-cart-item" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
                            <div class="bap-item-image"><?php echo $product->get_image( 'woocommerce_thumbnail' ); ?></div>
                            <div class="bap-item-info"><h3><a
                                            href="<?php echo esc_url( $product->get_permalink( $cart_item ) ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
                                </h3><span class="bap-item-location">پروژه مشارکت مردمی</span><span
                                        class="bap-item-tag">همیاری بنیاد علوی</span></div>
                            <div class="bap-item-amount"><label
                                        for="bap-amount-<?php echo esc_attr( $cart_item_key ); ?>">مبلغ مشارکت</label>
                                <div><input id="bap-amount-<?php echo esc_attr( $cart_item_key ); ?>"
                                            class="bap-amount-input" type="number"
                                            min="<?php echo esc_attr( max( 1, $minimum_amount ) ); ?>" step="10000"
                                            value="<?php echo esc_attr( $project_amount ); ?>"
                                            inputmode="numeric"><span>تومان</span></div>
                                <small>حداقل: <?php echo esc_html( number_format_i18n( $minimum_amount ) ); ?>
                                    تومان</small><small class="bap-update-note" aria-live="polite"></small></div>
                            <a class="bap-remove"
                               href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>"
                               aria-label="حذف پروژه">×</a></article>
                    <?php endforeach; ?></div>
                <div class="bap-cart-actions">
                    <button type="button" id="bap-update-cart">بروزرسانی سبد</button>
                </div>
            </section>
            <aside class="bap-cart-summary"><h2>خلاصه سبد مشارکت</h2>
                <div class="bap-summary-row">
                    <span>تعداد پروژه‌ها</span><strong><?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?></strong>
                </div>
                <div class="bap-summary-row">
                    <span>جمع مبالغ مشارکت</span><strong><?php wc_cart_totals_subtotal_html(); ?></strong></div>
                <div class="bap-summary-row"><span>هزینه خدمات</span><strong>رایگان</strong></div>
                <hr>
                <div class="bap-total">
                    <span>مبلغ قابل پرداخت</span><strong><?php wc_cart_totals_order_total_html(); ?></strong></div>
                <a class="bap-checkout-button" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">ادامه و تکمیل
                    پرداخت</a>
                <div class="bap-secure">✓ پرداخت امن از طریق درگاه بانکی</div>
            </aside>
        </div>
    </div>
</form>
<?php do_action( 'woocommerce_after_cart' ); ?>
