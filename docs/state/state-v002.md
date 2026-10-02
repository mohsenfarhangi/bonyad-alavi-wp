# State v002 — Legacy Documentation Consolidation

Period: **legacy project history → 2026-09-29**

این state خلاصه معنی‌دار مستندات legacy را قبل از حذف آن‌ها ثبت می‌کند. هدف حفظ evolution و نقاط باز مهم است، نه نگهداری terminal-log یا هر تغییر ظاهری کوچک.

## STATE-002-001 — Documentation sources consolidated

Date: 2026-09-29

Legacy sources reviewed:
- AFE build report، handoff، architecture/developer/acceptance/feature-completeness/project-state docs
- Theme handoff، admin-repeater component guide، version docs v0.1.0 تا v0.6.5، patch manifests

Current implementation contracts به root modules/ADRs منتقل شدند. تاریخچه معنی‌دار در همین state ثبت شد.

Preserved package-facing docs:
- AFE `README.md`
- AFE `readme.txt`
- AFE `THIRD-PARTY-NOTICES.md`

## STATE-002-002 — AFE historical milestones imported

Legacy AFE documentation records this evolution:

- **1.0.5 geography importer:** authenticated Ajax chunk import، حدود 32 KiB client chunks، incremental bulk upsert، retry/idempotency.
- **1.0.6 custom select isolation:** AFE custom select default؛ native select remains submission authority؛ theme Select2 isolation.
- **1.0.11 style isolation:** tokens moved under `.afe-shell`; strong/default/disabled modes.
- **1.0.12 upload UX:** native file input retained behind AFE dropzone؛ event isolation from theme scripts.
- **1.0.14 file ownership/safe replacement:** submission+field ownership، keep/remove manifest، rollback protection.
- **1.0.14 cross-step validation UX:** grouped final error summary، step navigation/error indicators.
- **1.0.15 upload hotfix:** scalar/nested upload normalization، client manifest، transport error mapping.
- **1.0.17:** generic Form Validator stopped duplicating FileField required validation.
- **1.0.18:** readable admin submission presentation، resolved labels، Repeater tables، inline admin editing، human-readable exports.
- **1.0.19 locale dates/per-form auth:** Jalali/Gregorian timezone service، date filters، six generated form capabilities.
- **1.0.20 preview/lock:** optional preview، lock-after-submit، edit request workflow.
- **1.0.21 trash/lock/CAPTCHA:** soft-delete/restore/purge، persisted locked redirect، Ajax CAPTCHA refresh.
- **1.0.22 admin menu gateway caps:** form-only roles can access AFE admin through synchronized gateway capabilities.
- **1.0.23 local assets/Jihadi form updates:** runtime browser assets local؛ Jihadi field/schema updates.
- **1.0.24 brand mark:** per-form default/custom/hidden mark + template token.
- **1.0.25/1.0.26 Jalali behavior:** first-open positioning work followed by rollback to stable standard behavior.
- **1.0.27 Template Registry:** form/preview/step code defaults، admin override/reset behavior.
- **1.0.28-dev:** field ordering UI، registry validators/masks، numeric direction، SMS provider routing، hardened User Actions، live duplicate، file preview links، export profiles.

These are historical milestones; current behavior is defined in [MODULE-AFE](../modules/alavi-form-engine.md).

## STATE-002-003 — AFE Action/SMS security hardening imported

Legacy pre-acceptance docs record:
- ActionDefinition/Registry + Event/Token registries
- stable `action_key`
- always/once-per-submission/first-in-cycle policies
- persisted execution logs and retry
- ActionRuntime chain outputs
- protected Administrator/manage_options targets
- blocked capability/session/application-password user-meta keys
- mapping prevalidation before writes
- MeliPayamak internal provider
- Persian WooCommerce SMS adapter without credential copying
- unknown pattern strategy fail-closed
- runtime refresh of SMS master switch

Real MeliPayamak account test was reported PASS by project administrator on **2026-09-04**.

## STATE-002-004 — AFE Live Duplicate evolution imported

