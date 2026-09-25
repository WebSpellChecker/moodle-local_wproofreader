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
    $accessrulesversion = 2026092200;

    if ($oldversion < $accessrulesversion) {
        // The feature toggles and the per-area toggles became access rules. Each
        // enabled feature is paired with each enabled area as one rule that applies
        // to every role, so the site keeps the reach it had. The old setting names
        // and their defaults are written out here because they no longer exist
        // anywhere else in the plugin.
        $featuretoggles = [
            'spelling' => ['enable_spelling', 1],
            'grammar' => ['enable_grammar', 1],
            'style' => ['enable_style', 1],
            'autocorrect' => ['enable_autocorrect', 0],
            'autocomplete' => ['enable_autocomplete', 0],
            'ai_writing_assistant' => ['enable_ai_writing_assistant', 1],
        ];
        $areatoggles = [
            'courses' => ['enable_in_courses', 1],
            'quiz' => ['enable_on_quiz', 0],
            'categories' => ['enable_in_categories', 1],
            'users' => ['enable_on_users', 1],
            'frontend' => ['enable_on_frontend', 0],
            'admin' => ['enable_in_admin', 0],
        ];

        $enabled = function (array $toggles): array {
            $on = [];

            foreach ($toggles as $key => [$name, $default]) {
                $stored = get_config('local_wproofreader', $name);

                if ($stored === false ? $default : (int) $stored) {
                    $on[] = $key;
                }
            }

            // Every one of them means the wildcard, which then also covers
            // anything added to the plugin later.
            return count($on) === count($toggles) ? [\local_wproofreader\local\access_rules::ANY] : $on;
        };

        // The toggles are deleted below, so a step that ran far enough to write the
        // rules but died before its savepoint must not rebuild them from what is
        // left: on a second pass every toggle reads as unset and the defaults would
        // replace whatever the site had configured.
        if (get_config('local_wproofreader', \local_wproofreader\local\access_rules::SETTING) === false) {
            $rules = [];
            $features = $enabled($featuretoggles);
            $areas = $enabled($areatoggles);

            foreach ($features as $feature) {
                foreach ($areas as $area) {
                    $rules[] = [
                        'role' => \local_wproofreader\local\access_rules::EVERYONE,
                        'feature' => $feature,
                        'area' => $area,
                    ];
                }
            }

            set_config(\local_wproofreader\local\access_rules::SETTING, json_encode($rules), 'local_wproofreader');
        }

        foreach (array_merge($featuretoggles, $areatoggles) as [$name]) {
            unset_config($name, 'local_wproofreader');
        }

        upgrade_plugin_savepoint(true, $accessrulesversion, 'local', 'wproofreader');
    }

    return true;
}
