# راهنمای کامپوننت Repeater مدیریت

از نسخه `v0.3.1` تمام Repeaterهای سفارشی که در رابط مدیریت وردپرس ساخته می‌شوند باید از کامپوننت `BA_Admin_Repeater_Component` استفاده کنند.

مسیر کلاس:

`inc/admin/components/class-ba-admin-repeater-component.php`

Assetهای عمومی:

- `assets/css/ba-admin-repeater.css`
- `assets/js/ba-admin-repeater.js`

## محدوده استفاده

این کامپوننت برای UIهای سفارشی `wp-admin` طراحی شده است؛ از جمله:

- متاباکس‌های Post/Product/CPT
- صفحات Settings API
- صفحات مدیریتی اختصاصی قالب
- فرم‌های مدیریتی سفارشی

برای کنترل‌های داخلی Elementor نباید این کامپوننت جایگزین `Elementor\Repeater` شود. در Elementor از API رسمی خود Elementor استفاده می‌شود.

## مسئولیت‌های کامپوننت

کامپوننت عمومی مسئول موارد زیر است:

- ساخت Wrapper و ساختار BEM استاندارد Repeater
- مدیریت Template ردیف جدید
- تولید و افزایش Index بدون نیاز به JavaScript اختصاصی
- افزودن ردیف
- حذف ردیف
- جابه‌جایی ردیف به بالا و پایین
- نمایش وضعیت خالی
- فوکوس روی اولین کنترل ردیف جدید
- انتشار رویداد عمومی بعد از تغییر Repeater
- بارگذاری CSS و JavaScript مشترک

کامپوننت **مسئول Sanitize یا ذخیره داده نیست**. هر Feature باید داده خودش را در Service یا Handler مربوط به همان قابلیت پاک‌سازی و ذخیره کند.

## قرارداد BEM

Block عمومی:

```text
ba-admin-repeater
```

Elementهای اصلی:

```text
ba-admin-repeater__list
ba-admin-repeater__item
ba-admin-repeater__toolbar
ba-admin-repeater__title
ba-admin-repeater__actions
ba-admin-repeater__body
ba-admin-repeater__empty
ba-admin-repeater__footer
ba-admin-repeater__add
```

استایل فیلدهای داخل هر ردیف باید متعلق به Block همان Feature باشد. برای مثال تنظیمات مرکز همچنان از `ba-center-settings__field` استفاده می‌کند و FAQ محصول از `ba-product-faq-admin__field`.

## بارگذاری Assetها

در صفحه‌ای که Repeater استفاده می‌شود، فقط این متد فراخوانی شود:

```php
BA_Admin_Repeater_Component::enqueue_assets();
```

این متد Handleهای زیر را ثبت/بارگذاری می‌کند:

```text
ba-admin-repeater
```

برای Repeater جدید نباید فایل JavaScript جداگانه‌ای فقط برای Add/Remove/Move نوشته شود.

## نمونه پایه در Settings API

فرض می‌کنیم داده با ساختار زیر ذخیره می‌شود:

```php
$settings['items'] = array(
    array(
        'title' => 'عنوان اول',
        'url'   => 'https://example.com',
    ),
);
```

رندر Repeater:

```php
BA_Admin_Repeater_Component::render(
    array(
        'id'           => 'ba-example-items-repeater',
        'items'        => (array) $settings['items'],
        'item_label'   => 'آیتم نمونه',
        'add_label'    => 'افزودن آیتم',
        'empty_label'  => 'هنوز آیتمی اضافه نشده است.',
        'row_renderer' => array( __CLASS__, 'render_item_fields' ),
        'context'      => $option_name,
    )
);
```

Callback فیلدهای ردیف:

```php
/**
 * فیلدهای یک ردیف نمونه را رندر می‌کند.
 *
 * @param string|int $index   اندیس ردیف.
 * @param array      $item    داده ردیف.
 * @param mixed      $context نام Option والد.
 * @return void
 */
public static function render_item_fields( $index, array $item, $context ) {
    $item = wp_parse_args(
        $item,
        array(
            'title' => '',
            'url'   => '',
        )
    );

    $title_name = BA_Admin_Repeater_Component::field_name(
        $context,
        'items',
        $index,
        'title'
    );

    $url_name = BA_Admin_Repeater_Component::field_name(
        $context,
        'items',
        $index,
        'url'
    );

    ?>
    <div class="ba-example-settings__grid">
        <label class="ba-example-settings__field">
            <span class="ba-example-settings__label">عنوان</span>
            <input type="text" name="<?php echo esc_attr( $title_name ); ?>" value="<?php echo esc_attr( $item['title'] ); ?>">
        </label>

        <label class="ba-example-settings__field">
            <span class="ba-example-settings__label">لینک</span>
            <input type="url" name="<?php echo esc_attr( $url_name ); ?>" value="<?php echo esc_attr( $item['url'] ); ?>">
        </label>
    </div>
    <?php
}
```

