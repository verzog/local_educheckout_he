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
 * Unit tests for the higher education unit of study manager.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he;

/**
 * Tests unit-of-study storage, normalisation, scoping, and guards.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 * @covers     \local_educheckout_he\unit_of_study_manager
 */
final class unit_of_study_manager_test extends \advanced_testcase {
    /** @var int A parent course of study created for the tests. */
    private int $courseid;

    /**
     * Resets the database, acts as admin, and creates a parent course.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->courseid = course_of_study_manager::save((object) [
            'code'        => 'BSCI',
            'name'        => 'Bachelor of Science',
            'courselevel' => course_of_study_manager::LEVEL_UNDERGRADUATE,
        ]);
    }

    /**
     * save() creates a unit and normalises its fields.
     *
     * @return void
     */
    public function test_save_creates_and_normalises(): void {
        $id = unit_of_study_manager::save((object) [
            'courseofstudyid' => $this->courseid,
            'code'            => '  SCI101  ',
            'name'            => '  Intro to Science  ',
            'eftsl'           => '0.125',
            'foecode'         => '  010101  ',
            'deliverymode'    => unit_of_study_manager::MODE_INTERNAL,
            'enabled'         => 1,
        ]);

        $unit = unit_of_study_manager::get($id);
        $this->assertNotNull($unit);
        $this->assertSame($this->courseid, (int) $unit->courseofstudyid);
        $this->assertSame('SCI101', $unit->code);
        $this->assertSame('Intro to Science', $unit->name);
        $this->assertEqualsWithDelta(0.125, (float) $unit->eftsl, 0.0000001);
        $this->assertSame('010101', $unit->foecode);
        $this->assertSame(unit_of_study_manager::MODE_INTERNAL, $unit->deliverymode);
        $this->assertSame(1, (int) $unit->enabled);
    }

    /**
     * An unknown mode is coerced to internal, a negative EFTSL clamps to 0,
     * and a blank FOE code is stored as null.
     *
     * @return void
     */
    public function test_save_coerces_mode_clamps_eftsl_nulls_foe(): void {
        $id = unit_of_study_manager::save((object) [
            'courseofstudyid' => $this->courseid,
            'code'            => 'SCI102',
            'name'            => 'Second',
            'eftsl'           => '-5',
            'foecode'         => '   ',
            'deliverymode'    => 'teleport',
        ]);

        $unit = unit_of_study_manager::get($id);
        $this->assertSame(unit_of_study_manager::MODE_INTERNAL, $unit->deliverymode);
        $this->assertEqualsWithDelta(0.0, (float) $unit->eftsl, 0.0000001);
        $this->assertNull($unit->foecode);
        $this->assertSame(1, (int) $unit->enabled);
    }

    /**
     * get_all() scopes to a course of study and to active units.
     *
     * @return void
     */
    public function test_get_all_scoping(): void {
        $other = course_of_study_manager::save((object) [
            'code' => 'BA', 'name' => 'Bachelor of Arts', 'courselevel' => 'undergraduate',
        ]);
        unit_of_study_manager::save((object) [
            'courseofstudyid' => $this->courseid, 'code' => 'U1', 'name' => 'One',
            'eftsl' => '0.125', 'deliverymode' => 'internal', 'enabled' => 1,
        ]);
        unit_of_study_manager::save((object) [
            'courseofstudyid' => $this->courseid, 'code' => 'U2', 'name' => 'Two',
            'eftsl' => '0.125', 'deliverymode' => 'internal', 'enabled' => 0,
        ]);
        unit_of_study_manager::save((object) [
            'courseofstudyid' => $other, 'code' => 'U3', 'name' => 'Three',
            'eftsl' => '0.125', 'deliverymode' => 'internal', 'enabled' => 1,
        ]);

        $this->assertCount(3, unit_of_study_manager::get_all());
        $this->assertCount(2, unit_of_study_manager::get_all($this->courseid));
        $this->assertCount(1, unit_of_study_manager::get_all($this->courseid, true));
    }

    /**
     * code_exists() is scoped to the parent course and ignores the excluded id.
     *
     * @return void
     */
    public function test_code_exists_scoped_to_course(): void {
        $other = course_of_study_manager::save((object) [
            'code' => 'BA', 'name' => 'Bachelor of Arts', 'courselevel' => 'undergraduate',
        ]);
        $id = unit_of_study_manager::save((object) [
            'courseofstudyid' => $this->courseid, 'code' => 'DUP', 'name' => 'One',
            'eftsl' => '0.125', 'deliverymode' => 'internal',
        ]);

        $this->assertTrue(unit_of_study_manager::code_exists($this->courseid, 'DUP'));
        // The same code is free in a different course of study.
        $this->assertFalse(unit_of_study_manager::code_exists($other, 'DUP'));
        // The record does not clash with itself when editing.
        $this->assertFalse(unit_of_study_manager::code_exists($this->courseid, 'DUP', $id));
    }

    /**
     * count_for_course() counts a course's units, guarding parent deletion.
     *
     * @return void
     */
    public function test_count_for_course(): void {
        $this->assertSame(0, unit_of_study_manager::count_for_course($this->courseid));
        unit_of_study_manager::save((object) [
            'courseofstudyid' => $this->courseid, 'code' => 'U1', 'name' => 'One',
            'eftsl' => '0.125', 'deliverymode' => 'internal',
        ]);
        $this->assertSame(1, unit_of_study_manager::count_for_course($this->courseid));
    }

    /**
     * delete() removes the unit of study.
     *
     * @return void
     */
    public function test_delete(): void {
        $id = unit_of_study_manager::save((object) [
            'courseofstudyid' => $this->courseid, 'code' => 'D', 'name' => 'Doomed',
            'eftsl' => '0.125', 'deliverymode' => 'internal',
        ]);

        $this->assertTrue(unit_of_study_manager::delete($id));
        $this->assertNull(unit_of_study_manager::get($id));
    }
}
