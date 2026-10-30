<?php
/**
 * Plugin Name: بنیاد علوی - صفحات
 * Description: صفحات هیئت مدیره، نمودار سازمانی، ارتباط با ما و مناطق هدف
 * Version: 1.0
 * Text Domain: alavi-pages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ALAVI_PAGES_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALAVI_PAGES_URL', plugin_dir_url( __FILE__ ) );

/* نقشه عکس اعضا: نام فارسی => نام فایل (بدون پسوند) */
function alavi_member_photo( $name, $alt = '', $css_class = 'alavi-member-photo' ) {
    static $map = [
        'حمید قربانی'   => 'دکتر قربانی',
        'محسن مردعلی'   => 'دکتر مردعلی',
        'عبداله عابدی'  => 'آقای عابدی',
        'حمید معتمدی'   => 'آقای معتمدی',
        'حیدر مصلحی'    => 'مصلحی',
    ];

    static $media = [
        'دکتر حسین دهقان'        => 'dr-hossein-dehghan.jpg',
        'دکتر محمود عسکری آزاد'  => 'dr-mahmoud-askari-azad.jpg',
        'حمید معتمدی'            => 'hamid-motamedi.jpg',
        'عبداله عابدی'           => 'abdollah-abedi.jpg',
        'سیدمهدی پاک‌ذات'         => 'seyed-mehdi-pakzat.jpg',
    ];
    $uploads = 'https://bonyadalavi.ir/wp-content/uploads/2026/10/';

    if ( isset( $media[ $name ] ) ) {
        return '<div class="' . esc_attr( $css_class ) . ' alavi-photo-has-img">'
             . '<img src="' . esc_url( $uploads . $media[ $name ] ) . '" alt="' . esc_attr( $alt ?: $name ) . '" loading="lazy">'
             . '</div>';
    }

    if ( isset( $map[ $name ] ) ) {
        $file = ALAVI_PAGES_DIR . 'assets/images/' . $map[ $name ] . '.webp';
        if ( file_exists( $file ) ) {
            $url = ALAVI_PAGES_URL . 'assets/images/' . rawurlencode( $map[ $name ] ) . '.webp';
            return '<div class="' . esc_attr( $css_class ) . ' alavi-photo-has-img">'
                 . '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ?: $name ) . '" loading="lazy">'
                 . '</div>';
        }
    }
    return '<div class="' . esc_attr( $css_class ) . ' alavi-photo-has-img">'
         . '<img src="' . esc_url( $uploads . 'alavi-logo-placeholder.jpg' ) . '" alt="' . esc_attr( $alt ?: $name ) . '" loading="lazy">'
         . '</div>';
}

/* ─── Assets ─────────────────────────────────────────────── */

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'alavi-pages',
        ALAVI_PAGES_URL . 'assets/css/style.css',
        [],
        '1.0'
    );

    wp_enqueue_script(
        'alavi-regions',
        ALAVI_PAGES_URL . 'assets/js/regions.js',
        [],
        '1.5',
        true
    );
} );

/* ─── Contact Form AJAX ───────────────────────────────────── */

add_action( 'wp_ajax_alavi_contact_form', 'alavi_handle_contact_form' );
add_action( 'wp_ajax_nopriv_alavi_contact_form', 'alavi_handle_contact_form' );

function alavi_handle_contact_form() {
    check_ajax_referer( 'alavi_contact_nonce', 'nonce' );

    $name    = sanitize_text_field( $_POST['name'] ?? '' );
    $phone   = sanitize_text_field( $_POST['phone'] ?? '' );
    $unit    = sanitize_text_field( $_POST['unit'] ?? '' );
    $type    = sanitize_text_field( $_POST['req_type'] ?? '' );
    $message = sanitize_textarea_field( $_POST['message'] ?? '' );

    if ( ! $name || ! $phone || ! $message ) {
        wp_send_json_error( 'لطفاً فیلدهای ضروری را تکمیل کنید.' );
    }

    $body  = "نام: {$name}\n";
    $body .= "شماره همراه: {$phone}\n";
    $body .= "واحد موردنظر: {$unit}\n";
    $body .= "نوع درخواست: {$type}\n\n";
    $body .= "متن:\n{$message}";

    $sent = wp_mail(
        'info@bonyadalavi.ir',
        "پیام از فرم تماس: {$type}",
        $body,
        [ 'Content-Type: text/plain; charset=UTF-8' ]
    );

    if ( $sent ) {
        wp_send_json_success( 'پیام شما با موفقیت ارسال شد.' );
    } else {
        wp_send_json_error( 'خطا در ارسال پیام. لطفاً مجدداً تلاش کنید.' );
    }
}

