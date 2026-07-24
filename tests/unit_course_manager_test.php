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
 * Unit tests for the higher education unit-course mapping manager.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he;

/**
 * Tests unit-course mapping storage and the enrolment-ownership lookup.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 * @covers     \local_educheckout_he\unit_course_manager
 */
final class unit_course_manager_test extends \advanced_testcase {
    /** @var int A parent course of study. */
    private int $cosid;

    /** @var int A unit of study under the course of study. */
    private int $unitid;

    /** @var int A Moodle course to map. */
    private int $courseid;

    /**
     * Resets the database, acts as admin, and builds a course/unit/course.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->cosid = course_of_study_manager::save((object) [
            'code'        => 'BSCI',
            'name'        => 'Bachelor of Science',
            'courselevel' => course_of_study_manager::LEVEL_UNDERGRADUATE,
        ]);
        $this->unitid = unit_of_study_manager::save((object) [
            'courseofstudyid' => $this->cosid,
            'code'            => 'SCI101',
            'name'            => 'Intro to Science',
            'eftsl'           => '0.125',
            'deliverymode'    => 'internal',
        ]);
        $this->courseid = (int) $this->getDataGenerator()->create_course()->id;
    }

    /**
     * map() creates a mapping and is idempotent on the same pair.
     *
     * @return void
     */
    public function test_map_is_idempotent(): void {
        $id = unit_course_manager::map($this->unitid, $this->courseid);
        $again = unit_course_manager::map($this->unitid, $this->courseid);

        $this->assertSame($id, $again);
        $this->assertSame(1, unit_course_manager::count_for_unit($this->unitid));
        $this->assertTrue(unit_course_manager::is_mapped($this->unitid, $this->courseid));
    }

    /**
     * get_for_unit() returns a unit's mappings; delete() removes one.
     *
     * @return void
     */
    public function test_get_for_unit_and_delete(): void {
        $other = (int) $this->getDataGenerator()->create_course()->id;
        $id1 = unit_course_manager::map($this->unitid, $this->courseid);
        unit_course_manager::map($this->unitid, $other);

        $this->assertCount(2, unit_course_manager::get_for_unit($this->unitid));

        $this->assertTrue(unit_course_manager::delete($id1));
        $this->assertCount(1, unit_course_manager::get_for_unit($this->unitid));
        $this->assertFalse(unit_course_manager::is_mapped($this->unitid, $this->courseid));
    }

    /**
     * course_is_owned() is true only for a mapped course whose unit and course
     * of study are both enabled.
     *
     * @return void
     */
    public function test_course_is_owned(): void {
        // Unmapped course is not owned.
        $this->assertFalse(unit_course_manager::course_is_owned($this->courseid));

        unit_course_manager::map($this->unitid, $this->courseid);
        $this->assertTrue(unit_course_manager::course_is_owned($this->courseid));
    }

    /**
     * A disabled unit of study takes its mapped course out of ownership.
     *
     * @return void
     */
    public function test_course_not_owned_when_unit_disabled(): void {
        unit_course_manager::map($this->unitid, $this->courseid);

        unit_of_study_manager::save((object) [
            'id'              => $this->unitid,
            'courseofstudyid' => $this->cosid,
            'code'            => 'SCI101',
            'name'            => 'Intro to Science',
            'eftsl'           => '0.125',
            'deliverymode'    => 'internal',
            'enabled'         => 0,
        ]);

        $this->assertFalse(unit_course_manager::course_is_owned($this->courseid));
    }

    /**
     * A disabled parent course of study takes the course out of ownership.
     *
     * @return void
     */
    public function test_course_not_owned_when_course_of_study_disabled(): void {
        unit_course_manager::map($this->unitid, $this->courseid);

        course_of_study_manager::save((object) [
            'id'          => $this->cosid,
            'code'        => 'BSCI',
            'name'        => 'Bachelor of Science',
            'courselevel' => course_of_study_manager::LEVEL_UNDERGRADUATE,
            'enabled'     => 0,
        ]);

        $this->assertFalse(unit_course_manager::course_is_owned($this->courseid));
    }

    /**
     * counts_by_unit() returns per-unit counts in one query and omits empties.
     *
     * @return void
     */
    public function test_counts_by_unit(): void {
        $emptyunit = unit_of_study_manager::save((object) [
            'courseofstudyid' => $this->cosid,
            'code'            => 'SCI102',
            'name'            => 'Second',
            'eftsl'           => '0.125',
            'deliverymode'    => 'internal',
        ]);
        $other = (int) $this->getDataGenerator()->create_course()->id;
        unit_course_manager::map($this->unitid, $this->courseid);
        unit_course_manager::map($this->unitid, $other);

        $counts = unit_course_manager::counts_by_unit([$this->unitid, $emptyunit]);
        $this->assertSame(2, $counts[$this->unitid]);
        // A unit with no mappings is absent (callers treat that as zero).
        $this->assertArrayNotHasKey($emptyunit, $counts);
        $this->assertSame([], unit_course_manager::counts_by_unit([]));
    }

    /**
     * Deleting a unit of study removes its course mappings too.
     *
     * @return void
     */
    public function test_unit_deletion_cascades_mappings(): void {
        unit_course_manager::map($this->unitid, $this->courseid);
        $this->assertSame(1, unit_course_manager::count_for_unit($this->unitid));

        unit_of_study_manager::delete($this->unitid);

        $this->assertSame(0, unit_course_manager::count_for_unit($this->unitid));
    }

    /**
     * Deleting a Moodle course clears its mappings through the observer.
     *
     * @return void
     */
    public function test_course_deletion_cleans_mappings(): void {
        unit_course_manager::map($this->unitid, $this->courseid);
        $this->assertTrue(unit_course_manager::is_mapped($this->unitid, $this->courseid));

        delete_course($this->courseid, false);

        $this->assertFalse(unit_course_manager::is_mapped($this->unitid, $this->courseid));
    }
}
