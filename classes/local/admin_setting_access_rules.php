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
 * Builds one access rule out of three dropdowns, and stores the rules added so far.
 *
 * The three dropdowns read as the sentence the rule stands for. They hold no
 * value of their own: picking all three and saving the page appends that rule
 * to the stored list, after which the dropdowns come back empty. This is why
 * they start on a prompt rather than on a real value, since otherwise saving
 * the settings page for any other reason would add a rule nobody asked for.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_access_rules extends admin_setting {
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
     * Append the submitted rule, when one was fully chosen.
     *
     * @param array $data Submitted dropdown values.
     * @return string Empty string on success, error message otherwise.
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            return '';
        }

        $rules = access_rules::all();

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
     * Render the rule builder.
     *
     * @param array $data Rules stored so far, as returned by get_setting().
     * @param string $query Search query the settings page was filtered by.
     * @return string
     */
    public function output_html($data, $query = '') {
        $builder = html_writer::tag(
            'div',
            $this->dropdown('role', access_rules::role_options())
            . html_writer::tag('span', get_string('rule_is_allowed_to', 'local_wproofreader'))
            . $this->dropdown('feature', access_rules::feature_options())
            . $this->dropdown('area', access_rules::area_options())
            . html_writer::tag('button', get_string('rule_add', 'local_wproofreader'), [
                'type' => 'submit',
                'class' => 'btn btn-secondary',
            ]),
            ['class' => 'local-wproofreader-rule-builder']
        );

        return format_admin_setting($this, $this->visiblename, $builder, $this->description, false, '', null, $query);
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
            ['id' => $id, 'disabled' => $this->is_readonly() ? 'disabled' : null]
        );

        return html_writer::tag('label', get_string('rule_' . $part, 'local_wproofreader'), [
            'for' => $id,
            'class' => 'sr-only visually-hidden',
        ]) . $select;
    }
}
