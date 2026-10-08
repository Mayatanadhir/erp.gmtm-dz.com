# Changelog: ENGI-GMTM Core Kernel (Point Zero)

All notable changes, features, refactorings, and fixes for this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

> [!NOTE]
> Detailed historical changelog prior to Point Zero Core Kernel extraction is preserved in `docs/archive/legacy_changelog.md`.
> Historical Core Kernel releases from `v1.0.0` through `v1.0.35` are archived in `docs/archive/core_kernel_archive.md`.

## [1.0.92] — 2026-10-08 — Measuring Instruments Unified Modal Expansion Across All 6 Categories
### Added & Enhanced
- **Measuring Instruments Modal Architecture (`resources/views/metrology/instruments.blade.php`)**:
  - Expanded the inline New / Edit Instrument modal into a responsive, unified multi-category dialog (`max-w-4xl`, sticky header/footer, scrollable body) supporting all 6 categories:
    1. **Transmitter**: Dynamic measurement type selection (Differential, Relative, Absolute, Static), process variable, fluid type, technology, measurement and source physical grandeurs with custom spans.
    2. **Probe**: RTD Pt100 / Thermocouple architecture, temperature & electrical resistance ranges, tolerance class.
    3. **Flow Computer**: Multichannel transmitter loop wiring with dynamic add/remove channel rows and site-aware transmitter filtering.
    4. **Chromatograph**: TCD/FID detector selection, carrier gas, sampling streams count, dual architecture info.
    5. **Standard Gauge**: Nominal capacity BMV, neck sensitivity, Gcm cubical expansion, ISO 17025 cert, dates, vessel material, base reference temperature.
    6. **Prover**: Prover classification type (Unidirectional, Bidirectional, Compact SVP), ID, WT, BPV, thermal expansion Gc, elasticity modulus E, SVP expansion coefficients (Ga, Gl), material, and pulse interpolation module.
  - Fortified Alpine.js reactive state (`onTypeChange()`, `template x-if`) and initial row mappings in `openEdit()` to populate category-specific data when editing.
  - Teleported modal to `document.body` via `<template x-teleport="body">` with elevated `z-[100]` and `bg-gray-900/70` backdrop blur, guaranteeing the dialog always displays at the topmost layer above all page headers, navigation bars, and sticky elements.
- **Repository Optimization & Memory Isolation (`InstrumentRepository`, `instruments.blade.php`)**:
  - Added eager loading of `linkedTransmitters` in `paginateWithFilter` to prevent N+1 queries when mapping flow computer channels.
  - Safe payload serialization for available transmitters in `instruments.blade.php` to prevent search filter text collision in automated test suites.
- **Legacy Cleanup & Dead Code Removal (`resources/views/metrology/instruments/edit.blade.php`, `create.blade.php`, `InstrumentController`)**:
  - Purged redundant legacy monolithic file `resources/views/metrology/instruments/edit.blade.php` (75 KB) in favor of decoupled category workspaces under `edit/`, updating `InstrumentController::edit()` fallback to clean 404 response.
  - Purged redundant category selection hub `resources/views/metrology/instruments/create.blade.php` (20 KB) now superseded by the unified index modal dialog, redirecting `InstrumentController::create()` cleanly to `metrology.instruments`.

## [1.0.91] — 2026-10-07 — Analytics Module Harmonization, Layout Standardization & Database-Agnostic Queries
### Fixed & Standardized
- **Analytics Layout & Component Architecture (`resources/views/analytics/`)**:
  - Replaced non-standard `<x-analytics-layout>` and custom unresolvable helper files with direct `<x-app-layout>` inheritance and `<x-analytics-tabs>` sidebar integration matching the ERP standard design system.
  - Migrated `resources/views/analytics/statistics.blade.php` from legacy raw HTML `<table>` elements to the unified `<x-table>` suite (`<x-table.th>`, `<x-table.tr>`, `<x-table.td>`, `<x-table.empty>`, `<x-table.action-view>`).
  - Purged unresolvable, untracked helper components (`analytics-layout.blade.php`, `ui-icon.blade.php`, `stat-card.blade.php`, `money.blade.php`, `section-heading.blade.php`).
- **Database-Agnostic Year Querying (`AnalyticsController@statistics`)**:
  - Replaced MySQL-specific `YEAR(start_date)` with `DB::getDriverName() === 'sqlite' ? "strftime('%Y', start_date)" : "YEAR(start_date)"`, ensuring 100% test compatibility across SQLite and MySQL.
- **Expenses Redirection (`AnalyticsController@expenses`)**:
  - Updated legacy `analytics.expenses` method to redirect cleanly to `financial.expenses`, preventing `ViewNotFoundException`.
- **Trilingual Dictionary Parity (`lang/en.json`, `lang/ar.json`, `lang/fr.json`)**:
  - Added 40+ standardized English master translation keys for analytical metrics with exact 1-to-1 parity (2,418 keys across all 3 locales).

## [1.0.90] — 2026-10-06 — Category-Isolated Measuring Instruments Deletion & Modal Architecture
### Added & Stabilized
- **Category-Isolated Deletion Engine (`InstrumentService`, `InstrumentController`, `Instrument`)**:
  - Isolated deletion logic into dedicated handlers per instrument category (`Transmitter`, `FlowComputer`, `Probe`, `Chromatograph`, `StandardGauge`, `Prover`).
  - Added calibration report and test verification blockers preventing deletion when historical test records exist.
  - Automatically cleans category-specific specification records and pivot relationships prior to soft delete when no test reports exist.
- **Delete Warning Modal Stabilization (`metrology/instruments.blade.php`, `equipment.blade.php`)**:
  - Eliminated base64/atob/JSON.parse dataset serialization on delete action buttons in favor of standard escaped Alpine modal invocations.
  - Fixed interleaved `</form>` and `</div>` tag closures in create and edit modal dialogs ensuring proper DOM tree parsing for `<x-crud-modal.delete>`.

## [1.0.89] — 2026-10-06 — Navigation & Delete Actions Stabilization
### Fixed & Standardized
- **Action Delete & Confirmation Architecture (`<x-table.action-delete>`, Show Views)**:
  - Fixed syntax error in `<x-table.action-delete>` by moving conditional click logic from inner tag directives into clean attribute merging.
  - Stabilized delete, approve, and lifecycle confirmation actions across `metrology/instruments`, `metrology/equipment`, `metrology/certificates`, `operations/contracts`, `operations/missions`, `operations/attachments`, and `financial/expenses`.
  - Replaced `@js` inside HTML tag attributes with `json_encode()` to prevent Livewire regex compilation buffer exhaustion.
- **Back to List Navigation**:
  - Verified and aligned "Back to List" navigation buttons and back arrows across all detail pages to ensure reliable return routing.

## [1.0.88] — 2026-10-06 — Measuring Instruments Creation & Edit Modularization Architecture
### Added & Modularized
- **Category Selection Hub & Edit Dispatcher (`resources/views/metrology/instruments/create.blade.php`, `edit.blade.php`)**:
  - Replaced legacy 767-line create view with an interactive 6-card category hub directing engineers to dedicated registration workspaces.
  - Replaced legacy 929-line monolithic edit form with a clean category view dispatcher.
- **Dedicated Independent Category Views (`create/` and `edit/`)**:
  - Created 6 standalone creation views in `resources/views/metrology/instruments/create/` and 6 standalone editing views in `resources/views/metrology/instruments/edit/`:
    - `transmitter.blade.php`: Pressure, flow, and level smart transmitters with measurement & source span configurations.
    - `flow-computer.blade.php`: Fiscal flow computing units with multichannel transmitter loop wiring.
    - `probe.blade.php`: RTD Pt100 / Thermocouple probes with Callendar-Van Dusen curve coefficients.
    - `chromatograph.blade.php`: Gas Chromatographs with detector configurations and carrier gas parameters.
    - `standard-gauge.blade.php`: Volumetric test measure standards with base volume and neck scale sensitivity.
    - `prover.blade.php`: Unidirectional/bidirectional pipe provers and compact SVPs per API MPMS Ch. 4.
    - `partials/_identity.blade.php`: Shared standard identification card (Tag, Serial Number, Site, Status, Photo).
- **Dedicated Routes & Controller Delegation**:
  - Registered route `/metrology/instruments/create/{type}` (`metrology.instruments.create.type`) handled by `InstrumentController::createType()`.
  - Updated `InstrumentController::edit()` to dynamically serve category-specific edit templates.
- **Trilingual Parity Synchronization**:
  - Audited all 23 views in `resources/views/metrology/instruments/` (239 total unique keys).
  - Added and synchronized all 83 category workspace master keys across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` (2,366 keys in 100% parity, 0 missing, 0 non-English keys).
- **Test Suite Coverage**:
  - Enhanced `MeasuringInstrumentFeatureTest` covering category hub and dedicated creation endpoints.

## [1.0.87] — 2026-10-06 — Measuring Instruments Complete Localization & Trilingual Parity Audit
### Fixed & Synchronized
- **Instruments Module Localization Parity (`resources/views/metrology/instruments/`)**:
  - Audited all 10 views and partials across the measuring instruments suite (`create`, `edit`, `show`, `_modal-edit`, `_card-audit`, `_header-actions`, `_identity-card`, `_metrological-specs`, `_specialized-specs`, `_technical-specs`).
  - Discovered and resolved 105 missing keys in `lang/en.json`, including 69 metrology keys previously added to AR/FR but omitted from the English master file, and 56 completely missing domain keys.
  - Replaced hardcoded technical strings and inline labels in Chromatograph Dual Architecture and loop wiring cards with localized keys.
  - Aligned delete confirmation in `_header-actions.blade.php` with standardized system confirmation key.
  - Synchronized exact 1:1 key parity across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` (2,283 master keys, 0 missing, 0 non-English keys).
  - Validated with 100% passing automated tests (`LocalizationTest`, `MeasuringInstrumentFeatureTest`).

## [1.0.86] — 2026-10-06 — Measuring Instruments Enterprise Modernization & Full Data Migration
### Added & Modernized
- **Industrial Instruments Enterprise Full-Page Create & Edit Views (`resources/views/metrology/instruments/`)**:
  - Engineered standalone, full-precision views `edit.blade.php` and `create.blade.php` mirroring all legacy capabilities from `app.gmtm-dz.com` while strictly implementing the SARL GMTM enterprise UI suite (`<x-app-layout>`, `<x-card>`, `<x-input-label>`, `<x-text-input>`, `<x-select>`, `<x-primary-button>`, `<x-secondary-button>`).
  - Implemented dynamic Alpine.js reactivity (`x-data="instrumentForm(...)"`) with dynamic sub-sections:
    - Dedicated process variable & measurement technology selectors with automated defaults for Gas Chromatographs, Volumetric Standard Gauges, and Meter Provers.
    - Dynamic loop channel wiring management for Flow Computers (`flow_computer_transmitter`), supporting alphanumeric channel identifiers (e.g. `CH1`, `AI-01`) filtered dynamically by deployed site.
    - Specialized Gas Chromatograph architecture with multi-stream detector cards (TCD, FID, FPD, Dual).
    - Standard Volumetric Gauge (Jauge étalon) technical parameters (Base Volume $BMV$, cubical thermal expansion $G_{cm}$, neck scale resolution, vessel material, ISO 17025 certificate tracking).
    - Dynamic Meter Prover (Pipe / Compact SVP) physical mechanics with full parameter precision ($G_c$, $E$, $G_a$, $G_l$, inner diameter, wall thickness, pulse interpolation).
    - Dual physical quantities matrices with min/max calibration ranges and precision accuracy types for both Measurement (Sensing) and Source (Generation) grandeurs.
- **FormRequests & Domain Service Modernization**:
  - Updated `StoreInstrumentRequest` and `UpdateInstrumentRequest` with validation rules for alphanumeric channels, prover expansion coefficients, and dual-matrix physical quantities.
  - Enhanced `InstrumentService` (`syncTransmitters`, `syncProverSpecification`) to store alphanumeric channel strings without truncation and handle area/linear thermal expansion coefficients.
  - Introduced `App\Constants\MetrologyConstants` defining standardized coefficients for cubical, linear, and area thermal expansions ($G_c$, $G_{cm}$, $G_a$, $G_l$) and elasticity modulus ($E$).
- **Database Schema & Data Migration (`metrology:migrate-legacy-instruments`)**:
  - Created migration `2026_10_06_121000_update_instruments_and_specifications_precision.php`:
    - Changed `flow_computer_transmitter.channel_number` from `unsignedInteger` to `string(50)` to prevent channel stripping (`CH1` -> `0`).
    - Added `area_expansion_coef` ($G_a$) and `linear_expansion_coef` ($G_l$) to `prover_specifications`.
  - Created and executed console migration command `MigrateLegacyInstrumentsData` (`php artisan metrology:migrate-legacy-instruments`):
    - Migrated and synchronized 83 measuring instruments from legacy `gmtm_app` into `gmtmdz_erp`.
    - Resolved duplicate legacy serial numbers via intelligent in-memory mapping (`$legacyToErpIdMap`).
    - Migrated 103 Flow Computer wiring channel links with exact alphanumeric channel codes (`ch1`, `ch2`, etc.).
    - Migrated 64 instrument physical specifications and calibration ranges.
    - Migrated Standard Gauge and Prover mechanical specifications.
    - Transferred 9 instrument nameplate/field photos into optimized storage with SHA-256 CAS deduplication.
- **Trilingual Localization Parity**:
  - Synchronized all metrology instrument labels across English, Arabic (`lang/ar.json`), and French (`lang/fr.json`).

## [1.0.85] — 2026-10-06 — Metrology Equipment Details Edit Modal Bugfix & View Modularization
### Fixed & Refactored
- **Equipment Show View Modularization & Edit Modal Architecture (`resources/views/metrology/equipment/`)**:
  - Decomposed monolithic 910-line `show.blade.php` into clean specialized partials:
    - `partials/_header-actions.blade.php`: Header actions and status badge.
    - `partials/_profile-card.blade.php`: Profile details, rapid actions, and responsive tab navigation bar.
    - `partials/_tab-overview.blade.php`: Core metadata, serials, packaging, description, and notes.
    - `partials/_tab-specifications.blade.php`: Physical capability specifications (Measurement & Source).
    - `partials/_tab-certificates.blade.php`: Calibration certificates history table and lifecycle actions.
    - `partials/_tab-audit.blade.php`: Forensic activity log and audit trail table.
    - `partials/_modal-edit.blade.php`: Complete edit modal form with capabilities and file attachments.
  - Reduced `show.blade.php` skeleton from 910 lines to ~92 lines, keeping all Alpine.js state intact (`showEditModal`, `activeTab`).
  - Resolved Alpine.js component scope isolation between `<header x-data>` and the main `<div x-data>` via `$dispatch('open-edit-equipment-modal')`.
  - Replaced unsafe `addslashes()` in Blade Alpine.js initializers with `{{ json_encode(...) }}`.
  - Resolved missing `$measurementGrandeurs` and `$sourceGrandeurs` in `EquipmentController::show()`.
  - Added feature tests verifying view data, modal trigger, and redirect-preserving updates in `EquipmentFeatureTest`.
- **Measuring Instruments Show View Modularization & Edit Modal Standardization (`resources/views/metrology/instruments/`)**:
  - Modularized 616-line `show.blade.php` into specialized partials under `resources/views/metrology/instruments/partials/`:
    - `_header-actions.blade.php`: Header action buttons (Edit, Delete with RBAC guard, Back to List).
    - `_identity-card.blade.php`: Identity & Media card (photo preview, tag, serial number, site, timestamps, rapid edit).
    - `_technical-specs.blade.php`: General technical attributes (type, process variable, fluid type, technology).
    - `_metrological-specs.blade.php`: Metrological ranges and accuracy specifications table.
    - `_specialized-specs.blade.php`: Flow computer channels, standard gauge blueprint, and prover engineering specifications.
    - `_card-audit.blade.php`: Forensic activity log table utilizing `<x-table>` suite and `$instrument->activities`.
    - `_modal-edit.blade.php`: Complete edit instrument modal with dynamic type-dependent fields, image uploads, and physical quantities.
  - Reduced main `show.blade.php` from 616 lines to ~85 lines while standardizing Alpine.js event dispatching (`@click="$dispatch('open-edit-instrument-modal')"` with `@open-edit-instrument-modal.window="showEditModal = true"`).

