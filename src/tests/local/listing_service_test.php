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
// along with Moodle.  If not, see <http://gnu.org>.

/**
 * Pruebas unitarias para listing_service::delete_listing().
 *
 * @package    local_chuspasocial
 * @category   test
 * @copyright  2026 sis-inf
 * @license    http://gnu.org GNU GPL v3 or later
 */

namespace local_chuspasocial\local;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * Clase de pruebas para el servicio de anuncios.
 *
 * @package    local_chuspasocial
 * @category   test
 * @copyright  2026 sis-inf
 * @license    http://gnu.org GNU GPL v3 or later
 * @covers     \local_chuspasocial\local\listing_service::delete_listing
 */
class listing_service_test extends advanced_testcase {

    /**
     * Configuración inicial antes de cada prueba.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Prueba que el autor original de un anuncio puede borrarlo con éxito.
     */
    public function test_delete_listing_as_author(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $author = $generator->create_user();

        $plugingenerator = $generator->get_plugin_generator('local_chuspasocial');
        $listing = $plugingenerator->create_listing(['userid' => $author->id]);

        $this->setUser($author);

        $result = listing_service::delete_listing($listing->id);
        $this->assertTrue($result);

        $this->assertFalse($DB->record_exists('local_chuspasocial_listing', ['id' => $listing->id]));
    }

    /**
     * Prueba que un usuario con rol de moderador puede borrar cualquier anuncio.
     */
    public function test_delete_listing_as_moderator(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $author = $generator->create_user();
        $moderator = $generator->create_user();

        $plugingenerator = $generator->get_plugin_generator('local_chuspasocial');
        $listing = $plugingenerator->create_listing(['userid' => $author->id]);

        $roleid = $generator->create_role();
        assign_capability('local/chuspasocial:moderate', CAP_ALLOW, $roleid, \context_system::instance()->id);
        role_assign($roleid, $moderator->id, \context_system::instance()->id);

        $this->setUser($moderator);

        $result = listing_service::delete_listing($listing->id);
        $this->assertTrue($result);

        $this->assertFalse($DB->record_exists('local_chuspasocial_listing', ['id' => $listing->id]));
    }

    /**
     * Prueba que al borrar un anuncio también se eliminan sus vínculos asociados.
     */
    public function test_delete_listing_removes_links(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $author = $generator->create_user();
        $course = $generator->create_course();

        $plugingenerator = $generator->get_plugin_generator('local_chuspasocial');
        $listing = $plugingenerator->create_listing(['userid' => $author->id]);

        $linkid = $DB->insert_record('local_chuspasocial_lstcourse', (object) [
            'listingid' => $listing->id,
            'courseid' => $course->id,
            'timecreated' => time()
        ]);

        $this->setUser($author);

        $result = listing_service::delete_listing($listing->id);
        $this->assertTrue($result);

        $this->assertFalse($DB->record_exists('local_chuspasocial_listing', ['id' => $listing->id]));
        $this->assertFalse($DB->record_exists('local_chuspasocial_lstcourse', ['id' => $linkid]));
    }

