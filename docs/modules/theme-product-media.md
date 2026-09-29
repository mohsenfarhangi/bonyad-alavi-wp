# MODULE-THEME-MEDIA — Product Media and Carousel

**Status:** implemented  
**Primary paths:** `inc/services/`, `inc/helpers/`, `inc/elementor/widgets/`

## Purpose

منطق انتخاب/نمایش تصاویر محصول ووکامرس بین participation widget و product-gallery widget مشترک باشد و carousel/lightbox behavior در featureها duplicate نشود.

## Main Components

- `BA_Product_Media_Service`
- `BA_Media_Helper`
- `class-bonyad-alavi-product-gallery-widget.php`
- participation widget
- `ba-product-carousel-core.js`

## Media Source Contract

برای تصویر پروژه/محصول، WooCommerce product media منبع است؛ کنترل دستی قدیمی `project_image` از participation widget حذف شده است.

ترتیب عملی:
1. featured image
2. gallery images
3. WooCommerce placeholder در نبود تصویر واقعی

Placeholder وارد lightbox نمی‌شود.

## Participation Widget Gallery

وقتی gallery واقعی و حداقل دو تصویر قابل نمایش وجود دارد:
- media area به carousel تبدیل می‌شود.
- previous/next، keyboard arrow و touch swipe پشتیبانی می‌شود.
- lightbox فقط تصاویر واقعی محصول را می‌گیرد.
- باز شدن lightbox از slide فعال current شروع می‌شود.
- دکمه zoom/gallery متناسب با حالت gallery نمایش داده می‌شود.

در نبود carousel، تصویر اول معتبر ثابت نمایش داده می‌شود.

## Product Gallery Widget

Controls اصلی:
- carousel on/off
- max image count
- aspect ratio: 1:1، 4:3، 3:2، 16:9
- object fit: cover/contain
- radius
- arrow size

وقتی carousel off است یا فقط یک تصویر وجود دارد، navigation/lightbox carousel behavior فعال نمی‌شود.

## Loading Strategy

legacy v0.2.1:
- وقتی carousel واقعاً چندتصویری است، تصویر اول eager و تصاویر بعدی lazy هستند.
- در single-image/non-carousel، رفتار lazy تصویر تکی حفظ می‌شود.
- `decoding="async"` برای تصاویر حفظ می‌شود.

## Design Constraints

- media/query helper مشترک را دور نزن.
- Elementor bootstrap/asset registration تکرار نشود.
- JS listenerها هنگام destroy widget cleanup شوند.
- BEM هر widget حفظ شود.

## History

جزئیات نسخه‌های legacy v0.2.0/v0.2.1 در [state-v002](../state/state-v002.md) خلاصه شده و version docs قدیمی حذف شده‌اند.
