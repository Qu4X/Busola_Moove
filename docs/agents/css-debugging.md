# Diagnosing live CSS with DevTools console snippets

Use this when a style fix does not show on `kursybusola.pl`, or before you write selectors for markup you have not seen. You cannot see the live page. The user can. Ask the user to run a snippet and paste the output, then edit code from the evidence.

## Loop

1. **Ask for evidence first.** Before you edit CSS for unseen markup, send the user one console snippet. Writing selectors from guessed class names is how this repo lost many commits.
2. **Name the context.** Tell the user which DevTools console context to select. Default to `top`, and walk into frames from code (see "Frames" below). That removes a manual step that users get wrong.
3. **Print, do not copy.** End every snippet with `console.log(...)` of one string. `copy()` prints `undefined`, and users then think the snippet failed.
4. **Keep output short.** Filter to visible elements (`e.offsetParent`), deduplicate with `Set`, and cap lists (`.slice(0, 20)`). The user pastes the output into chat.
5. **Edit from the output.** Use only class names that appear in the output. Then commit, push, and ask for a screenshot after `git pull`, `php admin/cli/purge_caches.php`, and `Ctrl + F5`.
6. **Done when** the user confirms with a screenshot. Until then, report the fix as unverified.

## Frames (H5P)

`mod_h5pactivity` nests 2 iframes on the same origin: `view.php` > `h5p/embed.php` > inner `iframe.h5p-iframe` (also `embed.php`). The H5P content is only in the inner frame. CSS does not cross frames. A snippet run in the outer `embed.php` context returns empty lists.

Reach the inner document from `top`:

```js
const d = document.querySelector('iframe').contentDocument.querySelector('iframe').contentDocument;
```

## Snippets

Map every frame (URL, sample classes, whether theme CSS loaded):

```js
(() => {
  const out = [];
  const walk = (win, path) => {
    let d; try { d = win.document; } catch (e) { out.push(path + ' cross-origin'); return; }
    const q = s => [...d.querySelectorAll(s)].filter(e => e.offsetParent).slice(0, 10)
      .map(e => e.tagName + '.' + [...e.classList].join('.'));
    out.push(JSON.stringify({ path, url: d.location.href.slice(0, 80),
      themeCss: [...d.styleSheets].some(s => (s.href || '').includes('ebusola')),
      buttons: q('button'), cards: q('.question-container') }, null, 1));
    [...d.querySelectorAll('iframe')].forEach((f, i) => f.contentWindow && walk(f.contentWindow, path + ' > iframe[' + i + ']'));
  };
  walk(window, 'top');
  console.log(out.join('\n'));
})();
```

Find every element with its own background in the visible question (finds "what paints this strip"):

```js
const d = document.querySelector('iframe').contentDocument.querySelector('iframe').contentDocument;
const q = [...d.querySelectorAll('.question-container')].find(e => e.offsetParent);
const rows = [];
[q, ...q.querySelectorAll('*'), ...q.parentNode.children].forEach(e => {
  const bg = getComputedStyle(e).backgroundColor;
  if (e.offsetParent && bg !== 'rgba(0, 0, 0, 0)') rows.push(e.tagName + '.' + [...e.classList].join('.') + ' | ' + bg);
});
console.log([...new Set(rows)].join('\n'));
```

Inspect buttons (class, label, background, opacity, clickability):

```js
const d = document.querySelector('iframe').contentDocument.querySelector('iframe').contentDocument;
console.log([...d.querySelectorAll('button')].filter(b => b.offsetParent).map(b => {
  const s = getComputedStyle(b);
  return b.className + ' | ' + b.textContent.trim() + ' | bg ' + s.backgroundColor + ' | op ' + s.opacity + ' | pe ' + s.pointerEvents;
}).join('\n'));
```

Adapt these: change the root selector or the properties read from `getComputedStyle`. Keep the shape (visible filter, one `console.log`).

## Facts learned on this site

- H5P uses the new theme system: buttons are `button.h5p-theme-button` (`.h5p-theme-primary-cta.h5p-theme-check` for "Check"), containers carry `.h5p-theme`. Legacy Joubel selectors (`.joubel-simple-rounded-button`, `.h5p-question-check-answer`) match nothing.
- QuestionSet keeps all questions in the DOM at once. Select the visible one with `e.offsetParent`. Each question owns its own nav buttons. Restyle them with CSS and leave them in place. Moving them with JS broke navigation.
- QuestionSet navigation is `nav.h5p-navigation.qs-footer`. The score strip is `.h5p-question-evaluation-container`.
- The theme loads `style/h5p.css` and `style/h5p_darkmode.js` into H5P via `classes/core_h5p_renderer.php`. This works. If styles seem missing, check selectors before renderer wiring.
- Dark mode in the frame: `html`/`body` get `data-bs-theme="dark"` and `.moove-darkmode`. Write dark rules as `[data-bs-theme="dark"] .x` or via tokens. `[data-bs-theme="dark"] html body .x` never matches, because `html` is the root.
- Avoid wide resets such as `.h5p-answer *` or `button::before { display: none }`. They removed correct/wrong icons and icon-only buttons. Scope each reset to one element.
