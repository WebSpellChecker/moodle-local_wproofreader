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
 * edited, so that adding and removing rules does not submit the page, and says
 * which rules repeat or take over which. The rules travel in a hidden field and
 * reach the database when the page is saved.
 *
 * The wording of every sentence comes from the server, and so does the way one
 * rule covers another, which is mirrored here so that the table answers before
 * the page is saved. Keep both in step with access_rules::covers().
 *
 * @module     local_wproofreader/settings_rules
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SETTING_SELECTOR = '.local-wproofreader-rules-setting';
const PARTS = ['role', 'feature', 'area'];
const EVERYONE = 0;
const ANY = '*';

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
 * Whether one rule already grants everything another one grants.
 *
 * @param {object} wide The rule that may be the wider one.
 * @param {object} narrow The rule that may be covered.
 * @returns {boolean}
 */
const covers = (wide, narrow) => (wide.role === EVERYONE || wide.role === narrow.role)
    && (wide.feature === ANY || wide.feature === narrow.feature)
    && (wide.area === ANY || wide.area === narrow.area);

/**
 * How one rule stands against a list of rules, by rule number.
 *
 * @param {object} rule The rule to weigh up.
 * @param {Array} rules The rules to weigh it against.
 * @param {number|null} self Position of the rule in that list, when it is one of them.
 * @returns {object} Rule numbers under duplicates, covered and takesover.
 */
const relations = (rule, rules, self = null) => {
    const found = {duplicates: [], covered: [], takesover: []};

    rules.forEach((other, index) => {
        if (index === self) {
            return;
        }

        const wider = covers(other, rule);
        const narrower = covers(rule, other);

        if (wider && narrower) {
            if (self === null || index < self) {
                found.duplicates.push(index + 1);
            }
        } else if (wider) {
            found.covered.push(index + 1);
        } else if (narrower) {
            found.takesover.push(index + 1);
        }
    });

    return found;
};

