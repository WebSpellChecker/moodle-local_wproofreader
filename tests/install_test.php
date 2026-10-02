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
 * Tests for what a fresh install starts with.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::xmldb_local_wproofreader_install
 */
final class install_test extends \advanced_testcase {
    /**
     * A new site proofreads everything, everywhere, for everyone.
     *
     * @return void
     */
    public function test_install_seeds_one_rule_that_covers_everything(): void {
        global $CFG;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/local/wproofreader/db/install.php');

        unset_config('access_rules', 'local_wproofreader');
        xmldb_local_wproofreader_install();

        $this->assertEquals(
            [['role' => access_rules::EVERYONE, 'feature' => access_rules::ANY, 'area' => access_rules::ANY]],
            access_rules::all()
        );

        foreach (context_evaluator::AREAS as $area) {
            $this->assertSame(access_rules::FEATURES, access_rules::granted($area, []), $area);
        }
    }
}
