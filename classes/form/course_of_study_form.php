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
 * Add / edit form for a higher education course of study.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_educheckout_he\course_of_study_manager;

/**
 * Captures a course of study's code, name, CRICOS code, and level.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */
class course_of_study_form extends \moodleform {
    /**
     * Defines the course of study form fields.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $component = 'local_educheckout_he';

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'code', get_string('cos_code', $component), ['size' => 30]);
        $mform->setType('code', PARAM_TEXT);
        $mform->addRule('code', null, 'required', null, 'client');

        $mform->addElement('text', 'name', get_string('cos_name', $component), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('text', 'cricoscode', get_string('cos_cricoscode', $component), ['size' => 20]);
        $mform->setType('cricoscode', PARAM_TEXT);
        $mform->addHelpButton('cricoscode', 'cos_cricoscode', $component);

        $levels = [];
        foreach (course_of_study_manager::levels() as $level) {
            $levels[$level] = get_string('cos_level_' . $level, $component);
        }
        $mform->addElement('select', 'courselevel', get_string('cos_level', $component), $levels);
        $mform->setDefault('courselevel', course_of_study_manager::LEVEL_UNDERGRADUATE);

        $mform->addElement(
            'advcheckbox',
            'enabled',
            get_string('cos_enabled', $component),
            get_string('cos_enabled_label', $component)
        );
        $mform->setDefault('enabled', 1);

        $this->add_action_buttons();
    }

    /**
     * Validates that the course of study code is unique.
     *
     * @param  array $data  Submitted values.
     * @param  array $files Submitted files.
     * @return array        Validation errors keyed by field name.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Server-side required checks: the addRule('required') calls above are
        // client-only, so a crafted POST could otherwise persist a blank record.
        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            $errors['code'] = get_string('required');
        }
        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors['name'] = get_string('required');
        }

        if ($code !== '' && course_of_study_manager::code_exists($code, (int) ($data['id'] ?? 0))) {
            $errors['code'] = get_string('cos_code_taken', 'local_educheckout_he');
        }

        return $errors;
    }
}
