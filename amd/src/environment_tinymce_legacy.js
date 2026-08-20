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
 * Legacy TinyMCE editor integration (editor_tinymce, TinyMCE 3.5.x, Moodle's
 * "TinyMCE HTML editor (legacy)"). Distinct from environment_tinymce.js,
 * which targets the newer TinyMCE 6 "tiny" editor plugin - the two ship
 * different iframe markup and editor APIs, and both publish themselves as
 * window.tinymce, so editor instances are told apart by feature (an
 * .iframeElement property only the newer editor exposes), not by a global
 * version flag.
 *
 * @module     local_wproofreader/environment_tinymce_legacy
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {notifyField, observeUnavailable} from 'local_wproofreader/notify';
import {ATTACHED_ATTR} from 'local_wproofreader/constants';

let observer = null;
let hookInstalled = false;
let attachErrorMessage = null;

const isMarked = (iframe) => iframe.hasAttribute(ATTACHED_ATTR);
const mark = (iframe) => iframe.setAttribute(ATTACHED_ATTR, '1');
const unmark = (iframe) => iframe.removeAttribute(ATTACHED_ATTR);

const isLegacyEditor = (editor) => !!editor
    && !editor.iframeElement
    && editor.onInit && typeof editor.onInit.add === 'function';

const findLegacyEditors = () => {
    if (!window.tinymce || typeof window.tinymce.get !== 'function') {
        return [];
    }
    const all = window.tinymce.get() || [];
    return (Array.isArray(all) ? all : []).filter(isLegacyEditor);
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
            window.console.warn('WProofreader: failed to attach to legacy TinyMCE editor', e);
        }
        notifyField(iframe, attachErrorMessage);
    }
};

/**
 * Decide when to attach to a single legacy editor instance: now, or once it
 * finishes initializing.
 *
 * @param {Object} editor A tinymce.Editor instance (legacy API).
 */
const attach = (editor) => {
    const iframe = document.getElementById(`${editor.id}_ifr`);
    if (!iframe || isMarked(iframe) || !window.WEBSPELLCHECKER || !window.WEBSPELLCHECKER_CONFIG) {
        return;
    }

    // Mark up front so repeated observer fires do not register duplicate hooks;
    // initInstance unmarks again if the editor turns out not to be ready.
    mark(iframe);

    if (!editor.initialized) {
        editor.onInit.add(() => initInstance(iframe));
        return;
    }

    initInstance(iframe);
};

const scanAndInit = () => {
    findLegacyEditors().forEach(attach);
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
 * Initialize the legacy TinyMCE environment.
 *
 * @param {Object} config Page configuration.
 */
export const init = (config) => {
    attachErrorMessage = config && config.editorAttachErrorMessage || null;
    hookBundleReady();
    startObserver();
    scanAndInit();
};

const findLegacyIframes = () => findLegacyEditors()
    .map((editor) => document.getElementById(`${editor.id}_ifr`))
    .filter(Boolean);

/**
 * Show a warning next to every legacy TinyMCE editor on the page, now and
 * for any that appear later, without attaching an instance. Used when the
 * WProofreader bundle itself failed to load, so no instance can ever attach
 * to report per-field problems itself.
 *
 * @param {string} message Warning text to display.
 */
export const notifyUnavailable = (message) => observeUnavailable(findLegacyIframes, message);
