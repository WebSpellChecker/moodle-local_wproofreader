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
 * @module     local_wproofreader/environment_atto
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {notifyField} from 'local_wproofreader/notify';
import {ATTACHED_ATTR} from 'local_wproofreader/constants';

const SELECTORS = [
    '.editor_atto_content[contenteditable="true"]',
    '.editor_atto [contenteditable="true"]',
];

let observer = null;
let hookInstalled = false;
let attachErrorMessage = null;

const findEditors = () => {
    const seen = new Set();
    SELECTORS.forEach((selector) => {
        document.querySelectorAll(selector).forEach((element) => seen.add(element));
    });
    return Array.from(seen);
};

const isInstanceCreated = (element) => element.hasAttribute(ATTACHED_ATTR);

const createInstance = (element) => {
    if (!element || !element.isContentEditable || isInstanceCreated(element) || !window.WEBSPELLCHECKER) {
        return;
    }

    element.setAttribute(ATTACHED_ATTR, '1');

    try {
        window.WEBSPELLCHECKER.init({container: element});
    } catch (e) {
        element.removeAttribute(ATTACHED_ATTR);
        if (window.console && window.console.warn) {
            window.console.warn('WProofreader: failed to attach to Atto editor', e);
        }
        notifyField(element, attachErrorMessage);
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
 *
 * @param {Object} config Page configuration.
 */
export const init = (config) => {
    attachErrorMessage = config && config.editorAttachErrorMessage || null;
    hookBundleReady();
    startObserver();
    scanAndInit();
};
