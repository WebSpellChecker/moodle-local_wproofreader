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
 * Attaches explicitly instead of relying on the bundle's own autoSearch
 * (disabled for textareas in proofreader_config.js), since autoSearch only
 * scans once at load and misses fields revealed later, such as an mform
 * "Show more" advanced section.
 *
 * @module     local_wproofreader/environment_textarea
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {notifyField, observeUnavailable} from 'local_wproofreader/notify';
import {ATTACHED_ATTR} from 'local_wproofreader/constants';

const SKIP_CLASS = 'wsc-skip-autosearch';
const CODE_FIELD_HINTS = [
    'id$="customcss"',
    'id$="custommenuitems"',
    'name="customcss"',
    'name="s_theme_boost_customcss"',
    'name$="[customcss]"',
];

let observer = null;
let hookInstalled = false;
let attachErrorMessage = null;

const tagSkippableTextareas = () => {
    const selectors = CODE_FIELD_HINTS.map((hint) => `textarea[${hint}]`).join(',');

    document.querySelectorAll(selectors).forEach((textarea) => {
        textarea.classList.add(SKIP_CLASS);
    });
};

const isVisible = (element) => !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);

const isManagedByTinymce = (textarea) => !!textarea.id
    && !!window.tinymce
    && typeof window.tinymce.get === 'function'
    && !!window.tinymce.get(textarea.id);

const findTextareas = () => Array.from(document.querySelectorAll('textarea'))
    .filter((textarea) => !textarea.classList.contains(SKIP_CLASS) && !isManagedByTinymce(textarea));

const createInstance = (textarea) => {
    if (!textarea || textarea.hasAttribute(ATTACHED_ATTR) || !isVisible(textarea) || !window.WEBSPELLCHECKER) {
        return;
    }

    textarea.setAttribute(ATTACHED_ATTR, '1');

    try {
        window.WEBSPELLCHECKER.init({container: textarea});
    } catch (e) {
        textarea.removeAttribute(ATTACHED_ATTR);
        if (window.console && window.console.warn) {
            window.console.warn('WProofreader: failed to attach to textarea', e);
        }
        notifyField(textarea, attachErrorMessage);
    }
};

const scanAndInit = () => {
    findTextareas().forEach(createInstance);
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
        attributeFilter: ['class'],
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
 * Initialize the textarea environment.
 *
 * @param {Object} config Page configuration.
 */
export const init = (config) => {
    attachErrorMessage = config && config.editorAttachErrorMessage || null;
    tagSkippableTextareas();
    hookBundleReady();
    startObserver();
    scanAndInit();
};

/**
 * Show a warning next to every plain textarea on the page, now and for any
 * that appear later, without attaching an instance. Used when the
 * WProofreader bundle itself failed to load, so no instance can ever attach
 * to report per-field problems itself.
 *
 * @param {string} message Warning text to display.
 */
export const notifyUnavailable = (message) => {
    tagSkippableTextareas();
    observeUnavailable(findTextareas, message);
};
