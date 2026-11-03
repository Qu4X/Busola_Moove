/**
 * Busola Theme - H5P Enhancements & Dark Mode Auto-Sync
 * 1. Synchronizes theme color mode (light/dark) between Moodle parent window and H5P iframe.
 * 2. Relocates H5P progress dots container above question card to match Busola Quiz mockup (Quiz.dc.html).
 * 3. Keeps progress segmented pill states synchronized.
 */
(function() {
    'use strict';

    // --- 1. DARK MODE SYNCHRONIZATION ---
    function applyDark(isDark) {
        var theme = isDark ? 'dark' : 'light';
        var doc = document;
        if (doc.documentElement) {
            doc.documentElement.setAttribute('data-bs-theme', theme);
            if (isDark) {
                doc.documentElement.classList.add('moove-darkmode');
            } else {
                doc.documentElement.classList.remove('moove-darkmode');
            }
        }
        if (doc.body) {
            doc.body.setAttribute('data-bs-theme', theme);
            if (isDark) {
                doc.body.classList.add('moove-darkmode');
            } else {
                doc.body.classList.remove('moove-darkmode');
            }
        }
    }

    function checkParentTheme() {
        try {
            var targetWindow = window.parent;
            for (var i = 0; i < 5; i++) {
                if (!targetWindow) {
                    break;
                }
                if (targetWindow.document && targetWindow.document.body) {
                    var isDark = targetWindow.document.body.classList.contains('moove-darkmode') ||
                                 targetWindow.document.body.getAttribute('data-bs-theme') === 'dark';
                    applyDark(isDark);
                    break;
                }
                if (targetWindow === targetWindow.parent) {
                    break;
                }
                targetWindow = targetWindow.parent;
            }
        } catch (e) {
            // Ignore potential cross-origin boundary.
        }
    }

    // --- 2. PROGRESS BAR RELOCATION & STATE SYNC ---
    function syncProgressBar() {
        var dots = document.querySelector('.h5p-progress-dots-container');
        var question = document.querySelector('.question-container.h5p-question') ||
                       document.querySelector('.question-container') ||
                       document.querySelector('.h5p-question');

        if (dots && question) {
            // Move dots above question container if not already positioned directly before it
            if (question.previousElementSibling !== dots) {
                question.parentNode.insertBefore(dots, question);
            }

            // Sync active state on list items
            var items = dots.querySelectorAll('li');
            for (var i = 0; i < items.length; i++) {
                var li = items[i];
                var link = li.querySelector('.h5p-progress-dot, a');
                var isCurrentOrAnswered = li.classList.contains('current') ||
                                         li.classList.contains('answered') ||
                                         (link && (link.classList.contains('current') ||
                                                   link.classList.contains('answered') ||
                                                   link.getAttribute('aria-current') === 'step'));
                if (isCurrentOrAnswered) {
                    li.classList.add('busola-active');
                } else {
                    li.classList.remove('busola-active');
                }
            }
        }
    }

    function runAll() {
        checkParentTheme();
        syncProgressBar();
    }

    // Initial run
    runAll();

    // DOM Ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runAll);
    }

    // Window load
    window.addEventListener('load', runAll);

    // Listen for direct messages from parent window
    window.addEventListener('message', function(event) {
        if (event.data && (event.data.type === 'busola-theme-change' || event.data.type === 'theme-change')) {
            applyDark(Boolean(event.data.isDark));
        }
    });

    // Observer for DOM mutations (H5P renders and switches questions dynamically)
    try {
        var observer = new MutationObserver(function() {
            syncProgressBar();
        });
        observer.observe(document.documentElement || document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'aria-current']
        });
    } catch (e) {
        // Fallback to interval if MutationObserver unavailable
    }

    // Polling interval to guarantee sync across slide transitions & theme toggles
    setInterval(runAll, 400);
})();
