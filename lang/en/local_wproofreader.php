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

$string['badge_placement'] = 'Badge placement';
$string['badge_placement_desc'] = 'Where to render the WProofreader badge. "Page corner" shows a single floating badge in the bottom-right corner of the page that controls all editors. "Per editor" attaches a separate badge to each editor.';
$string['badge_placement_page_corner'] = 'Page corner';
$string['badge_placement_per_editor'] = 'Per editor';
$string['customer_id'] = 'License key';
$string['customer_id_desc'] = 'Paste the license key delivered with your Moodle Marketplace purchase. Leave empty to use the free trial. The license key unlocks grammar checking and the AI writing assistant.';
$string['enable_ai_writing_assistant'] = 'AI writing assistant';
$string['enable_ai_writing_assistant_desc'] = 'Offer rephrasing and tone suggestions powered by the AI writing assistant. Requires a paid license; ignored on the free trial.';
$string['enable_autocomplete'] = 'Text autocomplete';
$string['enable_autocomplete_desc'] = 'Suggest word completions as the user types.';
$string['enable_autocorrect'] = 'Autocorrect';
$string['enable_autocorrect_desc'] = 'Automatically correct unambiguous misspellings as the user types.';
$string['enable_grammar'] = 'Grammar';
$string['enable_grammar_desc'] = 'Check grammar in editor content. Requires a paid license; ignored on the free trial.';
$string['enable_in_admin'] = 'Enable in site administration';
$string['enable_in_admin_desc'] = 'Check content in the Moodle site administration area.';
$string['enable_in_categories'] = 'Enable in course categories';
$string['enable_in_categories_desc'] = 'Check content in course category description editors.';
$string['enable_in_courses'] = 'Enable in courses and activities';
$string['enable_in_courses_desc'] = 'Check content in course pages, forums, assignments, wikis, glossaries, and other activities.';
$string['enable_on_frontend'] = 'Enable on system pages';
$string['enable_on_frontend_desc'] = 'Check content on Moodle pages with system-level context, such as the global calendar, global search, or system tag browsing. The site front page is treated as a course (id 1) and is governed by "Enable in courses and activities" instead. Login pages never load WProofreader regardless of this setting.';
$string['enable_on_quiz'] = 'Enable in quiz attempts';
$string['enable_on_quiz_desc'] = 'Check content while students answer essay questions and while teachers write feedback.';
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
$string['pluginname'] = 'WProofreader';
$string['privacy:metadata:no_user_data'] = 'WProofreader does not store any personal data inside Moodle. Editor text is processed by the external WebSpellChecker service; the transfer is declared below.';
$string['privacy:metadata:wproofreader_service'] = 'WProofreader sends text from editor fields to the WebSpellChecker service for spell, grammar, and style checking.';
$string['privacy:metadata:wproofreader_service:content'] = 'The text content the user is currently editing.';
$string['privacy:metadata:wproofreader_service:language'] = 'The language code selected for proofreading.';
$string['privacy:metadata:wproofreader_service:useragent'] = 'Browser user agent string sent with each request to the service.';
$string['privacy:metadata:wproofreader_service:userip'] = 'The IP address from which the browser contacts the WebSpellChecker service.';
$string['settings_advanced'] = 'Advanced features';
$string['settings_advanced_desc'] = 'Premium features that require a paid license. These are ignored on the free trial.';
$string['settings_contexts'] = 'Where to enable WProofreader';
$string['settings_contexts_desc'] = 'Choose which Moodle areas should use real-time spell, grammar, and style checking.';
$string['settings_editors'] = 'Editor support';
$string['settings_editors_desc'] = 'WProofreader works with the Atto editor (Moodle 4.5 only), TinyMCE 6, and plain HTML textareas. No extra configuration is needed.';
$string['settings_features'] = 'Proofreading features';
$string['settings_features_desc'] = 'Choose which checks WProofreader runs by default. Users can override these per editor from the badge menu, unless the feature is restricted by your license tier.';
$string['settings_general'] = 'General settings';
$string['settings_general_desc'] = 'Configure how WProofreader connects to the WebSpellChecker service and what content it checks.';
$string['settings_ignore'] = 'Spelling ignore options';
$string['settings_ignore_desc'] = 'Skip certain word patterns during spell checking. These defaults apply site-wide; users can adjust them per editor from the badge menu.';
$string['show_badge_button'] = 'Show badge button';
$string['show_badge_button_desc'] = 'Display the orange WProofreader badge. The badge gives quick access to settings and the proofreading dialog. Use the "Badge placement" setting below to control where it appears.';
$string['slang'] = 'Default language';
$string['slang_desc'] = 'The language WProofreader uses by default. Users can switch languages from the badge menu while editing.';
$string['slang_load_error'] = 'Could not load the language list from the WebSpellChecker service. Showing a static fallback list.';
$string['wproofreader:use'] = 'Use WProofreader on editor content';
