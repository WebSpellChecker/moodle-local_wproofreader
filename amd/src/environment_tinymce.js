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
 * Each TinyMCE editor renders its editable content inside an `<iframe>`, so the
 * bundle's autoSearch in the main document cannot reach it. For every editor
 * we wait for its init event, then attach a WProofreader instance using the
 * iframe element as container; the bundle is responsible for reaching into
 * the iframe document from there.
 *
 * @module     local_wproofreader/environment_tinymce
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const INSTANCE_ATTR = 'data-wsc-instance';
const MAX_ATTEMPTS = 50;
const ATTEMPT_DELAY = 100;

let listening = false;
let attempts = 0;

const markIframe = (iframe) => {
    const body = iframe.contentDocument && iframe.contentDocument.body;
    if (body) {
        body.setAttribute(INSTANCE_ATTR, '1');
    }
};

const hasInstance = (iframe) => {
    const body = iframe.contentDocument && iframe.contentDocument.body;
    return Boolean(body && body.hasAttribute(INSTANCE_ATTR));
};

const attachTo = (editor) => {
    if (!editor || !window.WEBSPELLCHECKER || !window.WEBSPELLCHECKER_CONFIG) {
        return;
    }

    const iframe = editor.iframeElement
        || (editor.getContentAreaContainer && editor.getContentAreaContainer().querySelector('iframe'));

    if (!iframe || hasInstance(iframe)) {
        return;
    }

    try {
        window.WEBSPELLCHECKER.init(Object.assign({}, window.WEBSPELLCHECKER_CONFIG, {
            container: iframe,
        }));
        markIframe(iframe);
    } catch (e) {
        if (window.console && window.console.warn) {
            window.console.warn('WProofreader: failed to attach to TinyMCE editor', e);
        }
    }
};

const hookEditor = (editor) => {
    if (!editor) {
        return;
    }

    if (editor.initialized) {
        attachTo(editor);
        return;
    }

    if (typeof editor.on === 'function') {
        editor.on('init', () => attachTo(editor));
    }
};

const attachToExisting = () => {
    if (!window.tinymce) {
        return false;
    }

    const editors = Array.isArray(window.tinymce.editors)
        ? window.tinymce.editors
        : (window.tinymce.editors ? Array.from(window.tinymce.editors) : []);

    editors.forEach(hookEditor);

    if (!listening && typeof window.tinymce.on === 'function') {
        listening = true;
        window.tinymce.on('AddEditor', (event) => {
            if (event && event.editor) {
                hookEditor(event.editor);
            }
        });
    }

    return true;
};

const poll = () => {
    attempts++;

    if (attachToExisting()) {
        return;
    }

    if (attempts < MAX_ATTEMPTS) {
        setTimeout(poll, ATTEMPT_DELAY);
    }
};

/**
 * Initialize the TinyMCE environment.
 */
export const init = () => {
    poll();
};
