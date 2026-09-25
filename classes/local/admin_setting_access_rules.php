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
     * @param array $data The submitted rules, and the dropdown values of any rule to add.
     * @return string Empty string on success, error message otherwise.
     */
    public function write_setting($data) {
        if (!is_array($data) || $this->is_readonly()) {
            return '';
        }

        if (isset($data['rules'])) {
            $rules = access_rules::decode((string) $data['rules']);

            if ($rules === null) {
                return get_string('rule_unknown_value', 'local_wproofreader');
            }
        } else {
            $rules = access_rules::all();
        }

        $role = $data['role'] ?? '';
        $feature = $data['feature'] ?? '';
        $area = $data['area'] ?? '';
        $added = null;

        if ($role !== '' && $feature !== '' && $area !== '') {
            if (access_rules::is_unreachable((int) $role, (string) $area)) {
                return get_string('rule_unreachable', 'local_wproofreader', (object) [
                    'role' => access_rules::role_options()[(int) $role] ?? $role,
                    'area' => access_rules::area_options()[(string) $area] ?? $area,
                ]);
            }

            $added = access_rules::make($role, $feature, $area);

            if (!$added) {
                return get_string('rule_unknown_value', 'local_wproofreader');
            }
        }

        if ($added) {
            $rules[] = $added;
        }

        return $this->config_write($this->name, json_encode($rules)) ? '' : get_string('errorsetting', 'admin');
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
            'removeDescription' => $this->label_template('rule_remove_label'),
            'missingRole' => get_string('rule_role_missing', 'local_wproofreader'),
            'unreachable' => context_evaluator::unreachable_areas(),
            'editLabel' => get_string('rule_edit', 'local_wproofreader'),
            'editDescription' => $this->label_template('rule_edit_label'),
            'saveLabel' => get_string('rule_save', 'local_wproofreader'),
            'cancelLabel' => get_string('rule_cancel', 'local_wproofreader'),
            'showing' => get_string('rules_showing', 'local_wproofreader', (object) [
                'shown' => sprintf(self::PLACEHOLDER, 'SHOWN'),
                'total' => sprintf(self::PLACEHOLDER, 'TOTAL'),
            ]),
            'warnings' => [
                'repeatsOne' => $this->template('rule_repeats_one'),
                'repeatsMany' => $this->template('rule_repeats_many'),
                'coveredOne' => $this->template('rule_covered_one'),
                'coveredMany' => $this->template('rule_covered_many'),
                'takesoverOne' => $this->template('rule_takesover_one'),
                'takesoverMany' => $this->template('rule_takesover_many'),
            ],
        ]]);

        $builder = html_writer::tag(
            'div',
            $this->dropdown('role', access_rules::role_options())
            . html_writer::tag('span', get_string('rule_is_allowed_to', 'local_wproofreader'))
            . $this->dropdown('feature', access_rules::feature_options())
            . $this->dropdown('area', access_rules::area_options())
            . html_writer::tag('button', get_string('rule_add', 'local_wproofreader'), [
                'type' => 'button',
                'class' => 'btn btn-primary',
                'data-rule-add' => '1',
            ]),
            ['class' => 'local-wproofreader-rule-builder']
        );

        $notice = html_writer::tag('div', '', [
            'class' => 'local-wproofreader-rule-notice alert alert-warning',
            'role' => 'status',
            'data-rule-notice' => '1',
            'hidden' => 'hidden',
        ]);

        $store = html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $this->get_full_name() . '[rules]',
            'value' => json_encode($rules),
            'data-rules-store' => '1',
            'disabled' => $this->is_readonly() ? 'disabled' : null,
        ]);

        $control = html_writer::tag(
            'div',
            $store . $builder . $notice . $this->controls() . $this->listing($rules) . $this->empty_notice($rules),
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

        return isset($data['rules'])
            ? access_rules::decode((string) $data['rules']) ?? access_rules::all()
            : access_rules::all();
    }

    /**
     * The filter and sort controls that sit above the table.
     *
     * They act on the page rather than on the stored rules, so they are hidden
     * until the script that works them is running.
     *
     * @return string
     */
    private function controls(): string {
        $filters = '';

        $parts = [
            'role' => access_rules::role_options(),
            'feature' => access_rules::feature_options(),
            'area' => access_rules::area_options(),
        ];

        $sortable = [];

        foreach ($parts as $part => $options) {
            $sortable[$part] = get_string('rule_' . $part, 'local_wproofreader');

            $filters .= $this->control(
                'filter_' . $part,
                $options,
                get_string('rules_filter_' . $part, 'local_wproofreader'),
                $sortable[$part],
                ['data-rule-filter' => $part]
            );
        }

        $sort = $this->control(
            'sort',
            $sortable,
            get_string('rules_sort_added', 'local_wproofreader'),
            get_string('rules_sort', 'local_wproofreader'),
            ['data-rule-sort' => '1'],
            true
        );

        $filters = html_writer::tag(
            'div',
            html_writer::tag('span', get_string('rules_filter', 'local_wproofreader'), [
                'class' => 'local-wproofreader-rules-label',
            ]) . $filters,
            ['class' => 'local-wproofreader-rules-group']
        );

        $sort = html_writer::tag('div', $sort, ['class' => 'local-wproofreader-rules-group']);

        $count = html_writer::tag('span', '', [
            'class' => 'local-wproofreader-rules-count text-muted',
            'data-rules-count' => '1',
        ]);

        return html_writer::tag('div', $filters . $sort . $count, [
            'class' => 'local-wproofreader-rules-controls',
            'data-rules-controls' => '1',
            'hidden' => 'hidden',
        ]);
    }

    /**
     * One dropdown of the filter and sort row.
     *
     * @param string $key Name to build the field id from.
     * @param array $options Values against their display names.
     * @param string $nothing Label of the option that picks nothing in particular.
     * @param string $label Accessible label.
     * @param array $attributes Extra attributes for the dropdown.
     * @param bool $showlabel Whether the label is shown rather than read out only.
     * @return string
     */
    private function control(
        string $key,
        array $options,
        string $nothing,
        string $label,
        array $attributes,
        bool $showlabel = false
    ): string {
        $id = $this->get_id() . '_' . $key;

        return html_writer::tag('label', $label, [
            'for' => $id,
            'class' => $showlabel ? 'local-wproofreader-rules-label' : 'accesshide',
        ]) . html_writer::select($options, '', '', ['' => $nothing], $attributes + ['id' => $id]);
    }

    /**
     * The shell of the rules table, which the module fills in.
     *
     * @param array[] $rules Rules as stored, to decide whether the table shows at all.
     * @return string
     */
    private function listing(array $rules): string {
        $head = html_writer::tag(
            'tr',
            html_writer::tag('th', get_string('rules_number', 'local_wproofreader'), ['scope' => 'col'])
            . html_writer::tag('th', get_string('rules_rule', 'local_wproofreader'), ['scope' => 'col'])
            . html_writer::tag('th', get_string('rules_actions', 'local_wproofreader'), ['scope' => 'col'])
        );

        return html_writer::tag(
            'table',
            html_writer::tag('thead', $head) . html_writer::tag('tbody', '', ['data-rules-body' => '1']),
            [
                'class' => 'table generaltable local-wproofreader-rules',
                'hidden' => $rules ? null : 'hidden',
            ]
        );
    }

    /**
     * A warning with a marker where its list of rule numbers goes.
     *
     * @param string $identifier String to fetch.
     * @return string
     */
    private function template(string $identifier): string {
        return get_string($identifier, 'local_wproofreader', sprintf(self::PLACEHOLDER, 'RULES'));
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
        ]) . html_writer::tag('p', get_string('rules_nomatch', 'local_wproofreader'), [
            'class' => 'local-wproofreader-rules-empty text-muted',
            'data-rules-nomatch' => '1',
            'hidden' => 'hidden',
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
     * The accessible label of a row button, with a marker where each part goes.
     *
     * @param string $identifier String to fetch.
     * @return string
     */
    private function label_template(string $identifier): string {
        return get_string($identifier, 'local_wproofreader', (object) [
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
            'class' => 'accesshide',
        ]) . $select;
    }
}
