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
 * Event observers for the EduCheckout Platform higher education pathway.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he;

/**
 * Handles Moodle events the higher education pathway needs to react to.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */
class observer {
    /**
     * Removes a deleted course's unit mappings.
     *
     * XMLDB foreign keys are advisory in Moodle, so a deleted course leaves its
     * unit-course mappings behind. Clean them up here so no orphan rows linger.
     * Runs regardless of the pathway kill-switch — stale rows should always be
     * cleared.
     *
     * @param  \core\event\course_deleted $event The course-deleted event.
     * @return void
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        unit_course_manager::delete_for_course((int) $event->objectid);
    }
}
