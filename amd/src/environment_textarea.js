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
 * Plain HTML textarea integration.
 *
 * Moodle exposes textareas in many places where neither Atto nor TinyMCE is
 * active: filter settings forms, the file picker, the "plain text area" editor
 * choice, and so on. The bundle's autoSearch already picks these up; this
 * module only filters out textareas where proofreading would be inappropriate
 * (raw HTML, CSS, JavaScript snippets) by tagging them with a class the
 * disableAutoSearchIn list looks for.
 *
 * @module     local_wproofreader/environment_textarea
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SKIP_CLASS = 'wsc-skip-autosearch';
const CODE_FIELD_HINTS = [
    'id$="customcss"',
    'id$="custommenuitems"',
    'name="customcss"',
    'name="s_theme_boost_customcss"',
    'name$="[customcss]"',
];

const tagSkippableTextareas = () => {
    const selectors = CODE_FIELD_HINTS.map((hint) => `textarea[${hint}]`).join(',');

    document.querySelectorAll(selectors).forEach((textarea) => {
        textarea.classList.add(SKIP_CLASS);
    });
};

/**
 * Initialize the textarea environment.
 *
 * @param {Object} config Page configuration (mutated to extend disableAutoSearchIn).
 */
export const init = (config) => {
    tagSkippableTextareas();

    if (window.WEBSPELLCHECKER_CONFIG && Array.isArray(window.WEBSPELLCHECKER_CONFIG.disableAutoSearchIn)) {
        const skipSelector = `.${SKIP_CLASS}`;
        if (!window.WEBSPELLCHECKER_CONFIG.disableAutoSearchIn.includes(skipSelector)) {
            window.WEBSPELLCHECKER_CONFIG.disableAutoSearchIn.push(skipSelector);
        }
    }

    // Keep the parameter signature consistent for the init dispatcher even though
    // we do not currently use the rest of the config here.
    void config;
};
