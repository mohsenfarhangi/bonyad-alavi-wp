# Current Project State

آخرین بازبینی: **2026-10-08**

## Project Phase

پروژه در توسعه فعال است.

- **Alavi Form Engine:** `1.0.28-dev`، DB `1.0.5-dev.2`، stable baseline/tag `1.0.27`; production bump هنوز به live acceptance وابسته است.
- **Ostadsho child theme:** custom integration layer روی Theme header `2.8`; version docs legacy v0.x به state history مهاجرت کرده‌اند.

## Current Goal

ویجت عمومی Elementor برای `ba-section-heading` اکنون به‌صورت مستقل و قابل استفاده در هر جای Elementor پیاده‌سازی شده است؛ مرحله باز، پذیرش بصری در Elementor واقعی است. News و About/Impact implementation داخلی خودشان را بدون تغییر حفظ می‌کنند و live acceptance آن‌ها نیز همچنان باز است.

AFE release goal همچنان live acceptance نسخه 1.0.28 و رفع blocker واقعی پیش از production promotion است.

## Recently Completed

### Hero mobile ticker
- تیکر ویجت Hero برای `max-width:760px` به track افقی مستقل و حرکت چپ به راست تبدیل شد؛ متن لینک‌ها RTL و label ثابت است.
- منطق عمودی قبلی به `initVerticalTicker` انتقال یافت و با switch/cleanup در breakpoint 760 مدیریت می‌شود.
- CSS فقط به انتهای asset موجود اضافه شده؛ PHP، Query و Elementor controls تغییری نکردند.
- بررسی ساختار JS/CSS و قراردادها انجام شد؛ آزمون مرورگری زنده در محیط وردپرس انجام نشده است.



### Mobile menu JS integration
- Added `ostadsho-child/assets/js/alavi-mobile-menu.js` with supplied mobile Elementor menu behaviors, null guards and accessible keyboard support.
- Enqueued in footer globally via `functions.php`, handle `ba-mobile-menu`, versioned by `filemtime`.
- JavaScript parses, and enqueue contract is verified. Live browser acceptance is pending.



### Mobile menu CSS asset
- استایل سفارشی منوی موبایل در `wp-content/themes/ostadsho-child/assets/css/alavi-mobile-menu.css` مستقل قرار گرفت.
- بلوک CSS ارسالی تکراری بود؛ فقط یک نسخه با همان selectorها و مقادیر CSS ثبت شد.
- فایل با handle `ba-mobile-menu` در `functions.php` روی همه صفحات فرانت‌اند enqueue می‌شود؛ خود قوانین به `max-width:1024px` و `prefers-reduced-motion` محدود هستند.
- تست ساختاری selector/brace و enqueue انجام شد؛ تست بصری در وردپرس زنده هنوز انجام نشده است.



### Global custom JavaScript in child theme
- فایل `wp-content/themes/ostadsho-child/assets/js/custom.js` برای کدهای JS عمومی فرانت‌اند اضافه شد.
- رفتار overflow منوی Elementor header با selector مشخص‌شده، بدون تغییر عملکردی، در این فایل قرار گرفت.
- `functions.php` فایل را با handle `ba-global-custom` روی hook عمومی `wp_enqueue_scripts` و نسخه `filemtime` در فوتر بارگذاری می‌کند.
- syntax فایل JS بررسی شد؛ تست مرورگری در سایت واقعی هنوز انجام نشده است.
- source commits: `617e81aa85b9f53007b300c7d8c39e8c81ab7437`, `b95a58a83d382431bdef385bbcc57ca5328498f4`.


