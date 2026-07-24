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
 * Privacy API provider for the EduCheckout Platform higher education pathway.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy API provider for the higher education pathway.
 *
 * The pathway's structural catalogue — courses of study, units of study, and
 * the unit-course mapping — is institutional configuration, not learner data.
 * The only personal data those tables hold is the authoring reference
 * (usermodified), exported for and anonymised on erasure of that admin.
 *
 * The student higher-education-elements table IS learner data — and its
 * disability element is special-category — so it is treated like core's welfare
 * profile: the learner's own record is exported and, on erasure, deleted (not
 * anonymised), while an admin's authorship (usermodified) of another learner's
 * record is anonymised. All data is held at the system context.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider
{
    /** The courses of study table. */
    const TABLE_COURSES = 'local_educheckout_he_coursesofstudy';

    /** The units of study table. */
    const TABLE_UNITS = 'local_educheckout_he_unitsofstudy';

    /** The unit-course mapping table. */
    const TABLE_UNITCOURSES = 'local_educheckout_he_unitcourses';

    /** The per-learner student higher-education-elements table. */
    const TABLE_STUDENTS = 'local_educheckout_he_students';

    /**
     * Returns metadata describing the personal data stored by this plugin.
     *
     * @param  collection $collection The metadata collection to populate.
     * @return collection             The populated collection.
     */
    public static function get_metadata(collection $collection): collection {
        // A course of study is institutional configuration; only the authoring
        // reference (usermodified) is personal data.
        $collection->add_database_table(
            self::TABLE_COURSES,
            [
                'usermodified' => 'privacy:metadata:coursesofstudy:usermodified',
                'timemodified' => 'privacy:metadata:coursesofstudy:timemodified',
            ],
            'privacy:metadata:coursesofstudy'
        );

        // A unit of study is institutional configuration; only the authoring
        // reference (usermodified) is personal data.
        $collection->add_database_table(
            self::TABLE_UNITS,
            [
                'usermodified' => 'privacy:metadata:unitsofstudy:usermodified',
                'timemodified' => 'privacy:metadata:unitsofstudy:timemodified',
            ],
            'privacy:metadata:unitsofstudy'
        );

        // A unit-course mapping is institutional configuration; only the
        // authoring reference (usermodified) is personal data.
        $collection->add_database_table(
            self::TABLE_UNITCOURSES,
            [
                'usermodified' => 'privacy:metadata:unitcourses:usermodified',
                'timemodified' => 'privacy:metadata:unitcourses:timemodified',
            ],
            'privacy:metadata:unitcourses'
        );

        // A student record holds the learner's higher education elements —
        // personal data (the disability element is special-category) — plus the
        // authoring admin's usermodified reference.
        $collection->add_database_table(
            self::TABLE_STUDENTS,
            [
                'userid'         => 'privacy:metadata:students:userid',
                'citizenship'    => 'privacy:metadata:students:citizenship',
                'usi'            => 'privacy:metadata:students:usi',
                'chessn'         => 'privacy:metadata:students:chessn',
                'disability'     => 'privacy:metadata:students:disability',
                'prioreducation' => 'privacy:metadata:students:prioreducation',
                'timemodified'   => 'privacy:metadata:students:timemodified',
                'usermodified'   => 'privacy:metadata:students:usermodified',
            ],
            'privacy:metadata:students'
        );

        return $collection;
    }

    /**
     * Returns the contexts that contain personal data for the given user.
     *
     * All higher education pathway data lives in the system context. A user
     * appears here as the author (usermodified) of a catalogue record, or as
     * the subject (userid) or author of a student higher-education record.
     *
     * @param  int         $userid The Moodle user ID.
     * @return contextlist         The list of contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :contextlevel
                   AND (EXISTS (SELECT 1 FROM {" . self::TABLE_COURSES . "} cos WHERE cos.usermodified = :userid1)
                     OR EXISTS (SELECT 1 FROM {" . self::TABLE_UNITS . "} uos WHERE uos.usermodified = :userid2)
                     OR EXISTS (SELECT 1 FROM {" . self::TABLE_UNITCOURSES . "} ucm WHERE ucm.usermodified = :userid3)
                     OR EXISTS (SELECT 1 FROM {" . self::TABLE_STUDENTS . "} stu WHERE stu.userid = :userid4)
                     OR EXISTS (SELECT 1 FROM {" . self::TABLE_STUDENTS . "} stm WHERE stm.usermodified = :userid5))";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_SYSTEM,
            'userid1'      => $userid,
            'userid2'      => $userid,
            'userid3'      => $userid,
            'userid4'      => $userid,
            'userid5'      => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Returns the users who have personal data in the given context.
     *
     * @param  userlist $userlist The userlist to populate.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if (!($context instanceof \context_system)) {
            return;
        }

        // Admins who authored a course of study appear via usermodified; skip
        // the 0 placeholder used when no author is recorded.
        $sql = "SELECT usermodified FROM {" . self::TABLE_COURSES . "} WHERE usermodified <> 0";
        $userlist->add_from_sql('usermodified', $sql, []);

        // Admins who authored a unit of study appear via usermodified too.
        $sql = "SELECT usermodified FROM {" . self::TABLE_UNITS . "} WHERE usermodified <> 0";
        $userlist->add_from_sql('usermodified', $sql, []);

        // Admins who authored a unit-course mapping appear via usermodified too.
        $sql = "SELECT usermodified FROM {" . self::TABLE_UNITCOURSES . "} WHERE usermodified <> 0";
        $userlist->add_from_sql('usermodified', $sql, []);

        // Learners appear by userid on their own student record; the admin who
        // created or last edited it appears via usermodified.
        $sql = "SELECT userid FROM {" . self::TABLE_STUDENTS . "}";
        $userlist->add_from_sql('userid', $sql, []);

        $sql = "SELECT usermodified FROM {" . self::TABLE_STUDENTS . "} WHERE usermodified <> 0";
        $userlist->add_from_sql('usermodified', $sql, []);
    }

    /**
     * Exports the personal data held for the given approved contexts.
     *
     * @param  approved_contextlist $contextlist The approved contexts to export.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!($context instanceof \context_system)) {
                continue;
            }

            // Export courses of study this user authored. The course of study
            // is institutional configuration; the user's link is usermodified.
            $courses = $DB->get_records(self::TABLE_COURSES, ['usermodified' => $userid]);
            if ($courses) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_educheckout_he'), 'Courses of study'],
                    (object) ['records' => array_values($courses)]
                );
            }

            // Export units of study this user authored.
            $units = $DB->get_records(self::TABLE_UNITS, ['usermodified' => $userid]);
            if ($units) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_educheckout_he'), 'Units of study'],
                    (object) ['records' => array_values($units)]
                );
            }

            // Export unit-course mappings this user authored.
            $mappings = $DB->get_records(self::TABLE_UNITCOURSES, ['usermodified' => $userid]);
            if ($mappings) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_educheckout_he'), 'Unit-course mappings'],
                    (object) ['records' => array_values($mappings)]
                );
            }

            // Export this learner's own higher education record.
            $students = $DB->get_records(self::TABLE_STUDENTS, ['userid' => $userid]);
            if ($students) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_educheckout_he'), 'Higher education elements'],
                    (object) ['records' => array_values($students)]
                );
            }

            // Export the audit trail for other learners' records this user
            // edited (their own record is covered above via userid). Only the
            // editor's own audit fields are exported: another learner's student
            // elements are that learner's personal data, so an editor's export
            // must not disclose them.
            $edited = $DB->get_records_select(
                self::TABLE_STUDENTS,
                'usermodified = :usermodified AND userid <> :userid',
                ['usermodified' => $userid, 'userid' => $userid]
            );
            if ($edited) {
                $audit = array_map(static function ($record) {
                    return (object) [
                        'studentid'    => $record->id,
                        'timemodified' => $record->timemodified,
                    ];
                }, array_values($edited));
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_educheckout_he'), 'Higher education records edited'],
                    (object) ['records' => $audit]
                );
            }
        }
    }

    /**
     * Deletes all personal data for all users in the given context.
     *
     * The catalogue records are configuration and are kept; only the authoring
     * reference is anonymised.
     *
     * @param  \context $context The context to delete within.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!($context instanceof \context_system)) {
            return;
        }

        $DB->set_field_select(self::TABLE_COURSES, 'usermodified', 0, 'usermodified <> ?', [0]);
        $DB->set_field_select(self::TABLE_UNITS, 'usermodified', 0, 'usermodified <> ?', [0]);
        $DB->set_field_select(self::TABLE_UNITCOURSES, 'usermodified', 0, 'usermodified <> ?', [0]);
        // Student records are learner data: delete them outright.
        $DB->delete_records(self::TABLE_STUDENTS);
    }

    /**
     * Deletes personal data for the given user in the approved contexts.
     *
     * @param  approved_contextlist $contextlist The approved contexts to delete within.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!($context instanceof \context_system)) {
                continue;
            }

            // Anonymise this admin's authorship of any catalogue record.
            $DB->set_field(self::TABLE_COURSES, 'usermodified', 0, ['usermodified' => $userid]);
            $DB->set_field(self::TABLE_UNITS, 'usermodified', 0, ['usermodified' => $userid]);
            $DB->set_field(self::TABLE_UNITCOURSES, 'usermodified', 0, ['usermodified' => $userid]);

            // This learner's own higher education record goes; their authorship
            // of another learner's record is anonymised, not deleted.
            $DB->delete_records(self::TABLE_STUDENTS, ['userid' => $userid]);
            $DB->set_field(self::TABLE_STUDENTS, 'usermodified', 0, ['usermodified' => $userid]);
        }
    }

    /**
     * Deletes personal data for the given users in the approved context.
     *
     * @param  approved_userlist $userlist The approved users to delete for.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();

        if (!($context instanceof \context_system)) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $DB->set_field_select(self::TABLE_COURSES, 'usermodified', 0, "usermodified $insql", $inparams);
        $DB->set_field_select(self::TABLE_UNITS, 'usermodified', 0, "usermodified $insql", $inparams);
        $DB->set_field_select(self::TABLE_UNITCOURSES, 'usermodified', 0, "usermodified $insql", $inparams);

        // These learners' own higher education records go; their authorship of
        // another learner's record is anonymised, not deleted.
        $DB->delete_records_select(self::TABLE_STUDENTS, "userid $insql", $inparams);
        $DB->set_field_select(self::TABLE_STUDENTS, 'usermodified', 0, "usermodified $insql", $inparams);
    }
}
