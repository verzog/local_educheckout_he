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
 * Form to map a Moodle course to a higher education unit of study.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_educheckout_he\unit_course_manager;

/**
 * Captures the Moodle course to map to a unit of study.
 *
 * The parent unit of study id is passed in via customdata under 'unitofstudyid'
 * so a submitted course can be checked for an existing mapping.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */
class unit_course_form extends \moodleform {
    /**
     * Defines the mapping form fields.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $component = 'local_educheckout_he';

        $mform->addElement('hidden', 'unitofstudyid', 0);
        $mform->setType('unitofstudyid', PARAM_INT);

        // The course autocomplete selector (single course).
        $mform->addElement(
            'course',
            'courseid',
            get_string('uc_course', $component),
            ['multiple' => false]
        );
        $mform->addRule('courseid', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('uc_add', $component));
    }

    /**
     * Validates the chosen course: a real course, not the site, not already mapped.
     *
     * @param  array $data  Submitted values.
     * @param  array $files Submitted files.
     * @return array        Validation errors keyed by field name.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $courseid = (int) ($data['courseid'] ?? 0);
        $unitofstudyid = (int) ($data['unitofstudyid'] ?? 0);

        if ($courseid <= 0 || $courseid == SITEID) {
            $errors['courseid'] = get_string('uc_course_invalid', 'local_educheckout_he');
        } else if ($unitofstudyid && unit_course_manager::is_mapped($unitofstudyid, $courseid)) {
            $errors['courseid'] = get_string('uc_already_mapped', 'local_educheckout_he');
        }

        return $errors;
    }
}