Legacy checkpoints record:
1. live duplicate check added before submit.
2. query delayed until all fingerprint fields complete.
3. requests debounced/cancelled; no file bytes.
4. Repeater fingerprint paths supported.
5. final submit remained race-safe authority.
6. UX changed so non-duplicate shows **no success message**.
7. old submissions became discoverable through fingerprint index rebuild/signature self-heal.

This final UX/behavior is current unless code proves otherwise.

## STATE-002-005 — AFE Export checkpoint 11 → 11.3 imported

### Checkpoint 11
- package-based PDF/XLSX introduced.
- Excel moved to PhpSpreadsheet real XLSX.
- PDF moved to tc-lib-pdf.
- per-form Export Profile + Admin overrides.
- PDF HTML/CSS/token editor.
- structured Excel columns/settings.
- multi-form sheets.
- file hyperlinks and identifier text preservation.

### 11.1 runtime hardening
- export package readiness and binary download paths hardened.

### 11.2 PDF font metadata
- DejaVu detector corrected to family directory path:
  `vendor/tecnickcom/tc-lib-pdf-font/target/fonts/dejavu/dejavusans.json`.

### 11.3 Composer isolation
- ZipStream pinned 3.2.2.
- `ExportAutoloadScope` added.
- foreign Composer loaders isolated during XLSX export.
- Reflection verifies AFE-owned class paths.
- preloaded foreign conflicts fail with exact path instead of opaque TypeError.
- status checks avoid accidental `class_exists()` preload.
- collision regression added.

Historical automated evidence at checkpoint 11.3:
- 57/57 regression PASS
- 168 PHP lint PASS
- JS syntax PASS
- composer JSON PASS
- no runtime CDN PASS
- extracted ZIP QA/integrity PASS

Live Excel with Pinova/foreign vendor and final PDF runtime success were still explicitly unverified in legacy docs.

Related: [ADR-006](../decisions/ADR-006-afe-export-dependency-isolation.md).

## STATE-002-006 — Theme v0.1.x / v0.2.x history imported

### v0.1.0
Development conventions established:
- BEM/scoped UI
- OOP/SOLID where useful
- shared Helper/Service over duplication
- Persian explanatory comments
- versioned handoff process (now replaced by root state system)

### v0.2.0
Product media/carousel architecture:
- manual participation image removed in favor of WooCommerce product media
- shared Product Media Service/Media Helper
- product gallery Elementor widget
- carousel navigation, keyboard/swipe, active-slide lightbox
- placeholder excluded from lightbox

### v0.2.1
Loading strategy:
- first real carousel image eager
- later carousel images lazy
- single/non-carousel image remains lazy
- async decoding retained

Current contract: [MODULE-THEME-MEDIA](../modules/theme-product-media.md).

## STATE-002-007 — Theme Jihadi Center history imported

### v0.3.0
Jihadi Center settings tab + Elementor widget added with Hero/Stats/Intro/System Cards/News/Media/Partners/FAQ and shared content query service.

### v0.3.1
Admin Repeater shared component introduced and FAQ/Center repeaters migrated.

### v0.4.0
Two-source Dashboard/Elementor schema with per-section enable switches and explicit empty-value semantics introduced.

### v0.4.1
System Cards changed to field-level precedence; standard Elementor Icons support added.

### v0.5.0
Shared AJAX settings controller, access service, savebar/dirty state and progressive fallback introduced.

### v0.5.1
System Card media became exclusive Image vs SVG/Icon; safe inline SVG helper added; legacy `icon_id` retained only for migration.

### v0.5.2
Forced SVG fill removed; hover/focus icon color added; first-card exceptional style removed.

### v0.5.3
Viewport reveal/content-visibility introduced for lower sections; Hero stayed eager for LCP; reduced-motion and Elementor Editor fallbacks added.

### v0.5.4
Settings tab asset loading moved to registry `assets_callback` and resolved active tab/capability rather than raw URL parameter.

### v0.5.5
Responsive stats margin and Hero height/object-fit/object-position/opacity/filter controls added.

### v0.5.6
Hero media/image explicit sizing and absolute fill fixed object-fit behavior.

