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

namespace local_chuspasocial\event;

use advanced_testcase;
use local_chuspasocial\local\listing_service;

/**
 * Pruebas unitarias para el evento listing_created.
 *
 * @package    local_chuspasocial
 * @category   test
 * @copyright  2026 SIS-INF
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_chuspasocial\event\listing_created
 */
class listing_created_test extends advanced_testcase {
    /**
     * Configuracion previa a cada test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('enablemarketplace', 1, 'local_chuspasocial');
    }

    /**
     * Test: Publicar un libro dispara exactamente un evento listing_created
     * con el objectid correcto.
     */
    public function test_publicar_libro_dispara_un_evento_listing_created(): void {
        $autor = $this->getDataGenerator()->create_user();
        $this->setUser($autor);

        $data = new \stdClass();
        $data->title = 'Libro de prueba';
        $data->author = 'Autor de prueba';
        $data->description = 'Descripcion de prueba';
        $data->price = 25.50;

        $sink = $this->redirectEvents();

        $listing = listing_service::create_listing($data);

        $events = $sink->get_events();
        $sink->close();

        $creados = array_values(array_filter($events, function ($event) {
            return $event instanceof listing_created;
        }));

        // Exactamente un evento listing_created.
        $this->assertCount(1, $creados);

        $event = $creados[0];

        // El objectid es el id del libro creado.
        $this->assertEquals($listing->get('id'), $event->objectid);
        $this->assertEquals('local_chuspasocial_listing', $event->objecttable);
        $this->assertEquals($autor->id, $event->userid);
        $this->assertEventContextNotUsed($event);
    }
}
