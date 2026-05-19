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
 * Public library functions for local_wproofreader.
 *
 * Modern Moodle versions (4.4+) deliver script injection through the hook
 * defined in db/hooks.php. This legacy callback is kept as a safety net for
 * sites that disable the hook system or run with non-standard themes.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Inject WProofreader assets at the top of the page body.
 *
 * @return string HTML appended to the top of the body (empty in normal operation).
 */
function local_wproofreader_before_standard_top_of_body_html(): string {
    if (defined('LOCAL_WPROOFREADER_INJECTED') && LOCAL_WPROOFREADER_INJECTED) {
        return '';
    }

    \local_wproofreader\local\page_injector::inject();

    return '';
}