### v0.5.7
Legacy handoff (no standalone file in repository) records responsive width/height controls for Hero image with 100% defaults.

### v0.5.8
Responsive aspect ratios added for featured/secondary News and Media; fixed media heights removed.

### v0.5.9
Independent featured/secondary News/Media title/date typography and normal/hover/focus colors added; featured Media date markup added.

Current contracts:
- [MODULE-JIHADI-CENTER](../modules/jihadi-center.md)
- [MODULE-THEME-ADMIN](../modules/theme-admin-settings.md)
- [ADR-004](../decisions/ADR-004-jihadi-center-source-precedence.md)
- [ADR-005](../decisions/ADR-005-shared-admin-repeater.md)

## STATE-002-008 — Theme Participation v0.6.0 → v0.6.5 imported

### v0.6.0
Inline Quick Checkout introduced:
- configured enabled gateway
- isolated cart context
- standard WooCommerce fields/hooks
- one-project order
- invoice + redirect gateway flow

### v0.6.1
Gateway sanitizer decoupled from runtime gateway registry; active/availability checks remain runtime.

### v0.6.2
Gateway ID became case-preserving and legacy lowercase values resolve to actual `$gateway->id`.

### v0.6.3
Mixed-patch compatibility added:
- guarded service resolver calls
- independent settings sanitizer/resolver
- Quick Checkout compatibility resolver

### v0.6.4
Inline checkout restricted to Billing fields; form rows forced full-width only inside participation scope.

### v0.6.5
Manual internal WooCommerce country/state event triggers removed; real Billing Country `change` inside Quick Checkout used instead, preventing `undefined.find` context error.

Current contract: [MODULE-PARTICIPATION](../modules/participation-payments.md) and [ADR-002](../decisions/ADR-002-participation-quick-checkout.md).

## STATE-002-009 — Legacy documentation retired

After reconciliation, duplicated continuation/history docs were removed from plugin/theme. New sessions must not recreate parallel handoff/version trees. Meaningful future work updates:
- `docs/state/CURRENT.md`
- active state file
- affected module doc
- ADR/index only when relevant

Package-facing AFE README/readme/third-party notices remain because they serve distribution/install/legal purposes rather than persistent project handoff.

## STATE-002-010 — Jihadi form moved from AFE to child theme

Date: 2026-09-29

- Source Definition `jihadi-group-registration` از `wp-content/plugins/alavi-form-engine/src/Forms/JihadiGroupRegistrationForm.php` خارج شد.
- ownership جدید: `wp-content/themes/ostadsho-child/inc/forms/class-ba-jihadi-group-registration-form.php`.
- AFE دیگر هیچ فرم business-specific را مستقیم در `Core\\Plugin` register نمی‌کند؛ فقط `afe_register_forms` را روی registry عمومی dispatch می‌کند.
- theme فرم را از `functions.php` روی همان hook register می‌کند.
- برای اینکه listener قالب قبل از dispatch hook ثبت شده باشد، AFE boot از `plugins_loaded` به `after_setup_theme` priority 20 منتقل شد. WordPress theme functions را قبل از این hook load می‌کند.
- slug `jihadi-group-registration` عمداً ثابت ماند؛ DB migration ایجاد نشد و submission/override/accessهای موجود باید با همان slug resolve شوند.
- دو regression فرم جهادی از plugin suite حذف و یک regression یکپارچه در theme اضافه شد؛ regression جدید AFE زمان‌بندی boot را guard می‌کند.
- README/readme افزونه از claim «built-in Jihadi form» به extension-based registration اصلاح شد.

Related: [ADR-007](../decisions/ADR-007-project-form-ownership.md) و [MODULE-JIHADI-FORM](../modules/jihadi-group-registration-form.md).

## STATE-002-011 — Homepage Hero converted to Elementor widget

Date: 2026-09-29

