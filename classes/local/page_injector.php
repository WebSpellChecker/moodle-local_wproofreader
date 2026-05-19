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

defined('MOODLE_INTERNAL') || die();

/**
 * Coordinates injection of the WProofreader bundle and AMD init call into the page.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_injector {

    /**
     * Inject the WProofreader AMD init call into the current page when allowed.
     */
    public static function inject(): void {
        global $PAGE;

        if (defined('LOCAL_WPROOFREADER_INJECTED') && LOCAL_WPROOFREADER_INJECTED) {
            return;
        }

        if (CLI_SCRIPT || AJAX_SCRIPT || WS_SERVER) {
            return;
        }

        if (!isset($PAGE) || !($PAGE instanceof \moodle_page)) {
            return;
        }

        if (!context_evaluator::should_enable($PAGE)) {
            return;
        }

        $config = config_builder::build();

        $PAGE->requires->js_call_amd('local_wproofreader/init', 'init', [$config]);

        define('LOCAL_WPROOFREADER_INJECTED', true);
    }
}
