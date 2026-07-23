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
 * Admin settings and navigation for the EduCheckout Platform higher education pathway.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

defined('MOODLE_INTERNAL') || die();

// The pathway nests its own sub-category and pages under core's top-level
// 'educheckout_core' category (owned by local_educheckout_core/settings.php).
// Registration lives in the provider so the same entry point can be reused by
// core code, per the base_provider contract. The only page today is a settings
// page, so registration sits behind the site-config guard.
if ($hassiteconfig) {
    \local_educheckout_he\pathway_provider::register_admin_pages($ADMIN);
}
