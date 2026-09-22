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
 * The evaluator maps the page to one area of the site, then answers a single
 * yes / no question from two gates: the `local/wproofreader:use` capability in
 * the page context, and the area by role matrix from the plugin settings.
 *
 * The matrix is restrictive: WProofreader loads only while every role the user
 * holds is ticked for the area being viewed. It has to work that way because
 * the authenticated user role is held by everyone who is logged in, so a rule
 * of "any ticked role grants it" would make every other column meaningless.
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

    /** @var string Stored in place of a role list when a whole area is switched off. */
    public const AREA_OFF = '*';

    /**
     * Areas of the site, in the order the settings matrix lists them.
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
     * Areas a fresh install switches off.
     *
     * These are the three the plugin shipped disabled before the matrix
     * replaced the per-area toggles. Quiz attempts in particular are off so
     * that a new site does not start offering suggestions during assessments.
     *
     * @var string[]
     */
    public const DEFAULT_OFF_AREAS = [self::AREA_QUIZ, self::AREA_FRONTEND, self::AREA_ADMIN];

    /**
     * Capability a role needs before it can reach an area, keyed by area.
     *
     * Areas that any logged-in user can reach are absent. Site administration
     * is the exception: its pages are gated by Moodle, so a role without this
     * capability can never render one and a cell for it would be inert.
     *
     * @var array
     */
    private const AREA_CAPABILITIES = [self::AREA_ADMIN => 'moodle/site:configview'];

    /**
     * Areas rendered inside a course, where a course role assignment applies.
     *
     * Roles are resolved against the page context in these areas, so being a
     * student in the course being viewed is what counts. Everywhere else a
     * course role is not in scope, and the roles the user holds anywhere on the
     * site are used instead, otherwise those cells could never take effect.
     *
     * @var string[]
     */
    public const COURSE_AREAS = [self::AREA_COURSES, self::AREA_QUIZ];

    /**
     * Should WProofreader be enabled on the given page?
     *
     * @param \moodle_page $page Current Moodle page.
     * @return bool
     */
    public static function should_enable(\moodle_page $page): bool {
        $context = $page->context;
        if (!$context) {
            $context = \context_system::instance();
        }

        if (!has_capability('local/wproofreader:use', $context)) {
            return false;
        }

        $pagetype = (string) $page->pagetype;

        if (self::is_excluded_pagetype($pagetype)) {
            return false;
        }

        $area = self::area_for($page, $pagetype, $context);
        $withheld = self::withheld_in($area);

        if ($withheld === self::AREA_OFF) {
            return false;
        }

        if (!$withheld || is_siteadmin()) {
            return true;
        }

        foreach (self::roleids_for($area, $context) as $roleid) {
            if (in_array($roleid, $withheld, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The matrix a fresh install starts with, encoded ready for set_config().
     *
     * Areas that are on are left out entirely, so every role keeps them and
     * roles added to the site later inherit the same answer.
     *
     * @return string
     */
    public static function default_area_roles(): string {
        $matrix = [];

        foreach (self::DEFAULT_OFF_AREAS as $area) {
            $matrix[$area] = self::AREA_OFF;
        }

        return json_encode($matrix);
    }

    /**
     * Roles whose cell can actually take effect in an area.
     *
     * A role is left out when Moodle would never let it render a page in the
     * area, and, outside courses, when the role is the front page role, which
     * only applies on the site home. The settings matrix shows those cells as
     * not applicable rather than as checkboxes nobody could act on.
     *
     * @param string $area One of the AREA_* constants.
     * @return int[] Role ids, in the order roles are sorted for display.
     */
    public static function applicable_roleids(string $area): array {
        global $CFG;

        $roleids = array_keys(get_all_roles());

        if (isset(self::AREA_CAPABILITIES[$area])) {
            $allowed = array_keys(get_roles_with_capability(
                self::AREA_CAPABILITIES[$area],
                CAP_ALLOW,
                \context_system::instance()
            ));
            $roleids = array_intersect($roleids, $allowed);
        }

        if (!in_array($area, self::COURSE_AREAS, true) && !empty($CFG->defaultfrontpageroleid)) {
            $roleids = array_diff($roleids, [(int) $CFG->defaultfrontpageroleid]);
        }

        return array_values(array_map('intval', $roleids));
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
     * What the matrix withholds in one area.
     *
     * @param string $area One of the AREA_* constants.
     * @return string|int[] AREA_OFF when the whole area is off, otherwise the
     *                      role ids that were unticked, empty for none.
     */
    private static function withheld_in(string $area) {
        $stored = json_decode((string) get_config('local_wproofreader', 'area_roles'), true);

        if (!is_array($stored) || !isset($stored[$area])) {
            return [];
        }

        if ($stored[$area] === self::AREA_OFF) {
            return self::AREA_OFF;
        }

        if (!is_array($stored[$area])) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $stored[$area])));
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
