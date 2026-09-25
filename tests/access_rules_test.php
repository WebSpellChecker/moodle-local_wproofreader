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
 * Tests for the access rules: what they grant, and how they stand against each other.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_wproofreader\local\access_rules
 */
final class access_rules_test extends \advanced_testcase {
    use rule_fixtures_trait;

    /**
     * A rule grants its feature to the role it names, in the area it names.
     *
     * @return void
     */
    public function test_granted_matches_role_feature_and_area(): void {
        $this->resetAfterTest();
        $student = $this->getDataGenerator()->create_role();
        $teacher = $this->getDataGenerator()->create_role();

        $this->store([
            [$student, 'spelling', context_evaluator::AREA_COURSES],
            [$teacher, 'grammar', context_evaluator::AREA_COURSES],
            [$student, 'style', context_evaluator::AREA_QUIZ],
        ]);

        $this->assertSame(['spelling'], access_rules::granted(context_evaluator::AREA_COURSES, [$student]));
        $this->assertSame(['grammar'], access_rules::granted(context_evaluator::AREA_COURSES, [$teacher]));
        $this->assertSame(['style'], access_rules::granted(context_evaluator::AREA_QUIZ, [$student]));
        $this->assertSame([], access_rules::granted(context_evaluator::AREA_USERS, [$student]));
        $this->assertSame([], access_rules::granted(context_evaluator::AREA_COURSES, []));
    }

    /**
     * Rules add up rather than override one another.
     *
     * @return void
     */
    public function test_granted_adds_rules_together(): void {
        $this->resetAfterTest();
        $role = $this->getDataGenerator()->create_role();

        $this->store([
            [access_rules::EVERYONE, 'spelling', context_evaluator::AREA_COURSES],
            [$role, 'grammar', context_evaluator::AREA_COURSES],
        ]);

        $granted = access_rules::granted(context_evaluator::AREA_COURSES, [$role]);
        sort($granted);

        $this->assertSame(['grammar', 'spelling'], $granted);
    }

    /**
     * The wildcards stand for every role, every feature and every area.
     *
     * @return void
     */
    public function test_granted_honours_the_wildcards(): void {
        $this->resetAfterTest();
        $this->store([[access_rules::EVERYONE, access_rules::ANY, access_rules::ANY]]);

        foreach (context_evaluator::AREAS as $area) {
            $this->assertSame(access_rules::FEATURES, access_rules::granted($area, []));
        }
    }

    /**
     * No rules means no proofreading anywhere.
     *
     * @return void
     */
    public function test_granted_is_empty_without_rules(): void {
        $this->resetAfterTest();
        set_config('access_rules', '[]', 'local_wproofreader');

        $this->assertSame([], access_rules::granted(context_evaluator::AREA_COURSES, [1, 2, 3]));
    }

    /**
     * A rule naming a role that has been deleted is kept, and simply matches nobody.
     *
     * @return void
     */
    public function test_a_rule_for_a_deleted_role_survives_reading(): void {
        $this->resetAfterTest();
        $this->store([[4242, 'spelling', context_evaluator::AREA_COURSES]]);

        $this->assertCount(1, access_rules::all());
        $this->assertSame([], access_rules::granted(context_evaluator::AREA_COURSES, [1]));
    }

    /**
     * A stored list naming a feature or an area that does not exist is not readable.
     *
     * @return void
     */
    public function test_decode_refuses_an_unknown_feature_or_area(): void {
        $this->resetAfterTest();

        $this->assertNull(access_rules::decode('nonsense'));
        $this->assertNull(access_rules::decode('[{"role":0,"feature":"telepathy","area":"courses"}]'));
        $this->assertNull(access_rules::decode('[{"role":0,"feature":"spelling","area":"atlantis"}]'));
        $this->assertNull(access_rules::decode('["not a rule"]'));
        $this->assertSame([], access_rules::decode('[]'));
    }

    /**
     * Writing a rule checks the role exists; reading one does not.
     *
     * @return void
     */
    public function test_make_checks_the_role_exists(): void {
        $this->resetAfterTest();
        $role = $this->getDataGenerator()->create_role();

        $this->assertNull(access_rules::make(4242, 'spelling', context_evaluator::AREA_COURSES));
        $this->assertSame(
            ['role' => $role, 'feature' => 'spelling', 'area' => context_evaluator::AREA_COURSES],
            access_rules::make($role, 'spelling', context_evaluator::AREA_COURSES)
        );
        $this->assertSame(
            ['role' => access_rules::EVERYONE, 'feature' => access_rules::ANY, 'area' => access_rules::ANY],
            access_rules::make(access_rules::EVERYONE, access_rules::ANY, access_rules::ANY)
        );
    }

    /**
     * A rule pairing a role with an area it can never reach is refused on write.
     *
     * @return void
     */
    public function test_make_refuses_an_unreachable_pairing(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        $student = $DB->get_record('role', ['shortname' => 'student']);
        $manager = $DB->get_record('role', ['shortname' => 'manager']);

        // Student holds no moodle/site:configview, so it can never open the area.
        $this->assertNull(access_rules::make($student->id, 'spelling', context_evaluator::AREA_ADMIN));
        $this->assertTrue(access_rules::is_unreachable((int) $student->id, context_evaluator::AREA_ADMIN));

        // Manager does, and Everyone is never narrowed.
        $this->assertNotNull(access_rules::make($manager->id, 'spelling', context_evaluator::AREA_ADMIN));
        $this->assertNotNull(access_rules::make(access_rules::EVERYONE, 'spelling', context_evaluator::AREA_ADMIN));

        // Other areas stay open to Student, and so does the wildcard.
        $this->assertNotNull(access_rules::make($student->id, 'spelling', context_evaluator::AREA_COURSES));
        $this->assertNotNull(access_rules::make($student->id, 'spelling', access_rules::ANY));

        // The front page role is shut out of everything outside the course areas,
        // so for it the wildcard is not inert but the other areas are.
        $frontpage = (int) $CFG->defaultfrontpageroleid;
        $this->assertTrue(access_rules::is_unreachable($frontpage, context_evaluator::AREA_USERS));
        $this->assertFalse(access_rules::is_unreachable($frontpage, context_evaluator::AREA_COURSES));
    }

    /**
     * An unreachable rule already stored is still readable, so saves keep working.
     *
     * @return void
     */
    public function test_an_unreachable_rule_already_stored_is_kept(): void {
        global $DB;

        $this->resetAfterTest();
        $student = $DB->get_record('role', ['shortname' => 'student']);
        $this->store([[$student->id, 'spelling', context_evaluator::AREA_ADMIN]]);

        $this->assertCount(1, access_rules::all());
        $this->assertNotNull(access_rules::decode(
            json_encode([['role' => $student->id, 'feature' => 'spelling', 'area' => 'admin']])
        ));
    }
}
