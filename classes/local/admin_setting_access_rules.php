<?php
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

namespace local_wproofreader\local;

use admin_setting;
use html_writer;

/**
 * Builds access rules out of three dropdowns, and lists the rules built so far.
 *
 * The three dropdowns read as the sentence the rule stands for. They hold no
 * value of their own: picking all three and adding puts that rule in the table,
 * and the table travels with the form in a hidden field. Nothing reaches the
 * database until the settings page is saved.
 *
 * Adding and removing are done in the browser. Without JavaScript the buttons
 * submit the page instead, which arrives here as a row to remove or a rule to
 * append, so the control still works, one page load at a time.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_access_rules extends admin_setting {
    /** @var string Marker the sentence template hands to the browser. */
    private const PLACEHOLDER = '@@%s@@';

    /**
     * Build the setting.
     *
     * @param string $name Setting name, as plugin/setting.
     * @param string $visiblename Localised name.
     * @param string $description Localised description.
     */
    public function __construct(string $name, string $visiblename, string $description) {
        parent::__construct($name, $visiblename, $description, null);
    }

    /**
     * The rules stored so far.
     *
     * @return array[]
     */
    public function get_setting() {
        return access_rules::all();
    }

    /**
     * Store the rules the form arrived with, after any removal and addition.
     *
     * @param array $data Submitted rules, dropdown values, and any row to remove.
     * @return string Empty string on success, error message otherwise.
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            return '';
        }

        if (isset($data['rules'])) {
            $rules = $this->submitted_rules($data['rules']);

            if ($rules === null) {
                return get_string('rule_unknown_value', 'local_wproofreader');
            }
        } else {
            $rules = access_rules::all();
        }

        // Without JavaScript a row is removed by submitting its number, which
        // counts from the table as it was rendered, so removal comes first.
        $remove = $data['remove'] ?? '';

        if ($remove !== '' && array_key_exists((int) $remove, $rules)) {
            unset($rules[(int) $remove]);
            $rules = array_values($rules);
        }

        $role = $data['role'] ?? '';
        $feature = $data['feature'] ?? '';
        $area = $data['area'] ?? '';

        if ($role !== '' && $feature !== '' && $area !== '') {
            $rule = access_rules::make($role, $feature, $area);

            if (!$rule) {
                return get_string('rule_unknown_value', 'local_wproofreader');
            }

            $rules[] = $rule;
        }

        return $this->config_write($this->name, json_encode($rules)) ? '' : get_string('errorsetting', 'admin');
    }

    /**
     * The rules the hidden field carried, provided every one of them is real.
     *
     * @param string $submitted JSON as the form posted it.
     * @return array[]|null The rules, or null when the field cannot be trusted.
     */
    private function submitted_rules($submitted): ?array {
        $decoded = json_decode((string) $submitted, true);

        if (!is_array($decoded)) {
            return null;
        }

        $rules = [];

        foreach ($decoded as $rule) {
            if (!is_array($rule)) {
                return null;
            }

            $made = access_rules::make($rule['role'] ?? null, $rule['feature'] ?? null, $rule['area'] ?? null);

            if (!$made) {
                return null;
            }

            $rules[] = $made;
        }

        return $rules;
    }

    /**
     * Render the rule builder, and the rules added so far underneath it.
     *
     * @param array $data Rules stored so far, as returned by get_setting().
     * @param string $query Search query the settings page was filtered by.
     * @return string
     */
    public function output_html($data, $query = '') {
        global $PAGE;

        $rules = $this->rules_to_show($data);

        $PAGE->requires->js_call_amd('local_wproofreader/settings_rules', 'init', [[
            'sentence' => $this->sentence_template(),
            'removeLabel' => get_string('rule_remove', 'local_wproofreader'),
            'removeDescription' => $this->remove_description_template(),
            'missingRole' => get_string('rule_role_missing', 'local_wproofreader'),
        ]]);

        $builder = html_writer::tag(
            'div',
            $this->dropdown('role', access_rules::role_options())
            . html_writer::tag('span', get_string('rule_is_allowed_to', 'local_wproofreader'))
            . $this->dropdown('feature', access_rules::feature_options())
            . $this->dropdown('area', access_rules::area_options())
            . html_writer::tag('button', get_string('rule_add', 'local_wproofreader'), [
                'type' => 'submit',
                'class' => 'btn btn-secondary',
                'data-rule-add' => '1',
            ]),
            ['class' => 'local-wproofreader-rule-builder']
        );

        $store = html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $this->get_full_name() . '[rules]',
            'value' => json_encode($rules),
            'data-rules-store' => '1',
        ]);

        $control = html_writer::tag(
            'div',
            $store . $builder . $this->listing($rules) . $this->empty_notice($rules),
            ['class' => 'local-wproofreader-rules-setting']
        );

        return format_admin_setting(
            $this,
            $this->visiblename,
            $control,
            $this->description,
            false,
            '',
            null,
            $query
        );
    }

    /**
     * The rules to draw the table from.
     *
     * Moodle hands output_html() the submitted data rather than the stored
     * rules when write_setting() reported an error, so that nothing the
     * administrator typed is lost. That arrives in the shape the form posts,
     * which has to be read back before it can be listed.
     *
     * @param mixed $data Stored rules, or the data the form submitted.
     * @return array[]
     */
    private function rules_to_show($data): array {
        if (!is_array($data)) {
            return [];
        }

        if (isset($data['rules'])) {
            return $this->submitted_rules($data['rules']) ?? access_rules::all();
        }

        return $data;
    }

    /**
     * The rules added so far, numbered, each with a button that removes it.
     *
     * The table is rendered even when there is nothing in it, because the
     * browser fills it in as rules are added.
     *
     * @param array[] $rules Rules as stored.
     * @return string
     */
    private function listing(array $rules): string {
        $head = html_writer::tag(
            'tr',
            html_writer::tag('th', get_string('rules_number', 'local_wproofreader'), ['scope' => 'col'])
            . html_writer::tag('th', get_string('rules_rule', 'local_wproofreader'), ['scope' => 'col'])
            . html_writer::tag('th', get_string('rules_actions', 'local_wproofreader'), ['scope' => 'col'])
        );

        $body = '';
        foreach ($rules as $index => $rule) {
            $body .= $this->row($index, access_rules::describe($rule));
        }

        return html_writer::tag(
            'table',
            html_writer::tag('thead', $head) . html_writer::tag('tbody', $body, ['data-rules-body' => '1']),
            [
                'class' => 'table generaltable local-wproofreader-rules',
                'hidden' => $rules ? null : 'hidden',
            ]
        );
    }

    /**
     * One row of the table.
     *
     * @param int $index Position of the rule in the list.
     * @param string $sentence The rule, written out.
     * @return string
     */
    private function row(int $index, string $sentence): string {
        $attributes = [
            'type' => 'submit',
            'class' => 'btn btn-secondary',
            'name' => $this->get_full_name() . '[remove]',
            'value' => $index,
            'data-rule-remove' => $index,
            'aria-label' => get_string('rule_remove_label', 'local_wproofreader', (object) [
                'number' => $index + 1,
                'sentence' => $sentence,
            ]),
        ];

        if ($this->is_readonly()) {
            $attributes['disabled'] = 'disabled';
        }

        return html_writer::tag(
            'tr',
            html_writer::tag('td', $index + 1, ['class' => 'local-wproofreader-rule-number'])
            . html_writer::tag('td', $sentence)
            . html_writer::tag(
                'td',
                html_writer::tag('button', get_string('rule_remove', 'local_wproofreader'), $attributes),
                ['class' => 'local-wproofreader-rule-actions']
            )
        );
    }

    /**
     * The line shown in place of the table while there are no rules.
     *
     * @param array[] $rules Rules as stored.
     * @return string
     */
    private function empty_notice(array $rules): string {
        return html_writer::tag('p', get_string('rules_none', 'local_wproofreader'), [
            'class' => 'local-wproofreader-rules-empty text-muted',
            'data-rules-empty' => '1',
            'hidden' => $rules ? 'hidden' : null,
        ]);
    }

    /**
     * The rule sentence with a marker where each part goes.
     *
     * The browser writes the same sentences as the server does, so it is given
     * the translated wording rather than a word order built into the script.
     *
     * @return string
     */
    private function sentence_template(): string {
        return get_string('rule_sentence', 'local_wproofreader', (object) [
            'role' => sprintf(self::PLACEHOLDER, 'ROLE'),
            'feature' => sprintf(self::PLACEHOLDER, 'FEATURE'),
            'area' => sprintf(self::PLACEHOLDER, 'AREA'),
        ]);
    }

    /**
     * The accessible label of a remove button, with a marker where each part goes.
     *
     * @return string
     */
    private function remove_description_template(): string {
        return get_string('rule_remove_label', 'local_wproofreader', (object) [
            'number' => sprintf(self::PLACEHOLDER, 'NUMBER'),
            'sentence' => sprintf(self::PLACEHOLDER, 'SENTENCE'),
        ]);
    }

    /**
     * One dropdown of the sentence.
     *
     * @param string $part Which part of the rule the dropdown chooses.
     * @param array $options Values against their display names.
     * @return string
     */
    private function dropdown(string $part, array $options): string {
        $id = $this->get_id() . '_' . $part;

        $select = html_writer::select(
            $options,
            $this->get_full_name() . '[' . $part . ']',
            '',
            ['' => get_string('rule_choose_' . $part, 'local_wproofreader')],
            [
                'id' => $id,
                'data-rule-part' => $part,
                'disabled' => $this->is_readonly() ? 'disabled' : null,
            ]
        );

        return html_writer::tag('label', get_string('rule_' . $part, 'local_wproofreader'), [
            'for' => $id,
            'class' => 'sr-only visually-hidden',
        ]) . $select;
    }
}