    /**
     * Crea un anuncio de libro de prueba.
     *
     * @param string $title Título del libro.
     * @param string $author Autor del libro.
     * @return int Id del anuncio creado.
     */
    private function crear_anuncio(string $title, string $author): int {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        return $DB->insert_record('local_chuspasocial_listing', (object) [
            'userid' => $user->id,
            'title' => $title,
            'author' => $author,
            'status' => 'available',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Busca por título.
     *
     * @covers \local_chuspasocial\local\listing_service::search
     */
    public function test_search_por_titulo(): void {
        $id = $this->crear_anuncio('Cálculo I', 'Stewart');
        $this->crear_anuncio('Física', 'Serway');

        $result = listing_service::search('Cálculo', 0, 10);

        $this->assertCount(1, $result);
        $this->assertEquals($id, $result[0]->id);
    }

    /**
     * Busca por autor.
     *
     * @covers \local_chuspasocial\local\listing_service::search
     */
    public function test_search_por_autor(): void {
        $id = $this->crear_anuncio('Física', 'Serway');
        $this->crear_anuncio('Cálculo I', 'Stewart');

        $result = listing_service::search('Serway', 0, 10);

        $this->assertCount(1, $result);
        $this->assertEquals($id, $result[0]->id);
    }

    /**
     * La búsqueda no distingue mayúsculas de minúsculas.
     *
     * @covers \local_chuspasocial\local\listing_service::search
     */
    public function test_search_ignora_mayusculas(): void {
        $id = $this->crear_anuncio('Programación Web', 'Pérez');

        $result = listing_service::search('PROGRAMACIÓN web', 0, 10);

        $this->assertCount(1, $result);
        $this->assertEquals($id, $result[0]->id);
    }

    /**
     * Los caracteres especiales se buscan como texto normal.
     *
     * @covers \local_chuspasocial\local\listing_service::search
     */
    public function test_search_caracteres_especiales(): void {
        $id = $this->crear_anuncio('100% Álgebra', 'Baldor');
        $this->crear_anuncio('100 problemas', 'Gómez');

        $result = listing_service::search('100%', 0, 10);

        $this->assertCount(1, $result);
        $this->assertEquals($id, $result[0]->id);
    }

    /**
     * Test case: Vincular cursos correctamente a un listado.
     *
     * @covers \local_chuspasocial\local\listing_service::set_courses
     */
    public function test_set_courses_vincular(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $listingid = $this->crear_anuncio('Libro de Redes', 'Tanenbaum');

        listing_service::set_courses($listingid, [ $course1->id, $course2->id ]);

        $this->assertTrue($DB->record_exists('local_chuspasocial_lstcourse', [ 'listingid' => $listingid, 'courseid' => $course1->id ]));
        $this->assertTrue($DB->record_exists('local_chuspasocial_lstcourse', [ 'listingid' => $listingid, 'courseid' => $course2->id ]));
    }

    /**
     * Test case: Reemplazar cursos existentes por un nuevo conjunto de cursos.
     *
     * @covers \local_chuspasocial\local\listing_service::set_courses
     */
    public function test_set_courses_reemplazar(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $listingid = $this->crear_anuncio('Libro de Base de Datos', 'Date');

        listing_service::set_courses($listingid, [ $course1->id ]);
        listing_service::set_courses($listingid, [ $course2->id ]);

        $this->assertFalse($DB->record_exists('local_chuspasocial_lstcourse', [ 'listingid' => $listingid, 'courseid' => $course1->id ]));
        $this->assertTrue($DB->record_exists('local_chuspasocial_lstcourse', [ 'listingid' => $listingid, 'courseid' => $course2->id ]));
    }

    /**
     * Test case: Intentar vincular cursos a un listado inexistente (debe lanzar una excepción).
     *
     * @covers \local_chuspasocial\local\listing_service::set_courses
     */
    public function test_set_courses_inexistente(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $invalidlistingid = 99999;

        $this->expectException(\moodle_exception::class);
        listing_service::set_courses($invalidlistingid, [ $course->id ]);
    }

    /**
     * Solo devuelve anuncios vinculados a las materias del usuario.
     *
     * @covers \local_chuspasocial\local\listing_service::get_for_my_courses
     */
    public function test_get_for_my_courses_solo_mis_materias(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $mycourse = $generator->create_course();
        $othercourse = $generator->create_course();
        $generator->enrol_user($user->id, $mycourse->id, 'student');

        $mylistingid = $this->crear_anuncio('Calculo', 'Stewart');
        $otherlistingid = $this->crear_anuncio('Fisica', 'Serway');
        $DB->insert_record('local_chuspasocial_lstcourse', (object) [
            'listingid' => $mylistingid,
            'courseid' => $mycourse->id,
        ]);
        $DB->insert_record('local_chuspasocial_lstcourse', (object) [
            'listingid' => $otherlistingid,
            'courseid' => $othercourse->id,
        ]);
        $this->setUser($user);

        $result = listing_service::get_for_my_courses(0, 10);

        $this->assertCount(1, $result);
        $this->assertEquals($mylistingid, $result[0]->id);
    }

    /**
     * Excluye vendidos aunque pertenezcan a mis materias.
     *
     * @covers \local_chuspasocial\local\listing_service::get_for_my_courses
     */
    public function test_get_for_my_courses_excluye_vendidos(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $generator->enrol_user($user->id, $course->id, 'student');

        $availableid = $this->crear_anuncio('Libro disponible', 'Autor A');
        $soldid = $this->crear_anuncio('Libro vendido', 'Autor B');
        $DB->set_field('local_chuspasocial_listing', 'status', 'sold', ['id' => $soldid]);
        foreach ([$availableid, $soldid] as $listingid) {
            $DB->insert_record('local_chuspasocial_lstcourse', (object) [
                'listingid' => $listingid,
                'courseid' => $course->id,
            ]);
        }
        $this->setUser($user);

        $result = listing_service::get_for_my_courses(0, 10);

        $this->assertCount(1, $result);
        $this->assertEquals($availableid, $result[0]->id);
    }

    /**
     * No repite un anuncio vinculado a dos de mis materias.
     *
     * @covers \local_chuspasocial\local\listing_service::get_for_my_courses
     */
    public function test_get_for_my_courses_sin_duplicados(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $generator->enrol_user($user->id, $course1->id, 'student');
        $generator->enrol_user($user->id, $course2->id, 'student');

        $listingid = $this->crear_anuncio('Algebra', 'Baldor');
        foreach ([$course1->id, $course2->id] as $courseid) {
            $DB->insert_record('local_chuspasocial_lstcourse', (object) [
                'listingid' => $listingid,
                'courseid' => $courseid,
            ]);
        }
        $this->setUser($user);

        $result = listing_service::get_for_my_courses(0, 10);

        $this->assertCount(1, $result);
        $this->assertEquals($listingid, $result[0]->id);
    }

    /**
     * El autor puede actualizar su anuncio.
     *
     * @covers \local_chuspasocial\local\listing_service::update_listing
     */
    public function test_update_listing_as_author(): void {
        global $DB;

        $listingid = $this->crear_anuncio('Titulo original', 'Autor de prueba');
        $authorid = $DB->get_field(
            'local_chuspasocial_listing',
            'userid',
            ['id' => $listingid],
            MUST_EXIST
        );
        $this->setUser((int) $authorid);

        $updated = listing_service::update_listing(
            $listingid,
            (object) ['title' => 'Titulo actualizado']
        );

        $this->assertSame('Titulo actualizado', $updated->get('title'));
        $this->assertSame(
            'Titulo actualizado',
            $DB->get_field(
                'local_chuspasocial_listing',
                'title',
                ['id' => $listingid],
                MUST_EXIST
            )
        );
    }

    /**
     * Otro usuario no puede actualizar un anuncio ajeno.
     *
     * @covers \local_chuspasocial\local\listing_service::update_listing
     */
    public function test_update_listing_as_other_user(): void {
        global $DB;

        $listingid = $this->crear_anuncio('Titulo original', 'Autor de prueba');
        $otheruser = $this->getDataGenerator()->create_user();
        $this->setUser($otheruser);

        try {
            listing_service::update_listing(
                $listingid,
                (object) ['title' => 'Cambio no permitido']
            );
            $this->fail('Se esperaba una excepcion de permisos.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('nopermissions', $exception->errorcode);
        }

        $this->assertSame(
            'Titulo original',
            $DB->get_field(
                'local_chuspasocial_listing',
                'title',
                ['id' => $listingid],
                MUST_EXIST
            )
        );
    }

    /**
     * El dueño del anuncio lo marca como vendido.
     *
     * @covers \local_chuspasocial\local\listing_service::mark_sold
     */
    public function test_mark_sold_como_dueno(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $owner = $generator->create_user();
        $plugingenerator = $generator->get_plugin_generator('local_chuspasocial');
        $listing = $plugingenerator->create_listing(['userid' => $owner->id]);

        $this->setUser($owner);

        listing_service::mark_sold($listing->id);

        $status = $DB->get_field('local_chuspasocial_listing', 'status', ['id' => $listing->id]);
        $this->assertEquals('sold', $status);
    }

    /**
     * Otro usuario no puede marcar como vendido un anuncio ajeno.
     *
     * @covers \local_chuspasocial\local\listing_service::mark_sold
     */
    public function test_mark_sold_otro_usuario(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $owner = $generator->create_user();
        $other = $generator->create_user();
        $plugingenerator = $generator->get_plugin_generator('local_chuspasocial');
        $listing = $plugingenerator->create_listing(['userid' => $owner->id]);

        $this->setUser($other);

        try {
            listing_service::mark_sold($listing->id);
            $this->fail('Se esperaba una excepción al marcar un anuncio ajeno.');
        } catch (\moodle_exception $e) {
            // Excepción esperada.
        }

        $status = $DB->get_field('local_chuspasocial_listing', 'status', ['id' => $listing->id]);
        $this->assertNotEquals('sold', $status);
    }
}
