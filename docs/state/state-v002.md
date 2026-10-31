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

## STATE-002-023 — About donut migrated to local Apache ECharts

Date: 2026-10-02

- custom SVG donut/source-card positioning implementation was retired after repeated instability in external label layout.
- Apache ECharts 6.1.0 is vendored locally under `assets/vendor/echarts/` with upstream `LICENSE` and `NOTICE`.
- WordPress handle `apache-echarts` is registered locally; About widget script depends on it. No runtime CDN is used.
- Funding Source Repeater remains the canonical content source and PHP emits a JSON payload with source name/value/color plus unit/decimals.
- ECharts now owns pie geometry, animation, external rich labels, label lines and overlap handling.
- main series uses `avoidLabelOverlap=true` and `labelLayout.moveOverlap='shiftY'`; renderer is SVG.
- old manual SVG circles, source-card DOM, server geometry fallback, dash/offset helpers, exact-midpoint calculations, collision resolver, connector calculations, MutationObserver and IntersectionObserver were removed.
- only chart resize observation remains.
- Summary continues to be server-rendered and retains auto funding total/manual item modes.
- Elementor label style controls are bridged through CSS variables on the funding pane.
- vendored ECharts file is the upstream browser distribution and is accompanied by local license notices.
- code commits: `1c2c3927af01ae662637a63aee6691e3c517f07e`, `50db6781bd473d1fd91a0f1f082bc8f85e39ed9c`.
- matching redesign commit: `ed98879f770cee6a7f2ef5d034bdff7cd4bd6c94`.
- static architecture/contract checks PASS and widget JavaScript syntax parse PASS.
- PHP lint and live Elementor acceptance remain pending.

Current contract: [MODULE-HOME-ABOUT](../modules/theme-home-about-widget.md).

## STATE-002-024 — About funding simplified and compacted

Date: 2026-10-02

- removed `ba-impact__funding-head` from render and removed `funding_kicker/funding_note` controls.
- removed the complete Summary feature from the current widget contract: Summary Repeater, global visibility switch, item visibility, summary icons/SVGs, auto-total/manual summary modes, render helper and Summary CSS.
- added two simple controls only: `beneficiaries_label` and `beneficiaries_value`; default value remains 3,250,000.
- beneficiaries now render as centered unboxed text below the chart with no background, border or icon.
- total funding remains available in the Donut center, so the former duplicate “مجموع منابع” Summary item is intentionally gone.
- ECharts radius remains `['42%', '58%']`; chart heights stay 405/430/380/350px.
- compaction is achieved by removing funding header/Summary and reducing section/content/funding padding at desktop/tablet/mobile breakpoints.
- section vertical spacing: 56/52 desktop, 50/48 <=1080, 40/38 <=760, 36/34 <=430.
- code commit: `6c326cd0d5e87862ab72f89f74c59d881f778872`.
- matching redesign commits: `70e47cf5f918fe92fc993e573057de977db87093`, `56346827a618ebd44b8a2537fdb69b102bca775b`.
- static source checks PASS; widget and redesign JavaScript syntax parse PASS.
- PHP lint could not run because shell DNS could not resolve raw GitHub; live Elementor acceptance remains pending.

Current contract: [MODULE-HOME-ABOUT](../modules/theme-home-about-widget.md).

## STATE-002-025 — About action buttons get branded hover motion

Date: 2026-10-03

- added scoped hover motion only to `ba-impact__actions .ba-button`.
- motion follows the UI guide: 200ms transitions and maximum 2px lift.
- hover adds a subtle green border/background/shadow response.
- a 2px gold accent expands from the center using a pseudo-element; no extra markup or JS is required.
- active state returns to baseline.
- `prefers-reduced-motion: reduce` disables transition and translate movement.
- other `ba-button` instances outside About actions are unaffected.
- code commit: `04720f6e0287fdfb73778ef840149ad945ecf1d0`.

Current contract: [MODULE-HOME-ABOUT](../modules/theme-home-about-widget.md).

