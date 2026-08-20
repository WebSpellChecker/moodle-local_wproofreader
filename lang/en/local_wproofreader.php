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
 * Language strings for local_wproofreader.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['bundle_load_error'] = '⚠️ WProofreader service is temporarily unavailable. Reload the page or try again later.';
$string['editor_attach_error'] = '⚠️ WProofreader could not start on this editor. Reload the page or try again later.';
$string['enable_ai_writing_assistant'] = 'AI writing assistant';
$string['enable_ai_writing_assistant_desc'] = 'Offer rephrasing and tone suggestions powered by the AI writing assistant. Requires a paid license.';
$string['enable_autocomplete'] = 'Text autocomplete';
$string['enable_autocomplete_desc'] = 'Suggest word completions as the user types.';
$string['enable_autocorrect'] = 'Autocorrect';
$string['enable_autocorrect_desc'] = 'Automatically correct unambiguous misspellings as the user types.';
$string['enable_grammar'] = 'Grammar';
$string['enable_grammar_desc'] = 'Check grammar in editor content.';
$string['enable_in_admin'] = 'Enable in site administration';
$string['enable_in_admin_desc'] = 'Check content in the Moodle site administration area.';
$string['enable_in_categories'] = 'Enable in course categories';
$string['enable_in_categories_desc'] = 'Check content in course category description editors.';
$string['enable_in_courses'] = 'Enable in courses and activities';
$string['enable_in_courses_desc'] = 'Check content in course pages, forums, assignments, wikis, glossaries, and other activities.';
$string['enable_on_frontend'] = 'Enable on system pages';
$string['enable_on_frontend_desc'] = 'Check content on Moodle pages with system-level context, such as the global calendar, global search, or system tag browsing. The site front page is treated as a course (id 1) and is governed by "Enable in courses and activities" instead. Login pages never load WProofreader regardless of this setting.';
$string['enable_on_quiz'] = 'Enable in quiz attempts';
$string['enable_on_quiz_desc'] = 'Check content while students answer essay questions and while teachers write feedback. Disabled by default to avoid prompting suggestions during assessments.';
$string['enable_on_users'] = 'Enable on user pages';
$string['enable_on_users_desc'] = 'Check content in user profile descriptions, the dashboard, and personal blog entries.';
$string['enable_spelling'] = 'Spelling';
$string['enable_spelling_desc'] = 'Check spelling in editor content.';
$string['enable_style'] = 'Style';
$string['enable_style_desc'] = 'Apply style guide suggestions to editor content.';
$string['ignore_all_caps'] = 'Ignore all caps words';
$string['ignore_all_caps_desc'] = 'Skip words written entirely in uppercase letters (typically acronyms).';
$string['ignore_domain_names'] = 'Ignore domain names';
$string['ignore_domain_names_desc'] = 'Skip strings that look like domain names or URLs.';
$string['ignore_mixed_case'] = 'Ignore words with mixed case';
$string['ignore_mixed_case_desc'] = 'Skip words that mix upper and lower case in unusual patterns, such as identifiers in camel case.';
$string['ignore_with_numbers'] = 'Ignore words with numbers';
$string['ignore_with_numbers_desc'] = 'Skip words that contain one or more digits.';
$string['lang'] = 'Default language';
$string['lang_auto'] = 'Auto';
$string['lang_desc'] = 'The language WProofreader uses by default. Pick "Auto" to let the service detect the language from the content being checked. Users can switch languages from the badge menu while editing.';
$string['lang_load_error'] = 'Could not load the language list from the WebSpellChecker service. Showing a static fallback list.';
$string['pluginname'] = 'WProofreader';
$string['privacy:metadata:no_user_data'] = 'WProofreader does not store any personal data inside Moodle. Editor text is processed by the external WebSpellChecker service; the transfer is declared below.';
$string['privacy:metadata:wproofreader_service'] = 'WProofreader sends text from editor fields to the WebSpellChecker service for spell, grammar, and style checking.';
$string['privacy:metadata:wproofreader_service:content'] = 'The text content the user is currently editing.';
$string['privacy:metadata:wproofreader_service:language'] = 'The language code selected for proofreading.';
$string['privacy:metadata:wproofreader_service:useragent'] = 'Browser user agent string sent with each request to the service.';
$string['privacy:metadata:wproofreader_service:userip'] = 'The IP address from which the browser contacts the WebSpellChecker service.';
$string['runtime_error_bad_request'] = '⚠️ WProofreader sent an invalid request to the service. Please report this issue to the site administrator.';
$string['runtime_error_conflict'] = '⚠️ WProofreader encountered a conflicting request. Please report this issue to the site administrator.';
$string['runtime_error_forbidden'] = '⚠️ WProofreader access was denied by the service. Check your license key and domain settings, or contact the site administrator.';
$string['runtime_error_not_found'] = '⚠️ WProofreader requested a resource that was not found on the service. Please report this issue to the site administrator.';
$string['runtime_error_server'] = '⚠️ WProofreader service encountered an internal error. Try again in a moment.';
$string['runtime_language_unsupported'] = '⚠️ WProofreader: selected language is not supported. Please report this issue to the site administrator.';
$string['runtime_service_unavailable'] = '⚠️ WProofreader service is temporarily unavailable. Try again in a moment.';
$string['service_id'] = 'License key';
$string['service_id_desc'] = 'Paste the license key delivered with your Moodle Marketplace purchase. Leave empty to use the free version. The license key lifts the daily usage limitation, unlocks enhanced grammar checking, the AI writing assistant, access to custom dictionaries and style guide functionality.';
$string['service_id_invalid'] = '⚠️ Provided license key is invalid. Contact WProofreader support for assistance.';
$string['settings_contexts'] = 'Where to enable WProofreader';
$string['settings_contexts_desc'] = 'Choose which Moodle areas should use real-time spell, grammar, and style checking.';
$string['settings_editors'] = 'Editor support';
$string['settings_editors_desc'] = 'WProofreader works with the Atto editor (Moodle 4.1 through 4.5 LTS, removed from core from 5.0 onward), TinyMCE 6, the legacy TinyMCE editor (Moodle 4.1 only, removed from core in later versions), and plain HTML textareas. No extra configuration is needed.';
$string['settings_features'] = 'Proofreading features';
$string['settings_features_desc'] = 'Choose which checks WProofreader runs by default. Users can override these per editor from the badge menu, unless the feature is restricted by your license tier.';
$string['settings_general'] = 'General settings';
$string['settings_general_desc'] = 'Configure how WProofreader connects to the WebSpellChecker service and what content it checks.';
$string['settings_ignore'] = 'Spelling ignore options';
$string['settings_ignore_desc'] = 'Skip certain word patterns during spell checking. These defaults apply site-wide; users can adjust them per editor from the badge menu.';
$string['show_badge_button'] = 'Show badge button';
$string['show_badge_button_desc'] = 'Display the orange WProofreader badge. The badge gives quick access to settings and the proofreading dialog.';
$string['usage_limit_exceeded'] = '⚠️ The WProofreader usage limit for your license key has been exceeded. Contact WProofreader support for assistance.';
$string['wproofreader:use'] = 'Use WProofreader on editor content';