## [1.0.84] — 2026-10-05 — Global UI Action Buttons Component Standardization & Ecosystem Modernization
### Added & Standardized
- **Core Table Action Button Suite Modernization (`resources/views/components/table/`)**:
  - Upgraded `<x-table.action>` to natively support new standardized semantic types: `pdf` (Rose/Red with PDF document SVG), `excel`/`csv` (Emerald with spreadsheet grid SVG), `print` (Slate with printer SVG), and `stats`/`chart` (Indigo with analytics bar chart SVG).
  - Created dedicated wrapper components: `<x-table.action-pdf>`, `<x-table.action-excel>`, `<x-table.action-print>`, and `<x-table.action-stats>` alongside existing `action-view`, `action-edit`, `action-delete`, `action-download`, and `action-restore`.
  - Preserved trilingual localization parity across AR, EN, and FR for all default tooltips and screen-reader accessibility labels.
- **Cross-Domain Table Refactoring & Actions Unification**:
  - **Metrology Chromatograph Reports (`resources/views/metrology/reports/report-chromatograph/index.blade.php`)**: Fully migrated legacy raw `<table>` into `<x-table>`, `<x-slot:toolbar>`, `<x-slot:header>`, `<x-table.th>`, `<x-table.tr>`, `<x-table.td>`, and `<x-table.empty>`, converting all raw icon links into `<x-table.actions>` with `<x-table.action-view>`, `<x-table.action-pdf>`, `<x-table.action-excel>`, `<x-table.action-edit>`, and `<x-table.action-delete>`.
  - **Commercial Contracts Explorer (`resources/views/operations/contracts/index.blade.php`)**: Replaced custom `inline-flex` divs and raw buttons with standardized `<x-table.actions>` featuring `<x-table.action-view>`, `<x-table.action-edit>`, `<x-table.action-stats>`, and `<x-table.action-delete>`.
  - **Operational Missions Explorer (`resources/views/operations/missions/index.blade.php`)**: Replaced raw action elements with `<x-table.actions>`, `<x-table.action-view>`, `<x-table.action-edit>`, `<x-table.action-stats>`, and `<x-table.action-delete>`.
  - **Operations Attachments Explorer (`resources/views/operations/attachments/index.blade.php`)**: Modernized actions column into `<x-table.actions>` with `<x-table.action-view>`, `<x-table.action-print>`, and `<x-table.action-edit>`.
  - **Financial Expenses Explorer (`resources/views/financial/expenses/index.blade.php`)**: Refactored action buttons into `<x-table.actions>` with `<x-table.action-view>`, `<x-table.action-edit>`, and `<x-table.action-delete>`.
  - **Metrology Instruments & Reports Sub-Tables (`report-instruments/index.blade.php`, report partials)**: Standardized report-level and partial action links to use `<x-table.actions>` and `<x-table.action-pdf>`.

## [1.0.83] — 2026-10-05 — Prover & Standard Gauges Hub Architecture & UI Modernization
### Added & Standardized
- **Standard Prover & Volumetric Gauges Architecture (`resources/views/metrology/reports/report-Prover/index.blade.php`)**:
  - Rebuilt the Prover & Standard Gauges Hub from scratch, eliminating mismatched instrument-loop legacy code and resolving undefined `$missions` / `$reportGroups` variables.
  - Standardized under SARL GMTM UI Design System using `<x-table>`, `<x-table.th>`, `<x-table.tr>`, `<x-table.td>`, `<x-table.empty>`, `<x-table.actions>`, `<x-table.action-view>`, `<x-global-filter>`, `<x-badge>`, and `<x-alert>`.
  - Added 4-card KPI statistics grid: Pipe & Compact Provers, Reference Standard Gauges, Nominal Base Volume ($V_0$), and Repeatability Target ($\le 0.05\%$ per API MPMS Ch. 4 / ISO 7278).
- **Domain Models & Controller Data Synchronization (`App\Models\ProverVerification`, `ReportController::proverIndex`)**:
  - Created `App\Models\ProverVerification` Eloquent model and linked `Instrument::proverVerifications` and `Instrument::latestProverVerification` relations.
  - Enhanced `ReportController::proverIndex` with multi-dimensional filtering (`search`, `type`, `site_id`, `status`), query persistence (`->withQueryString()`), and eager loaded specifications and latest calibrations.
- **Trilingual Localization Parity (`lang/en.json`, `lang/ar.json`, `lang/fr.json`)**:
  - Enforced 100% English master keys and synchronized 27 new translation keys across English, Arabic, and French dictionaries with zero parity diffs.
- **Automated Feature Testing (`tests/Feature/Metrology/ReportProverIndexTest.php`)**:
  - Added feature tests covering authentication, rendering, filter states (`search`, `type`, `site_id`), and guest authorization redirects.

## [1.0.82] — 2026-10-04 — PDF Verification Reports Synchronization & Modernization Parity
### Added & Fixed
- **Official Summary & Detailed PDF Reports Synchronization (`instrument_verification_report_summary.blade.php`, `instrument_verification_report_detailed.blade.php`)**:
  - Full structural and aesthetic parity with the approved `official_report_NotEMT.blade.php` reference template from `app.gmtm-dz.com`.
  - Standardized dedicated logo resolution to `public_path('images/Logo-black.png')` exclusively across all verification PDF templates, eliminating unnecessary array lookups while maintaining crisp branding.
  - Safe null-coalescing site display attribute resolution (`$report->mission?->site?->full_name ?? $report->mission?->site?->short_name ?? $report->mission?->site?->site_name ?? 'N/A'`) replacing missing `site_name` column accesses.
  - Safe scalar normalization for `measurement_type` (`$rawMt instanceof \BackedEnum ? $rawMt->value : $rawMt`) inside transmitter grouping logic.
  - Safe fallback resolution for calibrator calibration certificate references (`$calibrator->latestCertificate?->reference ?? $calibrator->calibrationCertificates?->last()?->reference ?? 'Agréé'`).
- **Equipment Domain Eloquent Relations (`App\Models\Equipment`)**:
  - Restored `latestCertificate(): HasOne` relation utilizing `latestOfMany(['calibration_date', 'id'])` on `CalibrationCertificate::class` ensuring instant access to current valid calibration certificate data across all report generators.
- **Automated Feature Testing (`tests/Feature/Metrology/ReportInstrumentsIndexTest.php`)**:
  - Enhanced `test_user_can_generate_detailed_and_summary_pdf_with_enum_casts` with attached equipment calibrator and calibration certificate, strictly asserting certificate references in both detailed and summary PDF streams.

## [1.0.81] — 2026-10-04 — PDF Verification Reports ProcessVariable & FluidType Enum String Conversion Fix
### Fixed & Standardized
- **Detailed & Summary PDF Reports Grouping (`instrument_verification_report_detailed.blade.php`, `instrument_verification_report_summary.blade.php`)**:
  - Fixed fatal `TypeError: Object of class App\Enums\ProcessVariable could not be converted to string` inside transmitter grouping closure by normalizing `ProcessVariable` backed enum instance into string before equality checks and string concatenation.
  - Normalized `FluidType` backed enum instance across overview badges, transmitter calibration evaluations, and flow computer ADC simulation loops.
- **OAM Metrology Service Defense (`App\Services\OamMetrologyService`)**:
  - Updated `evaluateTransmitter` and `evaluateADC` method signatures to accept `string|\BackedEnum|null $fluid = 'Liquid'`, normalizing backed enums (`FluidType`) and legacy strings into canonical `'Gas'` or `'Liquid'` to prevent strict type errors.
- **Automated Feature Test Suite (`tests/Feature/Metrology/ReportInstrumentsIndexTest.php`)**:
  - Added `test_user_can_generate_detailed_and_summary_pdf_with_enum_casts` verifying HTTP 200 stream responses for both detailed and summary PDF templates with enum-casted instruments.

## [1.0.80] — 2026-10-04 — Measuring Instruments Verification Saisie Views UI Modernization & Unification
### Added & Standardized
- **Transmitter Verification View Modernization (`report-instruments/transmitter.blade.php`)**:
  - Rebuilt legacy layout using standard `<x-app-layout>` with `<x-slot:header>` and sticky `<x-metrology-tabs active="reports" />` sidebar.
  - Replaced legacy raw `<style>` blocks and Bootstrap utility classes with pure Tailwind CSS cards, tables, and badge elements.
  - Standardized call-to-actions using `<x-primary-button>` and `<x-secondary-button>`.
  - Preserved live calculation engine for span percentages, signal measurements, and OIML R 140 EMT evaluation.
- **Temperature Probe Verification View Modernization (`report-instruments/probe.blade.php`)**:
  - Completely refactored view using `<x-app-layout>`, `<x-slot:header>`, standard cards, and dark-mode compatible tables.
  - Preserved full Callendar-Van Dusen Pt100 IEC 60751 temperature calculation engine (Newton-Raphson solver for $R(T) = R_0 (1 + A T + B T^2)$).
  - Standardized equipment selectors and conformance status badges.
- **Flow Computer / ADC Verification View Modernization (`report-instruments/flow_computer.blade.php`)**:
  - Modernized channel selection pills, current input tables, and dual-span ADC current calculations under `<x-app-layout>`.
  - Eliminated inline styles and legacy alert containers, adopting `<x-alert>` and semantic badges.

## [1.0.79] — 2026-10-04 — Calibration Saisie View Adapter & FluidType Enum Stabilization
### Fixed & Standardized
- **Layout Adapter Bridge (`resources/views/layouts/app.blade.php`)**:
  - Implemented adapter component connecting views utilizing `@extends('layouts.app')` directly to `<x-app-layout>` with `@yield('content')` and header slots.
  - Added `@stack('styles')` support in `layouts/app-rtl.blade.php` and `layouts/app-ltr.blade.php`.
- **Transmitter Verification Saisie View (`report-instruments/transmitter.blade.php`)**:
  - Fixed fatal `TypeError` on line 426 where `FluidType` backed enum instance was passed directly instead of its string scalar value to `OamMetrologyService::evaluateTransmitter()`.
- **Instruments Saisie Dispatcher View (`report-instruments/saisie.blade.php`)**:
  - Implemented dynamic polymorphism dispatcher view routing to appropriate specialized instrument calibration views (`transmitter`, `probe`, `flow_computer`).
- **Feature Test Suite (`tests/Feature/Metrology/ReportInstrumentsIndexTest.php`)**:
  - Added `test_user_can_access_report_instruments_saisie_view` verifying HTTP 200 and successful rendering.

## [1.0.78] — 2026-10-04 — Measuring Instruments Report Show View & Polymorphic Controller Dispatching
### Added & Standardized
- **Measuring Instruments Report Show View (`resources/views/metrology/reports/report-instruments/show.blade.php`)**:
  - Implemented comprehensive show view in `report-instruments/` directory conforming to UI Design System (`<x-app-layout>`, `<x-metrology-tabs active="reports" />`, `<x-table>` suite, `<x-badge>`).
  - Added 5-card KPI summary grid (Total Instruments, Compliant, Non-Compliant, In Progress, Progress Bar), report metadata card, and verified instruments table with instant verification saisie and curve navigation links.
- **Polymorphic Report Show View Dispatching (`App\Http\Controllers\Metrology\ReportController::show`)**:
  - Replaced legacy hardcoded view check with category-based polymorphic dispatching (`instruments`, `prover`, `chromatograph`), routing instruments reports directly to `metrology.reports.report-instruments.show`.
- **Feature Test Suite (`tests/Feature/Metrology/ReportInstrumentsIndexTest.php`)**:
  - Added `test_user_can_access_report_instruments_show_view` verifying route resolution, view binding, and report dataset.

## [1.0.77] — 2026-10-04 — Measuring Instruments Report Edit View & Controller Polymorphic Resolution
### Added & Standardized
- **Measuring Instruments Report Edit View (`resources/views/metrology/reports/report-instruments/edit.blade.php`)**:
  - Implemented complete dedicated edit view based on `create.blade.php`, with `@method('PUT')`, pre-populated report numbers, editable lifecycle status selector (`progress` / `completed`), pre-filled mission context, and initial instrument selection retention.
  - Added support for cascading calibrators synchronization (`sync_calibrators_to_all`).
- **Polymorphic Report Edit View Resolution (`App\Http\Controllers\Metrology\ReportController::edit`)**:
  - Dynamically routes category-specific reports to their dedicated edit view (`metrology.reports.report-instruments.edit`), with graceful fallback.
- **Trilingual Localization Parity (`lang/en.json`, `lang/ar.json`, `lang/fr.json`)**:
  - Synchronized 8 new translation keys across English, Arabic, and French maintaining exact 2,131 key parity.
- **Visual Instrument Thumbnails Integration in Report Selection Checklists**:
  - Enhanced `ReportController::getMissionDetails` to provide `image_url` for all site instruments.
  - Updated `report-instruments/edit.blade.php`, `report-instruments/create.blade.php`, `report-Prover/create.blade.php`, and `report-chromatograph/create.blade.php` with responsive image previews (`w-11 h-11 rounded-lg`) and fallback SVG icons.
- **Automated Feature Verification (`tests/Feature/Metrology/ReportInstrumentsIndexTest.php`)**:
  - Added automated tests covering accessing the edit view, view assertion, pre-filled attributes, successful PUT update, and verified that `getMissionDetails` delivers the correct `image_url` payload.

## [1.0.76] — 2026-10-03 — Measuring Instruments Report UI Modernization & Dark Mode
### Refactored & Standardized
- **Measuring Instruments Report Creation View (`resources/views/metrology/reports/report-instruments/create.blade.php`)**:
  - Full compliance with UI Design System (`ui-design-system.md`): Brand primary CTA buttons, `<x-secondary-button>`, `<x-alert variant="danger">` for validation errors, `<x-badge>` semantic tokens, and standardized inputs.
  - Complete Dark Mode support across cards, inputs, info banners, dynamic checklist items, and action bars.
  - Stabilized Arabic RTL numeric display with `dir="ltr"` and tabular font styling.
- **Reference Standards Card (`resources/views/metrology/reports/partials/calibrators_card.blade.php`)**:
  - Purged legacy Bootstrap grid classes and all inline styles (`style="..."`).
  - Implemented responsive Tailwind grid layout (`grid-cols-1 lg:grid-cols-2`, `sm:grid-cols-2 lg:grid-cols-4`) with complete dark mode styles.
- **Trilingual Localization Parity (`lang/en.json`, `lang/ar.json`, `lang/fr.json`)**:
  - Migrated all French translation keys to 100% English master keys in code.
  - Synchronized 53 new keys and resolved 41 previously unmapped keys, restoring 100% trilingual dictionary parity (2,123 keys each).

## [1.0.75] — 2026-10-02 — Metrology Reports Dashboard Architecture & Specialized Domain Hub
### Added & Standardized
- **Metrology Reports Dashboard Hub (`resources/views/metrology/reports/index.blade.php`, `reports.blade.php`)**:
  - Transformed reports explorer into an authentic, industrial-grade Metrology Reports Dashboard tailored specifically to the reports domain:
    - **Header & Navigation:** Standardized with `<x-tool-icon name="reports">`, `<x-badge variant="info">`, and integrated `<x-metrology-tabs active="reports">`.
    - **Metrics Grid:** Dynamic metrics for Measuring Instruments (`PT/TT/PDT`), Gas Chromatographs (`CPG`), Standard Provers (`Mastering Gas MG-1000`), and ISO/IEC 17025 Calibration Certificates.
    - **Specialized Domain Pillars:** Aligned with subfolders `report-instruments/`, `report-chromatograph/`, and `report-Prover/` with complete EMT compliance, OIML R 140 / ISO 6974 analysis, and volume calibration workflows.
    - **Standardized Table:** `<x-table>` recent reports and calibration log with full bilingual metadata and empty-state handling.
