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

/**
 * Lib functions for local_chuspasocial.
 *
 * @package    local_chuspasocial
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the «Wall» link to the navigation of the course.
 *
 * The link goes to course.php, the wall of the course, and it is only added
 * when the user has the local/chuspasocial:view capability in the course.
 *
 * @param navigation_node $navigation The course navigation node.
 * @param stdClass $course The course.
 * @param context_course $context The context of the course.
 * @return void
 */
function local_chuspasocial_extend_navigation_course(
    navigation_node $navigation,
    stdClass $course,
    context_course $context
): void {
    if (!has_capability('local/chuspasocial:view', $context)) {
        return;
    }

    $url = new moodle_url('/local/chuspasocial/course.php', ['id' => $course->id]);
    $navigation->add(
        get_string('wall', 'local_chuspasocial'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_chuspasocial_wall',
        new pix_icon('i/group', '')
    );
}

/**
 * Serves the images stored in the postimage file area of the posts.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param context $context The context of the file.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments of the file path.
 * @param bool $forcedownload Whether the file should be forced to download.
 * @param array $options Additional options for the file.
 * @return bool False if the file can not be served.
 */
function local_chuspasocial_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
): bool {
    global $USER;

    if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    if ($filearea === 'listingphoto') {
        require_login();
        require_capability('local/chuspasocial:viewmarketplace', $context);

        $itemid = (int) array_shift($args);
        $filename = array_pop($args);
        $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'local_chuspasocial', $filearea, $itemid, $filepath, $filename);
        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 0, 0, $forcedownload, $options);
    }

    if ($filearea !== 'postimage') {
        return false;
    }

    require_login($course, false, $cm);

    $itemid = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_chuspasocial', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    $post = \local_chuspasocial\local\persistent\post::get_record(['id' => $itemid]);
    if (!$post || !\local_chuspasocial\local\post_service::can_view($post, (int) $USER->id)) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
