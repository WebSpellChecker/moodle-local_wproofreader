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
 * Seed the availability matrix on a fresh install.
 *
 * An unset matrix means every area is on for every role, which is the wrong
 * starting point: quiz attempts, system pages and site administration were all
 * off in the versions that had per-area toggles, and a new site should still
 * start there. Sites upgrading keep whatever they had configured, which
 * db/upgrade.php migrates instead.
 *
 * @return bool
 */
function xmldb_local_wproofreader_install(): bool {
    set_config(
        'area_roles',
        \local_wproofreader\local\context_evaluator::default_area_roles(),
        'local_wproofreader'
    );

    return true;
}
