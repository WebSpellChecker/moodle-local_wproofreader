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
 * Surfaces the WProofreader bundle's own runtime console.warn calls as
 * visible warnings.
 *
 * The bundle reports some runtime conditions (an unsupported configured
 * language, the service being unreachable mid-session) only via
 * console.warn, with no exception thrown and no subscribable event, so
 * there is no other integration point to hook into.
 *
 * @module     local_wproofreader/bundle_warnings
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {notifyAllAttachedFields} from 'local_wproofreader/notify';

let installed = false;

const MATCHERS = [
    {pattern: /is unsupported or doesn't exist/, key: 'languageUnsupported'},
    {pattern: /language code is not available for your Service ID/, key: 'languageUnsupported'},
    {pattern: /language list is not defined/, key: 'languageUnsupported'},
    {pattern: /WebSpellChecker Service is currently unavailable/, key: 'serviceUnavailable'},
    {pattern: /CORS response parsing error/, key: 'serviceUnavailable'},
];

/**
 * Wrap window.console.warn once to catch the bundle's known runtime warnings.
 *
 * @param {Object} config Page configuration carrying the notification messages.
 */
export const install = (config) => {
    if (installed || !window.console || typeof window.console.warn !== 'function') {
        return;
    }
    installed = true;

    const messages = {
        languageUnsupported: config.runtimeLanguageUnsupportedMessage,
        serviceUnavailable: config.runtimeServiceUnavailableMessage,
    };
    const originalWarn = window.console.warn.bind(window.console);

    window.console.warn = (...args) => {
        originalWarn(...args);

        const text = String(args[0] || '');
        const matcher = MATCHERS.find((candidate) => candidate.pattern.test(text));
        if (!matcher || !messages[matcher.key]) {
            return;
        }

        notifyAllAttachedFields(messages[matcher.key]);
    };
};
