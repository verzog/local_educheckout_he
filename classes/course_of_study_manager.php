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
 * Courses of study (award courses) for the higher education pathway.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he;

/**
 * Stores and administers higher education courses of study.
 *
 * A course of study is an award course a student is admitted to (a TCSI course
 * of study) — the parent structure that units of study hang off. This manager
 * is the CRUD layer. Courses of study are institutional configuration, so only
 * the authoring reference is per-user. Gated by the pathway kill-switch.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */
class course_of_study_manager {
    /** The courses of study table. */
    const TABLE = 'local_educheckout_he_coursesofstudy';

    /** An undergraduate award course. */
    const LEVEL_UNDERGRADUATE = 'undergraduate';

    /** A postgraduate coursework award course. */
    const LEVEL_POSTGRADUATE = 'postgraduate';

    /** A higher degree by research. */
    const LEVEL_HIGHERDEGREE = 'higherdegree';

    /** An enabling / preparatory course. */
    const LEVEL_ENABLING = 'enabling';

    /** Anything that does not fit the other levels. */
    const LEVEL_OTHER = 'other';

    /**
     * Whether the higher education pathway is enabled.
     *
     * The kill-switch (CLAUDE.md §8): with it off, the courses-of-study pages
     * are unavailable. Delegates to the pathway master switch.
     *
     * @return bool True when the pathway is enabled.
     */
    public static function is_enabled(): bool {
        return pathway_provider::is_enabled();
    }

    /**
     * Returns the valid course levels.
     *
     * @return string[] The course level machine names.
     */
    public static function levels(): array {
        return [
            self::LEVEL_UNDERGRADUATE,
            self::LEVEL_POSTGRADUATE,
            self::LEVEL_HIGHERDEGREE,
            self::LEVEL_ENABLING,
            self::LEVEL_OTHER,
        ];
    }

    /**
     * Creates or updates a course of study, returning its id.
     *
     * An unknown level is coerced to "other" so a bad value never lands in the
     * store. A blank CRICOS code is stored as null.
     *
     * @param  \stdClass $data The course of study fields (id when updating).
     * @return int             The saved course of study id.
     */
    public static function save(\stdClass $data): int {
        global $DB, $USER;

        $now = time();
        $level = in_array($data->courselevel ?? '', self::levels(), true)
            ? $data->courselevel
            : self::LEVEL_OTHER;
        // Blank stays null (not CRICOS-registered); a given code is trimmed.
        $cricos = (isset($data->cricoscode) && trim((string) $data->cricoscode) !== '')
            ? trim((string) $data->cricoscode)
            : null;

        $record = (object) [
            'code'         => trim((string) ($data->code ?? '')),
            'name'         => trim((string) ($data->name ?? '')),
            'cricoscode'   => $cricos,
            'courselevel'  => $level,
            // Default to active when enabled is not supplied, matching the
            // table/form default.
            'enabled'      => !isset($data->enabled) ? 1 : (empty($data->enabled) ? 0 : 1),
            'timemodified' => $now,
            'usermodified' => (int) $USER->id,
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
     * Returns a course of study by id, or null if it does not exist.
     *
     * @param  int             $id The course of study id.
     * @return \stdClass|null       The record, or null.
     */
    public static function get(int $id): ?\stdClass {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', IGNORE_MISSING);

        return $record ?: null;
    }

    /**
     * Returns courses of study, ordered by name.
     *
     * @param  bool  $enabledonly Whether to return only active courses.
     * @return array              Course of study records keyed by id.
     */
    public static function get_all(bool $enabledonly = false): array {
        global $DB;

        $conditions = $enabledonly ? ['enabled' => 1] : [];

        return $DB->get_records(self::TABLE, $conditions, 'name ASC');
    }

    /**
     * Returns whether a course of study code is already in use.
     *
     * The code is the unique institutional identifier; the form validates
     * against this before saving. An optional id is excluded so editing a
     * record does not clash with itself.
     *
     * @param  string $code      The course of study code.
     * @param  int    $excludeid A record id to ignore (0 = none).
     * @return bool              True when the code is taken by another record.
     */
    public static function code_exists(string $code, int $excludeid = 0): bool {
        global $DB;

        $params = ['code' => trim($code)];
        $select = 'code = :code';
        if ($excludeid) {
            $select .= ' AND id <> :excludeid';
            $params['excludeid'] = $excludeid;
        }

        return $DB->record_exists_select(self::TABLE, $select, $params);
    }

    /**
     * Deletes a course of study.
     *
     * The caller must ensure the course of study has no units of study first;
     * the units reference it, so removing it would orphan them.
     *
     * @param  int  $id The course of study id.
     * @return bool     True if a course of study was removed.
     */
    public static function delete(int $id): bool {
        global $DB;

        return $DB->delete_records(self::TABLE, ['id' => $id]);
    }
}
