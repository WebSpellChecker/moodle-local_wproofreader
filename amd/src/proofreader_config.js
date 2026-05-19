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

/**
 * Builds the global WEBSPELLCHECKER_CONFIG object the bundle reads at startup.
 *
 * @module     local_wproofreader/proofreader_config
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const toBoolean = (value, fallback) => {
    if (value === true || value === 'true') {
        return true;
    }
    if (value === false || value === 'false') {
        return false;
    }
    return fallback;
};

const toArray = (value) => Array.isArray(value) ? value : [];

/**
 * Write the WebSpellChecker config to window.WEBSPELLCHECKER_CONFIG.
 *
 * @param {Object} config Server-supplied config payload.
 */
export const apply = (config) => {
    const isBadgeEnabled = toBoolean(config.enableBadgeButton, true);
    const defaultBadgeActions = isBadgeEnabled
        ? ['addWord', 'ignoreAll', 'settings', 'toggle', 'proofreadDialog']
        : ['addWord', 'ignoreAll', 'settings', 'proofreadDialog'];
    const badgeActions = toArray(config.actionItems).length
        ? config.actionItems
        : defaultBadgeActions;

    window.WEBSPELLCHECKER_CONFIG = {
        autoSearch: toBoolean(config.autoSearch, true),
        appType: config.appType || 'moodle_plugin',
        serviceProtocol: config.serviceProtocol || 'https',
        serviceHost: config.serviceHost || 'svc.webspellchecker.net',
        servicePath: config.servicePath || 'api',
        servicePort: config.servicePort || '443',
        enableGrammar: toBoolean(config.enableGrammar, false),
        aiWritingAssistant: toBoolean(config.aiWritingAssistant, false),
        settingsSections: toArray(config.settingsSections),
        serviceId: config.serviceId,
        lang: config.lang,
        enableBadgeButton: isBadgeEnabled,
        actionItems: badgeActions,
        disableAutoSearchIn: toArray(config.disableAutoSearchIn),
        disableOptionsStorage: toArray(config.disableOptionsStorage),
        disableDictionariesPreferences: toBoolean(config.disableDictionariesPreferences, false),
        autocomplete: toBoolean(config.autocomplete, false),
        autocorrect: toBoolean(config.autocorrect, false),
        spellingSuggestions: toBoolean(config.spellingSuggestions, true),
        grammarSuggestions: toBoolean(config.grammarSuggestions, false),
        styleGuideSuggestions: toBoolean(config.styleGuideSuggestions, true),
        ignoreAllCapsWords: toBoolean(config.ignoreAllCapsWords, true),
        ignoreDomainNames: toBoolean(config.ignoreDomainNames, true),
        ignoreWordsWithMixedCases: toBoolean(config.ignoreWordsWithMixedCases, true),
        ignoreWordsWithNumbers: toBoolean(config.ignoreWordsWithNumbers, true),
        globalBadge: toBoolean(config.globalBadge, true),
        compactBadge: toBoolean(config.compactBadge, true),
        allSuggestionsMode: toBoolean(config.allSuggestionsMode, true),
        onLoad: function() {
            const instance = this;
            try {
                this.subscribe('replaceProblem', () => {
                    try {
                        const element = instance.getContainerNode();
                        element.dispatchEvent(new Event('input', {bubbles: true}));
                    } catch (e) {
                        // The container may have been detached by the host editor; safe to ignore.
                    }
                });
            } catch (e) {
                // Older bundles may not expose subscribe; ignore.
            }
        },
    };

    if (Array.isArray(config.generalOptions) && config.generalOptions.length) {
        window.WEBSPELLCHECKER_CONFIG.generalOptions = config.generalOptions;
    }
};