### Reusable Section Heading Elementor widget
- ویجت مستقل `bonyad_alavi_section_heading` به دسته «بنیاد علوی» اضافه شد و در هر جای Elementor قابل استفاده است.
- markup و baseline CSS از `ba-section-heading` در redesign reference گرفته شده است.
- سه layout دارد: `Default`، `Stack` و `Card`؛ حالت Card همان contract مرجع `--stack + --card` را تولید می‌کند.
- محتوا شامل eyebrow، title، title HTML tag، lead، action text و action URL است؛ هر بخش خالی رندر نمی‌شود.
- اکشن فقط وقتی رندر می‌شود که متن و لینک هر دو موجود باشند؛ SVG فلش reference ثابت است و Icon Control اضافه نشده است.
- Style controls برای layout/alignment/gap، eyebrow/line، title، lead/max-width و action/hover/icon size بدون visual default اضافه شده‌اند.
- widget JavaScript ندارد و CSS با root `ba-section-heading-widget` scope شده است.
- طبق تصمیم پروژه، News/About و سایر ویجت‌های موجود برای استفاده از این ویجت refactor نشده‌اند.
- regression contract: `tests/section-heading-widget-contract.php`.
- static contract verification روی source commit‌شده PASS شد؛ PHP lint و live Elementor acceptance هنوز اجرا نشده‌اند.
- source commit: `62090c810d12c849d6e588a30641534281f763af`.

### Homepage News Elementor widget
- ویجت `bonyad_alavi_home_news` به child theme و دسته «بنیاد علوی» اضافه شد.
- baseline markup/CSS از `redesign/index.html#news`، `components.css` و `home.css` حفظ شده است؛ root اضافی `ba-home-news-widget` فقط scope قالب است.
- گرید reference بدون JavaScript باقی مانده: desktop چهار ستون، tablet دو ستون و mobile یک ستون.
- اخبار از `BA_Content_Query_Service::create_query( $settings, 'news' )` و `WP_Query` تغذیه می‌شوند؛ default چهار نوشته آخر `post` با ترتیب تاریخ DESC است.
- Query controls شامل Post Type، تعداد، category/tag، author، search، include/exclude IDs، order/orderby، offset، sticky behavior، date range و Taxonomy Query ساختاریافته با relation/operator/field/include_children است.
- `BA_Content_Query_Service` برای search و generic tax_query توسعه یافت؛ prefixهای قبلی بدون تنظیمات جدید همان رفتار قبلی را حفظ می‌کنند.
- تاریخ کارت از تاریخ واقعی نوشته با `get_the_date()` تولید می‌شود؛ فرمت پیش‌فرض `F Y` است و فارسی‌ساز سایت مسئول تبدیل شمسی/ارقام است.
- برچسب کارت به‌صورت پیش‌فرض اولین category است و taxonomy قابل تغییر/خاموش‌شدن است.
- تصویر و عنوان هر دو به permalink متصل‌اند؛ خبر بدون featured image یک placeholder داخلی و بدون asset خارجی دارد.
- متن/لینک «همه اخبار» کنترل مستقل دارند و اگر هرکدام خالی باشد اکشن رندر نمی‌شود.
- Style controls بدون visual default هستند و فقط override صریح کاربر CSS reference را تغییر می‌دهد.
- source commits: `bcf67bacac8cca89a91cc4fee3a32f9fc31dbae3`, `6ca7b8be560db0921f3655a10f4ec2ceb981d653`.

### Homepage About / Impact Elementor widget
- ویجت `bonyad_alavi_home_about` Intro / CTA / Funding Sources / KPI خدمات‌گیرندگان را از Elementor مدیریت می‌کند.
- Donut با Apache ECharts 6.1.0 محلی رندر می‌شود؛ runtime CDN وجود ندارد.
- Funding Source Repeater شامل label/value/color است و چهار source reference default هستند.
- `ba-impact__funding-head` و کنترل‌های `funding_kicker/funding_note` کامل حذف شدند.
- Summary Repeater، show/hide Summary، iconها، auto-total/manual Summary items و DOM/CSS مربوط به `ba-impact__summary` کامل حذف شدند.
- فقط `beneficiaries_label` و `beneficiaries_value` باقی مانده‌اند؛ خروجی به‌صورت `ba-impact__beneficiaries` بدون کادر، background، border یا icon و وسط‌چین زیر نمودار است.
- مجموع منابع فقط در مرکز Donut نمایش داده می‌شود.
- radius ECharts همان `['42%','58%']` است و chart heights تغییر نکرده‌اند.
- سکشن در هر سه breakpoint متراکم‌تر شده: desktop 56/52، <=1080 50/48، <=760 40/38 و <=430 36/34؛ content/funding padding نیز کم شده است.
- ECharts مالک arc geometry، external label layout، labelLine و overlap handling است؛ chart با RTL isolation و edge-aligned labels از clipping جلوگیری می‌کند. در هر لیبل، مقدار عددی در خط بالا و نقطه رنگی + عنوان منبع در خط پایین نمایش داده می‌شود.
- دکمه‌های `ba-impact__actions` در hover حداکثر 2px بالا می‌آیند، پس‌زمینه و border سبز می‌شوند و متن سفید می‌شود؛ افکت خط/زیرخط حذف شده و reduced-motion حرکت را خاموش می‌کند.
- source commits: `6c326cd0d5e87862ab72f89f74c59d881f778872`, `04720f6e0287fdfb73778ef840149ad945ecf1d0`, `132cfc1490c192afebacf34293fde883a6707f45`.
- latest redesign commits: `70e47cf5f918fe92fc993e573057de977db87093`, `56346827a618ebd44b8a2537fdb69b102bca775b`.

