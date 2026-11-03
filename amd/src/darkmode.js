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
