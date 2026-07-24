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
 * Form for editing a learner's higher education elements.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_educheckout_he\student_manager;

/**
 * Higher-education-elements form: pick a learner, then record their TCSI
 * student elements (citizenship, USI, CHESSN, disability, prior education).
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */
class student_form extends \moodleform {
    /**
     * Defines the form elements.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $component = 'local_educheckout_he';

        // Standard Moodle AJAX user picker, showing the chosen learner's name
        // when the form is redisplayed after a validation error.
        $mform->addElement('autocomplete', 'userid', get_string('student_learner', $component), [], [
            'ajax'              => 'core_user/form_user_selector',
            'multiple'          => false,
            'valuehtmlcallback' => function ($userid) {
                if (empty($userid)) {
                    return false;
                }
                $user = \core_user::get_user($userid);
                return $user ? fullname($user) : false;
            },
        ]);
        $mform->addRule('userid', null, 'required', null, 'client');

        $mform->addElement(
            'select',
            'citizenship',
            get_string('student_citizenship', $component),
            self::options('student_citizenship_', student_manager::citizenships(), $component)
        );
        $mform->setDefault('citizenship', student_manager::NOT_STATED);

        $mform->addElement('text', 'usi', get_string('student_usi', $component), ['size' => 16]);
        $mform->setType('usi', PARAM_TEXT);
        $mform->addHelpButton('usi', 'student_usi', $component);

        $mform->addElement('text', 'chessn', get_string('student_chessn', $component), ['size' => 16]);
        $mform->setType('chessn', PARAM_TEXT);
        $mform->addHelpButton('chessn', 'student_chessn', $component);

        $mform->addElement(
            'select',
            'disability',
            get_string('student_disability', $component),
            self::options('student_disability_', student_manager::disabilities(), $component)
        );
        $mform->setDefault('disability', student_manager::NOT_STATED);
        $mform->addHelpButton('disability', 'student_disability', $component);

        $mform->addElement(
            'select',
            'prioreducation',
            get_string('student_prioreducation', $component),
            self::options('student_prioreducation_', student_manager::prioreducations(), $component)
        );
        $mform->setDefault('prioreducation', student_manager::NOT_STATED);

        $this->add_action_buttons(true, get_string('student_save', $component));
    }

    /**
     * Builds an id → localised-label map for a coded select.
     *
     * @param  string   $prefix    The lang-string key prefix for the codes.
     * @param  string[] $codes     The code set.
     * @param  string   $component The plugin component for the strings.
     * @return array               Map of code → localised label.
     */
    private static function options(string $prefix, array $codes, string $component): array {
        $options = [];
        foreach ($codes as $code) {
            $options[$code] = get_string($prefix . $code, $component);
        }

        return $options;
    }

    /**
     * Validates the USI and CHESSN format when either is supplied.
     *
     * @param  array $data  Submitted values.
     * @param  array $files Submitted files.
     * @return array        Validation errors keyed by field name.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Both are 10-character alphanumeric identifiers when present.
        $usi = strtoupper(trim((string) ($data['usi'] ?? '')));
        if ($usi !== '' && (strlen($usi) !== 10 || !ctype_alnum($usi))) {
            $errors['usi'] = get_string('student_usi_invalid', 'local_educheckout_he');
        }

        $chessn = strtoupper(trim((string) ($data['chessn'] ?? '')));
        if ($chessn !== '' && (strlen($chessn) !== 10 || !ctype_alnum($chessn))) {
            $errors['chessn'] = get_string('student_chessn_invalid', 'local_educheckout_he');
        }

        return $errors;
    }
}
