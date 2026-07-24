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
 * Higher education student elements management for the higher education pathway.
 *
 * Lets an admin holding managestudents record a learner's TCSI student elements
 * (citizenship, USI, CHESSN, disability, prior education). Sensitive personal
 * data, off by default, kill-switch-gated.
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

use local_educheckout_he\student_manager;
use local_educheckout_he\form\student_form;
use core\output\notification;

admin_externalpage_setup('local_educheckout_he_students');

$component = 'local_educheckout_he';
$userid    = optional_param('userid', 0, PARAM_INT);
$pageurl   = new moodle_url('/local/educheckout_he/students.php');
$PAGE->set_url($pageurl);

// The subsystem kill-switch (CLAUDE.md §8) gates the whole surface.
if (!student_manager::is_enabled()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('students_manage', $component));
    echo $OUTPUT->notification(get_string('students_disabled', $component), 'info');
    echo $OUTPUT->footer();
    exit;
}

$mform = new student_form($pageurl);
if ($data = $mform->get_data()) {
    student_manager::save((int) $data->userid, $data);
    redirect(
        new moodle_url($pageurl, ['userid' => (int) $data->userid]),
        get_string('students_saved', $component),
        null,
        notification::NOTIFY_SUCCESS
    );
} else if ($userid) {
    // Prefill the form for the chosen learner: their existing record if any,
    // otherwise a blank form already pointed at that learner.
    $record = student_manager::get($userid) ?: new stdClass();
    $record->userid = $userid;
    $mform->set_data($record);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('students_manage', $component));
echo html_writer::tag('p', get_string('students_intro', $component));

$mform->display();

// The learners who already have a higher education record, each with an edit link.
$records = $DB->get_records(student_manager::TABLE, null, 'timemodified DESC');

if (!$records) {
    echo $OUTPUT->notification(get_string('students_none', $component), 'info');
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->heading(get_string('students_existing', $component), 3);

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->head = [
    get_string('student_learner', $component),
    get_string('student_col_citizenship', $component),
    get_string('student_col_usi', $component),
    get_string('student_col_updated', $component),
    get_string('student_col_actions', $component),
];

foreach ($records as $record) {
    $learner = \core_user::get_user($record->userid);
    $citizenkey = 'student_citizenship_' . $record->citizenship;
    $citizenship = get_string_manager()->string_exists($citizenkey, $component)
        ? get_string($citizenkey, $component)
        : s($record->citizenship);
    $edit = html_writer::link(
        new moodle_url($pageurl, ['userid' => $record->userid]),
        get_string('student_edit', $component)
    );

    $table->data[] = [
        $learner ? s(fullname($learner)) : get_string('student_user_deleted', $component),
        $citizenship,
        $record->usi !== null ? s($record->usi) : '—',
        userdate($record->timemodified),
        $edit,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
