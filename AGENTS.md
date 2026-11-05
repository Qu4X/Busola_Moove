# AGENTS.md

## Target environment

- **Moodle version**: Moodle 5.2 (Build: 20260420)
- **Theme base**: `theme_moove` 5.3.1 / `theme_boost`
- **Performance rules & budget**: When modifying PHP data providers (`classes/`), layouts, Mustache templates, SCSS, or AMD scripts, read `docs/adr/0001-theme-performance-architecture.md`.

## Core architectural rules

### Dynamic Moodle-driven data over hardcoding
Always pull dynamic content, courses, categories, and banners directly from Moodle APIs and the database:
- **Courses**: Titles, descriptions, completion rates, and images must come from Moodle core APIs (`enrol_get_my_courses()`, `\core_completion\progress`, and course `overviewfiles`). Never hardcode course names or fixed course lists in templates or PHP logic.
- **Categories**: Course categories, badges, and discovery groupings must reflect real Moodle categories (`{course_categories}` or `core_course_category`). Never hardcode category arrays or keyword-to-category mappings.
- **Banners & info blocks**: Informational cards and banners (such as exam information) must resolve to corresponding Moodle frontpage activities (`page`, `resource`, `url`) and pull their title (`name`) and description (`intro`), falling back to theme settings (`get_config()`) or language strings only when no activity exists.
- **Styling & tokens**: Colors, layout spacing, and theme assets must be tokenized via CSS custom properties (`scss/busola/_tokens.scss`) supporting light and dark modes. Avoid hardcoded hex colors or rigid element heights in templates and stylesheets.
- **Language & localization**: Polish (`lang/pl/theme_ebusola.php`) for the user-facing UI, English for codebase comments, commit messages, and developer documentation.

## Agent skills

### Issue tracker

Issues and specs are tracked in GitHub Issues for `Qu4X/Busola_Moove`. See `docs/agents/issue-tracker.md`.

### Triage labels

Canonical triage roles mapped to standard labels (`needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`). See `docs/agents/triage-labels.md`.

### Domain docs

Single-context repo layout (`CONTEXT.md` and `docs/adr/` at repo root). See `docs/agents/domain.md`.

### Page implementation & layout guidelines
When creating or modifying subpages (Kokpit / Dashboard, Moje kursy / My Courses, Profil / User Profile), strictly follow `PAGES_GUIDELINES.md` to prevent layout regressions (footer margins, sticky footer, flex gap double counts, switch hitboxes).

### Live CSS diagnosis

Evidence before selectors: when a style fix does not show on the live site, or you would style markup you have not seen (H5P iframes, Moodle core output), send the user a DevTools console snippet and edit from its output. Read `docs/agents/css-debugging.md` first.