### Homepage Live Stats Elementor widget
- ویجت `bonyad_alavi_home_live_stats` به child theme و دسته «بنیاد علوی» اضافه شد.
- markup اصلی reference شامل `ba-live-stats`, `ba-container`, panel/title/grid/item/value/label حفظ شده و فقط root class `ba-home-live-stats-widget` برای scope اضافه شده است.
- عنوان گزارش دو فیلد مستقل با defaultهای `گزارش برخط` و `اقدامات` دارد.
- Repeater آمار فقط `NUMBER` + label دارد؛ کاربر عدد خام بدون جداکننده وارد می‌کند و frontend آن را با ارقام فارسی و جداکننده هزارگان `٬` نمایش می‌دهد.
- ۶ مقدار/عنوان reference به‌صورت raw number default هستند.
- CSS حالت نهایی reference حفظ شده: green shell + white rounded stats card، desktop 6 columns، tablet 3 columns، mobile 2 columns.
- این block در reference هیچ JavaScriptی ندارد؛ برای widget نیز هیچ JS asset/dependency اضافه نشده است.
- Style controls بدون visual default هستند و فقط تغییر صریح کاربر reference CSS را override می‌کند.
- source commit: `51a6666f7fd1447075cfdd2a51afeeb946953ec1`.

### Homepage Quick Links Elementor widget
- ویجت `bonyad_alavi_home_quick_links` به child theme اضافه و در دسته «بنیاد علوی» ثبت شد.
- baseline markup/CSS از `redesign/index.html` و `home.css` برای block `ba-quick-links` حفظ شده است؛ root اضافی `ba-home-quick-links-widget` فقط scope قالب است.
- ۹ آیتم reference با همان label/link، SVGهای Tabler و رنگ‌های semantic پیش‌فرض تعریف شدند.
- Repeater شامل عنوان، لینک، Elementor Icon جایگزین و Color Control برای دایره آیکون است؛ رنگ reference تا زمان تغییر کاربر بدون inline override باقی می‌ماند.
- anchor `#services` حفظ شده است.
- دکمه «بیشتر/جمع کردن» و محاسبه overflow همان الگوریتم reference را دارد و فقط برای multiple Elementor instances root-scoped شده است.
- responsive reference حفظ شده: desktop item=116/media=58، <=760 item=98/media=57، <=430 item=86 و label=10px.
- Style controls بدون visual default اضافه شدند تا baseline reference فقط با تغییر صریح کاربر override شود.
- source commits: `28c2688e67d3ec3def18472f03d05eb182fd4808`, `392c26aba8959d7aa47927b7493ded9d5e9d73fb`, `d663991b83c001e5f47d992369707cd8dd939ddc`.

