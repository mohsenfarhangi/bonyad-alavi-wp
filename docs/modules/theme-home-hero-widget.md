# MODULE-HOME-HERO — Homepage Hero Elementor Widget

**Status:** implemented / live acceptance pending  
**Path:** `wp-content/themes/ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-home-hero-widget.php`  
**Elementor name:** `bonyad_alavi_home_hero`  
**Reference:** `mohsenfarhangi/bonyad-alavi-redesign/redesign/index.html` → `ba-hero__grid`

## Purpose

Hero جدید صفحه اصلی را به یک ویجت مستقل Elementor تبدیل می‌کند. قرارداد اصلی این ماژول این است که baseline مارک‌آپ و CSS دقیقاً از `redesign/index.html` و `redesign/assets/css/home.css` گرفته شود؛ Elementor فقط محتوا و overrideهای صریح کاربر را مدیریت می‌کند و نباید ظاهر مرجع را با defaultهای جداگانه تغییر دهد.

Assets:
- `assets/css/bonyad-alavi-home-hero-widget.css`
- `assets/js/bonyad-alavi-home-hero-widget.js`

Registration:
- `inc/elementor/elementor-widgets.php`
- style/script فقط register می‌شوند و از `get_style_depends()` / `get_script_depends()` ویجت load می‌شوند.

## Composition

### Mission Nav

Elementor Repeater:
- Icon
- Title
- Description
- Link

Defaultها چهار معاونت reference هستند. برای این چهار آیتم، SVGهای اصلی خود redesign به‌صورت fallback داخلی نگهداری می‌شوند: `tabler:briefcase-2`، `tabler:school`، `tabler:stethoscope` و `tabler:building-community`.

Icon contract:
- تا وقتی کاربر آیکون جدیدی در Elementor انتخاب نکرده باشد، SVG اصلی redesign نمایش داده می‌شود.
- انتخاب آیکون جدید با Elementor Icons، SVG پیش‌فرض را برای همان آیتم جایگزین می‌کند.
- چهار Font Awesome پیش‌فرض نسخه قبلی به‌عنوان legacy شناخته می‌شوند و خودکار به SVG متناظر برمی‌گردند تا نمونه‌های ذخیره‌شده قدیمی نیاز به ویرایش دستی نداشته باشند.
- آیتم جدیدی که default key ندارد و کاربر برای آن آیکون انتخاب نکرده، آیکون تصادفی دریافت نمی‌کند.

Rendering contract:
- Title خالی → title markup رندر نمی‌شود.
- Description خالی → description markup رندر نمی‌شود.
- Link خالی → item با همان ساختار بصری به‌صورت non-clickable `div` رندر می‌شود.
- Link موجود → item یک `a` امن با support برای external/nofollow است.
- Icon سفارشی با `Elementor\Icons_Manager` رندر می‌شود؛ SVG پیش‌فرض از map داخلی امن ویجت رندر می‌شود.

### Slider

Elementor Repeater:
- Media image
- Tag
- Title
- Description
- Button text
- Link

هیچ فایل تصویر reference از redesign در theme bundle نشده است. مدیر محتوا باید تصویر را از Media Control انتخاب کند.

Rendering contract:
- tag/title/description خالی → markup متناظر رندر نمی‌شود.
- Button فقط وقتی **هم button text و هم URL** موجود باشند رندر می‌شود.
- اگر URL موجود و button text خالی باشد، کل slide clickable می‌شود.
- بدون URL، slide لینک کلی ندارد.
- HTML tag عنوان قابل انتخاب است؛ default = `h2`.
- تصویر اول eager و تصاویر بعدی lazy هستند؛ decoding async است.
- اگر تصویر انتخاب نشده باشد، محتوای slide روی background اسلایدر باقی می‌ماند.

Behavior:
- autoplay default 6200 ms
- optional pause on hover/focus
- arrows و dots قابل خاموش/روشن شدن
- keyboard ArrowLeft/ArrowRight
- multiple widget instances مستقل
- reduced-motion → autoplay/transition behavior محدود می‌شود.

### Important News Ticker

منبع محتوا Repeater نیست؛ shared service:
`BA_Content_Query_Service::create_query( $settings, 'ticker' )`

Default:
- Post Type = `post`
- Posts per page = 3
- OrderBy = date
- Order = DESC
- Ignore sticky = yes

Controls:
- Post Type
- Count
- Categories
- Tags
- Authors
- Include IDs
- Exclude IDs
- OrderBy
- Order
- Offset
- Ignore sticky
- Date after/before

