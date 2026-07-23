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
 * Unit tests for the higher education course of study manager.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he;

/**
 * Tests course-of-study storage, normalisation, and the kill-switch.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 * @covers     \local_educheckout_he\course_of_study_manager
 */
final class course_of_study_manager_test extends \advanced_testcase {
    /**
     * Resets the database and acts as the admin before each test.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * save() creates a course of study and normalises its fields.
     *
     * @return void
     */
    public function test_save_creates_and_normalises(): void {
        $id = course_of_study_manager::save((object) [
            'code'        => '  BSCI  ',
            'name'        => '  Bachelor of Science  ',
            'cricoscode'  => '  012345A  ',
            'courselevel' => course_of_study_manager::LEVEL_UNDERGRADUATE,
            'enabled'     => 1,
        ]);

        $course = course_of_study_manager::get($id);
        $this->assertNotNull($course);
        $this->assertSame('BSCI', $course->code);
        $this->assertSame('Bachelor of Science', $course->name);
        $this->assertSame('012345A', $course->cricoscode);
        $this->assertSame(course_of_study_manager::LEVEL_UNDERGRADUATE, $course->courselevel);
        $this->assertSame(1, (int) $course->enabled);
    }

    /**
     * An unknown level is coerced to "other" and a blank CRICOS code to null.
     *
     * @return void
     */
    public function test_save_coerces_level_and_nulls_blank_cricos(): void {
        $id = course_of_study_manager::save((object) [
            'code'        => 'X1',
            'name'        => 'Mystery',
            'cricoscode'  => '   ',
            'courselevel' => 'not-a-level',
        ]);

        $course = course_of_study_manager::get($id);
        $this->assertSame(course_of_study_manager::LEVEL_OTHER, $course->courselevel);
        $this->assertNull($course->cricoscode);
        // Enabled defaults to active when not supplied.
        $this->assertSame(1, (int) $course->enabled);
    }

    /**
     * update() writes over the existing record rather than inserting.
     *
     * @return void
     */
    public function test_save_updates_existing(): void {
        $id = course_of_study_manager::save((object) [
            'code'        => 'C1',
            'name'        => 'First',
            'courselevel' => course_of_study_manager::LEVEL_UNDERGRADUATE,
        ]);
        course_of_study_manager::save((object) [
            'id'          => $id,
            'code'        => 'C1',
            'name'        => 'Renamed',
            'courselevel' => course_of_study_manager::LEVEL_POSTGRADUATE,
            'enabled'     => 0,
        ]);

        $course = course_of_study_manager::get($id);
        $this->assertSame('Renamed', $course->name);
        $this->assertSame(course_of_study_manager::LEVEL_POSTGRADUATE, $course->courselevel);
        $this->assertSame(0, (int) $course->enabled);
        $this->assertCount(1, course_of_study_manager::get_all());
    }

    /**
     * get_all() filters to active courses when asked, and orders by name.
     *
     * @return void
     */
    public function test_get_all_enabled_only(): void {
        course_of_study_manager::save((object) [
            'code' => 'A', 'name' => 'Active', 'courselevel' => 'undergraduate', 'enabled' => 1,
        ]);
        course_of_study_manager::save((object) [
            'code' => 'R', 'name' => 'Retired', 'courselevel' => 'undergraduate', 'enabled' => 0,
        ]);

        $this->assertCount(2, course_of_study_manager::get_all());
        $this->assertCount(1, course_of_study_manager::get_all(true));
    }

    /**
     * code_exists() detects a duplicate code and ignores the excluded id.
     *
     * @return void
     */
    public function test_code_exists(): void {
        $id = course_of_study_manager::save((object) [
            'code' => 'DUP', 'name' => 'One', 'courselevel' => 'undergraduate',
        ]);

        $this->assertTrue(course_of_study_manager::code_exists('DUP'));
        $this->assertFalse(course_of_study_manager::code_exists('OTHER'));
        // The record does not clash with itself when editing.
        $this->assertFalse(course_of_study_manager::code_exists('DUP', $id));
    }

    /**
     * delete() removes the course of study.
     *
     * @return void
     */
    public function test_delete(): void {
        $id = course_of_study_manager::save((object) [
            'code' => 'D', 'name' => 'Doomed', 'courselevel' => 'undergraduate',
        ]);

        $this->assertTrue(course_of_study_manager::delete($id));
        $this->assertNull(course_of_study_manager::get($id));
    }

    /**
     * is_enabled() reflects the pathway kill-switch.
     *
     * @return void
     */
    public function test_is_enabled_reflects_switch(): void {
        $this->assertFalse(course_of_study_manager::is_enabled());
        set_config('he_enabled', '1', 'local_educheckout_he');
        $this->assertTrue(course_of_study_manager::is_enabled());
    }
}
