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

use local_wproofreader\local\admin_setting_area_roles;
use local_wproofreader\local\context_evaluator;

/**
 * Tests for the area by role matrix on the settings page.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_wproofreader\local\admin_setting_area_roles
 */
final class admin_setting_area_roles_test extends \advanced_testcase {
    /** @var admin_setting_area_roles */
    private $setting;

    /** @var int Id of the student role. */
    private $studentroleid;

    /** @var int[] Every site role id. */
    private $allroleids;

    /**
     * Build the setting under test.
     */
    protected function setUp(): void {
        global $CFG, $DB;

        parent::setUp();
        $this->resetAfterTest();

        require_once($CFG->libdir . '/adminlib.php');

        $this->setting = new admin_setting_area_roles(
            'local_wproofreader/area_roles',
            'Where WProofreader is available',
            'Description'
        );

        $this->studentroleid = (int) $DB->get_field('role', 'id', ['shortname' => 'student']);
        $this->allroleids = array_keys(get_all_roles());
    }

    /**
     * Every role ticked in an area, as the form would post it.
     *
     * @param int[] $except Roles to leave unticked.
     * @return array
     */
    private function ticked(array $except = []): array {
        $ticked = [];

        foreach ($this->allroleids as $roleid) {
            if (!in_array($roleid, $except, true)) {
                $ticked[$roleid] = 1;
            }
        }

        return $ticked;
    }

    /**
     * A fully ticked matrix, as the form would post it.
     *
     * @param array $unticked Cells to leave unticked, as area => role ids.
     * @return array
     */
    private function matrix(array $unticked = []): array {
        $posted = ['xxxxx' => 1];

        foreach (context_evaluator::AREAS as $area) {
            $posted[$area] = [];

            foreach (context_evaluator::applicable_roleids($area) as $roleid) {
                if (!in_array($roleid, $unticked[$area] ?? [], true)) {
                    $posted[$area][$roleid] = 1;
                }
            }
        }

        return $posted;
    }

    /**
     * The unticked cells of one area are stored, and the other areas stay untouched.
     */
    public function test_write_setting_stores_unticked_cells_per_area(): void {
        $this->setting->write_setting($this->matrix([
            context_evaluator::AREA_QUIZ => [$this->studentroleid],
        ]));

        $this->assertSame(
            ['quiz' => [$this->studentroleid]],
            json_decode(get_config('local_wproofreader', 'area_roles'), true)
        );
    }

    /**
     * A fully unticked row is stored as an off area, not as a list of every role.
     *
     * Storing the list would quietly reopen the area for any role created later.
     */
    public function test_write_setting_collapses_a_fully_unticked_row(): void {
        $this->setting->write_setting($this->matrix([
            context_evaluator::AREA_ADMIN => context_evaluator::applicable_roleids(
                context_evaluator::AREA_ADMIN
            ),
        ]));

        $this->assertSame(
            ['admin' => context_evaluator::AREA_OFF],
            json_decode(get_config('local_wproofreader', 'area_roles'), true)
        );
    }

    /**
     * An off row round-trips back to a fully unticked row.
     */
    public function test_get_setting_expands_an_off_area(): void {
        set_config('area_roles', json_encode(['admin' => context_evaluator::AREA_OFF]), 'local_wproofreader');

        $state = $this->setting->get_setting();

        $this->assertSame([0], array_unique(array_values($state[context_evaluator::AREA_ADMIN])));
        $this->assertSame([1], array_unique(array_values($state[context_evaluator::AREA_COURSES])));
    }

    /**
     * A fully ticked grid clears the setting instead of storing empty areas.
     */
    public function test_write_setting_clears_a_fully_ticked_matrix(): void {
        set_config('area_roles', json_encode(['quiz' => [$this->studentroleid]]), 'local_wproofreader');

        $this->setting->write_setting($this->matrix());

        $this->assertSame('', get_config('local_wproofreader', 'area_roles'));
    }

    /**
     * An area whose row posts nothing has every one of its cells unticked.
     */
    public function test_write_setting_treats_a_missing_area_as_off(): void {
        $this->setting->write_setting([
            'xxxxx' => 1,
            context_evaluator::AREA_COURSES => $this->ticked(),
        ]);

        $stored = json_decode(get_config('local_wproofreader', 'area_roles'), true);
        $expected = array_diff(context_evaluator::AREAS, [context_evaluator::AREA_COURSES]);

        $this->assertSame(array_values($expected), array_keys($stored));
        $this->assertSame([context_evaluator::AREA_OFF], array_unique(array_values($stored)));
    }

    /**
     * Nothing is written when the setting is asked to store its null default.
     */
    public function test_write_setting_ignores_the_null_default(): void {
        $this->setting->write_setting($this->setting->get_defaultsetting());

        $this->assertFalse(get_config('local_wproofreader', 'area_roles'));
    }

    /**
     * Every covered area reports every role, ticked unless stored as withheld.
     */
    public function test_get_setting_reports_every_cell(): void {
        set_config('area_roles', json_encode(['quiz' => [$this->studentroleid]]), 'local_wproofreader');

        $state = $this->setting->get_setting();

        $this->assertSame(context_evaluator::AREAS, array_keys($state));
        $this->assertSame(
            context_evaluator::applicable_roleids(context_evaluator::AREA_QUIZ),
            array_keys($state[context_evaluator::AREA_QUIZ])
        );
        $this->assertSame(0, $state[context_evaluator::AREA_QUIZ][$this->studentroleid]);
        $this->assertSame(1, $state[context_evaluator::AREA_COURSES][$this->studentroleid]);
    }

