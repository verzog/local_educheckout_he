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
 * Courses of study management page for the higher education pathway.
 *
 * Lists the award courses (TCSI courses of study) and offers add, edit, and
 * delete. A course of study with units of study cannot be deleted; retire it
 * (untick Active) or remove its units first.
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
use local_educheckout_he\unit_of_study_manager;
use core\output\notification;

admin_externalpage_setup('local_educheckout_he_coursesofstudy');

$component = 'local_educheckout_he';
$pageurl   = new moodle_url('/local/educheckout_he/coursesofstudy.php');
$PAGE->set_url($pageurl);

// The pathway kill-switch (CLAUDE.md §8) gates the whole surface, admin
// included: with it off the courses-of-study pages are unavailable.
if (!course_of_study_manager::is_enabled()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('coursesofstudy', $component));
    echo $OUTPUT->notification(get_string('he_disabled', $component), 'info');
    echo $OUTPUT->footer();
    exit;
}

// Delete action: confirmation first, then a sesskey-checked delete.
$delete  = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
if ($delete) {
    $course = course_of_study_manager::get($delete);
    if (!$course) {
        redirect($pageurl);
    }

    // A course of study with units of study cannot be deleted: the units
    // reference it, so removing it would orphan them. Retire it (untick
    // Active) or remove its units first.
    if (unit_of_study_manager::count_for_course($delete) > 0) {
        redirect($pageurl, get_string('cos_has_units', $component), null, notification::NOTIFY_ERROR);
    }

    if ($confirm && confirm_sesskey()) {
        course_of_study_manager::delete($delete);
        redirect($pageurl, get_string('cos_deleted', $component), null, notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('cos_delete_confirm', $component, s($course->name)),
        new moodle_url($pageurl, ['delete' => $delete, 'confirm' => 1, 'sesskey' => sesskey()]),
        $pageurl
    );
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursesofstudy', $component));
echo html_writer::tag('p', get_string('coursesofstudy_intro', $component));

echo $OUTPUT->single_button(
    new moodle_url('/local/educheckout_he/course_of_study_edit.php'),
    get_string('cos_add', $component),
    'get'
);

$courses = course_of_study_manager::get_all();
if (!$courses) {
    echo $OUTPUT->notification(get_string('coursesofstudy_none', $component), 'info');
    echo $OUTPUT->footer();
    exit;
}

$yes = get_string('yes');
$no  = get_string('no');

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->head = [
    get_string('cos_col_code', $component),
    get_string('cos_col_name', $component),
    get_string('cos_col_cricoscode', $component),
    get_string('cos_col_level', $component),
    get_string('cos_col_units', $component),
    get_string('cos_col_enabled', $component),
    get_string('cos_col_actions', $component),
];

foreach ($courses as $course) {
    $levelkey = 'cos_level_' . $course->courselevel;
    $level = get_string_manager()->string_exists($levelkey, $component)
        ? get_string($levelkey, $component)
        : s($course->courselevel);

    $unitsurl = new moodle_url('/local/educheckout_he/unitsofstudy.php', ['courseofstudyid' => $course->id]);
    $editurl  = new moodle_url('/local/educheckout_he/course_of_study_edit.php', ['id' => $course->id]);
    $delurl   = new moodle_url($pageurl, ['delete' => $course->id]);
    $actions  = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')))
        . ' ' . $OUTPUT->action_icon($delurl, new pix_icon('t/delete', get_string('delete')));

    $table->data[] = [
        s($course->code),
        s($course->name),
        $course->cricoscode !== null && $course->cricoscode !== '' ? s($course->cricoscode) : '—',
        $level,
        html_writer::link($unitsurl, (string) unit_of_study_manager::count_for_course($course->id)),
        $course->enabled ? $yes : $no,
        $actions,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
