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

use local_wproofreader\local\context_evaluator;

/**
 * Tests for the availability gates in context_evaluator.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_wproofreader\local\context_evaluator
 */
final class context_evaluator_test extends \advanced_testcase {
    /** @var \stdClass Course the test users are enrolled in. */
    private $course;

    /** @var \context_course Context of that course. */
    private $coursecontext;

    /** @var \stdClass User enrolled as an editing teacher. */
    private $teacher;

    /** @var \stdClass User enrolled as a student. */
    private $student;

    /** @var int Id of the student role. */
    private $studentroleid;

    /** @var int Id of the editing teacher role. */
    private $teacherroleid;

    /** @var \stdClass|null Quiz course module, built on demand. */
    private $quizcm = null;

    /**
     * Set up a course with one teacher and one student, and an empty matrix.
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        $this->course = $generator->create_course();
        $this->coursecontext = \context_course::instance($this->course->id);
        $this->teacher = $generator->create_user();
        $this->student = $generator->create_user();

        $generator->enrol_user($this->teacher->id, $this->course->id, 'editingteacher');
        $generator->enrol_user($this->student->id, $this->course->id, 'student');

        $this->studentroleid = (int) $DB->get_field('role', 'id', ['shortname' => 'student']);
        $this->teacherroleid = (int) $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);

        set_config('area_roles', '', 'local_wproofreader');
    }

    /**
     * Store a matrix, as area => withheld role ids or context_evaluator::AREA_OFF.
     *
     * @param array $matrix Matrix to store.
     */
    private function set_matrix(array $matrix): void {
        set_config('area_roles', json_encode($matrix), 'local_wproofreader');
    }

    /**
     * Ask the evaluator about a page, as a given user.
     *
     * @param \stdClass $user User to answer for.
     * @param \context $context Context of the page.
     * @param string $pagetype Pagetype of the page.
     * @return bool
     */
    private function is_enabled(\stdClass $user, \context $context, string $pagetype): bool {
        $this->setUser($user);

        $page = new \moodle_page();
        $page->set_context($context);
        $page->set_pagetype($pagetype);

        return context_evaluator::should_enable($page);
    }

