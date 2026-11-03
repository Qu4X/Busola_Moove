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
        $mycourses = enrol_get_my_courses(['id', 'fullname', 'shortname', 'category', 'summary', 'summaryformat']);

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
            'totalcourses_label' => $totalcourseslabel,
            'inprogresscount' => $inprogresscount,
            'inprogress_label' => $inprogresslabel,
            'completedcount' => $completedcount,
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
            'examurl' => 'https://kursybusola.pl',
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
            $cat = 'Kursy motorowodne';
            $bgcolor = '#DCF0F3';
            $type = 'motor';
            $iconfile = str_contains($fullname, 'hol') ? 'Hol.png' : (str_contains($shortname, 'MSM') ? 'MSM.png' : 'SM.png');
        } else if (str_contains($shortname, 'SRC') || str_contains($fullname, 'radio') || str_contains($fullname, 'src')) {
            $cat = 'Kursy dodatkowe';
            $bgcolor = '#FFE8D4';
            $type = 'radio';
            $iconfile = 'SRC.png';
        } else {
            $cat = 'Kursy żeglarskie';
            $bgcolor = '#E3E8FB';
            $type = 'sail';
            $iconfile = (str_contains($shortname, 'JSM') || str_contains($fullname, 'morski')) ? 'JSM.png' : 'ZJ.png';
        }

        $iconurl = $OUTPUT->image_url('courses/' . $iconfile, 'theme_ebusola')->out(false);

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
}
