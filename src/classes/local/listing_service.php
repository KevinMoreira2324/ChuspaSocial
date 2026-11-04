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

/**
 * Service for managing book listings.
 *
 * @package    local_chuspasocial
 * @copyright  2026 SIS-INF
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_chuspasocial\local;

use local_chuspasocial\local\persistent\listing;
use moodle_exception;

/**
 * Service class for book listings.
 */
class listing_service {

    /**
     * Search listings by book title or author, case insensitive.
     *
     * The search text is always passed as a SQL parameter, never concatenated.
     *
     * @param string $q Text to search in the title or the author.
     * @param int $page Page number, starting at 0.
     * @param int $perpage Number of listings per page.
     * @return array List of listing records, newest first.
     */
    public static function search(string $q, int $page, int $perpage): array {
        global $DB;

        $titlelike = $DB->sql_like('l.title', ':title', false);
        $authorlike = $DB->sql_like('l.author', ':author', false);
        $pattern = '%' . $DB->sql_like_escape(trim($q)) . '%';

        $sql = "SELECT l.*
                  FROM {local_chuspasocial_listing} l
                 WHERE $titlelike OR $authorlike
              ORDER BY l.timecreated DESC, l.id DESC";
        $params = ['title' => $pattern, 'author' => $pattern];

        return array_values($DB->get_records_sql($sql, $params, $page * $perpage, $perpage));
    }

    /**
     * Save the photos of a listing from the draft area of the form.
     *
     * The files are stored in the 'listingphoto' file area, using the
     * listing id as item id. It must be called when a listing is created
     * and when it is updated.
     *
     * @param int $listingid Id of the listing that owns the photos.
     * @param int $draftitemid Draft area id of the filemanager element.
     */
    public static function save_photos(int $listingid, int $draftitemid): void {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $context = \context_system::instance();
        file_save_draft_area_files(
            $draftitemid,
            $context->id,
            'local_chuspasocial',
            'listingphoto',
            $listingid,
            ['subdirs' => 0, 'accepted_types' => ['image']]
        );
    }

