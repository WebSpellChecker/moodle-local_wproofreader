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
 * Settings-page helper: fetches the live language list from the WebSpellChecker
 * service and posts it back to the server for caching, so the language dropdown
 * stays current.
 *
 * @module     local_wproofreader/settings_page
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {loadBundle} from 'local_wproofreader/bundle_loader';

const SERVICE_READY_TIMEOUT_MS = 10000;
const SERVICE_READY_POLL_MS = 100;
const LANGUAGE_CODE_PATTERN = /^[a-zA-Z]{2,3}(_[a-zA-Z]{2,4})?$/;
const DROPDOWN_SELECTOR = 'select[name="s_local_wproofreader_slang"]';

const waitForService = () => new Promise((resolve) => {
    const start = Date.now();
    const tick = () => {
        if (window.WEBSPELLCHECKER && typeof window.WEBSPELLCHECKER.initWebApi === 'function') {
            resolve(true);
            return;
        }
        if (Date.now() - start > SERVICE_READY_TIMEOUT_MS) {
            resolve(false);
            return;
        }
        setTimeout(tick, SERVICE_READY_POLL_MS);
    };
    tick();
});

const sendLanguagesToServer = (payload) => {
    const requests = Ajax.call([{
        methodname: 'local_wproofreader_save_languages',
        args: {payload: typeof payload === 'string' ? payload : JSON.stringify(payload)},
    }]);

    return requests[0].catch(Notification.exception);
};

const mergeLanguageList = (result) => {
    const langlist = result && result.langList;
    if (!langlist || typeof langlist !== 'object') {
        return {};
    }

    const merged = {};
    ['ltr', 'rtl'].forEach((direction) => {
        const entries = langlist[direction];
        if (!entries || typeof entries !== 'object') {
            return;
        }
        Object.keys(entries).forEach((code) => {
            if (typeof code === 'string'
                && typeof entries[code] === 'string'
                && LANGUAGE_CODE_PATTERN.test(code)) {
                merged[code] = entries[code];
            }
        });
    });

    return merged;
};

const updateLanguageDropdown = (result) => {
    const select = document.querySelector(DROPDOWN_SELECTOR);
    if (!select) {
        return;
    }

    const merged = mergeLanguageList(result);
    const codes = Object.keys(merged).sort();
    if (codes.length === 0) {
        return;
    }

    const currentValue = select.value;
    while (select.firstChild) {
        select.removeChild(select.firstChild);
    }

    codes.forEach((code) => {
        const option = document.createElement('option');
        option.value = code;
        option.textContent = merged[code];
        if (code === currentValue) {
            option.selected = true;
        }
        select.appendChild(option);
    });
};

/**
 * Initialize the settings page helper.
 *
 * @param {Object} config Service config supplied by PHP.
 * @returns {Promise<void>}
 */
export const init = async(config) => {
    if (!config || !config.bundleUrl) {
        return;
    }

    await loadBundle(config.bundleUrl);

    const ready = await waitForService();
    if (!ready) {
        return;
    }

    let app;
    try {
        app = window.WEBSPELLCHECKER.initWebApi({
            autoSearch: false,
            serviceProtocol: config.serviceProtocol || 'https',
            serviceHost: config.serviceHost || 'svc.webspellchecker.net',
            servicePath: config.servicePath || 'api',
            servicePort: config.servicePort || '443',
            serviceId: config.serviceId,
            lang: config.lang,
            enableGrammar: config.enableGrammar === true || config.enableGrammar === 'true',
            appType: config.appType || 'moodle_plugin',
        });
    } catch (e) {
        return;
    }

    if (!app || typeof app.getInfo !== 'function') {
        return;
    }

    app.getInfo({
        success: (result) => {
            updateLanguageDropdown(result);
            sendLanguagesToServer(result);
        },
        error: () => {},
    });
};
