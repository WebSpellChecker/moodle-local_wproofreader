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

import {notifyField} from 'local_wproofreader/notify';

// Maps the HTTP status codes documented at
// https://docs.wproofreader.com/api-reference/http-response-status-codes to
// the config message key carrying the corresponding warning text. 503 reuses
// the existing service-unavailable message - same condition, whether reported
// as a transport failure or a real HTTP response.
const STATUS_MESSAGE_KEYS = {
    '400': 'runtimeErrorBadRequestMessage',
    '403': 'runtimeErrorForbiddenMessage',
    '404': 'runtimeErrorNotFoundMessage',
    '409': 'runtimeErrorConflictMessage',
    '500': 'runtimeErrorServerMessage',
    '503': 'runtimeServiceUnavailableMessage',
};

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
        appType: config.appType,
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
        // Plain textareas are attached explicitly by
        // local_wproofreader/environment_textarea instead of the bundle's
        // own autoSearch, which only scans once at load and misses fields
        // revealed later (e.g. an mform "Show more" advanced section).
        disableAutoSearchIn: [...toArray(config.disableAutoSearchIn), 'textarea'],
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
        globalBadge: toBoolean(config.globalBadge, false),
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
        onErrorRequest: (error, instance) => {
            const key = STATUS_MESSAGE_KEYS[error && error.status];
            const message = key && config[key];
            if (!message) {
                return;
            }

            try {
                notifyField(instance.getContainerNode(), message);
            } catch (e) {
                // The container may have been detached by the host editor; safe to ignore.
            }
        },
    };

    if (Array.isArray(config.generalOptions) && config.generalOptions.length) {
        window.WEBSPELLCHECKER_CONFIG.generalOptions = config.generalOptions;
    }
};
