# Module Index

| Module ID | Module | Keywords | Current summary | File |
|---|---|---|---|---|
| MODULE-AFE | Alavi Form Engine | forms, submissions, duplicate, actions, export, SMS | Plugin 1.0.28-dev؛ pre-acceptance، stable documented as 1.0.27 | [alavi-form-engine.md](alavi-form-engine.md) |
| MODULE-AFE-EXTENSION | AFE Extension API | hooks, form DSL, validators, masks, REST, actions | Canonical developer/extension guide replacing legacy DEVELOPER.md | [alavi-form-engine-extension-api.md](alavi-form-engine-extension-api.md) |
| MODULE-THEME | Ostadsho Child Theme | conventions, Elementor, WooCommerce, services | Composition و قواعد توسعه child theme | [ostadsho-child-theme.md](ostadsho-child-theme.md) |
| MODULE-THEME-ADMIN | Theme Admin Settings | settings, AJAX, access, repeater, assets | Shared settings persistence و wp-admin components | [theme-admin-settings.md](theme-admin-settings.md) |
| MODULE-THEME-MEDIA | Product Media | product gallery, carousel, image source, lazy loading | Shared product media service/helper and carousel behavior | [theme-product-media.md](theme-product-media.md) |
| MODULE-PARTICIPATION | Participation Payments | quick checkout, gateway, cart, billing | Isolated WooCommerce participation order/payment flow | [participation-payments.md](participation-payments.md) |
| MODULE-JIHADI-CENTER | Jihadi Center | Elementor, center settings, news, media, cards | Dashboard + Elementor data sources with centralized resolution | [jihadi-center.md](jihadi-center.md) |
| MODULE-JIHADI-FORM | Jihadi Group Registration Form | form definition, jihadi-group-registration, AFE registration | Project-specific form source owned by child theme and registered through `afe_register_forms` | [jihadi-group-registration-form.md](jihadi-group-registration-form.md) |
| MODULE-CONTACT-FORM | Contact Form | contact-us, contact, province, geo, AFE registration | One-step site contact form owned by child theme with geo province field and admin email action | [contact-form.md](contact-form.md) |
| MODULE-HOME-HERO | Homepage Hero Elementor Widget | homepage, hero, slider, mission nav, ticker, Elementor | Repeater-based Hero from redesign reference with query-driven ticker and scoped responsive assets | [theme-home-hero-widget.md](theme-home-hero-widget.md) |
| MODULE-HOME-QUICK-LINKS | Homepage Quick Links Elementor Widget | homepage, quick links, services, overflow, more, Elementor | Reference-faithful `ba-quick-links` widget with Repeater items, original SVG/color defaults and scoped overflow JS | [theme-home-quick-links-widget.md](theme-home-quick-links-widget.md) |
| MODULE-HOME-LIVE-STATS | Homepage Live Stats Elementor Widget | homepage, live stats, counters, report, Elementor | Reference-faithful `ba-live-stats` widget with numeric repeater values and frontend-only Persian thousands formatting | [theme-home-live-stats-widget.md](theme-home-live-stats-widget.md) |
| MODULE-HOME-ABOUT | Homepage About/Impact Elementor Widget | homepage, about, impact, donut, funding, summary, Elementor | Reference-faithful `ba-impact` widget with dynamic funding repeater, computed donut geometry and extensible summary | [theme-home-about-widget.md](theme-home-about-widget.md) |
| MODULE-HOME-NEWS | Homepage News Elementor Widget | homepage, news, WP_Query, taxonomy, cards, Elementor | Reference-faithful `ba-news` widget with shared WP_Query service, configurable date/card metadata and responsive 4/2/1 grid | [theme-home-news-widget.md](theme-home-news-widget.md) |
| MODULE-SECTION-HEADING | Reusable Section Heading Elementor Widget | section heading, eyebrow, title, lead, action, Elementor | Standalone reference-faithful `ba-section-heading` widget with Default/Stack/Card variants and scoped style controls | [theme-section-heading-widget.md](theme-section-heading-widget.md) |

برای task فرم جهادی از `MODULE-JIHADI-FORM` شروع کن و در صورت تغییر Engine سپس `MODULE-AFE`/`MODULE-AFE-EXTENSION` را بخوان.

برای فرم تماس با ما از `MODULE-CONTACT-FORM` شروع کن.

برای تغییرات `ba-hero__grid`/Hero صفحه اصلی از `MODULE-HOME-HERO` شروع کن.

برای تغییرات `ba-quick-links` صفحه اصلی از `MODULE-HOME-QUICK-LINKS` شروع کن.

برای تغییرات `ba-live-stats` صفحه اصلی از `MODULE-HOME-LIVE-STATS` شروع کن.

برای تغییرات سکشن `#about` / `ba-impact` صفحه اصلی از `MODULE-HOME-ABOUT` شروع کن.

برای تغییرات سکشن `#news` / `ba-news` صفحه اصلی از `MODULE-HOME-NEWS` شروع کن.

برای ویجت عمومی `ba-section-heading` از `MODULE-SECTION-HEADING` شروع کن.

برای task ابتدا فقط module مرتبط را بخوان. اگر module به ADR اشاره کرد، سپس فقط همان ADR را باز کن.
