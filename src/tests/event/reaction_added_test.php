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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

namespace local_chuspasocial\event;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_chuspasocial\local\reaction_service;

/**
 * Pruebas para el evento reaction_added.
 *
 * @package    local_chuspasocial
 * @category   test
 * @copyright  2026 SIS-INF
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_chuspasocial\event\reaction_added
 */
class reaction_added_test extends advanced_testcase {

    /**
     * Configuracion previa a cada test.
     */
    protected function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Verifica que reaccionar dispare exactamente un evento
     * reaction_added con el objectid correcto.
     */
    public function test_reaccion_dispara_un_evento_reaction_added(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $autor = $generator->create_user();
        $usuario = $generator->create_user();

        $post = new \stdClass();
        $post->userid = $autor->id;
        $post->courseid = 0;
        $post->type = 'post';
        $post->content = 'Post de prueba';
        $post->contentformat = FORMAT_PLAIN;
        $post->hidden = 0;
        $post->timecreated = time();
        $post->timemodified = time();

        $postid = $DB->insert_record('local_chuspasocial_post', $post);

        $this->setUser($usuario);

        $sink = $this->redirectEvents();

        $resultado = reaction_service::toggle($postid);

        $events = $sink->get_events();
        $sink->close();

        $creados = array_values(array_filter($events, function ($event) {
            return $event instanceof reaction_added;
        }));

        $this->assertTrue($resultado);
        $this->assertCount(1, $creados);

        $reaction = $DB->get_record('local_chuspasocial_reaction', [
            'postid' => $postid,
            'userid' => $usuario->id,
        ], '*', MUST_EXIST);

        $event = $creados[0];

        $this->assertEquals($reaction->id, $event->objectid);
        $this->assertEquals('local_chuspasocial_reaction', $event->objecttable);
        $this->assertEquals($usuario->id, $event->userid);
        $this->assertEquals('like', $event->other['reactiontype']);
        $this->assertEquals(\context_system::instance()->id, $event->contextid);
        $this->assertEventContextNotUsed($event);
    }
}
