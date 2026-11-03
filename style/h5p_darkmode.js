/**
 * Busola Theme - H5P Dark Mode Auto-Sync
 * Automatically synchronizes theme color mode (light/dark) between Moodle parent window and H5P iframe.
 */
(function() {
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

    // Check immediately on load.
    checkParentTheme();

    // Listen for direct messages from parent window.
    window.addEventListener('message', function(event) {
        if (event.data && (event.data.type === 'busola-theme-change' || event.data.type === 'theme-change')) {
            applyDark(Boolean(event.data.isDark));
        }
    });

    // Check periodically to stay in sync with live toggles without page reload.
    setInterval(checkParentTheme, 600);
})();