- reference اصلی: `mohsenfarhangi/bonyad-alavi-redesign/redesign/index.html`، بخش `ba-hero__grid`.
- ویجت جدید `Bonyad_Alavi_Home_Hero_Widget` با slug Elementor برابر `bonyad_alavi_home_hero` به child theme اضافه شد.
- سه بخش اصلی reference حفظ شدند: Mission Nav، Slider و Important News Ticker.
- Slider دارای Repeater برای image/tag/title/description/button/link است؛ image فقط از Elementor Media Control انتخاب می‌شود و تصاویر redesign به theme منتقل نشدند.
- متن‌های خالی render نمی‌شوند؛ Button نیازمند text + URL است؛ URL بدون button text کل slide را link می‌کند.
- Mission Nav دارای Repeater برای icon/title/description/link است؛ URL خالی آیتم را non-clickable نگه می‌دارد.
- Ticker از shared `BA_Content_Query_Service` استفاده می‌کند و default آن latest 3 posts است؛ title به permalink لینک می‌شود.
- Query controls شامل post type/count/category/tag/author/include/exclude/order/orderby/offset/sticky/date range هستند.
- Style controls برای layout، mission panel/items/icons، slider overlay/media/text/button/navigation و ticker اضافه شدند؛ همه text surfaces Typography دارند و hover surfaces state مستقل دارند.
- responsive contract reference حفظ شد: desktop split layout، tablet slider→ticker→2-column missions، mobile flex + ratio-driven slider با default 4:3.
- JavaScript per-instance و Elementor-aware است و reduced-motion را رعایت می‌کند.
- source commit: `dc1eb832c1a3582f722dde7085bb7d945e31e537`.
- live WordPress/Elementor acceptance هنوز انجام نشده است.

Current contract: [MODULE-HOME-HERO](../modules/theme-home-hero-widget.md).

## STATE-002-012 — Contact form added to child theme

Date: 2026-09-29

- فرم جدید `contact-us` در `wp-content/themes/ostadsho-child/inc/forms/class-ba-contact-form.php` اضافه شد.
- ownership مطابق ADR-007 در child theme است و AFE فقط Engine/API عمومی را تأمین می‌کند.
- `functions.php` کلاس `BA_Contact_Form` را load و با `afe_register_forms` ثبت می‌کند.
- فرم یک Step دارد و فیلدها شامل `full_name`، `mobile`، `email`، `province`، `subject` و `message` هستند.
- `province` از Geo Data Source AFE با level=`province` استفاده می‌کند؛ استان‌ها hard-code نشده‌اند.
- mobile required با `mobile_09` + `mobile_ir` و prefix نمایشی/ورودی `09` است.
- فرم Wizard/Progress/Save Draft ندارد؛ custom CAPTCHA و rate limit=5 فعال است.
- Admin Email Action با action key `notify_admin_email_on_submit` روی `submission.submitted` و policy=`once_per_submission` تعریف شده است.
- shortcode: `[alavi_form id="contact-us"]`.
- regression: `wp-content/themes/ostadsho-child/tests/contact-form-definition.php`.
- source commit: `5f181fe082f5879072262f15ba3e420c58a7b4b7`.
- live frontend/Geo/email acceptance هنوز تأیید نشده است.

Current contract: [MODULE-CONTACT-FORM](../modules/contact-form.md).

## STATE-002-013 — Mission Nav original SVG defaults restored

Date: 2026-09-29

- چهار آیکون پیش‌فرض Mission Nav در ویجت Hero از Font Awesome به SVGهای اصلی reference redesign برگشتند: `tabler:briefcase-2`، `tabler:school`، `tabler:stethoscope` و `tabler:building-community`.
- Repeater یک `default_icon_key` داخلی دارد؛ تا وقتی کاربر Elementor Icon جدیدی انتخاب نکند، SVG متناظر رندر می‌شود.
- custom Elementor icon همیشه نسبت به SVG پیش‌فرض اولویت دارد.
- برای نمونه‌های ذخیره‌شده قبل از این تغییر، چهار Font Awesome default قدیمی به SVG متناظر map می‌شوند و migration دستی لازم نیست.
- CSS خطی SVGهای Tabler را با `fill:none` و `stroke:currentColor` حفظ می‌کند تا کنترل رنگ/اندازه Style Tab همچنان کار کند.
- regression contract Hero برای SVG defaults، custom override و legacy compatibility به‌روزرسانی شد.
- source commit: `25a759aa008ebcf51fe49b3c9748514a94e0bf1e`.
- live Elementor visual acceptance هنوز تأیید نشده است.

