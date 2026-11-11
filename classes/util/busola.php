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
     * Build the context for the guest / unauthenticated frontpage view.
     *
     * All course names, categories, and exam info are dynamically pulled from Moodle
     * APIs and database according to AGENTS.md architectural rules.
     *
     * @return array
     */
    public static function get_guest_frontpage_context(): array {
        global $DB, $OUTPUT;

        $catmap = self::get_all_categories_map();

        $courses = [];
        try {
            $records = $DB->get_records('course', ['visible' => 1], 'sortorder ASC', 'id, fullname, shortname, category');
            foreach ($records as $course) {
                if ($course->id == SITEID) {
                    continue;
                }

                $catid = (int) ($course->category ?? 0);
                $categoryname = $catmap[$catid] ?? '';
                if (empty($categoryname) && !empty($catid)) {
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

                // Determine course illustration and background color.
                $shortname = strtoupper(trim($course->shortname ?? ''));
                $fullname = mb_strtolower($course->fullname ?? '');
                $catnamelower = mb_strtolower($categoryname);

                if (str_contains($catnamelower, 'motor') || (!str_contains($catnamelower, 'żegl') && !str_contains($catnamelower, 'zegl') && ($shortname === 'SM' || $shortname === 'MSM' || str_contains($fullname, 'motorowod')))) {
                    $illustration = 'motor';
                    $bgcolor = 'var(--busola-cat-motor-bg)';
                } else if (str_contains($catnamelower, 'dodatk') || str_contains($catnamelower, 'radio') || str_contains($shortname, 'SRC') || str_contains($fullname, 'radio')) {
                    $illustration = 'extra';
                    $bgcolor = 'var(--busola-cat-extra-bg)';
                } else {
                    $illustration = 'sail';
                    $bgcolor = 'var(--busola-cat-sail-bg)';
                }

                // Check overview files for uploaded image.
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
                    // Fall back to vector illustration.
                }

                $courses[] = [
                    'id' => $course->id,
                    'fullname' => format_string($course->fullname),
                    'shortname' => $course->shortname,
                    'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                    'categoryname' => $categoryname,
                    'bgcolor' => $bgcolor,
                    'iconurl' => $iconurl,
                    'hascustomicon' => !empty($iconurl),
                    'illustration' => $illustration,
                    'is_sail' => ($illustration === 'sail'),
                    'is_motor' => ($illustration === 'motor'),
                    'is_extra' => ($illustration === 'extra'),
                ];
            }
        } catch (\Exception $e) {
            // Silently fall back.
        }

        $examinfo = self::resolve_exam_info();

        return [
            'homeurl' => (new moodle_url('/'))->out(false),
            'loginurl' => (new moodle_url('/login/index.php'))->out(false),
            'courses' => $courses,
            'has_courses' => !empty($courses),
            'examurl' => $examinfo['examurl'],
            'exam_title' => $examinfo['exam_title'],
            'exam_desc' => $examinfo['exam_desc'],
            'exam_btn_text' => $examinfo['exam_btn_text'],
        ];
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
            'is_home_active' => true,
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
            if (str_contains($shortname, 'JSM') || (str_contains($fullname, 'jachtowy') && str_contains($fullname, 'morski'))) {
                $iconfile = 'JSM';
            } else if (str_contains($shortname, 'SM') || str_contains($fullname, 'motorowod')) {
                $iconfile = str_contains($fullname, 'hol') ? 'Hol' : (str_contains($shortname, 'MSM') ? 'MSM' : 'SM');
            } else if (str_contains($shortname, 'SRC') || str_contains($fullname, 'radio') || str_contains($fullname, 'src')) {
                $iconfile = 'SRC';
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

    /**
     * Build the context for the Kokpit view (/my/).
     *
     * All courses, quiz attempts, results, calendar events, and exam links
     * are dynamically fetched from Moodle database/APIs according to AGENTS.md.
     *
     * @return array|null
     */
    public static function get_kokpit_context(): ?array {
        global $USER, $DB;

        if (!isloggedin() || isguestuser()) {
            return null;
        }

        $firstname = !empty($USER->firstname) ? $USER->firstname : 'Kursancie';

        // 1. Enrolled courses and last accessed course.
        $mycourses = enrol_get_my_courses('id, fullname, shortname, category, summary, summaryformat');
        $enrolledids = [];
        $lastaccessedcourse = null;
        $latesttimeaccess = 0;

        foreach ($mycourses as $course) {
            if ($course->id == SITEID) {
                continue;
            }
            $enrolledids[] = (int) $course->id;

            $lastaccessrec = $DB->get_record('user_lastaccess', ['userid' => $USER->id, 'courseid' => $course->id], 'timeaccess');
            $timeaccess = $lastaccessrec ? (int) $lastaccessrec->timeaccess : 0;
            if ($timeaccess > $latesttimeaccess) {
                $latesttimeaccess = $timeaccess;
                $lastaccessedcourse = $course;
            }
        }

        if (!$lastaccessedcourse && !empty($mycourses)) {
            foreach ($mycourses as $course) {
                if ($course->id != SITEID) {
                    $lastaccessedcourse = $course;
                    break;
                }
            }
        }

        // 2. Continue card data.
        $continuecard = null;
        if ($lastaccessedcourse) {
            $imageinfo = self::resolve_course_image_and_badge($lastaccessedcourse);
            $categoryname = $imageinfo['categoryname'];
            $bgcolor = $imageinfo['bgcolor'];
            $iconurl = $imageinfo['iconurl'];
            $shortname = strtoupper(trim($lastaccessedcourse->shortname ?? ''));
            $fullname = mb_strtolower($lastaccessedcourse->fullname ?? '');
            $catnamelower = mb_strtolower($categoryname);

            if (str_contains($catnamelower, 'motor') || (!str_contains($catnamelower, 'żegl') && !str_contains($catnamelower, 'zegl') && ($shortname === 'SM' || $shortname === 'MSM' || str_contains($fullname, 'motorowod')))) {
                $illustration = 'motor';
            } else if (str_contains($catnamelower, 'dodatk') || str_contains($catnamelower, 'radio') || str_contains($shortname, 'SRC') || str_contains($fullname, 'radio')) {
                $illustration = 'extra';
            } else {
                $illustration = 'sail';
            }

            // Look for recent quiz attempt in this course or overall.
            $lastattempt = null;
            try {
                $sql = "
                    SELECT qa.id, qa.quiz, qa.attempt, qa.sumgrades, qa.timefinish,
                           q.name AS quizname, q.sumgrades AS quizsumgrades,
                           cm.id AS cmid
                    FROM {quiz_attempts} qa
                    JOIN {quiz} q ON q.id = qa.quiz
                    JOIN {modules} m ON m.name = 'quiz'
                    LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = q.id AND cm.course = q.course
                    WHERE qa.userid = :userid AND q.course = :courseid AND qa.state = 'finished'
                    ORDER BY qa.timefinish DESC
                ";
                $attempts = $DB->get_records_sql($sql, ['userid' => $USER->id, 'courseid' => $lastaccessedcourse->id], 0, 1);
                if (!empty($attempts)) {
                    $lastattempt = reset($attempts);
                }
            } catch (\Exception $e) {
                // Silently fall back.
            }

            if (!$lastattempt && !empty($enrolledids)) {
                try {
                    list($insql, $inparams) = $DB->get_in_or_equal($enrolledids, SQL_PARAMS_NAMED, 'attc');
                    $sql = "
                        SELECT qa.id, qa.quiz, qa.attempt, qa.sumgrades, qa.timefinish,
                               q.name AS quizname, q.sumgrades AS quizsumgrades,
                               cm.id AS cmid
                        FROM {quiz_attempts} qa
                        JOIN {quiz} q ON q.id = qa.quiz
                        JOIN {modules} m ON m.name = 'quiz'
                        LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = q.id AND cm.course = q.course
                        WHERE qa.userid = :userid AND q.course $insql AND qa.state = 'finished'
                        ORDER BY qa.timefinish DESC
                    ";
                    $params = array_merge(['userid' => $USER->id], $inparams);
                    $attempts = $DB->get_records_sql($sql, $params, 0, 1);
                    if (!empty($attempts)) {
                        $lastattempt = reset($attempts);
                    }
                } catch (\Exception $e) {
                    // Silently fall back.
                }
            }

            if ($lastattempt) {
                $quizname = format_string($lastattempt->quizname);
                $quizsum = (float) ($lastattempt->quizsumgrades ?? 0);
                $usersum = (float) ($lastattempt->sumgrades ?? 0);
                if ($quizsum > 0) {
                    $scoredisplay = round($usersum) . '/' . round($quizsum);
                    $subtitle = 'Ostatnio: ' . $quizname . ' · wynik ' . $scoredisplay;
                    $subtitleshort = 'Ostatnio: ' . $scoredisplay;
                } else {
                    $subtitle = 'Ostatnio: ' . $quizname;
                    $subtitleshort = 'Ostatnio: test';
                }
                $btntext = get_string('kokpit_btn_retry', 'theme_ebusola');
                if (empty($btntext) || $btntext === '[[kokpit_btn_retry]]') {
                    $btntext = 'Rozwiąż ponownie';
                }
                $btnurl = $lastattempt->cmid
                    ? (new moodle_url('/mod/quiz/view.php', ['id' => $lastattempt->cmid]))->out(false)
                    : (new moodle_url('/course/view.php', ['id' => $lastaccessedcourse->id]))->out(false);
            } else {
                $progress = null;
                if (class_exists('\core_completion\progress')) {
                    $progress = \core_completion\progress::get_course_progress_percentage($lastaccessedcourse, $USER->id);
                }
                $progressval = !is_null($progress) ? (int) floor($progress) : 0;
                $subtitle = ($progressval > 0) ? 'Postęp kursu: ' . $progressval . '%' : 'Rozpocznij naukę w tym kursie';
                $subtitleshort = ($progressval > 0) ? 'Postęp: ' . $progressval . '%' : 'Rozpocznij naukę';
                $btntext = get_string('kokpit_btn_continue', 'theme_ebusola');
                if (empty($btntext) || $btntext === '[[kokpit_btn_continue]]') {
                    $btntext = 'Kontynuuj naukę';
                }
                $btnurl = (new moodle_url('/course/view.php', ['id' => $lastaccessedcourse->id]))->out(false);
            }

            $continuecard = [
                'has_course' => true,
                'course_id' => $lastaccessedcourse->id,
                'course_name' => format_string($lastaccessedcourse->fullname),
                'course_url' => (new moodle_url('/course/view.php', ['id' => $lastaccessedcourse->id]))->out(false),
                'btn_url' => $btnurl,
                'btn_text' => $btntext,
                'subtitle' => $subtitle,
                'subtitle_short' => $subtitleshort,
                'bgcolor' => $bgcolor,
                'iconurl' => $iconurl,
                'hascustomicon' => !empty($iconurl),
                'illustration' => $illustration,
                'is_sail' => ($illustration === 'sail'),
                'is_motor' => ($illustration === 'motor'),
                'is_extra' => ($illustration === 'extra'),
            ];
        } else {
            $continuecard = [
                'has_course' => false,
            ];
        }

        // 3. Recent Quiz Results (last 6 finished attempts).
        $results = [];
        try {
            $sql = "
                SELECT qa.id, qa.quiz, qa.attempt, qa.sumgrades, qa.timefinish,
                       q.name AS quizname, q.course AS courseid, q.sumgrades AS quizsumgrades,
                       c.fullname AS coursename, c.shortname AS courseshortname,
                       cm.id AS cmid
                FROM {quiz_attempts} qa
                JOIN {quiz} q ON q.id = qa.quiz
                JOIN {course} c ON c.id = q.course
                JOIN {modules} m ON m.name = 'quiz'
                LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = q.id AND cm.course = q.course
                WHERE qa.userid = :userid AND qa.state = 'finished'
                ORDER BY qa.timefinish DESC
            ";
            $attemptrecs = $DB->get_records_sql($sql, ['userid' => $USER->id], 0, 6);
            foreach ($attemptrecs as $ar) {
                $quizsum = (float) ($ar->quizsumgrades ?? 0);
                $usersum = (float) ($ar->sumgrades ?? 0);
                if ($quizsum > 0) {
                    $percent = (int) round(($usersum / $quizsum) * 100);
                    $scoretext = round($usersum) . '/' . round($quizsum);
                } else {
                    $percent = 0;
                    $scoretext = (string) round($usersum);
                }
                $passed = ($percent >= 75);

                $dateformatted = self::format_polish_relative_date((int) $ar->timefinish);
                $subtext = format_string($ar->coursename) . ' · ' . $dateformatted;
                $subtextmobile = mb_convert_case(mb_substr($dateformatted, 0, 1), MB_CASE_UPPER, 'UTF-8') . mb_substr($dateformatted, 1);

                $url = $ar->cmid
                    ? (new moodle_url('/mod/quiz/review.php', ['attempt' => $ar->id]))->out(false)
                    : (new moodle_url('/course/view.php', ['id' => $ar->courseid]))->out(false);

                $passedstr = get_string('kokpit_results_passed', 'theme_ebusola');
                if (empty($passedstr) || $passedstr === '[[kokpit_results_passed]]') {
                    $passedstr = 'Zaliczony';
                }
                $failedstr = get_string('kokpit_results_failed', 'theme_ebusola');
                if (empty($failedstr) || $failedstr === '[[kokpit_results_failed]]') {
                    $failedstr = 'Poniżej progu';
                }

                $results[] = [
                    'id' => $ar->id,
                    'quiz_name' => format_string($ar->quizname),
                    'course_name' => format_string($ar->coursename),
                    'score_text' => $scoretext,
                    'percent' => $percent,
                    'is_passed' => $passed,
                    'is_failed' => !$passed,
                    'status_label' => $passed ? $passedstr : $failedstr,
                    'subtext' => $subtext,
                    'subtext_mobile' => $subtextmobile,
                    'url' => $url,
                ];
            }
        } catch (\Exception $e) {
            // Silently fall back.
        }

        // 4. Calendar computation.
        $calm = optional_param('cal_m', (int) date('n'), PARAM_INT);
        $caly = optional_param('cal_y', (int) date('Y'), PARAM_INT);
        if ($calm < 1 || $calm > 12) {
            $calm = (int) date('n');
        }
        if ($caly < 2000 || $caly > 2100) {
            $caly = (int) date('Y');
        }

        $polishmonths = [
            1 => 'Styczeń', 2 => 'Luty', 3 => 'Marzec', 4 => 'Kwiecień',
            5 => 'Maj', 6 => 'Czerwiec', 7 => 'Lipiec', 8 => 'Sierpień',
            9 => 'Wrzesień', 10 => 'Październik', 11 => 'Listopad', 12 => 'Grudzień',
        ];
        $monthname = $polishmonths[$calm] ?? date('F', mktime(0, 0, 0, $calm, 1, $caly));
        $calendartitle = $monthname . ' ' . $caly;

        $prevm = ($calm === 1) ? 12 : $calm - 1;
        $prevy = ($calm === 1) ? $caly - 1 : $caly;
        $nextm = ($calm === 12) ? 1 : $calm + 1;
        $nexty = ($calm === 12) ? $caly + 1 : $caly;
        $prevurl = (new moodle_url('/my/', ['cal_m' => $prevm, 'cal_y' => $prevy]))->out(false);
        $nexturl = (new moodle_url('/my/', ['cal_m' => $nextm, 'cal_y' => $nexty]))->out(false);

        $numdays = (int) date('t', mktime(0, 0, 0, $calm, 1, $caly));
        $firstdayofweek = (int) date('N', mktime(0, 0, 0, $calm, 1, $caly)); // 1 (Pn) - 7 (Nd)
        $leadingemptycount = $firstdayofweek - 1;
        $leadingempties = [];
        for ($i = 0; $i < $leadingemptycount; $i++) {
            $leadingempties[] = ['empty' => true];
        }

        $todayd = (int) date('j');
        $todaym = (int) date('n');
        $todayy = (int) date('Y');
        $istodaymonth = ($calm === $todaym && $caly === $todayy);

        // Fetch events for current month from {event}.
        $mstart = mktime(0, 0, 0, $calm, 1, $caly);
        $mend = mktime(23, 59, 59, $calm, $numdays, $caly);
        $eventdays = [];
        try {
            $params = [
                'userid' => $USER->id,
                'siteid' => SITEID,
                'mstart' => $mstart,
                'mend' => $mend,
            ];
            $coursewhere = "e.courseid = :siteid";
            if (!empty($enrolledids)) {
                list($einsql, $einparams) = $DB->get_in_or_equal($enrolledids, SQL_PARAMS_NAMED, 'ec');
                $coursewhere .= " OR e.courseid $einsql";
                $params = array_merge($params, $einparams);
            }
            $eventsql = "
                SELECT e.id, e.name, e.timestart, e.courseid
                FROM {event} e
                WHERE (e.userid = :userid OR $coursewhere)
                  AND e.timestart >= :mstart AND e.timestart <= :mend
                  AND e.visible = 1
                ORDER BY e.timestart ASC
            ";
            $monthrecs = $DB->get_records_sql($eventsql, $params);
            foreach ($monthrecs as $me) {
                $eday = (int) date('j', (int) $me->timestart);
                $eventdays[$eday] = format_string($me->name);
            }
        } catch (\Exception $e) {
            // Silently fall back.
        }

        $days = [];
        for ($d = 1; $d <= $numdays; $d++) {
            $istoday = ($istodaymonth && $d === $todayd);
            $hasevent = isset($eventdays[$d]);
            $eventtitle = $eventdays[$d] ?? '';
            $days[] = [
                'day' => $d,
                'is_today' => $istoday,
                'has_event' => $hasevent,
                'event_title' => $eventtitle,
                'is_normal' => (!$istoday && !$hasevent),
            ];
        }

        $calendarurl = (new moodle_url('/calendar/view.php', ['view' => 'month']))->out(false);

        // 5. Upcoming event.
        $upcomingevent = null;
        try {
            $now = time();
            $params = [
                'userid' => $USER->id,
                'siteid' => SITEID,
                'now' => $now,
            ];
            $coursewhere = "e.courseid = :siteid";
            if (!empty($enrolledids)) {
                list($ueinsql, $ueinparams) = $DB->get_in_or_equal($enrolledids, SQL_PARAMS_NAMED, 'uec');
                $coursewhere .= " OR e.courseid $ueinsql";
                $params = array_merge($params, $ueinparams);
            }
            $upsql = "
                SELECT e.id, e.name, e.timestart, e.courseid
                FROM {event} e
                WHERE (e.userid = :userid OR $coursewhere)
                  AND e.timestart >= :now
                  AND e.visible = 1
                ORDER BY e.timestart ASC
            ";
            $uprecs = $DB->get_records_sql($upsql, $params, 0, 1);
            if (!empty($uprecs)) {
                $ue = reset($uprecs);
                $tstart = (int) $ue->timestart;
                $diffdays = (int) floor(($tstart - $now) / 86400);
                if ($diffdays <= 0) {
                    $reltext = 'Dziś, ' . date('H:i', $tstart);
                } else if ($diffdays === 1) {
                    $reltext = 'Jutro, ' . date('H:i', $tstart);
                } else {
                    $reltext = 'Za ' . $diffdays . ' ' . self::pluralize_days($diffdays);
                }
                $shortmonths = [
                    1 => 'STY', 2 => 'LUT', 3 => 'MAR', 4 => 'KWI',
                    5 => 'MAJ', 6 => 'CZE', 7 => 'LIP', 8 => 'SIE',
                    9 => 'WRZ', 10 => 'PAŹ', 11 => 'LIS', 12 => 'GRU',
                ];
                $monthshort = $shortmonths[(int) date('n', $tstart)] ?? strtoupper(date('M', $tstart));
                $daynum = date('j', $tstart);
                $eventurl = (new moodle_url('/calendar/view.php', ['view' => 'day', 'time' => $tstart]))->out(false);

                $upcomingevent = [
                    'has_event' => true,
                    'title' => format_string($ue->name),
                    'month_short' => $monthshort,
                    'day_num' => $daynum,
                    'relative_text' => $reltext,
                    'event_url' => $eventurl,
                    'link_text' => 'Zobacz szczegóły',
                ];
            }
        } catch (\Exception $e) {
            // Silently fall back.
        }

        if (!$upcomingevent) {
            $examinfo = self::resolve_exam_info();
            $upcomingevent = [
                'has_event' => false,
                'is_exam_fallback' => true,
                'title' => $examinfo['exam_title'],
                'month_short' => 'EGZ',
                'day_num' => '!',
                'relative_text' => $examinfo['exam_desc'],
                'event_url' => $examinfo['examurl'],
                'link_text' => $examinfo['exam_btn_text'],
            ];
        }

        return [
            'firstname' => $firstname,
            'continue_card' => $continuecard,
            'results' => $results,
            'has_results' => !empty($results),
            'results_count' => count($results),
            'calendar' => [
                'title' => $calendartitle,
                'prev_url' => $prevurl,
                'next_url' => $nexturl,
                'leading_empties' => $leadingempties,
                'days' => $days,
                'full_url' => $calendarurl,
            ],
            'upcoming_event' => $upcomingevent,
            'homeurl' => (new moodle_url('/'))->out(false),
            'dashboardurl' => (new moodle_url('/my/'))->out(false),
            'allcoursesurl' => (new moodle_url('/my/courses.php'))->out(false),
            'profileurl' => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false),
            'messagesurl' => (new moodle_url('/message/index.php'))->out(false),
            'is_kokpit_active' => true,
        ];
    }

    /**
     * Format a timestamp into a Polish relative/short date string.
     *
     * @param int $timestamp
     * @return string
     */
    private static function format_polish_relative_date(int $timestamp): string {
        $todaystart = strtotime('today midnight');
        $yesterdaystart = strtotime('yesterday midnight');
        $timehm = date('H:i', $timestamp);

        if ($timestamp >= $todaystart) {
            return 'dziś, ' . $timehm;
        } else if ($timestamp >= $yesterdaystart && $timestamp < $todaystart) {
            return 'wczoraj, ' . $timehm;
        }

        $shortmonths = [
            1 => 'stycznia', 2 => 'lutego', 3 => 'marca', 4 => 'kwietnia',
            5 => 'maja', 6 => 'czerwca', 7 => 'lipca', 8 => 'sierpnia',
            9 => 'września', 10 => 'października', 11 => 'listopada', 12 => 'grudnia',
        ];
        $day = (int) date('j', $timestamp);
        $mon = (int) date('n', $timestamp);
        $year = (int) date('Y', $timestamp);
        $currentyear = (int) date('Y');

        if ($year === $currentyear) {
            return $day . ' ' . ($shortmonths[$mon] ?? date('M', $timestamp));
        }
        return $day . ' ' . ($shortmonths[$mon] ?? date('M', $timestamp)) . ' ' . $year;
    }

    /**
     * Polish plural form for days count.
     *
     * @param int $days
     * @return string
     */
    private static function pluralize_days(int $days): string {
        if ($days === 1) {
            return 'dzień';
        }
        return 'dni';
    }

    /**
     * Build the context for the My Courses page (/my/courses.php).
     *
     * All enrolled courses, visible sections counts, completion progress, favourites,
     * and category groupings are dynamically pulled from Moodle APIs and the database
     * without any N+1 queries according to AGENTS.md and ADR 0001.
     *
     * @return array|null
     */
    public static function get_mycourses_page_context(): ?array {
        global $USER, $DB;

        if (!isloggedin() || isguestuser()) {
            return null;
        }

        $mycourses = enrol_get_my_courses('id, fullname, shortname, category, summary, summaryformat, visible');
        $courseids = [];
        $validcourses = [];

        foreach ($mycourses as $course) {
            if ($course->id == SITEID) {
                continue;
            }
            $cid = (int) $course->id;
            $courseids[] = $cid;
            $validcourses[$cid] = $course;
        }

        if (empty($validcourses)) {
            $filterallshort = get_string('mycourses_filter_all_short', 'theme_ebusola');
            if (empty($filterallshort) || $filterallshort === '[[mycourses_filter_all_short]]') {
                $filterallshort = 'Wszystkie';
            }
            return [
                'has_courses' => false,
                'total_courses' => 0,
                'summary_text' => '0 kursów',
                'filter_all_short' => $filterallshort,
                'categories' => [],
                'homeurl' => (new moodle_url('/'))->out(false),
                'dashboardurl' => (new moodle_url('/my/'))->out(false),
                'allcoursesurl' => (new moodle_url('/my/courses.php'))->out(false),
                'profileurl' => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false),
                'is_courses_active' => true,
            ];
        }

        // Single SQL query for visible sections count per course.
        $sectionscounts = [];
        try {
            list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'sc');
            $sql = "
                SELECT course, COUNT(id) AS total_sections
                FROM {course_sections}
                WHERE course $insql AND section > 0 AND visible = 1
                GROUP BY course
            ";
            $secrecs = $DB->get_records_sql($sql, $inparams);
            foreach ($secrecs as $sr) {
                $sectionscounts[(int) $sr->course] = (int) $sr->total_sections;
            }
        } catch (\Exception $e) {
            // Silently fall back.
        }

        // Single SQL query for favourites.
        $favourites = [];
        try {
            list($finsql, $finparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'fc');
            $sql = "
                SELECT itemid
                FROM {favourite}
                WHERE component = 'core_course'
                  AND itemtype = 'courses'
                  AND userid = :userid
                  AND itemid $finsql
            ";
            $fparams = array_merge(['userid' => $USER->id], $finparams);
            $favrecs = $DB->get_records_sql($sql, $fparams);
            foreach ($favrecs as $fr) {
                $favourites[(int) $fr->itemid] = true;
            }
        } catch (\Exception $e) {
            // Silently fall back.
        }

        // Single SQL query for user_lastaccess.
        $lastaccesses = [];
        try {
            list($lainsql, $lainparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'lac');
            $sql = "
                SELECT courseid, timeaccess
                FROM {user_lastaccess}
                WHERE userid = :userid
                  AND courseid $lainsql
            ";
            $laparams = array_merge(['userid' => $USER->id], $lainparams);
            $larecs = $DB->get_records_sql($sql, $laparams);
            foreach ($larecs as $lr) {
                $lastaccesses[(int) $lr->courseid] = (int) $lr->timeaccess;
            }
        } catch (\Exception $e) {
            // Silently fall back.
        }

        // All visible categories.
        $catmap = self::get_all_categories_map();
        $catorder = [];
        try {
            $catrecs = $DB->get_records('course_categories', ['visible' => 1], 'sortorder ASC', 'id, name, sortorder');
            foreach ($catrecs as $cr) {
                $catorder[(int) $cr->id] = (int) $cr->sortorder;
            }
        } catch (\Exception $e) {
            // Silently fall back.
        }

        // Process courses and group by category.
        $categorygroups = [];
        foreach ($validcourses as $cid => $course) {
            $catid = (int) ($course->category ?? 0);
            $catname = $catmap[$catid] ?? '';
            if (empty($catname)) {
                $catname = get_string('course_cat_default', 'theme_ebusola');
                if (empty($catname) || $catname === '[[course_cat_default]]') {
                    $catname = 'Kursy';
                }
            }

            // Category color and illustration.
            $shortname = strtoupper(trim($course->shortname ?? ''));
            $fullname = format_string($course->fullname);
            $fullnamelower = mb_strtolower($fullname);
            $catnamelower = mb_strtolower($catname);

            if (str_contains($catnamelower, 'motor') || (!str_contains($catnamelower, 'żegl') && !str_contains($catnamelower, 'zegl') && ($shortname === 'SM' || $shortname === 'MSM' || str_contains($fullnamelower, 'motorowod')))) {
                $illustration = 'motor';
                $bgcolor = 'var(--busola-cat-motor-bg)';
            } else if (str_contains($catnamelower, 'dodatk') || str_contains($catnamelower, 'radio') || str_contains($shortname, 'SRC') || str_contains($fullnamelower, 'radio')) {
                $illustration = 'extra';
                $bgcolor = 'var(--busola-cat-extra-bg)';
            } else {
                $illustration = 'sail';
                $bgcolor = 'var(--busola-cat-sail-bg)';
            }

            // Course image from overviewfiles.
            $iconurl = null;
            try {
                $coursecontext = \context_course::instance($cid);
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
                // Silently fall back.
            }

            // Completion progress.
            $progress = null;
            if (class_exists('\core_completion\progress')) {
                $progress = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
            }
            $progressval = !is_null($progress) ? (int) round($progress) : 0;

            // Visible sections count: default to 6 if 0.
            $totalsections = $sectionscounts[$cid] ?? 0;
            if ($totalsections <= 0) {
                $totalsections = 6;
            }

            $isnotstarted = ($progressval === 0);
            $iscompleted = ($progressval >= 100);
            $isinprogress = ($progressval > 0 && $progressval < 100);

            if ($iscompleted) {
                $status = 'completed';
            } else if ($isinprogress) {
                $status = 'inprogress';
            } else {
                $status = 'notstarted';
            }

            // Segments calculation.
            $displaysegments = min(12, max(1, $totalsections));
            $completedsegments = (int) round(($progressval / 100) * $displaysegments);
            if ($isinprogress && $completedsegments === 0) {
                $completedsegments = 1;
            }
            if ($isinprogress && $completedsegments === $displaysegments) {
                $completedsegments = max(1, $displaysegments - 1);
            }
            if ($iscompleted) {
                $completedsegments = $displaysegments;
            }

            $segments = [];
            for ($s = 1; $s <= $displaysegments; $s++) {
                $segments[] = [
                    'is_done' => ($s <= $completedsegments),
                ];
            }

            $completedactual = (int) round(($progressval / 100) * $totalsections);
            if ($isinprogress && $completedactual === 0) {
                $completedactual = 1;
            }
            if ($isinprogress && $completedactual === $totalsections) {
                $completedactual = max(1, $totalsections - 1);
            }

            if ($iscompleted) {
                $progresslabel = get_string('mycourses_completed', 'theme_ebusola') . ' (' . $totalsections . ' z ' . $totalsections . ')';
            } else if ($isinprogress) {
                $progresslabel = $completedactual . ' z ' . $totalsections;
            } else {
                $progresslabel = '';
            }

            $isfavourite = !empty($favourites[$cid]);
            $favarialabel = $isfavourite
                ? get_string('mycourses_fav_remove', 'theme_ebusola', $fullname)
                : get_string('mycourses_fav_add', 'theme_ebusola', $fullname);

            $lastaccess = $lastaccesses[$cid] ?? 0;

            $coursedata = [
                'id' => $cid,
                'fullname' => $fullname,
                'fullname_lower' => $fullnamelower,
                'shortname' => $course->shortname,
                'url' => (new moodle_url('/course/view.php', ['id' => $cid]))->out(false),
                'categoryname' => $catname,
                'bgcolor' => $bgcolor,
                'iconurl' => $iconurl,
                'hascustomicon' => !empty($iconurl),
                'illustration' => $illustration,
                'is_sail' => ($illustration === 'sail'),
                'is_motor' => ($illustration === 'motor'),
                'is_extra' => ($illustration === 'extra'),
                'progress' => $progressval,
                'is_not_started' => $isnotstarted,
                'is_in_progress' => $isinprogress,
                'is_completed' => $iscompleted,
                'status' => $status,
                'total_sections' => $totalsections,
                'completed_sections' => $completedactual,
                'segments' => $segments,
                'progress_label' => $progresslabel,
                'is_favourite' => $isfavourite,
                'fav_aria_label' => $favarialabel,
                'lastaccess' => $lastaccess,
            ];

            if (!isset($categorygroups[$catid])) {
                $categorygroups[$catid] = [
                    'id' => $catid,
                    'name' => $catname,
                    'sortorder' => $catorder[$catid] ?? $catid,
                    'courses' => [],
                ];
            }
            $categorygroups[$catid]['courses'][] = $coursedata;
        }

        // Sort categories by sortorder.
        uasort($categorygroups, function($a, $b) {
            return ($a['sortorder'] ?? 0) <=> ($b['sortorder'] ?? 0);
        });

        $categories = [];
        foreach ($categorygroups as $cg) {
            $cg['count'] = count($cg['courses']);
            $categories[] = $cg;
        }

        $totalcourses = count($validcourses);
        $totalcategories = count($categories);

        $coursestext = self::pluralize_courses($totalcourses);
        $categoriestext = ($totalcategories === 1) ? '1 kategorii' : ($totalcategories . ' kategoriach');
        $summarytext = $coursestext . ' w ' . $categoriestext;

        $filterallshort = get_string('mycourses_filter_all_short', 'theme_ebusola');
        if (empty($filterallshort) || $filterallshort === '[[mycourses_filter_all_short]]') {
            $filterallshort = 'Wszystkie';
        }

        return [
            'has_courses' => true,
            'total_courses' => $totalcourses,
            'total_categories' => $totalcategories,
            'summary_text' => $summarytext,
            'filter_all_short' => $filterallshort,
            'categories' => $categories,
            'homeurl' => (new moodle_url('/'))->out(false),
            'dashboardurl' => (new moodle_url('/my/'))->out(false),
            'allcoursesurl' => (new moodle_url('/my/courses.php'))->out(false),
            'profileurl' => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false),
            'messagesurl' => (new moodle_url('/message/index.php'))->out(false),
            'is_courses_active' => true,
        ];
    }

    /**
     * Build the context for the Course view page (/course/view.php).
     *
     * All sections, activities, completion status, quiz attempts, scores, and glossary
     * are dynamically fetched from Moodle APIs and database (AGENTS.md, ADR 0001).
     *
     * @param object $course
     * @return array|null
     */
    public static function get_course_view_context(object $course): ?array {
        global $USER, $DB;

        if (!$course || $course->id == SITEID) {
            return null;
        }

        // 1. Course category.
        $catmap = self::get_all_categories_map();
        $catid = (int) ($course->category ?? 0);
        $categoryname = $catmap[$catid] ?? '';
        if (empty($categoryname) && !empty($catid)) {
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

        // 2. Modinfo and completion info.
        $modinfo = get_fast_modinfo($course);
        $completion = new \completion_info($course);
        $completionenabled = $completion->is_enabled();

        // 3. User's quiz attempts in this course.
        $quizbestscores = [];
        $lastattempt = null;
        $bestattempt = null;
        $bestpercentage = -1;

        if (isloggedin() && !isguestuser()) {
            try {
                $sql = "
                    SELECT qa.id, qa.quiz, qa.attempt, qa.sumgrades, qa.timefinish,
                           q.name AS quizname, q.sumgrades AS quizsumgrades,
                           cm.id AS cmid, cm.section AS cmsection
                    FROM {quiz_attempts} qa
                    JOIN {quiz} q ON q.id = qa.quiz
                    JOIN {modules} m ON m.name = 'quiz'
                    JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = q.id AND cm.course = q.course
                    WHERE qa.userid = :userid AND q.course = :courseid AND qa.state = 'finished'
                    ORDER BY qa.timefinish DESC
                ";
                $attempts = $DB->get_records_sql($sql, ['userid' => $USER->id, 'courseid' => $course->id]);
                if (!empty($attempts)) {
                    $lastattempt = reset($attempts);
                    foreach ($attempts as $att) {
                        $max = (float) ($att->quizsumgrades ?? 0);
                        $usergrade = (float) ($att->sumgrades ?? 0);
                        $pct = ($max > 0) ? ($usergrade / $max) * 100 : 0;
                        if ($pct > $bestpercentage) {
                            $bestpercentage = $pct;
                            $bestattempt = $att;
                        }

                        $qid = (int) $att->quiz;
                        if (!isset($quizbestscores[$qid]) || $pct > $quizbestscores[$qid]['percent']) {
                            $quizbestscores[$qid] = [
                                'percent' => $pct,
                                'usergrade' => $usergrade,
                                'maxgrade' => $max,
                                'score' => round($usergrade) . '/' . round($max),
                                'passed' => ($pct >= 75),
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                // Silently fall back.
            }
        }

        // Last score & best score labels.
        $hasattempts = ($lastattempt !== null);
        $lastscoretext = $hasattempts ? round((float) $lastattempt->sumgrades) . '/' . round((float) $lastattempt->quizsumgrades) : '-';
        $bestscoretext = ($bestattempt !== null) ? round((float) $bestattempt->sumgrades) . '/' . round((float) $bestattempt->quizsumgrades) : '-';

        // 4. Iterate course modules in section 0 for Glossary or search whole course.
        $glossarycm = null;
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible) {
                continue;
            }
            $modname = $cm->modname;
            $cmname = mb_strtolower(format_string($cm->name));
            if ($modname === 'glossary' || str_contains($cmname, 'słownicz') || str_contains($cmname, 'slownicz') || str_contains($cmname, 'glossary')) {
                $glossarycm = $cm;
                break;
            }
        }

        $glossarycard = null;
        if ($glossarycm) {
            $glossarycard = [
                'has_glossary' => true,
                'title' => format_string($glossarycm->name),
                'desc' => !empty(trim($glossarycm->content ?? '')) ? strip_tags($glossarycm->content) : get_string('course_glossary_desc', 'theme_ebusola'),
                'url' => $glossarycm->url->out(false),
            ];
        }

        // 5. Build section cards and activity items for section >= 1.
        $rawsections = $modinfo->get_section_info_all();
        $sections = [];
        $secindex = 0;

        foreach ($rawsections as $sec) {
            $secnum = (int) $sec->section;
            if ($secnum < 1) {
                continue;
            }
            if (!$sec->uservisible && !$sec->visible) {
                continue;
            }

            $secname = get_section_name($course, $sec);
            $lowsecname = mb_strtolower($secname);

            // Nautical SVG icon assignment.
            $icontype = 'helm';
            if (str_contains($lowsecname, 'ratow') || str_contains($lowsecname, 'bezpiecz')) {
                $icontype = 'rescue';
            } else if (str_contains($lowsecname, 'teor') || str_contains($lowsecname, 'zeglow') || str_contains($lowsecname, 'żeglow')) {
                $icontype = 'theory';
            } else if (str_contains($lowsecname, 'locj') || str_contains($lowsecname, 'nawig')) {
                $icontype = 'pilot';
            } else if (str_contains($lowsecname, 'przepis') || str_contains($lowsecname, 'praw')) {
                $icontype = 'rules';
            } else if (str_contains($lowsecname, 'meteor') || str_contains($lowsecname, 'pogod') || str_contains($lowsecname, 'wiatr')) {
                $icontype = 'weather';
            } else {
                $iconorder = ['helm', 'rescue', 'theory', 'pilot', 'rules', 'weather'];
                $icontype = $iconorder[$secindex % count($iconorder)];
            }

            // Modules in section.
            $activities = [];
            $haspdf = false;
            $hasvideo = false;
            $hasquiz = false;
            $secquizbest = null;
            $sectrackablecount = 0;
            $seccompletedtrackable = 0;

            $modids = $modinfo->sections[$secnum] ?? [];
            foreach ($modids as $cmid) {
                $cm = $modinfo->cms[$cmid] ?? null;
                if (!$cm || !$cm->uservisible) {
                    continue;
                }

                $modname = $cm->modname;
                $cmname = format_string($cm->name);
                $lowname = mb_strtolower($cmname);

                if ($modname === 'quiz') {
                    $hasquiz = true;
                    $acttype = 'quiz';
                    $qbest = $quizbestscores[$cm->instance] ?? null;
                    if ($qbest) {
                        $subtitle = '15 losowych pytań · najlepszy wynik ' . $qbest['score'];
                        if ($secquizbest === null || $qbest['percent'] > $secquizbest['percent']) {
                            $secquizbest = $qbest;
                        }
                    } else {
                        $subtitle = '15 losowych pytań · sprawdź wiedzę z działu';
                    }
                    $btntext = get_string('course_activity_btn_test', 'theme_ebusola');
                } else if ($modname === 'resource' || $modname === 'folder' || str_contains($lowname, 'pdf') || str_contains($lowname, 'prezentac') || str_contains($lowname, 'materiał') || str_contains($lowname, 'material')) {
                    $haspdf = true;
                    $acttype = 'pdf';
                    $subtitle = 'Prezentacja do pobrania lub przeglądania';
                    $btntext = null;
                } else if ($modname === 'page' || $modname === 'url' || $modname === 'h5pactivity' || $modname === 'hvp' || $modname === 'lesson' || str_contains($lowname, 'wykład') || str_contains($lowname, 'wyklad') || str_contains($lowname, 'wideo') || str_contains($lowname, 'film')) {
                    $hasvideo = true;
                    $acttype = 'video';
                    $subtitle = 'Wykład wideo';
                    $btntext = null;
                } else {
                    $acttype = 'other';
                    $subtitle = 'Materiał dydaktyczny';
                    $btntext = null;
                }

                // Completion status.
                if ($completionenabled && $completion->is_enabled($cm)) {
                    $sectrackablecount++;
                    $cdata = $completion->get_data($cm, false, $USER->id);
                    if ($cdata->completionstate == COMPLETION_COMPLETE || $cdata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $seccompletedtrackable++;
                    }
                }

                $activities[] = [
                    'cmid' => $cm->id,
                    'name' => $cmname,
                    'url' => (new \moodle_url('/mod/' . $modname . '/view.php', ['id' => $cm->id]))->out(false),
                    'act_type' => $acttype,
                    'is_quiz' => ($acttype === 'quiz'),
                    'is_pdf' => ($acttype === 'pdf'),
                    'is_video' => ($acttype === 'video'),
                    'is_other' => ($acttype === 'other'),
                    'subtitle' => $subtitle,
                    'btn_text' => $btntext,
                ];
            }

            // Section subtitle.
            $typespresent = [];
            if ($haspdf) {
                $typespresent[] = 'Prezentacja';
            }
            if ($hasvideo) {
                $typespresent[] = 'Wykład';
            }
            if ($hasquiz) {
                $typespresent[] = 'Test';
            }
            if (empty($typespresent)) {
                $secsubtitle = 'Materiały i test';
            } else {
                $secsubtitle = implode(' · ', $typespresent);
            }

            // Section completion check.
            $iscompleted = false;
            if ($secquizbest && $secquizbest['passed']) {
                $iscompleted = true;
            } else if ($sectrackablecount > 0 && $seccompletedtrackable >= $sectrackablecount) {
                $iscompleted = true;
            }

            if ($iscompleted) {
                if ($secquizbest) {
                    $statustext = get_string('course_section_status_best', 'theme_ebusola', $secquizbest['score']);
                    $mobilestatustext = 'Zaliczony · ' . $secquizbest['score'];
                } else {
                    $statustext = get_string('course_section_status_done', 'theme_ebusola');
                    $mobilestatustext = 'Zaliczony';
                }
            } else {
                $statustext = null;
                $mobilestatustext = null;
            }

            $sections[] = [
                'section_num' => $secnum,
                'section_id' => $sec->id,
                'name' => $secname,
                'subtitle' => $secsubtitle,
                'icon_type' => $icontype,
                'is_icon_helm' => ($icontype === 'helm'),
                'is_icon_rescue' => ($icontype === 'rescue'),
                'is_icon_theory' => ($icontype === 'theory'),
                'is_icon_pilot' => ($icontype === 'pilot'),
                'is_icon_rules' => ($icontype === 'rules'),
                'is_icon_weather' => ($icontype === 'weather'),
                'activities' => $activities,
                'has_activities' => !empty($activities),
                'is_completed' => $iscompleted,
                'status_text' => $statustext,
                'mobile_status_text' => $mobilestatustext,
                'close_label' => get_string('course_close_section', 'theme_ebusola', $secname),
            ];

            $secindex++;
        }

        // 6. Next section & todo flags.
        $nextsection = null;
        $foundnext = false;
        foreach ($sections as &$secitem) {
            if (!$secitem['is_completed'] && !$foundnext) {
                $secitem['is_next'] = true;
                $secitem['is_todo'] = false;
                $secitem['mobile_status_text'] = 'Następny';
                $foundnext = true;
                $nextsection = $secitem;
            } else if (!$secitem['is_completed']) {
                $secitem['is_next'] = false;
                $secitem['is_todo'] = true;
                $secitem['mobile_status_text'] = 'Do zrobienia';
            } else {
                $secitem['is_next'] = false;
                $secitem['is_todo'] = false;
            }
        }
        unset($secitem);

        if (!$nextsection && !empty($sections)) {
            $nextsection = reset($sections);
        }

        $nextsectionname = $nextsection ? $nextsection['name'] : $course->fullname;
        $nextsectionnum = $nextsection ? $nextsection['section_num'] : 1;
        $continuelabel = get_string('course_btn_continue', 'theme_ebusola', $nextsectionname);

        // 7. Segments in Hero.
        $totalsections = count($sections);
        $completedsections = count(array_filter($sections, fn($s) => !empty($s['is_completed'])));

        $segments = [];
        for ($i = 1; $i <= $totalsections; $i++) {
            $segments[] = [
                'is_done' => ($i <= $completedsections),
            ];
        }

        $progressfmt = new \stdClass();
        $progressfmt->completed = $completedsections;
        $progressfmt->total = $totalsections;
        $progresslabel = get_string('course_progress_fmt', 'theme_ebusola', $progressfmt);

        return [
            'course_id' => $course->id,
            'fullname' => format_string($course->fullname),
            'shortname' => $course->shortname,
            'category_name' => $categoryname,
            'mycourses_url' => (new \moodle_url('/my/courses.php'))->out(false),
            'total_sections' => $totalsections,
            'completed_sections' => $completedsections,
            'progress_label' => $progresslabel,
            'segments' => $segments,
            'has_scores' => $hasattempts,
            'last_score' => $lastscoretext,
            'last_score_mobile_label' => $hasattempts ? ('Ostatni wynik: ' . $lastscoretext) : '',
            'best_score' => $bestscoretext,
            'next_section_name' => $nextsectionname,
            'next_section_num' => $nextsectionnum,
            'continue_label' => $continuelabel,
            'sections' => $sections,
            'has_sections' => !empty($sections),
            'glossary' => $glossarycard,
            'homeurl' => (new \moodle_url('/'))->out(false),
            'dashboardurl' => (new \moodle_url('/my/'))->out(false),
            'allcoursesurl' => (new \moodle_url('/my/courses.php'))->out(false),
            'profileurl' => (new \moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false),
            'is_courses_active' => true,
        ];
    }

    /**
     * Helper for Polish pluralization forms.
     *
     * @param int $count Number of items.
     * @param string $one Singular nominative (np. "kurs", "dział zaliczony").
     * @param string $few Plural nominative 2-4 (np. "kursy", "działy zaliczone").
     * @param string $many Plural genitive 5+ (np. "kursów", "działów zaliczonych").
     * @return string
     */
    public static function pluralize_pl(int $count, string $one, string $few, string $many): string {
        $abs = abs($count);
        $mod10 = $abs % 10;
        $mod100 = $abs % 100;

        if ($abs === 1) {
            return $one;
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return $few;
        }
        return $many;
    }

    /**
     * Build the context for the Busola User Profile view (/user/profile.php).
     *
     * All user info, enrolled courses, progress bars, test results, and login dates
     * are dynamically fetched according to AGENTS.md architectural rules.
     *
     * @param object|null $targetuser User object (defaults to current $USER).
     * @return array|null
     */
    public static function get_profile_context(?object $targetuser = null): ?array {
        global $USER, $DB, $CFG, $PAGE;

        if (!isloggedin() || isguestuser()) {
            return null;
        }

        $user = $targetuser ?: $USER;
        if (empty($user) || empty($user->id)) {
            $user = $USER;
        }

        // Full name and first name.
        $fullname = fullname($user);
        $firstname = !empty($user->firstname) ? $user->firstname : $fullname;

        // Initials.
        $firstinitial = !empty($user->firstname) ? mb_substr($user->firstname, 0, 1) : 'J';
        $lastinitial = !empty($user->lastname) ? mb_substr($user->lastname, 0, 1) : 'K';
        $initials = mb_strtoupper($firstinitial . $lastinitial);

        // Edit permission check.
        $canedit = false;
        if (is_siteadmin()) {
            $canedit = true;
        } else if ($user->id == $USER->id) {
            $canedit = has_capability('moodle/user:editownprofile', \context_system::instance());
        } else {
            $usercontext = \context_user::instance($user->id, IGNORE_MISSING);
            if ($usercontext) {
                $canedit = has_capability('moodle/user:update', $usercontext);
            }
        }
        $editurl = (new \moodle_url('/user/edit.php', ['id' => $user->id]))->out(false);

        // Avatar.
        $avatarurl = null;
        $hascustomavatar = false;
        if (!empty($user->picture) && (int)$user->picture > 0) {
            $userpicture = new \user_picture($user);
            $userpicture->size = 100;
            $avatarurl = $userpicture->get_url($PAGE)->out(false);
            $hascustomavatar = true;
        }

        // Enrolled courses.
        $enrolledcourses = enrol_get_all_users_courses($user->id, true, 'id, fullname, shortname, category');
        $courseids = [];
        foreach ($enrolledcourses as $c) {
            if ($c->id != SITEID) {
                $courseids[] = (int) $c->id;
            }
        }

        // Section counts per course.
        $sectioncounts = [];
        if (!empty($courseids)) {
            try {
                list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'pscnt');
                $sql = "
                    SELECT course, COUNT(id) AS cnt
                    FROM {course_sections}
                    WHERE course $insql AND section > 0
                    GROUP BY course
                ";
                $counts = $DB->get_records_sql($sql, $inparams);
                foreach ($counts as $cid => $row) {
                    $sectioncounts[(int) $cid] = (int) $row->cnt;
                }
            } catch (\Exception $e) {
                // Silently fall back.
            }
        }

        $validcourses = [];
        $totalcompletedsections = 0;

        foreach ($enrolledcourses as $c) {
            if ($c->id == SITEID) {
                continue;
            }

            $cid = (int) $c->id;
            $coursefullname = format_string($c->fullname);
            $imageinfo = self::resolve_course_image_and_badge($c);
            $categoryname = $imageinfo['categoryname'];
            $shortname = strtoupper(trim($c->shortname ?? ''));
            $cfullnamelower = mb_strtolower($c->fullname ?? '');
            $catnamelower = mb_strtolower($categoryname);

            if (str_contains($catnamelower, 'motor') || (!str_contains($catnamelower, 'żegl') && !str_contains($catnamelower, 'zegl') && ($shortname === 'SM' || $shortname === 'MSM' || str_contains($cfullnamelower, 'motorowod')))) {
                $illustration = 'motor';
                $cardbg = '#DCF0F3';
            } else if (str_contains($catnamelower, 'dodatk') || str_contains($catnamelower, 'radio') || str_contains($shortname, 'SRC') || str_contains($cfullnamelower, 'radio')) {
                $illustration = 'extra';
                $cardbg = '#FFE8D4';
            } else {
                $illustration = 'sail';
                $cardbg = '#E3E8FB';
            }

            // Completion progress.
            $progress = null;
            if (class_exists('\core_completion\progress')) {
                $progress = \core_completion\progress::get_course_progress_percentage($c, $user->id);
            }
            $progressval = !is_null($progress) ? (int) round($progress) : 0;

            $totalsections = $sectioncounts[$cid] ?? 6;
            if ($totalsections <= 0) {
                $totalsections = 6;
            }

            $isnotstarted = ($progressval === 0);
            $iscompleted = ($progressval >= 100);
            $isinprogress = ($progressval > 0 && $progressval < 100);

            // Mockup displays a 6-segment progress indicator.
            $displaysegments = min(6, max(1, $totalsections));
            $completedsegments = (int) round(($progressval / 100) * $displaysegments);
            if ($isinprogress && $completedsegments === 0) {
                $completedsegments = 1;
            }
            if ($isinprogress && $completedsegments === $displaysegments) {
                $completedsegments = max(1, $displaysegments - 1);
            }
            if ($iscompleted) {
                $completedsegments = $displaysegments;
            }

            $completedactual = (int) round(($progressval / 100) * $totalsections);
            if ($isinprogress && $completedactual === 0) {
                $completedactual = 1;
            }
            if ($isinprogress && $completedactual === $totalsections) {
                $completedactual = max(1, $totalsections - 1);
            }
            if ($iscompleted) {
                $completedactual = $totalsections;
            }

            $totalcompletedsections += $completedactual;

            $segments = [];
            for ($s = 1; $s <= $displaysegments; $s++) {
                $segments[] = [
                    'is_done' => ($s <= $completedsegments),
                    'is_completed_course' => $iscompleted,
                ];
            }

            $progresslabel = '';
            if ($isinprogress) {
                $progresslabel = $completedactual . ' z ' . $totalsections;
            }

            $validcourses[] = [
                'id' => $cid,
                'fullname' => $coursefullname,
                'url' => (new \moodle_url('/course/view.php', ['id' => $cid]))->out(false),
                'illustration' => $illustration,
                'is_motor' => ($illustration === 'motor'),
                'is_extra' => ($illustration === 'extra'),
                'is_sail' => ($illustration === 'sail'),
                'card_bg' => $cardbg,
                'is_not_started' => $isnotstarted,
                'is_in_progress' => $isinprogress,
                'is_completed' => $iscompleted,
                'progress_label' => $progresslabel,
                'segments' => $segments,
            ];
        }

        $coursescount = count($validcourses);
        $coursesphrase = self::pluralize_pl($coursescount, 'kurs', 'kursy', 'kursów');
        $sectionsphrase = self::pluralize_pl($totalcompletedsections, 'dział zaliczony', 'działy zaliczone', 'działów zaliczonych');
        $statssummary = "{$coursescount} {$coursesphrase} · {$totalcompletedsections} {$sectionsphrase}";

        // Country name.
        $countryname = '–';
        if (!empty($user->country)) {
            $countries = get_string_manager()->get_list_of_countries();
            $countryname = $countries[$user->country] ?? $user->country;
        }

        // Timezone.
        $timezone = !empty($user->timezone) ? $user->timezone : \core_date::get_user_timezone($user);
        if ($timezone === '99' || empty($timezone)) {
            $timezone = \core_date::get_server_timezone();
        }

        // Polish months map for robust date rendering.
        $plmonths = [
            1 => 'stycznia', 2 => 'lutego', 3 => 'marca', 4 => 'kwietnia',
            5 => 'maja', 6 => 'czerwca', 7 => 'lipca', 8 => 'sierpnia',
            9 => 'września', 10 => 'października', 11 => 'listopada', 12 => 'grudnia'
        ];

        $firstlogintxt = '–';
        if (!empty($user->firstaccess)) {
            $m = (int) date('n', $user->firstaccess);
            $monthname = $plmonths[$m] ?? date('M', $user->firstaccess);
            $firstlogintxt = date('j', $user->firstaccess) . ' ' . $monthname . ' ' . date('Y', $user->firstaccess);
        }

        $lastlogintxt = '–';
        if (!empty($user->lastaccess)) {
            $todaystart = strtotime('today midnight');
            if ($user->lastaccess >= $todaystart) {
                $lastlogintxt = 'Dziś, ' . date('H:i', $user->lastaccess);
            } else if ($user->lastaccess >= ($todaystart - 86400)) {
                $lastlogintxt = 'Wczoraj, ' . date('H:i', $user->lastaccess);
            } else {
                $m = (int) date('n', $user->lastaccess);
                $monthname = $plmonths[$m] ?? date('M', $user->lastaccess);
                $lastlogintxt = date('j', $user->lastaccess) . ' ' . $monthname . ' ' . date('Y', $user->lastaccess) . ', ' . date('H:i', $user->lastaccess);
            }
        }

        // Privacy & policy links.
        $haspolicy = false;
        $policyurl = null;
        if (class_exists('\tool_policy\api') || file_exists($CFG->dirroot . '/admin/tool/policy/index.php')) {
            $haspolicy = true;
            $policyurl = (new \moodle_url('/admin/tool/policy/index.php'))->out(false);
        }
        $hasdataprivacy = false;
        $dataprivacyurl = null;
        if (file_exists($CFG->dirroot . '/admin/tool/dataprivacy/summary.php')) {
            $hasdataprivacy = true;
            $dataprivacyurl = (new \moodle_url('/admin/tool/dataprivacy/summary.php'))->out(false);
        }

        // Recent test attempt.
        $lastattemptinfo = null;
        try {
            $sql = "
                SELECT qa.id, qa.quiz, qa.sumgrades, qa.timefinish,
                       q.name AS quizname, q.sumgrades AS quizsumgrades
                FROM {quiz_attempts} qa
                JOIN {quiz} q ON q.id = qa.quiz
                WHERE qa.userid = :userid AND qa.state = 'finished'
                ORDER BY qa.timefinish DESC
            ";
            $recentattempts = $DB->get_records_sql($sql, ['userid' => $user->id], 0, 1);
            if (!empty($recentattempts)) {
                $ra = reset($recentattempts);
                $quizname = format_string($ra->quizname);
                $grade = (float) ($ra->sumgrades ?? 0);
                $maxgrade = (float) ($ra->quizsumgrades ?? 0);
                $formattedgrade = (floor($grade) == $grade) ? (string)(int)$grade : number_format($grade, 1);
                $formattedmax = (floor($maxgrade) == $maxgrade) ? (string)(int)$maxgrade : number_format($maxgrade, 1);
                $lastattemptinfo = "{$quizname}, {$formattedgrade}/{$formattedmax}";
            }
        } catch (\Exception $e) {
            // Silently fall back.
        }

        $allgradesurl = (new \moodle_url('/grade/report/overview/index.php', ['id' => $user->id]))->out(false);
        $mobileurl = (new \moodle_url('/admin/tool/mobile/index.php'))->out(false);

        return [
            'userid' => $user->id,
            'fullname' => $fullname,
            'firstname' => $firstname,
            'initials' => $initials,
            'avatar_url' => $avatarurl,
            'has_custom_avatar' => $hascustomavatar,
            'can_edit' => $canedit,
            'edit_url' => $editurl,
            'stats_summary' => $statssummary,
            'email' => $user->email,
            'country' => $countryname,
            'city' => !empty($user->city) ? $user->city : '–',
            'timezone' => $timezone,
            'first_login' => $firstlogintxt,
            'last_login' => $lastlogintxt,
            'has_policy' => $haspolicy,
            'policy_url' => $policyurl,
            'has_data_privacy' => $hasdataprivacy,
            'data_privacy_url' => $dataprivacyurl,
            'courses' => $validcourses,
            'has_courses' => !empty($validcourses),
            'last_test' => $lastattemptinfo,
            'has_last_test' => !empty($lastattemptinfo),
            'all_grades_url' => $allgradesurl,
            'mobile_url' => $mobileurl,
        ];
    }

}
