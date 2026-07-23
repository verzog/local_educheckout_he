# EduCheckout Platform — Higher Education pathway (`local_educheckout_he`)

> **Proprietary software.** Copyright © 2025 Vernon Spain.
> See [LICENSE.txt](LICENSE.txt) for full licence terms.
> Unauthorised copying, modification, or distribution is strictly prohibited.

---

## Overview

`local_educheckout_he` is the **higher education pathway** for the EduCheckout
Platform suite. It owns Australian higher-education structure and statutory
reporting — the unit-of-study model, and TCSI/HEIMS, HESA/HELP, and TEQSA
returns — the same way `local_educheckout_vet` owns AVETMISS for VET. The
platform core stays free of any single pathway's reporting obligations; this
plugin is where higher-education compliance lives.

It plugs into `local_educheckout_core` through the standard pathway contract:
core discovers it by autoload (no core change required), tags and counts the
enrolments it claims, and nests its admin pages under the platform's admin
category.

**Requires:** Moodle 5.0+ · PHP 8.2+ · `local_educheckout_core`
**Maturity:** Alpha
**Licence:** Proprietary — [educheckout.com](https://educheckout.com)

This release is the **foundation**: a valid, self-registering pathway with a
master kill-switch (off by default). It deliberately claims no enrolments and
exposes no operational pages until the unit-of-study data model lands — see
[docs/ROADMAP.md](docs/ROADMAP.md) for the planned lanes.

---

## Requirements

| Requirement | Minimum version |
|---|---|
| Moodle | 5.0 (`2025041400`) |
| PHP | 8.2 |
| `local_educheckout_core` | `2026071313` |
| Database | MySQL 8.4 / MariaDB 10.11.0 / PostgreSQL 14 |

The platform core plugin must be installed first — this pathway extends core's
`base_provider`, is discovered by core's pathway registry, and records against
core's enrolment envelope. The dependency is declared in `version.php`, so
Moodle will not install this pathway without a new-enough core.

---

## Installing via uploaded ZIP file

> ⚠️ **Packaging rule.** Moodle derives the plugin's disk location from the
> top-level folder name inside the zip. For a `local_` plugin the folder must be
> named **without** the `local_` prefix — `educheckout_he/`, not
> `local_educheckout_he/`.

```bash
# Rename the outer folder only — never the files inside it.
mv local_educheckout_he educheckout_he
zip -r educheckout_he.zip educheckout_he/
```

1. In Moodle: **Site Administration → Plugins → Install plugins**.
2. Upload `educheckout_he.zip` and follow the on-screen prompts.
3. Complete the database upgrade step when prompted.
4. Enable the pathway under **Site Administration → EduCheckout Platform →
   Higher education → Higher education settings** (off by default).

## Installing manually

Place the plugin so that it lives at `<moodleroot>/local/educheckout_he`
(or `<moodleroot>/public/local/educheckout_he` on the Moodle 5.1+ `public/`
layout), then visit **Site Administration → Notifications** to run the upgrade.

---

## Configuration

| Setting | Purpose |
|---|---|
| Enable higher education pathway | Master kill-switch. Off by default; while off the pathway stays inert. |

Licensing is owned by the platform core: the pathway becomes *active* (counted
toward the billing tier, shown as licensed on the pathway registry page) once a
`licence_he` token is present, exactly like every other pathway.

---

## Development

### The core → pathway contract

The pathway's single required class is `classes/pathway_provider.php`:

```php
namespace local_educheckout_he;

class pathway_provider extends \local_educheckout_core\base_provider {
    // get_name(), get_component(), get_label(), get_description(),
    // count_active_enrolments(), owns_enrolment(),
    // register_admin_pages(), get_dashboard_summary()
}
```

Core's `pathway_registry` already lists `'he'`, so installing this plugin is all
that is needed for discovery.

### Running CI checks locally

```bash
# Install moodle-plugin-ci
composer create-project -n --no-dev --prefer-dist moodlehq/moodle-plugin-ci ../moodle-plugin-ci ^4

# Code standards (uses the committed proprietary-header ruleset)
../moodle-plugin-ci/vendor/bin/phpcs --standard=.phpcs.xml ./
```

CI runs on GitHub Actions across PHP 8.2 / 8.3 / 8.4 × pgsql / mariadb / mysqli
× Moodle 5.0 / 5.1 / 5.2, timezone `Australia/Sydney`.

### Coding standards

- 4-space indentation; Moodle brace style; proprietary file header (§9 of core
  `CLAUDE.md`) — **not** the GPL boilerplate.
- AU/UK English in all `lang/en/` strings; dates via `userdate()`; currency AUD.

---

## Licence

EduCheckout Platform is proprietary software. See [LICENSE.txt](LICENSE.txt) for
full terms.

Copyright © 2025 Vernon Spain — [educheckout.com](https://educheckout.com)
