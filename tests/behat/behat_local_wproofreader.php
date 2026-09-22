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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;
use local_wproofreader\local\context_evaluator;

/**
 * Steps for driving the availability matrix and checking what it decided.
 *
 * Whether WProofreader is active is read from the page itself: the plugin
 * publishes its config as window.WPROOFREADER_BOOTSTRAP before the editors
 * load, so its presence is the server's answer and needs no JavaScript and no
 * call to the proofreading service.
 *
 * @package    local_wproofreader
 * @category   test
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_wproofreader extends behat_base {
    /**
     * Put every cell of the availability matrix back on.
     *
     * @Given /^the WProofreader availability matrix is cleared$/
     */
    public function the_availability_matrix_is_cleared(): void {
        $this->save_matrix([]);
    }

    /**
     * Untick one cell of the availability matrix.
     *
     * @Given /^WProofreader is withheld from the "(?P<role_string>[^"]*)" role in the "(?P<area_string>[^"]*)" area$/
     * @param string $role Role shortname, such as student.
     * @param string $area Area name, such as quiz.
     */
    public function wproofreader_is_withheld_from_role_in_area(string $role, string $area): void {
        global $DB;

        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => $role], MUST_EXIST);

        $matrix = $this->matrix();
        $withheld = isset($matrix[$area]) && is_array($matrix[$area]) ? $matrix[$area] : [];
        $withheld[] = $roleid;
        $matrix[$area] = array_values(array_unique($withheld));

        $this->save_matrix($matrix);
    }

    /**
     * Untick a whole row of the availability matrix.
     *
     * @Given /^the WProofreader "(?P<area_string>[^"]*)" area is switched off$/
     * @param string $area Area name, such as courses.
     */
    public function the_wproofreader_area_is_switched_off(string $area): void {
        $matrix = $this->matrix();
        $matrix[$area] = context_evaluator::AREA_OFF;

        $this->save_matrix($matrix);
    }

    /**
     * Check that the plugin decided to load on the page just visited.
     *
     * @Then /^WProofreader should be active$/
     */
    public function wproofreader_should_be_active(): void {
        if (!$this->is_active()) {
            throw new ExpectationException(
                'WProofreader was withheld from this page but should have loaded',
                $this->getSession()
            );
        }
    }

    /**
     * Check that the plugin stayed away from the page just visited.
     *
     * @Then /^WProofreader should not be active$/
     */
    public function wproofreader_should_not_be_active(): void {
        if ($this->is_active()) {
            throw new ExpectationException(
                'WProofreader loaded on this page but should have been withheld',
                $this->getSession()
            );
        }
    }

    /**
     * Whether the current page carries the bootstrap the plugin injects.
     *
     * @return bool
     */
    protected function is_active(): bool {
        $content = $this->getSession()->getPage()->getContent();

        return strpos($content, 'WPROOFREADER_BOOTSTRAP') !== false;
    }

    /**
     * The stored matrix, decoded.
     *
     * @return array
     */
    protected function matrix(): array {
        $stored = json_decode((string) get_config('local_wproofreader', 'area_roles'), true);

        return is_array($stored) ? $stored : [];
    }

    /**
     * Store a matrix, dropping it entirely when nothing is withheld.
     *
     * @param array $matrix Matrix to store.
     */
    protected function save_matrix(array $matrix): void {
        set_config('area_roles', $matrix ? json_encode($matrix) : '', 'local_wproofreader');
    }
}