export const init = (strings) => {
    const root = document.querySelector(SETTING_SELECTOR);

    if (!root) {
        return;
    }

    const store = root.querySelector('[data-rules-store]');
    const body = root.querySelector('[data-rules-body]');
    const table = root.querySelector('table');
    const notice = root.querySelector('[data-rule-notice]');
    const empty = root.querySelector('[data-rules-empty]');
    const addButton = root.querySelector('[data-rule-add]');
    const controls = root.querySelector('[data-rules-controls]');
    const count = root.querySelector('[data-rules-count]');
    const nomatch = root.querySelector('[data-rules-nomatch]');
    const sortBy = root.querySelector('[data-rule-sort]');
    const selects = {};
    const filters = {};

    PARTS.forEach((part) => {
        selects[part] = root.querySelector(`[data-rule-part="${part}"]`);
        filters[part] = root.querySelector(`[data-rule-filter="${part}"]`);
    });

    const missing = !store || !body || !table || !notice || !empty || !addButton
        || !controls || !count || !nomatch || !sortBy
        || PARTS.some((part) => !selects[part] || !filters[part]);

    if (missing) {
        return;
    }

    let rules = readRules(store);
    let editing = null;

    const connective = root.querySelector('.local-wproofreader-rule-builder > span');

    const labelOf = (part, value) => {
        const option = Array.from(selects[part].options).find((candidate) => candidate.value === String(value));

        if (option) {
            return option.textContent;
        }

        return part === 'role' ? strings.missingRole : String(value);
    };

    const sentenceOf = (rule) => strings.sentence
        .replace('@@ROLE@@', labelOf('role', rule.role))
        .replace('@@FEATURE@@', labelOf('feature', rule.feature))
        .replace('@@AREA@@', labelOf('area', rule.area));

    const phrase = (kind, numbers) => {
        const key = kind + (numbers.length > 1 ? 'Many' : 'One');

        return strings.warnings[key].replace('@@RULES@@', numbers.join(', '));
    };

    const warningOf = (rule, index) => {
        const found = relations(rule, rules, index);

        if (found.duplicates.length) {
            return phrase('repeats', found.duplicates);
        }

        return found.covered.length ? phrase('covered', found.covered) : '';
    };

    const markerOf = (warning) => {
        const marker = document.createElement('span');
        marker.className = 'local-wproofreader-rule-info';
        marker.tabIndex = 0;
        marker.setAttribute('role', 'note');
        marker.setAttribute('aria-label', warning);

        const glyph = document.createElement('span');
        glyph.setAttribute('aria-hidden', 'true');
        glyph.textContent = 'i';

        const infobox = document.createElement('span');
        infobox.className = 'local-wproofreader-rule-infobox';
        infobox.setAttribute('aria-hidden', 'true');
        infobox.textContent = warning;

        marker.append(glyph, infobox);

        return marker;
    };

    /**
     * A copy of one builder dropdown, ready to sit inside a table row.
     *
     * Cloning keeps the options, and their wording, in one place. The name and
     * the id have to go, or the row would post over the builder's own values.
     *
     * @param {string} part Which part of the rule the dropdown chooses.
     * @param {string|number} value Value to select.
     * @returns {HTMLSelectElement}
     */
    const cloneSelect = (part, value) => {
        const select = selects[part].cloneNode(true);

        select.removeAttribute('name');
        select.removeAttribute('id');
        select.removeAttribute('data-rule-part');
        select.dataset.ruleField = part;
        select.setAttribute('aria-label', root.querySelector(`label[for="${selects[part].id}"]`).textContent);
        select.value = String(value);

        return select;
    };

    /**
     * The rule an edit row currently spells out.
     *
     * @param {HTMLElement} row The row being edited.
     * @returns {object|null} The rule, or null while a dropdown sits on its prompt.
     */
    const chosenIn = (row) => {
        if (!row) {
            return null;
        }

        const rule = {};

        for (const part of PARTS) {
            const field = row.querySelector(`[data-rule-field="${part}"]`);

            if (!field || field.value === '') {
                return null;
            }

            rule[part] = part === 'role' ? Number(field.value) : field.value;
        }

        return rule;
    };

    const editRowOf = (rule, index) => {
        const row = document.createElement('tr');
        row.className = 'local-wproofreader-rule-editing';
        row.dataset.ruleEditrow = String(index);

        const number = document.createElement('td');
        number.className = 'local-wproofreader-rule-number';
        number.textContent = String(index + 1);

        const text = document.createElement('td');
        text.className = 'local-wproofreader-rule-edit';
        text.append(
            cloneSelect('role', rule.role),
            connective.cloneNode(true),
            cloneSelect('feature', rule.feature),
            cloneSelect('area', rule.area)
        );

        const warning = document.createElement('div');
        warning.className = 'local-wproofreader-rule-editnotice alert alert-warning';
        warning.dataset.ruleEditnotice = '1';
        warning.setAttribute('role', 'status');
        warning.hidden = true;
        text.appendChild(warning);

        const actions = document.createElement('td');
        actions.className = 'local-wproofreader-rule-actions';

        const save = document.createElement('button');
        save.type = 'button';
        save.className = 'btn btn-sm btn-primary';
        save.dataset.ruleSave = String(index);
        save.textContent = strings.saveLabel;

        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'btn btn-sm btn-outline-secondary';
        cancel.dataset.ruleCancel = String(index);
        cancel.textContent = strings.cancelLabel;

        actions.append(save, cancel);
        row.append(number, text, actions);

        return row;
    };

    const rowOf = (rule, index) => {
        if (index === editing) {
            return editRowOf(rule, index);
        }

        const sentence = sentenceOf(rule);
        const row = document.createElement('tr');

        const number = document.createElement('td');
        number.className = 'local-wproofreader-rule-number';
        number.textContent = String(index + 1);

        const text = document.createElement('td');
        text.textContent = sentence;

        const warning = warningOf(rule, index);

        if (warning) {
            text.appendChild(markerOf(warning));
        }

        const actions = document.createElement('td');
        actions.className = 'local-wproofreader-rule-actions';

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-outline-danger';
        remove.dataset.ruleRemove = String(index);
        remove.textContent = strings.removeLabel;
        remove.setAttribute('aria-label', strings.removeDescription
            .replace('@@NUMBER@@', String(index + 1))
            .replace('@@SENTENCE@@', sentence));

        const edit = document.createElement('button');
        edit.type = 'button';
        edit.className = 'btn btn-sm btn-outline-secondary';
        edit.dataset.ruleEdit = String(index);
        edit.textContent = strings.editLabel;
        edit.setAttribute('aria-label', strings.editDescription
            .replace('@@NUMBER@@', String(index + 1))
            .replace('@@SENTENCE@@', sentence));

        actions.append(edit, remove);
        row.append(number, text, actions);

        return row;
    };

    /**
     * Where a value sits in its dropdown, which is the order to sort it by.
     *
     * @param {string} part Which part of the rule the value belongs to.
     * @param {string|number} value Value as stored.
     * @returns {number}
     */
    const rank = (part, value) => Array.from(selects[part].options)
        .findIndex((option) => option.value === String(value));

    /**
     * The rules to draw, in the order and selection the controls ask for.
     *
     * Each one keeps the position it holds in the stored list, because that
     * position is the number the table shows and the warnings refer to.
     *
     * @returns {Array} Entries of rule and stored position.
     */
    const visible = () => {
        const shown = rules
            .map((rule, index) => ({rule, index}))
            .filter(({rule}) => PARTS.every((part) => filters[part].value === ''
                || String(rule[part]) === filters[part].value));

        if (sortBy.value !== '') {
            shown.sort((one, other) => rank(sortBy.value, one.rule[sortBy.value])
                - rank(sortBy.value, other.rule[sortBy.value]));
        }

        return shown;
    };

    const render = () => {
        const shown = visible();

        store.value = JSON.stringify(rules);
        body.replaceChildren(...shown.map(({rule, index}) => rowOf(rule, index)));

        table.hidden = shown.length === 0;
        empty.hidden = rules.length > 0;
        nomatch.hidden = rules.length === 0 || shown.length > 0;
        controls.hidden = rules.length === 0;
        count.textContent = shown.length === rules.length ? '' : strings.showing
            .replace('@@SHOWN@@', String(shown.length))
            .replace('@@TOTAL@@', String(rules.length));

        body.querySelectorAll('[data-rule-field]').forEach((field) => {
            field.addEventListener('change', preview);
        });
    };

    /**
     * The rule the dropdowns currently spell out, once all three are chosen.
     *
     * @returns {object|null} The rule, or null while the sentence is unfinished.
     */
    const chosen = () => {
        const rule = {};

        for (const part of PARTS) {
            if (selects[part].value === '') {
                return null;
            }

            rule[part] = part === 'role' ? Number(selects[part].value) : selects[part].value;
        }

        return rule;
    };

    /**
     * How one rule stands against the rest, before it is committed.
     *
     * @param {object|null} rule The rule being composed, or null while unfinished.
     * @param {number|null} self Position of the rule being edited, so that it is
     *                           not weighed against the version already stored.
     * @returns {string} What to say, empty when the rule stands on its own.
     */
    const phraseFor = (rule, self) => {
        const found = rule ? relations(rule, rules, self) : null;

        if (!found) {
            return '';
        }

        // A rule that repeats or is already covered takes nothing over that was
        // not taken over already, so only the first of these is worth saying.
        if (found.duplicates.length) {
            return phrase('repeats', found.duplicates);
        }

        if (found.covered.length) {
            return phrase('covered', found.covered);
        }

        return found.takesover.length ? phrase('takesover', found.takesover) : '';
    };

    /**
     * Put the warning where the rule it is about is being written.
     *
     * @param {HTMLElement|null} bar Where to say it, null while there is nowhere to.
     * @param {string} said What to say.
     */
    const say = (bar, said) => {
        if (!bar) {
            return;
        }

        bar.textContent = said;
        bar.hidden = said === '';
    };

    const preview = () => {
        if (editing === null) {
            say(notice, phraseFor(chosen(), null));
            return;
        }

        // The builder is not what is being written while a row is open.
        say(notice, '');

        const row = body.querySelector('[data-rule-editrow]');

        say(row ? row.querySelector('[data-rule-editnotice]') : null, phraseFor(chosenIn(row), editing));
    };

    /**
     * Put an edited rule back in the list, in the place it already held.
     *
     * @returns {boolean} Whether the edit was complete enough to keep.
     */
    const commit = () => {
        const row = body.querySelector('[data-rule-editrow]');
        const rule = row ? chosenIn(row) : null;

        if (!rule) {
            return false;
        }

        rules[editing] = rule;
        editing = null;
        render();
        preview();

        return true;
    };

    // Enter inside an edit row would otherwise reach the page's own save button
    // and leave the edit behind.
    body.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && editing !== null) {
            event.preventDefault();
            commit();
        }
    });

    // The buttons submit the page when this module does not run, so they give
    // that up only now that it does.
    addButton.type = 'button';

    PARTS.forEach((part) => {
        selects[part].addEventListener('change', preview);
        filters[part].addEventListener('change', render);
    });

    sortBy.addEventListener('change', render);

    addButton.addEventListener('click', () => {
        const rule = chosen();

        if (!rule) {
            const unchosen = PARTS.find((part) => selects[part].value === '');

            selects[unchosen].focus();
            return;
        }

        rules.push(rule);
        PARTS.forEach((part) => {
            selects[part].value = '';
        });
        render();
        preview();
    });

    root.addEventListener('click', (event) => {
        const edit = event.target.closest('[data-rule-edit]');

        if (edit) {
            event.preventDefault();
            editing = Number(edit.dataset.ruleEdit);
            render();
            preview();
            body.querySelector('[data-rule-field="role"]').focus();

            return;
        }

        const save = event.target.closest('[data-rule-save]');

        if (save) {
            event.preventDefault();
            commit();

            return;
        }

        const cancel = event.target.closest('[data-rule-cancel]');

        if (cancel) {
            event.preventDefault();
            editing = null;
            render();
            preview();

            return;
        }

        const remove = event.target.closest('[data-rule-remove]');

        if (!remove) {
            return;
        }

        event.preventDefault();
        rules.splice(Number(remove.dataset.ruleRemove), 1);
        render();
        preview();
    });

    render();
    preview();
};
