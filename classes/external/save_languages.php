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

namespace local_wproofreader\external;

// Resolve the external API classes to whichever form is actually loadable on
// this Moodle version. On 4.2+, the global-namespace names (external_api,
// etc.) only exist via a class_alias() in lib/externallib.php - and that
// alias is only registered once that file has been require()'d, which is not
// guaranteed by the time a webservice call dispatches to this class (that
// path throws "Class external_api not found" on 4.3/4.4/4.5/5.2, confirmed
// 2026-08-20). core_external\external_api itself is always autoloadable
// there, so prefer it and only fall back to the bare global names on
// pre-4.2 versions (4.1), which never had the core_external\ namespace.
if (class_exists('core_external\\external_api')) {
    class_alias('core_external\\external_api', __NAMESPACE__ . '\\compat_external_api');
    class_alias('core_external\\external_function_parameters', __NAMESPACE__ . '\\compat_external_function_parameters');
    class_alias('core_external\\external_value', __NAMESPACE__ . '\\compat_external_value');
    class_alias('core_external\\external_single_structure', __NAMESPACE__ . '\\compat_external_single_structure');
} else {
    class_alias('external_api', __NAMESPACE__ . '\\compat_external_api');
    class_alias('external_function_parameters', __NAMESPACE__ . '\\compat_external_function_parameters');
    class_alias('external_value', __NAMESPACE__ . '\\compat_external_value');
    class_alias('external_single_structure', __NAMESPACE__ . '\\compat_external_single_structure');
}

/**
 * External function that stores the live language list from the WebSpellChecker service.
 *
 * The settings page calls this over AJAX after asking the WProofreader bundle
 * for its supported languages. Only site administrators can invoke it.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_languages extends compat_external_api {
    /**
     * Parameter definition for save_languages.
     *
     * @return compat_external_function_parameters
     */
    public static function execute_parameters(): compat_external_function_parameters {
        return new compat_external_function_parameters([
            'payload' => new compat_external_value(PARAM_RAW, 'JSON-encoded language list from the WebSpellChecker service.'),
        ]);
    }

    /**
     * Decode the service payload and cache its language map.
     *
     * @param string $payload JSON string.
     * @return array{stored:bool,count:int}
     */
    public static function execute(string $payload): array {
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        [$payload] = array_values(self::validate_parameters(
            self::execute_parameters(),
            ['payload' => $payload]
        ));

        $languages = self::extract_language_map($payload);

        if (empty($languages)) {
            return ['stored' => false, 'count' => 0];
        }

        \local_wproofreader\local\language_catalog::cache($languages);

        return ['stored' => true, 'count' => count($languages)];
    }

    /**
     * Return value definition.
     *
     * @return compat_external_single_structure
     */
    public static function execute_returns(): compat_external_single_structure {
        return new compat_external_single_structure([
            'stored' => new compat_external_value(PARAM_BOOL, 'Whether a language list was successfully stored.'),
            'count'  => new compat_external_value(PARAM_INT, 'Number of languages stored.'),
        ]);
    }

    /**
     * Extract a code => label map from the WebSpellChecker getInfo() payload.
     *
     * The service returns the supported languages under `langList`, split by
     * text direction. Both directions are merged into a single flat map for
     * the settings dropdown.
     *
     * Example payload shape:
     *
     *   {
     *     "langList": {
     *       "ltr": {"en_US": "English (American)", "de_DE": "German", ...},
     *       "rtl": {"ar": "Arabic", "he_IL": "Hebrew", ...}
     *     },
     *     ...
     *   }
     *
     * @param string $payload Raw JSON.
     * @return array<string,string>
     */
    private static function extract_language_map(string $payload): array {
        $decoded = json_decode($payload, true);

        if (!is_array($decoded)) {
            return [];
        }

        $langlist = $decoded['langList'] ?? $decoded['lang_list'] ?? null;

        if (!is_array($langlist)) {
            return [];
        }

        $map = [];

        foreach (['ltr', 'rtl'] as $direction) {
            $entries = $langlist[$direction] ?? null;

            if (!is_array($entries)) {
                continue;
            }

            foreach ($entries as $code => $label) {
                if (!is_string($code) || !is_string($label)) {
                    continue;
                }

                $code = trim($code);
                $label = trim($label);

                if ($code === '' || $label === '') {
                    continue;
                }

                $map[$code] = $label;
            }
        }

        return $map;
    }
}
