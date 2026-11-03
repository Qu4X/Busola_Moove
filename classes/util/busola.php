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
 * @package    theme_ebusola
 * @copyright  2026 Szkoła Żeglarstwa Busola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class busola {
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

        // Fetch enrolled courses.
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

            // Course image and category color.
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

        // Courses to discover.
        $discover = self::get_courses_to_discover($enrolledids);

        // Polish plural labels.
        $totalcourseslabel = self::pluralize_courses($totalcourses);
        $completedlabel = self::pluralize_completed($completedcount);
        $inprogresslabel = self::pluralize_inprogress($inprogresscount);

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
            'messagesurl' => (new moodle_url('/message/index.php'))->out(false),
            'profileurl' => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false),
            'examurl' => self::resolve_exam_url(),
            'discover' => $discover,
        ];
    }

    /**
     * Group unenrolled visible courses into categories for discovery.
     *
     * @param array $enrolledids
     * @return array
     */
    private static function get_courses_to_discover(array $enrolledids): array {
        global $DB;

        $categories = [
            'sail' => [
                'title' => 'Kursy żeglarskie',
                'bgcolor' => '#E3E8FB',
                'icon' => 'sail',
                'courses' => [],
            ],
            'motor' => [
                'title' => 'Kursy motorowodne',
                'bgcolor' => '#DCF0F3',
                'icon' => 'motor',
                'courses' => [],
            ],
            'extra' => [
                'title' => 'Kursy dodatkowe',
                'bgcolor' => '#FFE8D4',
                'icon' => 'extra',
                'courses' => [],
            ],
        ];

        try {
            $allcourses = $DB->get_records('course', ['visible' => 1], 'sortorder ASC', 'id, fullname, shortname');
            foreach ($allcourses as $course) {
                if ($course->id == SITEID || in_array((int) $course->id, $enrolledids)) {
                    continue;
                }

                $meta = self::resolve_course_image_and_badge($course);
                $type = $meta['type'];
                $catkey = ($type === 'motor') ? 'motor' : (($type === 'radio') ? 'extra' : 'sail');

                $categories[$catkey]['courses'][] = [
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
            if ($cnt === 0) {
                $cat['status_label'] = 'Masz już wszystkie kursy z tej kategorii.';
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
     *
     * @param object $course
     * @return array
     */
    private static function resolve_course_image_and_badge(object $course): array {
        global $OUTPUT;

        $shortname = strtoupper(trim($course->shortname ?? ''));
        $fullname = mb_strtolower($course->fullname ?? '');

        // Category mapping:
        // Motorowodne: mint #DCF0F3
        // Żeglarskie: lilac #E3E8FB
        // Dodatkowe / SRC: peach #FFE8D4

        if (str_contains($shortname, 'SM') || str_contains($fullname, 'motorowod') || str_contains($fullname, 'hol')) {
            $cat = get_string('course_cat_motor', 'theme_ebusola');
            $bgcolor = '#DCF0F3';
            $type = 'motor';
            $iconfile = str_contains($fullname, 'hol') ? 'Hol' : (str_contains($shortname, 'MSM') ? 'MSM' : 'SM');
        } else if (str_contains($shortname, 'SRC') || str_contains($fullname, 'radio') || str_contains($fullname, 'src')) {
            $cat = get_string('course_cat_extra', 'theme_ebusola');
            $bgcolor = '#FFE8D4';
            $type = 'extra';
            $iconfile = 'SRC';
        } else {
            $cat = get_string('course_cat_sail', 'theme_ebusola');
            $bgcolor = '#E3E8FB';
            $type = 'sail';
            $iconfile = (str_contains($shortname, 'JSM') || str_contains($fullname, 'morski')) ? 'JSM' : 'ZJ';
        }

        if (empty($cat) || $cat === '[[course_cat_extra]]' || $cat === '[[course_cat_sail]]') {
            $cat = ($type === 'motor') ? 'Kursy motorowodne' : (($type === 'extra') ? 'Kursy dodatkowe' : 'Kursy żeglarskie');
        }

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
            // Fall back to theme icons.
        }

        if (empty($iconurl)) {
            $iconurl = $OUTPUT->image_url('courses/' . $iconfile, 'theme_ebusola')->out(false);
        }

        return [
            'categoryname' => $cat,
            'bgcolor' => $bgcolor,
            'iconurl' => $iconurl,
            'type' => $type,
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
     * Resolve exam information URL dynamically.
     *
     * @return string
     */
    public static function resolve_exam_url(): string {
        global $DB;

        $custom = get_config('theme_ebusola', 'examurl');
        if (!empty($custom)) {
            return $custom;
        }

        try {
            // First look on the front page course (SITEID).
            $sql = "
                SELECT cm.id, m.name AS modname
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                WHERE cm.course = :siteid AND cm.visible = 1
                  AND (
                      (m.name = 'page' AND cm.instance IN (SELECT p.id FROM {page} p WHERE LOWER(p.name) LIKE '%egzamin%' OR LOWER(p.name) LIKE '%informacj%'))
                      OR (m.name = 'resource' AND cm.instance IN (SELECT r.id FROM {resource} r WHERE LOWER(r.name) LIKE '%egzamin%' OR LOWER(r.name) LIKE '%informacj%'))
                      OR (m.name = 'url' AND cm.instance IN (SELECT u.id FROM {url} u WHERE LOWER(u.name) LIKE '%egzamin%' OR LOWER(u.name) LIKE '%informacj%'))
                  )
                ORDER BY cm.id ASC
            ";
            $rec = $DB->get_record_sql($sql, ['siteid' => SITEID], IGNORE_MULTIPLE);
            if ($rec) {
                return (new \moodle_url('/mod/' . $rec->modname . '/view.php', ['id' => $rec->id]))->out(false);
            }

            // Next check any course activity matching "egzamin".
            $sqlany = "
                SELECT cm.id, m.name AS modname
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                WHERE cm.visible = 1
                  AND (
                      (m.name = 'page' AND cm.instance IN (SELECT p.id FROM {page} p WHERE LOWER(p.name) LIKE '%egzamin%'))
                      OR (m.name = 'resource' AND cm.instance IN (SELECT r.id FROM {resource} r WHERE LOWER(r.name) LIKE '%egzamin%'))
                      OR (m.name = 'url' AND cm.instance IN (SELECT u.id FROM {url} u WHERE LOWER(u.name) LIKE '%egzamin%'))
                  )
                ORDER BY cm.id ASC
            ";
            $recany = $DB->get_record_sql($sqlany, [], IGNORE_MULTIPLE);
            if ($recany) {
                return (new \moodle_url('/mod/' . $recany->modname . '/view.php', ['id' => $recany->id]))->out(false);
            }

            // Fallback: any visible page on SITEID.
            $sqlpage = "
                SELECT cm.id, m.name AS modname
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                WHERE cm.course = :siteid AND cm.visible = 1 AND m.name = 'page'
                ORDER BY cm.id ASC
            ";
            $recpage = $DB->get_record_sql($sqlpage, ['siteid' => SITEID], IGNORE_MULTIPLE);
            if ($recpage) {
                return (new \moodle_url('/mod/page/view.php', ['id' => $recpage->id]))->out(false);
            }
        } catch (\Exception $e) {
            // Keep fallback below.
        }

        return (new \moodle_url('/course/view.php', ['id' => SITEID]))->out(false);
    }
}
