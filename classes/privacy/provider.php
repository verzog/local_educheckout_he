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
 * Privacy API provider for the EduCheckout Platform higher education pathway.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he\privacy;

/**
 * Privacy API provider for the higher education pathway.
 *
 * Foundation scope stores no personal data of its own: the pathway holds no
 * tables and no file areas yet, and its enrolment counting reads core's
 * already-covered enrolment envelope. It therefore declares the null provider.
 *
 * When the unit-of-study and statutory-reporting lanes add tables that carry
 * learner data (TCSI/HEIMS student and enrolment elements are personal data),
 * this class becomes a full metadata + request provider — see docs/ROADMAP.md.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */
class provider implements \core_privacy\local\metadata\null_provider
{
    /**
     * Returns the language string explaining why this plugin stores no data.
     *
     * @return string The name of a language string in this plugin.
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
