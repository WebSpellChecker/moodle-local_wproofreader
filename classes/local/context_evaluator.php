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

/**
 * Decides whether WProofreader should be activated on the current page.
 *
 * The per-area toggles this class used to read are gone. Until the access
 * rules replace them, the only gates are the `local/wproofreader:use`
 * capability and the page types that hold no editor.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_evaluator {
    /**
     * Should WProofreader be enabled on the given page?
     *
     * @param \moodle_page $page Current Moodle page.
     * @return bool
     */
    public static function should_enable(\moodle_page $page): bool {
        if (!has_capability('local/wproofreader:use', \context_system::instance())) {
            return false;
        }

        return !self::is_excluded_pagetype((string) $page->pagetype);
    }

    /**
     * Page types where the plugin should never load (no editors involved).
     *
     * @param string $pagetype Pagetype string from $PAGE.
     * @return bool
     */
    private static function is_excluded_pagetype(string $pagetype): bool {
        $exclusions = [
            'login-index',
            'login-signup',
            'login-confirm',
            'login-forgot_password',
            'admin-plugins',
            'admin-tool-installaddon',
            'admin-tool-uploaduser',
        ];

        return in_array($pagetype, $exclusions, true);
    }
}