- **Backend View Resolution & Metrics Pipeline (`App\Http\Controllers\MetrologyController`)**:
  - Updated `reports()` action to compute all domain counts (`instrumentsCount`, `chromatographsCount`, `proversCount`, `equipmentCount`, `certificatesCount`) at the controller level, upholding zero Eloquent queries inside Blade.
  - Resolved view resolution for both `metrology.reports.index` and root alias `resources/views/metrology/reports.blade.php`.
- **Bilingual Arabic Localization (`lang/ar.json`)**:
  - Added 41 translated keys for technical metrology reporting terminology and domain hubs.

## [1.0.74] — 2026-10-02 — Standard Gas Prover (MG-1000) High-Fidelity Vector Micro-Illustration
### Added & Standardized
- **Precision Volumetric/Gravimetric Standard Prover SVG Icon (`resources/views/components/icons/tools/module-prover.blade.php`, `prover.blade.php`)**:
  - Implemented an authentic, ultra-high-fidelity vector micro-illustration of the industrial standard prover matching the reference photograph ("Mastering Gas MG-1000"):
    - Cylindrical vessel drum with brushed stainless steel anisotropic lighting and circumferential weld bead line.
    - Steep conical reducer transition shoulder with radial specular reflections.
    - Central vertical graduated sight glass tube / piston column flanked by structural guide rods and upper/lower metallic union collars.
    - Twin vertical satin stainless steel scale plates with laser-engraved graduation rules, gas swirl logo, caution triangles, and specification legends.
    - Outer tubular stainless steel guard rail loops.
    - Polished brass/gold valve collar nut and forward-angled sampling drain petcock fitting.
    - Flanged top cap with clamping tie-bolts and domed mushroom adjustment knob / lifting eye.
    - Anodized black identification nameplate badge with corner fasteners, silver border, and complete engraved technical specifications (`TYPE: MG-1000`, `MAX PRESSURE: 350 bar`, `MAX TEMP: +50 °C`, `SERIAL N°: MG-1000-0425`, `DATE: 04/2025`).
    - Prominent lower horizontal tubular carrying handle mounted on heavy-duty triangular folded bracket gussets with dual hex mounting rivets.
  - Added alias forwarder `resources/views/components/icons/tools/prover.blade.php` for seamless `<x-tool-icon name="prover" />` integration.

## [1.0.73] — 2026-10-02 — System-Wide Dark Mode Background Audit & Standardization
### Fixed & Standardized
- **Tailwind Palette Opacity Support (`tailwind.config.js`)**:
  - Mapped GMTM brand color scale to explicit hex codes matching `tokens.css`, enabling Tailwind CSS to compile dynamic opacity utilities (`bg-brand-*/opacity`) into valid RGB triplets with alpha channels.
- **Metrology AI Extractor Theme Harmonization (`resources/views/components/metrology/ai-extractor.blade.php`)**:
  - Removed `via-white` and replaced drag-and-drop container background with dual-theme surface (`border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50`).
  - Standardized audit icon box to emerald palette (`bg-emerald-100 dark:bg-emerald-950/60`).
- **Calibration Certificates Theme Parity (`resources/views/metrology/certificates/`)**:
  - `create.blade.php` & `edit.blade.php`: Added missing dark mode styling to `addPoint()` action buttons (`bg-emerald-100 dark:bg-gray-700 text-emerald-800 dark:text-emerald-300`) and standardized header icon boxes.
  - `show.blade.php`: Replaced header certificate icon container with standard dual-theme emerald styling.
- **Operations Attachments & Contracts Dual-Theme Standard (`resources/views/operations/`)**:
  - `attachments/create.blade.php` & `attachments/edit.blade.php`: Replaced `bg-brand-50/70 dark:bg-brand-900/20` valorization footers with solid dual-theme surface (`bg-emerald-50/80 dark:bg-gray-800/90 border-emerald-200/80 dark:border-gray-700`).
  - `contracts/create.blade.php` & `contracts/edit.blade.php`: Standardized `addItem()` button and item status badges with dark mode support.
  - `contracts/index.blade.php` & `contracts/show.blade.php`: Standardized total contracts and associated missions count badges.
  - `contracts/statistics.blade.php`: Standardized mission statistics action link hover background.
- **Field Missions Dual-Theme Standards (`resources/views/operations/missions/`)**:
  - Standardized context banner badges, team leader and staff initials avatar circles, and equipment picker selection cards.
- **Process Gas Chromatograph (GC) SVG Micro-Illustration (`resources/views/components/icons/tools/module-chromatograph.blade.php`, `chromatograph.blade.php`)**:
  - Engineered realistic vector recreation of the industrial Process Gas Chromatograph matching the field instrument photo with high fidelity (cylindrical fluted column oven dome, cast industrial blue enclosure with mounting ear flange, dual AI 1-4 and AI 5-8 electronic module terminal blocks, stainless steel tubing run, bottom Swagelok multi-port sampling manifold rail, and circular explosion-proof display bezel featuring 3 status diagnostic LEDs, tactile navigation keypads, and illuminated LCD showing live chromatogram analytical separation peaks with `PV` and `mA` metrics).

## [1.0.72] — 2026-10-02 — Enterprise Clean Architecture Execution & Controller Decomposition
### Added & Refactored
- **Architectural Rules Adoption (`.antigravityrules`)**: Adopted complete strict Laravel backend architectural standard from `docs/STRICT_LARAVEL_BACKEND_PROMPT.md`.
- **Validation FormRequests Layer (`app/Http/Requests/Analytics/`)**: Replaced inline controller validation in `AnalyticsController` with dedicated `StoreForecastRequest` and `UpdateForecastRequest`.
- **Composite Database Indexes (`database/migrations/2026_10_02_200000_add_performance_composite_indexes.php`)**: Added high-throughput composite indexes for `charges(['type', 'date'])`, `missions(['status', 'start_date'])`, and `activity_log(['event', 'created_at'])`.
- **Domain Action Classes (`app/Actions/`)**:
  - `CreateContractAction` & `UpdateContractAction`: Encapsulated contract and line-item creation, diffing, and attachment protection out of `ContractController`.
  - `ActivateMissionAction`, `CompleteMissionAction`, `RevertMissionAction`: Encapsulated mission lifecycle transitions out of `MissionController`.
