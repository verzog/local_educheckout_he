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
 * Version metadata for the EduCheckout Platform higher education pathway.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component    = 'local_educheckout_he';
$plugin->version      = 2026072301;   // YYYYMMDDNN — increment NN for same-day releases.
$plugin->requires     = 2025041400;   // Moodle 5.0 minimum (released 14 April 2025).
$plugin->maturity     = MATURITY_ALPHA;
$plugin->release      = '0.1.0';

// The pathway plugin cannot function without the platform core: it extends
// core's base_provider, is discovered through core's pathway_registry, counts
// enrolments recorded in core's enrolment-envelope table, and nests its admin
// pages under core's 'educheckout_core' category. Pin to the current core
// release so an older core (missing base_provider) cannot satisfy the dependency.
$plugin->dependencies = [
    'local_educheckout_core' => 2026071313,
];
