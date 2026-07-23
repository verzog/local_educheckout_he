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
 * Pathway provider for the EduCheckout Platform higher education pathway.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */

namespace local_educheckout_he;

/**
 * Higher education pathway provider.
 *
 * Implements the core → pathway contract (\local_educheckout_core\base_provider)
 * for Australian higher education. Core's pathway_registry discovers this class
 * by autoload — no core change is needed to activate the pathway.
 *
 * Foundation scope: this pathway is a deliberate no-op until its unit-of-study
 * data model lands. It claims no enrolments (owns_enrolment() returns false),
 * so the nightly tier tally counts zero HE enrolments and no course is tagged
 * pathway = 'he'. The statutory-reporting surfaces (TCSI / HEIMS elements,
 * HESA/HELP loan reporting, TEQSA quality data) are built on top of that model
 * in later lanes — see docs/ROADMAP.md.
 *
 * @package    local_educheckout_he
 * @copyright  2025 Vernon Spain (https://educheckout.com)
 * @license    Proprietary — see LICENSE.txt in the plugin root for full terms
 */
class pathway_provider extends \local_educheckout_core\base_provider
{
    /** Machine-readable pathway identifier — the 'pathway' tag in core tables. */
    const PATHWAY = 'he';

    /**
     * Returns the machine-readable pathway identifier.
     *
     * @return string Lowercase alphanumeric identifier, no spaces.
     */
    public static function get_name(): string {
        return self::PATHWAY;
    }

    /**
     * Returns the Frankenstyle component name of this pathway plugin.
     *
     * @return string Frankenstyle plugin name.
     */
    public static function get_component(): string {
        return 'local_educheckout_he';
    }

    /**
     * Returns the human-readable label for this pathway.
     *
     * @return string Human-readable label, AU/UK English.
     */
    public static function get_label(): string {
        return get_string('pathway_label', 'local_educheckout_he');
    }

    /**
     * Returns a short description of what this pathway provides.
     *
     * @return string One or two sentences, plain text, AU/UK English.
     */
    public static function get_description(): string {
        return get_string('pathway_description', 'local_educheckout_he');
    }

    /**
     * Returns the number of active enrolments owned by this pathway.
     *
     * Counts only rows in core's enrolment envelope tagged with this pathway
     * and still active (status = 0), so the nightly tier tally never
     * double-counts another pathway's enrolments. A single indexed COUNT — no
     * recordset loop — as the base contract requires.
     *
     * @return int Count of active HE enrolments.
     */
    public static function count_active_enrolments(): int {
        global $DB;

        return $DB->count_records('local_educheckout_core_enrolments', [
            'pathway' => self::PATHWAY,
            'status'  => 0,
        ]);
    }

    /**
     * Returns true if this pathway claims ownership of a given enrolment.
     *
     * The contract is: an enrolment is HE-owned when its course carries a unit
     * of study record in the HE pathway tables. That data model does not exist
     * yet (foundation scope), so nothing is claimed and the method returns
     * false — the deliberate no-op that keeps a fresh HE install from altering
     * core's enrolment tagging. The real single-indexed lookup arrives with the
     * unit-of-study lane (docs/ROADMAP.md).
     *
     * @param  int  $userid   Moodle user ID being enrolled.
     * @param  int  $courseid Moodle course ID being enrolled in.
     * @return bool           True if this pathway claims ownership.
     */
    public static function owns_enrolment(int $userid, int $courseid): bool {
        unset($userid, $courseid); // No unit-of-study model yet; nothing to claim.

        return false;
    }

    /**
     * Registers this pathway's admin sub-category and pages under the
     * 'educheckout_core' parent category.
     *
     * Called from this plugin's own settings.php during page load. Foundation
     * scope registers the pathway category and a single settings page carrying
     * the kill-switch; the operational pages (unit-of-study CRUD, TCSI/HEIMS
     * exports) are added here as later lanes land.
     *
     * @param \admin_root $admin The Moodle admin root object ($ADMIN).
     * @return void
     */
    public static function register_admin_pages(\admin_root $admin): void {
        // Pathway sub-category, nested under the platform's top-level category.
        $admin->add('educheckout_core', new \admin_category(
            'educheckout_he',
            new \lang_string('admincat_he', 'local_educheckout_he')
        ));

        // Settings page, gated by the pathway's manage capability.
        $settings = new \admin_settingpage(
            'local_educheckout_he_settings',
            new \lang_string('settings_he', 'local_educheckout_he'),
            'local/educheckout_he:manage'
        );

        // Kill-switch for the whole pathway (CLAUDE.md §8). Off by default:
        // until it is ticked the pathway stays inert regardless of licence.
        $settings->add(
            new \admin_setting_configcheckbox(
                'local_educheckout_he/he_enabled',
                new \lang_string('setting_he_enabled', 'local_educheckout_he'),
                new \lang_string('setting_he_enabled_desc', 'local_educheckout_he'),
                0
            )
        );

        $admin->add('educheckout_he', $settings);
    }

    /**
     * Returns summary data for display on the EduCheckout Platform dashboard.
     *
     * Foundation scope reports only the active HE enrolment count. Richer
     * metrics (unit-of-study coverage, reporting-period status) are added as
     * the pathway's data model grows.
     *
     * @return array Dashboard summary data for this pathway.
     */
    public static function get_dashboard_summary(): array {
        return [
            'enrolment_count' => self::count_active_enrolments(),
        ];
    }
}
