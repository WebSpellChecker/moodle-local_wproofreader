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

namespace local_wproofreader\local;

/**
 * The stored access rules, and the vocabulary they are written in.
 *
 * One rule reads "role is allowed to feature on site part". Rules only ever
 * grant, so a site with no rules proofreads nothing, and two rules that reach
 * the same page simply add up.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access_rules {
    /** @var string Stands for every feature, or every site part. */
    public const ANY = '*';

    /** @var int Stands for every role. */
    public const EVERYONE = 0;

    /** @var string Plugin setting the rules live in. */
    public const SETTING = 'access_rules';

    /** @var string Spell checking. */
    public const FEATURE_SPELLING = 'spelling';

    /** @var string Grammar checking. */
    public const FEATURE_GRAMMAR = 'grammar';

    /** @var string Style guide suggestions. */
    public const FEATURE_STYLE = 'style';

    /** @var string Automatic correction of unambiguous misspellings. */
    public const FEATURE_AUTOCORRECT = 'autocorrect';

    /** @var string Word completion as the user types. */
    public const FEATURE_AUTOCOMPLETE = 'autocomplete';

    /** @var string The AI writing assistant, which also needs a paid licence. */
    public const FEATURE_AI = 'ai_writing_assistant';

    /**
     * Proofreading features a rule can grant, in the order the builder lists them.
     *
     * @var string[]
     */
    public const FEATURES = [
        self::FEATURE_SPELLING,
        self::FEATURE_GRAMMAR,
        self::FEATURE_STYLE,
        self::FEATURE_AUTOCORRECT,
        self::FEATURE_AUTOCOMPLETE,
        self::FEATURE_AI,
    ];

    /**
     * Every stored rule, in the order they were added.
     *
     * @return array[] Each rule as role, feature and area.
     */
    public static function all(): array {
        $stored = json_decode((string) get_config('local_wproofreader', self::SETTING), true);

        if (!is_array($stored)) {
            return [];
        }

        $rules = [];

        // Reading skips what it cannot make sense of rather than refusing the
        // whole list. A single rule naming something this version does not know,
        // after a downgrade or a hand edit, must not switch the plugin off site
        // wide and then be written over on the next save.
        foreach ($stored as $rule) {
            $normalized = is_array($rule)
                ? self::normalize($rule['role'] ?? null, $rule['feature'] ?? null, $rule['area'] ?? null)
                : null;

            if ($normalized !== null) {
                $rules[] = $normalized;
            }
        }

        return $rules;
    }

    /**
     * Read a submitted list of rules, refusing the lot if any of it is unknown.
     *
     * This is the write path, where a payload that does not parse is not to be
     * guessed at. Reading what is already stored is deliberately more forgiving:
     * see all(). Roles are not checked here either way, because a rule naming a
     * deleted role matches nobody and refusing it would make every later save
     * fail.
     *
     * @param string $json The list as it was stored.
     * @return array[]|null The rules, or null when the list itself is not readable.
     */
    public static function decode(string $json): ?array {
        $stored = json_decode($json, true);

        if (!is_array($stored)) {
            return null;
        }

        $rules = [];

        foreach ($stored as $rule) {
            if (!is_array($rule)) {
                return null;
            }

            $normalized = self::normalize($rule['role'] ?? null, $rule['feature'] ?? null, $rule['area'] ?? null);

            if ($normalized === null) {
                return null;
            }

            $rules[] = $normalized;
        }

        return $rules;
    }

    /**
     * Build one rule from what an administrator submitted.
     *
     * The role is checked against the site roles here, which reading does not
     * do: a rule naming a role that has since been deleted matches nobody, so
     * it needs no guard, but one must never be written in the first place.
     *
     * @param mixed $role Role id, or EVERYONE.
     * @param mixed $feature Feature key, or ANY.
     * @param mixed $area Area key, or ANY.
     * @return array|null The rule, or null when any part of it is unknown.
     */
    public static function make($role, $feature, $area): ?array {
        if ((int) $role !== self::EVERYONE && !array_key_exists((int) $role, self::role_options())) {
            return null;
        }

        $rule = self::normalize($role, $feature, $area);

        if ($rule && self::is_unreachable($rule['role'], $rule['area'])) {
            return null;
        }

        return $rule;
    }

    /**
     * Whether a role could never match in an area, so a rule pairing them is inert.
     *
     * Checked when a rule is written, not when one is read: a pairing that has
     * become unreachable since, because a role lost a capability, keeps working
     * as far as it can and stays in the table rather than blocking every save.
     *
     * @param int $role Role the rule names.
     * @param string $area Area the rule names.
     * @return bool
     */
    public static function is_unreachable(int $role, string $area): bool {
        if ($role === self::EVERYONE) {
            return false;
        }

        $blocked = context_evaluator::unreachable_areas()[$role] ?? [];

        // A rule naming everywhere is inert only if the role is shut out of all of it.
        return $area === self::ANY
            ? count($blocked) === count(context_evaluator::AREAS)
            : in_array($area, $blocked, true);
    }

    /**
     * One rule with its parts in the types they are stored as.
     *
     * @param mixed $role Role id, or EVERYONE.
     * @param mixed $feature Feature key, or ANY.
     * @param mixed $area Area key, or ANY.
     * @return array|null The rule, or null when the feature or the area is unknown.
     */
    private static function normalize($role, $feature, $area): ?array {
        $feature = (string) $feature;
        $area = (string) $area;

        if ($feature !== self::ANY && !in_array($feature, self::FEATURES, true)) {
            return null;
        }

        if ($area !== self::ANY && !in_array($area, context_evaluator::AREAS, true)) {
            return null;
        }

        return ['role' => (int) $role, 'feature' => $feature, 'area' => $area];
    }

    /**
     * Features the rules grant in one area to a user holding the given roles.
     *
     * @param string $area One of the context_evaluator::AREA_* constants.
     * @param int[] $roleids Roles the user holds, as they apply to the area.
     * @return string[] Feature keys, empty when nothing is granted.
     */
    public static function granted(string $area, array $roleids): array {
        $granted = [];

        foreach (self::all() as $rule) {
            if ($rule['area'] !== self::ANY && $rule['area'] !== $area) {
                continue;
            }

            if ($rule['role'] !== self::EVERYONE && !in_array($rule['role'], $roleids, true)) {
                continue;
            }

            if ($rule['feature'] === self::ANY) {
                return self::FEATURES;
            }

            $granted[$rule['feature']] = true;
        }

        return array_keys($granted);
    }

    /**
     * Roles to offer, as the value stored for each against its display name.
     *
     * @return array
     */
    public static function role_options(): array {
        static $options = null;

        if ($options === null || PHPUNIT_TEST) {
            $options = [self::EVERYONE => get_string('rule_everyone', 'local_wproofreader')];

            foreach (role_get_names(\context_system::instance(), ROLENAME_ORIGINAL, true) as $roleid => $rolename) {
                $options[(int) $roleid] = $rolename;
            }
        }

        return $options;
    }

    /**
     * Features to offer, as the value stored for each against its display name.
     *
     * @return array
     */
    public static function feature_options(): array {
        return self::options('rule_everything', self::FEATURES, 'feature_');
    }

    /**
     * Site parts to offer, as the value stored for each against its display name.
     *
     * @return array
     */
    public static function area_options(): array {
        return self::options('rule_everywhere', context_evaluator::AREAS, 'area_');
    }

    /**
     * One list of things to offer, led by the option that means all of them.
     *
     * Held for the request, because the settings page asks for each list several
     * times over: the builder and the filter row.
     *
     * @param string $anything String naming the option that stands for all of them.
     * @param string[] $keys Values to offer, in the order they are listed in.
     * @param string $prefix String name prefix each value's label is found under.
     * @return array
     */
    private static function options(string $anything, array $keys, string $prefix): array {
        static $lists = [];

        if (!isset($lists[$prefix]) || PHPUNIT_TEST) {
            $lists[$prefix] = [self::ANY => get_string($anything, 'local_wproofreader')];

            foreach ($keys as $key) {
                $lists[$prefix][$key] = get_string($prefix . $key, 'local_wproofreader');
            }
        }

        return $lists[$prefix];
    }
}