    /**
     * Ask the evaluator about a quiz attempt page, as a given user.
     *
     * @param \stdClass $user User to answer for.
     * @return bool
     */
    private function is_enabled_on_quiz(\stdClass $user): bool {
        if (is_null($this->quizcm)) {
            $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id]);
            $this->quizcm = get_coursemodule_from_instance('quiz', $quiz->id);
        }

        $this->setUser($user);

        $page = new \moodle_page();
        $page->set_cm($this->quizcm, $this->course);
        $page->set_pagetype('mod-quiz-attempt');

        return context_evaluator::should_enable($page);
    }

    /**
     * A fresh install starts with quiz attempts, system pages and site administration off.
     *
     * That is where the per-area toggles this matrix replaced used to start, and
     * quiz attempts in particular must not begin life offering suggestions during
     * assessments.
     */
    public function test_fresh_install_defaults_match_the_shipped_toggles(): void {
        require_once(\core_component::get_component_directory('local_wproofreader') . '/db/install.php');

        unset_config('area_roles', 'local_wproofreader');
        xmldb_local_wproofreader_install();

        $this->assertSame(
            ['quiz' => '*', 'frontend' => '*', 'admin' => '*'],
            json_decode(get_config('local_wproofreader', 'area_roles'), true)
        );

        $this->assertTrue($this->is_enabled($this->teacher, $this->coursecontext, 'course-view-topics'));
        $this->assertTrue($this->is_enabled(
            $this->student,
            \context_user::instance($this->student->id),
            'user-profile'
        ));

        $this->assertFalse($this->is_enabled_on_quiz($this->teacher));
        $this->assertFalse($this->is_enabled($this->teacher, \context_system::instance(), 'calendar-view'));
        $this->assertFalse($this->is_enabled(get_admin(), \context_system::instance(), 'admin-search'));
    }

    /**
     * An empty matrix means WProofreader is available everywhere it can attach.
     */
    public function test_empty_matrix_enables_every_area(): void {
        $this->assertTrue($this->is_enabled($this->teacher, $this->coursecontext, 'course-view-topics'));
        $this->assertTrue($this->is_enabled($this->student, $this->coursecontext, 'course-view-topics'));
        $this->assertTrue($this->is_enabled_on_quiz($this->student));
        $this->assertTrue($this->is_enabled(
            $this->student,
            \context_user::instance($this->student->id),
            'user-profile'
        ));
        $this->assertTrue($this->is_enabled($this->teacher, \context_system::instance(), 'calendar-view'));
    }

    /**
     * Unticking one cell withholds that role in that area and leaves the rest alone.
     *
     * This is the case the matrix exists for: students write quiz answers unaided
     * while teachers keep proofreading, and both keep it in the rest of the course.
     */
    public function test_unticked_cell_applies_to_one_role_in_one_area(): void {
        $this->set_matrix([context_evaluator::AREA_QUIZ => [$this->studentroleid]]);

        $this->assertTrue($this->is_enabled_on_quiz($this->teacher));
        $this->assertFalse($this->is_enabled_on_quiz($this->student));
        $this->assertTrue($this->is_enabled($this->student, $this->coursecontext, 'course-view-topics'));
    }

    /**
     * A fully unticked row switches its area off for everyone, admins included.
     */
    public function test_area_off_applies_to_everyone(): void {
        $this->set_matrix([context_evaluator::AREA_COURSES => context_evaluator::AREA_OFF]);

        $this->assertFalse($this->is_enabled($this->teacher, $this->coursecontext, 'course-view-topics'));
        $this->assertFalse($this->is_enabled($this->student, $this->coursecontext, 'course-view-topics'));
        $this->assertFalse($this->is_enabled(get_admin(), $this->coursecontext, 'course-view-topics'));
        $this->assertTrue($this->is_enabled_on_quiz($this->student));
    }

    /**
     * In course areas a cell matches the role held in the course being viewed.
     */
    public function test_course_areas_match_the_role_held_in_that_course(): void {
        $other = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($this->student->id, $other->id, 'editingteacher');

        $this->set_matrix([context_evaluator::AREA_COURSES => [$this->studentroleid]]);

        $this->assertFalse($this->is_enabled($this->student, $this->coursecontext, 'course-view-topics'));
        $this->assertTrue($this->is_enabled(
            $this->student,
            \context_course::instance($other->id),
            'course-view-topics'
        ));
    }

    /**
     * Outside courses a cell matches any role the user holds anywhere on the site.
     *
     * A user who is a student only inside courses holds no course role on their
     * own profile, so the matrix falls back to their site-wide roles rather than
     * leaving the cell unable to take effect.
     */
    public function test_other_areas_match_roles_held_anywhere(): void {
        $this->set_matrix([context_evaluator::AREA_USERS => [$this->studentroleid]]);

        $usercontext = \context_user::instance($this->student->id);

        $this->assertFalse($this->is_enabled($this->student, $usercontext, 'user-profile'));
        $this->assertTrue($this->is_enabled(
            $this->teacher,
            \context_user::instance($this->teacher->id),
            'user-profile'
        ));
    }

    /**
     * The same fallback applies to categories, system pages and site administration.
     */
    public function test_other_areas_cover_categories_system_and_admin(): void {
        $category = $this->getDataGenerator()->create_category();

        $this->set_matrix([
            context_evaluator::AREA_CATEGORIES => [$this->studentroleid],
            context_evaluator::AREA_FRONTEND => [$this->studentroleid],
            context_evaluator::AREA_ADMIN => [$this->studentroleid],
        ]);

        $this->assertFalse($this->is_enabled(
            $this->student,
            \context_coursecat::instance($category->id),
            'course-index-category'
        ));
        $this->assertFalse($this->is_enabled($this->student, \context_system::instance(), 'calendar-view'));
        $this->assertFalse($this->is_enabled($this->student, \context_system::instance(), 'admin-search'));

        $this->assertTrue($this->is_enabled(
            $this->teacher,
            \context_coursecat::instance($category->id),
            'course-index-category'
        ));
    }

    /**
     * Every role the user holds has to be ticked, because everyone is an authenticated user.
     */
    public function test_every_held_role_must_be_ticked(): void {
        global $CFG;

        $this->set_matrix([context_evaluator::AREA_COURSES => [(int) $CFG->defaultuserroleid]]);

        $this->assertFalse($this->is_enabled($this->teacher, $this->coursecontext, 'course-view-topics'));
        $this->assertFalse($this->is_enabled($this->student, $this->coursecontext, 'course-view-topics'));
    }

    /**
     * Site admins keep WProofreader whatever the role columns say.
     */
    public function test_site_admins_ignore_unticked_roles(): void {
        global $DB;

        $this->set_matrix([
            context_evaluator::AREA_COURSES => array_keys($DB->get_records('role', null, '', 'id')),
            context_evaluator::AREA_USERS => array_keys($DB->get_records('role', null, '', 'id')),
        ]);

        $admin = get_admin();

        $this->assertTrue($this->is_enabled($admin, $this->coursecontext, 'course-view-topics'));
        $this->assertTrue($this->is_enabled($admin, \context_user::instance($admin->id), 'user-profile'));
    }

    /**
     * The capability is still a gate of its own, and prohibit is what closes it.
     */
    public function test_capability_prohibit_switches_off_a_role(): void {
        assign_capability(
            'local/wproofreader:use',
            CAP_PROHIBIT,
            $this->studentroleid,
            \context_system::instance()->id,
            true
        );
        accesslib_clear_all_caches_for_unit_testing();

        $this->assertTrue($this->is_enabled($this->teacher, $this->coursecontext, 'course-view-topics'));
        $this->assertFalse($this->is_enabled($this->student, $this->coursecontext, 'course-view-topics'));
    }

    /**
     * Prohibiting the capability in one course leaves the rest of the site alone.
     */
    public function test_capability_prohibit_can_be_scoped_to_one_course(): void {
        assign_capability(
            'local/wproofreader:use',
            CAP_PROHIBIT,
            $this->studentroleid,
            $this->coursecontext->id,
            true
        );
        accesslib_clear_all_caches_for_unit_testing();

        $this->assertFalse($this->is_enabled($this->student, $this->coursecontext, 'course-view-topics'));
        $this->assertTrue($this->is_enabled(
            $this->student,
            \context_user::instance($this->student->id),
            'user-profile'
        ));
    }

    /**
     * Preventing the capability is not enough on its own.
     *
     * The authenticated user role grants the capability site-wide and Moodle lets an
     * allow held through any one role win, so prevent on the student role changes
     * nothing. This is why the settings page points admins at prohibit.
     */
    public function test_capability_prevent_does_not_switch_off_a_role(): void {
        assign_capability(
            'local/wproofreader:use',
            CAP_PREVENT,
            $this->studentroleid,
            \context_system::instance()->id,
            true
        );
        accesslib_clear_all_caches_for_unit_testing();

        $this->assertTrue($this->is_enabled($this->student, $this->coursecontext, 'course-view-topics'));
    }

    /**
     * Login pages never load WProofreader, whatever the matrix says.
     */
    public function test_login_pages_are_always_excluded(): void {
        $this->assertFalse($this->is_enabled($this->student, \context_system::instance(), 'login-index'));
    }

    /**
     * A malformed stored matrix is ignored rather than switching everything off.
     */
    public function test_malformed_matrix_is_ignored(): void {
        set_config('area_roles', 'not json', 'local_wproofreader');

        $this->assertTrue($this->is_enabled($this->student, $this->coursecontext, 'course-view-topics'));
    }
}
