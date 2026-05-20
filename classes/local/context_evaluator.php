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
 * The evaluator reads the plugin settings and the Moodle page context, then
 * answers a single yes / no question. Each context level is mapped to one of
 * the toggle settings to keep behavior predictable.
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

        $pagetype = (string) $page->pagetype;

        if (self::is_excluded_pagetype($pagetype)) {
            return false;
        }

        $context = $page->context;
        $config = get_config('local_wproofreader');

        if (self::is_admin_pagetype($pagetype)) {
            return !empty($config->enable_in_admin);
        }

        $contextlevel = $context ? (int) $context->contextlevel : CONTEXT_SYSTEM;

        switch ($contextlevel) {
            case CONTEXT_MODULE:
                if (self::is_quiz_module($page)) {
                    return !empty($config->enable_on_quiz);
                }
                return !empty($config->enable_in_courses);

            case CONTEXT_COURSE:
                return !empty($config->enable_in_courses);

            case CONTEXT_COURSECAT:
                return !empty($config->enable_in_categories);

            case CONTEXT_USER:
                return !empty($config->enable_on_users);

            case CONTEXT_SYSTEM:
            default:
                return !empty($config->enable_on_frontend);
        }
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

        foreach ($exclusions as $excluded) {
            if ($pagetype === $excluded) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the pagetype corresponds to the site administration area.
     *
     * @param string $pagetype Pagetype string from $PAGE.
     * @return bool
     */
    private static function is_admin_pagetype(string $pagetype): bool {
        if ($pagetype === '') {
            return false;
        }

        return strpos($pagetype, 'admin-') === 0
            || strpos($pagetype, 'admin/') === 0
            || $pagetype === 'admin';
    }

    /**
     * Whether the current module is a quiz-style activity.
     *
     * On the quiz attempt page (`mod-quiz-attempt`) the hook fires before
     * `$PAGE->cm` is populated, so `$page->cm` is null at this point even
     * though it is set later (the body class still ends up with
     * `cm-type-quiz`). Pagetype is set by the time the hook fires and is
     * used as a fallback signal.
     *
     * @param \moodle_page $page Current page.
     * @return bool
     */
    private static function is_quiz_module(\moodle_page $page): bool {
        $modnames = ['quiz', 'questionnaire', 'feedback'];

        if (isset($page->cm) && $page->cm) {
            return in_array((string) $page->cm->modname, $modnames, true);
        }

        $pagetype = (string) $page->pagetype;
        foreach ($modnames as $modname) {
            if (strpos($pagetype, "mod-{$modname}-") === 0) {
                return true;
            }
        }

        return false;
    }
}
