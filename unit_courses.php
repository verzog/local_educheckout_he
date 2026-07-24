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
 * Manage the Moodle courses mapped to a higher education unit of study.
 *
 * The mapping is what makes the pathway claim an enrolment: a learner enrolled
 * in a mapped course is owned by the higher education pathway.
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

use local_educheckout_he\unit_of_study_manager;
use local_educheckout_he\unit_course_manager;
use local_educheckout_he\form\unit_course_form;
use core\output\notification;

admin_externalpage_setup('local_educheckout_he_unitsofstudy');

$unitofstudyid = required_param('unitofstudyid', PARAM_INT);
$component = 'local_educheckout_he';
$pageurl   = new moodle_url('/local/educheckout_he/unit_courses.php', ['unitofstudyid' => $unitofstudyid]);
$listurl   = new moodle_url('/local/educheckout_he/unitsofstudy.php');
$PAGE->set_url($pageurl);

// The pathway kill-switch (CLAUDE.md §8) gates the whole surface.
if (!unit_course_manager::is_enabled()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('unitcourses', $component));
    echo $OUTPUT->notification(get_string('he_disabled', $component), 'info');
    echo $OUTPUT->footer();
    exit;
}

$unit = unit_of_study_manager::get($unitofstudyid);
if (!$unit) {
    redirect($listurl);
}

// Remove-mapping action: confirmation first, then a sesskey-checked delete.
$delete  = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
if ($delete) {
    $mapping = unit_course_manager::get($delete);
    if (!$mapping || (int) $mapping->unitofstudyid !== $unitofstudyid) {
        redirect($pageurl);
    }

    if ($confirm && confirm_sesskey()) {
        unit_course_manager::delete($delete);
        redirect($pageurl, get_string('uc_removed', $component), null, notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('uc_remove_confirm', $component),
        new moodle_url($pageurl, ['delete' => $delete, 'confirm' => 1, 'sesskey' => sesskey()]),
        $pageurl
    );
    echo $OUTPUT->footer();
    exit;
}

$mform = new unit_course_form($pageurl->out(false), ['unitofstudyid' => $unitofstudyid]);
$mform->set_data((object) ['unitofstudyid' => $unitofstudyid]);

if ($mform->is_cancelled()) {
    redirect($pageurl);
} else if ($data = $mform->get_data()) {
    unit_course_manager::map($unitofstudyid, (int) $data->courseid);
    redirect($pageurl, get_string('uc_mapped', $component), null, notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('unitcourses', $component));
echo html_writer::tag('p', get_string('unitcourses_intro', $component, s($unit->code . ' — ' . $unit->name)));

$mappings = unit_course_manager::get_for_unit($unitofstudyid);
if (!$mappings) {
    echo $OUTPUT->notification(get_string('unitcourses_none', $component), 'info');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    $table->head = [
        get_string('uc_col_course', $component),
        get_string('uc_col_actions', $component),
    ];

    foreach ($mappings as $mapping) {
        $course = $DB->get_record('course', ['id' => $mapping->courseid], 'id, fullname, shortname');
        if ($course) {
            $coursename = format_string($course->fullname) . ' (' . format_string($course->shortname) . ')';
        } else {
            // The mapped course has since been deleted; the mapping is harmless
            // (it never matches an enrolment) but is shown so it can be removed.
            $coursename = get_string('uc_course_missing', $component, (int) $mapping->courseid);
        }

        $delurl  = new moodle_url($pageurl, ['delete' => $mapping->id]);
        $actions = $OUTPUT->action_icon($delurl, new pix_icon('t/delete', get_string('remove')));

        $table->data[] = [$coursename, $actions];
    }

    echo html_writer::table($table);
}

echo $mform->render();

echo html_writer::tag(
    'div',
    html_writer::link($listurl, get_string('back')),
    ['class' => 'mt-3']
);

echo $OUTPUT->footer();
