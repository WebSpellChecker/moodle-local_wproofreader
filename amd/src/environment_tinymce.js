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
 * TinyMCE 6 editor integration.
 *
 * @module     local_wproofreader/environment_tinymce
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {notifyField} from 'local_wproofreader/notify';
import {ATTACHED_ATTR} from 'local_wproofreader/constants';

const SELECTOR = 'iframe.tox-edit-area__iframe';

let observer = null;
let hookInstalled = false;
let attachErrorMessage = null;

const findIframes = () => Array.from(document.querySelectorAll(SELECTOR));

const isMarked = (iframe) => iframe.hasAttribute(ATTACHED_ATTR);
const mark = (iframe) => iframe.setAttribute(ATTACHED_ATTR, '1');
const unmark = (iframe) => iframe.removeAttribute(ATTACHED_ATTR);


const findEditor = (iframe) => {
    if (!window.tinymce || typeof window.tinymce.get !== 'function') {
        return null;
    }
    const editors = window.tinymce.get() || [];
    return editors.find((editor) => editor && editor.iframeElement === iframe) || null;
};

const initInstance = (iframe) => {
    const doc = iframe.contentDocument;
    if (!doc || !doc.body) {
        unmark(iframe);
        return;
    }

    try {
        window.WEBSPELLCHECKER.init(Object.assign({}, window.WEBSPELLCHECKER_CONFIG, {
            container: iframe,
        }));
    } catch (e) {
        unmark(iframe);
        if (window.console && window.console.warn) {
            window.console.warn('WProofreader: failed to attach to TinyMCE editor', e);
        }
        notifyField(iframe, attachErrorMessage);
    }
};

/**
 * Decide when to attach to a single iframe: now, or on the editor's init event.
 *
 * @param {HTMLIFrameElement} iframe
 */
const attach = (iframe) => {
    if (!iframe || isMarked(iframe) || !window.WEBSPELLCHECKER || !window.WEBSPELLCHECKER_CONFIG) {
        return;
    }

    // Mark up front so repeated observer fires do not register duplicate hooks;
    // initInstance unmarks again if the editor turns out not to be ready.
    mark(iframe);

    const editor = findEditor(iframe);
    if (editor && !editor.initialized && typeof editor.on === 'function') {
        editor.on('init', () => initInstance(iframe));
        return;
    }

    initInstance(iframe);
};

const scanAndInit = () => {
    findIframes().forEach(attach);
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
 * Initialize the TinyMCE environment.
 *
 * @param {Object} config Page configuration.
 */
export const init = (config) => {
    attachErrorMessage = config && config.editorAttachErrorMessage || null;
    hookBundleReady();
    startObserver();
    scanAndInit();
};
