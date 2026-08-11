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

use admin_setting_heading;

/**
 * Editor-info heading for the settings page.
 *
 * output_html() only runs when this settings page is the one actually being
 * rendered, unlike settings.php itself which reruns for every admin page while
 * Moodle rebuilds the full settings tree. Queuing the live language-list fetch
 * here (rather than as a plain js_call_amd() in settings.php) keeps it from
 * firing on every other admin settings page.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_page_heading extends admin_setting_heading {
    /**
     * Generates the HTML for the heading and queues the settings-page AMD module.
     *
     * @param mixed $data
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        global $PAGE;

        $PAGE->requires->js_call_amd('local_wproofreader/settings_page', 'init', [
            config_builder::settings_page_config(),
        ]);

        return parent::output_html($data, $query);
    }
}
