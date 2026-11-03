<?php
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

namespace theme_ebusola;

use core\hook\output\before_html_attributes;
use core\hook\output\before_requirejs_config;
use core\hook\output\before_standard_head_html_generation;
use core\output\html_writer;
use theme_ebusola\colour_mode;

// Backwards compatibility stub for Moodle < 5.3 where theme_boost\hook_listener does not exist.
if (!class_exists('theme_boost\hook_listener')) {
    class fallback_boost_hook_listener {}
    class_alias('theme_ebusola\fallback_boost_hook_listener', 'theme_boost\hook_listener');
}

/**
 * Hook listeners for theme_ebusola.
 *
 * @package    theme_ebusola
 * @copyright  2026 Szkoła Żeglarstwa Busola
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener extends \theme_boost\hook_listener {
    /**
     * Set the Bootstrap colour mode on the html tag.
     *
     * @param before_html_attributes $hook The hook object.
     */
    public static function before_html_attributes_listener(before_html_attributes $hook): void {
        if (!colour_mode::is_ebusola_theme() || !colour_mode::is_enabled()) {
            return;
        }

        $mode = colour_mode::get_current_mode();

        $hook->add_attribute('data-bs-theme', $mode === colour_mode::AUTO ? colour_mode::LIGHT : $mode);
        $hook->add_attribute('data-colourmode', $mode);
    }

    /**
     * Add the script which resolves the "auto" colour mode before the page is painted.
     *
     * @param before_standard_head_html_generation $hook The hook object.
     */
    public static function before_standard_head_html_generation_listener(
        before_standard_head_html_generation $hook,
    ): void {
        if (!colour_mode::is_ebusola_theme() || !colour_mode::is_enabled()) {
            return;
        }

        if (defined('BEHAT_SITE_RUNNING')) {
            return;
        }

        $config = json_encode([
            'auto' => colour_mode::AUTO,
            'dark' => colour_mode::DARK,
            'light' => colour_mode::LIGHT,
        ]);

        $js = <<<EOF
            (function() {
                var config = {$config};
                var root = document.documentElement;
                var query = window.matchMedia('(prefers-color-scheme: dark)');
                var resolve = function() {
                    var mode = root.getAttribute('data-colourmode');
                    root.setAttribute(
                        'data-bs-theme',
                        mode === config.auto ? (query.matches ? config.dark : config.light) : mode
                    );
                };
                resolve();
                query.addEventListener('change', resolve);
            })();
EOF;

        $hook->add_html(html_writer::tag('script', $js));
    }

    /**
     * Set the colour mode cookie domain to match the session cookie.
     *
     * @param before_requirejs_config $hook The hook object.
     */
    public static function before_requirejs_config_listener(before_requirejs_config $hook): void {
        if (!colour_mode::is_ebusola_theme() || !colour_mode::is_enabled()) {
            return;
        }

        $hook->add_config([
            'colourmode' => [
                'cookieattributes' => colour_mode::get_cookie_attributes(),
            ],
        ]);
    }
}
