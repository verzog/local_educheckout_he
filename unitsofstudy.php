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
 * Units of study management page for the higher education pathway.
 *
 * Lists the reportable teaching units (TCSI units of study), optionally scoped
 * to one course of study, and offers add, edit, and delete.
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
use local_educheckout_he\unit_course_manager;
use core\output\notification;

admin_externalpage_setup('local_educheckout_he_unitsofstudy');

$courseofstudyid = optional_param('courseofstudyid', 0, PARAM_INT);
$component = 'local_educheckout_he';
$pageurl   = new moodle_url('/local/educheckout_he/unitsofstudy.php');
if ($courseofstudyid) {
    $pageurl->param('courseofstudyid', $courseofstudyid);
}
$PAGE->set_url($pageurl);

// The pathway kill-switch (CLAUDE.md §8) gates the whole surface, admin
// included: with it off the units-of-study pages are unavailable.
if (!unit_of_study_manager::is_enabled()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('unitsofstudy', $component));
    echo $OUTPUT->notification(get_string('he_disabled', $component), 'info');
    echo $OUTPUT->footer();
    exit;
}

// Delete action: confirmation first, then a sesskey-checked delete.
$delete  = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
if ($delete) {
    $unit = unit_of_study_manager::get($delete);
    if (!$unit) {
        redirect($pageurl);
    }

    if ($confirm && confirm_sesskey()) {
        unit_of_study_manager::delete($delete);
        redirect($pageurl, get_string('uos_deleted', $component), null, notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('uos_delete_confirm', $component, s($unit->name)),
        new moodle_url($pageurl, ['delete' => $delete, 'confirm' => 1, 'sesskey' => sesskey()]),
        $pageurl
    );
    echo $OUTPUT->footer();
    exit;
}

// The course-of-study label map, reused for the filter menu and the rows.
$courses = course_of_study_manager::get_all();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('unitsofstudy', $component));
echo html_writer::tag('p', get_string('unitsofstudy_intro', $component));

// A unit of study needs a parent course of study, so guide the admin to make
// one first when none exist.
if (!$courses) {
    echo $OUTPUT->notification(get_string('uos_needs_course', $component), 'info');
    echo $OUTPUT->footer();
    exit;
}

// Filter by course of study. "All" clears the scope.
$filteroptions = [0 => get_string('uos_filter_all', $component)];
foreach ($courses as $course) {
    $filteroptions[$course->id] = $course->code . ' — ' . $course->name;
}
$filterselect = new single_select($pageurl, 'courseofstudyid', $filteroptions, $courseofstudyid, []);
$filterselect->label = get_string('uos_filter', $component);
echo $OUTPUT->render($filterselect);

$addurl = new moodle_url('/local/educheckout_he/unit_of_study_edit.php');
if ($courseofstudyid) {
    $addurl->param('courseofstudyid', $courseofstudyid);
}
echo $OUTPUT->single_button($addurl, get_string('uos_add', $component), 'get');

$units = unit_of_study_manager::get_all($courseofstudyid);
if (!$units) {
    echo $OUTPUT->notification(get_string('unitsofstudy_none', $component), 'info');
    echo $OUTPUT->footer();
    exit;
}

$yes = get_string('yes');
$no  = get_string('no');

// Mapped-course counts for every listed unit, fetched in one grouped query
// rather than one per row.
$mapcounts = unit_course_manager::counts_by_unit(array_keys($units));

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->head = [
    get_string('uos_col_code', $component),
    get_string('uos_col_name', $component),
    get_string('uos_col_courseofstudy', $component),
    get_string('uos_col_eftsl', $component),
    get_string('uos_col_foecode', $component),
    get_string('uos_col_mode', $component),
    get_string('uos_col_courses', $component),
    get_string('uos_col_enabled', $component),
    get_string('uos_col_actions', $component),
];

foreach ($units as $unit) {
    $modekey = 'uos_mode_' . $unit->deliverymode;
    $mode = get_string_manager()->string_exists($modekey, $component)
        ? get_string($modekey, $component)
        : s($unit->deliverymode);

    $coursename = isset($courses[$unit->courseofstudyid])
        ? s($courses[$unit->courseofstudyid]->code)
        : '—';

    // The mapped-course count links through to the mapping manager for the unit.
    $coursesurl = new moodle_url('/local/educheckout_he/unit_courses.php', ['unitofstudyid' => $unit->id]);
    $coursescount = html_writer::link($coursesurl, (string) ($mapcounts[$unit->id] ?? 0));

    $editurl = new moodle_url('/local/educheckout_he/unit_of_study_edit.php', ['id' => $unit->id]);
    $delurl  = new moodle_url($pageurl, ['delete' => $unit->id]);
    $actions = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')))
        . ' ' . $OUTPUT->action_icon($delurl, new pix_icon('t/delete', get_string('delete')));

    $table->data[] = [
        s($unit->code),
        s($unit->name),
        $coursename,
        format_float($unit->eftsl, 5),
        $unit->foecode !== null && $unit->foecode !== '' ? s($unit->foecode) : '—',
        $mode,
        $coursescount,
        $unit->enabled ? $yes : $no,
        $actions,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