### Contact form in child theme
- Source Definition جدید `contact-us` در `ostadsho-child/inc/forms/class-ba-contact-form.php` اضافه شد و از `functions.php` روی `afe_register_forms` ثبت می‌شود.
- فرم تک‌مرحله‌ای شامل نام و نام خانوادگی، شماره همراه، ایمیل، استان، موضوع و متن پیام است.
- فیلد `province` از Data Source استاندارد AFE با `type=geo` و `level=province` تغذیه می‌شود و لیست استان‌ها در قالب hard-code نشده است.
- شماره همراه از validator `mobile_09` و mask `mobile_ir` استفاده می‌کند.
- Wizard/Progress/Save Draft خاموش، CAPTCHA سفارشی فعال و rate limit برابر 5 است.
- اکشن `notify_admin_email_on_submit` بعد از `submission.submitted` یک‌بار برای ایمیل مدیر سایت اجرا می‌شود و از field tokens برای موضوع/بدنه استفاده می‌کند.
- shortcode استفاده: `[alavi_form id="contact-us"]`.
- source commit: `5f181fe082f5879072262f15ba3e420c58a7b4b7`.

### Homepage Hero Elementor widget
- Hero widget visual baseline دوباره با reference اصلی همسان شد: DOM classes به `ba-hero__grid / ba-mission-nav / ba-slider / ba-ticker` برگشت و CSS بر پایه مقادیر نهایی `redesign/assets/css/home.css` بازنویسی شد.
- Style Tab دیگر default ظاهری تزریق نمی‌کند؛ فقط تغییر صریح کاربر CSS مرجع را override می‌کند. Style control IDs نیز با prefix `ref_` migrate شدند تا overrideهای ذخیره‌شده نسخه قبلی روی instance موجود صفحه خنثی شوند.
- Mission Nav defaults از SVGهای اصلی redesign (`briefcase-2`, `school`, `stethoscope`, `building-community`) استفاده می‌کند؛ Elementor icon selection همان SVG را per item جایگزین می‌کند و legacy Font Awesome defaults نیز map می‌شوند.
- بخش `ba-hero__grid` از reference repository `bonyad-alavi-redesign/redesign/index.html` به ویجت `bonyad_alavi_home_hero` در child theme تبدیل شد.
- Slider و Mission Nav با Elementor Repeater مدیریت می‌شوند؛ تصویر اسلاید فقط Media Control دارد و هیچ تصویر reference به قالب کپی نشده است.
- Ticker از `BA_Content_Query_Service` استفاده می‌کند و Query کامل Elementor دارد؛ پیش‌فرض آخرین 3 نوشته است و فقط عنوان + permalink رندر می‌شود.
- قرارداد لینک اسلاید: دکمه فقط با متن+لینک نمایش داده می‌شود؛ لینک بدون متن دکمه کل اسلاید را clickable می‌کند.
- Style Tab شامل Typography/Color/Background و stateهای Normal/Hover برای نقاط تعاملی است؛ CSS با block `ba-home-hero` scope شده است.
- JS برای چند instance اسکوپ شده، با Elementor frontend hook، keyboard navigation، autoplay/pause و reduced-motion سازگار است.
- source commit: `dc1eb832c1a3582f722dde7085bb7d945e31e537`.

### Jihadi form ownership transfer
- Source Definition فرم `jihadi-group-registration` از `alavi-form-engine/src/Forms` به `ostadsho-child/inc/forms` منتقل شد.
- ثبت مستقیم فرم از `Core\Plugin` حذف و registration به `afe_register_forms` در child theme منتقل شد.
- AFE boot از `plugins_loaded` به `after_setup_theme` priority 20 منتقل شد تا theme registration window معتبر باشد.
- slug فرم تغییر نکرد؛ migration دیتابیس لازم نیست.
- تست‌های اختصاصی فرم از suite افزونه خارج و regression theme جایگزین شد؛ یک regression برای bootstrap registration window نیز به AFE اضافه شد.

### Persistent documentation migration
- دانش AFE legacy از Project State، Architecture، Developer، Acceptance، Feature Completeness، Build Report و Handoff به module/ADR/state canonical منتقل شد.
- دانش theme legacy از Handoff، Admin Repeater guide، v0.1.0→v0.6.5 version docs و Patch Manifests منتقل شد.
- legacy duplicate docs حذف شدند.
- package-facing AFE `README.md`, `readme.txt`, `THIRD-PARTY-NOTICES.md` حفظ شدند و README references به docs canonical هدایت شدند.

### Latest product baseline before documentation-only commits
`2b7cc8fecb8be39d40c2562349ea8c0b6adce12e`

