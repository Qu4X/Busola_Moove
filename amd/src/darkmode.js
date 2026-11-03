// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contain the logic for native Moove dark mode.
 *
 * @copyright  2020 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax'], function($, Ajax) {

    var SELECTORS = {
        TRIGGER: '#toggle-darkmode-input',
        MODECLASS: 'moove-darkmode'
    };

    /**
     * Synchronize light/dark mode with all H5P iframes on the page.
     *
     * @param {boolean} isDark Whether dark mode is enabled.
     */
    function syncH5PIframes(isDark) {
        var theme = isDark ? 'dark' : 'light';
        var iframes = document.querySelectorAll('iframe');

        iframes.forEach(function(frame) {
            try {
                if (frame.contentWindow) {
                    frame.contentWindow.postMessage({
                        type: 'busola-theme-change',
                        theme: theme,
                        isDark: isDark
                    }, '*');
                }

                var doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
                if (doc) {
                    if (doc.documentElement) {
                        doc.documentElement.setAttribute('data-bs-theme', theme);
                        if (isDark) {
                            doc.documentElement.classList.add(SELECTORS.MODECLASS);
                        } else {
                            doc.documentElement.classList.remove(SELECTORS.MODECLASS);
                        }
                    }
                    if (doc.body) {
                        doc.body.setAttribute('data-bs-theme', theme);
                        if (isDark) {
                            doc.body.classList.add(SELECTORS.MODECLASS);
                        } else {
                            doc.body.classList.remove(SELECTORS.MODECLASS);
                        }
                    }

                    // Check nested iframes inside H5P player.
                    var nested = doc.querySelectorAll('iframe');
                    nested.forEach(function(nestedFrame) {
                        try {
                            if (nestedFrame.contentWindow) {
                                nestedFrame.contentWindow.postMessage({
                                    type: 'busola-theme-change',
                                    theme: theme,
                                    isDark: isDark
                                }, '*');
                            }

                            var nestedDoc = nestedFrame.contentDocument || (nestedFrame.contentWindow && nestedFrame.contentWindow.document);
                            if (nestedDoc) {
                                if (nestedDoc.documentElement) {
                                    nestedDoc.documentElement.setAttribute('data-bs-theme', theme);
                                    if (isDark) {
                                        nestedDoc.documentElement.classList.add(SELECTORS.MODECLASS);
                                    } else {
                                        nestedDoc.documentElement.classList.remove(SELECTORS.MODECLASS);
                                    }
                                }
                                if (nestedDoc.body) {
                                    nestedDoc.body.setAttribute('data-bs-theme', theme);
                                    if (isDark) {
                                        nestedDoc.body.classList.add(SELECTORS.MODECLASS);
                                    } else {
                                        nestedDoc.body.classList.remove(SELECTORS.MODECLASS);
                                    }
                                }
                            }
                        } catch (e2) {}
                    });
                }
            } catch (e1) {}
        });
    }

    var DarkMode = function() {
        this.registerEventListeners();

        var isDark = document.body.classList.contains(SELECTORS.MODECLASS) ||
                     document.body.getAttribute('data-bs-theme') === 'dark';

        if (isDark) {
            document.body.setAttribute('data-bs-theme', 'dark');
            document.body.classList.add(SELECTORS.MODECLASS);
            $(SELECTORS.TRIGGER).prop('checked', true);
        } else {
            document.body.setAttribute('data-bs-theme', 'light');
            document.body.classList.remove(SELECTORS.MODECLASS);
            $(SELECTORS.TRIGGER).prop('checked', false);
        }

        syncH5PIframes(isDark);

        // Keep H5P in sync as iframes load asynchronously.
        var pollCounts = 0;
        var pollInterval = setInterval(function() {
            pollCounts++;
            var currentDark = document.body.classList.contains(SELECTORS.MODECLASS) ||
                              document.body.getAttribute('data-bs-theme') === 'dark';
            syncH5PIframes(currentDark);
            if (pollCounts >= 5) {
                clearInterval(pollInterval);
            }
        }, 800);
    };

    DarkMode.prototype.registerEventListeners = function() {
        $(SELECTORS.TRIGGER).on('change', function() {
            var checked = $(this).is(':checked');

            if (checked) {
                document.body.classList.add(SELECTORS.MODECLASS);
                document.body.setAttribute('data-bs-theme', 'dark');
            } else {
                document.body.classList.remove(SELECTORS.MODECLASS);
                document.body.setAttribute('data-bs-theme', 'light');
            }

            syncH5PIframes(checked);

            try {
                Ajax.call([{
                    methodname: 'theme_ebusola_toggledarkmode',
                    args: {}
                }]);
            } catch (e) {
                // Ignore.
            }
        });
    };

    return {
        'init': function() {
            return new DarkMode();
        }
    };
});