    /**
     * With nothing stored, every cell reads as ticked.
     */
    public function test_get_setting_defaults_to_ticked(): void {
        $state = $this->setting->get_setting();

        foreach (context_evaluator::AREAS as $area) {
            $this->assertSame([1], array_unique(array_values($state[$area])));
        }
    }

    /**
     * A malformed stored value reads as a fully ticked grid.
     */
    public function test_get_setting_survives_a_malformed_value(): void {
        set_config('area_roles', 'not json', 'local_wproofreader');

        $state = $this->setting->get_setting();

        $this->assertSame([1], array_unique(array_values($state[context_evaluator::AREA_QUIZ])));
    }

    /**
     * The grid renders one checkbox per area and role, checked to match the stored value.
     */
    public function test_output_html_renders_one_checkbox_per_cell(): void {
        set_config('area_roles', json_encode(['quiz' => [$this->studentroleid]]), 'local_wproofreader');

        $html = $this->setting->output_html($this->setting->get_setting());

        $cells = 0;
        foreach (context_evaluator::AREAS as $area) {
            $cells += count(context_evaluator::applicable_roleids($area));
        }

        $this->assertSame($cells, substr_count($html, 'type="checkbox"'));
        $this->assertSame($cells - 1, substr_count($html, 'checked="checked"'));

        $pattern = '/<input[^>]*name="s_local_wproofreader_area_roles\[quiz\]\['
            . $this->studentroleid . '\]"[^>]*>/';

        $this->assertSame(1, preg_match($pattern, $html, $matches));
        $this->assertStringNotContainsString('checked', $matches[0]);
    }

    /**
     * A role that cannot reach an area gets a dash instead of a checkbox.
     */
    public function test_output_html_draws_a_dash_for_unreachable_cells(): void {
        $html = $this->setting->output_html($this->setting->get_setting());

        $dashes = 0;
        foreach (context_evaluator::AREAS as $area) {
            $dashes += count($this->allroleids) - count(context_evaluator::applicable_roleids($area));
        }

        $this->assertGreaterThan(0, $dashes);
        $this->assertSame($dashes, substr_count($html, 'local-wproofreader-na'));
        $this->assertSame($dashes, substr_count($html, 'local-wproofreader-offscreen'));

        $pattern = '/<input[^>]*name="s_local_wproofreader_area_roles\[admin\]\['
            . $this->studentroleid . '\]"/';

        $this->assertSame(0, preg_match($pattern, $html));
    }

    /**
     * Only roles that can open Site administration are offered on that row.
     */
    public function test_admin_row_offers_only_roles_that_can_reach_it(): void {
        global $DB;

        $applicable = context_evaluator::applicable_roleids(context_evaluator::AREA_ADMIN);
        // The capability name is bound rather than inlined: its colon would
        // otherwise read as a named parameter alongside the positional one.
        $expected = array_keys($DB->get_records_sql(
            "SELECT DISTINCT r.id
               FROM {role} r
               JOIN {role_capabilities} rc ON rc.roleid = r.id
              WHERE rc.capability = ? AND rc.permission = ?
           ORDER BY r.id",
            ['moodle/site:configview', CAP_ALLOW]
        ));

        $this->assertEqualsCanonicalizing($expected, $applicable);
        $this->assertNotContains($this->studentroleid, $applicable);
    }

    /**
     * The front page role only applies on the site home, so it is offered in course areas only.
     */
    public function test_front_page_role_is_offered_in_course_areas_only(): void {
        global $CFG;

        $frontpage = (int) $CFG->defaultfrontpageroleid;
        $this->assertNotEmpty($frontpage);

        $this->assertContains($frontpage, context_evaluator::applicable_roleids(context_evaluator::AREA_COURSES));
        $this->assertNotContains($frontpage, context_evaluator::applicable_roleids(context_evaluator::AREA_USERS));
    }

    /**
     * Dashed cells are never written, so an unreachable role cannot creep into the value.
     */
    public function test_write_setting_ignores_unreachable_cells(): void {
        $this->setting->write_setting($this->matrix([
            context_evaluator::AREA_QUIZ => [$this->studentroleid],
        ]));

        $stored = json_decode(get_config('local_wproofreader', 'area_roles'), true);

        $this->assertSame(['quiz' => [$this->studentroleid]], $stored);
    }

    /**
     * Every area row is labelled and carries its own description line.
     */
    public function test_output_html_labels_every_area(): void {
        $html = $this->setting->output_html($this->setting->get_setting());

        // Boost borders every th of a table without this class, at a specificity
        // the plugin stylesheet cannot beat, so losing it would break the header.
        $this->assertStringContainsString('class="table local-wproofreader-grid"', $html);
        $this->assertStringContainsString('local-wproofreader-grid-wrap', $html);

        $this->assertSame(
            count(context_evaluator::AREAS),
            substr_count($html, 'local-wproofreader-area-desc')
        );

        foreach (context_evaluator::AREAS as $area) {
            $this->assertStringContainsString(
                get_string('area_' . $area, 'local_wproofreader'),
                $html
            );
        }
    }
}
