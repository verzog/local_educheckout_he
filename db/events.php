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
 * Event observer registration for the higher education pathway.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // When a Moodle course is deleted, drop any unit-course mappings that
    // reference it so no orphan rows are left behind.
    [
        'eventname' => '\core\event\course_deleted',
        'callback'  => '\local_educheckout_he\observer::course_deleted',
    ],
];
