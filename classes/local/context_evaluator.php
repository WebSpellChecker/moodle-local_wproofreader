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
 * Works out what WProofreader may do on the current page.
 *
 * The page is mapped to one area of the site and the user to the roles that
 * apply there, then the access rules say which features they are allowed. An
 * empty answer means the plugin does not load at all.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_evaluator {
    /** @var string Course and activity pages. */
    public const AREA_COURSES = 'courses';

    /** @var string Quiz-style activity pages. */
    public const AREA_QUIZ = 'quiz';

    /** @var string Course category pages. */
    public const AREA_CATEGORIES = 'categories';

    /** @var string User pages, such as a profile or the dashboard. */
    public const AREA_USERS = 'users';

    /** @var string System level pages, such as the global calendar. */
    public const AREA_FRONTEND = 'frontend';

    /** @var string Site administration pages. */
    public const AREA_ADMIN = 'admin';

    /**
     * Areas of the site, in the order the rule builder lists them.
     *
     * @var string[]
     */
    public const AREAS = [
        self::AREA_COURSES,
        self::AREA_QUIZ,
        self::AREA_CATEGORIES,
        self::AREA_USERS,
        self::AREA_FRONTEND,
        self::AREA_ADMIN,
    ];

    /**
     * Areas rendered inside a course, where a course role assignment applies.
     *
     * Roles are resolved against the page context in these areas, so being a
     * student in the course being viewed is what counts. Everywhere else a
     * course role is not in scope, and the roles the user holds anywhere on the
     * site are used instead, otherwise those rules could never take effect.
     *
     * @var string[]
     */
    public const COURSE_AREAS = [self::AREA_COURSES, self::AREA_QUIZ];

    /**
     * Features the current user is allowed on the given page.
     *
     * @param \moodle_page $page Current Moodle page.
     * @return string[] Feature keys, empty when WProofreader must not load.
     */
    public static function allowed_features(\moodle_page $page): array {
        if (!has_capability('local/wproofreader:use', \context_system::instance())) {
            return [];
        }

        $pagetype = (string) $page->pagetype;

        if (self::is_excluded_pagetype($pagetype)) {
            return [];
        }

        $context = $page->context ?? \context_system::instance();
        $area = self::area_for($page, $pagetype, $context);

        return access_rules::granted($area, self::roleids_for($area, $context));
    }

    /**
     * Which area of the site the given page belongs to.
     *
     * @param \moodle_page $page Current Moodle page.
     * @param string $pagetype Pagetype string from $PAGE.
     * @param \context $context Page context.
     * @return string One of the AREA_* constants.
     */
    private static function area_for(\moodle_page $page, string $pagetype, \context $context): string {
        if (self::is_admin_pagetype($pagetype)) {
            return self::AREA_ADMIN;
        }

        switch ((int) $context->contextlevel) {
            case CONTEXT_MODULE:
                return self::is_quiz_module($page) ? self::AREA_QUIZ : self::AREA_COURSES;

            case CONTEXT_COURSE:
                return self::AREA_COURSES;

            case CONTEXT_COURSECAT:
                return self::AREA_CATEGORIES;

            case CONTEXT_USER:
                return self::AREA_USERS;

            case CONTEXT_SYSTEM:
            default:
                return self::AREA_FRONTEND;
        }
    }

    /**
     * Role ids the current user holds, as they apply to the given area.
     *
     * `get_user_roles_with_special()` is used inside courses because it adds
     * the authenticated user and front page roles, which are applied through
     * config rather than stored as role assignments. Outside courses the same
     * roles come from the site-wide accessdata, so that being a student
     * somewhere still counts on a page where no course role is in scope.
     *
     * The guest role is added by hand, since neither helper reports it.
     *
     * @param string $area One of the AREA_* constants.
     * @param \context $context Page context.
     * @return int[]
     */
    private static function roleids_for(string $area, \context $context): array {
        global $CFG, $USER;

        $userid = (int) $USER->id;

        if (isguestuser() && !empty($CFG->guestroleid)) {
            return [(int) $CFG->guestroleid];
        }

        $roleids = [];

        if (in_array($area, self::COURSE_AREAS, true)) {
            foreach (get_user_roles_with_special($context, $userid) as $assignment) {
                $roleids[] = (int) $assignment->roleid;
            }

            return array_values(array_unique($roleids));
        }

        if (!$userid) {
            return [];
        }

        $accessdata = get_user_roles_sitewide_accessdata($userid);

        // The accessdata carries the front page role at the front page path, where
        // it is applied through config rather than assigned. It only means anything
        // on the site home, which is a course area, so it is skipped here.
        $frontpage = (int) ($CFG->defaultfrontpageroleid ?? 0);
        $frontpagepath = $frontpage ? \context_course::instance(SITEID)->path : '';

        foreach ($accessdata['ra'] as $path => $assigned) {
            foreach ($assigned as $roleid) {
                if ((int) $roleid === $frontpage && $path === $frontpagepath) {
                    continue;
                }

                $roleids[] = (int) $roleid;
            }
        }

        return array_values(array_unique($roleids));
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
