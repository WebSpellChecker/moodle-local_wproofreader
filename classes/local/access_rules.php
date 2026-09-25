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

    /** @var string[] Proofreading features a rule can grant. */
    public const FEATURES = [
        'spelling',
        'grammar',
        'style',
        'autocorrect',
        'autocomplete',
        'ai_writing_assistant',
    ];

    /**
     * Every stored rule, in the order they were added.
     *
     * @return array[] Each rule as role, feature and area.
     */
    public static function all(): array {
        return self::decode((string) get_config('local_wproofreader', self::SETTING)) ?? [];
    }

    /**
     * Read a stored list of rules.
     *
     * Roles are not checked against the site here. A rule naming a role that has
     * since been deleted matches nobody, so it is harmless, and refusing to read
     * it would strand the administrator: the list travels back with the settings
     * form, so one stale rule would make every later save fail.
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

        return self::normalize($role, $feature, $area);
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

        if ($options === null) {
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
     * times over: the builder, the filter row, and every rule it writes out.
     *
     * @param string $anything String naming the option that stands for all of them.
     * @param string[] $keys Values to offer, in the order they are listed in.
     * @param string $prefix String name prefix each value's label is found under.
     * @return array
     */
    private static function options(string $anything, array $keys, string $prefix): array {
        static $lists = [];

        if (!isset($lists[$prefix])) {
            $lists[$prefix] = [self::ANY => get_string($anything, 'local_wproofreader')];

            foreach ($keys as $key) {
                $lists[$prefix][$key] = get_string($prefix . $key, 'local_wproofreader');
            }
        }

        return $lists[$prefix];
    }

    /**
     * Whether one rule already grants everything another one grants.
     *
     * A rule reaches wider when it names everyone rather than one role, every
     * feature rather than one, or everywhere rather than one site part. Two
     * rules that cover each other are the same rule.
     *
     * @param array $wide The rule that may be the wider one.
     * @param array $narrow The rule that may be covered.
     * @return bool
     */
    public static function covers(array $wide, array $narrow): bool {
        return ($wide['role'] === self::EVERYONE || $wide['role'] === $narrow['role'])
            && ($wide['feature'] === self::ANY || $wide['feature'] === $narrow['feature'])
            && ($wide['area'] === self::ANY || $wide['area'] === $narrow['area']);
    }

    /**
     * How one rule stands against a list of rules.
     *
     * Nothing here forbids a rule. It only says which of the rules already
     * listed say the same thing, say it more widely, or are made pointless by
     * this one, by their number in the table.
     *
     * @param array $rule The rule to weigh up.
     * @param array[] $rules The rules to weigh it against.
     * @param int|null $self Position of the rule inside that list, when it is one of them.
     * @return array Rule numbers under `repeats`, `covered` and `takesover`.
     */
    public static function relations(array $rule, array $rules, ?int $self = null): array {
        $relations = ['repeats' => [], 'covered' => [], 'takesover' => []];

        foreach ($rules as $index => $other) {
            if ($index === $self) {
                continue;
            }

            $wider = self::covers($other, $rule);
            $narrower = self::covers($rule, $other);

            if ($wider && $narrower) {
                // The same rule twice. The one listed first is the original.
                if ($self === null || $index < $self) {
                    $relations['repeats'][] = $index + 1;
                }
            } else if ($wider) {
                $relations['covered'][] = $index + 1;
            } else if ($narrower) {
                $relations['takesover'][] = $index + 1;
            }
        }

        return $relations;
    }

    /**
     * One rule written out as the sentence it stands for.
     *
     * @param array $rule A rule, as stored.
     * @return string
     */
    public static function describe(array $rule): string {
        $roles = self::role_options();
        $features = self::feature_options();
        $areas = self::area_options();

        return get_string('rule_sentence', 'local_wproofreader', (object) [
            'role' => $roles[$rule['role']] ?? get_string('rule_role_missing', 'local_wproofreader'),
            'feature' => $features[$rule['feature']] ?? $rule['feature'],
            'area' => $areas[$rule['area']] ?? $rule['area'],
        ]);
    }
}
