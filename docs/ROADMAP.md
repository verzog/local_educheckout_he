# EduCheckout Platform — Higher education pathway roadmap

Internal planning notes for `local_educheckout_he`. A living document, not a
commitment: it captures the larger pieces this pathway will build so the next
contributor has a map. Each lane lists the anchors it should build on — in
platform core, in this plugin, or in the Australian higher-education reporting
standards the pathway exists to satisfy.

## Scope

This pathway owns **Australian higher-education structure and statutory
reporting**, the way `local_educheckout_vet` owns AVETMISS for VET. Platform
core is deliberately pathway-agnostic and carries no single pathway's reporting
obligations, so everything below lives here, not in core:

- **TCSI / HEIMS** — the Tertiary Collection of Student Information (the HEIMS
  successor): student, course-of-study, unit-of-study, load/liability and
  completions elements, submitted through the TCSI packet model.
- **HESA / HELP** — Higher Education Support Act obligations: Commonwealth
  support, HECS-HELP / FEE-HELP liability, EFTSL-based load reporting.
- **TEQSA** — quality and standards data returns.

Nothing here duplicates what Moodle core or platform core already provides
(gradebook, enrolment methods, the payment ledger, certificates, the enrolment
envelope, licensing/tiers). The pathway *reads* that shared state and adds the
HE-specific structure and reporting on top.

## Status

In place today (**foundation — shipped**):

- **Pathway provider** — `pathway_provider extends
  \local_educheckout_core\base_provider`, discovered by core's
  `pathway_registry` on autoload. Implements the full contract; a deliberate
  no-op — `owns_enrolment()` claims nothing and `count_active_enrolments()`
  reports zero — until the unit-of-study model (§1) lands.
- **Admin** — a "Higher education" sub-category under the platform's admin
  category, with a settings page carrying the `he_enabled` master kill-switch
  (off by default) and a `local/educheckout_he:manage` capability.
- **CI / packaging** — the platform CI matrix (PHP 8.2/8.3/8.4 × pgsql/mariadb/
  mysqli × Moodle 5.0/5.1/5.2), the proprietary-header `.phpcs.xml` ruleset, the
  proprietary licence and file headers, and the `local_educheckout_core`
  dependency pinned in `version.php`.

Landed since (**§1 PR 1 — structural catalogue, shipped**):

- **Data model** — `local_educheckout_he_coursesofstudy` (award courses: code,
  name, CRICOS code, level of course) and `local_educheckout_he_unitsofstudy`
  (units within a course: code, name, EFTSL load, field-of-education code,
  delivery mode), with managers, admin CRUD, and the pathway kill-switch gating
  every surface.
- **Privacy** — now a full provider: both tables are institutional
  configuration, so only the authoring reference (`usermodified`) is personal
  data — exported for, and anonymised on erasure of, that admin.
- **Tests** — phpunit coverage of both managers (normalisation, coercion,
  scoping, guards, kill-switch).

The lanes below are the substantial build-outs, listed roughly in dependency
order. §1 gates most of the rest — the reporting lanes have nothing to report
until the structure exists.

## 1. Unit-of-study & course-of-study model

The structural foundation, and the thing that makes the pathway claim
enrolments. TCSI is organised around a **course of study** (an award course a
student is admitted to) composed of **units of study** (the reportable teaching
units), each mapped to one or more Moodle courses.

- **PR 1 (shipped)** — the structural catalogue. `coursesofstudy` and
  `unitsofstudy` tables (the latter a child of the former), their managers,
  admin CRUD (list / add / edit / delete, with a course-of-study filter on the
  units list and a delete guard on a course that still has units), the pathway
  kill-switch gating every surface, a full Privacy provider (both tables are
  configuration; only `usermodified` is personal data, anonymised on erasure),
  and phpunit tests. `owns_enrolment()` stays false: the catalogue exists but
  nothing links a unit to a Moodle course yet.
- **PR 2 (next)** — the unit→Moodle-course mapping. A `unitcourses` join table
  (unique on `(unitofstudyid, courseid)`, indexed on `courseid`), the mapping
  UI, and a real `owns_enrolment()` — true when the enrolled course maps to an
  enabled unit of study, a single indexed lookup gated by the kill-switch. This
  is what turns a Moodle enrolment into a reportable HE enrolment and starts the
  `pathway = 'he'` tagging in core's envelope.

- **Anchors:** `pathway_provider::owns_enrolment()` (PR 2 gives it a real
  single-indexed lookup); core's `local_educheckout_core_enrolments` envelope
  (the observer tags a claimed enrolment `pathway = 'he'`); the resource/manager
  + admin-CRUD + kill-switch conventions from every core lane.
