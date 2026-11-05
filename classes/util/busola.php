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

namespace theme_ebusola\util;

defined('MOODLE_INTERNAL') || die();

use core_course_list_element;
use moodle_url;

/**
 * Busola Learning Hub helper.
 *
 * All course names, categories, completion data, and info banners are dynamically
 * pulled from Moodle APIs and the database according to AGENTS.md architectural rules.
 *
 * @package    theme_ebusola
    * @copyright  2026 Szkoła Żeglarstwa Busola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class busola {
    /**
     * Cache and retrieve visible course categories mapping: catid => category name.
     *
     * @return array<int, string>
     */
    private static function get_all_categories_map(): array {
        global $DB;
        static $catmap = null;
        if ($catmap !== null) {
            return $catmap;
        }

        $catmap = [];
        try {
            $categories = $DB->get_records('course_categories', ['visible' => 1], 'sortorder ASC', 'id, name, parent');
            foreach ($categories as $cat) {
                $catmap[(int) $cat->id] = format_string($cat->name);
            }
        } catch (\Exception $e) {
            // Silently fall back.
        }

        return $catmap;
    }

    /**
     * Build the context for the Busola Hub (Hero, My Courses, Categories).
     *
     * @return array|null
     */
    public static function get_dashboard_context(): ?array {
        global $USER, $DB, $OUTPUT, $CFG;

        if (!isloggedin() || isguestuser()) {
            return null;
        }

        $firstname = !empty($USER->firstname) ? $USER->firstname : 'Kursancie';
        $userinitials = mb_substr($USER->firstname ?? 'J', 0, 1) . mb_substr($USER->lastname ?? 'K', 0, 1);

        // Fetch enrolled courses dynamically from Moodle API.
        $mycourses = enrol_get_my_courses('id, fullname, shortname, category, summary, summaryformat');

        $totalcourses = 0;
        $inprogresscount = 0;
        $completedcount = 0;
        $notstartedcount = 0;

        $coursesdata = [];
        $enrolledids = [];
        $lastaccessedcourse = null;
        $latesttimeaccess = 0;

        foreach ($mycourses as $course) {
            if ($course->id == SITEID) {
                continue;
            }

            $enrolledids[] = (int) $course->id;
            $totalcourses++;

            // Progress percentage.
            $progress = null;
            if (class_exists('\core_completion\progress')) {
                $progress = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
            }
            $progressval = !is_null($progress) ? (int) floor($progress) : 0;

            $iscompleted = ($progressval >= 100);
            $isinprogress = ($progressval > 0 && $progressval < 100);
            $isnotstarted = ($progressval === 0);

            if ($iscompleted) {
                $completedcount++;
            } else if ($isinprogress) {
                $inprogresscount++;
            } else {
                $notstartedcount++;
            }

            // Check last access time.
            $lastaccessrec = $DB->get_record('user_lastaccess', ['userid' => $USER->id, 'courseid' => $course->id], 'timeaccess');
            $timeaccess = $lastaccessrec ? (int) $lastaccessrec->timeaccess : 0;

            // Course image and category color derived dynamically.
            $imageinfo = self::resolve_course_image_and_badge($course);

            $coursedata = [
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
                'shortname' => $course->shortname,
                'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                'progress' => $progressval,
                'iscompleted' => $iscompleted,
                'isinprogress' => $isinprogress,
                'isnotstarted' => $isnotstarted,
                'timeaccess' => $timeaccess,
                'categoryname' => $imageinfo['categoryname'],
                'bgcolor' => $imageinfo['bgcolor'],
                'iconurl' => $imageinfo['iconurl'],
                'hascustomicon' => !empty($imageinfo['iconurl']),
                'type' => $imageinfo['type'],
                'progress_color' => $iscompleted ? '#141B5E' : '#FF7A1A',
            ];

            $coursesdata[] = $coursedata;

            if ($timeaccess > $latesttimeaccess) {
                $latesttimeaccess = $timeaccess;
                $lastaccessedcourse = $coursedata;
            }
        }

        // If no last accessed found, use the first in-progress course, or first course.
        if (!$lastaccessedcourse && !empty($coursesdata)) {
            foreach ($coursesdata as $c) {
                if ($c['isinprogress']) {
                    $lastaccessedcourse = $c;
                    break;
                }
            }
            if (!$lastaccessedcourse) {
                $lastaccessedcourse = $coursesdata[0];
            }
        }

        // Courses to discover grouped by actual Moodle categories.
        $discover = self::get_courses_to_discover($enrolledids);

        // Polish plural labels.
        $totalcourseslabel = self::pluralize_courses($totalcourses);
        $completedlabel = self::pluralize_completed($completedcount);
        $inprogresslabel = self::pluralize_inprogress($inprogresscount);

        // Dynamic exam banner information directly from Moodle activity or settings.
        $examinfo = self::resolve_exam_info();

        return [
            'firstname' => $firstname,
            'initials' => mb_strtoupper($userinitials),
            'totalcourses' => $totalcourses,
            'has_totalcourses' => ($totalcourses > 0),
            'totalcourses_label' => $totalcourseslabel,
            'inprogresscount' => $inprogresscount,
            'has_inprogress' => ($inprogresscount > 0),
            'inprogress_label' => $inprogresslabel,
            'completedcount' => $completedcount,
            'has_completed' => ($completedcount > 0),
            'completed_label' => $completedlabel,
            'notstartedcount' => $notstartedcount,
            'hascourses' => !empty($coursesdata),
            'courses' => array_values($coursesdata),
            'haslastcourse' => !empty($lastaccessedcourse),
            'lastcourse' => $lastaccessedcourse,
            'allcoursesurl' => (new moodle_url('/my/courses.php'))->out(false),
            'homeurl' => (new moodle_url('/'))->out(false),
            'dashboardurl' => (new moodle_url('/my/'))->out(false),
            'messagesurl' => (new moodle_url('/message/index.php'))->out(false),
            'profileurl' => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false),
            'examurl' => $examinfo['examurl'],
            'exam_title' => $examinfo['exam_title'],
            'exam_desc' => $examinfo['exam_desc'],
            'exam_btn_text' => $examinfo['exam_btn_text'],
            'discover' => $discover,
        ];
    }

    /**
     * Group unenrolled visible courses into actual Moodle categories for discovery.
     *
     * @param array $enrolledids
     * @return array
     */
    private static function get_courses_to_discover(array $enrolledids): array {
        global $DB;

        $catmap = self::get_all_categories_map();
        $categories = [];

        // Design token pastel colors and SVG icons.
        $pastelcolors = ['#E3E8FB', '#DCF0F3', '#FFE8D4'];
        $icons = ['sail', 'motor', 'extra'];

        $i = 0;
        foreach ($catmap as $catid => $catname) {
            $catnamelower = mb_strtolower($catname);
            if (str_contains($catnamelower, 'motor')) {
                $icon = 'motor';
            } else if (str_contains($catnamelower, 'dodatk') || str_contains($catnamelower, 'radio') || str_contains($catnamelower, 'src')) {
                $icon = 'extra';
            } else {
                $icon = $icons[$i % count($icons)];
            }

            $categories[$catid] = [
                'id' => $catid,
                'title' => $catname,
                'categoryurl' => (new moodle_url('/course/index.php', ['categoryid' => $catid]))->out(false),
                'bgcolor' => $pastelcolors[$catid % count($pastelcolors)],
                'icon' => $icon,
                'is_sail' => ($icon === 'sail'),
                'is_motor' => ($icon === 'motor'),
                'is_extra' => ($icon === 'extra'),
                'courses' => [],
            ];
            $i++;
        }

        try {
            $allcourses = $DB->get_records('course', ['visible' => 1], 'sortorder ASC', 'id, fullname, shortname, category');
            foreach ($allcourses as $course) {
                if ($course->id == SITEID || in_array((int) $course->id, $enrolledids)) {
                    continue;
                }

                $catid = (int) $course->category;
                if (!isset($categories[$catid])) {
                    $catname = $catmap[$catid] ?? format_string($course->fullname);
                    $icon = 'sail';
                    $categories[$catid] = [
                        'id' => $catid,
                        'title' => $catname,
                        'categoryurl' => (new moodle_url('/course/index.php', ['categoryid' => $catid]))->out(false),
                        'bgcolor' => $pastelcolors[$catid % count($pastelcolors)],
                        'icon' => $icon,
                        'is_sail' => true,
                        'is_motor' => false,
                        'is_extra' => false,
                        'courses' => [],
                    ];
                }

                $categories[$catid]['courses'][] = [
                    'fullname' => format_string($course->fullname),
                    'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                ];
            }
        } catch (\Exception $e) {
            // Silently fall back if DB read fails.
        }

        // Add count and labels.
        foreach ($categories as $key => &$cat) {
            $cnt = count($cat['courses']);
            $cat['has_courses'] = ($cnt > 0);
            $cat['targeturl'] = !empty($cat['courses']) ? $cat['courses'][0]['url'] : $cat['categoryurl'];
            if ($cnt === 0) {
                $cat['status_label'] = get_string('all_category_courses_enrolled', 'theme_ebusola');
                if (empty($cat['status_label']) || $cat['status_label'] === '[[all_category_courses_enrolled]]') {
                    $cat['status_label'] = 'Masz już wszystkie kursy z tej kategorii.';
                }
            } else if ($cnt === 1) {
                $cat['status_label'] = '1 kurs do odkrycia';
            } else if ($cnt >= 2 && $cnt <= 4) {
                $cat['status_label'] = $cnt . ' kursy do odkrycia';
            } else {
                $cat['status_label'] = $cnt . ' kursów do odkrycia';
            }
        }

        return array_values($categories);
    }

    /**
     * Resolve badge category, background pastel color, and image.
     * Category name is dynamically fetched from Moodle's course categories.
     *
     * @param object $course
     * @return array
     */
    private static function resolve_course_image_and_badge(object $course): array {
        global $OUTPUT;

        $catmap = self::get_all_categories_map();
        $catid = (int) ($course->category ?? 0);
        $categoryname = $catmap[$catid] ?? '';

        // If category not found in map, attempt direct DB query.
        if (empty($categoryname) && !empty($catid)) {
            global $DB;
            $catrec = $DB->get_record('course_categories', ['id' => $catid], 'name');
            if ($catrec) {
                $categoryname = format_string($catrec->name);
            }
        }

        if (empty($categoryname)) {
            $categoryname = get_string('course_cat_default', 'theme_ebusola');
            if (empty($categoryname) || $categoryname === '[[course_cat_default]]') {
                $categoryname = 'Kurs';
            }
        }

        // Deterministic pastel token background based on category ID.
        $pastelcolors = [
            '#E3E8FB', // Lilac
            '#DCF0F3', // Mint
            '#FFE8D4', // Peach
        ];
        $bgcolor = $pastelcolors[$catid % count($pastelcolors)];

        // Check if course has an overview image uploaded in Moodle course settings.
        $iconurl = null;
        try {
            $coursecontext = \context_course::instance($course->id);
            $fs = get_file_storage();
            $files = $fs->get_area_files($coursecontext->id, 'course', 'overviewfiles', 0, 'itemid, filepath, filename', false);
            foreach ($files as $file) {
                if ($file->is_valid_image()) {
                    $iconurl = \moodle_url::make_file_url(
                        '/pluginfile.php',
                        '/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . $file->get_filepath() . $file->get_filename()
                    )->out(false);
                    break;
                }
            }
        } catch (\Exception $e) {
            // Fall back to theme default icon.
        }

        if (empty($iconurl)) {
            $shortname = strtoupper(trim($course->shortname ?? ''));
            $fullname = mb_strtolower($course->fullname ?? '');
            if (str_contains($shortname, 'SM') || str_contains($fullname, 'motorowod')) {
                $iconfile = str_contains($fullname, 'hol') ? 'Hol' : (str_contains($shortname, 'MSM') ? 'MSM' : 'SM');
            } else if (str_contains($shortname, 'SRC') || str_contains($fullname, 'radio') || str_contains($fullname, 'src')) {
                $iconfile = 'SRC';
            } else if (str_contains($shortname, 'JSM') || str_contains($fullname, 'morski')) {
                $iconfile = 'JSM';
            } else {
                $iconfile = 'ZJ';
            }
            $iconurl = $OUTPUT->image_url('courses/' . $iconfile, 'theme_ebusola')->out(false);
        }

        return [
            'categoryname' => $categoryname,
            'bgcolor' => $bgcolor,
            'iconurl' => $iconurl,
            'type' => 'course',
        ];
    }

    /**
     * Polish plural form for courses count.
     *
     * @param int $count
     * @return string
     */
    private static function pluralize_courses(int $count): string {
        if ($count === 1) {
            return '1 kurs';
        }
        $mod10 = $count % 10;
        $mod100 = $count % 100;
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return $count . ' kursy';
        }
        return $count . ' kursów';
    }

    /**
     * Polish plural form for completed courses count.
     *
     * @param int $count
     * @return string
     */
    private static function pluralize_completed(int $count): string {
        if ($count === 1) {
            return '1 ukończony';
        }
        $mod10 = $count % 10;
        $mod100 = $count % 100;
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return $count . ' ukończone';
        }
        return $count . ' ukończonych';
    }

    /**
     * Polish form for in-progress courses count.
     *
     * @param int $count
     * @return string
     */
    private static function pluralize_inprogress(int $count): string {
        return $count . ' w trakcie';
    }

    /**
     * Resolve exam information dynamically directly from Moodle activity on frontpage.
     *
     * @return array
     */
    public static function resolve_exam_info(): array {
        global $DB;

        $customurl = get_config('theme_ebusola', 'examurl');
        $defaulttitle = get_string('exam_title', 'theme_ebusola');
        $defaultdesc = get_string('exam_desc', 'theme_ebusola');
        $defaultbtn = get_string('exam_btn', 'theme_ebusola');

        try {
            // Find visible page activity on the front page course (SITEID).
            $sql = "
                SELECT cm.id AS cmid, m.name AS modname, p.name, p.intro, p.introformat
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                JOIN {page} p ON p.id = cm.instance
                WHERE cm.course = :siteid AND cm.visible = 1 AND m.name = 'page'
                ORDER BY cm.id ASC
            ";
            $recs = $DB->get_records_sql($sql, ['siteid' => SITEID]);
            $matched = null;
            if (!empty($recs)) {
                foreach ($recs as $r) {
                    $low = mb_strtolower($r->name);
                    if (str_contains($low, 'egzamin') || str_contains($low, 'informacj')) {
                        $matched = $r;
                        break;
                    }
                }
                if (!$matched) {
                    $matched = reset($recs);
                }
            }

            if ($matched) {
                $url = !empty($customurl) ? $customurl : (new moodle_url('/mod/page/view.php', ['id' => $matched->cmid]))->out(false);
                $title = !empty(trim($matched->name)) ? format_string($matched->name) : $defaulttitle;
                $desc = !empty(trim($matched->intro)) ? strip_tags(format_text($matched->intro, $matched->introformat)) : $defaultdesc;

                return [
                    'examurl' => $url,
                    'exam_title' => $title,
                    'exam_desc' => $desc,
                    'exam_btn_text' => $defaultbtn,
                ];
            }
        } catch (\Exception $e) {
            // Silently fall back.
        }

        return [
            'examurl' => !empty($customurl) ? $customurl : (new moodle_url('/course/view.php', ['id' => SITEID]))->out(false),
            'exam_title' => $defaulttitle,
            'exam_desc' => $defaultdesc,
            'exam_btn_text' => $defaultbtn,
        ];
    }

    /**
     * Resolve exam information URL dynamically.
     *
     * @return string
     */
    public static function resolve_exam_url(): string {
        $info = self::resolve_exam_info();
        return $info['examurl'];
    }
}