## STATE-002-026 — About CTA hover simplified to solid green

Date: 2026-10-03

- removed the gold underline/pseudo-element hover effect completely.
- hover now uses solid `var(--green-700)` background and border with white text.
- the existing 2px lift and subtle green shadow remain.
- active returns to baseline and reduced-motion still disables movement/transition.
- no extra markup or JavaScript is used.
- code commit: `132cfc1490c192afebacf34293fde883a6707f45`.


## STATE-002-028 — Homepage News converted to Query-driven Elementor widget

Date: 2026-10-03

- reference source: `bonyad-alavi-redesign/redesign/index.html#news` plus shared `components.css` and `home.css`.
- new Elementor widget: `bonyad_alavi_home_news`.
- DOM keeps `ba-news`, `ba-section-heading`, `ba-news__grid`, `ba-news-card` and `ba-card--news`; `ba-home-news-widget` is only a CSS scope.
- default query is four latest published `post` items ordered by date DESC via `BA_Content_Query_Service`.
- query UI covers post type/count/category/tag/author/search/include/exclude/order/orderby/offset/sticky/date range plus structured generic Taxonomy Query.
- shared query service gained search and generic `tax_query` parsing; prior widget prefixes keep their previous behavior when these settings are absent.
- card date uses `get_the_date()` with configurable format and default `F Y`; the site's Persian date plugin remains responsible for Jalali conversion.
- first category is the default card label; display taxonomy is configurable and may be disabled.
- featured image and title both link to the post; missing image renders an internal placeholder while preserving 16:9 geometry.
- «همه اخبار» is rendered only when both its text and URL are present.
- no widget JavaScript was added; reference responsive grid remains 4 columns desktop, 2 tablet, 1 mobile.
- regression contract added at `wp-content/themes/ostadsho-child/tests/home-news-widget-contract.php`.
- static contract checks against committed GitHub sources: PASS; live WordPress/Elementor acceptance and PHP runtime checks remain pending.
- code commits: `bcf67bacac8cca89a91cc4fee3a32f9fc31dbae3`, `6ca7b8be560db0921f3655a10f4ec2ceb981d653`.

Current contract: [MODULE-HOME-NEWS](../modules/theme-home-news-widget.md).


## STATE-002-029 — About donut labels show value above source name

Date: 2026-10-03

- visual order of ECharts external labels changed without changing donut geometry or labelLine ownership.
- each label now renders the numeric value on the first line.
- colored dot + source name render on the second line.
- existing label edge alignment, overlap handling, colors, typography controls and responsive chart dimensions remain unchanged.
- regression contract now guards the value-first formatter order.

Current contract: [MODULE-HOME-ABOUT](../modules/theme-home-about-widget.md).


## STATE-002-030 — Reusable Section Heading Elementor widget added

Date: 2026-10-04

- new standalone Elementor widget: `bonyad_alavi_section_heading`.
- intended for use anywhere in Elementor; existing News/About widgets intentionally remain unchanged.
- reference contract comes from `redesign/assets/css/components.css` → `.ba-section-heading`.
- content controls: eyebrow, title, title HTML tag, lead, action text and action URL.
- empty content parts are omitted; action renders only when both text and URL exist.
- action icon is the fixed reference arrow SVG; no Elementor Icon Control is exposed.
- layout control has `Default`, `Stack` and `Card`; Card emits both `ba-section-heading--stack` and `ba-section-heading--card` to match the reference.
- Style controls cover responsive spacing/alignment, eyebrow typography/line, title, lead/max-width and action normal/hover/icon size without injecting visual defaults.
- CSS is scoped by `ba-section-heading-widget`; no JavaScript dependency was added.
- regression contract: `wp-content/themes/ostadsho-child/tests/section-heading-widget-contract.php`.
- static source checks PASS; PHP runtime lint and live Elementor visual acceptance remain pending.
- code commit: `62090c810d12c849d6e588a30641534281f763af`.

