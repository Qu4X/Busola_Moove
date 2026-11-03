# Architecture & Maintenance Guide - theme_ebusola

> Dedicated Moodle theme for **Szkoła Żeglarstwa Busola** ([kursybusola.pl](https://kursybusola.pl)), built on **Moove 5.3.1** base and optimized for **Moodle 5.2 / 5.3+**.

---

## 1. High-Level Architecture

`theme_ebusola` operates as a stand-alone Moodle theme extending `theme_boost` with architectural patterns inherited from `theme_moove`, customized for the Busola nautical school brand and e-learning requirements.

```
                    ┌───────────────────────────────┐
                    │          Moodle Core          │
                    │   (APIs, Enrolment, Courses)  │
                    └───────────────┬───────────────┘
                                    │
                                    ▼
                    ┌───────────────────────────────┐
                    │         theme_ebusola         │
                    │  (classes/util/busola.php)    │
                    │   Dynamic Data Providers      │
                    └───────────────┬───────────────┘
                                    │
         ┌──────────────────────────┼──────────────────────────┐
         │                          │                          │
         ▼                          ▼                          ▼
┌──────────────────┐       ┌──────────────────┐       ┌──────────────────┐
│  Mustache Views  │       │   SCSS Tokens    │       │     AMD / JS     │
│  (templates/)    │       │  (scss/busola/)  │       │    (amd/src/)    │
│  • navbar        │       │  • _tokens.scss  │       │  • darkmode.js   │
│  • editswitch    │       │  • _busola.scss  │       │  • webservice    │
│  • darkmode      │       │  • default.scss  │       │    calls         │
│  • busola_exams  │       │                  │       │                  │
└──────────────────┘       └──────────────────┘       └──────────────────┘
```

---

## 2. Directory Layout & Key Components

| Path | Purpose | Key Responsibilities |
| :--- | :--- | :--- |
| `classes/util/busola.php` | Dynamic Data Aggregator | Resolves enrolled courses with progress, real Moodle categories, and frontpage exam activities without hardcoding. |
| `classes/output/core_renderer.php` | Template Renderer Overrides | Injects darkmode controls, edit switches, custom headers, and dashboard contexts into templates. |
| `templates/navbar.mustache` | Global Top Navigation | Top-level bar with brand logos, navigation menus, darkmode switch, user profile, and edit toggle. |
| `templates/core/editswitch.mustache` | Site-wide Edit Mode Toggle | Reliable native HTML form submit to `/editmode.php` with SVG pencil icon. |
| `templates/moove/darkmode.mustache` | Light / Dark Mode Toggle | Single dynamic SVG icon (22px sun / rotating moon) + accessible switch pill. |
| `templates/ebusola/` | Busola Custom Dashboard | Hero card (`busola_hero.mustache`), dynamic exam banner (`busola_exams.mustache`), course cards (`coursecard.mustache`). |
| `scss/busola/_tokens.scss` | CSS Design Tokens | Tokenized CSS variables (`--busola-*`) supporting both light mode (`:root`) and dark mode (`.moove-darkmode`, `[data-bs-theme="dark"]`). |
| `scss/busola/_busola.scss` | Busola Layout & Styling | Sticky flexbox footer pinning, switch hitboxes, responsive course card grids, hero banner. |
| `amd/src/darkmode.js` | Asynchronous Dark Mode JS | Toggles body classes and triggers Ajax webservice `theme_ebusola_toggledarkmode`. |

---

## 3. Core Architectural Principles

### 3.1 Dynamic Moodle-Driven Data Over Hardcoding (Mandatory Rule)
Content, courses, categories, and banners must always resolve dynamically from Moodle APIs and the database:
- **Courses**: Titles, summaries, completion percentages, and cover images come from `enrol_get_my_courses()`, `\core_completion\progress::get_course_progress_percentage()`, and course `overviewfiles`. Never hardcode course names or IDs in templates or PHP logic.
- **Categories**: Course categories and discovery listings query `{course_categories}` (`core_course_category`). Never hardcode category arrays or keyword-to-category mappings.
- **Banners & Info Cards (e.g. Exam Banner)**: The exam banner dynamically inspects frontpage activities (`page`, `resource`, `url`) matching exam keywords to pull `name` (title) and `intro` (description). It falls back to theme config (`get_config()`) or language strings only when no activity exists.

### 3.2 Dual-Theme Tokenization (Light & Dark)
All theme colors and spacing must use CSS custom properties defined in `scss/busola/_tokens.scss`.
- Avoid hardcoded hex codes (`#141B5E`, `#FF7A1A`) in individual component rules.
- Always use `var(--busola-text-primary)`, `var(--busola-surface-card)`, `var(--busola-accent-orange)`, etc.
- Dark mode overrides are applied automatically when `body.moove-darkmode` or `body[data-bs-theme="dark"]` is present.

### 3.3 Localization Policy
- **User-facing UI strings**: Polish (`lang/pl/theme_ebusola.php`), with fallback in English (`lang/en/theme_ebusola.php`).
- **Codebase, comments, commit messages, and documentation**: English only.

---

## 4. Key Differences from Upstream Moove (5.3.1)

### 4.1 Edit Mode Switch (`templates/core/editswitch.mustache`)
- **Upstream Moove 5.3 Problem**: Upstream commit `d3d71d1` attempted to use React-based `@moodle/lms/core/EditModeSwitch` passing `reactprops`. In Moodle 5.2 (and certain Moodle 5.3 configurations), core renderer does not provide `reactprops` to `editswitch.mustache`, resulting in a completely non-functional switch.
- **eBusola Implementation**: We replaced it with a direct HTML form submitting to `/editmode.php` containing `sesskey`, `pageurl`, `pagecontextid`, and hidden `setmode`. A sleek SVG pencil icon highlights orange (`--busola-accent-orange`) when editing is active.

### 4.2 Dark Mode Switch (`templates/moove/darkmode.mustache`)
- **Upstream Moove Problem**: Natively renders two separate icons simultaneously (sun on left, moon on right framing the switch), creating visual clutter. Furthermore, using `.d-inline-block` with an empty `<label>` collapsed the element baseline, causing the switch to float ~15px above the navbar line.
- **eBusola Implementation**:
  - A single dynamic icon container (22px) placed to the left of the switch pill.
  - In light mode: warm amber sun icon (`#F59E0B`).
  - In dark mode: cool indigo moon icon (`#A5B4FC`) with a 360-degree rotation transition.
  - The icon holder is wrapped inside `<label for="toggle-darkmode-input">`, allowing users to click either the icon or the switch pill to toggle.
  - In dark mode, the switch track turns deep indigo (`#312E81`) with a soft light knob (`#E0E7FF`), keeping it visually distinct from the orange edit mode switch.

### 4.3 Flexbox Layout & Footer Pinning
- **Upstream Moove Problem**: The page used `min-height: 100%` on wrapper elements combined with fixed padding and empty `.footer-content-debugging` containers, causing the footer to float over content or leave massive blank gaps at the bottom.
- **eBusola Implementation**:
  - `.busola-hub-wrapper` uses a flexbox column layout: `display: flex; flex-direction: column; min-height: 100vh; padding-bottom: 0 !important;`.
  - `.busola-main-container` fills available height with `flex: 1 0 auto;`.
  - Empty debugging containers (`.footer-content-debugging`) are removed from `templates/footer.mustache`.
  - Vertical rhythm between sections relies on standard container gaps (`gap: 64px`) rather than cumulative bottom margins.

---

## 5. Maintenance & Upgrades (What to Watch Out For)

When merging updates from upstream `moodle-theme_moove` or upgrading Moodle core, follow these critical precautions:

### ⚠️ 1. Protected Files (Do NOT Blindly Overwrite)
The following files contain crucial eBusola custom logic and must NOT be overwritten by upstream merges:
1. `classes/util/busola.php` - Custom Moodle API data queries.
2. `classes/output/core_renderer.php` - Custom renderer and control hooks.
3. `templates/navbar.mustache` - eBusola navbar layout and switch placement.
4. `templates/core/editswitch.mustache` - Native form edit mode switch.
5. `templates/moove/darkmode.mustache` - Dynamic single-icon darkmode toggle.
6. `templates/footer.mustache` - Clean footer without empty debug bar.
7. `templates/ebusola/*` - All custom dashboard templates.
8. `scss/busola/*` - Design tokens and Busola styles.

### ⚠️ 2. Component Name Collision (`theme_moove` vs `theme_ebusola`)
- Upstream Moove uses `theme_moove` as its frankenstyle component name.
- eBusola must strictly use `theme_ebusola` in all:
  - Language string calls: `get_string('key', 'theme_ebusola')` or `{{#str}} key, theme_ebusola {{/str}}`.
  - AMD modules: `require(['theme_ebusola/darkmode'], ...)`.
  - Webservice Ajax calls: `theme_ebusola_toggledarkmode`.
  - `db/services.php`, `db/upgrade.php`, and `version.php`.

### ⚠️ 3. Cache Purging & SCSS Recompilation
Moodle aggressively caches compiled SCSS into `/var/www/moodledata/localcache/theme/`.
Whenever SCSS or Mustache files are updated:
1. Bump `$plugin->version` in `version.php` (e.g. `2026110318`).
2. Run the cache purge CLI on the production server:
   ```bash
   php /home/busola/web/kursybusola.pl/public_html/admin/cli/purge_caches.php
   ```

### ⚠️ 4. JavaScript & AMD Modules
- Source JavaScript lives in `amd/src/*.js`.
- Moodle loads production JavaScript from `amd/build/*.min.js`.
- If modifying AMD source code, always recompile using Grunt/Terser or ensure `amd/build/` is kept in sync.

---

## 6. Testing & Quality Assurance Checklist

Before pushing changes to production:

- [ ] **Desktop & Mobile Navbar**: Check that the dark mode toggle and edit switch are vertically centered with consistent padding.
- [ ] **Dark Mode Toggle**: Ensure the sun icon displays in light mode, swaps to moon in dark mode with smooth rotation, and dark mode background tokens apply across all cards.
- [ ] **Edit Mode**: Confirm toggle turns on editing, highlights the pencil icon orange, and can be toggled off cleanly.
- [ ] **Sticky Footer**: Verify the footer stays pinned to the bottom of the viewport on pages with low content and pushes down naturally on long pages.
- [ ] **Zero Hardcoded Data**: Verify all course cards, exam cards, and categories load from the Moodle database.
- [ ] **Guest vs Logged In**: Confirm dark mode and edit switches render only for authorized logged-in users and don't break guest views.