add_action( 'wp_footer', function () {
    /* اجرا می‌شه روی همه صفحات؛ گارد داخل JS چک می‌کنه که form وجود داشته باشه */
    if ( true ) {
        ?>
        <script>
        (function(){
            var form = document.getElementById('alavi-contact-form');
            if (!form) return;
            form.addEventListener('submit', function(e){
                e.preventDefault();
                var btn = form.querySelector('button[type=submit]');
                var msg = document.getElementById('alavi-form-msg');
                btn.disabled = true;
                btn.textContent = 'در حال ارسال...';
                var data = new FormData(form);
                data.append('action','alavi_contact_form');
                data.append('nonce','<?php echo wp_create_nonce("alavi_contact_nonce"); ?>');
                fetch('<?php echo admin_url("admin-ajax.php"); ?>',{method:'POST',body:data})
                    .then(r=>r.json())
                    .then(function(res){
                        msg.className = res.success ? 'alavi-form-success' : 'alavi-form-error';
                        msg.textContent = res.data;
                        if(res.success) form.reset();
                        btn.disabled = false;
                        btn.textContent = 'ارسال پیام';
                    });
            });
        })();
        </script>
        <?php
    }
} );

/* ─── Page Creation on Activation ────────────────────────── */

register_activation_hook( __FILE__, 'alavi_create_pages' );

function alavi_create_pages() {
    $pages = [
        [
            'title'   => 'هیئت مدیره',
            'slug'    => 'board-of-directors',
            'sc'      => 'alavi_board',
        ],
        [
            'title'   => 'نمودار سازمانی',
            'slug'    => 'org-chart',
            'sc'      => 'alavi_org_chart',
        ],
        [
            'title'   => 'ارتباط با ما',
            'slug'    => 'contact-us',
            'sc'      => 'alavi_contact',
        ],
        [
            'title'   => 'مناطق هدف',
            'slug'    => 'target-regions',
            'sc'      => 'alavi_regions',
        ],
    ];

    foreach ( $pages as $p ) {
        if ( get_page_by_path( $p['slug'] ) ) continue;

        $id = wp_insert_post( [
            'post_title'   => $p['title'],
            'post_name'    => $p['slug'],
            'post_content' => '[' . $p['sc'] . ']',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ] );

        if ( $id && ! is_wp_error( $id ) ) {
            update_post_meta( $id, '_elementor_edit_mode', 'builder' );
            update_post_meta( $id, '_elementor_template_type', 'wp-page' );
            update_post_meta( $id, '_elementor_version', '3.0.0' );
            $el_data = alavi_elementor_shortcode_section( $p['sc'] );
            update_post_meta( $id, '_elementor_data', wp_slash( $el_data ) );
        }
    }
}

function alavi_elementor_shortcode_section( $sc ) {
    $uid = substr( md5( $sc ), 0, 8 );
    return json_encode( [[
        'id'       => 's' . $uid,
        'elType'   => 'section',
        'settings' => [ 'layout' => 'full_width', 'gap' => 'no' ],
        'elements' => [[
            'id'       => 'c' . $uid,
            'elType'   => 'column',
            'settings' => [ '_column_size' => 100 ],
            'elements' => [[
                'id'         => 'w' . $uid,
                'elType'     => 'widget',
                'widgetType' => 'shortcode',
                'settings'   => [ 'shortcode' => '[' . $sc . ']' ],
            ]],
        ]],
        'isInner'  => false,
    ]], JSON_UNESCAPED_UNICODE );
}

/* ─── Shortcodes ──────────────────────────────────────────── */

add_shortcode( 'alavi_board',     'alavi_board_shortcode' );
add_shortcode( 'alavi_org_chart', 'alavi_org_chart_shortcode' );
add_shortcode( 'alavi_contact',   'alavi_contact_shortcode' );
add_shortcode( 'alavi_regions',   'alavi_regions_shortcode' );

/* ── هیئت مدیره ── */

