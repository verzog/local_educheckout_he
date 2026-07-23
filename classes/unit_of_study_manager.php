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
 * Units of study (reportable teaching units) for the higher education pathway.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he;

/**
 * Stores and administers higher education units of study.
 *
 * A unit of study is a reportable teaching unit within a course of study (a
 * TCSI unit of study), carrying the EFTSL load and field-of-education code TCSI
 * reports on. This manager is the CRUD layer. Units are institutional
 * configuration, so only the authoring reference is per-user. Gated by the
 * pathway kill-switch. A later lane maps a unit to the Moodle course(s) that
 * deliver it, which is what makes the pathway claim an enrolment.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */
class unit_of_study_manager {
    /** The units of study table. */
    const TABLE = 'local_educheckout_he_unitsofstudy';

    /** Delivered on campus / face to face. */
    const MODE_INTERNAL = 'internal';

    /** Delivered by distance / online. */
    const MODE_EXTERNAL = 'external';

    /** A mix of internal and external delivery. */
    const MODE_MULTIMODAL = 'multimodal';

    /**
     * Whether the higher education pathway is enabled.
     *
     * The kill-switch (CLAUDE.md §8): with it off, the units-of-study pages are
     * unavailable. Delegates to the pathway master switch.
     *
     * @return bool True when the pathway is enabled.
     */
    public static function is_enabled(): bool {
        return pathway_provider::is_enabled();
    }

    /**
     * Returns the valid delivery modes.
     *
     * @return string[] The delivery mode machine names.
     */
    public static function modes(): array {
        return [self::MODE_INTERNAL, self::MODE_EXTERNAL, self::MODE_MULTIMODAL];
    }

    /**
     * Creates or updates a unit of study, returning its id.
     *
     * An unknown delivery mode is coerced to "internal" so a bad value never
     * lands in the store. The EFTSL is clamped to a non-negative number and a
     * blank field-of-education code is stored as null.
     *
     * @param  \stdClass $data The unit of study fields (id when updating).
     * @return int             The saved unit of study id.
     */
    public static function save(\stdClass $data): int {
        global $DB, $USER;

        $now = time();
        $mode = in_array($data->deliverymode ?? '', self::modes(), true)
            ? $data->deliverymode
            : self::MODE_INTERNAL;
        // Clamp the load into the eftsl column's range — NUMBER(10,5), so
        // 0 to 99999.99999 — so no caller can overflow the column. The form
        // rejects out-of-range input with a message; this is the backstop.
        $eftsl = min(99999.99999, max(0, (float) ($data->eftsl ?? 0)));
        // Blank stays null (unspecified); a given code is trimmed.
        $foecode = (isset($data->foecode) && trim((string) $data->foecode) !== '')
            ? trim((string) $data->foecode)
            : null;

        $record = (object) [
            'courseofstudyid' => (int) ($data->courseofstudyid ?? 0),
            'code'            => trim((string) ($data->code ?? '')),
            'name'            => trim((string) ($data->name ?? '')),
            'eftsl'           => $eftsl,
            'foecode'         => $foecode,
            'deliverymode'    => $mode,
            // Default to active when enabled is not supplied, matching the
            // table/form default.
            'enabled'         => !isset($data->enabled) ? 1 : (empty($data->enabled) ? 0 : 1),
            'timemodified'    => $now,
            'usermodified'    => (int) $USER->id,
        ];

        if (!empty($data->id)) {
            $record->id = (int) $data->id;
            $DB->update_record(self::TABLE, $record);
            return $record->id;
        }

        $record->timecreated = $now;

        return (int) $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Returns a unit of study by id, or null if it does not exist.
     *
     * @param  int             $id The unit of study id.
     * @return \stdClass|null       The record, or null.
     */
    public static function get(int $id): ?\stdClass {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', IGNORE_MISSING);

        return $record ?: null;
    }

    /**
     * Returns units of study, optionally scoped to one course of study.
     *
     * @param  int   $courseofstudyid A course of study id to filter by (0 = all).
     * @param  bool  $enabledonly     Whether to return only active units.
     * @return array                  Unit of study records keyed by id.
     */
    public static function get_all(int $courseofstudyid = 0, bool $enabledonly = false): array {
        global $DB;

        $conditions = [];
        if ($courseofstudyid) {
            $conditions['courseofstudyid'] = $courseofstudyid;
        }
        if ($enabledonly) {
            $conditions['enabled'] = 1;
        }

        return $DB->get_records(self::TABLE, $conditions, 'code ASC');
    }

    /**
     * Returns whether a unit code is already used within a course of study.
     *
     * A unit code is unique within its parent course of study; the form
     * validates against this before saving. An optional id is excluded so
     * editing a record does not clash with itself.
     *
     * @param  int    $courseofstudyid The parent course of study id.
     * @param  string $code            The unit of study code.
     * @param  int    $excludeid       A record id to ignore (0 = none).
     * @return bool                    True when the code is taken in that course.
     */
    public static function code_exists(int $courseofstudyid, string $code, int $excludeid = 0): bool {
        global $DB;

        $params = ['courseofstudyid' => $courseofstudyid, 'code' => trim($code)];
        $select = 'courseofstudyid = :courseofstudyid AND code = :code';
        if ($excludeid) {
            $select .= ' AND id <> :excludeid';
            $params['excludeid'] = $excludeid;
        }

        return $DB->record_exists_select(self::TABLE, $select, $params);
    }

    /**
     * Returns the number of units of study in a course of study.
     *
     * Used to guard deletion of a course of study that still has units.
     *
     * @param  int $courseofstudyid The course of study id.
     * @return int                  The count of units.
     */
    public static function count_for_course(int $courseofstudyid): int {
        global $DB;

        return $DB->count_records(self::TABLE, ['courseofstudyid' => $courseofstudyid]);
    }

    /**
     * Deletes a unit of study.
     *
     * @param  int  $id The unit of study id.
     * @return bool     True if a unit of study was removed.
     */
    public static function delete(int $id): bool {
        global $DB;

        return $DB->delete_records(self::TABLE, ['id' => $id]);
    }
}
