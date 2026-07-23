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
 * The pathway's structural catalogue — courses of study and units of study —
 * is institutional configuration, not learner data. The only personal data it
 * holds is the authoring reference (usermodified) recording which admin created
 * or last changed each record. That reference is exported for, and anonymised
 * on erasure of, that admin, mirroring the way core treats its shared-config
 * tables (fee structures, timetabling resources). All data is held at the
 * system context.
 *
 * When later lanes add learner-data tables (TCSI student and enrolment
 * elements are personal data), this provider gains those tables.
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

        return $collection;
    }

    /**
     * Returns the contexts that contain personal data for the given user.
     *
     * All higher education pathway data lives in the system context. A user
     * appears here only as the author (usermodified) of a catalogue record.
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
                     OR EXISTS (SELECT 1 FROM {" . self::TABLE_UNITS . "} uos WHERE uos.usermodified = :userid2))";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_SYSTEM,
            'userid1'      => $userid,
            'userid2'      => $userid,
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
    }
}
