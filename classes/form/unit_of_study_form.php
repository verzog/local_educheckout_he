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
 * Add / edit form for a higher education unit of study.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_educheckout_he\unit_of_study_manager;

/**
 * Captures a unit's parent course, code, name, EFTSL, FOE code, and mode.
 *
 * The parent course of study options are passed in via customdata under the
 * 'courses' key (an id → label map).
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */
class unit_of_study_form extends \moodleform {
    /**
     * Defines the unit of study form fields.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $component = 'local_educheckout_he';

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $courses = $this->_customdata['courses'] ?? [];
        $mform->addElement('select', 'courseofstudyid', get_string('uos_courseofstudy', $component), $courses);
        $mform->addRule('courseofstudyid', null, 'required', null, 'client');

        $mform->addElement('text', 'code', get_string('uos_code', $component), ['size' => 30]);
        $mform->setType('code', PARAM_TEXT);
        $mform->addRule('code', null, 'required', null, 'client');

        $mform->addElement('text', 'name', get_string('uos_name', $component), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Raw-trimmed so validation can reject a non-numeric load precisely.
        $mform->addElement('text', 'eftsl', get_string('uos_eftsl', $component), ['size' => 10]);
        $mform->setType('eftsl', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('eftsl', 'uos_eftsl', $component);
        $mform->setDefault('eftsl', '0');

        $mform->addElement('text', 'foecode', get_string('uos_foecode', $component), ['size' => 12]);
        $mform->setType('foecode', PARAM_TEXT);
        $mform->addHelpButton('foecode', 'uos_foecode', $component);

        $modes = [];
        foreach (unit_of_study_manager::modes() as $mode) {
            $modes[$mode] = get_string('uos_mode_' . $mode, $component);
        }
        $mform->addElement('select', 'deliverymode', get_string('uos_mode', $component), $modes);
        $mform->setDefault('deliverymode', unit_of_study_manager::MODE_INTERNAL);

        $mform->addElement(
            'advcheckbox',
            'enabled',
            get_string('uos_enabled', $component),
            get_string('uos_enabled_label', $component)
        );
        $mform->setDefault('enabled', 1);

        $this->add_action_buttons();
    }

    /**
     * Validates the EFTSL load and the code's uniqueness within its course.
     *
     * @param  array $data  Submitted values.
     * @param  array $files Submitted files.
     * @return array        Validation errors keyed by field name.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $eftsl = trim((string) ($data['eftsl'] ?? ''));
        if ($eftsl === '' || !is_numeric($eftsl) || (float) $eftsl < 0) {
            $errors['eftsl'] = get_string('uos_eftsl_invalid', 'local_educheckout_he');
        }

        $code = trim((string) ($data['code'] ?? ''));
        $courseofstudyid = (int) ($data['courseofstudyid'] ?? 0);
        if ($code !== '' && $courseofstudyid
                && unit_of_study_manager::code_exists($courseofstudyid, $code, (int) ($data['id'] ?? 0))) {
            $errors['code'] = get_string('uos_code_taken', 'local_educheckout_he');
        }

        return $errors;
    }
}
