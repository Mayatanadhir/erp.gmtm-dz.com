# Global Soft Delete Standardization & Database Inventory Audit

**Date:** 2026-10-10
**Scope:** Comprehensive audit of all database tables in accordance with *Standard 6: Global Soft Delete Standardization*.
**Status:** Standardized & Verified

---

## 1. Executive Summary

- **Total Database Tables Audited:** 71 tables.
- **Category A (Previously Standardized):** 7 tables.
- **Category B (Eligible & Standardized in this phase):** 19 tables.
- **Category C (Exempted with Technical/Business Justification):** 45 tables.
- **Total Tables Supporting Soft Deletes:** 26 tables.

---

## 2. Category A: Tables Already Standardized

| Table Name | Eloquent Model | Soft Deletes Trait | Record Count |
| :--- | :--- | :--- | :--- |
| `calibration_certificates` | `CalibrationCertificate` | Implemented | 54 |
| `employees` | `Employee` | Implemented | 7 |
| `equipment` | `Equipment` | Implemented | 29 |
| `instruments` | `Instrument` | Implemented | 86 |
| `mission_deployments` | `MissionDeployment` | Implemented | 423 |
| `mission_orders` | `MissionOrder` | Implemented | 77 |
| `missions` | `Mission` | Implemented | 32 |

---

## 3. Category B: Newly Standardized Eligible Tables

| Table Name | Eloquent Model | Column Added | Business Purpose |
| :--- | :--- | :--- | :--- |
| `attachments` | `Attachment` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `attachment_items` | `AttachmentItem` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `charges` | `Expense` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `chromatograph_verifications` | `ChromatographVerification` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `contracts` | `Contract` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `contract_items` | `ContractItem` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `customers` | `Customer` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `flow_computer_verifications` | `FlowComputerVerification` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `garanties` | `Warranty` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `grandeurs` | `Grandeur` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `income_forecasts` | `IncomeForecast` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `item_types` | `ItemType` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `probe_verifications` | `ProbeVerification` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `prover_verifications` | `ProverVerification` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `prover_verification_runs` | `ProverVerificationRun` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `reports` | `Report` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `sites` | `Site` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `transmitter_verifications` | `TransmitterVerification` | `deleted_at TIMESTAMP NULL` | Core business entity; critical for audit trails, accidental deletion protection, and lifecycle recovery. |
| `calibration_certificate_extractions` | `CalibrationCertificateExtraction` | `deleted_at TIMESTAMP NULL` | AI OCR extraction staging entities; soft-deletable for safety and recovery before final application. |

---

## 4. Category C: Exempted Tables & Justifications