Current contract: [MODULE-HOME-HERO](../modules/theme-home-hero-widget.md).

## STATE-002-014 — Homepage Hero fidelity reset to redesign reference

Date: 2026-09-29

- implementation قبلی Hero از classهای مستقل `ba-home-hero__*` و defaultهای Style Tab استفاده می‌کرد که با cascade و geometry فایل reference اختلاف ایجاد می‌کرد.
- DOM اصلی به classهای reference برگشت: `ba-hero`, `ba-container`, `ba-hero__grid`, `ba-mission-nav`, `ba-slider`, `ba-ticker` و elementهای همان blockها.
- `ba-home-hero-widget` فقط به‌عنوان root scope باقی ماند تا CSS reference با قالب/Elementor تداخل سراسری نداشته باشد.
- baseline CSS از مقادیر نهایی `redesign/assets/css/home.css` بازسازی شد: desktop grid 220px/.82fr + 2.25fr، rows 410/48، tablet 390/48، mobile order slider→ticker→mission و ratio 4:3.
- Slider content به `right: clamp(24px, 5vw, 62px)` و `bottom: clamp(31px, 6vw, 68px)` برگشت؛ Mission hover به padding-right مرجع برگشت؛ CTA دوباره `ba-button ba-button--light` است.
- Ticker default با 3 Query item + duplicate first item و animation 18s مرجع کار می‌کند؛ query countهای دیگر fallback JS scoped دارند.
- Style controls حفظ شدند ولی visual defaultهای جداگانه حذف شدند؛ بنابراین بدون تغییر کاربر هیچ CSS تولیدشده Elementor ظاهر reference را override نمی‌کند.
- slider title mode پیش‌فرض reference است: اسلاید اول H1 و بقیه H2.
- برای instanceهای موجود Elementor، Style Control IDها با prefix `ref_` بازتعریف شدند تا مقادیر default اشتباه ذخیره‌شده نسخه قبلی دیگر روی baseline reference اعمال نشوند؛ title-tag control نیز reset شد تا حالت مرجع H1/H2 برقرار شود.
- source commits: `04ac3aae2287fcd2298946cc5745120b59c61479`, `59d55c680744fa34b607a59fe961fc6828ea6425`, `29a104896013ec91fe14bba11584b94f37c5fb02`.
- static comparison و JavaScript syntax check PASS؛ اجرای PHP regression/live Elementor هنوز باز است.

Current contract: [MODULE-HOME-HERO](../modules/theme-home-hero-widget.md).

## STATE-002-015 — Homepage Quick Links converted to Elementor widget

Date: 2026-09-29

