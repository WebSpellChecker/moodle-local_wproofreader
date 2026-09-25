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
    /**
     * Store a list of rules, written the short way.
     *
     * @param array $rules Each rule as role, feature and area.
     * @return void
     */
    private function store(array $rules): void {
        set_config('access_rules', json_encode(array_map(function (array $rule): array {
            return ['role' => $rule[0], 'feature' => $rule[1], 'area' => $rule[2]];
        }, $rules)), 'local_wproofreader');
    }

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
     * A rule covers another when it is the same rule or a wider one.
     *
     * @return void
     */
    public function test_covers(): void {
        $everything = ['role' => access_rules::EVERYONE, 'feature' => access_rules::ANY, 'area' => access_rules::ANY];
        $spelling = ['role' => access_rules::EVERYONE, 'feature' => 'spelling', 'area' => 'courses'];
        $student = ['role' => 5, 'feature' => 'spelling', 'area' => 'courses'];
        $teacher = ['role' => 3, 'feature' => 'spelling', 'area' => 'courses'];

        $this->assertTrue(access_rules::covers($everything, $student));
        $this->assertFalse(access_rules::covers($student, $everything));
        $this->assertTrue(access_rules::covers($spelling, $student));
        $this->assertFalse(access_rules::covers($student, $spelling));
        $this->assertFalse(access_rules::covers($student, $teacher));
        $this->assertTrue(access_rules::covers($student, $student));
    }

    /**
     * A rule is weighed against the list by number, and never against itself.
     *
     * @return void
     */
    public function test_relations(): void {
        $spelling = ['role' => access_rules::EVERYONE, 'feature' => 'spelling', 'area' => 'courses'];
        $student = ['role' => 5, 'feature' => 'spelling', 'area' => 'courses'];
        $everything = ['role' => access_rules::EVERYONE, 'feature' => access_rules::ANY, 'area' => access_rules::ANY];
        $rules = [$spelling, $student, $spelling];

        // A rule not in the list is weighed against all of it.
        $relations = access_rules::relations($spelling, $rules);
        $this->assertSame([1, 3], $relations['repeats']);
        $this->assertSame([2], $relations['takesover']);

        // The second copy repeats the first; the first repeats nothing.
        $this->assertSame([1], access_rules::relations($spelling, $rules, 2)['repeats']);
        $this->assertSame([], access_rules::relations($spelling, $rules, 0)['repeats']);

        // The narrower rule is covered by both copies of the wider one.
        $this->assertSame([1, 3], access_rules::relations($student, $rules, 1)['covered']);

        // A rule that covers the lot takes all of it over.
        $this->assertSame([1, 2, 3], access_rules::relations($everything, $rules)['takesover']);

        // A rule with nothing in common stands on its own.
        $alone = ['role' => access_rules::EVERYONE, 'feature' => 'autocorrect', 'area' => 'admin'];
        $this->assertSame(
            ['repeats' => [], 'covered' => [], 'takesover' => []],
            access_rules::relations($alone, $rules)
        );
    }
}
