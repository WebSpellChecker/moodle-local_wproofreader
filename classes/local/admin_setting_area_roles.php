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
 * Matrix of areas down the side and roles across the top, one checkbox per cell.
 *
 * A ticked cell means users holding that role keep WProofreader in that area.
 * This is the plugin's only availability control, so unticking a whole row is
 * what switches an area off for everyone.
 *
 * Cells that could never take effect are drawn as not applicable instead of as
 * a checkbox, so nobody ticks a box that does nothing. Which cells those are
 * comes from context_evaluator::applicable_roleids().
 *
 * The value is stored as JSON of area name to the role ids that were unticked,
 * so an empty value, a missing area and a role added to the site later all
 * mean enabled. Storing the unticked side keeps the plugin's "available unless
 * restricted" default as roles come and go. A row with every checkbox cleared
 * is stored as context_evaluator::AREA_OFF instead of a list, so the area stays
 * off for roles created after the save rather than quietly reopening for them.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_area_roles extends admin_setting {
    /**
     * Build the setting.
     *
     * The default is null so that a fresh install stores nothing and every role
     * starts out enabled in every area.
     *
     * @param string $name Setting name, as plugin/setting.
     * @param string $visiblename Localised name.
     * @param string $description Localised description.
     */
    public function __construct(string $name, string $visiblename, string $description) {
        parent::__construct($name, $visiblename, $description, null);
    }

    /**
     * Site roles to offer as columns, as role id => display name.
     *
     * @return array
     */
    private function roles(): array {
        return role_fix_names(
            get_all_roles(),
            \context_system::instance(),
            ROLENAME_ORIGINAL,
            true
        );
    }

    /**
     * Ticked state of every cell, as area => [role id => 1 or 0].
     *
     * @return array
     */
    public function get_setting() {
        $stored = json_decode((string) $this->config_read($this->name), true);

        if (!is_array($stored)) {
            $stored = [];
        }

        $setting = [];

        foreach (context_evaluator::AREAS as $area) {
            $areaoff = ($stored[$area] ?? null) === context_evaluator::AREA_OFF;
            $withheld = $areaoff ? [] : array_map('intval', (array) ($stored[$area] ?? []));

            foreach (context_evaluator::applicable_roleids($area) as $roleid) {
                $ticked = !$areaoff && !in_array($roleid, $withheld, true);
                $setting[$area][$roleid] = $ticked ? 1 : 0;
            }
        }

        return $setting;
    }

    /**
     * Store the unticked cells of every area.
     *
     * @param array $data Submitted grid, as area => [role id => 1] for ticked cells only.
     * @return string Empty string on success, error message otherwise.
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            return '';
        }

        $current = json_decode((string) $this->config_read($this->name), true);
        $stored = [];

        foreach (context_evaluator::AREAS as $area) {
            $applicable = context_evaluator::applicable_roleids($area);

            if (!$applicable) {
                // Nothing to act on, so keep whatever the area already had.
                if (isset($current[$area])) {
                    $stored[$area] = $current[$area];
                }
                continue;
            }

            $ticked = is_array($data[$area] ?? null) ? $data[$area] : [];
            $withheld = [];

            foreach ($applicable as $roleid) {
                if (empty($ticked[$roleid])) {
                    $withheld[] = $roleid;
                }
            }

            if (count($withheld) === count($applicable)) {
                $stored[$area] = context_evaluator::AREA_OFF;
            } else if ($withheld) {
                $stored[$area] = $withheld;
            }
        }

        $value = $stored ? json_encode($stored) : '';

        return $this->config_write($this->name, $value) ? '' : get_string('errorsetting', 'admin');
    }

    /**
     * Render the grid.
     *
     * @param array $data Current state, as returned by get_setting().
     * @param string $query Search query the settings page was filtered by.
     * @return string
     */
    public function output_html($data, $query = '') {
        $roles = $this->roles();

        if (!$roles) {
            return '';
        }

        if (!is_array($data)) {
            $data = [];
        }

        $header = html_writer::tag('td', '', ['class' => 'local-wproofreader-corner']);
        foreach ($roles as $rolename) {
            $header .= html_writer::tag('th', $rolename, [
                'scope' => 'col',
                'class' => 'local-wproofreader-role',
            ]);
        }

        $rows = '';
        foreach (context_evaluator::AREAS as $area) {
            $cells = html_writer::tag('th', $this->area_label($area), [
                'scope' => 'row',
                'class' => 'local-wproofreader-area',
            ]);

            $applicable = context_evaluator::applicable_roleids($area);

            foreach ($roles as $roleid => $rolename) {
                $cell = in_array((int) $roleid, $applicable, true)
                    ? $this->checkbox($area, $roleid, $rolename, !empty($data[$area][$roleid]))
                    : $this->notapplicable($area, $rolename);

                $cells .= html_writer::tag('td', $cell, ['class' => 'local-wproofreader-cell']);
            }

            $rows .= html_writer::tag('tr', $cells);
        }

        // The "table" class is load bearing: Boost borders every th of a table
        // that does not carry it, at a specificity this plugin cannot override.
        $table = html_writer::tag(
            'table',
            html_writer::tag('thead', html_writer::tag('tr', $header)) . html_writer::tag('tbody', $rows),
            ['class' => 'table local-wproofreader-grid']
        );

        $table = html_writer::tag('div', $table, ['class' => 'local-wproofreader-grid-wrap']);

        // Unticked checkboxes post nothing, so the sentinel is what guarantees
        // write_setting() still receives an array when the grid is cleared.
        $sentinel = html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $this->get_full_name() . '[xxxxx]',
            'value' => 1,
        ]);

        return format_admin_setting(
            $this,
            $this->visiblename,
            $sentinel . $table,
            $this->description,
            false,
            '',
            null,
            $query
        );
    }

    /**
     * Row label for an area: its name, plus a muted line saying what it covers.
     *
     * @param string $area One of the context_evaluator::AREA_* constants.
     * @return string
     */
    private function area_label(string $area): string {
        return html_writer::tag('span', get_string('area_' . $area, 'local_wproofreader'))
            . html_writer::tag(
                'span',
                get_string('area_' . $area . '_desc', 'local_wproofreader'),
                ['class' => 'local-wproofreader-area-desc']
            );
    }

    /**
     * Marker for a cell that could never take effect.
     *
     * @param string $area Area the cell belongs to.
     * @param string $rolename Role name, for the explanation.
     * @return string
     */
    private function notapplicable(string $area, string $rolename): string {
        $label = get_string('area_roles_na', 'local_wproofreader', (object) [
            'role' => $rolename,
            'area' => get_string('area_' . $area, 'local_wproofreader'),
        ]);

        return html_writer::tag('span', "\u{2014}", [
            'class' => 'local-wproofreader-na',
            'title' => $label,
            'aria-hidden' => 'true',
        ]) . html_writer::tag('span', $label, ['class' => 'local-wproofreader-offscreen']);
    }

    /**
     * One cell checkbox.
     *
     * @param string $area Area the cell belongs to.
     * @param int $roleid Role the cell belongs to.
     * @param string $rolename Role name, for the accessible label.
     * @param bool $ticked Whether the role keeps WProofreader in this area.
     * @return string
     */
    private function checkbox(string $area, int $roleid, string $rolename, bool $ticked): string {
        $attributes = [
            'type' => 'checkbox',
            'id' => $this->get_id() . '_' . $area . '_' . $roleid,
            'name' => $this->get_full_name() . '[' . $area . '][' . $roleid . ']',
            'value' => 1,
            'aria-label' => get_string('area_' . $area, 'local_wproofreader') . ': ' . $rolename,
        ];

        if ($ticked) {
            $attributes['checked'] = 'checked';
        }

        if ($this->is_readonly()) {
            $attributes['disabled'] = 'disabled';
        }

        return html_writer::empty_tag('input', $attributes);
    }
}