- **Metrology Controller Decomposition (`App\Http\Controllers\Metrology\`)**:
  - Extracted single-responsibility controllers: `InstrumentController`, `EquipmentController`, and `GrandeurUnitController`.
  - Decomposed 337-line god controller `MetrologyController` into a lean dashboard orchestrator while preserving 100% of route names and URL patterns.

## [1.0.71] — 2026-10-02 — Project-Wide Filter Standard Compliance & Query State Persistence Harmonization
### Fixed & Standardized
- **Annual Forecasts Filter Modernization (`resources/views/analytics/forecasts.blade.php`, `AnalyticsController`)**:
  - Replaced ad-hoc GET form with `<x-global-filter>` featuring a dedicated `<select name="year">` dropdown with auto-submit and reset button.
  - Supplied `$availableYears` collection from `AnalyticsController::forecasts` for standard year filtering.
- **Statistics View Localization Standard (`resources/views/analytics/statistics.blade.php`)**:
  - Replaced non-standard `select_year` translation key with standard English master key `Select Year`.
- **Equipment & Units Query State Persistence (`resources/views/metrology/equipment.blade.php`, `units.blade.php`)**:
  - Appended `request()->query()` to modal edit and delete action routes in Equipment and Quantities & Units, preventing filter loss upon record mutations.
- **Operations Warranties Route Context Harmonization (`resources/views/operations/warranties.blade.php`)**:
  - Adjusted `<x-global-filter>` action to contextually respect current routing module (`operations.*` vs `financial.*`).
- **System Users Query State Persistence (`app/Http/Controllers/SystemTableController.php`, `resources/views/system/users.blade.php`)**:
  - Preserved `$request->query()` across user store, update, toggle-status, and destroy operations under the authorized security gate.

## [1.0.70] — 2026-10-01 — Mission Statistics Comprehensive Calculation & UI Explanation Alignment
### Fixed & Enhanced
- **Mission Statistics Calculations (`App\Services\MissionStatisticsService`)**:
  - Confirmed and anchored `$totalDueDays = $operationalDays + $mobDemobDays` based on corporate policy that transit days represent paid on-duty working days for field personnel.
  - Replaced arbitrary lump-sum `$dailyTeamRate` with strict individual employee entitlement calculations (`$days = diffInDays + 1`, `$totalAmount = $days * $daily_rate`) per mission order.
  - Aligned Gross Daily Yield (`$rateJAvecDepenses`) to strictly divide by active operational days (`$operationalDays`) matching the UI explanation.
  - Aligned Net Daily Yield (`$rateJReel`) to divide gross profit across full mission days (`$totalMissionDays`), providing accurate unit economics smoothing.
  - Aligned field expense metrics (Option A): separated `$directCharges` from total field expenditure (`$totalExpensesMission = $totalHrCost + $directCharges`), eliminating double-counting.
  - Fixed flat array keys `net_profit` (DZD) and `net_margin` (%) in service return payload.
- **Mission Statistics View & Tooltip Transparency (`operations/missions/statistics.blade.php`)**:
  - Added formula tooltips to all metric cards (`Total Due Days`, `Operational HR Costs`, `Total HR Cost`, `Direct Expenses`, `Total Field Expenses`, `Direct Operating Charges`).
  - Replaced misleading `Daily Team Rate` with active `Assigned Staff` (`staff_count`).
  - Synchronized all new translation keys across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` maintaining 100% key parity (1,952 keys).

## [1.0.69] — 2026-10-01 — Mission Statistics Automatic Annual Fiscal Year Subjection & GMTM Overhead Fix
### Fixed & Enhanced
- **Mission Unit Economics & Corporate Overhead Absorption (`App\Services\MissionStatisticsService`)**:
  - Automatically resolved the operational fiscal year strictly from the mission's active execution dates (`start_date`, fallback to `end_date` / `created_at`), eliminating the requirement for manual filter manipulation.
  - Fixed snake_case `charge_type` column resolution on the `charges` table, repairing annual GMTM fixed and variable overhead retrieval which previously silently evaluated to zero due to legacy case sensitivity.
  - Synchronized annual company working days divisor with soft-delete exclusion (`deleted_at IS NULL`), accurately deriving the daily corporate office overhead absorption rate (`gmtm_expense_rate`).
  - Corrected Net Profit calculation (`$grossProfit - ($totalMissionDays * $gmtmExpenseRate)`) to accurately deduct annual corporate overhead absorption.
- **Mission Statistics View Context Transparency (`operations/missions/statistics.blade.php`)**:
  - Added visual `Fiscal Year` badge in the header context banner directly identifying the operational year governing the calculations.
  - Enhanced Section 6 (Corporate Office Absorption Box) with a 4-metric transparency grid displaying Fiscal Year, Total Annual Office Overhead, Company Active Working Days, and Mission Absorbed Overhead.
  - Added 4 trilingual keys (`Fiscal Year`, `Annual Office Overhead`, `Company Active Days`, `Mission Absorbed Overhead`) with 100% key parity across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` (1,942 keys each).

## [1.0.68] — 2026-10-01 — Financial Expenses & Charges Legacy Modernization & Migration
### Changed
- Standardized visible date and datetime output to `dd/mm/yyyy` through a shared Blade component while retaining ISO values for date inputs and editing payloads.
- Fixed mission creation date controls to use left-to-right British date presentation in RTL pages.
- Isolated the mission departure date label to preserve its translated text and required marker order in RTL layouts.

### Added
- **Full Expenses CRUD Suite (`Financial\ExpenseController`, `ExpenseService`, `charges` table)**:
  - Migrated legacy `charges` schema to Clean Architecture with standard column naming, foreign key constraints (`missions`, `contracts`, `attachment_items`, `item_types`), and activity logging (`Spatie\Activitylog`).
  - Implemented `App\Models\Expense` (with `Charge` backward-compatibility alias) and Enums `ExpenseAffiliation` (`mission`, `contract`, `item`, `gmtm`, `prisma`) and `ExpenseChargeType` (`fixed`, `variable`).
  - Added dedicated CRUD views matching user-preferred legacy flow with standalone pages: `Financial.expenses.index`, `Financial.expenses.create`, `Financial.expenses.edit`, `Financial.expenses.show`.
  - Implemented dynamic affiliation linking in Blade/JS: fields for Mission, Active Contract, or Attachment Item dynamically display based on the selected type, with corporate GMTM fixed/variable classification.
  - Implemented annual calendar and category filtering with instant yearly turnover total calculation on the index page.
  - Seeded 53 historical charges records from `backup_2026_017.sql` preserving exact historical primary keys, amounts, and dates.
  - Implemented Month-in-the-Middle hybrid Date Input component (`DD/MM/YYYY` / `يوم/شهر/سنة`) in `Financial/expenses/create.blade.php` and `edit.blade.php` powered by Alpine.js with native datepicker modal integration and automatic dual-format normalization (`prepareForValidation` converting `d/m/Y` to `Y-m-d`).
  - Added Form Requests: `StoreExpenseRequest` and `UpdateExpenseRequest`.
  - Added 45 translation keys across Arabic, English, and French dictionaries (`lang/*.json`) with 100% key parity (1,938 keys each).
  - Implemented comprehensive Feature Test `tests/Feature/Financial/ExpenseManagementTest.php` with 10 passing tests covering authorization, filtering, CRUD, affiliation normalization, and `DD/MM/YYYY` date parsing.


## [1.0.67] — 2026-10-01 — Operations Attachments Localization & Table Layout Stabilization
### Changed & Optimized
- **Attachments Index Table Stabilization & Typography (`operations/attachments/index.blade.php`)**:
  - Reduced table typography to `text-xs` (with secondary metadata at `text-[10px]` / `text-[11px]`) and applied compact cell padding (`py-2.5 px-3`).
  - Enforced strict `whitespace-nowrap` across all `<th>`, `<tr>`, and `<td>` elements to completely prevent line breaks and keep rows clean and single-line.
  - Stabilized Arabic RTL numeric, date, and currency formatting with `<span dir="ltr">` wrappers per `ADR-043`.
- **KPI Annual Filter Synchronization (`AttachmentController.php` & `operations/attachments/index.blade.php`)**:
  - Integrated Alpine.js reactive annual state across the entire KPI summary grid, ensuring "Approved / Invoiced" (count & validation rate) and "Total Invoiced" (monetary turnover) dynamically update in sync with the selected year (2026, 2025, 2024, etc. or All Years).
  - Added persistent all-time "Grand Total" (`المجموع الكلي`) display at the bottom of the "Total Attachments" card, preserving context while switching annual views.
  - Implemented backend yearly aggregation in `AttachmentController::index` calculating counts (total, approved, draft) and valorized revenue per year, synchronizing trilingual keys `"All Years"` and `"Grand Total"` across Arabic, English, and French dictionaries (1,845 keys each).
- **Localization Audit & Synchronization (`operations/attachments/index.blade.php`, `show.blade.php`, `edit.blade.php`, `create.blade.php`, `lang/*.json`)**:
  - Performed comprehensive localization audit on `edit.blade.php` and `create.blade.php`, standardizing all translation calls into English master keys (converting legacy French keys `Semestrielle`/`Annuelle` to `Semi-annual`/`Annual`).
  - Added 34 new translation keys across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` reaching 1,879 keys each with 100% key parity, zero fallbacks, and zero missing translations.
  - Stabilized live valorization totals, line item unit prices, and subtotals with `<span dir="ltr">` preventing Arabic RTL BiDi digit inversion.
  - Converted unlocalized placeholder (`e.g. 1844-7 / ODS-2026`) and JavaScript item badges/labels (`Category`, `Contract Total`, `Standard`) to use standard `__()` calls.
- **Active Contracts Invariant Enforcement (`AttachmentController.php` & `AttachmentTest.php`)**:
  - Enforced strict `Contract::active()` query scoping in `create()`, `edit()`, and `index()` methods, ensuring expired contracts never appear in contract selection dropdowns or forms.
  - In `edit()`, retained current contract assignment via fallback `orWhere('id', $selectedContractId)` while preventing selection of other expired contracts.
  - Added feature test `test_create_attachment_view_excludes_expired_contracts` asserting that expired contracts are excluded from the view (4/4 tests passing).
- **Vehicle Selection Display Standardization (`MissionController.php`, `MissionOrderController.php`, & Blade Views)**:
  - Added `serial_number` column to vehicles queries in `MissionController::create()`, `MissionController::edit()`, and `MissionOrderController::edit()`.
  - Harmonized vehicle dropdown labels across `missions/create.blade.php`, `missions/edit.blade.php`, and `missions/orders/edit.blade.php` to display `{{ $vehicle->full_name }} ({{ $vehicle->serial_number }})`, accurately exposing vehicle matriculation numbers.
- **Attachment Creation Legacy Parity (`create.blade.php` & `edit.blade.php`)**:
  - Aligned items calculation and behavior with legacy `app.gmtm-dz`: `actual_quantity` initializes to `0` upon contract auto-fill or item addition, leaving actual executed volume to be entered by the user.
  - Pre-calculated `planned_quantity` dynamically using contract item quantity and duration schedule (`annual: total / (duration / 12)`, `semi-annual: total / (duration / 6)`).
  - Locked `code_ref` input to `readonly` with explicit system auto-generation visual styling, mirroring legacy system flow.

## [1.0.66] — 2026-10-01 — Operations Contract Show Page Optimization & Design System Compliance
### Changed & Optimized
- **Contracts Show Architectural Optimization (`ContractController::show` & `show.blade.php`)**:
  - Eliminated in-view database queries by pre-computing financial totals (`$totalPlanned`, `$totalInvoiced`, `$totalConsumed`) in `ContractController::show` and passing them cleanly to the Blade view.
  - Eliminated N+1 query vulnerability when iterating associated missions by eager-loading `'missions.site'`.
  - Added clean model methods in `App\Models\Contract`: `expiryBadgeVariant(): string` and `expiryPhaseLabel(): string` removing ad-hoc PHP `match` blocks from Blade templates.
  - Fully refactored Contract Items and Associated Missions tables to use the standardized `<x-table>` suite (`<x-table>`, `<x-table.th>`, `<x-table.tr>`, `<x-table.td>`, `<x-table.empty>`, `<x-table.actions>`, `<x-table.action-view>`).
  - Standardized action buttons in header using unified tokens and `<x-danger-button>` for contract deletion.
  - Localized contract item type labels (`__(ucfirst($item->type ?? 'service'))`).
  - Implemented Arabic RTL BiDi isolation wrappers (`<bdi>` & `.font-mono`) across contract references, dates, durations, monetary amounts, and percentages per `ADR-043` (`app-rtl.css`).
  - Fixed section container grid nesting in `show.blade.php`, ensuring Contract Items and Associated Field Missions tables render as full-width vertical stacked sections (`w-full`) rather than horizontal grid columns.
  - Handled negative remaining days in `Time Remaining` card to display semantic message `"Expired"` (`"منتهية"`) with elapsed expiration days subtitle (`"Expired :days d ago"` / `"انتهت منذ :days يوم"`).
  - Simplified Contracts Catalog table header in `operations/contracts/index.blade.php` from `{{ __('Reference & Object') }}` to `{{ __('Reference') }}`.
  - Added dedicated Services and Supplies KPI cards in `operations/contracts/show.blade.php` with nature breakdown (`servicesTotalPlanned()`, `suppliesTotalPlanned()`, `servicesCount()`, `suppliesCount()`), maintaining zero-SQL view rendering and 100% trilingual dictionary parity.
  - Stabilized KPI summary cards grid layout in `operations/contracts/show.blade.php` to a balanced 3-column structure (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-3`) with strict `whitespace-nowrap` typography, preventing line wrapping across numbers, currencies, badges, and subtitles.
  - Ordered Contract Items & Consumptions in `operations/contracts/show.blade.php` and `ContractController::show` to prioritize Service items first, followed by Supply items second, adding nature indicator badges in the table toolbar.
  - Transformed Card 1 in `operations/contracts/show.blade.php` to display Unconsumed Value (`{{ __('Unconsumed Value') }}` / `"القيمة غير المستهلكة"`) calculated as `totalUnconsumed() = Items sum - Total Consumed` (`rawTotalUnconsumed()`), with dynamic deficit formatting (`text-rose-600`) and Items sum subtitle.
  - Added tests for nature-based KPI totals, items ordering, and unconsumed value calculation in `ContractManagementTest` (16/16 passing tests).
- **Operations Missions Index Page Localization & BiDi Formatting (`operations/missions/index.blade.php`)**:
  - Identified and synchronized 6 missing translation keys across all 3 language dictionaries (`lang/en.json`, `lang/ar.json`, `lang/fr.json`): `"Missions"`, `"Ref, site, engineer..."`, `"Team Leader & Staff"`, `"op."`, `"No Leader Assigned"`, `"members"`, maintaining strict 100% English master key parity.
  - Applied Arabic RTL BiDi isolation (`<bdi>` & `.font-mono`) to mission references, badge counts, member counts, and timeline operational day metrics per `ADR-043` (`app-rtl.css`).
  - Verified 18/18 localization tests and 22/22 mission management feature tests passing cleanly.
- **Operations Missions Terminology Harmonization (`lang/ar.json`)**:
  - Replaced legacy terminology `"الكوادر"` with `"المهندسين"` / `"المهندسون"` across mission operational statistics and views: `"Total HR Cost"` &rarr; `"إجمالي تكاليف المهندسين"`, `"HR Per-Diems + Operational Charges"` &rarr; `"بدلات المهندسين + نفقات المهام المباشرة"`, `"Staff Deployment"` &rarr; `"حجم انتشار المهندسين"`, `"Field Personnel & Official Travel Orders (Ordres de Mission)"` &rarr; `"المهندسون الميدانيون وأوامر السفر الرسمية"`.
  - Preserved strict 100% dictionary parity across Arabic, English, and French.
- **Mission Statistics View Translation Refactoring & Normalization (`operations/missions/statistics.blade.php`, `lang/*.json`)**:
  - Eliminated 41 legacy `snake_case` translation keys (`mission_statistics`, `select_year`, `cost_breakdown`, `days_mob_demob`, etc.) and converted them to natural English master keys per `ADR-037` and `.ai/rules/localization-alerts.md`.
  - Fixed 4 untranslated keys that previously printed raw snake_case on screen: `"Total Revenue"`, `"Total Expenses"`, `"Gross Profit"`, and `"Gross Margin"`.
  - Added full trilingual translations for table headers and office overhead metrics (`"Assignment"`, `"Total"`, `"No staff members assigned to this mission."`, `"GMTM Office Daily Overhead Absorption"`, `"Calculated based on reference year office expenses divided by annual active/forecast days."`, `"Office Daily Rate"`).
  - Synchronized exact 100% parity across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` (1,779 keys each).

## [1.0.65] — 2026-10-01 — Arabic RTL BiDi Numbers & Dates Stabilization
### Fixed
- **Arabic RTL Bidirectional (BiDi) Number Inversion (`app-rtl.css` & `app-rtl.js`)**:
  - Enforced `Noto Kufi Arabic` typography across `[dir="rtl"]` layout with tabular numbers (`font-feature-settings: "tnum" 1`).
  - Added comprehensive CSS BiDi isolation (`direction: ltr !important; unicode-bidi: isolate !important;`) for `.font-mono`, `code`, `time`, `kbd`, `samp`, `.tabular-nums`, and `.ltr-data`.
  - Protected form inputs (`number`, `tel`, `date`, `time`, `datetime-local`, `month`, `week`) and technical identifier inputs (`reference`, `code`, `serial`, `registration`, `email`) in RTL mode.
  - Implemented an intelligent client-side BiDi stabilizer in `resources/js/app-rtl.js` with `MutationObserver` to automatically isolate dates, monetary amounts, references, phone numbers, and IDs across dynamic Alpine modals, tables, and views without breaking DOM reactivity.
  - Updated Blade templates (`article-types.blade.php`, `Financial/warranties.blade.php`, `operations/warranties.blade.php`, `contracts/index.blade.php`, `contracts/statistics.blade.php`, `missions/statistics.blade.php`) with explicit `<bdi>` and `.font-mono` isolation wrappers.
  - Recompiled production frontend assets via `npm run build`.

## [1.0.64] — 2026-09-30 — Contracts Schema Harmonization (garantie_id Alignment)
### Changed
- **Contracts Schema & Relationship Harmonization (`warranty_id` → `garantie_id`)**:
  - Renamed foreign key column `contracts.warranty_id` to `contracts.garantie_id` via migration `2026_09_30_202046_rename_warranty_id_to_garantie_id_in_contracts_table.php`, restoring exact semantic alignment with `garanties` table.
  - Re-established foreign key constraint `contracts_garantie_id_foreign` pointing to `garanties(id)` with `on delete set null`.
  - Updated `Contract` model `$fillable`, `$filterable`, `getActivitylogOptions`, and relationship `warranty()` (with `garantie()` alias).
  - Updated `Warranty` model `contracts()` relationship and `deleting` event nullification hook.
  - Updated `ContractController`, `StoreContractRequest`, and `UpdateContractRequest` with backward-compatible alias merging.
  - Updated Blade templates `operations/contracts/create.blade.php` and `operations/contracts/edit.blade.php`.
  - Updated `MigrateContractsFromLegacy` artisan command and expanded `ContractManagementTest` suite (12 passing tests).
- **Bank Guarantees Redirect & Module Alignment (`financial.warranties`)**:
  - Unified all warranty creation, update, and deletion redirects to `financial.warranties`.
  - Harmonized legacy `OperationsController` warranty actions to forward requests to the Financial module.
  - Corrected modal action routes in both `resources/views/Financial/warranties.blade.php` and `resources/views/operations/warranties.blade.php` to target `financial.warranties.*`.
  - Updated and expanded `WarrantyTest` suite covering canonical and legacy routes (10 passing tests).

## [1.0.63] — 2026-09-30 — Permissions Classification & Financial Module Alignment
### Changed
- **Permissions Static Registry Alignment (`config/permissions.php`)**:
  - Registered `financial` as a dedicated business module (`Financial Management`) with root view permission `view financial` and `teal` semantic theme.
  - Reclassified `warranties` (Bank Guarantees) from `operations` to `financial`.
  - Reclassified `expenses` (Expenses & Charges) from `analytics` to `financial`.
  - Updated `PermissionDiscoveryService::pruneStaleCrudPermissions` to safeguard explicitly configured permissions against spurious pruning.
  - Added `teal` badge and color styling in `resources/views/system/roles.blade.php` for Role Create and Edit permission matrices.
  - Synchronized static registry into database via `permissions:sync-tables` (86 total active permissions bound to `Super-Admin`).
  - Updated `tests/Feature/BusinessModulesTest.php` with 14 passing feature tests covering all 5 business modules and dashboards.

## [1.0.62] — 2026-09-28 — Article Types (ItemType) Operations CRUD & Active Contracts Filtering
### Added
- **Article Types (ItemType) Operations Suite**:
  - Implemented `StoreItemTypeRequest` and `UpdateItemTypeRequest` with `designation` unique validation.
  - Implemented `ItemTypeRepositoryInterface`, `ItemTypeRepository`, and `ItemTypeService` for article types catalogue management.
  - Implemented `OperationsController` CRUD methods (`articleTypes`, `storeItemType`, `updateItemType`, `destroyItemType`).
  - Upgraded `resources/views/operations/article-types.blade.php` with GMTM Design System, `<x-global-filter>`, responsive table, Alpine modals (create, edit, delete), and full pagination query string persistence.
  - Synchronized 15 new trilingual translation keys across `lang/en.json`, `lang/ar.json`, and `lang/fr.json`.
- **Active Contracts Selection Filter Invariant**:
  - Enforced business invariant restricting contracts selection lists across all views exclusively to active contracts (`scopeActive()`), ensuring expired contracts never appear in dropdown lists.
- **Contracts Domain Trilingual Translation Audit & 100% Parity Synchronization**:
  - Audited all 5 views in `resources/views/operations/contracts/` (`index`, `create`, `edit`, `show`, `statistics`) along with `ContractController` and `ContractRequest` classes.
  - Added and synchronized 124 missing translation keys across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` (reaching 1,723 keys each with 0 missing and 0 non-English keys).
  - Sanitized `ContractObserver` to replace legacy hardcoded Arabic `'غير محدد'` with neutral fallback `'—'`.
- **Living Memory & Documentation-First Directives**:
  - Updated `.ai/rules/documentation.md`, `.ai/rules/index.md`, `AGENTS.md`, `CLAUDE.md`, `.antigravityrules`, and `docs/project_state.md` strictly prohibiting blind codebase scanning and mandating documentation-first discovery and post-flight updates.


## [1.0.61] — 2026-09-26 — Contracts & Attachments Domain Management Suite
### Added
- **Contracts & Attachments Domain Architecture (`Contract`, `ContractItem`, `ItemType`, `Attachment`, `AttachmentItem`)**:
  - Implemented migrations for `contracts`, `contract_items`, `item_types`, `attachments`, and `attachment_items`.
  - Established `ContractItem` as the central entity linking commercial contracts to the item types catalogue (`ItemType`).
  - Added `BillingCycle` enum and `ContractStatisticsService` for financial consumption and budget execution tracking.

## [1.0.60] — 2026-09-25 — Missions Management Suite Modernization & Legacy Migration
### Added
- **Missions Domain Models & Database Architecture (`App\Models\Mission`, `MissionOrder`, `MissionDeployment`)**:
  - Implemented modern Eloquent models adhering to SARL GMTM Core Kernel architecture (`#[Fillable]`, `FilterableTrait`, `HasActivity`, `SoftDeletes`).
  - Added backed PHP enums: `MissionStatus` (`Planned`, `Active`, `Completed`, `Cancelled`), `MissionOrderStatus` (`Active`, `Completed`, `Cancelled`), and `MissionDeploymentStatus` (`Active`, `Returned`, `Damaged`) with badge variants, colors, and lifecycle transition guards.
  - Implemented database migrations:
    - `2026_09_25_120000_create_missions_table.php` (Includes `description`, `reference` unique constraint, `softDeletes`).
    - `2026_09_25_121000_create_mission_orders_table.php` (Foreign keys to `missions`, `employees`, and `equipments`).
    - `2026_09_25_122000_create_mission_deployments_table.php` (Foreign keys to `missions` and `equipments`, deployment state tracking).
- **Clean Architecture Service & Repository Suite (`MissionService`, `MissionStatisticsService`, `MissionConflictService`, `MissionRepository`, `MissionOrderRepository`)**:
  - Created `MissionRepositoryInterface` and `MissionRepository` implementing query filtering, scoped sorting, and eager relationship hydration (`site`, `missionOrders.employee`, `deployments.equipment`).
  - Created `MissionOrderRepositoryInterface` and `MissionOrderRepository` managing individual travel orders and operational logistics.
  - Created `MissionConflictService` providing temporal conflict detection for both personnel (overlapping employee travel dates) and equipment (calibrator double-booking) with `MissionConflictException`.
  - Created `MissionStatisticsService` calculating Unit Economics KPIs (operational days, personnel per diem costs, vehicle fleet usage, equipment deployment rates) with database-agnostic cross-DB compatibility (Carbon-based duration math).
  - Created `MissionService` extending `BaseService` with atomic database transactions (`DB::transaction`), lifecycle state machine transitions (`activate`, `complete`, `revert`), reference generation, and team leader order synchronization.
  - Form Requests: `StoreMissionRequest`, `UpdateMissionRequest`, and `UpdateMissionOrderRequest` enforcing authorization gates and the single team leader business invariant.
- **Missions UI Suite & Travel Document Generation (`resources/views/operations/missions/`)**:
  - `index.blade.php`: Modern listing view with KPI metrics, status filters, search, responsive data table, and action triggers.
  - `create.blade.php` & `edit.blade.php`: Multi-card form interfaces with dynamic team member addition, site selection, vehicle specification, and single team leader selection.
  - `show.blade.php`: Comprehensive mission dossier displaying operational snapshot, team travel orders, and deployed calibrators.
  - `orders/edit.blade.php`: Dedicated editor for individual employee travel orders, per diem rates, transport modes, and departure/return dates.
  - `orders/print.blade.php`: Official Algerian A4 printable "Ordre de Mission" travel permit adhering to Algerian regulatory and company standards, featuring official GMTM branding, bilingual headers, transport authorization, and signature/stamp boxes.
  - `statistics.blade.php`: Dedicated Unit Economics and financial performance dashboard detailing personnel allocations, operational day metrics, and equipment utilization.
- **Legacy Migration & Verification**:
  - Implemented `LegacyMissionSeeder` migrating historical data from `backup_2026_017.sql` (21 missions, 65 mission orders, and 263 deployments successfully migrated and cross-verified).
  - Synchronized 93 new translation keys across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` maintaining 100% trilingual key parity.
  - Added comprehensive test suite in `tests/Feature/Operations/MissionManagementTest.php` (9 tests, 28 assertions, 100% passing across MySQL and SQLite).
  - Full application test suite verified: 403 tests, 1,886 assertions passing cleanly with zero failures.

## [1.0.59] — 2026-09-24 — Measuring Instruments Modernization & Legacy Migration
### Added
- **Measuring Instruments Domain Entities & Eloquent Architecture (`App\Models\Instrument`, `InstrumentSpecification`, `StandardGaugeSpecification`, `ProverSpecification`)**:
  - Implemented modern Eloquent models adhering to SARL GMTM Core Kernel architecture (`#[Fillable]`, `#[ObservedBy]`, `FilterableTrait`, `HasActivity`, `SoftDeletes`).
  - Added backed PHP enums: `InstrumentType`, `InstrumentStatus`, `ProcessVariable`, `FluidType`, `ProverType` with semantic badge variants and FA icons.
  - Implemented migration `2026_09_24_223000_create_instruments_and_specifications_tables.php` creating `instruments`, `instrument_specifications`, `flow_computer_transmitter`, `standard_gauge_specifications`, and `prover_specifications`.
  - Created `InstrumentObserver` handling SHA-256 CAS deduplication and safe media deletion via `MediaOptimizationService`.
- **Clean Architecture Service & Repository Suite (`InstrumentService`, `InstrumentRepository`, Requests)**:
  - Created `InstrumentRepositoryInterface` and `InstrumentRepository` with query filtering, eager loading, and pagination.
  - Created `InstrumentService` with transaction safety, CAS WebP image optimization, specifications syncing, and flow computer channel mapping.
  - Created `StoreInstrumentRequest` and `UpdateInstrumentRequest` with granular authorization (`create measuring instruments`, `edit measuring instruments`).
- **Measuring Instruments UI Suite (`resources/views/metrology/instruments.blade.php`, `show.blade.php`)**:
  - Upgraded `/metrology/instruments` with GMTM Design System, `<x-tool-icon name="instruments">`, 4 KPI cards, `<x-global-filter>`, `<x-table>`, responsive Create/Edit Alpine modals, and delete modal.
  - Created detailed show view `resources/views/metrology/instruments/show.blade.php` displaying technical characteristics, physical ranges, flow computer channels, standard gauge and prover blueprints.
  - Added full CRUD routes under `metrology.instruments.*` handled by thin `MetrologyController`.
  - Resolved `MethodNotAllowedHttpException` on instrument deletion by binding `<x-crud-modal.delete>` action-url and item-name properties and properly structuring query parameter merging.
  - Resolved edit modal initialization and payload binding by replacing unescaped JSON attribute values with base64 payload serialization and direct Alpine modal architecture.
- **Legacy Migration & Verification**:
  - Created `php artisan instruments:import-legacy` command and `LegacyInstrumentSeeder` successfully modernizing and importing all 82 legacy instruments from `gmtm_app` into `gmtmdz_erp`.
  - Synchronized 56 new translation keys across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` maintaining 100% trilingual key parity.
  - Added comprehensive feature tests in `tests/Feature/Metrology/MeasuringInstrumentFeatureTest.php` (7 tests, 27 assertions, 100% passing).

## [1.0.58] — 2026-09-24 — Metrology Reports Feature Relocation
### Changed
- **Reports Explorer Relocation to Metrology Domain (`resources/views/metrology/reports.blade.php`)**:
  - Relocated reports management explorer view from `analytics` to `metrology` domain (`resources/views/metrology/reports.blade.php`), binding it with `<x-metrology-tabs active="reports" />`.
  - Registered route `GET /metrology/reports` (`metrology.reports`) handled by `MetrologyController@reports` with `Gate::authorize('view reports')`.
  - Added Reports Management navigation item to `<x-metrology-tabs>` and integrated Reports overview card and quick-access trigger in `resources/views/metrology/index.blade.php`.
  - Deprecated and removed `analytics.reports` route, controller method (`AnalyticsController@reports`), analytics tab, and dashboard card in `resources/views/analytics/index.blade.php`.
  - Updated permission metadata in `config/permissions.php` migrating `reports` entity to `metrology` module.
  - Updated test assertions in `tests/Feature/BusinessModulesTest.php` ensuring full permission-based suite coverage.

## [1.0.57] — 2026-09-24 — Global Dark Mode Color & Contrast Harmonization
### Changed
- **Token-Based Alpha Transparency Refactoring**:
  - Replaced all non-standard muddy dark background overrides (`dark:bg-*-950/60`, `dark:ring-brand-950/60`) with curated luminous alpha-transparency tokens (`dark:bg-*-500/20`, `dark:ring-brand-500/30`, `dark:border-*-500/20`) across navigation avatars, equipment KPI cards, and modal headers.
- **Form Controls & Checkbox Dark Mode Standards**:
  - Upgraded raw form checkboxes across equipment management modals to standard dark mode tokens (`dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-offset-gray-800`).
  - Standardized modal backdrops across views to uniform `bg-gray-900/60 backdrop-blur-sm transition-opacity`.
- **System Components & Alerts Refactoring (`<x-alert>`, `components.css`)**:
  - Harmonized alert banners, button ghost styles, and primary icon containers with clean alpha-transparency dark tokens.
- **Alerts, Notifications & Messages Dark Mode Overhaul (`<x-alert>`, `<x-auth-session-status>`, `resources/views/system/notifications.blade.php`, `ai-extractor.blade.php`)**:
  - Upgraded `<x-alert>` and `<x-auth-session-status>` with a premium frosted glassmorphism style (`rounded-2xl`, `backdrop-blur-md`, `dark:bg-*-950/35`, `dark:border-*-500/30`, `dark:text-*-100`, `dark:shadow-lg`) and frosted glass icon badge containers (`w-8 h-8 rounded-xl backdrop-blur-sm dark:bg-*-500/20 border dark:border-*-500/40`).
  - Enhanced system database notifications view and Alpine inspection modal (`resources/views/system/notifications.blade.php`) with high-contrast column typography, code badges, and modal payload styling.
  - Refined AI extractor toasts and navigation logout hover states (`dark:hover:bg-rose-500/20`) eliminating muddy `*-900`/`*-950` dark backgrounds.
- **Equipment Operational Status Simplification (`App\Enums\EquipmentStatus`, `EquipmentService`, `EquipmentRepository`, Views, Migration)**:
  - Streamlined `EquipmentStatus` to strictly two operational states: `Active` (`active` / "نشط" / `success` variant) and `Inactive` (`inactive` / "غير نشط" / `danger` variant).
  - Deprecated and removed legacy intermediate states (`maintenance`, `deployed`, `retired`), migrating all existing database records accordingly.
  - Harmonized equipment details (`resources/views/metrology/equipment/show.blade.php`), index overview, KPI bar, edit modal, translation dictionaries (`lang/ar.json`), and test suite (`EquipmentFeatureTest`).
- **Metrology KPI Pillar Card Harmonization (`resources/views/metrology/equipment.blade.php`, `resources/views/metrology/certificates/index.blade.php`, `resources/views/metrology/units.blade.php`)**:
  - Re-aligned the "Has Certificate" priority card to Emerald (`emerald-500`), representing valid metrological certification with luminous dark mode alpha tokens and distinct active states.
  - Upgraded the 5-Card Certificate KPI Counter Grid (`Total Certificates`, `Valid & Active`, `Expiring Soon`, `Expired`, `Drafts`) and the table's `Certificate` column icon container from muddy `dark:bg-*-900/40` to luminous alpha-transparency tokens (`dark:bg-brand-500/20` and `dark:border-brand-500/30`).
  - Upgraded the 4-Card Quantities & Units KPI Counter Grid (`Total Quantities`, `Measurement Capabilities`, `Source Capabilities`, `Configured Specs`) in `resources/views/metrology/units.blade.php` to luminous alpha-transparency tokens (`dark:bg-*-500/20` and `dark:border-*-500/30`).
- **Physical Quantities Discipline & Unit Symbol Badge Harmonization (`app/Enums/GrandeurDiscipline.php`)**:
  - Replaced legacy muddy opaque dark backgrounds across all 11 metrology disciplines in `GrandeurDiscipline::badgeClass()` and `GrandeurDiscipline::iconContainerClass()` with standard alpha-transparency tokens (`dark:bg-*-500/20` and `dark:border-*-500/30`).
  - Standardized the `Unit Symbol` badge column across units catalog, equipment specifications, calibration curves, and `<x-grandeur-badge>` components with luminous, high-contrast dark mode badges.
- **Calibration Certificates Equipment Scope & Domain Isolation (`App\Models\Equipment`, `CalibrationCertificateController`, `CalibrationCertificateService`, Requests)**:
  - Enforced strict business domain filtering ensuring only equipment eligible for metrological calibration (`Equipment::forCalibration()`) is fetched across the Certificates module (`index`, `create`, `edit`), eliminating non-calibration assets (vehicles, work tools, screwdrivers).
  - Added `Equipment::isCalibrationEligible()` helper and database-backed validation rules (`StoreCalibrationCertificateRequest`, `UpdateCalibrationCertificateRequest`, `StoreCertificateExtractionRequest`) guarding against assigning calibration records to work tools or vehicles.
  - Added dedicated feature test `test_certificates_module_only_fetches_and_accepts_calibration_eligible_equipment` ensuring end-to-end enforcement across listing, creation, and submission.

## [1.0.56] — 2026-09-24 — AI Certificate Compatibility Audit & Table Routing Matrix
### Added
- **Interactive Table Routing & Specification Mapping Plan (`resources/views/components/metrology/ai-extractor.blade.php`)**:
  - Implemented an interactive Table-to-Specification Routing Matrix replacing blind/random dumping of calibration points.
  - Automatically matches physical standards by unit symbol (`symbol`) and mode (`measurement` vs `source`), providing explicit assignment dropdowns for manual selection or skipping unassigned tables.
  - Displays match status indicators (`Matched (Auto)`, `Manual`, `Skip this Table`) with mapped tables counter.
- **Equipment Compatibility Audit & Auto-Resolution (`resources/views/components/metrology/ai-extractor.blade.php`, `app/Services/AiPdfExtractionService.php`)**:
  - Extended Gemini extraction prompt and normalization schema to parse the certificate `device` metadata (`model`, `serial_number`, `manufacturer`, `designation`, `identification_code`).
  - Added real-time audit comparing certificate device details against the selected form equipment (serial number match, model verification, standards coverage check).
  - Implemented smart inventory auto-resolution with a one-click `[Switch to this Equipment]` action button when a matching device is discovered in inventory.
- **Trilingual Localization & Tests**:
  - Registered all verification and routing keys across `lang/en.json`, `lang/ar.json`, and `lang/fr.json`.
  - Added unit/feature test `test_extraction_normalizes_device_metadata_for_compatibility_verification` in `tests/Feature/Metrology/CalibrationCertificateExtractionTest.php`.

## [1.0.55] — 2026-09-24 — Metrology Equipment 4-Pillar Priority Ordering & KPI Bar Harmonization
### Changed
- **Equipment Inventory Priority Ordering (`App\Repositories\EquipmentRepository`)**:
  - Enforced structured default sort priority via SQL CASE: (1) Has Certificate / Measuring Instrument with calibration (`requires_calibration = 1`), (2) Work Tools (`work_tool`), (3) Vehicles (`vehicle`), (4) Inactive / Retired (`inactive`, `retired`).
- **Calibration Curve Component Isolation & Stability (`<x-curve>`, `resources/views/components/curve.blade.php`, `resources/views/metrology/certificates/show.blade.php`)**:
  - Extracted the entire Florian Platel interpolation and 5-point calibration curve interface (multi-parameter specification selector, setpoint configuration form, uncertainty decomposition table, single calibration curve Chart.js canvas, and historical drift comparison chart) into a reusable Blade component `<x-curve>`.
  - Added direct "View Curve" action buttons in the toolbar of each standard table (both Measurement and Source) linking directly to the parameter's isolated curve view (`tab=interpolation&spec_id={id}`).
  - Fixed Chart.js animation race condition (`TypeError: Cannot read properties of null (reading 'save')` in `_drawDataset`) by disabling animation, adding canvas DOM visibility guards before chart construction, and cleanly stopping pending animations before destroying chart instances.
  - Encapsulated `@once @push('scripts')` containing `chart.umd.min.js` and `interpolationViewer()` inside the component, eliminating redundant script declarations.
  - Reduced `show.blade.php` by over 500 lines while supporting optional `:tab-condition` for both tabbed views and standalone curve pages.
  - Updated `CalibrationCertificateWorkflowTest` asserting curve component rendering and direct curve navigation links.
- **Multi-Standard Certificate Creation & Editing Architecture (`resources/views/metrology/certificates/create.blade.php`, `resources/views/metrology/certificates/edit.blade.php`, `app/Http/Controllers/Metrology/CalibrationCertificateController.php`)**:
  - Restructured both `create` and `edit` views to natively support multi-standard calibrators (e.g. ADT221A/ADT223A) with dynamic capability tabs partitioned into Measurement Standards (Emerald) and Source Standards (Amber).
  - Hydrated client-side equipment specifications via `$equipmentsData` in `CalibrationCertificateController`, binding equipment selection directly to active standards, engineering units, nominal ranges, and accuracies.
  - Implemented standard-specific actions including single point addition, batch 5-point creation (`+ 5 Points`), point clearing, and an "All Standards Overview" table with per-row specification dropdowns.
  - Added feature test coverage in `tests/Feature/Metrology/CalibrationCertificateWorkflowTest.php` and verified all 34 tests pass cleanly.
- **Physical Standards & Grandeur Discipline Visual Identity System (`App\Enums\GrandeurDiscipline`, `<x-grandeur-icon>`, `<x-grandeur-badge>`)**:
  - Implemented `GrandeurDiscipline` PHP Enum with auto-detection for physical standards (Temperature, Pressure, Current, Voltage, Resistance, Frequency, Pulse, Dimensional, Mass, Flow, Generic) mapping tailored icons, colors (Rose, Sky, Amber, Indigo, Emerald, Fuchsia, Purple, Cyan, Slate, Teal), and Chart.js hex values.
  - Built reusable `<x-grandeur-icon>` (with self-contained crisp SVG vectors and FA fallback) and `<x-grandeur-badge>` components.
  - Integrated across `units.blade.php`, `equipment/show.blade.php`, `certificates/show.blade.php`, `create.blade.php`, `edit.blade.php`, and `<x-curve>`.
  - Added Font Awesome stylesheet to `app-ltr.blade.php` and `app-rtl.blade.php` and registered trilingual translations.
  - Added automated tests in `tests/Feature/Metrology/GrandeurFeatureTest.php` (8/8 passed).
- **KPI Metrics & Aggregations (`App\Services\EquipmentService`)**:
  - Added discrete counter metrics for `has_certificate`, `work_tools`, `vehicles`, and `inactive` status.
- **UI & KPI Grid Realignment (`resources/views/metrology/equipment.blade.php`)**:
  - Restructured top statistics grid into a unified single-row layout (`grid-cols-4`) featuring 4 numbered pillars (`1`, `2`, `3`, `4`) with interactive quick-filtering and visual active states.
- **Trilingual Localization (`lang/en.json`, `lang/ar.json`, `lang/fr.json`)**:
  - Registered `Has Certificate` ("يمتلك شهادة", "Possède un certificat") and aligned `Vehicles` Arabic label to "المراكب".
  - Registered `Archived Certificate` ("شهادة مؤرشفة", "Certificat archivé") and updated certificate KPI card subtitle in `resources/views/metrology/certificates/index.blade.php`.
- **Testing (`tests/Feature/Metrology/EquipmentFeatureTest.php`)**:
  - Added feature test asserting priority order (Certified -> Work Tools -> Vehicles -> Inactive) and KPI card renders.
- **Equipment-Sourced Calibration Standards & Units Grouping (`resources/views/metrology/certificates/show.blade.php`, `App\Http\Controllers\Metrology\CalibrationCertificateController`)**:
  - Re-anchored the Calibration Points tab to strictly derive all standards, ranges, and engineering units from the device (`$certificate->equipment->specifications`).
  - Grouped calibration points into distinct dedicated `<x-table>` cards for each equipment specification, partitioned into Measurement Standards (`GrandeurType::Measurement`, Emerald theme) and Source Standards (`GrandeurType::Source`, Amber theme).
  - Displayed equipment nominal ranges (`range_min → range_max`), accuracy (`±accuracy_value`), and engineering unit badges in the toolbar of each standard, and explicitly appended the unit symbol to all numerical values (Nominal, Correction, Uncertainty, Confidence Limits).
  - Added filter bar to toggle between All Standards, Measurement Standards, and Source Standards.
  - Added trilingual localization keys and updated feature test `test_show_certificate_displays_isolated_measurement_and_source_calibration_tables`.
### Fixed
- **Resilient Public Storage & Profile Photo Delivery for cPanel / Shared Hosting (`config/filesystems.php`, `StorageFileController`, `User`, `Employee`, `routes/web.php`)**:
  - Disabled `'serve' => false` on the `local` private disk in `config/filesystems.php`, eliminating Laravel's internal route collision where unsigned public asset requests to `/storage/{path}` were rejected with 403/404.
  - Built `StorageFileController` (`GET /storage/{path}`) providing a transparent fallback when the physical symlink (`public_html/storage`) is missing, broken, or blocked by host security policies on cPanel.
  - Updated `User` and `Employee` `getProfilePhotoUrlAttribute()` accessors to utilize `asset('storage/...')` instead of relying on `env('APP_URL')`, ensuring dynamic resolution of host, port, and HTTPS protocol.
  - Exempted `storage/*` in `EnsureSuperAdminExists` middleware.
- **PHP Execution Time Extension for AI Multi-Page Extraction (`CalibrationCertificateExtractionController`, `ProcessCertificateExtractionJob`, `AiPdfExtractionService`)**:
  - Explicitly configured `set_time_limit(300)` and `ini_set('max_execution_time', '300')` across the controller upload action, queue job, and AI extraction service.
  - Eliminated `Fatal error: Maximum execution time of 30 seconds exceeded` during multi-page document processing.
- **AI PDF Exhaustive Multi-Page Metrology Extraction (`app/Services/AiPdfExtractionService.php`)**:
  - Enforced strict multi-page completeness instructions in `SYSTEM_PROMPT` ensuring exhaustive parsing across all pages (tested and verified on multi-page calibrators like ADT 223A with 5 pages and 88 points).
  - Explicitly prohibited row sampling or table omission (capturing all 16 points for Temperature °C on page 4 and all 16 points for Resistance Ω on page 5).
  - Set `maxOutputTokens => 16384` and `thinkingBudget => 0` in Gemini `generationConfig` to ensure complete, non-truncated JSON payloads and fast execution (~25s).
- **AI Extraction Button Alpine Binding (`resources/views/components/metrology/ai-extractor.blade.php`)**:
  - Escaped the `disabled` Alpine binding on the Blade component so `aiState.loading` is not evaluated as a PHP constant during certificate creation.
- **Reliable AI Extraction Execution (`app/Http/Controllers/Metrology/CalibrationCertificateExtractionController.php`, `config/services.php`)**:
  - Routed certificate extraction jobs through Laravel's `database` connection so the HTTP upload is not blocked by Gemini's response time or the 30-second PHP limit.
  - Added a 25-second total deadline and 3-second connection timeout per Gemini attempt, preventing multi-key/model rotation from exceeding the HTTP execution limit.
  - Persisted and displayed only the active Gemini key number and model (`Key #N`), never the secret API key value.
- **Calibration Points Specification Remapping & Dual-Capability Isolation (`database/migrations/2026_09_24_093000_remap_calibration_points_specifications.php`)**:
  - Resolved root cause where dual-mode process calibrators (`ADT221A` and `ADT223A`) had all points (both Measurement and Source) mapped to Measurement specifications, causing duplicate points in the Measurement table and leaving Source tables empty.
  - Correctly remapped historical points across 13 certificates (Certs 169, 170, 171, 172, 173, 174, 175, 177, 178, 179, 181, 219, 221) to their respective Measurement (first half) and Source (second half) specifications for Temperature, Current, Voltage, and Resistance.
  - Reassigned misplaced 0-nominal points in pressure gauges back to Pressure specifications (`range_min = 0`).
  - Upgraded `CalibrationCertificateService::syncPoints()` with two-tier exact range vs tolerance matching to prevent range overlaps from misclassifying points.
  - Preserved `equipment_specification_id` in `resources/views/metrology/certificates/edit.blade.php` to prevent specification loss on edit.
- **Certificate Download Filename Sanitization (`App\Http\Controllers\Metrology\CalibrationCertificateController`)**:
  - Sanitized certificate reference strings by converting `/` and `\` into hyphens and filtering illegal characters, preventing Symfony `HeaderUtils` `InvalidArgumentException` on download.
  - Added regression test `test_download_certificate_with_slashes_in_reference_sanitizes_filename` in `tests/Feature/Metrology/CalibrationCertificateWorkflowTest.php`.

## [1.0.54] — 2026-09-23 — Equipment-Sourced Standards Architecture & GrandeurType Color/Theme Harmonization
### Changed
- **Authoritative Source Realignment (`App\Services\InterpolationService`)**:
  - Re-anchored `getCertificateSpecifications()` to treat `$certificate->equipment->specifications` as the sole authoritative source of truth for physical standards, measuring ranges, and engineering units.
  - Implemented graceful fallback defaulting `span_min` and `span_max` to equipment nominal limits (`range_min`, `range_max`) when certificate points are missing or below threshold.
- **GrandeurType Semantic Distinction & Historical Matching**:
  - Enforced strict physical separation between Measurement (`GrandeurType::Measurement` / Sensor / IN) and Source (`GrandeurType::Source` / Generation / OUT) across domain services and multi-certificate comparison datasets.
  - In `generateMultiCertificateComparison()`, datasets strictly filter by `grandeur_type` and append `[IN]` / `[OUT]` tags, ensuring Temperature Sensor is never mixed with Temperature Source for the same equipment.
  - Restricted historical comparison chart dates strictly to certificates containing active calibration data for the selected standard.
- **View Layer Harmonization (`resources/views/metrology/certificates/show.blade.php`)**:
  - Sub-tab selector pills styled with distinctive Emerald borders/badges (`Measurement / In`, `fa-sign-in-alt`) and Amber borders/badges (`Source / Out`, `fa-bolt`).
  - Dynamic Chart.js palette in `renderSingleChart()` rendering Emerald (`#059669`) for Measurement and Amber (`#d97706`) for Source with matching shaded uncertainty envelopes.
- **Historical Points & Specification Auto-Mapping Fix (`App\Services\InterpolationService`, `App\Services\CalibrationCertificateService`)**:
  - Resolved root cause where 1,513 points across 51 certificates lacked `equipment_specification_id`, which caused `getCleanPoints()` to filter them out and fall back to equipment nominal bounds.
  - Backfilled `equipment_specification_id` across all historical points based on equipment specifications and nominal range compatibility.
  - Enhanced `getCleanPoints()` and `syncPoints()` with intelligent automatic range-matching fallbacks when points are unlinked.
- **Testing & Quality Assurance**:
  - Added comprehensive feature test `test_equipment_specifications_as_sole_source_distinguishing_measurement_from_source_with_same_grandeur` in `tests/Feature/InterpolationServiceTest.php` (10 passing tests, 95 assertions).
  - Synchronized new keys (`Measurement / In`, `Source / Out`) across `lang/en.json`, `lang/ar.json`, and `lang/fr.json`.

---

## [1.0.53] — 2026-09-23 — Multi-Parameter Standard Isolation & Engineering Units System for Metrology Curves
### Added
- **Database Schema Evolution (`database/migrations/2026_09_23_123000_add_equipment_specification_to_calibration_interpolations_table.php`)**: Added `equipment_specification_id` nullable foreign key to `equipment_specifications` with compound index `(calibration_certificate_id, equipment_specification_id, point_index)`.
- **Model Layer (`App\Models\CalibrationInterpolation`)**: Added `equipment_specification_id` to `$fillable`, `$casts`, Spatie activity logs, and added `equipmentSpecification(): BelongsTo` relationship.
- **Domain Service Isolation (`App\Services\InterpolationService`)**:
  - `getCertificateSpecifications()`: Detects all distinct specifications (`EquipmentSpecification`) linked to certificate points.
  - `generateFivePointGrid()` & `saveFivePointGrid()`: Accepts `$equipmentSpecificationId` to isolate 5-point grid generation and database persistence per parameter/standard with unit symbols (`bar`, `mA`, `°C`, etc.) and grandeur names.
  - `generateMultiCertificateComparison()`: Enforces strict physical parameter matching across historical certificates, isolating drift tracking per standard and appending units to series.
- **Certificate View (`resources/views/metrology/certificates/show.blade.php`)**:
  - Multi-parameter standard selection pills allowing switching between calibrated parameters.
  - Parameter badges and engineering unit symbols displayed in headers, form setpoint labels, and Platel uncertainty table columns.
  - Chart.js single and historical comparison axes titled with active engineering units ($X\text{ [unit]}$, $Y\text{ [unit]}$).
- **Automated Tests (`tests/Feature/InterpolationServiceTest.php`)**: Added feature tests validating multi-specification grid isolation, independent database persistence (10 points total for 2 specs), and controller query scoping.
- **Trilingual Localization**: Synchronized new localization keys (`Select Standard / Parameter`, `Standard Parameter`, etc.) across `lang/en.json`, `lang/ar.json`, and `lang/fr.json`.

---

## [1.0.52] — 2026-09-23 — Florian Platel 5-Point Linear Interpolation & Uncertainty Curve System
### Added
- **Database Table (`calibration_interpolations`)**: Dedicated metrological table storing standard 5-point interpolated grid with decomposed experimental and modeling uncertainties.
- **Model (`App\Models\CalibrationInterpolation`)**: Eloquent model with confidence limit accessors, certificate relation, and Spatie activity logging.
- **Domain Service (`App\Services\InterpolationService`)**: Implemented Florian Platel linear interpolation model ($u_c = \sqrt{u_{exp}^2 + u_{mod}^2}$, $U = 2u_c$, modeling error $u_{mod} = \frac{P^2 |a_2|}{4\sqrt{3}}$), ignoring points outside $[min, max]$, generating 5-point grids, and building multi-certificate historical evolution datasets.
- **Certificate View (`resources/views/metrology/certificates/show.blade.php`)**: Added dedicated `Interpolation & 5-Point Curves` tab with 5-point configuration form, decomposition `<x-table>`, Single Certificate Curve with $\pm U$ shaded envelope, and Multi-Certificate Historical Comparison line chart.
- **Route & Controller Endpoint**: Added `POST /metrology/calibration-certificates/{certificate}/interpolation-grid` (`CalibrationCertificateController@updateInterpolationGrid`).
- **Feature Tests (`tests/Feature/InterpolationServiceTest.php`)**: Automated test coverage (7 tests, 40 assertions) validating Platel decomposition, bound filtering, table persistence, and controller endpoints.
- **Trilingual Localization**: Synchronized 44 new keys across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` with 100% English master keys.

---

## [1.0.51] — 2026-09-23 — Calibration Curves Subsystem Total Purge
### Removed
- **`CalibrationChartService` (`app/Services/CalibrationChartService.php`)**: Fully deleted service class.
- **`<x-metrology-curves>` (`resources/views/components/metrology-curves.blade.php`)**: Fully deleted component.
- **Certificate Curves Tab (`resources/views/metrology/certificates/show.blade.php`)**: Removed curves tab button, panel, and set default `activeTab` to `'points'`.
- **`CalibrationCertificateController`**: Removed `CalibrationChartService` import, constructor injection, and `$chartData` view passing.
- **Test File (`tests/Feature/Metrology/CalibrationChartFeatureTest.php`)**: Deleted obsolete test suite.
- **Linear Interpolation Architecture Guide (`docs/calibration_curves_and_invocation_guide.md`)**: Re-architected guide into the authoritative specification for `InterpolationService` based on the Florian Platel uncertainty model from `docs/linear_interpolation_uncertainty_logic.md`.

---

## [1.0.50] — 2026-09-23 — System Prompt & Rule Harmonization for Token Economy

### Changed
- **`AGENTS.md` & `.antigravityrules` & `CLAUDE.md`**:
  - Enshrined the **Strict Token-Economy Response Directive**: prohibited echoing full document contents or large text dumps of `docs/` files in chat messages, mandating concise 3-5 line diff summaries instead.
  - Mandated **Targeted Line Slicing**: required using `grep_search` and slice notation (`StartLine`/`EndLine`) when inspecting documentation rather than bulk-reading entire files.
  - Synchronized `.antigravityrules` and `CLAUDE.md` to remove obsolete blanket 3-file pre-flight read requirements in favor of Smart Pre-Flight and Proportional Post-Flight.
  - Aligned legacy migration gatekeeper rules to prevent pipeline blocking on obvious 1-to-1 standard migrations.
- **`docs/project_state.md`**:
  - Bumped baseline version to `v1.0.50`.

### Added
- **`.ai/rules/documentation.md`**: Dedicated token-efficient documentation rule file covering Smart Pre-Flight, Proportional Post-Flight, and Token-Economy response standards for `docs/**`.
- **`.ai/rules/index.md`**: Registered path mapping for `docs/**` pointing to `.ai/rules/documentation.md`.

---

## [1.0.49] — 2026-09-23 — Documentation Token Optimization & Streamlining Overhaul

### Changed
- **`AGENTS.md` (Living Memory & Workflow Protocol)**:
  - Replaced the token-draining blanket instruction (reading 250 KB across 3 documents on every turn) with **Smart Pre-Flight (Targeted Context Reading)**: read `project_state.md` for active system topology, and consult specific ADRs or recent changelog entries on-demand based on task scope.
  - Instituted **Proportional Post-Flight Protocol**: always update `changelog.md`; only update `project_state.md` when schema/models/routes/commands change; restrict ADR entries to genuine architectural decisions, stopping the proliferation of micro-ADRs for visual/bug fixes.
- **`docs/project_state.md`**:
  - Streamlined Section 8 ("Standing Architectural Directives & Conventions") by replacing 160 lines of duplicated rules with a concise 30-line architectural pointer referencing `AGENTS.md`.
  - Reduced file size from 60 KB to 36 KB (~40% reduction, saving thousands of tokens per read).
  - Bumped active version to `v1.0.49`.
- **`docs/ARCHITECTURE_LOG.md`**:
  - Consolidated visual micro-ADRs into unified thematic records:
    - `[ADR-016..020]`: Business Modules UI Harmonization, Terminology & Metric Grid Standardization.
    - `[ADR-022..030]`: Industrial Green Design System Harmonization for Tool Icons & Navigation.
  - Added `[ADR-053]` documenting the documentation token optimization overhaul.
  - Reduced file size from 82 KB to 63 KB.
- **`docs/rules/legacy_migration_directive.md`**:
  - Made the Gatekeeper naming convention comparison table conditional on ambiguous, irregular, or non-standard legacy identifiers, preventing pipeline blocks on obvious standard migrations.
- **`docs/changelog.md`**:
  - Archived historical Core Kernel releases (`v1.0.0` through `v1.0.35`) into `docs/archive/core_kernel_archive.md`, reducing active changelog size from 90 KB to 29 KB (~68% reduction).

### Added
- **`docs/archive/core_kernel_archive.md`**: Dedicated archival record for historical releases `v1.0.0` through `v1.0.35`.

---

## [1.0.48] — 2026-09-23 — Documentation Harmonization & Architectural Directive Alignment

### Changed
- **`docs/project_state.md`**:
  - Harmonized active Database Engine identifier to `gmtmdz_erp` to match `.env` and migration specs.
  - Standardized trilingual dictionary key counts across the overview and standing rules to active parity baseline (1,237 keys each).
  - Updated Employees module cross-references from legacy numbers (`ADR-021..024`) to current canonical records (`ADR-031`, `ADR-034`).
  - Added 6 missing active console commands (`employees:import-legacy`, `equipment:import-legacy`, `customers:import-legacy`, `sites:import-legacy`, `warranties:import-legacy`, `metrology:check-expiring-certificates`) to the architectural components catalog.
  - Modernized `AccountStatus` enum rule specification to declare the unified `badgeVariant(): string` contract.
- **`docs/employees_migration_spec.md`**:
  - Updated system version to current baseline (`v1.0.48`).
  - Re-mapped historical ADR references (`ADR-021`, `ADR-023`, `ADR-024`) to current canonical architecture records (`ADR-031`, `ADR-034`).
- **`docs/rules/legacy_migration_directive.md`**:
  - Integrated dedicated Repositories & Interfaces (`app/Repositories/`, `app/Interfaces/`) into Clean Architecture Standards and the 10-step Sequential Execution Order.
- **`docs/ARCHITECTURE_LOG.md`**:
  - Updated `[ADR-007]` status to `Accepted (Partially Superseded by ADR-010 for static catalog discovery)` and appended an explicit note clarifying the deprecation of dynamic schema introspection in favor of the static code-first registry.

---

## [1.0.47] — 2026-09-23 — Calibration Curves Feature Removal (Metrology Domain)

### Removed
- **Calibration Curves Tab (`metrology.equipment.show`)**:
  - Removed the "Calibration Curves" tab button from the interactive tab navigation in `resources/views/metrology/equipment/show.blade.php`.
  - Removed the tab content panel that rendered `<x-metrology-curves :equipment="$equipment" :chartData="$curvesData" />`.
- **`CalibrationChartService` Dependency**:
  - Removed `CalibrationChartService` import and constructor injection from `MetrologyController`.
  - Removed `$curvesData` computation call (`$this->calibrationChartService->getEquipmentCurves($equipment)`) from `showEquipment()`.
  - Cleaned up `compact()` call to only pass `equipment`.

---

## [1.0.46] — 2026-09-22 — Quantities & Units Service Modernization (Metrology Domain)


### Added
- **Quantities & Units Clean Architecture (`metrology.units`)**:
  - Implemented `GrandeurRepositoryInterface` and `GrandeurRepository` in `app/Repositories/` providing eager-loaded specifications counts and dynamic filtering.
  - Implemented `GrandeurService` in `app/Services/` providing atomic transactions, KPI counter calculations, and strict data integrity guards preventing deletion of any physical quantity currently linked to equipment or instrument specifications.
  - Registered `GrandeurRepositoryInterface` in `RepositoryServiceProvider`.
- **Form Requests & HTTP Layer**:
  - Created `StoreGrandeurRequest` and `UpdateGrandeurRequest` in `app/Http/Requests/Metrology/` with enum validation (`GrandeurType`) and RBAC authorization (`create quantities units`, `edit quantities units`).
  - Added `storeUnit`, `updateUnit`, and `destroyUnit` to `MetrologyController`.
  - Registered RESTful CRUD routes in `routes/web.php` under `metrology.units.*`.
- **Modernized Blade Explorer (`resources/views/metrology/units.blade.php`)**:
  - 4-Card KPI Counter Grid (Total Quantities, Measurement Sensors / In, Source Generators / Out, Configured Specs).
  - Standardized `<x-global-filter>` with dynamic search and `GrandeurType` filter.
  - Data table `<x-table>` with semantic badges (`neutral` for symbol, `GrandeurType::badgeVariant()`), specification links counter, and unified `<x-table.actions>`, `<x-table.action-edit>`, and `<x-table.action-delete>`.
  - Alpine.js modal suite (Create, Edit, and Delete with integrity alert if linked).
- **System-Wide Action Buttons Standardization**:
  - Replaced legacy and ad-hoc `<button>` elements in table cells with standardized GMTM Blade components (`<x-table.action-edit>`, `<x-table.action-delete>`, `<x-table.action-view>`), guaranteeing visual, behavioral, and accessibility consistency across all application tables.
- **Trilingual Dictionary Synchronization**:
  - Synchronized 24 new translation keys with 100% parity across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` (1122 keys each, 0 non-English keys).
- **Automated Feature Tests**:
  - Created `tests/Feature/Metrology/GrandeurFeatureTest.php` covering index view, KPI counters, create, update, delete guard against equipment usage, unlinked deletion, and unauthorized access (6 tests, 22 assertions, 100% passing).

---

## [1.0.45] — 2026-09-22 — Equipment Service Modernization & Legacy Migration (Metrology Domain)

### Added
- **Metrology Equipment Architecture & Domain Schema**:
  - Implemented 3 normalized migrations conforming to strict enterprise standards:
    - `2026_09_22_100000_create_grandeurs_table.php` (`grandeurs`: physical measurement quantities and symbols).
    - `2026_09_22_110000_create_equipment_table.php` (`equipment`: enterprise inventory tracking, internal code, serial number, category, package lot, status, calibration toggle, WebP photo, certificate path, CAS SHA-256 hash, and soft deletes).
    - `2026_09_22_120000_create_equipment_specifications_table.php` (`equipment_specifications`: normalized min/max ranges and precision tolerances linked to physical quantities).
- **Domain Enums (`app/Enums/`)**:
  - `EquipmentCategory`: `measuring_instrument`, `work_tool`, `vehicle`, `other` with `badgeVariant()` and `requiresCalibrationByDefault()`.
  - `EquipmentStatus`: `active`, `maintenance`, `deployed`, `retired`, `inactive` with semantic status badges.
  - `EquipmentPackage`: `lot_01`, `lot_02`, `vehicle_lot`, `none` for logistics grouping.
  - `GrandeurType`: `measurement`, `source` with semantic badges.
  - `AccuracyType`: `%`, `abs`.
- **Domain Service & Repository Pattern**:
  - `EquipmentRepositoryInterface` & `EquipmentRepository` implementing eager loading (`specifications.grandeur`) and `FilterableTrait`.
  - `EquipmentService` handling atomic DB transactions, CAS WebP image optimization and safe deletion, certificate PDF storage, specification range synchronization, and KPI counter statistics.
  - Registered `EquipmentRepositoryInterface` in `RepositoryServiceProvider`.
- **Form Requests & Validation**:
  - `StoreEquipmentRequest` & `UpdateEquipmentRequest` with enum validation, unique serial numbers ignoring soft deletes, file mimes validation (images up to 5MB, PDF up to 10MB), and dynamic specification rules.
- **Observers & CAS Deduplication**:
  - `EquipmentObserver` monitoring image updates to calculate SHA-256 CAS hash and triggering `MediaOptimizationService::safeDelete()` to prevent disk leakage.
- **Trilingual Localization Parity (1098 Keys)**:
  - 87 new English master translation keys introduced across forms, table headers, activity logs, and modals, synchronized with 100% key parity across `lang/en.json`, `lang/ar.json`, and `lang/fr.json`.
- **Enterprise Blade Views & Components**:
  - `resources/views/metrology/equipment.blade.php`: Modernized explorer with 4-card KPI counter grid, `<x-global-filter>` category/package/status filters, `<x-table>`, Alpine.js Create/Edit/Delete modals, and dynamic physical specification range inputs.
  - `resources/views/metrology/equipment/show.blade.php`: Tabbed details inspection page (Overview & Specs, Physical Capabilities, Certificate PDF viewer, and Spatie Forensic Audit Trail).
- **Automated Feature Testing**:
  - Created `tests/Feature/Metrology/EquipmentFeatureTest.php` covering index explorer, KPI counters, detail view, store with specs, update with specs sync, and soft delete (5 tests, 21 assertions, 100% passing).
- **Legacy Migration Command & Seeder**:
  - `ImportLegacyEquipmentCommand` (`php artisan equipment:import-legacy`) & `LegacyEquipmentSeeder`:
    - Successfully imported all 29 historical equipment records from `app.gmtm-dz.com` with primary keys (1–29) strictly preserved.
    - Converted and optimized all legacy images to modern `.webp` via `MediaOptimizationService`.
    - Synced 39 physical specifications across calibrated measuring instruments.

---

## [1.0.44] — 2026-09-22 — Brand Identity Modernization (ENGI-GMTM) & Unified Favicon Architecture

### Changed
- **Application Rebranding (`ENGI-MATE` -> `ENGI-GMTM`)**:
  - Renamed core application moniker from `ENGI-MATE` to `ENGI-GMTM` across project documentation, `README.md`, `SystemTableController`, and trilingual dictionaries.
  - Updated initialization status flash message to: `__('System initialized successfully! Welcome to ENGI-GMTM.')`.
  - Synchronized translation key with 100% parity across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` with zero non-English keys.

### Added
- **Unified Favicon Architecture (`public/images/LogoP.jpg`)**:
  - Integrated GMTM Metrology & Instrumentation favicon `<link rel="icon" type="image/x-icon" href="{{ asset('images/LogoP.jpg') }}">` across all core layout views:
    - `resources/views/layouts/app-ltr.blade.php`
    - `resources/views/layouts/app-rtl.blade.php`
    - `resources/views/layouts/guest-ltr.blade.php`
    - `resources/views/layouts/guest-rtl.blade.php`
    - `resources/views/welcome.blade.php`
  - Replaced empty 0-byte `public/favicon.ico` with authentic GMTM brand asset to satisfy direct browser icon lookups.
  - Mirrored asset to `public/assets/image/LogoP.jpg` for legacy path backward compatibility.

---

## [1.0.43] — 2026-09-21 — Legacy Code Modernization & Migration Protocol (SARL GMTM Core Kernel)

### Added
- **Permanent Architectural Directive Codification (`AGENTS.md`, `.antigravityrules`, `CLAUDE.md`, `.ai/rules/legacy-migration.md`, `.ai/rules/index.md`, `docs/rules/legacy_migration_directive.md`)**:
  - Enshrined the **Legacy Code Modernization & Migration Protocol** (Role: *Strict Enterprise Architect*):
    1. **Zero Spaghetti Mirroring:** Prohibits replicating legacy architecture, monolithic styles, or inline queries. Only business logic mining is permitted.
    2. **Laravel Eloquent Standards Enforcement:** Models singular PascalCase, Tables plural snake_case, Pivot tables singular alphabetical snake_case, Foreign keys singular_id.
    3. **Gatekeeper Step (Naming Convention Fixes Table):** Mandatory comparative table explaining all legacy vs. standard names before writing any executable code.
    4. **Clean Architecture Isolation:** Ultra-skinny controllers, dedicated domain services, dedicated FormRequests, and backed PHP Enums with `badgeVariant(): string`.
    5. **UI Component Architecture:** Exclusively GMTM Blade components (`<x-table>`, `<x-badge>`, `<x-*-button>`, `<x-global-filter>`, `<x-alert>`), zero inline styles, and 100% English master translation keys in code synchronized across AR, EN, and FR.
    6. **Foreign Key & Data Integrity:** Strict mapping preventing orphan rows, preserving soft deletes, timestamps, and audit trails.
    7. **Sequential Execution Order:** 9-step structured output sequence.
    8. **Mandatory Confirmation Gate:** Response trigger *"مستعد لتطبيق معايير التسمية القياسية"*.

---

## [1.0.42] — 2026-09-20 — Mandatory Comprehensive Ecosystem & Dependency Synchronization Architecture

### Added
- **Permanent AI Mandate Codification (`AGENTS.md`, `.antigravityrules`, `CLAUDE.md`, `.ai/rules/services.md`, `.ai/rules/views.md`, `.ai/rules/index.md`)**:
  - Enshrined the **Mandatory Comprehensive Ecosystem & Dependency Synchronization Protocol**:
    Whenever the AI assistant creates, extends, or modifies any new service (`app/Services/`, Actions, Repositories, Jobs) or any new page/view (`resources/views/`, Controllers, Routes), the task is strictly prohibited from completion until all directly connected ecosystem layers are systematically verified and synchronized:
    1. Trilingual Localization Synchronization: 100% of user-facing strings, labels, errors, and activity descriptions extracted into English master keys and synchronized simultaneously across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` (1-to-1 parity, 0 non-English keys).
    2. Alerts, Flash Messages & UI Feedback: Standardized controller flash keys (`with('success')`, `with('error')`, `with('warning')`, `with('info')`) paired with corresponding `<x-alert>` components in target views.
    3. Audit Trail Forensics & Activity Regex Engines: Logging mutations in `activity_log` (`causedBy`, `performedOn`, `properties`) and registering new activity description patterns in `SystemTableService::translateActivityDescription()` with regex matchers.
    4. Notifications & System Alerts: Structured payloads (`SystemActivityAlert`) scoped by the 3 functional tiers (Management, Engineering, Technicians).
    5. Table & Query State Persistence: Standardized `<x-global-filter>`, 3 functional `<optgroup>` tiers, and query string retention (`->withQueryString()`, `$request->query()`).
    6. Design System & Badges: Standardized `<x-table>`, polymorphic action buttons, `<x-badge>` functional variants (`primary`, `info`, `neutral`), and zero inline styles.
    7. RBAC & Navigation Hierarchy: Permission registration in `config/permissions.php`, `@can` view guards, and sidebar/navigation tab integration.
    8. Automated Verification & Living Memory: Feature test coverage, Pint code formatting, and post-flight living memory updates.

---

## [1.0.41] — 2026-09-20 — Forensic Audit Trail Trilingual Parity & Activity Description Localization Engine

### Added
- **Audit Trail UI Localization (`resources/views/system/activity-log.blade.php`)**:
  - Localized event filter selector options via `__('Created')`, `__('Updated')`, `__('Deleted')`.
  - Localized table column header `<x-table.th>{{ __('ID') }}</x-table.th>`.
  - Localized event badge chips dynamically via `{{ __($act->event ?? 'event') }}` rendering proper localized badges in Arabic (`إنشاء`, `تعديل`, `حذف`), French (`Créé`, `Modifié`, `Supprimé`), and English (`Created`, `Updated`, `Deleted`).
  - Localized causer fallback label `{{ __('ID') }}: {{ $act->causer_id }}`.
- **Activity Description Localization Engine (`app/Services/SystemTableService.php`)**:
  - Extended regex matchers in `SystemTableService::translateActivityDescription()` to handle:
    - User status changes: `Changed user status to ':status' for ':name'`.
    - System setting mutations: `Updated system setting ':key'`.
    - Registration status transitions: `Changed registration status to enabled` and `Changed registration status to disabled`.
- **Trilingual Dictionary Parity (`lang/en.json`, `lang/ar.json`, `lang/fr.json`)**:
  - Added 16 missing translation keys across all 3 languages (931 keys per language with 100% 1-to-1 parity and 0 non-English keys):
    - Activity descriptions: `Employee has been created`, `Employee has been updated`, `Employee has been deleted`, `Initialized system and created the initial Super Admin account`, `Changed registration status to enabled`, `Changed registration status to disabled`, `Changed user status to ':status' for ':name'`, `Updated system setting ':key'`.
    - Interface and badge keys: `ID`, `Updated`, `Deleted`, `event`, `active`, `suspended`, `enabled`, `disabled`.
- **Automated Verification (`tests/Feature/SystemTableTest.php`, `tests/Feature/LocalizationTest.php`)**:
  - Expanded `test_system_table_service_translates_activity_descriptions_accurately_across_locales` to 25 assertions covering Employee mutations, User status changes, System Settings, and Registration toggles across Arabic, French, and English.
  - Verified 100% test suite passage (306 tests, 1404 assertions).

---

## [1.0.40] — 2026-09-20 — Forensic Audit Trail Causer Identity & Search Integration

### Added
- **Causer Identity Integration (`resources/views/system/activity-log.blade.php`)**:
  - Enhanced the Causer column in the forensic audit trail table (`activity_log`) under developer pre-approval per Rule 13 (Strict Security Quarantine).
  - Displays user avatar (profile photo URL or brand initials chip) alongside both the User Name (in bold) and Email address (underneath in mono styling) for rich, unambiguous actor tracking.
  - Retains graceful fallback states: `ID: {causer_id}` if causer record was soft/hard purged, and italicized `System` badge when actions originate from system tasks, migrations, or unauthenticated processes.
- **Causer Search in Activity Logs (`app/Services/SystemTableService.php`, `resources/views/system/activity-log.blade.php`)**:
  - Extended `SystemTableService::getActivityLogs()` to support searching by causer user name and email via Eloquent's `whereHasMorph('causer', [User::class], ...)` alongside existing searches across `description`, `log_name`, and `subject_type`.
  - Updated filter search input placeholder to `__('Search description, subject, user...')` with synchronized trilingual entries in `lang/en.json`, `lang/ar.json`, and `lang/fr.json`.
- **Automated Feature Verification (`tests/Feature/SystemTableTest.php`)**:
  - Added `test_activity_logs_explorer_displays_causer_name_and_email_and_supports_causer_search` verifying that both actor name and email are rendered in the Causer cell and that searching by causer name and email properly isolates the relevant audit trail rows.

---

## [1.0.39] — 2026-09-20 — Mandatory Unified Alerts, Notifications, Functional Classification & Trilingual Parity

### Added
- **Strict Guidelines & Architecture Mandates (`AGENTS.md`, `.antigravityrules`, `CLAUDE.md`, `.ai/rules/views.md`, `.ai/rules/notifications.md`)**:
  - Enshrined the **Mandatory Unified Alerts, Notifications, Functional Classification & Trilingual Parity Architecture**:
    1. Single Source of Truth for In-Page Alerts: All in-page alerts, operational notices, and flash feedback MUST strictly use `<x-alert :variant="...">` (`success`, `danger`, `warning`, `info`, `primary`). Standardized session flash keys: `with('success', __('...'))`, `with('error', __('...'))`, `with('warning', __('...'))`, `with('info', __('...'))`.
    2. Unified Notification Architecture: System activity notifications (`SystemActivityAlert`) must adhere to structured payload schema (`title`, `message`, `type`, `causer`, `extra`), with `type` mapping to semantic badge variants.
    3. Mandatory Functional Role Classification: Notifications and alerts must be scoped by the 3 functional tiers: Management & Executive Leadership (`isManagement()`) for administrative/security alerts, Engineering & Specialist Roles (`isEngineer()`) for technical and calibration alerts, Field Operations & Technicians (`isTechnician()`) for logistics/field tasks. Role chips in notifications must use `<x-badge>` categorized into the 3 functional tiers (`primary`, `info`, `neutral`).
    4. Mandatory Trilingual Localization Across 3 Languages: 100% English master keys in `__('...')` synchronized across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` with 1-to-1 parity.
- **Automated Feature Verification (`tests/Feature/DatabaseNotificationTest.php`)**:
  - Added `test_system_notification_titles_have_exact_trilingual_parity` verifying all system activity notification titles and actions exist in Arabic, English, and French dictionaries with non-empty translations.
- **Rule Infrastructure (`.ai/rules/notifications.md`, `.ai/rules/index.md`)**:
  - Created dedicated rule file `.ai/rules/notifications.md` covering `app/Notifications/**` and registered it in `.ai/rules/index.md`.

### Changed
- **Alert Component Token Harmonization (`resources/views/components/alert.blade.php`)**:
  - Corrected `info` variant text and hover classes to use dedicated indigo tokens (`text-indigo-600 dark:text-indigo-400`, `hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300`).

---

## [1.0.38] — 2026-09-20 — 100% English Master Translation Keys Mandate & Non-English Key Purge

### Added
- **Strict Guidelines & Architecture Mandates (`AGENTS.md`, `.antigravityrules`, `CLAUDE.md`, `.ai/rules/views.md`)**:
  - Enshrined the **Absolute Mandate for 100% English Master Translation Keys**:
    1. Every translation key in code (`__('...')`, `@lang('...')`, validation, notifications, controllers) MUST strictly be authored in English.
    2. Every dictionary key (JSON object property name) across `lang/en.json`, `lang/ar.json`, and `lang/fr.json` MUST strictly and exclusively be in English.
    3. Strict Zero-Tolerance Prohibition against using Arabic, French, or any non-English text as dictionary keys. Arabic and French text may ONLY exist as translated values.
- **Automated Verification Test (`tests/Feature/LocalizationTest.php`)**:
  - Added `test_all_translation_keys_are_strictly_in_english` verifying that 0 translation keys contain Arabic characters across `lang/en.json`, `lang/ar.json`, and `lang/fr.json`.

### Changed
- **Language Dictionaries Cleanup (`lang/`)**:
  - Purged legacy reverse-translation bridge keys (`"حذف مستخدم"` and `"مستخدم جديد"`) from `lang/en.json`, `lang/ar.json`, and `lang/fr.json`.
  - Canonical English master keys `"New User"` and `"User Deleted"` now exclusively govern these translations.
  - Achieved 100% pure English dictionary keys (914 keys per file with exact 1-to-1 parity).

---

## [1.0.37] — 2026-09-20 — Mandatory Unified Search, Filter & State Persistence Architecture

### Added
- **Strict Guidelines & Architecture Mandates (`AGENTS.md`, `.antigravityrules`, `CLAUDE.md`, `.ai/rules/views.md`)**:
  - Formally codified `Mandatory Unified Search, Filter & State Persistence Architecture`:
    1. Single Source of Truth for Search & Filters: `<x-global-filter>` is the authoritative standard for all table search inputs, status/position filters, and date filters across all tabular pages.
    2. Mandatory Functional Role Grouping in Selectors: In all position filters, dropdowns, and create/edit modal selects, job positions MUST be grouped into the 3 standardized functional tiers via `<optgroup>` (`Management & Executive Leadership`, `Engineering & Specialist Roles`, `Field Operations & Technicians`).
    3. Mandatory Filter & Search State Persistence Across All Lifecycle Events: Active search and filter queries (`search`, `position`, `status`, etc.) MUST be preserved across:
       - Language Switching (`LaravelLocalization::getLocalizedURL($localeCode, null, [], true)`).
       - Pagination Navigation (`->withQueryString()`).
       - Record Creation (Modal form action URLs append `request()->query()` and controller store actions redirect back with `$request->query()`).
       - Record Modification/Editing (Modal form action URLs append `request()->query()` and controller update actions redirect back with `$request->query()`).
       - Record Deletion (Delete modal action URLs append `request()->query()` and controller destroy actions redirect back with `$request->query()`).
- **Automated Feature Test**:
  - `tests/Feature/MasterData/EmployeeFilterPersistenceTest.php`: Comprehensive test suite verifying query string persistence across pagination links, employee creation redirect, employee update redirect, and employee deletion redirect (4/4 passed).
- **Trilingual Dictionary Keys (`lang/en.json`, `lang/ar.json`, `lang/fr.json`)**:
  - Synchronized keys with 100% 1-to-1 parity: `"Management & Executive Leadership"`, `"Engineering & Specialist Roles"`, `"Field Operations & Technicians"` (919 keys each, 0 missing).

### Changed
- **Paginator Query State Persistence (`app/Repositories/`)**:
  - `app/Repositories/EmployeeRepository.php`: Chained `->withQueryString()` to `paginateWithFilter()`.
  - `app/Repositories/BaseRepository.php`: Chained `->withQueryString()` to `paginate()` and `paginateWithFilter()`.
  - `app/Repositories/UserRepository.php`: Chained `->withQueryString()` to `getVerifiedUsers()`.
- **Controller Redirection Query Preservation (`app/Http/Controllers/MasterDataController.php`)**:
  - `storeEmployee`: Redirects back with `$request->query()`.
  - `updateEmployee`: Redirects back with `$request->query()`.
  - `destroyEmployee`: Redirects back with `request()->query()`.
- **Views & Modals (`resources/views/master-data/employees.blade.php`)**:
  - Position filter select inside `<x-global-filter>` categorized into 3 functional `<optgroup>` tiers.
  - Create modal and edit modal position selects grouped into 3 functional `<optgroup>` tiers.
  - Action URLs for create, edit, and delete modals dynamically pass `request()->query()` to preserve active filter state.

---

## [1.0.36] — 2026-09-20 — Project-Wide Color Unification, Semantic Classification & Design Token Harmonization

### Changed
- **PHP Enums Layer (`app/Enums/`)**:
  - `app/Enums/AccountStatus.php`: Implemented `badgeVariant(): string` contract returning `'success'` for `Active` and `'danger'` for `Suspended`. Modernized legacy `badgeClass()` to use alpha-transparency design tokens (`bg-emerald-500/10`, `bg-rose-500/10`).
- **CSS Semantic Classes & Design Tokens (`resources/css/components.css`)**:
  - Replaced legacy opaque dark mode classes (`dark:bg-*-950`) in `.badge-primary`, `.badge-danger`, `.badge-warning`, `.badge-success`, and `.badge-neutral` with unified alpha-transparency tokens (`bg-*/10`, `border-*/20`, `text-* dark:text-*`) matching `<x-badge>`.
- **Domain Overview Metric Cards (`resources/views/*/index.blade.php`)**:
  - `resources/views/metrology/index.blade.php`: Harmonized metric card sub-labels (`Operational`, `Tracking`, `Certifications`, `Standards`) to `text-brand-700 dark:text-brand-400`.
  - `resources/views/operations/index.blade.php`: Harmonized all 5 metric card sub-labels (`Field Operations`, `Active Agreements`, `Project Files`, `Guarantees Active`, `Classifications`) to `text-brand-700 dark:text-brand-400`.
  - `resources/views/master-data/index.blade.php`: Harmonized all 3 metric card sub-labels (`Accounts`, `Staff`, `Facilities`) and empty state icon to `text-brand-700 dark:text-brand-400` and `bg-brand-600/10`.
  - `resources/views/analytics/index.blade.php`: Harmonized all 4 metric card sub-labels (`Cost Tracking`, `Projections`, `Key Metrics`, `Documents Ready`) and empty state icon to `text-brand-700 dark:text-brand-400` and `bg-brand-600/10`.
- **Workspace Dashboard (`resources/views/workspace.blade.php`)**:
  - Harmonized executive Super-Admin badge to `primary` brand variant (`<x-badge variant="primary" size="md">`).
  - Unified all 4 business portal explorer count badges (Metrology, Operations, Analytics, Master Data) from arbitrary rainbow variants to clean, neutral status tokens (`<x-badge variant="neutral" size="md">`), preventing UI noise and decoupling counters from operational alarm states.
- **System Module Views (`resources/views/system/**`)**:
  - `resources/views/system/users.blade.php`: Harmonized registration open/closed pills to modern alpha tokens (`bg-emerald-500/10`, `bg-rose-500/10`).
  - `resources/views/system/settings.blade.php`: Modernized registration pills and boolean setting values to alpha tokens.
  - `resources/views/system/roles.blade.php`: Harmonized all system privileges badges and permission count chips to alpha tokens (`bg-emerald-500/10`, `bg-indigo-500/10`), and updated row toggles to `bg-indigo-500/10 dark:hover:bg-indigo-500/20`.
- **Table Action Component (`resources/views/components/table/action.blade.php`)**:
  - Modernized theme classes across all action variants (`edit`, `delete`, `primary`, `success`, `view`) to leverage alpha-transparency tokens (`bg-*/10`, `border-*/20`, `hover:bg-*`, `dark:bg-*/20`) ensuring high-contrast rendering across light and dark themes.

### Added
- **Unit Test Suite Coverage**:
  - `tests/Unit/EnumsBadgeContractTest.php`: Created automated unit test verifying `badgeVariant(): string` contract across all Enums (`AccountStatus`, `EmployeePosition`, `EmployeeStatus`) and asserting strict 3-tier functional classification compliance.

---

> [!NOTE]
> Historical release entries from `v1.0.0` through `v1.0.35` have been archived to [Core Kernel Baseline Archive](file:///c:/Project%20HARD/erp.gmtm-dz.com/docs/archive/core_kernel_archive.md) to maintain token efficiency.
> Pre-Core-Kernel historical logs are preserved in [Legacy Changelog](file:///c:/Project%20HARD/erp.gmtm-dz.com/docs/archive/legacy_changelog.md).


## [2026-09-24] AI PDF Extraction - Frontend & QA Completion

### Added
- database/factories/CalibrationCertificateExtractionFactory.php - Factory with states: completed(), ailed(), pplied()
- 	ests/Feature/Metrology/CalibrationCertificateExtractionTest.php - 10 feature tests (10/10 pass): upload dispatch, auth guard, validation, status, preview, mark-applied, unapplied listing, key rotation, job completion, job failure
- AI translation keys (35 keys) added to lang/en.json, lang/ar.json, lang/fr.json

### Fixed
- pp/Services/AiPdfExtractionService.php - Replaced non-existent File::isAbsolutePath() with portable path detection using str_starts_with() + drive letter check
- Fixed BOM encoding issue introduced in lang/ar.json, lang/en.json, lang/fr.json during key injection

### Status
- AI PDF Extraction feature: **100% complete** (Backend + Frontend component + Localization + Tests)
