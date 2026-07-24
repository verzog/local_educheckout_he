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
 * Per-learner higher education elements for the higher education pathway.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he;

/**
 * Stores and answers a learner's higher education elements.
 *
 * These are the TCSI student elements Moodle does not hold: citizenship /
 * residency status, USI and CHESSN, disability, and highest prior educational
 * attainment. Exactly one record exists per learner, keyed by userid, so a save
 * is an upsert. This is the learner's personal data — the disability element is
 * special-category — so the surface is capability-gated and off by default; the
 * coded fields store internal codes mapped to TCSI code sets at export time.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */
class student_manager {
    /** The student higher-education-elements table. */
    const TABLE = 'local_educheckout_he_students';

    /** The "not stated" code, shared by every coded field as the default. */
    const NOT_STATED = 'notstated';

    /**
     * Whether the student higher education elements subsystem is enabled.
     *
     * A dedicated opt-in (off by default) on top of the pathway kill-switch:
     * this is sensitive learner data, so enabling the pathway does not silently
     * start collecting it. Both switches must be on.
     *
     * @return bool True when the subsystem is enabled.
     */
    public static function is_enabled(): bool {
        return pathway_provider::is_enabled()
            && (string) get_config('local_educheckout_he', 'he_students_enabled') === '1';
    }

    /**
     * Returns the citizenship / residency status codes.
     *
     * @return string[] The citizenship code set.
     */
    public static function citizenships(): array {
        return [
            'australian',
            'nzcitizen',
            'permanentresident',
            'permanenthumanitarian',
            'international',
            self::NOT_STATED,
        ];
    }

    /**
     * Returns the disability status codes.
     *
     * @return string[] The disability code set.
     */
    public static function disabilities(): array {
        return [self::NOT_STATED, 'none', 'reported'];
    }

    /**
     * Returns the highest prior educational attainment codes.
     *
     * @return string[] The prior-education code set.
     */
    public static function prioreducations(): array {
        return [self::NOT_STATED, 'none', 'secondary', 'vet', 'heincomplete', 'hecomplete', 'other'];
    }

    /**
     * Returns a learner's higher education record, or null when none exists.
     *
     * @param  int            $userid The learner user id.
     * @return \stdClass|null         The record, or null.
     */
    public static function get(int $userid): ?\stdClass {
        global $DB;

        if ($userid <= 0) {
            return null;
        }

        $record = $DB->get_record(self::TABLE, ['userid' => $userid], '*', IGNORE_MISSING);

        return $record ?: null;
    }

    /**
     * Creates or updates a learner's higher education record.
     *
     * One record exists per learner, so this upserts on userid. Coded fields
     * are coerced to a valid code (falling back to "not stated"); a blank USI
     * or CHESSN is stored as null.
     *
     * @param  int       $userid The learner user id.
     * @param  \stdClass $data   The submitted values.
     * @return int               The record id, or 0 if refused.
     */
    public static function save(int $userid, \stdClass $data): int {
        global $DB, $USER;

        if ($userid <= 0) {
            return 0;
        }

        $now = time();
        $record = new \stdClass();
        $record->citizenship = self::coerce($data->citizenship ?? '', self::citizenships());
        $record->disability = self::coerce($data->disability ?? '', self::disabilities());
        $record->prioreducation = self::coerce($data->prioreducation ?? '', self::prioreducations());
        $record->usi = self::clean_identifier($data->usi ?? '');
        $record->chessn = self::clean_identifier($data->chessn ?? '');
        $record->timemodified = $now;
        $record->usermodified = (int) $USER->id;

        $existing = self::get($userid);
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record(self::TABLE, $record);
            return (int) $existing->id;
        }

        $record->userid = $userid;
        $record->timecreated = $now;

        return (int) $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Removes a learner's higher education record.
     *
     * Used by the privacy erasure path and available for administrative
     * cleanup. A no-op when the learner has no record.
     *
     * @param  int  $userid The learner user id.
     * @return void
     */
    public static function delete(int $userid): void {
        global $DB;

        if ($userid <= 0) {
            return;
        }

        $DB->delete_records(self::TABLE, ['userid' => $userid]);
    }

    /**
     * Coerces a submitted code to one of an allowed set.
     *
     * An unknown or empty value falls back to "not stated" so a bad code never
     * lands in the store.
     *
     * @param  string   $value   The submitted code.
     * @param  string[] $allowed The allowed code set.
     * @return string            A valid code.
     */
    private static function coerce(string $value, array $allowed): string {
        return in_array($value, $allowed, true) ? $value : self::NOT_STATED;
    }

    /**
     * Normalises a student identifier (USI / CHESSN): trimmed, uppercased.
     *
     * A blank value is stored as null.
     *
     * @param  string      $value The submitted identifier.
     * @return string|null        The cleaned identifier, or null when blank.
     */
    private static function clean_identifier(string $value): ?string {
        $value = strtoupper(trim($value));

        return ($value === '') ? null : $value;
    }
}
