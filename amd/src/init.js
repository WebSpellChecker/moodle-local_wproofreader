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
 * WProofreader main entry point.
 *
 * Applies global configuration, dynamically loads the WebSpellChecker bundle,
 * and dispatches to the editor-specific environments once the bundle is ready.
 *
 * @module     local_wproofreader/init
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Config from 'local_wproofreader/proofreader_config';
import * as AttoEnv from 'local_wproofreader/environment_atto';
import * as TinyMceEnv from 'local_wproofreader/environment_tinymce';
import * as TextareaEnv from 'local_wproofreader/environment_textarea';
import {loadBundle} from 'local_wproofreader/bundle_loader';

/**
 * Boot WProofreader on the current page.
 *
 * @param {Object} config Server-side configuration payload.
 * @returns {Promise<void>}
 */
export const init = async(config) => {
    if (!config || typeof config !== 'object') {
        return;
    }

    Config.apply(config);
    await loadBundle(config.bundleUrl);

    if (!window.WEBSPELLCHECKER) {
        return;
    }

    TextareaEnv.init(config);
    AttoEnv.init(config);
    TinyMceEnv.init(config);
};