- reference: `mohsenfarhangi/bonyad-alavi-redesign/redesign/index.html`، block `ba-quick-links` + CSS/JS همان redesign.
- ویجت جدید `Bonyad_Alavi_Home_Quick_Links_Widget` با slug Elementor برابر `bonyad_alavi_home_quick_links` به child theme اضافه شد.
- asset handles: `bonyad-alavi-home-quick-links-widget` برای CSS و JS؛ فقط از dependencyهای widget load می‌شوند.
- markup اصلی reference حفظ شد: `ba-quick-links`, `ba-container`, `ba-quick-links__inner`, `ba-quick-links__list`, item/media/label و More button. `ba-home-quick-links-widget` فقط scope root است.
- anchor `id="services"` برای لینک داخلی صفحه حفظ شد؛ IDهای JS reference به data attributeهای scoped تبدیل شدند تا چند instance تداخل نداشته باشد.
- Repeater دارای label/link/icon/media_color است. ۹ آیتم reference با همان linkها و semantic keyها default هستند.
- SVGهای اصلی: `users-group`, `building-community`, `gavel`, `heart-handshake`, `report-analytics`, `mosque`, `award`, `route`, `tent`؛ انتخاب Elementor Icon آن‌ها را per item جایگزین می‌کند.
- semantic colors reference: people=#0f8a57، organization=#2f6fa3، auction=#b7791f، jihadi=#b44c5e، studies=#6658a6، habib=#247a70، award=#b57b0d، hamgam=#3f7eaf، camp=#678a43. Color Control فقط در صورت تغییر کاربر inline override می‌سازد.
- JS collapse/expand همان الگوریتم width/gap/visibleCount reference را استفاده می‌کند؛ فقط root-scoped + Elementor hook + cleanup برای multiple instances اضافه شده است.
- responsive reference: desktop item 116px/media 58px؛ <=760 item 98px/media 57px + left-start؛ <=430 item 86px + label 10px.
- Style controls layout/label/media/More بدون default ظاهری هستند تا reference cascade را تغییر ندهند.
- regression: `wp-content/themes/ostadsho-child/tests/home-quick-links-widget-contract.php`.
- static contract verification + JavaScript parse PASS؛ PHP lint و live Elementor acceptance هنوز تأیید نشده‌اند.
- source commits: `28c2688e67d3ec3def18472f03d05eb182fd4808`, `392c26aba8959d7aa47927b7493ded9d5e9d73fb`, `d663991b83c001e5f47d992369707cd8dd939ddc`.

Current contract: [MODULE-HOME-QUICK-LINKS](../modules/theme-home-quick-links-widget.md).

## STATE-002-016 — Homepage Live Stats converted to Elementor widget

Date: 2026-09-29

- reference: `mohsenfarhangi/bonyad-alavi-redesign/redesign/index.html`، block `ba-live-stats` و final CSS refinements در `redesign/assets/css/home.css`.
- ویجت جدید `Bonyad_Alavi_Home_Live_Stats_Widget` با slug Elementor برابر `bonyad_alavi_home_live_stats` به child theme اضافه شد.
- markup reference حفظ شد: section/container/panel/title/title-line/grid/item/value/label؛ root class اضافی فقط برای CSS scope است.
- عنوان دو control مستقل دارد: `گزارش برخط` و `اقدامات`.
- stat Repeater شامل NUMBER control و label است. مقادیر default به‌صورت raw integer ذخیره می‌شوند: 266549, 348969, 109729, 17939, 25232, 1163646.
- formatter فقط هنگام render، عدد را به ارقام فارسی و separator `٬` تبدیل می‌کند؛ بنابراین مدیر در Elementor جداکننده وارد نمی‌کند.
- final visual contract reference حفظ شد: panel=158px+content / green shell / border 2px / radius22 / padding4 / gap4 / min-height96، white grid radius18، desktop 6 columns، tablet 148px + 3 columns، mobile 1-column panel + 2-column stats + radius14.
- separatorهای بین آمارها مطابق reference در desktop/tablet/mobile حفظ شدند.
- reference برای این block JavaScript ندارد؛ widget نیز `get_script_depends()` یا JS asset ندارد.
- Style controls برای panel/title/grid/value/label/separator اضافه شدند ولی default ظاهری ندارند.
- regression: `wp-content/themes/ostadsho-child/tests/home-live-stats-widget-contract.php`.
- static source verification انجام شد؛ PHP lint و live Elementor acceptance هنوز تأیید نشده‌اند.
- source commit: `51a6666f7fd1447075cfdd2a51afeeb946953ec1`.

Current contract: [MODULE-HOME-LIVE-STATS](../modules/theme-home-live-stats-widget.md).

## STATE-002-017 — Homepage About/Impact converted to Elementor widget

Date: 2026-10-01

