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
 * core/notification.addNotification() always renders into the page-level
 * #user-notifications region. When the editor triggering a warning lives
 * inside an open Moodle modal (e.g. the calendar's "New event" dialog), that
 * region sits behind the modal's backdrop and the notification is never
 * seen. Render inside the open modal's own body in that case instead.
 *
 * Where the warning can be tied to a specific editor field, show it next to
 * that field directly, styled as an informational (orange) note rather than
 * Moodle's own per-field validation error display: core_form/events'
 * notifyFieldValidationFailure() sets .is-invalid/.has-danger, which mform's
 * own client-side validation checks on submit and blocks the form on - not
 * appropriate here, since this is an FYI about proofreading, not a reason to
 * stop the user submitting their content.
 *
 * @module     local_wproofreader/notify
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import {INSTANCE_ATTR, INSTANCE_ATTR_VALUE, ATTACHED_ATTR} from 'local_wproofreader/constants';

const OPEN_MODAL_SELECTOR = '.modal.show';
const FIELD_WARNING_CLASS = 'wsc-field-warning';
// Plain textareas only carry the bundle's own INSTANCE_ATTR; Atto/TinyMCE
// only carry our own ATTACHED_ATTR (see constants.js for why neither alone
// covers all three editor types).
const ATTACHED_INSTANCE_SELECTOR = `[${INSTANCE_ATTR}="${INSTANCE_ATTR_VALUE}"], [${ATTACHED_ATTR}]`;

const findOpenModalBody = () => {
    const modal = document.querySelector(OPEN_MODAL_SELECTOR);
    if (!modal) {
        return null;
    }
    return modal.querySelector('.modal-body') || modal;
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

    if (container.tagName === 'IFRAME' && window.tinymce && typeof window.tinymce.get === 'function') {
        const editor = window.tinymce.get().find((candidate) => candidate && candidate.iframeElement === container);
        if (editor && typeof editor.getElement === 'function') {
            return editor.getElement();
        }
        return null;
    }

    // Atto hides the original textarea and inserts its editable wrapper as a
    // sibling within the same mform field wrapper; a plain textarea is its
    // own only match within that same wrapper.
    const wrapper = container.closest('.felement, .fitem');
    return wrapper ? wrapper.querySelector('textarea') : null;
};

/**
 * Show a warning, inside the open modal if there is one, else page-wide.
 *
 * @param {string} message Warning text to display.
 */
export const showWarning = (message) => {
    if (!message) {
        return;
    }

    const modalBody = findOpenModalBody();
    if (!modalBody) {
        Notification.addNotification({message, type: 'warning'}).then(() => {
            document.querySelector('#user-notifications')?.scrollIntoView({behavior: 'smooth', block: 'center'});
            return;
        });
        return;
    }

    const alert = document.createElement('div');
    alert.className = 'alert alert-warning';
    alert.setAttribute('role', 'alert');
    alert.textContent = message;
    modalBody.prepend(alert);
    alert.scrollIntoView({behavior: 'smooth', block: 'nearest'});
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
        showWarning(message);
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
 * block form submission. Falls back to showWarning() if the container
 * cannot be resolved back to a real mform field.
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
        showWarning(message);
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
