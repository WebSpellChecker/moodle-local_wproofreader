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

global $CFG;

require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/rule_fixtures_trait.php');

use local_wproofreader\local\access_rules;
use local_wproofreader\local\admin_setting_access_rules;
use local_wproofreader\local\context_evaluator;

/**
 * Tests for the settings control that stores the rules.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_wproofreader\local\admin_setting_access_rules
 */
final class admin_setting_access_rules_test extends \advanced_testcase {
    use rule_fixtures_trait;

    /**
     * The control under test.
     *
     * @return admin_setting_access_rules
     */
    private function setting(): admin_setting_access_rules {
        return new admin_setting_access_rules(
            'local_wproofreader/access_rules',
            'Feature access rules',
            'Description',
            null
        );
    }

    /**
     * The hidden field's list is what gets stored.
     *
     * @return void
     */
    public function test_a_posted_list_is_stored(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $posted = json_encode([
            ['role' => access_rules::EVERYONE, 'feature' => 'spelling', 'area' => context_evaluator::AREA_COURSES],
        ]);

        $this->assertSame('', $this->setting()->write_setting($posted));
        $this->assertSame(
            [['role' => access_rules::EVERYONE, 'feature' => 'spelling', 'area' => context_evaluator::AREA_COURSES]],
            access_rules::all()
        );
    }

    /**
     * An empty list is a real answer, and clears the rules.
     *
     * @return void
     */
    public function test_an_empty_list_clears_the_rules(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->store([[access_rules::EVERYONE, 'spelling', context_evaluator::AREA_COURSES]]);

        $this->assertSame('', $this->setting()->write_setting('[]'));
        $this->assertSame([], access_rules::all());
    }

    /**
     * A list that cannot be read is refused, and the stored rules stay.
     *
     * @return void
     */
    public function test_an_unreadable_list_is_refused(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->store([[access_rules::EVERYONE, 'spelling', context_evaluator::AREA_COURSES]]);

        $setting = $this->setting();

        foreach (['not json at all', '{"role":0}', json_encode([['role' => 0, 'feature' => 'nonsense']])] as $bad) {
            $this->assertNotSame('', $setting->write_setting($bad), $bad);
        }

        $this->assertCount(1, access_rules::all());
    }

    /**
     * Anything that is not the rules string is refused rather than cast.
     *
     * @return void
     */
    public function test_a_value_that_is_not_a_string_is_refused(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->store([[access_rules::EVERYONE, 'spelling', context_evaluator::AREA_COURSES]]);

        $this->assertNotSame('', $this->setting()->write_setting(['rules' => '[]']));
        $this->assertCount(1, access_rules::all());
    }

    /**
     * What was stored is what the control reports back.
     *
     * @return void
     */
    public function test_the_control_reports_the_stored_rules(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->store([[access_rules::EVERYONE, access_rules::ANY, access_rules::ANY]]);

        $this->assertSame(access_rules::all(), $this->setting()->get_setting());
    }
}
