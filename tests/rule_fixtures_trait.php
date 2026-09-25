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

namespace local_wproofreader;

/**
 * Writes a list of rules for a test, spelled the short way.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait rule_fixtures_trait {
    /**
     * Store a list of rules, each given as role, feature and area.
     *
     * @param array $rules Each rule as a three-element list.
     * @return void
     */
    private function store(array $rules): void {
        set_config('access_rules', json_encode(array_map(function (array $rule): array {
            return ['role' => $rule[0], 'feature' => $rule[1], 'area' => $rule[2]];
        }, $rules)), 'local_wproofreader');
    }
}
