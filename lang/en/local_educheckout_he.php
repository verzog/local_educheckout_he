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
 * English language strings for the EduCheckout Platform higher education pathway.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

$string['admincat_he'] = 'Higher education';
$string['cos_add'] = 'Add course of study';
$string['cos_code'] = 'Course of study code';
$string['cos_code_taken'] = 'This course of study code is already in use.';
$string['cos_col_actions'] = 'Actions';
$string['cos_col_code'] = 'Code';
$string['cos_col_cricoscode'] = 'CRICOS code';
$string['cos_col_enabled'] = 'Active';
$string['cos_col_level'] = 'Level';
$string['cos_col_name'] = 'Name';
$string['cos_col_units'] = 'Units';
$string['cos_cricoscode'] = 'CRICOS code';
$string['cos_cricoscode_help'] = 'The CRICOS registration code for an international (CRICOS-registered) course. Leave blank for a course that is not CRICOS-registered.';
$string['cos_delete_confirm'] = 'Are you sure you want to delete the course of study "{$a}"?';
$string['cos_deleted'] = 'Course of study deleted.';
$string['cos_edit'] = 'Edit course of study';
$string['cos_enabled'] = 'Active';
$string['cos_enabled_label'] = 'This course of study is active';
$string['cos_has_units'] = 'This course of study has units of study and cannot be deleted. Retire it (untick Active) or remove its units first.';
$string['cos_level'] = 'Level of course';
$string['cos_level_enabling'] = 'Enabling course';
$string['cos_level_higherdegree'] = 'Higher degree by research';
$string['cos_level_other'] = 'Other';
$string['cos_level_postgraduate'] = 'Postgraduate coursework';
$string['cos_level_undergraduate'] = 'Undergraduate';
$string['cos_name'] = 'Name';
$string['cos_saved'] = 'Course of study saved.';
$string['coursesofstudy'] = 'Courses of study';
$string['coursesofstudy_intro'] = 'Award courses students are admitted to (TCSI courses of study). Each is the parent structure for its units of study.';
$string['coursesofstudy_none'] = 'No courses of study have been created yet.';
$string['educheckout_he:manage'] = 'Manage the higher education pathway';
$string['educheckout_he:managestudents'] = 'Manage higher education elements for learners';
$string['he_disabled'] = 'The higher education pathway is currently disabled. Enable it under the higher education settings to use this page.';
$string['pathway_description'] = 'Australian higher education pathway: unit-of-study structure and statutory reporting (TCSI/HEIMS, HESA, TEQSA).';
$string['pathway_label'] = 'Higher education pathway';
$string['pluginname'] = 'EduCheckout Platform — Higher education';
$string['privacy:metadata:coursesofstudy'] = 'Courses of study are institutional configuration; the only personal data is the reference to the administrator who created or last changed each one.';
$string['privacy:metadata:coursesofstudy:timemodified'] = 'The time the course of study was last changed.';
$string['privacy:metadata:coursesofstudy:usermodified'] = 'The administrator who created or last changed the course of study.';
$string['privacy:metadata:students'] = 'A learner\'s higher education elements TCSI reports on, including special-category data.';
$string['privacy:metadata:students:chessn'] = 'The learner\'s Commonwealth Higher Education Student Support Number.';
$string['privacy:metadata:students:citizenship'] = 'The learner\'s citizenship or residency status.';
$string['privacy:metadata:students:disability'] = 'The learner\'s disability status (special-category data).';
$string['privacy:metadata:students:prioreducation'] = 'The learner\'s highest prior educational attainment.';
$string['privacy:metadata:students:timemodified'] = 'The time the higher education record was last changed.';
$string['privacy:metadata:students:userid'] = 'The learner the higher education record is about.';
$string['privacy:metadata:students:usermodified'] = 'The administrator who created or last changed the record.';
$string['privacy:metadata:students:usi'] = 'The learner\'s Unique Student Identifier.';
$string['privacy:metadata:unitcourses'] = 'Unit-course mappings are institutional configuration; the only personal data is the reference to the administrator who created or last changed each one.';
$string['privacy:metadata:unitcourses:timemodified'] = 'The time the unit-course mapping was last changed.';
$string['privacy:metadata:unitcourses:usermodified'] = 'The administrator who created or last changed the unit-course mapping.';
$string['privacy:metadata:unitsofstudy'] = 'Units of study are institutional configuration; the only personal data is the reference to the administrator who created or last changed each one.';
$string['privacy:metadata:unitsofstudy:timemodified'] = 'The time the unit of study was last changed.';
$string['privacy:metadata:unitsofstudy:usermodified'] = 'The administrator who created or last changed the unit of study.';
$string['setting_he_enabled'] = 'Enable higher education pathway';
$string['setting_he_enabled_desc'] = 'Master switch for the higher education pathway. When off, the pathway stays inert: it claims no enrolments and exposes no operational pages. Off by default.';
$string['setting_he_students_enabled'] = 'Enable student higher education elements';
$string['setting_he_students_enabled_desc'] = 'Allow recording learners\' higher education elements (citizenship, USI, CHESSN, disability, prior education). This is sensitive personal data, so it is off by default and independent of the pathway switch; both must be on.';
$string['settings_he'] = 'Higher education settings';
$string['student_chessn'] = 'CHESSN';
$string['student_chessn_help'] = 'The learner\'s Commonwealth Higher Education Student Support Number, a 10-character identifier. Leave blank if not recorded.';
$string['student_chessn_invalid'] = 'Enter the CHESSN as 10 letters or digits.';
$string['student_citizenship'] = 'Citizenship / residency';
$string['student_citizenship_australian'] = 'Australian citizen';
$string['student_citizenship_international'] = 'International (student visa or other)';
$string['student_citizenship_notstated'] = 'Not stated';
$string['student_citizenship_nzcitizen'] = 'New Zealand citizen';
$string['student_citizenship_permanenthumanitarian'] = 'Permanent humanitarian visa';
$string['student_citizenship_permanentresident'] = 'Permanent resident';
$string['student_col_actions'] = 'Actions';
$string['student_col_citizenship'] = 'Citizenship / residency';
$string['student_col_updated'] = 'Updated';
$string['student_col_usi'] = 'USI';
$string['student_disability'] = 'Disability status';
$string['student_disability_help'] = 'Whether the learner has reported a disability. This is special-category personal data; record it only with the learner\'s knowledge.';
$string['student_disability_none'] = 'No disability reported';
$string['student_disability_notstated'] = 'Not stated';
$string['student_disability_reported'] = 'Disability reported';
$string['student_edit'] = 'Edit';
$string['student_learner'] = 'Learner';
$string['student_prioreducation'] = 'Highest prior educational attainment';
$string['student_prioreducation_hecomplete'] = 'Completed higher education';
$string['student_prioreducation_heincomplete'] = 'Incomplete higher education';
$string['student_prioreducation_none'] = 'No prior attainment';
$string['student_prioreducation_notstated'] = 'Not stated';
$string['student_prioreducation_other'] = 'Other';
$string['student_prioreducation_secondary'] = 'Secondary education';
$string['student_prioreducation_vet'] = 'VET award';
$string['student_save'] = 'Save higher education record';
$string['student_user_deleted'] = 'Deleted user';
$string['student_usi'] = 'USI';
$string['student_usi_help'] = 'The learner\'s Unique Student Identifier, a 10-character identifier. Leave blank if not recorded.';
$string['student_usi_invalid'] = 'Enter the USI as 10 letters or digits.';
$string['students_disabled'] = 'Student higher education elements are currently disabled. Enable them under the higher education settings to use this page.';
$string['students_existing'] = 'Learners with a higher education record';
$string['students_intro'] = 'Record the higher education elements TCSI reports on for a learner. This is sensitive personal data.';
$string['students_manage'] = 'Student higher education elements';
$string['students_none'] = 'No learner higher education records have been created yet.';
$string['students_saved'] = 'Higher education record saved.';
$string['uc_add'] = 'Map course';
$string['uc_already_mapped'] = 'That course is already mapped to this unit.';
$string['uc_col_actions'] = 'Actions';
$string['uc_col_course'] = 'Course';
$string['uc_course'] = 'Course';
$string['uc_course_invalid'] = 'Choose a valid course.';
$string['uc_course_missing'] = 'Deleted course (id {$a})';
$string['uc_mapped'] = 'Course mapped to the unit of study.';
$string['uc_remove_confirm'] = 'Are you sure you want to remove this course mapping?';
$string['uc_removed'] = 'Course mapping removed.';
$string['unitcourses'] = 'Courses for this unit';
$string['unitcourses_intro'] = 'Moodle courses that deliver the unit of study "{$a}". A learner enrolled in a mapped course is claimed by the higher education pathway.';
$string['unitcourses_none'] = 'No courses are mapped to this unit yet.';
$string['unitsofstudy'] = 'Units of study';
$string['unitsofstudy_intro'] = 'Reportable teaching units within a course of study (TCSI units of study), carrying the EFTSL load and field-of-education classification.';
$string['unitsofstudy_none'] = 'No units of study have been created yet.';
$string['uos_add'] = 'Add unit of study';
$string['uos_code'] = 'Unit of study code';
$string['uos_code_taken'] = 'This unit code is already in use within the selected course of study.';
$string['uos_col_actions'] = 'Actions';
$string['uos_col_code'] = 'Code';
$string['uos_col_courseofstudy'] = 'Course of study';
$string['uos_col_courses'] = 'Courses';
$string['uos_col_eftsl'] = 'EFTSL';
$string['uos_col_enabled'] = 'Active';
$string['uos_col_foecode'] = 'FOE code';
$string['uos_col_mode'] = 'Delivery mode';
$string['uos_col_name'] = 'Name';
$string['uos_courseofstudy'] = 'Course of study';
$string['uos_delete_confirm'] = 'Are you sure you want to delete the unit of study "{$a}"?';
$string['uos_deleted'] = 'Unit of study deleted.';
$string['uos_edit'] = 'Edit unit of study';
$string['uos_eftsl'] = 'EFTSL';
$string['uos_eftsl_help'] = 'The Equivalent Full-Time Student Load for this unit, as a decimal (for example, 0.125). Used for TCSI load reporting.';
$string['uos_eftsl_invalid'] = 'Enter the EFTSL as a number between 0 and 99999.99999 (for example, 0.125).';
$string['uos_enabled'] = 'Active';
$string['uos_enabled_label'] = 'This unit of study is active';
$string['uos_filter'] = 'Course of study';
$string['uos_filter_all'] = 'All courses of study';
$string['uos_foecode'] = 'Field of education code';
$string['uos_foecode_help'] = 'The ASCED field of education code for this unit. Leave blank if unspecified.';
$string['uos_mode'] = 'Delivery mode';
$string['uos_mode_external'] = 'External';
$string['uos_mode_internal'] = 'Internal';
$string['uos_mode_multimodal'] = 'Multimodal';
$string['uos_name'] = 'Name';
$string['uos_needs_course'] = 'Create a course of study first — a unit of study belongs to one.';
$string['uos_saved'] = 'Unit of study saved.';