| Table Name | Classification | Technical / Business Justification |
| :--- | :--- | :--- |
| `activity_log` | Immutable Audit Log | Legal and compliance audit trail. Records must remain permanently immutable and tamper-evident. |
| `cache` | Framework Infrastructure | Key-value cache storage managed with TTL expiration. |
| `cache_locks` | Framework Infrastructure | Distributed atomic lock acquisition records. |
| `calibration_interpolations` | Calculation Math Grid | Computed interpolation matrix points tied directly to certificates. |
| `calibration_points` | High-Frequency Point Data | Child measurement records (1500+ rows) tied to parent certificate. Filtered via parent certificate relation and deleted via parent cascade. |
| `calibration_templates` | Static Report Template | Static calibration template layouts. |
| `calibrator_specifications` | Technical Specification | Physical calibration tool operating specifications. |
| `chromatograph_composition_points` | Granular Gas Data Points | Gas chromatography component measurement points tied to parent CPG verification. |
| `chromatograph_physical_properties` | Granular Gas Properties | Physical property calculation points tied to parent CPG verification. |
| `chromatograph_verification_calibrators` | Junction Table | Many-to-many junction between CPG verification and calibrators. |
| `deployments` | Legacy Deployment Staging | Legacy staging table superseded by `mission_deployments`. |
| `equipment_specifications` | Technical Lookup | Equipment technical limits reference metadata. |
| `failed_jobs` | Queue Infrastructure | Failed async job execution logs managed by retry/forget queue commands. |
| `flow_computer_transmitter` | Junction Table | Legacy junction table for flow computer transmitters. |
| `flow_computer_transmitters` | Junction Table | Many-to-many junction between flow computer runs and transmitter instruments. |
| `flow_computer_verification_calibrators` | Junction Table | Many-to-many junction between flow computer runs and calibrators. |
| `flow_computer_verification_points` | Granular Point Data | Calibration run verification points tied to parent flow computer verification. |
| `instrument_specifications` | Technical Lookup | Instrument measurement bounds reference metadata. |
| `item_contracts` | Junction Table | Many-to-many junction table between item types and contracts. |
| `job_batches` | Queue Infrastructure | Batch job progress tracking metadata. |
| `jobs` | Queue Infrastructure | Pending queue execution payloads. |
| `media_assets` | Storage File Registry | Uploaded physical file registry managed via Storage disk cleanup. |
| `media_variants` | Storage Thumbnail Registry | Image derivative/thumbnail cache records. |
| `migrations` | Framework Infrastructure | Tracks applied database migrations. Modifying schema state cannot support soft-deletion. |
| `model_has_permissions` | RBAC Pivot / Junction | Many-to-many junction between users and permissions. |
| `model_has_roles` | RBAC Pivot / Junction | Many-to-many junction between users and roles. |
| `movement_equipment` | Movement History Ledger | Append-only ledger of equipment warehouse check-in and check-out events. |
| `notifications` | Framework Notifications | State managed via `read_at` timestamp. Historical notifications purged via pruning retention rules. |
| `password_reset_tokens` | Authentication Security | Transient security tokens with short expiration lifetimes. |
| `permission_categories` | Security Taxonomy | Static categorization grouping for system permissions. |
| `permissions` | Security RBAC Core | Spatie permission catalog entries. |
| `probe_verification_calibrators` | Junction Table | Many-to-many junction between probe verifications and calibrators. |
| `probe_verification_points` | Granular Point Data | Temperature/pressure probe calibration verification points. |
| `prover_specifications` | Technical Lookup | Meter prover pipe volume and thermal bounds reference metadata. |
| `report_instruments` | Report Snapshot View | Denormalized summary table for fast instrument report rendering. |
| `role_has_permissions` | RBAC Pivot / Junction | Many-to-many junction between roles and permissions. |
| `roles` | Security RBAC Core | Spatie permission system roles. Direct assignment architecture. |
| `services` | Service Catalog | Static service offerings catalog. |
| `sessions` | Framework Infrastructure | Stores temporary HTTP session states. Sessions are expired and purged by Garbage Collection, not soft-deleted. |
| `settings` | System Configuration | Legacy/fallback application key-value configuration. |
| `standard_gauge_specifications` | Technical Lookup | Reference gauge standard specification bounds. |
| `system_settings` | System Configuration | Key-value application settings storage. Managed through dedicated configuration lifecycles. |
| `transmitter_verification_calibrators` | Junction Table | Many-to-many junction between transmitter verifications and calibrators. |
| `transmitter_verification_points` | Granular Point Data | Pressure/differential transmitter calibration verification points. |
| `users` | Security Sovereign Entity | Managed through explicit `AccountStatus` lifecycle states (`active`, `suspended`, `pending`). Sovereign table excluded from accidental mass purge. |

---

## 5. Verification & Acceptance Checklist

- [x] **Audit Completeness:** All 71 tables surveyed and mapped.
- [x] **Schema Standard:** Column defined as `deleted_at TIMESTAMP NULL DEFAULT NULL`.
- [x] **Model Compatibility:** Eloquent `SoftDeletes` trait applied to corresponding models.
- [x] **Data Invariance:** Zero existing rows modified, erased, or falsely marked as deleted.
- [x] **Full Traceability:** All exempted tables formally justified with zero silent exclusions.