Current contract: [MODULE-SECTION-HEADING](../modules/theme-section-heading-widget.md).

## 2026-10-14 — Homepage live-stats Swiper carousel

- Converted the stats grid to an Elementor-bundled Swiper Grid without changing the fixed report title.
- Responsive defaults: desktop 6 columns × 1 row, tablet 3 × 2, mobile 2 × 3.
- Responsive controls for columns, rows, slides-per-move, horizontal and vertical gaps.
- Optional arrows, pagination, autoplay, interaction controls; Loop/Free Mode hidden for multirow and disabled at runtime on multirow breakpoints.
- Added widget-specific JS, registered assets, changed CSS, updated regression checks and documentation.
- Scope excludes changing other home widgets or bundling third-party carousel assets.

Current module: [MODULE-HOME-LIVE-STATS](../modules/theme-home-live-stats-widget.md).

## 2026-10-14 — Live Stats item Dynamic Tags / ACF

- Enabled Elementor Dynamic Tags for the existing numeric `value` and text `label` controls inside the widget's stats Repeater.
- Enabled the same text-based dynamic capability for both fixed report title lines.
- Maintained original field IDs, manual values, the Swiper markup and the Persian grouping/digit formatter.
- No ACF-specific runtime dependency; use Elementor Pro's ACF Field dynamic tags in the current post/page context.
- The widget's Repeater is still configured manually; ACF Repeater row-to-slide conversion is out of scope.
- Extended the widget regression contract to verify dynamic controls and parsed frontend render path.

Current module: [MODULE-HOME-LIVE-STATS](../modules/theme-home-live-stats-widget.md).

## 2026-10-18 — Facilitator Recruitment Form in Child Theme

- Added `facilitator-recruitment` as a project-specific AFE Source Definition in `ostadsho-child/inc/forms/`.
- Eleven fields, with nine required and description/resume optional; national ID, mobile and landline validators; resume MIME/size constraints.
- Province/county use AFE built-in Geo Data Source and dependent field support (no custom geography logic).
- Enabled built-in AFE duplicate blocking by `national_id` for both preflight and final submission.
- Disabled editing/drafts/preview and hid public tracking UI through scoped theme CSS/JS; AFE retains its internal identifiers and Admin/exports.
- Registered shortcode-compatible form and scoped responsive assets in theme `functions.php`; added theme regression test.
- GitHub Actions run 39121959508: PHP lint, Node JS syntax and 23/23 theme regression checks PASS. Live WordPress/Elementor testing remains pending.

Current module: [MODULE-FACILITATOR-FORM](../modules/facilitator-recruitment-form.md).

## 2026-10-17 — Hero ratio sizing and Elementor Swiper

- Replaced the custom slide index/timer engine with Elementor's bundled Swiper and its async loader; ticker behavior remains independent.
- Added speed/loop/effect/interaction/touch/keyboard settings alongside preserved autoplay/arrows/dots IDs.
- Added responsive slider ratio; kept saved minimum-height ID and legacy mobile-ratio values. Zero minimum no longer collapses tablet rows.
- Removed fixed grid row sizing. Scoped compatibility handles observed legacy production fixed-row/hidden-arrow CSS absent from this checkout.
- PHP lint, 32 source contracts, isolated control registration, and browser fixture checks using actual widget rendering plus installed Elementor Swiper/loader passed.
- Browser checks covered responsive ratios, minimum height, navigation, pagination, autoplay/focus/interaction pause, slide/fade, no loop, single-slide and re-init.
- Full local WP boot was blocked by DB connection failure; no production deploy or full Elementor editor acceptance occurred.

Current contract: [MODULE-HOME-HERO](../modules/theme-home-hero-widget.md).


## 2026-10-19 — AFE frontend select / honeypot CSS fixes

