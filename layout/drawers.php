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
 * A drawer based layout for the Eskada theme.
 *
 * @package    theme_ebusola
 * @copyright  2025 Willian Mano - willianmanoaraujo@gmail.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));

if (!$hasblocks) {
    $blockdraweropen = false;
}

$themesettings = new \theme_ebusola\util\settings();
if (!$themesettings->enablecourseindex) {
    $courseindex = '';
} else {
    $courseindex = core_course_drawer();
}

if (!$courseindex) {
    $courseindexopen = false;
}

$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $secondary = $PAGE->secondarynav;

    if ($secondary->get_children_key_list()) {
        $tablistnav = $PAGE->has_tablist_secondary_navigation();
        $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
        $secondarynavigation = $moremenu->export_for_template($OUTPUT);
        $extraclasses[] = 'has-secondarynavigation';
    }

    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$coursefullname = ($PAGE->course?->fullname) ? format_string(
    $PAGE->course->fullname,
    true,
    ['context' => context_course::instance($PAGE->course->id), 'escape' => false],
) : '';
$courseurl = $PAGE->course ? new \core\url('/course/view.php', ['id' => $PAGE->course->id]) : null;

$ismycoursespage = (strpos($PAGE->url->get_path(), '/my/courses.php') !== false || $PAGE->pagelayout === 'mycourses');
$issiteindex = ($PAGE->pagetype === 'site-index');
$ismyindex = ($PAGE->pagetype === 'my-index' && !$ismycoursespage);
$isuserprofile = ($PAGE->pagetype === 'user-profile' || strpos($PAGE->url->get_path(), '/user/profile.php') !== false);
$isuseredit = ($PAGE->pagetype === 'user-edit' || $PAGE->pagetype === 'user-editadvanced' || strpos($PAGE->url->get_path(), '/user/edit.php') !== false || strpos($PAGE->url->get_path(), '/user/editadvanced.php') !== false);
$isuserchangepassword = ($PAGE->pagetype === 'login-change_password' || strpos($PAGE->url->get_path(), '/login/change_password.php') !== false);
$iscalendar = ($PAGE->pagetype === 'calendar-view' || strpos($PAGE->url->get_path(), '/calendar/view.php') !== false);

$isbusoladashboard = false;
$isbusolakokpit = false;
$isbusolamycourses = false;
$isbusolaprofile = false;
$busolahub = null;
$busolakokpit = null;
$busolamycourses = null;
$busolaprofile = null;

if ($isuseredit) {
    $extraclasses[] = 'has-busola-form';
    $extraclasses[] = 'has-busola-user-edit';
}
if ($isuserchangepassword) {
    $extraclasses[] = 'has-busola-form';
    $extraclasses[] = 'has-busola-change-password';
}
if ($iscalendar) {
    $extraclasses[] = 'has-busola-calendar';
}

if (isloggedin() && !isguestuser()) {
    if ($issiteindex) {
        $busolahub = \theme_ebusola\util\busola::get_dashboard_context();
        if ($busolahub) {
            $isbusoladashboard = true;
            $extraclasses[] = 'has-busola-dashboard';
        }
    } else if ($ismyindex) {
        $busolakokpit = \theme_ebusola\util\busola::get_kokpit_context();
        if ($busolakokpit) {
            $isbusolakokpit = true;
            $extraclasses[] = 'has-busola-dashboard';
            $extraclasses[] = 'has-busola-kokpit';
        }
    } else if ($ismycoursespage) {
        $busolamycourses = \theme_ebusola\util\busola::get_mycourses_page_context();
        if ($busolamycourses) {
            $isbusolamycourses = true;
            $extraclasses[] = 'has-busola-dashboard';
            $extraclasses[] = 'has-busola-mycourses';
        }
    } else if ($isuserprofile) {
        $targetuserid = optional_param('id', 0, PARAM_INT);
        if (!$targetuserid && $PAGE->context && $PAGE->context->contextlevel == CONTEXT_USER) {
            $targetuserid = (int) $PAGE->context->instanceid;
        }
        if (!$targetuserid) {
            $targetuserid = (int) $USER->id;
        }
        $targetuser = ($targetuserid == (int) $USER->id) ? $USER : $DB->get_record('user', ['id' => $targetuserid]);
        if ($targetuser) {
            $busolaprofile = \theme_ebusola\util\busola::get_profile_context($targetuser);
            if ($busolaprofile) {
                $isbusolaprofile = true;
                $extraclasses[] = 'has-busola-dashboard';
                $extraclasses[] = 'has-busola-profile';
            }
        }
    }
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => \core\context\course::instance(SITEID), "escape" => false]),
    'coursefullname' => $coursefullname,
    'courseurl' => $courseurl ? $courseurl->out(false) : null,
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
    'user_is_editing' => $PAGE->user_is_editing(),
];

$themesettings = new \theme_ebusola\util\settings();

$templatecontext = array_merge($templatecontext, $themesettings->footer());

if ($isbusoladashboard) {
    $templatecontext['is_busola_dashboard'] = true;
    $templatecontext['busola_hub'] = $busolahub;
}

if ($isbusolakokpit) {
    $templatecontext['is_busola_kokpit'] = true;
    $templatecontext['busola_kokpit'] = $busolakokpit;
}

if ($isbusolamycourses) {
    $templatecontext['is_busola_mycourses'] = true;
    $templatecontext['busola_mycourses'] = $busolamycourses;
}

if ($isbusolaprofile) {
    $templatecontext['is_busola_profile'] = true;
    $templatecontext['busola_profile'] = $busolaprofile;
}

if ($isuseredit) {
    $templatecontext['is_busola_user_edit'] = true;
    $templatecontext['busola_user_profile_url'] = (new \core\url('/user/profile.php'))->out(false);
}

if ($isuserchangepassword) {
    $templatecontext['is_busola_change_password'] = true;
    $templatecontext['busola_user_profile_url'] = (new \core\url('/user/profile.php'))->out(false);
}

if ($iscalendar) {
    $templatecontext['is_busola_calendar'] = true;
}

echo $OUTPUT->render_from_template('theme_ebusola/drawers', $templatecontext);
