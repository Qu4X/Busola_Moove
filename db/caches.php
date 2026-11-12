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
 * Cache definitions for theme_ebusola.
 *
 * Implements MUC application-level cache per ADR-0001 Rule #18.
 *
 * @package    theme_ebusola
 * @copyright  2026 Szkoła Żeglarstwa Busola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // Course metadata cache: overview image URLs, category badges, category maps.
    'coursemetadata' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 900, // 15 minutes.
        'staticacceleration' => true,
        'staticaccelerationsize' => 50,
        'canuselocalstore' => true,
    ],
];