    /**
     * Get the available listings linked to the courses of the current user.
     *
     * A listing linked to several courses of the user is returned only once.
     *
     * @param int $page Page number, starting at 0.
     * @param int $perpage Number of listings per page.
     * @return array List of listing records, newest first.
     */
    public static function get_for_my_courses(int $page, int $perpage): array {
        global $DB;

        $courseids = array_keys(enrol_get_my_courses('id'));
        if (empty($courseids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');
        $params['status'] = 'available';

        $sql = "SELECT l.*
                  FROM {local_chuspasocial_listing} l
                 WHERE l.status = :status
                   AND EXISTS (
                       SELECT 1
                         FROM {local_chuspasocial_lstcourse} lc
                        WHERE lc.listingid = l.id
                          AND lc.courseid $insql
                   )
              ORDER BY l.timecreated DESC, l.id DESC";

        return array_values($DB->get_records_sql($sql, $params, $page * $perpage, $perpage));
    }

    /**
     * Formats a listing price using the configured currency.
     *
     * @param float $price Price to format.
     * @return string Formatted price or localized free label for zero.
     */
    public static function format_price(float $price): string {
        if ($price == 0.0) {
            return get_string('free', 'local_chuspasocial');
        }

        $currency = get_config('local_chuspasocial', 'currency');

        if (empty($currency)) {
            $currency = 'BOB';
        }

        return $currency . ' ' . format_float($price, 2);
    }

    public static function can_manage(listing $listing, int $userid): bool {
        $isauthor = (int) $listing->get('userid') === $userid;

        $ismoderator = false;
        if (get_capability_info('local/chuspasocial:moderate')) {
            $context = \context_system::instance();
            $ismoderator = has_capability('local/chuspasocial:moderate', $context, $userid);
        }

        return $isauthor || $ismoderator;
    }
    
    /**
     * Creates a listing for the current user.
     *
     * Requires the createlisting capability and the marketplace setting enabled.
     *
     * @param \stdClass $data Fields of the listing.
     * @return listing The created listing.
     * @throws moodle_exception If the marketplace is disabled.
     * @throws \invalid_parameter_exception If the price is negative.
     */
    public static function create_listing(\stdClass $data): listing {
        global $USER;

        if (!get_config('local_chuspasocial', 'enablemarketplace')) {
            throw new moodle_exception('nopermissions', 'error', '', 'create a listing');
        }

        require_capability('local/chuspasocial:createlisting', \context_system::instance());

        if (isset($data->price) && (float) $data->price < 0) {
            throw new \invalid_parameter_exception('The price must be greater than or equal to 0');
        }

        // Fields the author is not allowed to set.
        $protected = ['id', 'userid', 'timecreated', 'timemodified', 'usermodified'];
        $definition = listing::properties_definition();

        $listing = new listing();
        foreach ((array) $data as $field => $value) {
            if (!in_array($field, $protected, true) && isset($definition[$field])) {
                $listing->set($field, $value);
            }
        }
        $listing->set('userid', (int) $USER->id);
        $listing->create();

        if (!empty($data->photos)) {
            self::save_photos((int) $listing->get('id'), (int) $data->photos);
        }
        
        $event = \local_chuspasocial\event\listing_created::create([
            'objectid' => (int) $listing->get('id'),
            'context' => \context_system::instance(),
            'userid' => (int) $USER->id,
        ]);
        $event->trigger();
        
        return $listing;
    }
    /**
     * Replace the courses linked to a listing.
     *
     * All the current links of the listing in the lstcourse table are removed and
     * a new link is added for each course. Repeated ids are linked only once.
     * Every course must exist; if one does not exist, nothing is changed.
     *
     * @param int $listingid Id of the listing.
     * @param array $courseids Ids of the courses to link to the listing.
     * @throws \moodle_exception If a course does not exist.
     */
    public static function set_courses(int $listingid, array $courseids): void {
        global $DB;

        $courseids = array_values(array_unique(array_map('intval', $courseids)));

        if (!empty($courseids)) {
            [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');
            $params['siteid'] = SITEID;
            $existing = $DB->get_fieldset_select('course', 'id', "id $insql AND id <> :siteid", $params);
            $missing = array_diff($courseids, array_map('intval', $existing));
            if (!empty($missing)) {
                throw new \moodle_exception('invalidcourseid', 'error', '', implode(', ', $missing));
            }
        }

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('local_chuspasocial_lstcourse', ['listingid' => $listingid]);
        $records = [];
        foreach ($courseids as $courseid) {
            $records[] = (object) ['listingid' => $listingid, 'courseid' => $courseid];
        }
        $DB->insert_records('local_chuspasocial_lstcourse', $records);
        $transaction->allow_commit();
    }
        /**
     * Updates a listing. Only the author can edit it.
     *
     * The persistent updates timemodified and usermodified on its own.
     *
     * @param int $listingid ID of the listing to update.
     * @param \stdClass $data Fields to update.
     * @return listing The updated listing.
     * @throws moodle_exception If the current user is not the author.
     */
    public static function update_listing(int $listingid, \stdClass $data): listing {
        global $USER;

        $listing = new listing($listingid);

        if ((int) $listing->get('userid') !== (int) $USER->id) {
            throw new moodle_exception('nopermissions', 'error', '', 'edit this listing');
        }

        $protected = ['id', 'userid', 'timecreated', 'timemodified', 'usermodified'];
        $definition = listing::properties_definition();

        foreach ((array) $data as $field => $value) {
            if (!in_array($field, $protected, true) && isset($definition[$field])) {
                $listing->set($field, $value);
            }
        }

        $listing->update();

        if (!empty($data->photos)) {
            self::save_photos($listingid, (int) $data->photos);
        }

        return $listing;
    }
    /**
     * Marks a listing as sold. Only the author can do it.
     *
     * @param int $listingid ID of the listing to mark as sold.
     * @throws moodle_exception If the current user is not the author.
     */
    public static function mark_sold(int $listingid): void {
        global $USER;

        $listing = new listing($listingid);

        if ((int) $listing->get('userid') !== (int) $USER->id) {
            throw new moodle_exception('nopermissions', 'error', '', 'mark this listing as sold');
        }

        $listing->set('status', listing::STATUS_SOLD);
        $listing->update();
    }

    /**
     * Deletes a listing. Only the author or a moderator can do it.
     *
     * Also removes the links to the courses and the photos of the listing.
     *
     * @param int $listingid ID of the listing to delete.
     * @throws moodle_exception If the current user is neither the author nor a moderator.
     */
    public static function delete_listing(int $listingid): void {
        global $CFG, $DB, $USER;
        require_once($CFG->libdir . '/filelib.php');

        $listing = new listing($listingid);
        $context = \context_system::instance();

        $isauthor = (int) $listing->get('userid') === (int) $USER->id;
        if (!$isauthor && !has_capability('local/chuspasocial:moderate', $context)) {
            throw new moodle_exception('nopermissions', 'error', '', 'delete this listing');
        }

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('local_chuspasocial_lstcourse', ['listingid' => $listingid]);
        $listing->delete();
        $transaction->allow_commit();

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_chuspasocial', 'photos', $listingid);
    }
}
