# Page Implementation Guidelines for theme_ebusola

> **Instruction for AI Agents and Developers** creating or refactoring pages (such as **Kokpit / Dashboard**, **Moje kursy / My Courses**, **Profil / User Profile**) in `theme_ebusola`.
>
> This document details battle-tested rules, layout geometry, Moodle-specific quirks, and antipatterns to prevent regressions.

---

## 1. Page Shell & Layout Hierarchy (The Golden Structure)

All Moodle layout templates (`drawers.mustache`, `course.mustache`, `frontpage.mustache`, etc.) must strictly adhere to the following DOM tree:

```html
<body class="...">
    <div id="page-wrapper" class="d-print-block">
        <!-- 1. Header Navigation -->
        {{> theme_ebusola/navbar }}

        <!-- 2. Boost Drawers (Left Course Index & Right Blocks) -->
        ...

        <!-- 3. Main Page Container (Fills Available Viewport Height) -->
        <div id="page" data-region="mainpage" class="drawers ...">
            ... Page Content, Breadcrumbs, Hero, Cards ...
            {{{ output.standard_after_main_region_html }}}
        </div> <!-- Closes #page -->

        <!-- 4. Global Footer (ALWAYS outside #page, directly in #page-wrapper) -->
        {{> theme_ebusola/footer }}
        {{> theme_ebusola/whatsapp_button }}
    </div> <!-- Closes #page-wrapper -->
</body>
```

### ⚠️ Critical Rule: Footer Placement
- **NEVER** place `{{> theme_ebusola/footer }}` inside `<div id="page">`.
- **Reason**: In standard Moodle themes, `<div id="page">` has `padding: 0 15px`. Putting the footer inside `#page` causes it to render at `calc(100% - 30px)`, creating **unwanted white side margins** where the light `body` background bleeds through.
- **Sticky Footer Mechanism**:
  - `#page-wrapper` has `min-height: 100vh; display: flex; flex-direction: column;`.
  - `#page` has `flex: 1 0 auto;`.
  - `#page-footer` has `margin-top: auto; width: 100%; flex-shrink: 0;`.
  This guarantees that on short pages the footer stays pinned to the bottom of the viewport, and on long pages it pushes down naturally.

---

## 2. Spacing & Vertical Rhythm (Preventing Doubled Gaps)

### ⚠️ Antipattern: Combining Flex Gaps with Child Margins
When a section container uses Flexbox with a gap (e.g. `gap: 64px`), child elements must **NOT** have `margin-bottom: 64px`.

| Bad Practice | Good Practice |
| :--- | :--- |
| Container has `gap: 64px`, and child has `margin-bottom: 64px` (resulting in a **doubled 128px gap**). | Container controls spacing via `gap: 64px` (or `gap: 32px`). Children have `margin-bottom: 0`. |
| Hardcoded element heights (`height: 480px`). | Dynamic content sizing with `min-height` and flex auto-grow. |

---

## 3. Navbar, Switches & Hitbox Ergonomics

When working on any page, never disturb or override the navbar switch mechanics:

1. **Edit Mode Switch (`templates/core/editswitch.mustache`)**:
   - **Do not replace with React**: Upstream Moove 5.3 broke Moodle 5.2 by relying on `@moodle/lms/core/EditModeSwitch` and `reactprops`. Keep the native form submit to `/editmode.php` with `sesskey` and the SVG pencil icon.
   - **Pencil Icon**: Highlights orange (`--busola-accent-orange`) when active.
2. **Dark Mode Toggle (`templates/moove/darkmode.mustache`)**:
   - Uses a single dynamic 22px icon (amber sun in light mode, rotating indigo moon in dark mode).
   - Both `.custom-switch` and `.custom-control-label` must retain `height: 24px; min-height: 24px; display: inline-flex; align-items: center;`.
   - Never apply `.d-inline-block` to an empty `<label>` (it collapses baseline height to 0px, causing the switch to float ~15px above the icon).
   - In dark mode, the track turns deep indigo (`#312E81`), keeping it visually distinct from the orange edit mode switch.
3. **Hitbox Coverage**:
   - The invisible `<input class="custom-control-input">` must span `width: 2.25rem; height: 1.5rem; top: 50%; transform: translateY(-50%);` so clicking any part of the switch track toggles the state reliably.
4. **Navbar Spacing**:
   - `.navbar-brand .logo` has `padding: 7px 21px;`.
   - The rightmost navigation element has `padding-right: 1.5rem;` to prevent edge crowding.

---

## 4. Dynamic Moodle Data (Zero Hardcoding)

Every newly implemented page (Kokpit, Moje kursy, Profil) must pull real data from Moodle APIs:

- **Courses**:
  - Call `enrol_get_my_courses()` or Moodle core course renderers.
  - Progress percentages must come from `\core_completion\progress::get_course_progress_percentage($course)`.
  - Cover images must resolve through `course_overviewfiles`.
  - **Never** hardcode course titles, static progress bars, or placeholder mock arrays in production templates.
