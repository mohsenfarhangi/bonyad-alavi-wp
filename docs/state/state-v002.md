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