- Honeypot hidden at `top:0;right:0;left:auto` inside the form instead of `left:-10000px`, avoiding RTL document overflow.
- Native enhanced selects clipped to 1px with strong-isolation specificity; original hiding declaration removed and final override moved after `.afe-control` strong isolation rules.
- `AFE_VERSION` and plugin header increased to `1.0.28-dev.1` for CSS URL cache invalidation, leaving DB `1.0.5-dev.2` and stable tag `1.0.27` unchanged.
- GitHub Actions run 39222851678: CSS contract and PHP lint PASS, Chromium viewport 360/780/782/1024 PASS for no overflow, select option choice and mocked submission. Existing full suite export-profile and export-package-integration tests still fail on stale Jihadi form references (not caused by these CSS changes).
- Production custom Additional CSS cleanup and server/CDN cache purge require connected site access; no site is connected through WPVibe currently.

Current module: [MODULE-AFE](../modules/alavi-form-engine.md).

## 2026-10-20 — Optional mobile image for homepage Hero slides

- Added the optional `mobile_image` Elementor Media control to each slide without altering the persisted `image` control or any of the existing slider configuration.
- Responsive HTML uses `<picture>` with `<source media="(max-width: 760px)">` only when a mobile image exists, and the existing WordPress/BA_Media_Helper desktop `<img>` as fallback for tablets, desktop and unset mobile.
- WordPress-attachment mobile image provides a responsive srcset; if only URL is available, use it directly. Preserve alt, eager/lazy, async decoding, slider overlay, Swiper and ratios.
- Added runtime output regression and static contract checks; PHP/JS workflow tests, with live Elementor/production acceptance pending.

Current module: [MODULE-HOME-HERO](../modules/theme-home-hero-widget.md).

## 2026-10-22 — Secure optional duplicate draft continuation

- Added per-form `duplicate.resume_draft` setting and admin checkbox; default remains off for other forms.
- Authorized draft owners can continue the previous unlocked record via verified WordPress login, edit token, or tracking code, with the existing edit URL. No auto-load by fingerprint.
- Unauthorized users receive generic recovery guidance only: no draft status, submission ID, saved values or private link.
- Jihadi group registration enables `block` duplicate policy keyed by `registration_code` + `group_mobile`, with draft continuation enabled.
- Added dedicated security/regression tests and GitHub Actions checks, bumped AFE dev asset version to 1.0.28-dev.2; DB/stable unchanged. Real WordPress acceptance remains open.

Modules: [MODULE-AFE](../modules/alavi-form-engine.md), [MODULE-JIHADI-FORM](../modules/jihadi-group-registration-form.md).

## 2026-10-22 — Farapayamak REST JSON provider and completion-before-commit rule

- Standalone `farapayamak` SMS Provider for free and approved service templates using fixed JSON POST endpoints.
- Independent encrypted API key using AFE SecretStore; payload secrets/raw responses do not appear in returned Action diagnostics.
- Success requires valid RetStatus and Value. Mocked provider/integration tests added; live credential, sender and approved bodyId acceptance still pending.
- AFE development version incremented to 1.0.28-dev.3; DB and stable unchanged.
- AGENTS.md requires completing code, tests and docs prior to the final task commit.

## 2026-10-22 — Inline duplicate reference credential verification

- Users selecting `duplicate.behavior=reference` previously saw only a duplicate error if a new form lacked verified credentials for the older submission. This is secure, but insufficient UX.
- On forms with tracking-code edit enabled, live duplicate and submit error now offer an inline tracking-code input and verification action. It reuses the secured `afe_check_duplicate` endpoint and only navigates on matching authorized `edit_url` of the duplicate row; no data leaks through the fingerprint.
- Explicit recovery attempts have a separate per-form/IP limit of 12/10 minutes. Existing authorized edit preflights are exempt.
- PHP regression and executable Node VM recovery UI regression added; AFE dev asset version `1.0.28-dev.4`, no DB/stable bump. Live WordPress acceptance pending.

## 2026-10-22 — Opt-in SMS OTP recovery and admin SMS test

