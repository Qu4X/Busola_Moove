/**
 * Busola Theme - H5P Enhancements & Dark Mode Auto-Sync
 * 1. Synchronizes theme color mode (light/dark) between Moodle parent window and H5P iframe.
 * 2. Relocates H5P progress dots container above question card to match Busola Quiz mockup (Quiz.dc.html).
 * 3. Injects and updates question counter "Pytanie X z Y" above progress bar.
 * 4. Aligns action buttons: "Sprawdź" centered, Previous on the left, Next on the right on the same row.
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

    // --- 2. QUESTION COUNTER UPDATE ---
    function updateQuestionCounter(dots) {
        if (!dots || !dots.parentNode) {
            return;
        }
        var counter = document.querySelector('.busola-question-counter');
        if (!counter) {
            counter = document.createElement('div');
            counter.className = 'busola-question-counter';
            dots.parentNode.insertBefore(counter, dots);
        }

        var items = dots.querySelectorAll('li');
        if (items.length === 0) {
            return;
        }
        var currentIdx = 0;
        for (var i = 0; i < items.length; i++) {
            var a = items[i].querySelector('a');
            if (items[i].classList.contains('current') || (a && a.classList.contains('current'))) {
                currentIdx = i;
                break;
            }
        }
        var counterText = 'Pytanie ' + (currentIdx + 1) + ' z ' + items.length;
        if (counter.textContent !== counterText) {
            counter.textContent = counterText;
        }
    }

    // --- 3. ALIGN ACTION BUTTONS (PREV - CHECK - NEXT ON SAME ROW) ---
    function alignActionButtons() {
        try {
            var question = document.querySelector('.question-container.h5p-question') ||
                           document.querySelector('.question-container') ||
                           document.querySelector('.h5p-question');
            if (!question) {
                return;
            }

            var buttonContainer = question.querySelector('.h5p-question-buttons');
            var footer = document.querySelector('.h5p-question-set-footer') ||
                         document.querySelector('.h5p-question-set-navigation');

            if (buttonContainer) {
                if (footer) {
                    var prevBtn = footer.querySelector('.h5p-question-prev, .h5p-question-set-prev, .h5p-prev-button');
                    var nextBtn = footer.querySelector('.h5p-question-next, .h5p-question-set-next, .h5p-next-button');

                    if (prevBtn && prevBtn.parentNode !== buttonContainer) {
                        buttonContainer.insertBefore(prevBtn, buttonContainer.firstChild);
                    }
                    if (nextBtn && nextBtn.parentNode !== buttonContainer) {
                        buttonContainer.appendChild(nextBtn);
                    }
                }

                // Remove redundant title attribute matching text to prevent dark popover badge
                var checkBtn = buttonContainer.querySelector('.h5p-question-check-answer, .joubel-simple-rounded-button');
                if (checkBtn && checkBtn.getAttribute('title') && checkBtn.getAttribute('title').trim() === checkBtn.textContent.trim()) {
                    checkBtn.removeAttribute('title');
                }
            }
        } catch (e) {
            // Silently catch timing issues
        }
    }

    // --- 4. PROGRESS BAR RELOCATION ---
    function relocateProgressBar() {
        try {
            var dots = document.querySelector('.h5p-progress-dots-container');
            var question = document.querySelector('.question-container.h5p-question') ||
                           document.querySelector('.question-container');

            if (dots && question && question.parentNode) {
                if (question.previousElementSibling !== dots) {
                    question.parentNode.insertBefore(dots, question);
                }
                updateQuestionCounter(dots);
            }

            alignActionButtons();
        } catch (e) {
            // Silently catch any timing issues
        }
    }

    // Check theme immediately
    checkParentTheme();

    // Listen for messages from parent window
    window.addEventListener('message', function(event) {
        if (event.data && (event.data.type === 'busola-theme-change' || event.data.type === 'theme-change')) {
            applyDark(Boolean(event.data.isDark));
        }
    });

    // Safely relocate progress bar & align buttons once H5P is ready
    if (window.H5P && window.H5P.externalDispatcher) {
        window.H5P.externalDispatcher.on('initialized', function() {
            setTimeout(relocateProgressBar, 50);
        });
    }

    window.addEventListener('load', function() {
        setTimeout(relocateProgressBar, 200);
    });

    // Periodic check for dark mode, progress bar, and button alignment across slide changes
    setInterval(function() {
        checkParentTheme();
        relocateProgressBar();
    }, 600);
})();
