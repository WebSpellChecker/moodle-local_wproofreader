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

$string['access_rules'] = 'Add a rule';
$string['access_rules_desc'] = 'Complete the sentence, then save the page to add the rule. Rules only grant access, so a user gets a feature wherever at least one rule allows it, and a site with no rules runs no proofreading at all.';
$string['area_admin'] = 'in site administration';
$string['area_categories'] = 'in course categories';
$string['area_courses'] = 'in courses and activities';
$string['area_frontend'] = 'on system pages';
$string['area_quiz'] = 'in quiz attempts';
$string['area_users'] = 'on user pages';
$string['bundle_load_error'] = '⚠️ WProofreader service is temporarily unavailable. Reload the page or try again later.';
$string['editor_attach_error'] = '⚠️ WProofreader could not start on this editor. Reload the page or try again later.';
$string['feature_ai_writing_assistant'] = 'use the AI writing assistant';
$string['feature_autocomplete'] = 'autocomplete text';
$string['feature_autocorrect'] = 'autocorrect text';
$string['feature_grammar'] = 'check grammar';
$string['feature_spelling'] = 'check spelling';
$string['feature_style'] = 'apply style suggestions';
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
$string['rule_add'] = 'Add rule';
$string['rule_area'] = 'Site part';
$string['rule_choose_area'] = 'choose a site part';
$string['rule_choose_feature'] = 'choose a feature';
$string['rule_choose_role'] = 'Choose a role';
$string['rule_everyone'] = 'Everyone';
$string['rule_everything'] = 'do everything';
$string['rule_everywhere'] = 'everywhere';
$string['rule_feature'] = 'Feature';
$string['rule_is_allowed_to'] = 'is allowed to';
$string['rule_remove'] = 'Remove';
$string['rule_remove_label'] = 'Remove rule {$a->number}: {$a->sentence}';
$string['rule_role'] = 'Role';
$string['rule_role_missing'] = 'A role that no longer exists';
$string['rule_sentence'] = '{$a->role} is allowed to {$a->feature} {$a->area}';
$string['rule_unknown_value'] = 'The rule was not added: one of the values picked no longer exists.';
$string['rules_actions'] = 'Actions';
$string['rules_none'] = 'No rules yet. WProofreader stays off everywhere until at least one rule is added.';
$string['rules_number'] = '#';
$string['rules_rule'] = 'Rule';
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
$string['settings_editors'] = 'Editor support';
$string['settings_editors_desc'] = 'WProofreader works with the Atto editor (Moodle 4.1 through 4.5 LTS, removed from core from 5.0 onward), TinyMCE 6, the legacy TinyMCE editor (Moodle 4.1 only, removed from core in later versions), and plain HTML textareas. No extra configuration is needed.';
$string['settings_general'] = 'General settings';
$string['settings_general_desc'] = 'Configure how WProofreader connects to the WebSpellChecker service and what content it checks.';
$string['settings_ignore'] = 'Spelling ignore options';
$string['settings_ignore_desc'] = 'Skip certain word patterns during spell checking. These defaults apply site-wide; users can adjust them per editor from the badge menu.';
$string['settings_rules'] = 'Feature access rules';
$string['settings_rules_desc'] = 'Decide who may use which proofreading feature, and where. Each rule reads as a sentence, and the rules added so far are listed underneath.';
$string['show_badge_button'] = 'Show badge button';
$string['show_badge_button_desc'] = 'Display the orange WProofreader badge. The badge gives quick access to settings and the proofreading dialog.';
$string['usage_limit_exceeded'] = '⚠️ The WProofreader usage limit for your license key has been exceeded. Contact WProofreader support for assistance.';
$string['wproofreader:use'] = 'Use WProofreader on editor content';
