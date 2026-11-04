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
 * External function local_chuspasocial_toggle_follow.
 *
 * @package    local_chuspasocial
 * @copyright  2026 SIS-INF
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_chuspasocial\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use local_chuspasocial\local\follow_service;

/**
 * Follows or unfollows a user or a tag for the current user.
 *
 * @package    local_chuspasocial
 * @copyright  2026 SIS-INF
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toggle_follow extends external_api {

    /**
     * Describes the parameters of the function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component' => new external_value(PARAM_ALPHA, 'Type of item to follow: user or tag.'),
            'itemid' => new external_value(PARAM_INT, 'Id of the user or of the tag.'),
        ]);
    }

    /**
     * Follows the item if the current user does not follow it yet, and unfollows it otherwise.
     *
     * @param string $component Type of item: user or tag.
     * @param int $itemid Id of the user or of the tag.
     * @return array
     */
    public static function execute(string $component, int $itemid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'itemid' => $itemid,
        ]);
        $component = $params['component'];
        $itemid = $params['itemid'];

        if (!in_array($component, ['user', 'tag'], true)) {
            throw new invalid_parameter_exception('The component must be user or tag.');
        }

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/chuspasocial:follow', $context);
    if ($component === 'user') {
    // 1. Rechazar el auto-seguimiento
        if ((int)$USER->id === (int)$itemid) {
            throw new invalid_parameter_exception('Cannot follow yourself.');
    }

    // 2. Validar que el usuario objetivo exista y esté activo (no borrado ni suspendido)
    $DB->get_record('user', [
        'id' => $itemid,
        'deleted' => 0,
        'suspended' => 0
    ], 'id', MUST_EXIST);
}
        $following = $DB->record_exists('local_chuspasocial_follow', [
            'userid' => $USER->id,
            'itemtype' => $component,
            'itemid' => $itemid,
        ]);

        if ($following) {
            if ($component === 'user') {
                follow_service::unfollow_user($itemid);
            } else {
                follow_service::unfollow_tag($itemid);
            }
            $action = 'unfollowed';
            $message = get_string('follow', 'local_chuspasocial');
        } else {
            if ($component === 'user') {
                follow_service::follow_user($itemid);
            } else {
                $DB->get_record('tag', ['id' => $itemid], 'id', MUST_EXIST);
                follow_service::follow_tag($itemid);
            }
            $action = 'followed';
            $message = get_string('following', 'local_chuspasocial');
        }

        return [
            'status' => true,
            'action' => $action,
            'message' => $message,
        ];
    }

    /**
     * Describes the return value of the function.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Whether the operation was done.'),
            'action' => new external_value(PARAM_ALPHA, 'Result of the operation: followed or unfollowed.'),
            'message' => new external_value(PARAM_TEXT, 'Text that describes the new state.'),
        ]);
    }
}
