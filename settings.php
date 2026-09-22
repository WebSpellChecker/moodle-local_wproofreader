<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Admin settings for local_wproofreader.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_wproofreader',
        get_string('pluginname', 'local_wproofreader')
    );
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        // General section.
        $settings->add(new admin_setting_heading(
            'local_wproofreader/heading_general',
            get_string('settings_general', 'local_wproofreader'),
            get_string('settings_general_desc', 'local_wproofreader')
        ));

        $settings->add(new admin_setting_configtext(
            'local_wproofreader/service_id',
            get_string('service_id', 'local_wproofreader'),
            get_string('service_id_desc', 'local_wproofreader'),
            '',
            PARAM_TEXT,
            80
        ));

        $languages = \local_wproofreader\local\language_catalog::options();
        $settings->add(new admin_setting_configselect(
            'local_wproofreader/lang',
            get_string('lang', 'local_wproofreader'),
            get_string('lang_desc', 'local_wproofreader'),
            \local_wproofreader\local\language_catalog::AUTO_OPTION,
            $languages
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_wproofreader/show_badge_button',
            get_string('show_badge_button', 'local_wproofreader'),
            get_string('show_badge_button_desc', 'local_wproofreader'),
            1
        ));

        // Proofreading features section.
        $settings->add(new admin_setting_heading(
            'local_wproofreader/heading_features',
            get_string('settings_features', 'local_wproofreader'),
            get_string('settings_features_desc', 'local_wproofreader')
        ));

        $featuretoggles = [
            'enable_spelling'             => 1,
            'enable_grammar'              => 1,
            'enable_style'                => 1,
            'enable_autocorrect'          => 0,
            'enable_autocomplete'         => 0,
            'enable_ai_writing_assistant' => 1,
        ];

        foreach ($featuretoggles as $name => $default) {
            $settings->add(new admin_setting_configcheckbox(
                'local_wproofreader/' . $name,
                get_string($name, 'local_wproofreader'),
                get_string($name . '_desc', 'local_wproofreader'),
                $default
            ));
        }

        // Spelling ignore options section.
        $settings->add(new admin_setting_heading(
            'local_wproofreader/heading_ignore',
            get_string('settings_ignore', 'local_wproofreader'),
            get_string('settings_ignore_desc', 'local_wproofreader')
        ));

        $ignoretoggles = [
            'ignore_all_caps'     => 1,
            'ignore_domain_names' => 1,
            'ignore_mixed_case'   => 1,
            'ignore_with_numbers' => 1,
        ];

        foreach ($ignoretoggles as $name => $default) {
            $settings->add(new admin_setting_configcheckbox(
                'local_wproofreader/' . $name,
                get_string($name, 'local_wproofreader'),
                get_string($name . '_desc', 'local_wproofreader'),
                $default
            ));
        }

        // Availability section.
        $availabilityinfo = (object) [
            'capability' => get_string('wproofreader:use', 'local_wproofreader'),
            'defineroles' => (new moodle_url('/admin/roles/manage.php'))->out(),
        ];

        $settings->add(new admin_setting_heading(
            'local_wproofreader/heading_availability',
            get_string('settings_availability', 'local_wproofreader'),
            ''
        ));

        $settings->add(new \local_wproofreader\local\admin_setting_area_roles(
            'local_wproofreader/area_roles',
            get_string('area_roles', 'local_wproofreader'),
            get_string('area_roles_desc', 'local_wproofreader', $availabilityinfo)
        ));

        // Editor info.
        $settings->add(new \local_wproofreader\local\settings_page_heading(
            'local_wproofreader/heading_editors',
            get_string('settings_editors', 'local_wproofreader'),
            get_string('settings_editors_desc', 'local_wproofreader')
        ));
    }
}
