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
 * Static fallback language list, with a thin cache for the service-reported list.
 *
 * The settings page asks the WebSpellChecker service for the live language list
 * via JavaScript and posts the result back to {@see \local_wproofreader\external\save_languages}.
 * That handler stores the list in a plugin config value, which this class then
 * reads when rendering the dropdown.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class language_catalog {
    /** Config key where the cached service language list is stored. */
    public const CONFIG_KEY = 'cached_languages';

    /**
     * Options array suitable for admin_setting_configselect.
     *
     * @return array<string,string>
     */
    public static function options(): array {
        $cached = self::read_cache();

        if (!empty($cached)) {
            return $cached;
        }

        return self::fallback();
    }

    /**
     * Replace the cached language list.
     *
     * @param array<string,string> $languages Map of code => label.
     */
    public static function cache(array $languages): void {
        $sanitised = [];

        foreach ($languages as $code => $label) {
            $code = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $code);
            $label = trim((string) $label);

            if ($code === '' || $label === '') {
                continue;
            }

            $sanitised[$code] = $label;
        }

        if (empty($sanitised)) {
            return;
        }

        set_config(self::CONFIG_KEY, json_encode($sanitised), 'local_wproofreader');
    }

    /**
     * Read the cached language list, if present and valid.
     *
     * Entries whose key does not look like a language code (e.g., garbage left
     * by a previous buggy parser) are dropped so the dropdown never displays
     * service-level identifiers such as a customer ID.
     *
     * @return array<string,string>
     */
    private static function read_cache(): array {
        $raw = (string) get_config('local_wproofreader', self::CONFIG_KEY);

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return [];
        }

        $clean = [];

        foreach ($decoded as $code => $label) {
            if (!is_string($code) || !is_string($label)) {
                continue;
            }

            if (!preg_match('/^[a-zA-Z]{2,3}(_[a-zA-Z]{2,4})?$/', $code)) {
                continue;
            }

            $clean[$code] = $label;
        }

        return $clean;
    }

    /**
     * Static fallback used when no cached list is available.
     *
     * @return array<string,string>
     */
    private static function fallback(): array {
        return [
            'en_US' => 'English (American)',
            'en_GB' => 'English (British)',
            'en_AU' => 'English (Australian)',
            'en_CA' => 'English (Canadian)',
            'de_DE' => 'German',
            'de_AT' => 'German (Austrian)',
            'de_CH' => 'German (Swiss)',
            'fr_FR' => 'French',
            'fr_CA' => 'French (Canadian)',
            'es_ES' => 'Spanish',
            'es_MX' => 'Spanish (Mexican)',
            'it_IT' => 'Italian',
            'pt_BR' => 'Portuguese (Brazilian)',
            'pt_PT' => 'Portuguese (European)',
            'nl_NL' => 'Dutch',
            'sv_SE' => 'Swedish',
            'da_DK' => 'Danish',
            'fi_FI' => 'Finnish',
            'nb_NO' => 'Norwegian Bokmal',
            'pl_PL' => 'Polish',
            'cs_CZ' => 'Czech',
            'sk_SK' => 'Slovak',
            'el_GR' => 'Greek',
            'tr_TR' => 'Turkish',
            'uk_UA' => 'Ukrainian',
            'ru_RU' => 'Russian',
            'ar'    => 'Arabic',
            'he_IL' => 'Hebrew',
            'id_ID' => 'Indonesian',
        ];
    }
}
