# Theme performance architecture and budget

`theme_ebusola` enforces strict frontend and backend constraints on Moodle 5.2 (Build: 20260420) to prevent database bottlenecks after user login and to keep fast rendering times.

## Context

In Moodle, anonymous users receive cached pages, while logged-in users run PHP layout scripts and output renderers on every request. Naive theme renderers cause N+1 database queries across course lists. In addition, custom inline critical CSS breaks Moodle `styles.php` caching, and deep SCSS nesting bloats the compiled stylesheet.

## Decision

### Scope of custom views versus `theme_boost` fallback

1. Limit custom `theme_ebusola` templates and styles to the 11 end-user views:
   - Login, password reset, and change password (`/login/*`)
   - Guest frontpage (`/?redirect=0`)
   - Logged-in frontpage and Dashboard (`/`, `/my/`)
   - My courses (`/my/courses.php`)
   - Course enrollment (`/enrol/index.php`)
   - Course view (`/course/view.php`)
   - Course activities (`page`, `quiz`, `h5p`/`hvp`, `resource`, `assign`)
   - User profile and profile edit (`/user/profile.php`, `/user/edit.php`)
   - User preferences (`/user/preferences.php`)
   - Notifications popover and page (`/message/output/popup/notifications.php`)
   - User grades (`/grade/report/overview/index.php`, `/grade/report/user/index.php`)
2. Delegate all other Moodle pages (site administration, teacher reports, question bank, course editing forms) to native `theme_boost` layouts and templates styled only by global CSS tokens, `navbar.mustache`, and `footer.mustache`. Remove legacy `theme_moove` features that Busola does not use (`mylearning` popover, `whatsapp_button`, `accessibilitybar` when disabled).

### SCSS and CSS

3. Trim the main SCSS bundle instead of extracting inline critical CSS. Do not import unused Bootstrap or `moove/*` modules, and do not duplicate `theme_boost` rules. Keep SCSS selector nesting at three levels or fewer.
4. Apply `content-visibility: auto` and `contain-intrinsic-size` to offscreen course and category grids. Animate only `transform` and `opacity`. Never use `transition: all`.

### Local fonts and icons

5. Host font files locally in `fonts/` and reference them in SCSS through the Moodle token `url('[[font:theme|FontName.woff2]]') format('woff2')`.
6. Declare only `.woff2` in `@font-face` (`latin` and `latin-ext` subsets, or a single variable font file). Do not list `.otf`, `.ttf`, or `.eot` before `.woff2`.
7. Always set `font-display: swap` in `@font-face` rules and preload only the primary text weight. Never load fonts from external CDNs (`fonts.googleapis.com`) or load two text font families at the same time.
8. Use inline SVGs for UI icons on custom Busola views. Avoid FontAwesome classes (`.fa`, `.fas`) where possible, because a single FontAwesome icon triggers a 158 KB `fa-solid-900.woff2` download.

### Templates, JavaScript, and DOM

9. Load ES6+ JavaScript modules on demand via `$PAGE->requires->js_call_amd()`. Do not register global scripts in `config.php` when a script runs on one page type only.
10. Guard every Mustache partial that contains a `{{#js}}` block with a PHP boolean flag. Never include JS-bearing partials unconditionally.
11. Never hide unused Moodle core regions (`{{{ output.main_content }}}`, drawers, or popovers) inside `<div class="d-none">`. Conditionalize them in PHP and Mustache so Moodle does not enqueue their AMD and React bundles.
12. Initialize modals and popovers lazily inside click handlers. Never run `Ajax.call()` or fetch modal templates (`core/modal`, `core/loading`) during page load.

### Images, static assets, and accessibility

13. Serve static assets through Moodle core APIs (`styles.php`, `pix_url`, `theme_config::setting_file_url()`). Moodle revisions (`themerev`) set immutable cache headers automatically.
14. Set `fetchpriority="high"` and `loading="eager"` on the hero image. Set `loading="lazy"`, `decoding="async"`, and explicit `width` and `height` attributes on all course cards, logos, and below-the-fold images.
15. Render one logo asset per view (prefer an adaptable SVG or conditionally render the active theme variant) instead of rendering both `.logo.light` and `.logo.dark` `<img>` tags in the DOM.
16. Never place `aria-label` on a `<label>` element (`aria-prohibited-attr`). Put `aria-label` directly on the `<input>` element or place a `<span class="visually-hidden">` inside `<label>`.

### Backend and database

17. Never run `$DB->get_record()`, `get_fast_modinfo()`, or `completion_info` inside course loops. Fetch records in batches with `$DB->get_records_list()` or `$DB->get_records_menu()` before the loop.
18. Cache expensive renderer aggregations (categories, progress rates, course images) in Moodle Universal Cache (`cache::make('theme_ebusola', ...)`). Invalidate the cache on course or enrollment events.
19. Pass flat, view-specific arrays to Mustache templates. Do not cast and pass full `$COURSE` or `$USER` objects.

## Performance budget

- Extra database queries added by the theme after login: maximum 5 per request (measured with Moodle `perfdebug`).
- Cumulative Layout Shift (CLS): under 0.05.
- Largest Contentful Paint (LCP): under 1.8 seconds.
- Total Blocking Time (TBT): under 200 milliseconds.
