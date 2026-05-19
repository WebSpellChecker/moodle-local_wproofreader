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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

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
class save_languages extends external_api {

    /**
     * Parameter definition for save_languages.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'payload' => new external_value(PARAM_RAW, 'JSON-encoded language list from the WebSpellChecker service.'),
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
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'stored' => new external_value(PARAM_BOOL, 'Whether a language list was successfully stored.'),
            'count'  => new external_value(PARAM_INT, 'Number of languages stored.'),
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
