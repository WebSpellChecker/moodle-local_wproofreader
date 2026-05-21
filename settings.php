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

    // General section.
    $settings->add(new admin_setting_heading(
        'local_wproofreader/heading_general',
        get_string('settings_general', 'local_wproofreader'),
        get_string('settings_general_desc', 'local_wproofreader')
    ));

    $settings->add(new admin_setting_configtext(
        'local_wproofreader/customer_id',
        get_string('customer_id', 'local_wproofreader'),
        get_string('customer_id_desc', 'local_wproofreader'),
        '',
        PARAM_TEXT,
        80
    ));

    $languages = \local_wproofreader\local\language_catalog::options();
    $settings->add(new admin_setting_configselect(
        'local_wproofreader/slang',
        get_string('slang', 'local_wproofreader'),
        get_string('slang_desc', 'local_wproofreader'),
        \local_wproofreader\local\language_catalog::AUTO_OPTION,
        $languages
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_wproofreader/show_badge_button',
        get_string('show_badge_button', 'local_wproofreader'),
        get_string('show_badge_button_desc', 'local_wproofreader'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'local_wproofreader/badge_placement',
        get_string('badge_placement', 'local_wproofreader'),
        get_string('badge_placement_desc', 'local_wproofreader'),
        'page_corner',
        [
            'page_corner' => get_string('badge_placement_page_corner', 'local_wproofreader'),
            'per_editor'  => get_string('badge_placement_per_editor', 'local_wproofreader'),
        ]
    ));

    // Proofreading features section.
    $settings->add(new admin_setting_heading(
        'local_wproofreader/heading_features',
        get_string('settings_features', 'local_wproofreader'),
        get_string('settings_features_desc', 'local_wproofreader')
    ));

    $featuretoggles = [
        'enable_spelling'     => 1,
        'enable_style'        => 1,
        'enable_autocorrect'  => 0,
        'enable_autocomplete' => 0,
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

    // Advanced features section (paid-only).
    $settings->add(new admin_setting_heading(
        'local_wproofreader/heading_advanced',
        get_string('settings_advanced', 'local_wproofreader'),
        get_string('settings_advanced_desc', 'local_wproofreader')
    ));

    $advancedtoggles = [
        'enable_grammar'              => 1,
        'enable_ai_writing_assistant' => 1,
    ];

    foreach ($advancedtoggles as $name => $default) {
        $settings->add(new admin_setting_configcheckbox(
            'local_wproofreader/' . $name,
            get_string($name, 'local_wproofreader'),
            get_string($name . '_desc', 'local_wproofreader'),
            $default
        ));
    }

    // Context section.
    $settings->add(new admin_setting_heading(
        'local_wproofreader/heading_contexts',
        get_string('settings_contexts', 'local_wproofreader'),
        get_string('settings_contexts_desc', 'local_wproofreader')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_wproofreader/enable_in_courses',
        get_string('enable_in_courses', 'local_wproofreader'),
        get_string('enable_in_courses_desc', 'local_wproofreader'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_wproofreader/enable_in_categories',
        get_string('enable_in_categories', 'local_wproofreader'),
        get_string('enable_in_categories_desc', 'local_wproofreader'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_wproofreader/enable_on_users',
        get_string('enable_on_users', 'local_wproofreader'),
        get_string('enable_on_users_desc', 'local_wproofreader'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_wproofreader/enable_on_quiz',
        get_string('enable_on_quiz', 'local_wproofreader'),
        get_string('enable_on_quiz_desc', 'local_wproofreader'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_wproofreader/enable_on_frontend',
        get_string('enable_on_frontend', 'local_wproofreader'),
        get_string('enable_on_frontend_desc', 'local_wproofreader'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_wproofreader/enable_in_admin',
        get_string('enable_in_admin', 'local_wproofreader'),
        get_string('enable_in_admin_desc', 'local_wproofreader'),
        0
    ));

    // Editor info.
    $settings->add(new admin_setting_heading(
        'local_wproofreader/heading_editors',
        get_string('settings_editors', 'local_wproofreader'),
        get_string('settings_editors_desc', 'local_wproofreader')
    ));

    $PAGE->requires->js_call_amd('local_wproofreader/settings_page', 'init', [
        \local_wproofreader\local\config_builder::settings_page_config(),
    ]);
}
