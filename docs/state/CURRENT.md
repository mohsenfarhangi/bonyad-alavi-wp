# Current Project State

آخرین بازبینی: **2026-10-02**

## Project Phase

پروژه در توسعه فعال است.

- **Alavi Form Engine:** `1.0.28-dev`، DB `1.0.5-dev.2`، stable baseline/tag `1.0.27`; production bump هنوز به live acceptance وابسته است.
- **Ostadsho child theme:** custom integration layer روی Theme header `2.8`; version docs legacy v0.x به state history مهاجرت کرده‌اند.

## Current Goal

ویجت Elementor جدید برای سکشن `#about` / `ba-impact` صفحه اصلی بر اساس reference `bonyad-alavi-redesign/redesign/index.html` پیاده‌سازی شده است. مرحله باز این subsystem، پذیرش بصری/رفتاری در Elementor واقعی، به‌خصوص نمودار داینامیک، hover/connectorها و رفتار responsive با تعداد متغیر منابع است.

AFE release goal همچنان live acceptance نسخه 1.0.28 و رفع blocker واقعی پیش از production promotion است.

## Recently Completed

### Homepage About / Impact Elementor widget
- ویجت `bonyad_alavi_home_about` به child theme و دسته «بنیاد علوی» اضافه شد.
- markup اصلی reference سکشن `#about` شامل intro/actions/funding donut/source cards/summary حفظ شده و root class `ba-home-about-widget` فقط برای scope اضافه شده است.
- سه دکمه reference با Repeater آزاد مدیریت می‌شوند و نوع Primary/Secondary دارند.
- منابع دونات Repeater آزاد هستند؛ چهار منبع reference فقط default هستند و کاربر می‌تواند منبع اضافه/حذف کند یا عنوان، مقدار و رنگ را تغییر دهد.
- redesign commit `006cd3fa167491af10cfd764e410388cf2a37c84` روی widget هم sync شد: منبع واحد داده `data-impact-value` است و JS در runtime سهم، gap تطبیقی، dash/offset، زاویه، total و accessibility description را می‌سازد.
- مجموع منابع به‌صورت خودکار از Repeater منابع محاسبه می‌شود و مرکز دونات/summary می‌تواند از آن استفاده کند.
- Summary دارای switch کلی و Repeater مستقل است؛ هر آیتم نیز switch نمایش، icon override، نوع مقدار `funding_total/manual`، decimal و suffix دارد. دو آیتم HTML reference default هستند.
- JS reference برای counter animation، hover linkage، collision-resolved card positioning، connector-to-segment geometry، `ResizeObserver`، `MutationObserver` و `IntersectionObserver` حفظ و فقط per-instance/Elementor-safe شده است.
- بر اساس اصلاح UI مورخ 2026-10-02، fixed orbit کارت‌ها حذف شد؛ هر کارت با توجه به ابعاد خودش در فاصله هدف `16px` از لبه Donut قرار می‌گیرد و حداقل `8px` از مرز funding visual فاصله دارد. این تغییر connectorهای بلند و فضای خالی نامتناسب تصویر گزارش‌شده را کاهش می‌دهد.
- CSS نهایی reference، شامل framing نهایی `ba-impact__grid` و funding pane یکپارچه، با scope ویجت منتقل شده است.
- source commits: `b1b38bd82dbfc35a9f722ee57278198f0a3d6429`, `b1bbf05c01452f34e321bc57ab1d8607237eabb1`, `9995a1324825ae1cb98a3102aadc0b56de5dc654`, `b234215294fae2e4f78a18151f348cb4090414fa`. Latest redesign placement reference: `9f6d01482e21e342ec5eaecb6bf3a515bf99c8fd`.

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

برای About/Impact صفحه اصلی regression contract `tests/home-about-widget-contract.php` latest data-driven contract را پوشش می‌دهد: `data-impact-value/data-impact-total`، runtime geometry، adaptive segment gap، edge-aware card gap=16px، visual inset=8px، حذف fixed orbit، collision resolver، Resize/Mutation/Intersection Observer، CSS responsive، registration و JS per-instance. JavaScript نسخه commit‌شده با parser V8 بدون خطا parse شد. **PHP lint و live WordPress/Elementor acceptance در این session اجرا نشده‌اند.**

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

**Current task:** ویجت About/Impact صفحه اصلی مطابق `#about` / `ba-impact` reference پیاده‌سازی شده است؛ مرحله باز بعدی live Elementor visual/interaction acceptance روی صفحه اصلی واقعی است.

**Start here:** [../INDEX.md](../INDEX.md) → [../modules/INDEX.md](../modules/INDEX.md).

**Do not reconsider without new evidence:** AFE source-definition override model، ownership فرم پروژه‌ای در theme، participation cart isolation، theme shared settings persistence، Jihadi source precedence، shared admin Repeater، export autoload isolation.

**Do not assume:** current HEAD QA PASS، live export acceptance، vendor availability یا production readiness.

**History lookup:** [INDEX.md](INDEX.md) → `state-v002.md` برای legacy milestones.
