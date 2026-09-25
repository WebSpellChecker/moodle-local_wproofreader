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
 * Install steps for local_wproofreader.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Seed the access rules on a fresh install.
 *
 * Rules only grant, so a site with none proofreads nothing. One rule that
 * covers everyone, every feature and every area is what makes the plugin work
 * as soon as it is installed. Sites that upgrade keep the reach they had, which
 * db/upgrade.php migrates instead.
 *
 * @return bool
 */
function xmldb_local_wproofreader_install(): bool {
    set_config(
        'access_rules',
        json_encode([['role' => 0, 'feature' => '*', 'area' => '*']]),
        'local_wproofreader'
    );

    return true;
}
