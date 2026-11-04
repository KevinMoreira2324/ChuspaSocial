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
 * Page to create or edit a book listing of the marketplace.
 *
 * Shows the listing form. Without an id it creates a new listing, with an id it
 * updates that listing, which only its owner can edit. After saving it redirects
 * to the listing detail page.
 *
 * @package    local_chuspasocial
 * @copyright  2026 SIS-INF
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use core\output\notification;
use local_chuspasocial\form\listing_form;
use local_chuspasocial\local\listing_service;

$id = optional_param('id', 0, PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('local/chuspasocial:viewmarketplace', $context);

$marketplaceenabled = get_config('local_chuspasocial', 'enablemarketplace');
if ($marketplaceenabled !== false && empty($marketplaceenabled)) {
    throw new moodle_exception('marketplacedisabled', 'local_chuspasocial');
}

$pageurl = new moodle_url('/local/chuspasocial/listing_edit.php', $id ? ['id' => $id] : []);
$marketplaceurl = new moodle_url('/local/chuspasocial/marketplace.php');
$cancelurl = $id ? new moodle_url('/local/chuspasocial/listing.php', ['id' => $id]) : $marketplaceurl;

$listing = null;
if ($id) {
    $listing = $DB->get_record('local_chuspasocial_listing', ['id' => $id], '*', MUST_EXIST);
    if ((int) $listing->userid !== (int) $USER->id) {
        throw new moodle_exception('nopermissions', 'error', $cancelurl, get_string('edit'));
    }
}

$title = $listing ? get_string('edit') : get_string('publishbook', 'local_chuspasocial');

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading(get_string('pluginname', 'local_chuspasocial'));
$PAGE->navbar->add(get_string('books', 'local_chuspasocial'), $marketplaceurl);
$PAGE->navbar->add($title);

$fileoptions = ['subdirs' => 0, 'maxfiles' => 3, 'accepted_types' => ['.jpg', '.png', '.webp']];

$form = new listing_form($pageurl);

if ($form->is_cancelled()) {
    redirect($cancelurl);
}

if ($data = $form->get_data()) {
    $record = (object) [
        'title' => $data->title,
        'author' => $data->author,
        'description' => $data->description['text'],
        'descriptionformat' => (int) $data->description['format'],
        'price' => (float) $data->price,
        'externalcontact' => trim($data->contact) === '' ? null : trim($data->contact),
    ];

    if ($listing) {
        listing_service::update_listing($id, $record);
    } else {
        $record->userid = (int) $USER->id;
        $id = (int) listing_service::create_listing($record);
    }

    listing_service::set_courses($id, $data->course ?? []);
    file_save_draft_area_files($data->photos, $context->id, 'local_chuspasocial', 'listingphoto', $id, $fileoptions);

    redirect(
        new moodle_url('/local/chuspasocial/listing.php', ['id' => $id]),
        get_string('changessaved'),
        null,
        notification::NOTIFY_SUCCESS
    );
}

if ($listing) {
    $draftitemid = file_get_submitted_draft_itemid('photos');
    file_prepare_draft_area($draftitemid, $context->id, 'local_chuspasocial', 'listingphoto', $id, $fileoptions);

    $form->set_data((object) [
        'title' => $listing->title,
        'author' => $listing->author,
        'description' => ['text' => $listing->description ?? '', 'format' => $listing->descriptionformat],
        'price' => $listing->price,
        'contact' => $listing->externalcontact,
        'course' => $DB->get_fieldset_select('local_chuspasocial_lstcourse', 'courseid', 'listingid = ?', [$id]),
        'photos' => $draftitemid,
    ]);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$form->display();
echo $OUTPUT->footer();