AFE changes در آن baseline:
- admin masked data به normalized/raw presentation نزدیک شد.
- frontend preview می‌تواند mask presentation را اعمال کند.
- 24-digit IBAN preset بدون فاصله شد.
- per-form custom CSS همراه render form/locked preview output می‌شود.
- mask regression tests به‌روزرسانی شدند.

## Currently In Progress

هیچ feature task نیمه‌تمام قابل اثبات در repository ثبت نشده است.

Open release work برای AFE:
- live acceptance WordPress/MySQL/Elementor
- Excel collision verification در سایت واقعی با foreign Composer vendor
- PDF فارسی/font runtime verification
- export override/reset verification
- live duplicate verification روی legacy submission

## Blocked / Known Issues

- AFE به `1.0.28` / DB `1.0.5` / Stable `1.0.28` تا PASS acceptance زنده bump نشود.
- `vendor/` AFE در Git نیست؛ export deployment به Composer build و PDF font metadata وابسته است.
- documented checkpoint 11.3 QA یک PASS تاریخی است؛ بعد از product commit `2b7cc8f` full QA در این documentation migration اجرا نشده، پس HEAD فعلی **unverified** است.
- WordPress core، parent theme و third-party plugins در repo نیستند؛ integration tests محیط کامل می‌خواهند.

## Important Active Decisions

- [ADR-001 — Code-defined forms](../decisions/ADR-001-code-defined-forms.md)
- [ADR-002 — Isolated participation checkout](../decisions/ADR-002-participation-quick-checkout.md)
- [ADR-003 — Shared theme settings persistence](../decisions/ADR-003-shared-theme-settings-persistence.md)
- [ADR-004 — Jihadi Center source precedence](../decisions/ADR-004-jihadi-center-source-precedence.md)
- [ADR-005 — Shared admin Repeater](../decisions/ADR-005-shared-admin-repeater.md)
- [ADR-006 — AFE export dependency isolation](../decisions/ADR-006-afe-export-dependency-isolation.md)
- [ADR-007 — Project form ownership](../decisions/ADR-007-project-form-ownership.md)

## Relevant Modules

- [MODULE-AFE](../modules/alavi-form-engine.md)
- [MODULE-AFE-EXTENSION](../modules/alavi-form-engine-extension-api.md)
- [MODULE-THEME](../modules/ostadsho-child-theme.md)
- [MODULE-THEME-ADMIN](../modules/theme-admin-settings.md)
- [MODULE-THEME-MEDIA](../modules/theme-product-media.md)
- [MODULE-PARTICIPATION](../modules/participation-payments.md)
- [MODULE-JIHADI-CENTER](../modules/jihadi-center.md)
- [MODULE-JIHADI-FORM](../modules/jihadi-group-registration-form.md)
- [MODULE-CONTACT-FORM](../modules/contact-form.md)
- [MODULE-HOME-HERO](../modules/theme-home-hero-widget.md)
- [MODULE-HOME-QUICK-LINKS](../modules/theme-home-quick-links-widget.md)
- [MODULE-HOME-LIVE-STATS](../modules/theme-home-live-stats-widget.md)
- [MODULE-HOME-ABOUT](../modules/theme-home-about-widget.md)
- [MODULE-HOME-NEWS](../modules/theme-home-news-widget.md)
- [MODULE-SECTION-HEADING](../modules/theme-section-heading-widget.md)

## Tests Status

Historical AFE checkpoint 11.3:
- 57/57 regression PASS
- 168 PHP lint PASS
- JS syntax PASS
- composer JSON PASS
- runtime CDN check PASS
- extracted ZIP QA/integrity PASS

Real MeliPayamak test: PASS reported by project administrator on 2026-09-04.

Theme legacy v0.6.5 docs recorded PHP/JS syntax + ZIP checks for that patch.

برای فرم تماس با ما regression `tests/contact-form-definition.php` اضافه شده است؛ source/test روی draft محلی PHP lint شدند و نسخه commit‌شده از GitHub برای slug، Geo province، required fields، admin email action و theme registration بازبینی استاتیک شد. **اجرای regression در checkout کامل repository و live WordPress هنوز در این session انجام نشده است.**

