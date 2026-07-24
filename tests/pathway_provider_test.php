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
 * Unit tests for the higher education pathway provider.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he;

/**
 * Tests the pathway provider's enrolment-ownership decision.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 * @covers     \local_educheckout_he\pathway_provider
 */
final class pathway_provider_test extends \advanced_testcase {
    /** @var int A Moodle course mapped to an enabled unit of study. */
    private int $courseid;

    /**
     * Resets the database and maps a course to an enabled unit of study.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $cosid = course_of_study_manager::save((object) [
            'code'        => 'BSCI',
            'name'        => 'Bachelor of Science',
            'courselevel' => course_of_study_manager::LEVEL_UNDERGRADUATE,
        ]);
        $unitid = unit_of_study_manager::save((object) [
            'courseofstudyid' => $cosid,
            'code'            => 'SCI101',
            'name'            => 'Intro to Science',
            'eftsl'           => '0.125',
            'deliverymode'    => 'internal',
        ]);
        $this->courseid = (int) $this->getDataGenerator()->create_course()->id;
        unit_course_manager::map($unitid, $this->courseid);
    }

    /**
     * The identity methods return the pathway's fixed values.
     *
     * @return void
     */
    public function test_identity(): void {
        $this->assertSame('he', pathway_provider::get_name());
        $this->assertSame('local_educheckout_he', pathway_provider::get_component());
    }

    /**
     * owns_enrolment() claims a mapped course only while the pathway is enabled.
     *
     * @return void
     */
    public function test_owns_enrolment_respects_kill_switch(): void {
        // Off by default: nothing is claimed even though the course is mapped.
        $this->assertFalse(pathway_provider::owns_enrolment(0, $this->courseid));

        set_config('he_enabled', '1', 'local_educheckout_he');
        $this->assertTrue(pathway_provider::owns_enrolment(0, $this->courseid));
    }

    /**
     * An unmapped course is never claimed, even when the pathway is enabled.
     *
     * @return void
     */
    public function test_owns_enrolment_ignores_unmapped_course(): void {
        set_config('he_enabled', '1', 'local_educheckout_he');
        $unmapped = (int) $this->getDataGenerator()->create_course()->id;

        $this->assertFalse(pathway_provider::owns_enrolment(0, $unmapped));
    }
}
