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
 * Unit-of-study to Moodle-course mapping for the higher education pathway.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he;

/**
 * Maps a unit of study to the Moodle course(s) that deliver it.
 *
 * This mapping is what makes the pathway claim an enrolment. course_is_owned()
 * answers the enrolment-ownership question the observer asks on every new
 * enrolment — true when the course maps to a unit of study that (and whose
 * parent course of study) is enabled — as a single indexed lookup. Mappings
 * are institutional configuration; only the authoring reference is per-user.
 * Gated by the pathway kill-switch.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */
class unit_course_manager {
    /** The unit-course mapping table. */
    const TABLE = 'local_educheckout_he_unitcourses';

    /**
     * Whether the higher education pathway is enabled.
     *
     * @return bool True when the pathway is enabled.
     */
    public static function is_enabled(): bool {
        return pathway_provider::is_enabled();
    }

    /**
     * Maps a unit of study to a Moodle course, returning the mapping id.
     *
     * Idempotent: a pair that is already mapped returns the existing id rather
     * than inserting a duplicate (the unique index also enforces this).
     *
     * @param  int $unitofstudyid The unit of study id.
     * @param  int $courseid      The Moodle course id.
     * @return int                The mapping id.
     */
    public static function map(int $unitofstudyid, int $courseid): int {
        global $DB, $USER;

        $conditions = ['unitofstudyid' => $unitofstudyid, 'courseid' => $courseid];
        $existingid = $DB->get_field(self::TABLE, 'id', $conditions, IGNORE_MISSING);
        if ($existingid) {
            return (int) $existingid;
        }

        $now = time();
        $record = (object) [
            'unitofstudyid' => $unitofstudyid,
            'courseid'      => $courseid,
            'timecreated'   => $now,
            'timemodified'  => $now,
            'usermodified'  => (int) $USER->id,
        ];

        try {
            return (int) $DB->insert_record(self::TABLE, $record);
        } catch (\dml_exception $e) {
            // A concurrent request (or a double-submit) may have inserted the
            // same pair between the check above and here, tripping the unique
            // index. Re-read and return the winner so map() stays idempotent.
            $existingid = $DB->get_field(self::TABLE, 'id', $conditions, IGNORE_MISSING);
            if ($existingid) {
                return (int) $existingid;
            }
            throw $e;
        }
    }

    /**
     * Returns whether a unit/course pair is already mapped.
     *
     * @param  int  $unitofstudyid The unit of study id.
     * @param  int  $courseid      The Moodle course id.
     * @return bool                True when the pair is mapped.
     */
    public static function is_mapped(int $unitofstudyid, int $courseid): bool {
        global $DB;

        $conditions = ['unitofstudyid' => $unitofstudyid, 'courseid' => $courseid];

        return $DB->record_exists(self::TABLE, $conditions);
    }

    /**
     * Returns a mapping by id, or null if it does not exist.
     *
     * @param  int             $id The mapping id.
     * @return \stdClass|null       The record, or null.
     */
    public static function get(int $id): ?\stdClass {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', IGNORE_MISSING);

        return $record ?: null;
    }

    /**
     * Returns the course mappings for a unit of study, ordered by id.
     *
     * @param  int   $unitofstudyid The unit of study id.
     * @return array                Mapping records keyed by id.
     */
    public static function get_for_unit(int $unitofstudyid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['unitofstudyid' => $unitofstudyid], 'id ASC');
    }

    /**
     * Returns the number of courses mapped to a unit of study.
     *
     * @param  int $unitofstudyid The unit of study id.
     * @return int                The count of mapped courses.
     */
    public static function count_for_unit(int $unitofstudyid): int {
        global $DB;

        return $DB->count_records(self::TABLE, ['unitofstudyid' => $unitofstudyid]);
    }

    /**
     * Returns mapped-course counts for many units in a single query.
     *
     * Keeps a listing that shows a per-unit count off the one-query-per-row
     * path. Units with no mappings are absent from the result (treat as 0).
     *
     * @param  int[] $unitids The unit of study ids.
     * @return array          Map of unit of study id → mapped-course count.
     */
    public static function counts_by_unit(array $unitids): array {
        global $DB;

        if (empty($unitids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($unitids, SQL_PARAMS_NAMED);
        $sql = "SELECT unitofstudyid, COUNT(*) AS cnt
                  FROM {" . self::TABLE . "}
                 WHERE unitofstudyid $insql
              GROUP BY unitofstudyid";

        $counts = [];
        foreach ($DB->get_records_sql($sql, $inparams) as $row) {
            $counts[(int) $row->unitofstudyid] = (int) $row->cnt;
        }

        return $counts;
    }

    /**
     * Deletes a mapping.
     *
     * @param  int  $id The mapping id.
     * @return bool     True if a mapping was removed.
     */
    public static function delete(int $id): bool {
        global $DB;

        return $DB->delete_records(self::TABLE, ['id' => $id]);
    }

    /**
     * Deletes every mapping belonging to a unit of study.
     *
     * Called when the unit is deleted so no orphan mappings are left behind.
     *
     * @param  int $unitofstudyid The unit of study id.
     * @return void
     */
    public static function delete_for_unit(int $unitofstudyid): void {
        global $DB;

        $DB->delete_records(self::TABLE, ['unitofstudyid' => $unitofstudyid]);
    }

    /**
     * Deletes every mapping that references a Moodle course.
     *
     * Called from the course_deleted observer so a removed course does not
     * leave orphan mappings behind.
     *
     * @param  int $courseid The Moodle course id.
     * @return void
     */
    public static function delete_for_course(int $courseid): void {
        global $DB;

        $DB->delete_records(self::TABLE, ['courseid' => $courseid]);
    }

    /**
     * Returns whether a Moodle course is owned by the higher education pathway.
     *
     * A course is owned when it maps to a unit of study that is enabled and
     * whose parent course of study is enabled. This is the enrolment-ownership
     * test the core observer calls on every new enrolment, so it is a single
     * indexed lookup (the courseid index on the mapping, joined to the unit and
     * course-of-study primary keys) — never a full scan.
     *
     * @param  int  $courseid The Moodle course id being enrolled in.
     * @return bool           True if this pathway claims the course.
     */
    public static function course_is_owned(int $courseid): bool {
        global $DB;

        $sql = "SELECT 1
                  FROM {" . self::TABLE . "} uc
                  JOIN {local_educheckout_he_unitsofstudy} us ON us.id = uc.unitofstudyid
                  JOIN {local_educheckout_he_coursesofstudy} cos ON cos.id = us.courseofstudyid
                 WHERE uc.courseid = :courseid
                   AND us.enabled = 1
                   AND cos.enabled = 1";

        return $DB->record_exists_sql($sql, ['courseid' => $courseid]);
    }
}