Output هر item:
- Post title
- Permalink

Ticker با JavaScript per-instance عمودی جابه‌جا می‌شود؛ hover/focus آن را pause می‌کند.

## Responsive Contract

### Desktop > 1080px
- Grid reference: `minmax(220px, .82fr) minmax(0, 2.25fr)`
- Areas: `mission slider` / `mission ticker`
- rows: `410px 48px`
- Mission column + Slider و Ticker زیر Slider
- Mission width فقط در صورت override صریح کاربر قابل تغییر است.

### Tablet 761–1080px
ترتیب:
1. Slider
2. Ticker
3. Mission Nav

Mission Nav به grid دو ستونه تبدیل می‌شود.

### Mobile <= 760px
flex order:
1. Slider
2. Ticker
3. Mission Nav

Slider ratio-driven است؛ default `4 / 3` و از Style control قابل تغییر است. Mission description در موبایل مخفی نمی‌شود.

## Style Tab Contract

Style controls به‌صورت scoped روی کلاس‌های اصلی reference (`.ba-mission-nav`, `.ba-slider`, `.ba-ticker` و ...) اعمال می‌شوند. این کنترل‌ها عمداً **visual default ندارند**؛ تا وقتی کاربر مقداری را تغییر نداده، CSS فایل مرجع authority ظاهری است.

Sections:
- Layout / dimensions
- Mission panel/items/icons
- Slider / overlay / image / content
- Slider navigation
- Ticker

Typography برای همه text surfaces:
- Mission title
- Mission description
- Slider tag
- Slider title
- Slider description
- Slider button
- Ticker label
- Ticker item title

Color/background:
- متن‌ها
- panel/item/button/ticker backgrounds در محل لازم
- borders
- icon foreground/background/border
- slider overlay
- arrows/dots

Hover states مستقل:
- Mission item/title/description/icon
- Slider button
- Slider arrows
- Slider dots
- Ticker item

## BEM / Scope

کلاس‌های DOM اصلی برای fidelity با reference حفظ می‌شوند: `ba-hero`, `ba-container`, `ba-hero__grid`, `ba-mission-nav`, `ba-slider`, `ba-ticker` و elementهای همان blockها.

فقط یک scope کمکی `ba-home-hero-widget` روی root اضافه شده تا CSS مرجع بدون نشت به سایر صفحات/کامپوننت‌ها اجرا شود. Global selectors برای body/html/forms استفاده نمی‌شوند. JavaScript نیز فقط root همان widget instance را mutate می‌کند.

## Shared Dependencies

- `BA_Media_Helper` برای render تصویر
- `BA_Content_Query_Service` برای Query
- Elementor Repeater / Icons Manager / Style Controls

Query logic جدید داخل widget duplicate نشود؛ در صورت نیاز به capability عمومی جدید، shared query service توسعه داده شود.

## QA

Regression contract:
`wp-content/themes/ostadsho-child/tests/home-hero-widget-contract.php`

موارد guardشده:
- widget slug
- Slider/Mission Repeaters
- reference SVG defaults + custom Elementor icon override + legacy default icon compatibility
- shared ticker query
- default 3 posts
- whole-slide link behavior
- button text+URL contract
- absence of bundled reference images
- typography/hover controls
- BEM CSS scope
- mobile aspect ratio
- Elementor JS hook
- multi-instance root scoping
- reduced-motion

پس از refactor fidelity، فایل‌های HEAD با reference برای class structure، grid/rows، slider content clamp، mobile order، ticker dimensions، mobile ratio و SVG defaults مقایسه استاتیک شدند؛ JavaScript نیز با parser V8 syntax-check شد و brace balance PHP/CSS بررسی شد. اجرای PHP regression و Live WordPress/Elementor acceptance همچنان باز است.

## Live Acceptance Checklist

1. Widget در دسته «بنیاد علوی» دیده شود.
2. Repeater اسلاید و Mission در Editor بدون console error کار کند.
3. Media انتخاب‌شده صحیح render شود؛ slide بدون image نیز layout را نشکند.
4. همه حالت‌های link contract تست شوند.
5. Query ticker با category/post-type/include/exclude/date کار کند.
6. چند instance در یک صفحه timer/state مشترک نداشته باشند.
7. autoplay، hover/focus pause، arrows/dots و keyboard تست شوند.
8. desktop/tablet/mobile با breakpointهای reference بررسی شوند.
9. Style controls و Normal/Hover در frontend مطابق Editor اعمال شوند.
10. reduced-motion و empty content behavior بررسی شوند.