- Public SMS OTP challenge/verify for matching unlocked draft submissions; fixed 6-digit code with 5-minute expiry, capped failed attempts and rate limits; successful verification yields a scoped 1-hour grant accepted by Renderer and HTTP submit. The prior tracking code is not disclosed.
- Opt-in per-form flags `duplicate.otp_enabled`, `otp_mobile_field`, `otp_provider`; Jihadi group registration opts in for stored `group_mobile`.
- Admin-only SMS test-send UI and endpoint (saved provider, real message, optional charge, nonce/capability, throttling and redacted error).
- Development version `1.0.28-dev.5`, stable and DB untouched. Live SMS, WordPress runtime and end-user UX acceptance pending.

## 2026-10-22 — Global settings tabs and safe SMS debug log

- Changed AFE global settings page to 6 accessible tabs with a single POST form, keyboard navigation and sticky Save. All fields persist across tab switches and current tab is restored after a successful save.
- Added opt-in `afe_settings.sms.debug_enabled` and a bounded 40-event diagnostic ring for SMS Action, OTP and test sends. Admin-only AJAX refresh/clear guarded by capability and nonce; no recipient, message content, OTP, credentials, raw response or exception body stored.
- Farapayamak returns only limited numeric status metadata for debugging. Inline SMS test shows sanitized summaries. Rate limit for test messages remains 3 per 10 minutes.
- Added regression tests for logging privacy, tabs and admin security. Development asset version 1.0.28-dev.6; stable and DB unchanged. Real WordPress/admin and live provider acceptance pending.

## 2026-10-22 — Configurable draft recovery SMS template and Pattern/BodyId

- Introduced per-form `duplicate.otp_sms_mode` (`free`/`pattern`), `otp_sms_text`, `otp_sms_pattern_code`, and ordered `otp_sms_pattern_values` under the existing duplicate prevention settings.
- `SmsOtpMessageFactory` requires exactly one server-owned `{{code}}` in body or pattern arguments; optional `{{minutes}}` renders to five. Invalid mode, missing code, unknown placeholders, bad BodyId or invalid arguments fail closed. Existing forms without these keys retain the previous free-text SMS.
- Pattern resolution is provider-mode aware. Only the stored draft mobile is used; OTP verify and rate limits unchanged. Per-form admin UI exposes the options; no changes to global SMS Actions.
- Added unit and draft-flow integration tests; version 1.0.28-dev.7, no DB migration or stable promotion. Live Pattern/BodyId acceptance remains pending.

## STATE-002-031 — Live-server theme changes synced into repository (2026-10-30)

- نسخه live برای `style.css` و `class-bonyad-alavi-home-quick-links-widget.php` مرجع بود و به repository آورده شد؛ تفاوت‌ها صرفاً افزودنی بودند و repository قبلاً تغییر هم‌پوشان نداشت.
- `style.css`: ruleهای محلی صفحه اصلی (پر شدن تصویر slider، ارتفاع ستون donut `.elementor-element-a1e99ce`، فونت peyda و justify بخش About، استایل News، فونت آمار استان‌ها، مخفی‌سازی `.ba-slider__controls`، و چند fix منو/جستجو/ticker).
- Quick Links: کلید `books` (گروه‌های جهادی) آیکون stacked-books و رنگ `#6658a6` گرفت؛ `studies` همچنان `tabler:report-analytics` است.
- هشدار: `.elementor-element-964556b .ba-hero__grid { grid-template-rows: ... !important }` و مخفی‌سازی سراسری controls همان overrideهایی هستند که `theme-home-hero-widget.md` درباره‌شان هشدار می‌دهد؛ با قرارداد aspect-ratio هیرو ممکن است تداخل کنند. بازبینی نشده است.
- بقیه فایل‌های قالب از نظر محتوا با repository یکسان بودند. پلاگین `alavi-pages` track نمی‌شود.

## STATE-002-032 — Studies Center page (2026-10-31)