برای Section Heading عمومی regression contract `tests/section-heading-widget-contract.php` اضافه شده است. registration، نبود JS dependency، سه variant، content controls، شرط اکشن، SVG ثابت، markup/CSS reference، responsive mobile و scope CSS به‌صورت استاتیک از GitHub بررسی و PASS شدند. **PHP lint و live WordPress/Elementor acceptance در این session اجرا نشده‌اند.**

برای News صفحه اصلی regression contract `tests/home-news-widget-contract.php` اضافه شده است. روی نسخه commit‌شده، registration، نبود JS dependency، shared WP_Query service، کنترل‌های core/advanced Query، فرمت تاریخ، clickable title، placeholder، شرط نمایش «همه اخبار»، markup/CSS reference و responsive 4/2/1 به‌صورت استاتیک از GitHub بررسی و همگی PASS شدند. **PHP lint و اجرای regression در checkout کامل repository و live WordPress/Elementor در این session اجرا نشده‌اند.**

برای About/Impact صفحه اصلی regression contract `tests/home-about-widget-contract.php` علاوه بر ECharts، حذف کامل funding-head/Summary، KPI ساده خدمات‌گیرندگان، density responsive و ثابت‌ماندن chart height/radius را guard می‌کند. JavaScript نسخه commit‌شده با parser V8 بدون خطا parse شد. **PHP lint به‌دلیل عدم resolve شدن raw GitHub در shell این session اجرا نشد و live WordPress/Elementor acceptance هنوز باز است.**

برای Live Stats صفحه اصلی regression contract جدید `tests/home-live-stats-widget-contract.php` اضافه شده است. روی نسخه commit‌شده، markup، NUMBER-only input، ۶ default، formatter frontend، desktop/tablet/mobile CSS و نبود JS dependency به‌صورت استاتیک بازبینی شد. **PHP lint و live WordPress/Elementor acceptance در این session اجرا نشده‌اند.**

برای Quick Links صفحه اصلی regression contract جدید `tests/home-quick-links-widget-contract.php` اضافه شده است. روی نسخه commit‌شده، contractهای markup/reference SVGs/colors/responsive/overflow/registration با GitHub source بازبینی و JavaScript با parser V8 بررسی شد. **PHP lint و live WordPress/Elementor acceptance در این session اجرا نشده‌اند.**

برای Hero صفحه اصلی فایل regression contract جدید `tests/home-hero-widget-contract.php` اضافه شده است. روی draft محلی معادل این implementation، PHP lint، JavaScript syntax و contract checks PASS شدند؛ نسخه commit‌شده نیز از GitHub برای registration، Query reuse، link contract، نبود تصاویر پیش‌فرض، scope CSS و Elementor JS hook بازبینی استاتیک شد. **اجرای live WordPress/Elementor روی HEAD جدید هنوز تأیید نشده است.**

Historical PASSهای AFE/Theme به HEAD جدید تعمیم داده نمی‌شوند.

## Next Actions

1. task domain را از [modules/INDEX.md](../modules/INDEX.md) انتخاب کن.
2. فقط module + ADR مرتبط + source/test files همان subsystem را load کن.
3. AFE code change → domain tests + `tools/qa.sh`; export scope → vendor/font checks.
4. Theme code change → PHP/JS syntax + integration-specific checks.
5. بعد از تغییر مهم، CURRENT + state active + module/ADR/index متاثر را update کن.
6. parallel handoff/version documentation tree دوباره ایجاد نکن.

## Next Agent Handoff

**Current task:** ویجت عمومی `bonyad_alavi_section_heading` با سه variant مرجع ساخته شده و آماده live Elementor visual acceptance است. News/About عمداً refactor نشده‌اند و contract داخلی خودشان را حفظ می‌کنند.

**Start here:** [../INDEX.md](../INDEX.md) → [../modules/INDEX.md](../modules/INDEX.md).

**Do not reconsider without new evidence:** AFE source-definition override model، ownership فرم پروژه‌ای در theme، participation cart isolation، theme shared settings persistence، Jihadi source precedence، shared admin Repeater، export autoload isolation.

**Do not assume:** current HEAD QA PASS، live export acceptance، vendor availability یا production readiness.

**History lookup:** [INDEX.md](INDEX.md) → `state-v002.md` برای legacy milestones.
