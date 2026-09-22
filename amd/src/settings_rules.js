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
    const selects = {};

    PARTS.forEach((part) => {
        selects[part] = root.querySelector(`[data-rule-part="${part}"]`);
    });

    const missing = !store || !body || !table || !notice || !empty || !addButton
        || PARTS.some((part) => !selects[part]);

    if (missing) {
        return;
    }

    let rules = readRules(store);

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
        marker.setAttribute('aria-label', strings.warningLabel);

        const glyph = document.createElement('span');
        glyph.setAttribute('aria-hidden', 'true');
        glyph.textContent = 'i';

        const infobox = document.createElement('span');
        infobox.className = 'local-wproofreader-rule-infobox';
        infobox.textContent = warning;

        marker.append(glyph, infobox);

        return marker;
    };

    const rowOf = (rule, index) => {
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
        empty.hidden = rules.length > 0;
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

    const preview = () => {
        const rule = chosen();
        const found = rule ? relations(rule, rules) : null;

        // A rule that repeats or is already covered takes nothing over that was
        // not taken over already, so only the first of these is worth saying.
        let said = '';

        if (found && found.duplicates.length) {
            said = phrase('repeats', found.duplicates);
        } else if (found && found.covered.length) {
            said = phrase('covered', found.covered);
        } else if (found && found.takesover.length) {
            said = phrase('takesover', found.takesover);
        }

        notice.textContent = said;
        notice.hidden = said === '';
    };

    // The buttons submit the page when this module does not run, so they give
    // that up only now that it does.
    addButton.type = 'button';

    PARTS.forEach((part) => {
        selects[part].addEventListener('change', preview);
    });

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
