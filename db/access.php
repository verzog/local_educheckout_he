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
 * Capability definitions for the EduCheckout Platform higher education pathway.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

defined('MOODLE_INTERNAL') || die();

// Capability definitions for local_educheckout_he.
//
// Namespace:  local/educheckout_he:<capability>
// Context:    CONTEXT_SYSTEM for all pathway-level administration.

$capabilities = [

    // Manage the higher education pathway: its settings and (as later lanes
    // land) its unit-of-study records and statutory-reporting exports. Gates
    // the pathway's admin pages. Manager-only by default, per least privilege.
    'local/educheckout_he:manage' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // Manage learners' higher education elements (citizenship, USI, CHESSN,
    // disability, prior education). This is sensitive (special-category)
    // personal data, so it is a dedicated capability separate from :manage —
    // it can be granted without granting catalogue administration, and vice
    // versa. Manager-only by default, per least privilege.
    'local/educheckout_he:managestudents' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
