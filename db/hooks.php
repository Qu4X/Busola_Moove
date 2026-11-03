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

/**
 * Hook callbacks for theme_ebusola.
 *
 * @package    theme_ebusola
 * @copyright  2026 Szkoła Żeglarstwa Busola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [];

// Only register colour mode hooks if Moodle core Boost supports them (Moodle 5.3+).
if (class_exists('\theme_boost\hook_listener')) {
    $callbacks = [
        [
            'hook' => \core\hook\output\before_requirejs_config::class,
            'callback' => [\theme_ebusola\hook_listener::class, 'before_requirejs_config_listener'],
        ],
        [
            'hook' => \core\hook\output\before_html_attributes::class,
            'callback' => [\theme_ebusola\hook_listener::class, 'before_html_attributes_listener'],
        ],
        [
            'hook' => \core\hook\output\before_standard_head_html_generation::class,
            'callback' => [\theme_ebusola\hook_listener::class, 'before_standard_head_html_generation_listener'],
        ],
    ];
}
