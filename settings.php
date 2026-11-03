<?php
// This file is part of Ranking block for Moodle - http://moodle.org/
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
 * Theme Moove block settings file
 *
 * @package    theme_ebusola
 * @copyright  2017 Willian Mano http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

// This is used for performance, we don't need to know about these settings on every page in Moodle, only when
// we are looking at the admin settings pages.
if ($ADMIN->fulltree) {
    // Boost provides a nice setting page which splits settings onto separate tabs. We want to use it here.
    $settings = new theme_boost_admin_settingspage_tabs('themesettingebusola', get_string('configtitle', 'theme_ebusola'));

    /*
    * ----------------------
    * General settings tab
    * ----------------------
    */
    $page = new admin_settingpage('theme_ebusola_general', get_string('generalsettings', 'theme_ebusola'));

    // Logo file setting.
    $name = 'theme_ebusola/logo';
    $title = get_string('logo', 'theme_ebusola');
    $description = get_string('logodesc', 'theme_ebusola');
    $opts = ['accepted_types' => ['.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'], 'maxfiles' => 1];
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo', 0, $opts);
    $page->add($setting);

    // Favicon setting.
    $name = 'theme_ebusola/favicon';
    $title = get_string('favicon', 'theme_ebusola');
    $description = get_string('favicondesc', 'theme_ebusola');
    $opts = ['accepted_types' => ['.ico'], 'maxfiles' => 1];
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon', 0, $opts);
    $page->add($setting);

    // Preset.
    $name = 'theme_ebusola/preset';
    $title = get_string('preset', 'theme_ebusola');
    $description = get_string('preset_desc', 'theme_ebusola');
    $default = 'plain.scss';

    $context = \core\context\system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_ebusola', 'preset', 0, 'itemid, filepath, filename', false);

    $choices = [];
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }
    // These are the built in presets.
    $choices['default.scss'] = 'default.scss';
    $choices['plain.scss'] = 'plain.scss';

    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Preset files setting.
    $name = 'theme_ebusola/presetfiles';
    $title = get_string('presetfiles', 'theme_ebusola');
    $description = get_string('presetfiles_desc', 'theme_ebusola');

    $setting = new admin_setting_configstoredfile(
        $name,
        $title,
        $description,
        'preset',
        0,
        ['maxfiles' => 10, 'accepted_types' => ['.scss']]
    );
    $page->add($setting);

    // Login page background image.
    $name = 'theme_ebusola/loginbgimg';
    $title = get_string('loginbgimg', 'theme_ebusola');
    $description = get_string('loginbgimg_desc', 'theme_ebusola');
    $opts = ['accepted_types' => ['.png', '.jpg', '.svg']];
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginbgimg', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $brand-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_ebusola/brandcolor';
    $title = get_string('brandcolor', 'theme_ebusola');
    $description = get_string('brandcolor_desc', 'theme_ebusola');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#15195D');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $navbar-header-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_ebusola/secondarymenucolor';
    $title = get_string('secondarymenucolor', 'theme_ebusola');
    $description = get_string('secondarymenucolor_desc', 'theme_ebusola');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#FF751F');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $fontsarr = [
        'Moodle' => 'Moodle Font',
        'Roboto' => 'Roboto',
        'Poppins' => 'Poppins',
        'Montserrat' => 'Montserrat',
        'Open Sans' => 'Open Sans',
        'Lato' => 'Lato',
        'Raleway' => 'Raleway',
        'Inter' => 'Inter',
        'Nunito' => 'Nunito',
        'Encode Sans' => 'Encode Sans',
        'Work Sans' => 'Work Sans',
        'Oxygen' => 'Oxygen',
        'Manrope' => 'Manrope',
        'Sora' => 'Sora',
        'Epilogue' => 'Epilogue',
    ];

    $name = 'theme_ebusola/fontsite';
    $title = get_string('fontsite', 'theme_ebusola');
    $description = get_string('fontsite_desc', 'theme_ebusola');
    $setting = new admin_setting_configselect($name, $title, $description, 'Open Sans', $fontsarr);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'theme_ebusola/enablecourseindex';
    $title = get_string('enablecourseindex', 'theme_ebusola');
    $description = get_string('enablecourseindex_desc', 'theme_ebusola');
    $default = 1;
    $choices = [0 => get_string('no'), 1 => get_string('yes')];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $page->add($setting);

    $name = 'theme_ebusola/enableclassicbreadcrumb';
    $title = get_string('enableclassicbreadcrumb', 'theme_ebusola');
    $description = get_string('enableclassicbreadcrumb_desc', 'theme_ebusola');
    $default = 0;
    $choices = [0 => get_string('no'), 1 => get_string('yes')];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $page->add($setting);

    // Exam info URL setting.
    $name = 'theme_ebusola/examurl';
    $title = get_string('examurl', 'theme_ebusola');
    $description = get_string('examurl_desc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_RAW_TRIMMED);
    $page->add($setting);

    // Must add the page after definiting all the settings!
    $settings->add($page);

    /*
    * ----------------------
    * Advanced settings tab
    * ----------------------
    */
    $page = new admin_settingpage('theme_ebusola_advanced', get_string('advancedsettings', 'theme_ebusola'));

    // Raw SCSS to include before the content.
    $setting = new admin_setting_scsscode(
        'theme_ebusola/scsspre',
        get_string('rawscsspre', 'theme_ebusola'),
        get_string('rawscsspre_desc', 'theme_ebusola'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content.
    $setting = new admin_setting_scsscode(
        'theme_ebusola/scss',
        get_string('rawscss', 'theme_ebusola'),
        get_string('rawscss_desc', 'theme_ebusola'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Google analytics block.
    $name = 'theme_ebusola/googleanalytics';
    $title = get_string('googleanalytics', 'theme_ebusola');
    $description = get_string('googleanalyticsdesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // H5P custom CSS.
    $setting = new admin_setting_configtextarea(
        'theme_ebusola/hvpcss',
        get_string('hvpcss', 'theme_ebusola'),
        get_string('hvpcss_desc', 'theme_ebusola'),
        ''
    );
    $page->add($setting);

    $settings->add($page);

    /*
    * -----------------------
    * Frontpage settings tab
    * -----------------------
    */
    $page = new admin_settingpage('theme_ebusola_frontpage', get_string('frontpagesettings', 'theme_ebusola'));

    // Disable teachers from cards.
    $name = 'theme_ebusola/disableteacherspic';
    $title = get_string('disableteacherspic', 'theme_ebusola');
    $description = get_string('disableteacherspicdesc', 'theme_ebusola');
    $default = 1;
    $choices = [0 => get_string('no'), 1 => get_string('yes')];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $page->add($setting);

    // Slideshow.
    $name = 'theme_ebusola/slideshowlayout';
    $title = get_string('slideshowlayout', 'theme_ebusola');
    $description = get_string('slideshowlayoutdesc', 'theme_ebusola');
    $default = 'classic';
    $options = [
        'classic' => get_string('slideshowlayoutclassic', 'theme_ebusola'),
        'centered' => get_string('slideshowlayoutcentered', 'theme_ebusola'),
        'editorial' => get_string('slideshowlayouteditorial', 'theme_ebusola'),
    ];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $page->add($setting);

    $name = 'theme_ebusola/slidercount';
    $title = get_string('slidercount', 'theme_ebusola');
    $description = get_string('slidercountdesc', 'theme_ebusola');
    $default = 0;
    $options = [];
    for ($i = 0; $i < 13; $i++) {
        $options[$i] = $i;
    }
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $page->add($setting);

    // If we don't have an slide yet, default to the preset.
    $slidercount = get_config('theme_ebusola', 'slidercount');

    if (!$slidercount) {
        $slidercount = $default;
    }

    if ($slidercount) {
        for ($sliderindex = 1; $sliderindex <= $slidercount; $sliderindex++) {
            $fileid = 'sliderimage' . $sliderindex;
            $name = 'theme_ebusola/sliderimage' . $sliderindex;
            $title = get_string('sliderimage', 'theme_ebusola');
            $description = get_string('sliderimagedesc', 'theme_ebusola');
            $opts = ['accepted_types' => ['.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'], 'maxfiles' => 1];
            $setting = new admin_setting_configstoredfile($name, $title, $description, $fileid, 0, $opts);
            $page->add($setting);

            $name = 'theme_ebusola/slidertitle' . $sliderindex;
            $title = get_string('slidertitle', 'theme_ebusola');
            $description = get_string('slidertitledesc', 'theme_ebusola');
            $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
            $page->add($setting);

            $name = 'theme_ebusola/slidercap' . $sliderindex;
            $title = get_string('slidercaption', 'theme_ebusola');
            $description = get_string('slidercaptiondesc', 'theme_ebusola');
            $default = '';
            $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
            $page->add($setting);
        }
    }

    $setting = new admin_setting_heading('slidercountseparator', '', '<hr>');
    $page->add($setting);

    $name = 'theme_ebusola/displaymarketingbox';
    $title = get_string('displaymarketingboxes', 'theme_ebusola');
    $description = get_string('displaymarketingboxesdesc', 'theme_ebusola');
    $default = 1;
    $choices = [0 => get_string('no'), 1 => get_string('yes')];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $page->add($setting);

    $displaymarketingbox = get_config('theme_ebusola', 'displaymarketingbox');

    if ($displaymarketingbox) {
        // Marketingheading.
        $name = 'theme_ebusola/marketingheading';
        $title = get_string('marketingsectionheading', 'theme_ebusola');
        $default = 'Platforma e-learningowa Szkoły Żeglarstwa Busola';
        $setting = new admin_setting_configtext($name, $title, '', $default);
        $page->add($setting);

        // Marketingcontent.
        $name = 'theme_ebusola/marketingcontent';
        $title = get_string('marketingsectioncontent', 'theme_ebusola');
        $default = '<p>Strona w trakcie budowy</p>';
        $setting = new admin_setting_confightmleditor($name, $title, '', $default);
        $page->add($setting);

        for ($i = 1; $i < 5; $i++) {
            $filearea = "marketing{$i}icon";
            $name = "theme_ebusola/$filearea";
            $title = get_string('marketingicon', 'theme_ebusola', $i . '');
            $opts = ['accepted_types' => ['.png', '.jpg', '.gif', '.webp', '.tiff', '.svg']];
            $setting = new admin_setting_configstoredfile($name, $title, '', $filearea, 0, $opts);
            $page->add($setting);

            $name = "theme_ebusola/marketing{$i}heading";
            $title = get_string('marketingheading', 'theme_ebusola', $i . '');
            $default = $i == 1 ? 'Nasz strona www' : 'Lorem';
            $setting = new admin_setting_configtext($name, $title, '', $default);
            $page->add($setting);

            $name = "theme_ebusola/marketing{$i}content";
            $title = get_string('marketingcontent', 'theme_ebusola', $i . '');
            $default = $i == 1 ? '<p><a href="https://busolamazury.pl">Link</a></p>' : 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod.';
            $setting = new admin_setting_confightmleditor($name, $title, '', $default);
            $page->add($setting);
        }

        $setting = new admin_setting_heading('displaymarketingboxseparator', '', '<hr>');
        $page->add($setting);
    }

    // Enable or disable Numbers sections settings.
    $name = 'theme_ebusola/numbersfrontpage';
    $title = get_string('numbersfrontpage', 'theme_ebusola');
    $description = get_string('numbersfrontpagedesc', 'theme_ebusola');
    $default = 1;
    $choices = [0 => get_string('no'), 1 => get_string('yes')];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $page->add($setting);

    $numbersfrontpage = get_config('theme_ebusola', 'numbersfrontpage');

    if ($numbersfrontpage) {
        $name = 'theme_ebusola/numbersfrontpagecontent';
        $title = get_string('numbersfrontpagecontent', 'theme_ebusola');
        $description = get_string('numbersfrontpagecontentdesc', 'theme_ebusola');
        $default = get_string('numbersfrontpagecontentdefault', 'theme_ebusola');
        $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
        $page->add($setting);
    }

    // Enable FAQ.
    $name = 'theme_ebusola/faqcount';
    $title = get_string('faqcount', 'theme_ebusola');
    $description = get_string('faqcountdesc', 'theme_ebusola');
    $default = 0;
    $options = [];
    for ($i = 0; $i < 11; $i++) {
        $options[$i] = $i;
    }
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $page->add($setting);

    $faqcount = get_config('theme_ebusola', 'faqcount');

    if ($faqcount > 0) {
        for ($i = 1; $i <= $faqcount; $i++) {
            $name = "theme_ebusola/faqquestion{$i}";
            $title = get_string('faqquestion', 'theme_ebusola', $i . '');
            $setting = new admin_setting_configtext($name, $title, '', '');
            $page->add($setting);

            $name = "theme_ebusola/faqanswer{$i}";
            $title = get_string('faqanswer', 'theme_ebusola', $i . '');
            $setting = new admin_setting_confightmleditor($name, $title, '', '');
            $page->add($setting);
        }

        $setting = new admin_setting_heading('faqseparator', '', '<hr>');
        $page->add($setting);
    }

    $settings->add($page);

    /*
    * --------------------
    * Footer settings tab
    * --------------------
    */
    $page = new admin_settingpage('theme_ebusola_footer', get_string('footersettings', 'theme_ebusola'));

    // Website.
    $name = 'theme_ebusola/website';
    $title = get_string('website', 'theme_ebusola');
    $description = get_string('websitedesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, 'https://busolamazury.pl');
    $page->add($setting);

    // Mobile.
    $name = 'theme_ebusola/mobile';
    $title = get_string('mobile', 'theme_ebusola');
    $description = get_string('mobiledesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Mail.
    $name = 'theme_ebusola/mail';
    $title = get_string('mail', 'theme_ebusola');
    $description = get_string('maildesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, 'wsparcie@kursybusola.pl');
    $page->add($setting);

    // TikTok url setting.
    $name = 'theme_ebusola/tiktok';
    $title = get_string('tiktok', 'theme_ebusola');
    $description = get_string('tiktokdesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, 'https://www.tiktok.com/@busolamazury');
    $page->add($setting);

    // Facebook url setting.
    $name = 'theme_ebusola/facebook';
    $title = get_string('facebook', 'theme_ebusola');
    $description = get_string('facebookdesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, 'https://www.facebook.com/BusolaSzkolaZeglarstwa');
    $page->add($setting);

    // Twitter url setting.
    $name = 'theme_ebusola/twitter';
    $title = get_string('twitter', 'theme_ebusola');
    $description = get_string('twitterdesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Linkdin url setting.
    $name = 'theme_ebusola/linkedin';
    $title = get_string('linkedin', 'theme_ebusola');
    $description = get_string('linkedindesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Youtube url setting.
    $name = 'theme_ebusola/youtube';
    $title = get_string('youtube', 'theme_ebusola');
    $description = get_string('youtubedesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Instagram url setting.
    $name = 'theme_ebusola/instagram';
    $title = get_string('instagram', 'theme_ebusola');
    $description = get_string('instagramdesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, 'https://www.instagram.com/busolamazury');
    $page->add($setting);

    // Pinterest url setting.
    $name = 'theme_ebusola/pinterest';
    $title = get_string('pinterest', 'theme_ebusola');
    $description = get_string('pinterestdesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Whatsapp url setting.
    $name = 'theme_ebusola/whatsapp';
    $title = get_string('whatsapp', 'theme_ebusola');
    $description = get_string('whatsappdesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    // Enable whatsapp floating button.
    $name = 'theme_ebusola/enablewhatsappbutton';
    $title = get_string('enablewhatsappbutton', 'theme_ebusola');
    $description = get_string('enablewhatsappbutton_desc', 'theme_ebusola');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $page->add($setting);

    // Whatsapp floating button message.
    $name = 'theme_ebusola/whatsappbuttonmessage';
    $title = get_string('whatsappbuttonmessage', 'theme_ebusola');
    $description = get_string('whatsappbuttonmessagedesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
    $page->add($setting);

    // Telegram url setting.
    $name = 'theme_ebusola/telegram';
    $title = get_string('telegram', 'theme_ebusola');
    $description = get_string('telegramdesc', 'theme_ebusola');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $page->add($setting);

    $settings->add($page);

    /*
    * --------------------
    * Footer settings tab
    * --------------------
    */
    $page = new admin_settingpage('theme_ebusola_colourmode', get_string('colourmodesettings', 'theme_ebusola'));

    // Colour modes. Off until a site opts in, because a plugin which has not been checked in dark mode can still
    // draw its pages in light colours.
    $name = 'theme_ebusola/enablecolourmodes';
    $title = get_string('enablecolourmodes', 'theme_ebusola');
    $description = get_string('enablecolourmodes_desc', 'theme_ebusola');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 1);
    $page->add($setting);

    // Light while colour modes are experimental. Following the device would put everyone whose device asks for dark
    // into dark mode the moment a site ticks the setting above, including the guests and users who are not logged in
    // who never get the switcher to change it back. MDL-89379 restores the device default once the components have
    // all been converted.
    $name = 'theme_ebusola/defaultcolourmode';
    $title = get_string('defaultcolourmode', 'theme_ebusola');
    $description = get_string('defaultcolourmode_desc', 'theme_ebusola');
    $choices = [];
    foreach (\theme_ebusola\colour_mode::get_modes() as $mode) {
        $choices[$mode] = get_string('colourmode:' . $mode, 'theme_ebusola');
    }
    $setting = new admin_setting_configselect($name, $title, $description, \theme_ebusola\colour_mode::LIGHT, $choices);
    $page->add($setting);
    // The dependency goes on the tabs page rather than the tab: admin/settings.php reads the dependencies from the
    // top level page, and theme_ebusola_admin_settingspage_tabs::add_tab() copies a tab's settings up but not its
    // dependencies, so one recorded on the tab never reaches the JavaScript.
    $settings->hide_if('theme_ebusola/defaultcolourmode', 'theme_ebusola/enablecolourmodes', 'notchecked');

    // Logo file setting.
    $name = 'theme_ebusola/logodark';
    $title = get_string('logodark', 'theme_ebusola');
    $description = get_string('logodarkdesc', 'theme_ebusola');
    $opts = ['accepted_types' => ['.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'], 'maxfiles' => 1];
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logodark', 0, $opts);
    $page->add($setting);

    $settings->hide_if('theme_ebusola/logodark', 'theme_ebusola/enablecolourmodes', 'notchecked');

    $settings->add($page);
}
