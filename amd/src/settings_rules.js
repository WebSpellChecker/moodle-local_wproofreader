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
 * Settings-page helper: keeps the access rules in the form while they are being
 * edited, so that adding and removing rules does not submit the page. The rules
 * travel in a hidden field and reach the database when the page is saved.
 *
 * @module     local_wproofreader/settings_rules
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SETTING_SELECTOR = '.local-wproofreader-rules-setting';
const PARTS = ['role', 'feature', 'area'];

/**
 * Read the rules the form arrived with.
 *
 * @param {HTMLInputElement} store Hidden field holding the rules.
 * @returns {Array} The rules, empty when the field cannot be read.
 */
const readRules = (store) => {
    try {
        const rules = JSON.parse(store.value);
        return Array.isArray(rules) ? rules : [];
    } catch (e) {
        return [];
    }
};

/**
 * The display name a dropdown gives one stored value.
 *
 * @param {HTMLSelectElement} select Dropdown the value belongs to.
 * @param {string} value Value as stored.
 * @returns {string} The label, or the raw value when the dropdown has no such option.
 */
const labelOf = (select, value) => {
    const option = Array.from(select.options).find((candidate) => candidate.value === String(value));

    return option ? option.textContent : String(value);
};

export const init = (strings) => {
    const root = document.querySelector(SETTING_SELECTOR);

    if (!root) {
        return;
    }

    const store = root.querySelector('[data-rules-store]');
    const body = root.querySelector('[data-rules-body]');
    const table = root.querySelector('table');
    const notice = root.querySelector('[data-rules-empty]');
    const addButton = root.querySelector('[data-rule-add]');
    const selects = {};

    PARTS.forEach((part) => {
        selects[part] = root.querySelector(`[data-rule-part="${part}"]`);
    });

    if (!store || !body || !table || !notice || !addButton || PARTS.some((part) => !selects[part])) {
        return;
    }

    let rules = readRules(store);

    const roleLabel = (role) => {
        const option = Array.from(selects.role.options).find((candidate) => candidate.value === String(role));

        return option ? option.textContent : strings.missingRole;
    };

    const sentenceOf = (rule) => strings.sentence
        .replace('@@ROLE@@', roleLabel(rule.role))
        .replace('@@FEATURE@@', labelOf(selects.feature, rule.feature))
        .replace('@@AREA@@', labelOf(selects.area, rule.area));

    const rowOf = (rule, index) => {
        const sentence = sentenceOf(rule);
        const row = document.createElement('tr');

        const number = document.createElement('td');
        number.className = 'local-wproofreader-rule-number';
        number.textContent = String(index + 1);

        const text = document.createElement('td');
        text.textContent = sentence;

        const actions = document.createElement('td');
        actions.className = 'local-wproofreader-rule-actions';

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-secondary';
        remove.dataset.ruleRemove = String(index);
        remove.textContent = strings.removeLabel;
        remove.setAttribute('aria-label', strings.removeDescription
            .replace('@@NUMBER@@', String(index + 1))
            .replace('@@SENTENCE@@', sentence));

        actions.appendChild(remove);
        row.append(number, text, actions);

        return row;
    };

    const render = () => {
        store.value = JSON.stringify(rules);
        body.replaceChildren(...rules.map(rowOf));
        table.hidden = rules.length === 0;
        notice.hidden = rules.length > 0;
    };

    // The buttons submit the page when this module does not run, so they give
    // that up only now that it does.
    addButton.type = 'button';

    addButton.addEventListener('click', () => {
        const rule = {};

        for (const part of PARTS) {
            if (selects[part].value === '') {
                selects[part].focus();
                return;
            }

            rule[part] = part === 'role' ? Number(selects[part].value) : selects[part].value;
        }

        rules.push(rule);
        PARTS.forEach((part) => {
            selects[part].value = '';
        });
        render();
    });

    root.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-rule-remove]');

        if (!remove) {
            return;
        }

        event.preventDefault();
        rules.splice(Number(remove.dataset.ruleRemove), 1);
        render();
    });

    render();
};
