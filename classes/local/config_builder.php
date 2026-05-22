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
 * Builds the configuration payload passed from PHP to the WProofreader JavaScript bundle.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config_builder {
    /** Free tier customer ID. */
    public const TRIAL_CUSTOMER_ID = 'yDxiCIre3y6k39z';

    /** Default service host. */
    public const SERVICE_HOST = 'svc.webspellchecker.net';

    /** Bundle URL on the service. */
    public const BUNDLE_URL = 'https://svc.webspellchecker.net/spellcheck31/wscbundle/wscbundle.js';

    /**
     * Build the full configuration array passed to the AMD init function.
     *
     * @return array
     */
    public static function build(): array {
        $customerid = self::customer_id();
        $isfree = self::is_free_edition($customerid);
        $badgeenabled = self::is_badge_enabled();

        $spelling     = self::feature_enabled('enable_spelling', true);
        $grammar      = self::feature_enabled('enable_grammar', true);
        $style        = self::feature_enabled('enable_style', true);
        $autocorrect  = self::feature_enabled('enable_autocorrect', false);
        $autocomplete = self::feature_enabled('enable_autocomplete', false);
        $aiassistant  = self::feature_enabled('enable_ai_writing_assistant', true) && !$isfree;

        $ignoreallcaps    = self::feature_enabled('ignore_all_caps', true);
        $ignoredomains    = self::feature_enabled('ignore_domain_names', true);
        $ignoremixedcase  = self::feature_enabled('ignore_mixed_case', true);
        $ignorewithnums   = self::feature_enabled('ignore_with_numbers', true);

        $config = [
            'serviceId'         => $customerid,
            'lang'              => self::language(),
            'bundleUrl'         => self::BUNDLE_URL,
            'serviceProtocol'   => 'https',
            'serviceHost'       => self::SERVICE_HOST,
            'servicePath'       => 'api',
            'servicePort'       => '443',
            'appType'           => 'moodle_plugin',
            'autoSearch'        => true,
            'enableGrammar'     => $grammar,
            'aiWritingAssistant' => $aiassistant,
            'enableBadgeButton' => $badgeenabled,
            'globalBadge'       => self::is_page_corner_badge(),
            'compactBadge'      => true,
            'autocomplete'      => $autocomplete,
            'allSuggestionsMode' => false,
            'spellingSuggestions'   => $spelling,
            'grammarSuggestions'    => $grammar,
            'styleGuideSuggestions' => $style,
            'autocorrect'           => $autocorrect,
            'ignoreAllCapsWords'        => $ignoreallcaps,
            'ignoreDomainNames'         => $ignoredomains,
            'ignoreWordsWithMixedCases' => $ignoremixedcase,
            'ignoreWordsWithNumbers'    => $ignorewithnums,
            'settingsSections'  => $isfree
                ? ['dictionaries', 'languages', 'general', 'options']
                : ['options', 'languages', 'dictionaries', 'about', 'general'],
            'disableOptionsStorage'          => self::disabled_options_storage(),
            'disableDictionariesPreferences' => $isfree,
            'actionItems' => $badgeenabled
                ? ['addWord', 'ignoreAll', 'toggle', 'proofreadDialog']
                : ['addWord', 'ignoreAll', 'proofreadDialog'],
            'disableAutoSearchIn' => self::excluded_selectors(),
        ];

        if ($isfree) {
            $config['generalOptions'] = [
                'spellingSuggestions',
                'grammarSuggestions',
                'styleGuideSuggestions',
                'autocorrect',
            ];
        }

        return $config;
    }

    /**
     * Build the lightweight config used by the settings page to fetch live languages.
     *
     * @return array
     */
    public static function settings_page_config(): array {
        $customerid = self::customer_id();

        return [
            'serviceId'       => $customerid,
            'lang'            => self::language(),
            'bundleUrl'       => self::BUNDLE_URL,
            'serviceProtocol' => 'https',
            'serviceHost'     => self::SERVICE_HOST,
            'servicePath'     => 'api',
            'servicePort'     => '443',
            'appType'         => 'moodle_plugin',
            'enableGrammar'   => self::feature_enabled('enable_grammar', true),
            'autoOption'      => language_catalog::AUTO_OPTION,
            'autoLabel'       => get_string('slang_auto', 'local_wproofreader'),
        ];
    }

    /**
     * Resolve the customer ID, falling back to the trial key.
     *
     * @return string
     */
    public static function customer_id(): string {
        $stored = trim((string) get_config('local_wproofreader', 'customer_id'));

        return $stored !== '' ? $stored : self::TRIAL_CUSTOMER_ID;
    }

    /**
     * Whether the active customer ID is the bundled free version key.
     *
     * @param string|null $customerid Optional already-resolved customer ID.
     * @return bool
     */
    public static function is_free_edition(?string $customerid = null): bool {
        $customerid = $customerid ?? self::customer_id();

        return $customerid === self::TRIAL_CUSTOMER_ID;
    }

    /**
     * Whether the badge should render as a single page-corner element.
     *
     * Defaults to true so existing installs that have not yet seen the
     * `badge_placement` setting keep their previous behavior.
     *
     * @return bool
     */
    public static function is_page_corner_badge(): bool {
        return (string) get_config('local_wproofreader', 'badge_placement') !== 'per_editor';
    }

    /**
     * Whether the WProofreader badge should be shown.
     *
     * @return bool
     */
    public static function is_badge_enabled(): bool {
        return self::feature_enabled('show_badge_button', true);
    }

    /**
     * Read a boolean plugin setting with an explicit default.
     *
     * Moodle does not write declared defaults to the database until the
     * admin clicks Save, so a missing row would otherwise read as `false`
     * regardless of what the settings page displays. This helper applies
     * the declared default at read time.
     *
     * @param string $name Setting name (under the `local_wproofreader` plugin).
     * @param bool   $default Default to use when no value has been stored yet.
     * @return bool
     */
    public static function feature_enabled(string $name, bool $default): bool {
        $stored = get_config('local_wproofreader', $name);

        return $stored === false ? $default : (bool) $stored;
    }

    /**
     * Resolve the default proofreading language.
     *
     * @return string
     */
    public static function language(): string {
        $stored = (string) get_config('local_wproofreader', 'slang');

        return $stored !== '' ? $stored : language_catalog::AUTO_OPTION;
    }

    /**
     * Bundle option keys that must not be persisted in the browser's localStorage.
     *
     * Without this, the bundle remembers each user's per-session choices from
     * the badge settings dialog and uses them on subsequent page loads, which
     * means an admin who changes a default (for example the proofreading
     * language or the style toggle) sees no effect on any browser that has
     * previously interacted with the badge.
     *
     * Note: only per-user toggles appear here. The paid-tier capabilities
     * themselves (enableGrammar, aiWritingAssistant, custom dictionaries
     * via disableDictionariesPreferences) are unaffected by this list.
     *
     * @return array
     */
    private static function disabled_options_storage(): array {
        return [
            'lang',
            'spellingSuggestions',
            'grammarSuggestions',
            'styleGuideSuggestions',
            'autocorrect',
            'autocomplete',
            'ignoreAllCapsWords',
            'ignoreDomainNames',
            'ignoreWordsWithMixedCases',
            'ignoreWordsWithNumbers',
        ];
    }

    /**
     * Selectors that must not be auto-proofread.
     *
     * @return array
     */
    private static function excluded_selectors(): array {
        return [
            // Moodle URL, password, and short-text fields where proofreading is noise.
            'input[type="url"]',
            'input[type="email"]',
            'input[type="password"]',
            'input[type="search"]',
            'input[name="username"]',
            'input[name="email"]',
            'input[name="url"]',
            'input[name="city"]',
            'input[name="phone1"]',
            'input[name="phone2"]',
            // Quiz answer inputs for non-essay question types (numeric, short, etc.).
            'input.formulation',
            // Atto image / link dialog inputs.
            '.atto_image_urlentry',
            '.atto_link_urlentry',
            // TinyMCE dialog URL fields.
            '.tox-dialog input[type="url"]',
        ];
    }
}
