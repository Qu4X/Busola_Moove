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

// Backwards compatibility stub for Moodle < 5.3 where theme_boost\colour_mode does not exist.
if (!class_exists('theme_boost\colour_mode')) {
    class fallback_boost_colour_mode {
        public const LIGHT = 'light';
        public const DARK = 'dark';
        public const AUTO = 'auto';

        public static function get_modes(): array {
            return [self::LIGHT, self::DARK, self::AUTO];
        }

        public static function is_valid_mode(?string $mode): bool {
            return in_array($mode, self::get_modes(), true);
        }

        public static function get_current_mode(): string {
            return self::LIGHT;
        }

        public static function get_cookie_attributes(): array {
            return [];
        }
    }
    class_alias('theme_ebusola\fallback_boost_colour_mode', 'theme_boost\colour_mode');
}

/**
 * Helper for the Boost light and dark colour modes.
 *
 * @package    theme_ebusola
 * @copyright  2026 Willian Mano <willianmanoaraujo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class colour_mode extends \theme_boost\colour_mode {
    /** @var array<string, string> The Font Awesome icon representing each colour mode. */
    private const ICONS = [
        self::LIGHT => 'fa-sun',
        self::DARK => 'fa-moon',
        self::AUTO => 'fa-circle-half-stroke',
    ];

    /**
     * Whether users are allowed to switch between colour modes on this site.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        if (during_initial_install()) {
            return false;
        }

        if (
            defined('BEHAT_SITE_RUNNING')
            && function_exists('behat_get_colour_mode')
            && self::is_valid_mode(behat_get_colour_mode())
        ) {
            return true;
        }

        return (bool) get_config('theme_ebusola', 'enablecolourmodes');
    }

    /**
     * Whether the current user can choose their own colour mode.
     *
     * @return bool
     */
    public static function can_choose_mode(): bool {
        return self::is_enabled() && isloggedin() && !isguestuser();
    }

    /**
     * The colour mode used for users who have not chosen one.
     *
     * @return string One of the self::LIGHT, self::DARK or self::AUTO constants.
     */
    public static function get_site_default(): string {
        if (during_initial_install()) {
            return self::LIGHT;
        }

        if (defined('BEHAT_SITE_RUNNING')) {
            $behatmode = behat_get_colour_mode();
            if (self::is_valid_mode($behatmode)) {
                return $behatmode;
            }
        }

        $default = get_config('theme_ebusola', 'defaultcolourmode');
        return self::is_valid_mode($default) ? $default : self::LIGHT;
    }

    /**
     * Whether the colour mode features apply to the theme currently in use.
     *
     * @return bool
     */
    public static function is_ebusola_theme(): bool {
        global $PAGE;

        $theme = $PAGE->theme;
        return $theme->name === 'ebusola' || in_array('ebusola', $theme->parents, true);
    }

    /**
     * Render the navbar menu for switching between the colour modes.
     *
     * @param \renderer_base $output The renderer to render the menu with.
     * @return string HTML for the colour mode menu, or an empty string.
     */
    public static function render_menu(\renderer_base $output): string {
        if (!self::can_choose_mode()) {
            return '';
        }

        $current = self::get_current_mode();
        $modes = [];
        foreach (self::get_modes() as $mode) {
            $label = get_string('colourmode:' . $mode, 'theme_ebusola');
            $modes[] = [
                'mode' => $mode,
                'label' => $label,
                'icon' => self::ICONS[$mode],
                'togglelabel' => get_string('colourmodeselected', 'theme_ebusola', $label),
                'isactive' => $mode === $current,
            ];
        }

        return $output->render_from_template('theme_ebusola/colour_mode_menu', [
            'cookieattributes' => self::get_cookie_attributes(),
            'currenticon' => self::ICONS[$current],
            'currenttogglelabel' => get_string(
                'colourmodeselected',
                'theme_ebusola',
                get_string('colourmode:' . $current, 'theme_ebusola'),
            ),
            'modes' => $modes,
        ]);
    }
}
