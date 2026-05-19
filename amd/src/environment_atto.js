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
 * Atto editor integration.
 *
 * Atto renders each editor as `<div class="editor_atto_content" contenteditable="true">`
 * in the main document. Three signals drive attachment so we are resilient to
 * Atto initializing before, during, or after the bundle:
 *
 *   1. A `MutationObserver` watching both `childList` and the `contenteditable`
 *      attribute catches every editor Atto creates while the page is alive.
 *      It is installed before the initial sweep so editors that appear during
 *      that sweep are not missed.
 *   2. An immediate sweep of the existing DOM picks up editors Atto already
 *      finished building before this module ran (the observer only sees
 *      mutations, not initial state).
 *   3. The `webspellcheckerAlreadyLoaded` global the bundle calls when its
 *      own autoSearch pass finishes triggers one more rescan as a safety net.
 *
 * Atto is only present in Moodle 4.5 LTS. From Moodle 5.0 onward the selector
 * matches nothing and the module is a no-op.
 *
 * @module     local_wproofreader/environment_atto
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = [
    '.editor_atto_content[contenteditable="true"]',
    '.editor_atto [contenteditable="true"]',
];
const INSTANCE_ATTR = 'data-wsc-instance';

let observer = null;
let hookInstalled = false;

const findEditors = () => {
    const seen = new Set();
    SELECTORS.forEach((selector) => {
        document.querySelectorAll(selector).forEach((element) => seen.add(element));
    });
    return Array.from(seen);
};

const isInstanceCreated = (element) => element.hasAttribute(INSTANCE_ATTR);

const createInstance = (element) => {
    if (!element || !element.isContentEditable || isInstanceCreated(element) || !window.WEBSPELLCHECKER) {
        return;
    }

    element.setAttribute(INSTANCE_ATTR, '1');

    try {
        window.WEBSPELLCHECKER.init({container: element});
    } catch (e) {
        element.removeAttribute(INSTANCE_ATTR);
        if (window.console && window.console.warn) {
            window.console.warn('WProofreader: failed to attach to Atto editor', e);
        }
    }
};

const scanAndInit = () => {
    findEditors().forEach(createInstance);
};

const startObserver = () => {
    if (observer || typeof MutationObserver === 'undefined') {
        return;
    }

    observer = new MutationObserver(() => {
        scanAndInit();
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['contenteditable'],
    });
};

const hookBundleReady = () => {
    if (hookInstalled) {
        return;
    }
    hookInstalled = true;

    const previous = window.webspellcheckerAlreadyLoaded;
    window.webspellcheckerAlreadyLoaded = function() {
        if (typeof previous === 'function') {
            try {
                previous.apply(this, arguments);
            } catch (e) {
                // Preserve original callback contract on failure.
            }
        }
        scanAndInit();
    };
};

/**
 * Initialize the Atto editor environment.
 */
export const init = () => {
    hookBundleReady();
    startObserver();
    scanAndInit();
};
