<?php
// Copyright (c) 2025 Vernon Spain. All rights reserved.
//
// This file is part of the EduCheckout Platform, proprietary software
// developed by Vernon Spain (https://educheckout.com). It is not free
// software and is not released under the GNU General Public License.
//
// Unauthorised copying, distribution, modification, or use of this file,
// in whole or in part, via any medium, is strictly prohibited without the
// prior written permission of Vernon Spain. The software is provided "as
// is", without warranty of any kind, express or implied.

/**
 * Create and edit higher education courses of study.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

// Resolve config.php from either the traditional layout (config beside the
// docroot) or the Moodle 5.1+ public/ layout (config kept at the project root,
// one level above public/). Trying the nearer path first keeps both working.
// The conditional include is not recognised by moodle.Files.MoodleInternal,
// which only accepts an unconditional config.php require as the first statement;
// it is ignored on the line below since config.php is in fact included before
// any other change in global state.
if (file_exists(__DIR__ . '/../../config.php')) { // phpcs:ignore moodle.Files.MoodleInternal
    require(__DIR__ . '/../../config.php');
} else {
    require(__DIR__ . '/../../../config.php');
}
require_once($CFG->libdir . '/adminlib.php');

use local_educheckout_he\course_of_study_manager;
use local_educheckout_he\form\course_of_study_form;
use core\output\notification;

admin_externalpage_setup('local_educheckout_he_coursesofstudy');

$id        = optional_param('id', 0, PARAM_INT);
$component = 'local_educheckout_he';
$listurl   = new moodle_url('/local/educheckout_he/coursesofstudy.php');
$editurl   = new moodle_url('/local/educheckout_he/course_of_study_edit.php', ['id' => $id]);
$PAGE->set_url($editurl);

// The pathway kill-switch (CLAUDE.md §8) gates the whole surface: with it off,
// courses of study cannot be created or edited.
if (!course_of_study_manager::is_enabled()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('coursesofstudy', $component));
    echo $OUTPUT->notification(get_string('he_disabled', $component), 'info');
    echo $OUTPUT->footer();
    exit;
}

if ($id) {
    $course = course_of_study_manager::get($id);
    if (!$course) {
        redirect($listurl);
    }
    // A null CRICOS code maps to a blank field.
    $course->cricoscode = $course->cricoscode ?? '';
} else {
    $course = (object) [
        'id'          => 0,
        'courselevel' => course_of_study_manager::LEVEL_UNDERGRADUATE,
        'enabled'     => 1,
    ];
}

$mform = new course_of_study_form($editurl->out(false));
$mform->set_data($course);

if ($mform->is_cancelled()) {
    redirect($listurl);
} else if ($data = $mform->get_data()) {
    // The manager normalises the level and CRICOS code on save.
    course_of_study_manager::save($data);
    redirect($listurl, get_string('cos_saved', $component), null, notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string($id ? 'cos_edit' : 'cos_add', $component));
$mform->display();
echo $OUTPUT->footer();
