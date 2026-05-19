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
 * Shared loader for the WebSpellChecker bundle script.
 *
 * Multiple AMD modules (page init and the settings page helper) may need the
 * bundle. This module memoizes the load so only one network request is made.
 *
 * @module     local_wproofreader/bundle_loader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

let bundlePromise = null;

/**
 * Load the WebSpellChecker bundle if it is not already present.
 *
 * @param {string} url Bundle URL.
 * @returns {Promise<void>}
 */
export const loadBundle = (url) => {
    if (bundlePromise) {
        return bundlePromise;
    }

    bundlePromise = new Promise((resolve) => {
        if (window.WEBSPELLCHECKER) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = url;
        script.async = false;
        script.charset = 'UTF-8';
        script.onload = () => resolve();
        script.onerror = () => {
            if (window.console && window.console.warn) {
                window.console.warn('WProofreader: failed to load bundle from', url);
            }
            resolve();
        };
        (document.head || document.documentElement).appendChild(script);
    });

    return bundlePromise;
};
