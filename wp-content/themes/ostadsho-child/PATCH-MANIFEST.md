# Patch Manifest — ostadsho-child v0.5.9

## مبدا و مقصد

- مبدا: `v0.5.8`
- مقصد: `v0.5.9`
- نوع نسخه: PATCH

## هدف

افزودن کنترل‌های کامل Typography و رنگ Normal/Hover برای عنوان و تاریخ آیتم‌های شاخص و ثانویه در سکشن‌های اخبار/مقالات و چندرسانه‌ای.

## فایل‌های داخل Patch

### `inc/elementor/widgets/class-bonyad-alavi-jihadi-center-widget.php`

- افزودن Helper مشترک `register_post_item_text_style_controls()`.
- افزودن چهار گروه مستقل استایل متن برای خبر شاخص، سایر اخبار، چندرسانه‌ای شاخص و سایر آیتم‌های چندرسانه‌ای.
- افزودن Typography مستقل عنوان و تاریخ.
- افزودن رنگ Normal/Hover مستقل عنوان و تاریخ.
- افزودن تاریخ به Markup آیتم شاخص چندرسانه‌ای.
- حفظ شناسه Typographyهای عنوان نسخه قبل برای سازگاری تنظیمات Elementor.

### `assets/css/bonyad-alavi-jihadi-center-widget.css`

- افزودن Transition رنگ برای عنوان و تاریخ مطالب.
- افزودن استایل پایه تاریخ آیتم شاخص چندرسانه‌ای.

### `docs/handoff.md`

- ثبت قرارداد مشترک استایل متن Query Itemها و Helper مربوطه.
- افزودن نسخه `v0.5.9` به تاریخچه.

### `docs/versions/v0.5.9.md`

- مستند کامل تغییرات، سازگاری و تست‌های نسخه.

## Migration

نیازی به Migration داده نیست.

## متن Commit پیشنهادی

`feat: تکمیل استایل عنوان و تاریخ اخبار و چندرسانه‌ای`