## ساخت نام فیلد

برای جلوگیری از تکرار رشته‌های نام‌گذاری، از متد زیر استفاده شود:

```php
BA_Admin_Repeater_Component::field_name(
    $base_name,
    $collection,
    $index,
    $field
);
```

مثال:

```php
BA_Admin_Repeater_Component::field_name(
    'ba_jihadi_center_settings',
    'stats',
    2,
    'title'
);
```

خروجی:

```text
ba_jihadi_center_settings[stats][2][title]
```

برای Repeaterهایی که خود آرایه اصلی مستقیماً نام ورودی است، پارامتر `collection` خالی ارسال می‌شود:

```php
BA_Admin_Repeater_Component::field_name(
    'bap_product_faqs',
    '',
    0,
    'question'
);
```

خروجی:

```text
bap_product_faqs[0][question]
```

## Context

پارامتر `context` برای انتقال اطلاعات مورد نیاز Feature به Callback ردیف است. این مقدار می‌تواند string، array یا object باشد.

نمونه:

```php
'context' => array(
    'option_name' => $option_name,
    'screen'      => 'example',
),
```

کامپوننت روی Context هیچ تغییری اعمال نمی‌کند.

## تنظیمات قابل استفاده

آرگومان‌های اصلی `render()`:

| کلید | توضیح |
|---|---|
| `id` | شناسه یکتای Repeater در صفحه |
| `items` | آرایه آیتم‌های فعلی |
| `item_label` | عنوان ثابت هر ردیف |
| `add_label` | متن دکمه افزودن |
| `empty_label` | متن حالت بدون آیتم |
| `row_renderer` | Callback رندر محتوای هر ردیف |
| `context` | داده کمکی برای Callback |
| `show_order_controls` | نمایش/عدم نمایش دکمه‌های بالا و پایین |
| `class_name` | Modifier/Class اختیاری برای ریشه |

## رویداد JavaScript

پس از تغییر Repeater، رویداد زیر روی ریشه Component منتشر می‌شود:

```text
ba:admin-repeater:change
```

در `event.detail.action` یکی از مقادیر زیر قرار می‌گیرد:

```text
add
remove
move-up
move-down
```

نمونه مصرف:

```js
document.addEventListener('ba:admin-repeater:change', (event) => {
    const { action, item } = event.detail;
    // منطق اختصاصی Feature در صورت نیاز.
});
```

تا زمانی که یک Feature واقعاً به رفتار اضافه نیاز ندارد، نباید Listener جدید نوشته شود.

## Sanitize و ذخیره

ذخیره داده باید خارج از Component انجام شود. نمونه:

```php
foreach ( is_array( $items ) ? $items : array() as $item ) {
    if ( ! is_array( $item ) ) {
        continue;
    }

    $clean[] = array(
        'title' => sanitize_text_field( $item['title'] ?? '' ),
        'url'   => esc_url_raw( $item['url'] ?? '' ),
    );
}
```

Sanitize باید متناسب با نوع فیلد باشد و ردیف‌های نامعتبر در همان Feature حذف شوند.

## قوانین توسعه آینده

1. قبل از ساخت Repeater سفارشی جدید، ابتدا از `BA_Admin_Repeater_Component` استفاده شود.
2. Add/Remove/Move/Template/Index نباید دوباره در JavaScript یک Feature پیاده‌سازی شود.
3. فیلدهای داخل ردیف باید BEM مربوط به Feature خودشان را داشته باشند.
4. نام فیلدهای تو در تو ترجیحاً با `field_name()` ساخته شود.
5. Sanitize و Validation باید در Service/Handler همان Feature باقی بماند.
6. اگر رفتار عمومی جدیدی مورد نیاز چند Feature بود، ابتدا Component توسعه داده شود؛ نه اینکه در چند فایل تکرار شود.
7. Repeater رسمی Elementor خارج از محدوده این Component است و باید با `Elementor\Repeater` پیاده‌سازی شود.

## مصرف‌کننده‌های فعلی

از نسخه `v0.3.1` این Component در موارد زیر استفاده می‌شود:

- FAQ اختصاصی محصول ووکامرس
- آمار مرکز حرکت‌های مردمی و جهادی
- کارت‌های بخش‌های مرکز
- همراهان مرکز
- FAQ مرکز در تنظیمات بنیاد علوی