- **Categories**:
  - Pull categories from `{course_categories}` (`core_course_category::get_all()`).
  - **Never** hardcode category names (e.g. *"Kursy motorowodne"*, *"Kursy żeglarskie"*) in templates.
- **User Information**:
  - User names, avatars, and initials must come from `$USER->firstname`, `$USER->lastname`, and core avatar helpers.
- **Localization**:
  - All user-facing labels must use language strings: `{{#str}} key, theme_ebusola {{/str}}` with definitions in `lang/pl/theme_ebusola.php` and `lang/en/theme_ebusola.php`.

---

## 5. Design System Tokens (`scss/busola/_tokens.scss`)

Never use hardcoded hex colors or arbitrary spacing. Always reference design tokens:

```scss
// Backgrounds & Surfaces
background-color: var(--busola-bg-canvas);       // Page canvas (#F5F6FB in light, #0B0E2A in dark)
background-color: var(--busola-surface-card);     // Card surface (#FFFFFF in light, #15193B in dark)
border: 1px solid var(--busola-surface-border);   // Borders (#E2E8F0 in light, #2D3768 in dark)

// Typography
color: var(--busola-text-primary);               // Deep navy (#141B5E in light, #FFFFFF in dark)
color: var(--busola-text-secondary);             // Muted navy (#5B6280 in light, #94A3B8 in dark)

// Brand Accents
background-color: var(--busola-accent-orange);   // #FF7A1A (Buttons, active edit mode, progress fill)
background-color: var(--busola-accent-navy);     // #141B5E in light, #7A8AFF in dark

// Border Radii
border-radius: var(--busola-radius-sm);          // 8px (Pills, badges)
border-radius: var(--busola-radius-md);          // 14px (Icon containers, small cards)
border-radius: var(--busola-radius-lg);          // 20px (Standard cards, hero boxes)
border-radius: var(--busola-radius-xl);          // 24px (Large modals)
```

---

## 6. Specific Page Guidelines

### 6.1 Kokpit / Dashboard (`/my/index.php`)
- **Hero Greeting**: Dynamic `Ahoj, {{firstname}}!` with course continuation banner.
- **Course Continuation**: Displays the most recently accessed active course with progress percentage and *„Wróć do nauki”* CTA.
- **Block Region**: Admin editing zone (`busola-edit-zone`) rendered only when `{{#user_is_editing}}` is true.

### 6.2 Moje kursy / My Courses (`/my/courses.php`)
- **Desktop View (`>= 768px`)**: 3-column responsive card grid (`gap: 24px; flex: 1 1 260px; max-width: 340px;`).
- **Mobile View (`< 768px`)**: Horizontal card stack (square icon 60x60px on left, title + progress bar on right).
- **Course Status Badges**:
  - Not started: `Jeszcze nie zacząłeś`
  - In progress: Progress bar + `X%`
  - Completed: `✓ Ukończony` (progress bar at 100% or checkmark badge)

### 6.3 Profil / User Profile (`/user/profile.php`)
- Standard Moodle user profile details wrapped in Busola tokenized surface cards.
- Preserves Moodle user preferences, badges, and course participation records without breaking core forms.

---

## 7. Mobile Responsiveness & Bottom Tab Bar (`< 768px`)

When designing or styling for mobile:

1. **Bottom Navigation Bar**:
   - The fixed bottom bar (`fixed-bottom`, height ~60px) requires `padding-bottom: 76px` on `#page-wrapper` so footer content is not obscured.
2. **Touch Targets**:
   - All clickable elements (buttons, nav items, icons) must have a minimum tap target of **44x44px** per WCAG 2.1 AA.
3. **Safe Area Inset**:
   - For modern iOS/Android navigation bars, use `padding-bottom: calc(12px + env(safe-area-inset-bottom));` on fixed bottom elements.

---

## 8. Pre-Flight Checklist for Every Commit

Before opening a PR or committing changes to `origin/main`:

- [ ] **No white margins around footer**: Verified `#page-footer` spans 100% of viewport without bleeding.
- [ ] **Sticky footer works**: On short test pages, the footer is pinned to the bottom.
- [ ] **No doubled gaps**: Spacing between sections uses flex container `gap`, not child `margin-bottom`.
- [ ] **Dark mode verified**: Page renders cleanly with `body.moove-darkmode` and `[data-bs-theme="dark"]`.
- [ ] **Edit mode verified**: Turning on edit mode preserves layout and displays editing controls.
- [ ] **Zero hardcoded strings**: All labels use `{{#str}}`, all courses come from database APIs.
- [ ] **Mobile layout tested**: Page scales down to 360px width without horizontal overflow.
- [ ] **Version bumped in `version.php`**: e.g. `$plugin->version = 2026110501;`.
- [ ] **Cache purged**: Ran `php admin/cli/purge_caches.php` on server.
