<?php
// This file is part of Moodle - http://moodle.org
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
 * Generador de datos de prueba para ChuspaSocial.
 *
 * @package    local_chuspasocial
 * @category   test
 * @copyright  2026 sis-inf
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Clase generadora de datos de prueba para el componente.
 *
 * @package    local_chuspasocial
 * @category   test
 * @copyright  2026 sis-inf
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class local_chuspasocial_generator extends component_generator_base {
    /**
     * Creates a reaction for testing.
     *
     * @param array $record Reaction data.
     * @return \local_chuspasocial\local\persistent\reaction
     */
    public function create_reaction(array $record = []): \local_chuspasocial\local\persistent\reaction {
        global $DB;

        if (empty($record['userid'])) {
            $user = $this->datagenerator->create_user();
            $record['userid'] = $user->id;
        }

        if (empty($record['postid'])) {
            $postid = $DB->insert_record('local_chuspasocial_post', (object) [
                'userid' => $record['userid'],
                'courseid' => 0,
                'type' => 'post',
                'content' => 'Publicacion de prueba',
                'contentformat' => FORMAT_HTML,
                'hidden' => 0,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);

            $record['postid'] = $postid;
        }

        $record['type'] = $record['type'] ?? \local_chuspasocial\local\persistent\reaction::TYPE_LIKE;
        $record['timecreated'] = $record['timecreated'] ?? time();

        $reaction = new \local_chuspasocial\local\persistent\reaction(0, (object) $record);
        $reaction->create();

        return $reaction;
    }
}

