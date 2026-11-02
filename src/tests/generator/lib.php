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

/**
 * Data generator for local_chuspasocial.
 *
 * @package    local_chuspasocial
 * @category   test
 * @copyright  2026 SIS-INF
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_chuspasocial\local\persistent\listing;

/**
 * Data generator class for local_chuspasocial.
 */
class local_chuspasocial_generator extends component_generator_base {
    /** @var int Number of listings created. */
    protected $listingcount = 0;

    /**
     * Create a listing record for tests.
     *
     * @param array $record Optional values that override the defaults.
     * @return listing The persistent instance.
     */
    public function create_listing(array $record = []): listing {
        global $USER;

        $this->listingcount++;
        $i = $this->listingcount;

        $defaults = [
            'userid' => $USER->id ?? 0,
            'title' => 'Listing ' . $i,
            'author' => 'Author ' . $i,
            'description' => 'Description for listing ' . $i,
            'descriptionformat' => FORMAT_HTML,
            'price' => 10.0,
            'externalcontact' => null,
            'status' => listing::STATUS_AVAILABLE,
        ];

        $record = array_merge($defaults, $record);

        $listing = new listing(0, (object) $record);
        $listing->create();

        return $listing;
    }
}
