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
 * Shared constants for the WProofreader AMD modules.
 *
 * @module     local_wproofreader/constants
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The bundle's own `instance_attribute` option (confirmed in wscbundle.js's
 * OptionsManager defaults). It sets this to "true" on the container it
 * directly manages once an instance is attached. For a plain textarea
 * (autoSearch-attached, container === the textarea) that is a reliable
 * outer-page signal. For TinyMCE the container we pass is the outer
 * iframe, but the bundle tags something inside that iframe's own document
 * instead, so this never appears on the iframe element itself - Atto and
 * TinyMCE rely on ATTACHED_ATTR below instead.
 */
export const INSTANCE_ATTR = 'data-wsc-instance';
export const INSTANCE_ATTR_VALUE = 'true';

/**
 * Our own marker, set immediately before calling WEBSPELLCHECKER.init() and
 * kept afterwards on success (only removed if that call throws). Used both
 * to stop the MutationObserver re-attempting an attach already in flight,
 * and - for Atto/TinyMCE specifically - as the outer-page-queryable "this
 * instance is attached" signal that INSTANCE_ATTR cannot reliably provide.
 */
export const ATTACHED_ATTR = 'data-wsc-attached';
