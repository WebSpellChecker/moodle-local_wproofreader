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
        $stored = json_decode((string) get_config('local_wproofreader', self::SETTING), true);

        if (!is_array($stored)) {
            return [];
        }

        $rules = [];

        foreach ($stored as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $normalized = self::normalize($rule['role'] ?? null, $rule['feature'] ?? null, $rule['area'] ?? null);

            if ($normalized) {
                $rules[] = $normalized;
            }
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
        if ((int) $role !== self::EVERYONE && !array_key_exists((int) $role, get_all_roles())) {
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

        return array_values(array_filter(self::FEATURES, function (string $feature) use ($granted): bool {
            return isset($granted[$feature]);
        }));
    }

    /**
     * Roles to offer, as the value stored for each against its display name.
     *
     * Held for the request, because every rule on the settings page asks for it.
     *
     * @return array
     */
    public static function role_options(): array {
        static $options = null;

        if ($options !== null) {
            return $options;
        }

        $options = [self::EVERYONE => get_string('rule_everyone', 'local_wproofreader')];

        $roles = role_fix_names(get_all_roles(), \context_system::instance(), ROLENAME_ORIGINAL, true);

        foreach ($roles as $roleid => $rolename) {
            $options[(int) $roleid] = $rolename;
        }

        return $options;
    }

    /**
     * Features to offer, as the value stored for each against its display name.
     *
     * @return array
     */
    public static function feature_options(): array {
        $options = [self::ANY => get_string('rule_everything', 'local_wproofreader')];

        foreach (self::FEATURES as $feature) {
            $options[$feature] = get_string('feature_' . $feature, 'local_wproofreader');
        }

        return $options;
    }

    /**
     * Site parts to offer, as the value stored for each against its display name.
     *
     * @return array
     */
    public static function area_options(): array {
        $options = [self::ANY => get_string('rule_everywhere', 'local_wproofreader')];

        foreach (context_evaluator::AREAS as $area) {
            $options[$area] = get_string('area_' . $area, 'local_wproofreader');
        }

        return $options;
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
