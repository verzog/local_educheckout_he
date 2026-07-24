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
 * Unit tests for the higher education student-elements manager.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he;

/**
 * Tests student higher-education-element storage, coercion, and the switch.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 * @covers     \local_educheckout_he\student_manager
 */
final class student_manager_test extends \advanced_testcase {
    /** @var int A learner user id. */
    private int $userid;

    /**
     * Resets the database, acts as admin, and creates a learner.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->userid = (int) $this->getDataGenerator()->create_user()->id;
    }

    /**
     * save() creates a record and normalises its fields.
     *
     * @return void
     */
    public function test_save_creates_and_normalises(): void {
        $id = student_manager::save($this->userid, (object) [
            'citizenship'    => 'australian',
            'usi'            => '  abcde12345  ',
            'chessn'         => '1234567890',
            'disability'     => 'reported',
            'prioreducation' => 'secondary',
        ]);

        $record = student_manager::get($this->userid);
        $this->assertNotNull($record);
        $this->assertSame($id, (int) $record->id);
        $this->assertSame('australian', $record->citizenship);
        // USI is trimmed and uppercased.
        $this->assertSame('ABCDE12345', $record->usi);
        $this->assertSame('1234567890', $record->chessn);
        $this->assertSame('reported', $record->disability);
        $this->assertSame('secondary', $record->prioreducation);
    }

    /**
     * Unknown codes fall back to "not stated" and blank identifiers to null.
     *
     * @return void
     */
    public function test_save_coerces_codes_and_nulls_blank_ids(): void {
        student_manager::save($this->userid, (object) [
            'citizenship'    => 'martian',
            'usi'            => '   ',
            'chessn'         => '',
            'disability'     => 'bogus',
            'prioreducation' => 'nope',
        ]);

        $record = student_manager::get($this->userid);
        $this->assertSame(student_manager::NOT_STATED, $record->citizenship);
        $this->assertSame(student_manager::NOT_STATED, $record->disability);
        $this->assertSame(student_manager::NOT_STATED, $record->prioreducation);
        $this->assertNull($record->usi);
        $this->assertNull($record->chessn);
    }

    /**
     * save() upserts on userid — a second save updates, not inserts.
     *
     * @return void
     */
    public function test_save_upserts_on_userid(): void {
        $first = student_manager::save($this->userid, (object) ['citizenship' => 'australian']);
        $second = student_manager::save($this->userid, (object) ['citizenship' => 'international']);

        $this->assertSame($first, $second);
        $this->assertSame('international', student_manager::get($this->userid)->citizenship);
        $this->assertCount(1, $GLOBALS['DB']->get_records(student_manager::TABLE));
    }

    /**
     * A non-positive user id is refused rather than stored.
     *
     * @return void
     */
    public function test_save_refuses_invalid_user(): void {
        $this->assertSame(0, student_manager::save(0, (object) ['citizenship' => 'australian']));
        $this->assertCount(0, $GLOBALS['DB']->get_records(student_manager::TABLE));
    }

    /**
     * delete() removes the learner's record.
     *
     * @return void
     */
    public function test_delete(): void {
        student_manager::save($this->userid, (object) ['citizenship' => 'australian']);
        student_manager::delete($this->userid);
        $this->assertNull(student_manager::get($this->userid));
    }

    /**
     * is_enabled() requires both the pathway and the student sub-switch.
     *
     * @return void
     */
    public function test_is_enabled_requires_both_switches(): void {
        $this->assertFalse(student_manager::is_enabled());

        set_config('he_enabled', '1', 'local_educheckout_he');
        $this->assertFalse(student_manager::is_enabled());

        set_config('he_students_enabled', '1', 'local_educheckout_he');
        $this->assertTrue(student_manager::is_enabled());

        set_config('he_enabled', '0', 'local_educheckout_he');
        $this->assertFalse(student_manager::is_enabled());
    }
}
