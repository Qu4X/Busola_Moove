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

namespace theme_ebusola\output\mod_hvp;

use moodle_url;

/**
 * Class renderer for mod_hvp.
 *
 * @package     theme_ebusola
 * @copyright   2026 Busola
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Add styles when an H5P is displayed.
     *
     * @param array $styles Styles that will be applied.
     * @param array $libraries Libraries that will be shown.
     * @param string $embedtype How the H5P is displayed.
     */
    public function hvp_alter_styles(&$styles, array $libraries, string $embedtype) {
        global $CFG;

        $theme = \theme_config::load('ebusola');

        // Built-in theme H5P styles.
        $themeh5pfile = $CFG->dirroot . '/theme/ebusola/style/h5p.css';
        if (file_exists($themeh5pfile)) {
            $version = filemtime($themeh5pfile);
            $styles[] = (object) [
                'path' => (new moodle_url('/theme/ebusola/style/h5p.css', ['v' => $version]))->out(false),
                'version' => '',
            ];
        }

        $content = $theme->settings->hvpcss ?? '';

        if (!empty($content)) {
            $styles[] = (object) [
                'path' => $this->get_style_url($content),
                'version' => '',
            ];
        }
    }

    /**
     * Add scripts when an H5P is displayed.
     *
     * @param array $scripts Scripts that will be applied.
     * @param array $libraries Libraries that will be shown.
     * @param string $embedtype How the H5P is displayed.
     */
    public function hvp_alter_scripts(&$scripts, array $libraries, string $embedtype) {
        global $CFG;

        $themeh5pjs = $CFG->dirroot . '/theme/ebusola/style/h5p_darkmode.js';
        if (file_exists($themeh5pjs)) {
            $version = filemtime($themeh5pjs);
            $scripts[] = (object) [
                'path' => (new moodle_url('/theme/ebusola/style/h5p_darkmode.js', ['v' => $version]))->out(false),
                'version' => '',
            ];
        }
    }

    /**
     * Get style URL when an H5P is displayed.
     *
     * @param string $content Content.
     *
     * @return moodle_url the URL.
     */
    protected function get_style_url($content) {
        global $CFG;

        $syscontext = \core\context\system::instance();

        $itemid = md5($content);

        return moodle_url::make_file_url(
            "$CFG->wwwroot/pluginfile.php",
            "/$syscontext->id/theme_ebusola/hvp/$itemid/themehvp.css"
        );
    }
}