- reference: `mohsenfarhangi/bonyad-alavi-redesign/redesign/index.html`، سکشن `#about` / `ba-impact` و final CSS/JS همان redesign.
- ویجت جدید `Bonyad_Alavi_Home_About_Widget` با slug Elementor برابر `bonyad_alavi_home_about` به child theme اضافه شد.
- intro شامل eyebrow/title/description و actions Repeater است؛ سه دکمه HTML reference default هستند.
- funding sources از چهار آیتم ثابت خارج و به Repeater آزاد تبدیل شدند؛ defaultها همان Foundation/Banks/Organizations/Stakeholders با مقادیر 14.8/9.6/5.4/3.2 و رنگ‌های reference هستند.
- سهم هر segment از مقدارهای جاری محاسبه می‌شود. برای fidelity reference، share به دو رقم اعشار round می‌شود، gap یک واحد pathLength کم می‌شود، offset از cumulative share و angle از midpoint visible dash محاسبه می‌شود.
- چهار default دقیقاً dashهای 43.85/28.09/15.36/8.70، offsetهای 0/-44.85/-73.94/-90.30 و angleهای 78.93/212.02/293.83/340.74 را می‌سازند.
- segment color، dash/offset و animation delay با CSS custom properties inline از Repeater تأمین می‌شوند؛ source-card color/card delay نیز داینامیک است.
- total funding با جمع Repeater محاسبه می‌شود؛ Persian number formatting در initial HTML و `Intl.NumberFormat('fa-IR')` در animation reference حفظ شده است.
- Summary دارای global visibility switch و Repeater آزاد با per-item visibility، Elementor icon override، default SVG، نوع مقدار auto funding total یا manual، decimals و suffix است.
- دو Summary item reference default هستند: مجموع منابع با auto total و خدمات‌گیرندگان مستقیم با manual=3250000.
- JS reference شامل hover-linked segment/card، connector positioning، resize RAF، counter animation و IntersectionObserver حفظ و به WeakMap/per-instance + Elementor hook تبدیل شد.
- CSS final framing reference حفظ شد: one shared surface، content pane + integrated funding pane، desktop two-column، tablet stacked و mobile funding cards grid.
- regression: `wp-content/themes/ostadsho-child/tests/home-about-widget-contract.php`.
- static contract verification و JavaScript syntax parse PASS؛ PHP lint و live Elementor acceptance هنوز تأیید نشده‌اند.
- source commits: `b1b38bd82dbfc35a9f722ee57278198f0a3d6429`, `b1bbf05c01452f34e321bc57ab1d8607237eabb1`.

Current contract: [MODULE-HOME-ABOUT](../modules/theme-home-about-widget.md).

## STATE-002-018 — About funding synced with fully data-driven redesign

Date: 2026-10-01

- redesign reference advanced to commit `006cd3fa167491af10cfd764e410388cf2a37c84` (`feat: make about funding donut fully data driven`).
- widget source cards now expose raw values through `data-impact-value`; source counters no longer own the authoritative value.
- donut center and auto-total Summary counters use `data-impact-total`; JS recalculates and synchronizes them from source values.
- SVG description now uses `data-js-impact-desc` and is regenerated from current source labels/values.
- segment dash/offset/angle moved from PHP to JS runtime and use the new adaptive gap formula `min(1, rawShare * .22)`.
- fixed desktop positions for four reference source cards were removed from widget CSS.
- source-card layout now resolves collisions independently on left/right sides before connector drawing.
- connectors now terminate at the actual segment point rather than approximate donut outer radius subtraction.
- `ResizeObserver` and `MutationObserver` were added per reference; theme adaptation keeps observers/listeners instance-scoped and cleans them on Elementor re-init.
- source commit: `9995a1324825ae1cb98a3102aadc0b56de5dc654`.
- static contract checks + JavaScript V8 parse PASS; PHP lint/live Elementor acceptance remain pending.

Current contract: [MODULE-HOME-ABOUT](../modules/theme-home-about-widget.md).

## STATE-002-019 — About donut labels moved closer to segments

Date: 2026-10-02