- **Notes:** units of study and courses of study are institutional
  configuration, not learner data — only the authoring reference is per-user in
  the Privacy provider. The mapping is what turns a Moodle enrolment into a
  reportable HE enrolment, so it must be a cheap indexed lookup (§ base contract:
  no full scans in `owns_enrolment`).

## 2. Student & enrolment HE elements

The learner-level data TCSI requires that Moodle does not hold: citizenship /
residency status, USI and CHESSN, disability, prior educational attainment,
term/permanent address, and the course-admission and unit-enrolment records that
tie a student to §1's structure.

- **Anchors:** §1's course/unit tables; core's user-identity layer
  (`local_educheckout_core_users`) as the extension pattern; the admin-page +
  capability + kill-switch conventions.
- **Privacy:** this is the lane that turns the null provider into a full one —
  student HE elements are personal (and some, e.g. disability, are
  special-category, so mirror core §16's sensitive-data handling: capability
  gated, exported and erased by `userid`, authoring actor anonymised).
- **Notes:** validate every element against its TCSI code set at the boundary
  with the correct `PARAM_*` type; store codes, render labels from lang strings.

## 3. Load & liability (EFTSL, HELP, Commonwealth support)

The financial/load half of a TCSI submission: EFTSL load per unit enrolment,
student status and liability (HECS-HELP / FEE-HELP / Commonwealth supported
place), and the amounts that feed HELP reporting.

- **Anchors:** §2's unit enrolments; core's payment ledger and
  `payment\manager::calculate_gst()` / fee-charge model (HE liability is
  adjacent to, but distinct from, the fees lane — it is a statutory amount, not
  an invoice); the calendar-arithmetic date rules (§5.6 of core CLAUDE.md) for
  census-date logic — **never `+ DAYSECS`** across a boundary.
- **Notes:** census dates and reporting periods are date-sensitive under
  `Australia/Sydney`; derive them with calendar arithmetic.

## 4. TCSI / HEIMS packet export & validation

Turn §1–§3 into submittable TCSI packets: assemble the student, course,
unit-enrolment, load/liability and completions elements for a reporting period,
validate them against TCSI business rules *before* export, and produce the
submission artefact.

- **Anchors:** core's `builtin_engine` PDF/stored-file + `dataformat` CSV export
  patterns (for human-readable validation reports); the bulk-action rules
  (CLAUDE.md §8) — a packet build is a bulk operation, so **queue adhoc tasks,
  throttle per cron tick, confirm, and ship a kill-switch**; §8.1 (filter
  eligibility in SQL before any cap).
- **Notes:** validation is the high-value piece — surface rule violations per
  element with a clear fix path, never a silent drop.

## 5. TEQSA & HESA returns

TEQSA quality-and-standards data and the HESA statistical returns that reuse the
same underlying elements as §4 but aggregate them differently.

- **Anchors:** §4's assembled elements and validation; core's analytics
  aggregation pattern (read-only, aggregates-only, no new personal data) for the
  summary figures.

## 6. Reporting-period & correction workflow

Manage reporting periods (open / census / submitted / corrected), and the
resubmission/correction path TCSI requires when a previously reported element
changes.

- **Anchors:** §4's packet model; the admissions state-machine pattern from core
  §11 (guarded status transitions, terminal-state protection) as the model for a
  period/correction lifecycle.

## 7. Dashboard integration

Surface HE reporting status through `get_dashboard_summary()` — reportable
enrolment count, current reporting period, outstanding validation errors — for
the platform dashboard block.

- **Anchors:** `pathway_provider::get_dashboard_summary()` (foundation returns
  only the enrolment count today); core's dashboard summary contract.

## Cross-cutting reminders

- **Statutory data is sensitive.** Student HE elements are personal data and
  some are special-category — capability-gate every surface, kill-switch off by
  default, and cover it fully in the Privacy API (core CLAUDE.md §4, §16).
- **Bulk/reporting actions** (packet builds, validation sweeps, resubmissions)
  follow CLAUDE.md §8: queue adhoc tasks, throttle, confirm, kill-switch; and
  §8.1 — filter eligibility in SQL before any cap.
- **AU dates & load maths** — `userdate()` for display, calendar arithmetic
  (never `+ DAYSECS`) for census dates and reporting boundaries under
  `Australia/Sydney` (core CLAUDE.md §3, §5.6).
- **Every cached-asset change** (templates, AMD, lang, DB, capabilities) needs a
  `version.php` bump (core CLAUDE.md §7).
