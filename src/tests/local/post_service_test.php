<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_chuspasocial\local;

use advanced_testcase;

/**
 * Pruebas unitarias para el metodo can_post_announcement.
 *
 * @package    local_chuspasocial
 * @category   test
 * @copyright  2026 SIS-INF
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_chuspasocial\local\post_service::can_post_announcement
 */
class post_service_test extends advanced_testcase {
    /**
     * Configuracion previa a cada test.
     */
    protected function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Test: Un docente puede publicar anuncios en su curso.
     */
    public function test_can_post_announcement_docente() {
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'editingteacher');

        $this->setUser($user);

        $result = post_service::can_post_announcement($course->id);
        $this->assertTrue($result);
    }

    /**
     * Test: Un estudiante No puede publicar anuncios.
     */
    public function test_can_post_announcement_estudiante(): void {
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');

        $this->setUser($user);

        $result = post_service::can_post_announcement($course->id);
        $this->assertFalse($result);
    }

    /**
     * Test: Un administrador puede publicar anuncios en el muro general (site course / frontpage).
     */
    public function test_can_post_announcement_admin_muro_general(): void {
        global $SITE;

        $this->setAdminUser();

        $result = post_service::can_post_announcement($SITE->id);
        $this->assertTrue($result);
    }

    /**
     * Create a post record in a course for the given user.
     *
     * @param int $userid Author of the post.
     * @param int $courseid Course where the post is published.
     * @return int Id of the new post.
     */
    private function create_test_post(int $userid, int $courseid): int {
        global $DB;

        return (int) $DB->insert_record('local_chuspasocial_post', (object) [
            'userid' => $userid,
            'courseid' => $courseid,
            'type' => 'post',
            'content' => 'Test post',
            'contentformat' => FORMAT_HTML,
            'hidden' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Register the moderate capability (not yet defined in db/access.php)
     * and give it to a new user through a role at system level.
     *
     * @return \stdClass The moderator user.
     */
    private function create_moderator(): \stdClass {
        global $DB;

        if (!get_capability_info('local/chuspasocial:moderate')) {
            $DB->insert_record('capabilities', (object) [
                'name' => 'local/chuspasocial:moderate',
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'component' => 'local_chuspasocial',
                'riskbitmask' => 0,
            ]);
            \cache::make('core', 'capabilities')->purge();
        }

        $moderator = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $systemcontext = \context_system::instance();
        assign_capability('local/chuspasocial:moderate', CAP_ALLOW, $roleid, $systemcontext->id);
        role_assign($roleid, $moderator->id, $systemcontext->id);

        return $moderator;
    }

    /**
     * The author can delete their own post.
     *
     * @covers \local_chuspasocial\local\post_service::delete_post
     */
    public function test_delete_post_by_author(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $author = $generator->create_user();
        $generator->enrol_user($author->id, $course->id, 'student');
        $postid = $this->create_test_post($author->id, $course->id);

        $this->setUser($author);
        post_service::delete_post($postid);

        $this->assertFalse($DB->record_exists('local_chuspasocial_post', ['id' => $postid]));
    }

    /**
     * A moderator can delete a post of another user.
     *
     * @covers \local_chuspasocial\local\post_service::delete_post
     */
    public function test_delete_post_by_moderator(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $author = $generator->create_user();
        $generator->enrol_user($author->id, $course->id, 'student');
        $postid = $this->create_test_post($author->id, $course->id);
        $moderator = $this->create_moderator();

        $this->setUser($moderator);
        post_service::delete_post($postid);

        $this->assertFalse($DB->record_exists('local_chuspasocial_post', ['id' => $postid]));
    }

    /**
     * Another user who is not the author nor a moderator cannot delete the post.
     *
     * @covers \local_chuspasocial\local\post_service::delete_post
     */
    public function test_delete_post_by_other_user_throws_exception(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $author = $generator->create_user();
        $other = $generator->create_user();
        $generator->enrol_user($author->id, $course->id, 'student');
        $generator->enrol_user($other->id, $course->id, 'student');
        $postid = $this->create_test_post($author->id, $course->id);

        $this->setUser($other);
        try {
            post_service::delete_post($postid);
            $this->fail('Expected moodle_exception was not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertEquals('nopermissions', $e->errorcode);
        }

        $this->assertTrue($DB->record_exists('local_chuspasocial_post', ['id' => $postid]));
    }

    /**
     * Deleting a post also deletes its comments, reactions, files and tags.
     *
     * @covers \local_chuspasocial\local\post_service::delete_post
     */
    public function test_delete_post_cascade_deletes_related_data(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $author = $generator->create_user();
        $other = $generator->create_user();
        $generator->enrol_user($author->id, $course->id, 'student');
        $generator->enrol_user($other->id, $course->id, 'student');
        $postid = $this->create_test_post($author->id, $course->id);
        $context = \context_course::instance($course->id);

        $DB->insert_record('local_chuspasocial_comment', (object) [
            'postid' => $postid,
            'userid' => $other->id,
            'content' => 'Test comment',
            'contentformat' => FORMAT_HTML,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $DB->insert_record('local_chuspasocial_reaction', (object) [
            'postid' => $postid,
            'userid' => $other->id,
            'type' => 'like',
            'timecreated' => time(),
        ]);
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'local_chuspasocial',
            'filearea' => 'post',
            'itemid' => $postid,
            'filepath' => '/',
            'filename' => 'test.txt',
        ], 'Test file');
        \core_tag_tag::set_item_tags(
            'local_chuspasocial',
            'local_chuspasocial_post',
            $postid,
            $context,
            ['testtag']
        );

        // Check that the related data exists before deleting the post.
        $this->assertEquals(1, $DB->count_records('local_chuspasocial_comment', ['postid' => $postid]));
        $this->assertEquals(1, $DB->count_records('local_chuspasocial_reaction', ['postid' => $postid]));
        $this->assertFalse(get_file_storage()->is_area_empty($context->id, 'local_chuspasocial', 'post', $postid));
        $this->assertCount(1, \core_tag_tag::get_item_tags('local_chuspasocial', 'local_chuspasocial_post', $postid));

        $this->setUser($author);
        post_service::delete_post($postid);

        $this->assertFalse($DB->record_exists('local_chuspasocial_post', ['id' => $postid]));
        $this->assertEquals(0, $DB->count_records('local_chuspasocial_comment', ['postid' => $postid]));
        $this->assertEquals(0, $DB->count_records('local_chuspasocial_reaction', ['postid' => $postid]));
        $this->assertTrue(get_file_storage()->is_area_empty($context->id, 'local_chuspasocial', 'post', $postid));
        $this->assertEmpty(\core_tag_tag::get_item_tags('local_chuspasocial', 'local_chuspasocial_post', $postid));
    }

    /**
     * The author can edit their own post and the content gets updated.
     *
     * @covers \local_chuspasocial\local\post_service::update_post
     */
    public function test_update_post_by_author(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $author = $generator->create_user();
        $generator->enrol_user($author->id, $course->id, 'student');
        $postid = $this->create_test_post($author->id, $course->id);

        $this->setUser($author);
        post_service::update_post($postid, 'Contenido editado', FORMAT_PLAIN);

        $post = $DB->get_record('local_chuspasocial_post', ['id' => $postid]);
        $this->assertEquals('Contenido editado', $post->content);
        $this->assertEquals(FORMAT_PLAIN, $post->contentformat);
    }

    /**
     * Another user who is not the author cannot edit the post.
     *
     * @covers \local_chuspasocial\local\post_service::update_post
     */
    public function test_update_post_by_other_user_throws_exception(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $author = $generator->create_user();
        $other = $generator->create_user();
        $generator->enrol_user($author->id, $course->id, 'student');
        $generator->enrol_user($other->id, $course->id, 'student');
        $postid = $this->create_test_post($author->id, $course->id);

        $this->setUser($other);
        try {
            post_service::update_post($postid, 'Contenido ajeno', FORMAT_PLAIN);
            $this->fail('Expected moodle_exception was not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertEquals('cannotupdatepost', $e->errorcode);
        }

        $post = $DB->get_record('local_chuspasocial_post', ['id' => $postid]);
        $this->assertEquals('Test post', $post->content);
    }

    /**
     * Editing a post updates its timemodified timestamp.
     *
     * @covers \local_chuspasocial\local\post_service::update_post
     */
    public function test_update_post_updates_timemodified(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $author = $generator->create_user();
        $generator->enrol_user($author->id, $course->id, 'student');
        $oldtime = time() - 10;
        $postid = (int) $DB->insert_record('local_chuspasocial_post', (object) [
            'userid' => $author->id,
            'courseid' => $course->id,
            'type' => 'post',
            'content' => 'Test post',
            'contentformat' => FORMAT_HTML,
            'hidden' => 0,
            'timecreated' => $oldtime,
            'timemodified' => $oldtime,
        ]);

        $this->setUser($author);
        post_service::update_post($postid, 'Contenido editado', FORMAT_PLAIN);

        $post = $DB->get_record('local_chuspasocial_post', ['id' => $postid]);
        $this->assertGreaterThan($oldtime, $post->timemodified);
    }

    /**
     * Devuelve los ids (int) de los posts de un feed.
     *
     * @param array $feed Resultado de get_general_feed().
     * @return int[] Ids de los posts.
     */
    private function get_feed_ids(array $feed): array {
        return array_map('intval', array_column($feed, 'id'));
    }

    /**
     * El feed incluye las publicaciones generales (courseid 0).
     *
     * @covers \local_chuspasocial\local\post_service::get_general_feed
     */
    public function test_get_general_feed_incluye_publicaciones_generales(): void {
        $user = $this->getDataGenerator()->create_user();
        $postid = $this->create_test_post($user->id, 0);

        $this->setUser($user);
        $feed = post_service::get_general_feed(0, 10);

        $this->assertContains($postid, $this->get_feed_ids($feed));
    }

    /**
     * El feed incluye las publicaciones de las materias donde el usuario esta inscrito.
     *
     * @covers \local_chuspasocial\local\post_service::get_general_feed
     */
    public function test_get_general_feed_incluye_publicaciones_de_materias_propias(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');
        $postid = $this->create_test_post($user->id, $course->id);

        $this->setUser($user);
        $feed = post_service::get_general_feed(0, 10);

        $this->assertContains($postid, $this->get_feed_ids($feed));
    }

    /**
     * El feed no incluye publicaciones de materias donde el usuario no esta inscrito.
     *
     * @covers \local_chuspasocial\local\post_service::get_general_feed
     */
    public function test_get_general_feed_excluye_publicaciones_de_materias_ajenas(): void {
        $generator = $this->getDataGenerator();
        $owncourse = $generator->create_course();
        $othercourse = $generator->create_course();
        $user = $generator->create_user();
        $author = $generator->create_user();
        $generator->enrol_user($user->id, $owncourse->id, 'student');
        $generator->enrol_user($author->id, $othercourse->id, 'student');
        $ownpostid = $this->create_test_post($user->id, $owncourse->id);
        $foreignpostid = $this->create_test_post($author->id, $othercourse->id);

        $this->setUser($user);
        $ids = $this->get_feed_ids(post_service::get_general_feed(0, 10));

        $this->assertContains($ownpostid, $ids);
        $this->assertNotContains($foreignpostid, $ids);
    }

    /**
     * El feed ordena las mas recientes primero y respeta la paginacion.
     *
     * @covers \local_chuspasocial\local\post_service::get_general_feed
     */
    public function test_get_general_feed_ordena_y_pagina(): void {
        $user = $this->getDataGenerator()->create_user();
        $first = $this->create_test_post($user->id, 0);
        $second = $this->create_test_post($user->id, 0);
        $third = $this->create_test_post($user->id, 0);

        $this->setUser($user);

        $this->assertSame([$third, $second], $this->get_feed_ids(post_service::get_general_feed(0, 2)));
        $this->assertSame([$first], $this->get_feed_ids(post_service::get_general_feed(1, 2)));
    }
}