- visual issue reproduced from live screenshot: source cards used a fixed center orbit, causing uneven apparent gaps and overly long connectors, especially around top/bottom segments.
- redesign fix source: `9f6d01482e21e342ec5eaecb6bf3a515bf99c8fd`.
- Elementor widget sync: `b234215294fae2e4f78a18151f348cb4090414fa`.
- fixed `orbitRadius = chartWidth * .68` removed.
- each source card now computes radial edge distance from its actual width/height and segment angle.
- target card radius = donut outer radius + radial card-edge distance + `10px`.
- cards are clamped with `8px` funding-visual inset before collision resolution.
- left/right collision resolver remains enabled with 8px card-to-card gap.
- connector still starts from actual card edge and terminates at matching segment midpoint.
- mobile <=760 source grid remains unchanged.
- JavaScript syntax parse PASS; live Elementor visual acceptance remains pending.

Current contract: [MODULE-HOME-ABOUT](../modules/theme-home-about-widget.md).

## STATE-002-020 — About widget gets server-side donut label fallback

Date: 2026-10-02

- after the first JS-only tightening produced no visible change on the reported page, the widget now also emits initial source-card geometry from PHP.
- fallback uses the same source values and adaptive segment-gap formula to derive the angle.
- default source-card dimensions (154×68 reference) are used only for initial radial edge distance; JS later refines using measured DOM dimensions.
- inline initial `left/top` overrides stale/global per-source CSS positions even before widget JS runs.
- target donut-to-card gap reduced to `10px`; visual inset remains `8px`.
- mobile <=760 remains protected by the existing `left/top:auto !important` grid rules.
- stale regression assertion for pre-runtime PHP donut math was removed; contract now guards runtime math + server fallback.
- source commits: `310c72d20901799ecd92143636ecbbe454bff8b9`, `e3f493ef69b6258715803dbbaeacbbd16091e19e`.
- JavaScript syntax parse PASS; full PHP lint/live Elementor acceptance still pending.

Current contract: [MODULE-HOME-ABOUT](../modules/theme-home-about-widget.md).

## STATE-002-021 — About label layout no longer jumps after reveal animation

Date: 2026-10-02

- reported behavior: cards were correctly close to the donut in the server-rendered first frame, then moved back outward when widget JS initialized/revealed the animation.
- root cause in widget lifecycle: first `syncImpactFundingData()` and the initial `ResizeObserver` callback both rewrote `left/top` after the PHP fallback had already produced the desired position.
- first data sync now preserves the PHP `left/top`; JS only recalculates connector geometry against those existing positions.
- subsequent data mutations can still run full layout because angles genuinely change.
- `ResizeObserver` now ignores callbacks unless funding-visual width or height changes by more than 2px, so reveal/paint callbacks do not trigger an unnecessary layout rewrite.
- collision resolution remains vertical but is capped to ±18px from each card's base Y position.
- regression contract now guards the initial-layout lock, resize threshold and collision cap.
- source commits: `e0cc64dc92e8b28ccc004a76eb90ad83f751b33e`, `e568a60a8e83d881a92ad19927911116240d416c`.
- JavaScript syntax parse PASS; live Elementor visual acceptance remains pending.

Current contract: [MODULE-HOME-ABOUT](../modules/theme-home-about-widget.md).

## STATE-002-022 — About labels anchored to exact segment midpoint

Date: 2026-10-02

- label anchor is derived from the exact outer midpoint of each SVG donut segment.
- donut SVG geometry: viewBox 320×320, center path radius 104, stroke width 34, therefore exact outer radius = 121 SVG units.
- runtime scale = rendered chart size / 320; anchor coordinates are center + radial vector × (121 × scale).
- label center starts from that exact anchor and moves outward only by its measured radial edge distance + 10px gap.
- connector endpoint is the same exact segmentX/segmentY coordinate.
- source cards expose the calculated coordinates as data-impact-segment-x and data-impact-segment-y for debugging/verification.
- approximate chartRect.width * .38 geometry was removed.
- one exact runtime layout happens before reveal animation; observer callbacks do not create a second layout unless dimensions materially change.
- PHP fallback now uses the exact 121/320 ratio against the reference 310px chart width; JS remains authoritative after init.
- source commit: `e76f85bab8ae191fcfe8e2f315d13f7a59291a39`.
- JavaScript syntax parse PASS; live Elementor visual acceptance remains pending.

Current contract: [MODULE-HOME-ABOUT](../modules/theme-home-about-widget.md).
