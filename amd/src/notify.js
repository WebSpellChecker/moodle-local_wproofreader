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
 * Shared warning display for WProofreader runtime notices.
 *
 * Warnings are always shown next to the specific editor field they concern,
 * styled as an informational (orange, bold) note in the same spot as
 * Moodle's own per-field validation error - but not using core_form/events'
 * notifyFieldValidationFailure() itself: that sets .is-invalid/.has-danger,
 * which mform's own client-side validation checks on submit and blocks the
 * form on. This is an FYI about proofreading, not a reason to stop the user
 * submitting their content. There is no page-level or modal-level fallback:
 * a warning that cannot be tied to a specific field is dropped rather than
 * shown anywhere else.
 *
 * @module     local_wproofreader/notify
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {INSTANCE_ATTR, INSTANCE_ATTR_VALUE, ATTACHED_ATTR} from 'local_wproofreader/constants';

const FIELD_WARNING_CLASS = 'wsc-field-warning';
// Plain textareas only carry the bundle's own INSTANCE_ATTR; Atto/TinyMCE
// only carry our own ATTACHED_ATTR (see constants.js for why neither alone
// covers all three editor types).
const ATTACHED_INSTANCE_SELECTOR = `[${INSTANCE_ATTR}="${INSTANCE_ATTR_VALUE}"], [${ATTACHED_ATTR}]`;

/**
 * Find the tinymce.Editor instance (new or legacy) whose iframe is, or
 * contains, the given container - either the outer iframe itself, or its
 * contentDocument.body, which is what a WProofreader instance's own
 * getContainerNode() reports once it finishes attaching inside an iframe
 * (it reassigns its tracked container from the outer iframe we passed in to
 * the inner editable body).
 *
 * @param {HTMLElement} container
 * @returns {Object|null} tinymce.Editor instance, or null if none matches.
 */
const findTinymceEditorFor = (container) => {
    if (!window.tinymce || typeof window.tinymce.get !== 'function') {
        return null;
    }

    const editors = window.tinymce.get() || [];
    return (Array.isArray(editors) ? editors : []).find((candidate) => {
        if (!candidate) {
            return false;
        }
        const iframe = candidate.iframeElement || document.getElementById(`${candidate.id}_ifr`);
        return iframe === container || (iframe && iframe.contentDocument && iframe.contentDocument.body === container);
    }) || null;
};

/**
 * Resolve a WProofreader attach container back to the original mform field
 * it replaced, so the warning can be anchored in the right place: an Atto
 * content div or a TinyMCE iframe both replace an original textarea, while
 * a plain autoSearch-attached textarea is already that field itself.
 *
 * @param {HTMLElement} container The element WEBSPELLCHECKER attached an instance to.
 * @returns {HTMLElement|null}
 */
const findOriginalField = (container) => {
    if (!container) {
        return null;
    }

    if (container.tagName === 'IFRAME' || container.tagName === 'BODY') {
        const editor = findTinymceEditorFor(container);
        return editor && typeof editor.getElement === 'function' ? editor.getElement() : null;
    }

    // Atto hides the original textarea and inserts its editable wrapper as a
    // sibling within the same mform field wrapper; a plain textarea is its
    // own only match within that same wrapper.
    const wrapper = container.closest('.felement, .fitem');
    return wrapper ? wrapper.querySelector('textarea') : null;
};

/**
 * Insert or update the informational warning note beside a field, without
 * touching the field's own validity state.
 *
 * @param {HTMLElement} field The mform field to anchor the note to.
 * @param {string} message Warning text to display.
 */
const showFieldWarning = (field, message) => {
    const wrapper = field.closest('.felement') || field.parentElement;
    if (!wrapper) {
        return;
    }

    let note = wrapper.querySelector(`.${FIELD_WARNING_CLASS}`);
    if (!note) {
        note = document.createElement('div');
        note.className = FIELD_WARNING_CLASS;
        note.setAttribute('role', 'status');
        wrapper.appendChild(note);
    }
    note.textContent = message;
};

/**
 * Show a warning next to the specific editor field that triggered it, as an
 * informational note rather than a form validation error - it must never
 * block form submission. Dropped silently if the container cannot be
 * resolved back to a real mform field.
 *
 * @param {HTMLElement} container The element passed to WEBSPELLCHECKER.init().
 * @param {string} message Warning text to display.
 */
export const notifyField = (container, message) => {
    if (!message) {
        return;
    }

    const field = findOriginalField(container);
    if (!field) {
        return;
    }

    showFieldWarning(field, message);
};

/**
 * Show a warning against every currently attached WProofreader instance on
 * the page. Used for warnings that stem from shared configuration (e.g. an
 * unsupported language), which affects every attached instance equally
 * rather than one specific field.
 *
 * @param {string} message Warning text to display.
 */
export const notifyAllAttachedFields = (message) => {
    if (!message) {
        return;
    }

    document.querySelectorAll(ATTACHED_INSTANCE_SELECTOR).forEach((container) => notifyField(container, message));
};

/**
 * Show a warning beside every container an editor environment finds, now and
 * for any that appear later (e.g. the calendar's AJAX-loaded "New event"
 * dialog, or an mform "Show more" section revealed after the initial scan).
 * Used when the WProofreader bundle failed to load entirely, so there is no
 * instance to attach and no ATTACHED_ATTR marker will ever be set the normal
 * way - this sets it itself, to avoid re-notifying the same container.
 *
 * @param {Function} findContainers Returns the current list of containers an environment recognizes.
 * @param {string} message Warning text to display.
 */
export const observeUnavailable = (findContainers, message) => {
    if (!message) {
        return;
    }

    const scan = () => {
        findContainers().forEach((container) => {
            if (!container.hasAttribute(ATTACHED_ATTR)) {
                container.setAttribute(ATTACHED_ATTR, '1');
                notifyField(container, message);
            }
        });
    };

    scan();

    if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(scan).observe(document.body, {childList: true, subtree: true});
    }
};
