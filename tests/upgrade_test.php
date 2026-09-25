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

/**
 * Tests for the migration from the old toggles to the access rules.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::xmldb_local_wproofreader_upgrade
 */
final class upgrade_test extends \advanced_testcase {
    /** @var int The version the rules arrived in. */
    private const RULES_VERSION = 2026092200;

    /** @var string[] Every toggle the migration reads and then deletes. */
    private const TOGGLES = [
        'enable_spelling', 'enable_grammar', 'enable_style', 'enable_autocorrect',
        'enable_autocomplete', 'enable_ai_writing_assistant', 'enable_in_courses',
        'enable_on_quiz', 'enable_in_categories', 'enable_on_users',
        'enable_on_frontend', 'enable_in_admin',
    ];

    /**
     * Load the upgrade steps and clear anything the plugin has stored.
     *
     * @return void
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest();
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/local/wproofreader/db/upgrade.php');

        foreach (array_merge(self::TOGGLES, ['access_rules']) as $name) {
            unset_config($name, 'local_wproofreader');
        }

        // The savepoint refuses to write a version the plugin already has.
        set_config('version', 2026082000, 'local_wproofreader');
    }

    /**
     * Set the twelve toggles.
     *
     * @param array $toggles Toggle name against its value.
     * @return void
     */
    private function set_toggles(array $toggles): void {
        foreach ($toggles as $name => $value) {
            set_config($name, $value, 'local_wproofreader');
        }
    }

    /**
     * The rules the migration left behind.
     *
     * @return array
     */
    private function rules(): array {
        return json_decode((string) get_config('local_wproofreader', 'access_rules'), true);
    }

    /**
     * Every enabled feature is paired with every enabled area.
     *
     * @return void
     */
    public function test_toggles_become_the_cross_product(): void {
        $this->set_toggles([
            'enable_spelling' => 1, 'enable_grammar' => 1, 'enable_style' => 0,
            'enable_autocorrect' => 0, 'enable_autocomplete' => 0, 'enable_ai_writing_assistant' => 0,
            'enable_in_courses' => 1, 'enable_on_quiz' => 0, 'enable_in_categories' => 1,
            'enable_on_users' => 0, 'enable_on_frontend' => 0, 'enable_in_admin' => 0,
        ]);

        xmldb_local_wproofreader_upgrade(2026082000);

        $this->assertEquals([
            ['role' => 0, 'feature' => 'spelling', 'area' => 'courses'],
            ['role' => 0, 'feature' => 'spelling', 'area' => 'categories'],
            ['role' => 0, 'feature' => 'grammar', 'area' => 'courses'],
            ['role' => 0, 'feature' => 'grammar', 'area' => 'categories'],
        ], $this->rules());
    }

    /**
     * All of them on collapses to the wildcard.
     *
     * @return void
     */
    public function test_everything_on_becomes_one_rule(): void {
        $this->set_toggles(array_fill_keys(self::TOGGLES, 1));

        xmldb_local_wproofreader_upgrade(2026082000);

        $this->assertEquals([['role' => 0, 'feature' => '*', 'area' => '*']], $this->rules());
    }

    /**
     * A site with nothing enabled keeps nothing enabled.
     *
     * @return void
     */
    public function test_everything_off_becomes_no_rules(): void {
        $this->set_toggles(array_fill_keys(self::TOGGLES, 0));

        xmldb_local_wproofreader_upgrade(2026082000);

        $this->assertSame([], $this->rules());
    }

    /**
     * Unset toggles fall back to the defaults the plugin used to ship.
     *
     * @return void
     */
    public function test_unset_toggles_use_the_old_defaults(): void {
        xmldb_local_wproofreader_upgrade(2026082000);

        $rules = $this->rules();
        $features = array_values(array_unique(array_column($rules, 'feature')));
        $areas = array_values(array_unique(array_column($rules, 'area')));

        $this->assertSame(['spelling', 'grammar', 'style', 'ai_writing_assistant'], $features);
        $this->assertSame(['courses', 'categories', 'users'], $areas);
        $this->assertCount(12, $rules);
    }

    /**
     * The old toggles are gone once the step has run.
     *
     * @return void
     */
    public function test_the_toggles_are_removed(): void {
        $this->set_toggles(array_fill_keys(self::TOGGLES, 1));

        xmldb_local_wproofreader_upgrade(2026082000);

        foreach (self::TOGGLES as $name) {
            $this->assertFalse(get_config('local_wproofreader', $name), $name);
        }
    }

    /**
     * Running the step again does not rebuild the rules from the deleted toggles.
     *
     * A step that wrote the rules and then died before its savepoint runs a second
     * time with every toggle already gone, which must not mean "use the defaults".
     *
     * @return void
     */
    public function test_the_step_can_run_twice(): void {
        $this->set_toggles(array_fill_keys(self::TOGGLES, 0));

        xmldb_local_wproofreader_upgrade(2026082000);
        $this->assertSame([], $this->rules());

        // A step that died before its savepoint leaves the old version behind,
        // and the toggles it already deleted stay deleted.
        set_config('version', 2026082000, 'local_wproofreader');

        xmldb_local_wproofreader_upgrade(2026082000);
        $this->assertSame([], $this->rules());
    }

    /**
     * A site already past this version is left alone.
     *
     * @return void
     */
    public function test_a_newer_site_is_untouched(): void {
        set_config('access_rules', '[{"role":7,"feature":"grammar","area":"quiz"}]', 'local_wproofreader');

        xmldb_local_wproofreader_upgrade(self::RULES_VERSION);

        $this->assertEquals([['role' => 7, 'feature' => 'grammar', 'area' => 'quiz']], $this->rules());
    }
}
