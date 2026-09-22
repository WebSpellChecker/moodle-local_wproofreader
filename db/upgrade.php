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

/**
 * Upgrade steps for local_wproofreader.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Run upgrade steps for local_wproofreader.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool
 */
function xmldb_local_wproofreader_upgrade(int $oldversion): bool {
    global $DB;

    if ($oldversion < 2026090800) {
        // The student archetype was added to local/wproofreader:use after the capability
        // already existed, and Moodle only seeds archetype defaults for new capabilities.
        // Apply it here so upgraded sites match a fresh install. assign_capability()
        // leaves roles alone when the capability is already defined for them.
        $studentroles = $DB->get_records('role', ['archetype' => 'student'], '', 'id');
        $systemcontext = context_system::instance();

        foreach ($studentroles as $role) {
            assign_capability('local/wproofreader:use', CAP_ALLOW, $role->id, $systemcontext->id);
        }

        upgrade_plugin_savepoint(true, 2026090800, 'local', 'wproofreader');
    }

    if ($oldversion < 2026090900) {
        // The six enable_* toggles and the site-wide role list became one area by
        // role matrix. Fold whatever the site had configured into it: an area that
        // was switched off becomes a fully off row, and roles that were withheld
        // site-wide become unticked in every area that is still on.
        $areas = [
            'courses' => 'enable_in_courses',
            'quiz' => 'enable_on_quiz',
            'categories' => 'enable_in_categories',
            'users' => 'enable_on_users',
            'frontend' => 'enable_on_frontend',
            'admin' => 'enable_in_admin',
        ];

        $sitewide = array_values(array_filter(array_map(
            'intval',
            explode(',', (string) get_config('local_wproofreader', 'disable_for_roles'))
        )));

        $matrix = [];

        foreach ($areas as $area => $toggle) {
            if (!get_config('local_wproofreader', $toggle)) {
                $matrix[$area] = '*';
            } else if ($sitewide) {
                $matrix[$area] = $sitewide;
            }
        }

        set_config('area_roles', $matrix ? json_encode($matrix) : '', 'local_wproofreader');

        foreach (array_values($areas) as $toggle) {
            unset_config($toggle, 'local_wproofreader');
        }

        unset_config('disable_for_roles', 'local_wproofreader');

        upgrade_plugin_savepoint(true, 2026090900, 'local', 'wproofreader');
    }

    return true;
}
