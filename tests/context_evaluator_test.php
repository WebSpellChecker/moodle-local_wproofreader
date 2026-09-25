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

namespace local_wproofreader;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/rule_fixtures_trait.php');

use local_wproofreader\local\access_rules;
use local_wproofreader\local\context_evaluator;

/**
 * Tests for which features a page and a user end up with.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_wproofreader\local\context_evaluator
 */
final class context_evaluator_test extends \advanced_testcase {
    use rule_fixtures_trait;

    /**
     * A page set to the given context and pagetype.
     *
     * @param \context $context Context to set.
     * @param string $pagetype Pagetype to set.
     * @return \moodle_page
     */
    private function page(\context $context, string $pagetype): \moodle_page {
        $page = new \moodle_page();
        $page->set_context($context);
        $page->set_pagetype($pagetype);

        return $page;
    }

    /**
     * Each context level maps to the area the rules name.
     *
     * @return void
     */
    public function test_each_area_is_reached_by_its_own_page(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $category = \context_coursecat::instance($course->category);

        $this->store([
            [access_rules::EVERYONE, 'spelling', context_evaluator::AREA_COURSES],
            [access_rules::EVERYONE, 'grammar', context_evaluator::AREA_CATEGORIES],
            [access_rules::EVERYONE, 'style', context_evaluator::AREA_USERS],
            [access_rules::EVERYONE, 'autocorrect', context_evaluator::AREA_FRONTEND],
            [access_rules::EVERYONE, 'autocomplete', context_evaluator::AREA_ADMIN],
        ]);

        $coursecontext = \context_course::instance($course->id);

        $this->assertSame(['spelling'], context_evaluator::allowed_features(
            $this->page($coursecontext, 'course-view')
        ));
        $this->assertSame(['grammar'], context_evaluator::allowed_features(
            $this->page($category, 'course-index-category')
        ));
        $this->assertSame(['style'], context_evaluator::allowed_features(
            $this->page(\context_user::instance($this->getDataGenerator()->create_user()->id), 'user-profile')
        ));
        $this->assertSame(['autocorrect'], context_evaluator::allowed_features(
            $this->page(\context_system::instance(), 'calendar-view')
        ));
        $this->assertSame(['autocomplete'], context_evaluator::allowed_features(
            $this->page(\context_system::instance(), 'admin-setting-local_wproofreader')
        ));
    }

    /**
     * A quiz-style activity is its own area, told apart by pagetype.
     *
     * @return void
     */
    public function test_quiz_pages_are_their_own_area(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $context = \context_module::instance($quiz->cmid);

        $this->store([
            [access_rules::EVERYONE, 'spelling', context_evaluator::AREA_COURSES],
            [access_rules::EVERYONE, 'grammar', context_evaluator::AREA_QUIZ],
        ]);

        $this->assertSame(['grammar'], context_evaluator::allowed_features(
            $this->page($context, 'mod-quiz-attempt')
        ));

        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $this->assertSame(['spelling'], context_evaluator::allowed_features(
            $this->page(\context_module::instance($forum->cmid), 'mod-forum-view')
        ));
    }

    /**
     * Inside a course the role that counts is the one held in that course.
     *
     * @return void
     */
    public function test_course_roles_are_scoped_to_the_course(): void {
        $this->resetAfterTest();

        $teaching = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/wproofreader:use', CAP_ALLOW, $roleid, \context_system::instance()->id);
        $this->getDataGenerator()->enrol_user($user->id, $teaching->id, $roleid);

        $this->store([[$roleid, 'grammar', context_evaluator::AREA_COURSES]]);
        $this->setUser($user);

        $this->assertSame(['grammar'], context_evaluator::allowed_features(
            $this->page(\context_course::instance($teaching->id), 'course-view')
        ));
        $this->assertSame([], context_evaluator::allowed_features(
            $this->page(\context_course::instance($other->id), 'course-view')
        ));
    }

    /**
     * Outside courses, a role held anywhere on the site counts.
     *
     * @return void
     */
    public function test_site_wide_roles_count_outside_courses(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/wproofreader:use', CAP_ALLOW, $roleid, \context_system::instance()->id);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $roleid);

        $this->store([[$roleid, 'style', context_evaluator::AREA_USERS]]);
        $this->setUser($user);

        $this->assertSame(['style'], context_evaluator::allowed_features(
            $this->page(\context_user::instance($user->id), 'user-profile')
        ));
    }

    /**
     * Nobody logged in still holds the role the site gives visitors.
     *
     * @return void
     */
    public function test_a_visitor_holds_the_not_logged_in_role(): void {
        global $CFG;

        $this->resetAfterTest();

        // The test site forces login, which makes has_capability() refuse user 0
        // outright. A site that lets visitors in is what this is about.
        set_config('forcelogin', 0);
        $this->setUser(null);

        $roleid = (int) $CFG->notloggedinroleid;
        $this->assertNotEmpty($roleid);
        assign_capability('local/wproofreader:use', CAP_ALLOW, $roleid, \context_system::instance()->id, true);
        accesslib_clear_all_caches_for_unit_testing();

        $this->store([[$roleid, 'spelling', context_evaluator::AREA_FRONTEND]]);

        $this->assertSame(['spelling'], context_evaluator::allowed_features(
            $this->page(\context_system::instance(), 'site-index')
        ));
    }

    /**
     * Pages with no editor never load the plugin, whatever the rules say.
     *
     * @return void
     */
    public function test_excluded_pagetypes_are_never_reached(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->store([[access_rules::EVERYONE, access_rules::ANY, access_rules::ANY]]);

        foreach (['login-index', 'login-signup', 'admin-plugins', 'admin-tool-uploaduser'] as $pagetype) {
            $this->assertSame([], context_evaluator::allowed_features(
                $this->page(\context_system::instance(), $pagetype)
            ), $pagetype);
        }
    }

    /**
     * Without the capability nothing is allowed, however wide the rules are.
     *
     * @return void
     */
    public function test_the_capability_gates_everything(): void {
        global $CFG;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->store([[access_rules::EVERYONE, access_rules::ANY, access_rules::ANY]]);
        $this->setUser($user);

        $this->assertNotEmpty(context_evaluator::allowed_features(
            $this->page(\context_system::instance(), 'calendar-view')
        ));

        $roleid = (int) $CFG->defaultuserroleid;
        assign_capability('local/wproofreader:use', CAP_PROHIBIT, $roleid, \context_system::instance()->id, true);
        accesslib_clear_all_caches_for_unit_testing();

        $this->assertSame([], context_evaluator::allowed_features(
            $this->page(\context_system::instance(), 'calendar-view')
        ));
    }
}