function alavi_board_shortcode() {
    ob_start();
    ?>
    <div class="alavi-page alavi-board" dir="rtl">
        <div class="alavi-section-title">
            <h2>هیئت مدیره بنیاد علوی</h2>
        </div>

        <!-- رئیس بنیاد مستضعفان -->
        <div class="alavi-board-top">
            <div class="alavi-member-card alavi-member-featured">
                <div class="alavi-member-photo alavi-photo-placeholder">
                    <span>عکس</span>
                </div>
                <div class="alavi-member-info">
                    <h3>دکتر حسین دهقان</h3>
                    <p>ریاست بنیاد مستضعفان انقلاب اسلامی</p>
                </div>
            </div>
        </div>

        <!-- رئیس هیئت مدیره -->
        <div class="alavi-board-top">
            <div class="alavi-member-card alavi-member-featured alavi-member-chairman">
                <div class="alavi-member-photo alavi-photo-placeholder">
                    <span>عکس</span>
                </div>
                <div class="alavi-member-info">
                    <h3>دکتر محمود عسگری آزاد</h3>
                    <p>ریاست هیئت مدیره</p>
                </div>
            </div>
        </div>

        <!-- اعضای هیئت مدیره -->
        <div class="alavi-section-subtitle"><h3>اعضای هیئت مدیره</h3></div>
        <div class="alavi-board-grid">
            <?php
            $members = [
                [ 'محسن مردعلی',           'عضو هیئت مدیره' ],
                [ 'اسماعیل سقاب اصفهانی',  'عضو هیئت مدیره' ],
                [ 'سیدمهدی پاک‌ذات',       'عضو هیئت مدیره' ],
                [ 'حیدر مصلحی',            'عضو هیئت مدیره' ],
                [ 'محمد امید',             'عضو هیئت مدیره' ],
                [ 'محمد ترکمانه',          'عضو هیئت مدیره' ],
            ];
            foreach ( $members as $m ) : ?>
                <div class="alavi-member-card">
                    <?php echo alavi_member_photo( $m[0] ); ?>
                    <div class="alavi-member-info">
                        <h4><?php echo esc_html( $m[0] ); ?></h4>
                        <p><?php echo esc_html( $m[1] ); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/* ── نمودار سازمانی ── */

function alavi_org_chart_shortcode() {
    ob_start();
    ?>
    <div class="alavi-page alavi-org-chart" dir="rtl">
        <div class="alavi-section-title">
            <h2>نمودار سازمانی بنیاد علوی</h2>
        </div>

        <div class="alavi-org-tree">

            <!-- CEO -->
            <div class="alavi-org-level alavi-org-level-ceo">
                <div class="alavi-org-node alavi-org-node-ceo">
                    <?php echo alavi_member_photo( 'دکتر محمود عسکری آزاد', '', 'alavi-org-photo' ); ?>
                    <strong>دکتر محمود عسکری آزاد</strong>
                    <span>مدیرعامل</span>
                </div>
            </div>

            <div class="alavi-org-connector"></div>

            <!-- Level 1 deputies -->
            <div class="alavi-org-level alavi-org-level-1">
                <?php
                $l1 = [
                    [ 'مهدی هاتف',       'مدیرعامل شرکت اسکان' ],
                    [ 'سیدمحمدحسن صافی', 'معاون سلامت' ],
                    [ 'حمید قربانی',   'معاون توسعه مدیریت' ],
                    [ 'محسن مردعلی',   'معاون برنامه‌ریزی و تجهیز منابع' ],
                    [ 'عبداله عابدی',  'معاون توانمندسازی اقتصادی' ],
                    [ 'علی جعفری',     'معاون آموزش، مهارت و پرورش' ],
                ];
                foreach ( $l1 as $n ) : ?>
                    <div class="alavi-org-node">
                        <?php echo alavi_member_photo( $n[0], $n[0], 'alavi-org-photo' ); ?>
                        <strong><?php echo esc_html( $n[0] ); ?></strong>
                        <span><?php echo esc_html( $n[1] ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- مدیر حوزه مدیرعامل -->
            <div class="alavi-org-connector"></div>
            <div class="alavi-org-level alavi-org-level-1">
                <div class="alavi-org-node alavi-org-node-mid">
                    <?php echo alavi_member_photo( 'سید محسن کالوندی', '', 'alavi-org-photo' ); ?>
                    <strong>سید محسن کالوندی</strong>
                    <span>مدیر حوزه مدیرعامل</span>
                </div>
            </div>

            <div class="alavi-org-connector"></div>

            <!-- Level 2 directors (two fixed rows) -->
            <?php
            $l2_rows = [
                [
                    [ 'بهزاد مایلی',          'مدیر مجتمع اردوگاهی امام خمینی (ره)' ],
                    [ 'شریف پایان',           'رئیس حراست' ],
                    [ 'محمدصادق توسلی',       'مدیر مرکز هماهنگی حرکت‌های مردمی، جهادی و اردوهای توانمندساز' ],
                    [ 'محمدمهدی اشراقی',      'مدیر تجهیز منابع مالی و قرض‌الحسنه' ],
                    [ 'مهدی صانعی',           'مدیر ارزیابی عملکرد، برنامه و بودجه' ],
                    [ 'حمید معتمدی',          'مدیر مرکز مطالعات راهبردی و پیشرفت مناطق محروم' ],
                    [ 'مسلم پورجمالی',        'مجری طرح ملی حبیب' ],
                ],
                [
                    [ 'حامد مهدی یار',        'سرپرست رسالت اجتماعی و قراردادهای بین‌سازمانی' ],
                    [ 'آرش نوروزی',           'سرپرست مدیریت مالی' ],
                    [ 'حسین صیامی',           'مدیر سرمایه انسانی' ],
                    [ 'سیدعلی موسوی',         'سرپرست مرکز فناوری اطلاعات و هوشمندسازی' ],
                    [ 'حسین‌زاده قشمی',       'سرپرست مرکز روابط عمومی و ارتباطات' ],
                    [ 'محمد علائی',           'مدیر پشتیبانی' ],
                ],
            ];
            foreach ( $l2_rows as $row_index => $l2 ) : ?>
            <div class="alavi-org-level alavi-org-level-2"<?php echo $row_index ? ' style="margin-top:10px"' : ''; ?>>
                <?php foreach ( $l2 as $n ) : ?>
                    <div class="alavi-org-node alavi-org-node-sm">
                        <?php echo alavi_member_photo( $n[0], $n[0], 'alavi-org-photo' ); ?>
                        <strong><?php echo esc_html( $n[0] ); ?></strong>
                        <span><?php echo esc_html( $n[1] ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/* ── ارتباط با ما ── */

function alavi_contact_shortcode() {
    ob_start();
    ?>
    <div class="alavi-page alavi-contact" dir="rtl">

        <!-- بخش اطلاعات اصلی + نقشه -->
        <div class="alavi-contact-top">
            <div class="alavi-contact-info">
                <div class="alavi-section-title">
                    <h2>ارتباط با ما</h2>
                </div>
                <ul class="alavi-info-list">
                    <li>
                        <span class="alavi-info-icon">📍</span>
                        <div>
                            <strong>آدرس:</strong>
                            <p>تهران، خیابان کریم‌خان زند، بعد از تقاطع حافظ، خیابان شهید گلابی، پلاک ۱۲</p>
                        </div>
                    </li>
                    <li>
                        <span class="alavi-info-icon">📮</span>
                        <div>
                            <strong>کد پستی:</strong>
                            <p dir="ltr">۱۵۹۴۷۱۶۳۱۳</p>
                        </div>
                    </li>
                    <li>
                        <span class="alavi-info-icon">📞</span>
                        <div>
                            <strong>تلفن:</strong>
                            <p dir="ltr"><a href="tel:02183352000">۰۲۱۸۳۳۵۲۰۰۰</a></p>
                        </div>
                    </li>
                    <li>
                        <span class="alavi-info-icon">✉️</span>
                        <div>
                            <strong>ایمیل:</strong>
                            <p><a href="mailto:info@bonyadalavi.ir">info@bonyadalavi.ir</a></p>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="alavi-map">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d864.5!2d51.4138!3d35.7065!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzXCsDQyJzIzLjQiTiA1McKwMjQnNDkuNyJF!5e0!3m2!1sfa!2sir!4v1"
                    width="100%"
                    height="100%"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="موقعیت بنیاد علوی">
                </iframe>
            </div>
        </div>

        <!-- جدول داخلی‌ها -->
        <div class="alavi-extensions">
            <h3 class="alavi-subsection-title">داخلی‌های واحدها</h3>
            <div class="alavi-table-wrapper">
                <table class="alavi-ext-table">
                    <thead>
                        <tr>
                            <th>عنوان واحد</th>
                            <th>داخلی</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $exts = [
                            [ 'معاونت آموزش، مهارت و پرورش',               '۳۶۸ – ۳۷۰ – ۳۳۵' ],
                            [ 'معاونت توانمندسازی اقتصادی',                 '۱۸۱' ],
                            [ 'مرکز هماهنگی حرکت‌های مردمی و جهادی',        '۱۲۰ – ۱۹۹' ],
                            [ 'مشارکت‌های مردمی و سازمانی',                 '۴۸۵' ],
                            [ 'امور مزایده‌ها و مناقصه‌ها',                   '۵۸۸' ],
                            [ 'جایزه ملی علوی',                            '۴۴۱' ],
                            [ 'طرح حبیب',                                  '۴۵۹' ],
                            [ 'مرکز فناوری اطلاعات',                       '۲۲۲' ],
                            [ 'مرکز مطالعات راهبردی',                      '۳۰۳' ],
                            [ 'مرکز رسانه و روابط عمومی',                  '۳۹۷' ],
                        ];
                        foreach ( $exts as $ext ) : ?>
                            <tr>
                                <td><?php echo esc_html( $ext[0] ); ?></td>
                                <td dir="ltr"><?php echo esc_html( $ext[1] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- مراکز وابسته -->
        <div class="alavi-affiliated">
            <h3 class="alavi-subsection-title">مراکز وابسته</h3>
            <div class="alavi-cards-grid">

                <div class="alavi-affiliate-card">
                    <h4>مجتمع اردوگاهی امام خمینی (ره)</h4>
                    <ul>
                        <li>
                            <span>📞</span>
                            <span dir="ltr">۰۲۱۲۳۵۶۷۷۰۳-۴</span>
                            &nbsp;|&nbsp;
                            <span dir="ltr">۰۹۰۵۳۵۵۱۰۹۲</span>
                        </li>
                        <li>
                            <span>📍</span>
                            <span>تهران، لواسان، خیابان امام خمینی (ره)، بعد از پل فلزی، کوچه شکوفه، انتهای کوچه نگار، مجتمع اردوگاهی امام خمینی (ره)</span>
                        </li>
                        <li>
                            <span>✉️</span>
                            <a href="mailto:ordogah@bonyadalavi.ir">ordogah@bonyadalavi.ir</a>
                        </li>
                    </ul>
                </div>

                <div class="alavi-affiliate-card">
                    <h4>موسسه سلامت</h4>
                    <ul>
                        <li>
                            <span>📞</span>
                            <span dir="ltr">۰۲۱۸۳۳۹۵۰۰۰</span>
                            &nbsp;|&nbsp;
                            داخلی
                            <span dir="ltr">۲۰۲</span>
                        </li>
                        <li>
                            <span>📍</span>
                            <span>تهران، خیابان حضرت ولیعصر (عج)، بالاتر از خیابان میرداماد، خیابان تابان غربی، پلاک ۷۲</span>
                        </li>
                        <li>
                            <span>✉️</span>
                            <a href="mailto:salamat@bonyadalavi.ir">salamat@bonyadalavi.ir</a>
                        </li>
                    </ul>
                </div>

                <div class="alavi-affiliate-card">
                    <h4>شرکت اسکان</h4>
                    <ul>
                        <li>
                            <span>📞</span>
                            <span dir="ltr">۰۲۱۸۸۶۵۶۴۵۱</span>
                            &nbsp;|&nbsp;
                            داخلی
                            <span dir="ltr">۲۰۵</span>
                        </li>
                        <li>
                            <span>📍</span>
                            <span>تهران، خیابان میرداماد، برج اسکان، بلوک T، طبقه C</span>
                        </li>
                        <li>
                            <span>✉️</span>
                            <a href="mailto:eskan@bonyadalavi.ir">eskan@bonyadalavi.ir</a>
                        </li>
                    </ul>
                </div>

            </div>
        </div>

        <!-- فرم تماس -->
        <div class="alavi-contact-form-section">
            <h3 class="alavi-subsection-title">ارسال پیام</h3>
            <p class="alavi-form-desc">جهت ارسال نظرات، پیشنهادات و یا شکایات خود می‌توانید از طریق تکمیل فرم زیر اقدام نمایید.</p>

            <form id="alavi-contact-form" class="alavi-form" novalidate>
                <div class="alavi-form-row">
                    <div class="alavi-form-group">
                        <label for="acf-name">نام و نام خانوادگی <span class="req">*</span></label>
                        <input type="text" id="acf-name" name="name" required placeholder="نام خود را وارد کنید">
                    </div>
                    <div class="alavi-form-group">
                        <label for="acf-phone">شماره همراه <span class="req">*</span></label>
                        <input type="tel" id="acf-phone" name="phone" required placeholder="۰۹۱۲۰۰۰۰۰۰۰" dir="ltr">
                    </div>
                </div>
                <div class="alavi-form-row">
                    <div class="alavi-form-group">
                        <label for="acf-unit">واحد موردنظر</label>
                        <select id="acf-unit" name="unit">
                            <option value="">انتخاب کنید...</option>
                            <option>معاونت آموزش، مهارت و پرورش</option>
                            <option>معاونت توانمندسازی اقتصادی</option>
                            <option>مرکز هماهنگی حرکت‌های مردمی و جهادی</option>
                            <option>مشارکت‌های مردمی و سازمانی</option>
                            <option>امور مزایده‌ها و مناقصه‌ها</option>
                            <option>جایزه ملی علوی</option>
                            <option>طرح حبیب</option>
                            <option>مرکز فناوری اطلاعات</option>
                            <option>مرکز رسانه و روابط عمومی</option>
                            <option>حراست</option>
                            <option>حوزه مدیرعامل</option>
                        </select>
                    </div>
                    <div class="alavi-form-group">
                        <label>نوع درخواست</label>
                        <div class="alavi-radio-group">
                            <label class="alavi-radio"><input type="radio" name="req_type" value="نظر و پیشنهاد" checked> نظر و پیشنهاد</label>
                            <label class="alavi-radio"><input type="radio" name="req_type" value="انتقاد و شکایت"> انتقاد و شکایت</label>
                            <label class="alavi-radio"><input type="radio" name="req_type" value="سایر"> سایر</label>
                        </div>
                    </div>
                </div>
                <div class="alavi-form-group">
                    <label for="acf-message">متن درخواست <span class="req">*</span></label>
                    <textarea id="acf-message" name="message" rows="5" required placeholder="متن پیام خود را اینجا بنویسید..."></textarea>
                </div>
                <div id="alavi-form-msg" class="alavi-form-msg" role="alert"></div>
                <button type="submit" class="alavi-btn-submit">ارسال پیام</button>
            </form>
        </div>

    </div>
    <?php
    return ob_get_clean();
}

/* ── مناطق هدف ── */

function alavi_regions_shortcode() {
    ob_start();
    ?>
    <div class="alavi-page alavi-regions" dir="rtl">
        <div class="alavi-section-title">
            <h2>مناطق هدف بنیاد علوی</h2>
            <p>این صفحه شامل مناطق روستایی، عشایری و محله‌های شهری تحت پوشش بنیاد علوی است.</p>
        </div>

        <div class="alavi-regions-stats" id="alavi-stats">
            <div class="alavi-stat-card">
                <strong id="stat-total">۵٬۶۰۰</strong>
                <span>کل مناطق</span>
            </div>
            <div class="alavi-stat-card">
                <strong id="stat-rural">۴٬۸۹۱</strong>
                <span>مناطق روستایی و عشایری</span>
            </div>
            <div class="alavi-stat-card">
                <strong id="stat-urban">۷۰۹</strong>
                <span>محله‌های شهری</span>
            </div>
        </div>

        <div class="alavi-regions-filters">
            <div class="alavi-filter-group">
                <label for="filter-province">استان:</label>
                <select id="filter-province">
                    <option value="">همه استان‌ها</option>
                </select>
            </div>
            <div class="alavi-filter-group">
                <label>نوع منطقه:</label>
                <div class="alavi-radio-group">
                    <label class="alavi-radio"><input type="radio" name="filter-type" value="" checked> همه</label>
                    <label class="alavi-radio"><input type="radio" name="filter-type" value="R"> روستایی و عشایری</label>
                    <label class="alavi-radio"><input type="radio" name="filter-type" value="U"> محله شهری</label>
                </div>
            </div>
            <div class="alavi-filter-group">
                <label for="filter-search">جستجو:</label>
                <input type="text" id="filter-search" placeholder="نام آبادی یا شهر یا شهرستان...">
            </div>
        </div>

        <div class="alavi-regions-loading" id="alavi-loading">
            <p>در حال بارگذاری داده‌ها...</p>
        </div>

        <div class="alavi-table-wrapper" id="alavi-table-wrapper" style="display:none">
            <div class="alavi-table-info">
                نمایش <strong id="filter-count">۰</strong> منطقه
            </div>
            <table class="alavi-regions-table" id="alavi-regions-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>استان</th>
                        <th>شهرستان</th>
                        <th>نوع منطقه</th>
                        <th>آبادی / شهر</th>
                    </tr>
                </thead>
                <tbody id="alavi-regions-tbody"></tbody>
            </table>
            <div class="alavi-pagination" id="alavi-pagination"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

