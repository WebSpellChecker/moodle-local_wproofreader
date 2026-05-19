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

namespace local_wproofreader\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for local_wproofreader.
 *
 * The plugin does not store any personal data inside Moodle. It does, however,
 * transmit the text being edited to the WebSpellChecker service for analysis,
 * so we document that external transfer.
 *
 * @package    local_wproofreader
 * @copyright  2026 WebSpellChecker
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadata_provider {

    /**
     * Describe data sent to the WebSpellChecker service.
     *
     * @param collection $collection Collection to populate.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
            'wproofreader_service',
            [
                'content'   => 'privacy:metadata:wproofreader_service:content',
                'language'  => 'privacy:metadata:wproofreader_service:language',
                'userip'    => 'privacy:metadata:wproofreader_service:userip',
                'useragent' => 'privacy:metadata:wproofreader_service:useragent',
            ],
            'privacy:metadata:wproofreader_service'
        );

        return $collection;
    }
}