- branch `feat/studies-center-page`: صفحه «مرکز مطالعات» و صفحه داخلی نشست طبق طرح تأییدشده (۴ تصویر دسکتاپ/موبایل). [MODULE-STUDIES-CENTER](../modules/studies-center.md)، [ADR-008](../decisions/ADR-008-studies-center-data-model.md).
- مدل داده: CPT `ba_study_session`، Taxonomy مشترک `ba_study_area` (۸ حوزه، seed یک‌باره)، `ba_research_type` روی publications؛ کتاب/پژوهش از دسته‌های انتخابی تب تنظیمات با exclude پژوهش از کتاب.
- دسترسی: `ba_manage_studies_center` برای تب + همه caps نشست؛ publications دست نخورده.
- ۸ ویجت Elementor، REST فقط‌خواندنی فیلتر، exportهای JSON (single نشست، صفحه اصلی، آرشیو نشست‌ها).
- `functions.php`: فقط یک خط `require_once` (مجوز صریح مدیر پروژه).
- آزمون محلی PHP 8.5: contract ۴۱/۴۱ و runtime ۲۷/۲۷ PASS، php -l همه فایل‌های جدید و `node --check` سه JS PASS. harness ایزوله با Chromium headless در ۱۲۸۰ و ۳۹۰ رندر و با طرح مقایسه شد (اصلاح‌ها: عرض صفر اسلایدر به‌علت `margin:auto` در CSS خود Swiper، چیدمان footer کارت در موبایل، تعداد کارت در نمای موبایل، ارتفاع Hero موبایل).
- GitHub Actions [run 41186040854](https://github.com/mohsenfarhangi/bonyad-alavi-wp/actions/runs/41186040854) روی branch PASS شد.
- پیش‌ازاین روی `main` هم: `tests/home-news-widget-contract.php` در `default-date-format` FAIL است (مرتبط با این task نیست).
- باز: Theme Builder/Elementor Pro و import templateها، پذیرش زنده، تصمیم جایگزینی page 7930، افزودن منو.


## STATE-002-033 — Studies carousel category query override (2026-10-31)

- اضافه شدن انتخاب چندگانه دسته‌بندی publications برای کاروسل‌های کتاب/پژوهش و حوزه‌های نشست برای کاروسل نشست.
- اولویت فیلتر دسته برای کتاب/پژوهش: non-empty تنظیمات مدیریت > انتخاب ویجت > پیش‌فرض فعلی. پژوهش بدون انتخاب هیچ منبعی ندارد؛ کتاب همچنان دسته‌های پژوهش سراسری را exclude می‌کند.
- افزودن categories امن و یکسان به REST برای حفظ نتایج هنگام تعویض فیلتر حوزه و پوشش جدید contract/runtime.
- راهنمای ماژول و CURRENT به‌روزرسانی شدند. WordPress/Elementor live acceptance همچنان لازم است.

## STATE-002-034 — Site-wide taxonomy terms in Studies carousel (2026-10-31)

- کنترل SELECT2 کاروسل اکنون termهای تمامی taxonomyهای ثبت‌شده سایت را همراه برچسب taxonomy نمایش داده و جست‌وجو می‌کند.
- شناسه term و پارامتر categories ثابت مانده تا تنظیمات قبلی سازگار بمانند. Query بر اساس taxonomy واقعی Term ساخته می‌شود، OR در هر taxonomy و AND بین taxonomyها.
- اولویت تنظیمات مدیریت و حذف دسته پژوهش از کتاب حفظ شد. انتخاب نامعتبر به query گسترده‌تر تبدیل نمی‌شود.
- تست‌های runtime/contract و harness توسعه یافتند؛ تست زنده WordPress/Elementor باز است.

## STATE-002-035 — Studies REST 500 root cause (2026-10-31)

- علت ۵۰۰ endpoint `ba/v1/studies/items` بدون `area` از `error_log` سرور: `Object of class WP_REST_Request could not be converted to string` در `build_args()`. وردپرس به `sanitize_callback` سه آرگومان (مقدار، درخواست، نام) می‌دهد؛ `sanitize_title` مستقیم، درخواست را `$fallback_title` می‌گیرد و برای `area` خالی همان شیء را برمی‌گرداند.
- رفع: `BA_Studies_Rest_Controller::sanitize_area()` فقط مقدار را می‌گیرد. تست‌های `rest-area-empty-is-string` و `rest-area-slug` افزوده شدند. تا deploy این فایل، فیلتر live کاروسل‌ها با حوزه «همه» ۵۰۰ می‌دهد.

## STATE-002-036 — Studies area filter double arrow (2026-10-31)

- علت فلش دوم: Select2 سایت (مثلاً از WooCommerce) روی `select` فیلتر حوزه اعمال می‌شود، `span.select2` با فلش پیش‌فرض خودش اضافه می‌کند و کنار `__chevron` ویجت دو فلش دیده می‌شود. با probe در Chrome headless روی صفحه زنده تأیید شد.
- رفع فقط در CSS ویجت: کانتینر Select2 در `.ba-studies-filter__control` مخفی و `select` بومی با ظاهر ویجت نمایش داده می‌شود؛ رویدادهای `change` و JS ویجت بدون تغییرند. با تزریق CSS جدید به نسخه محلی صفحه زنده، یک فلش دیده شد. روی سایت واقعی تا deploy این فایل اعمال نمی‌شود.
- فلش‌های اسلایدر Hero با کنترل `show_arrows` خود ویجت در Elementor خاموش شد (داده سایت، نه ریپو).

## STATE-002-037 — Sessions carousel from post categories (2026-10-31)

- مشکل: روی سایت زنده کاروسل «نشست‌ها» دسته `317` («رویدادها و نشست‌ها» از دسته‌بندی نوشته‌ها) را انتخاب کرده بود، ولی Query نشست همیشه فقط CPT `ba_study_session` را می‌خواند، پس همیشه خالی می‌ماند.
- رفع: `BA_Studies_Query_Service::build_args()` برای نوع `sessions` با termهای انتخاب‌شده، Post Type را از `object_type` taxonomyها تعیین می‌کند (فقط CPT نشست برای حوزه یا بدون انتخاب).
- منشأ داده: در قالب ذخیره‌شده «صفحه مطالعات و آبادانی قدیمی» (Elementor template 29741) سکشن «رویداد و نشست» یک Loop Carousel روی نوشته‌های دسته `317` بود و «تحقیق و پژوهش» روی انتشارات دسته `335`. تصمیم کارفرما: داده نشست‌ها همان نوشته‌های 317 باشد (موقتاً تا ورود نشست‌های CPT با ویدئو/فایل). فیلتر حوزه روی نوشته‌ها اثر ندارد.
- تصحیح تصمیم داده: دسته پژوهش در تب «مرکز مطالعات» روی سایت زنده باید `333` (آئین‌نامه و دستورالعمل) باشد، نه `335`؛ ۹ کتاب طرح هر دو دسته 334 و 335 را دارند و انتخاب 335 آن‌ها را از سکشن کتاب برمی‌داشت.
- تست‌های runtime با `get_taxonomy` و ۶ مورد جدید توسعه یافتند. پذیرش زنده WordPress/Elementor باز است و تا deploy این کد کاروسل نشست خالی می‌ماند.

## STATE-002-038 — Studies card dates from global post (2026-10-31)

- روی سایت زنده تاریخ همه کارت‌های کاروسل «۲۴ خرداد ۱۴۰۴» بود (تاریخ خود صفحه 7930). افزونه تاریخ شمسی سایت `get_the_date` را از پست سراسری می‌خواند و آرگومان `$post` را نادیده می‌گیرد.
- رفع: `BA_Studies_Query_Service::normalize()` هنگام ساخت تاریخ، `$GLOBALS['post']` را موقتاً برابر پست کارت می‌گذارد و بعد برمی‌گرداند. تست‌های `card-date-uses-own-post` و `card-date-restores-global-post` (شکست بدون اصلاح، پاس با اصلاح) افزوده شدند.

