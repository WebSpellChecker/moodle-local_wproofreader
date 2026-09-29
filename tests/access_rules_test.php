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
     * Any capability that opens an admin page counts, not one chosen capability.
     *
     * @return void
     */
    public function test_any_admin_tree_capability_reaches_site_administration(): void {
        $this->resetAfterTest();

        // The tree is assembled from what the viewer may configure, and the
        // settings page this feeds is only ever open to an administrator.
        $this->setAdminUser();

        $system = \context_system::instance();
        $opener = (int) $this->getDataGenerator()->create_role();
        $outsider = (int) $this->getDataGenerator()->create_role();

        // Guards the user list at admin/user.php, and is not site:configview.
        assign_capability('moodle/user:update', CAP_ALLOW, $opener, $system->id, true);
        accesslib_clear_all_caches_for_unit_testing();

        $blocked = context_evaluator::unreachable_areas();

        $this->assertNotContains(context_evaluator::AREA_ADMIN, $blocked[$opener] ?? []);
        $this->assertContains(context_evaluator::AREA_ADMIN, $blocked[$outsider] ?? []);
    }

    /**
     * An ordinary role named as the front page role still reaches every area.
     *
     * @return void
     */
    public function test_an_ordinary_front_page_role_is_not_narrowed(): void {
        global $DB;

        $this->resetAfterTest();

        $student = (int) $DB->get_field('role', 'id', ['shortname' => 'student']);
        set_config('defaultfrontpageroleid', $student);

        // Core applies this role at the site home through config, but the site
        // still holds it wherever it is assigned, so its rules do take effect.
        $blocked = context_evaluator::unreachable_areas();
        $this->assertNotContains(context_evaluator::AREA_USERS, $blocked[$student] ?? []);

        // The dedicated role is the one that never leaves the site home.
        $frontpage = (int) $DB->get_field('role', 'id', ['shortname' => 'frontpage']);
        set_config('defaultfrontpageroleid', $frontpage);

        $blocked = context_evaluator::unreachable_areas();
        $this->assertContains(context_evaluator::AREA_USERS, $blocked[$frontpage] ?? []);
        $this->assertNotContains(context_evaluator::AREA_COURSES, $blocked[$frontpage] ?? []);
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
