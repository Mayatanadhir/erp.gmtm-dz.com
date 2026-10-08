# Mission Code Details: Master Technical & Architectural Reference

> **Document Type:** Senior Software Architect Technical Reference Specification  
> **Target Module:** `Missions` (Field Calibration & Operational Assignment Management)  
> **Application:** ENGI-MATE ("Your Engineering Work Assistant")  
> **Framework:** Laravel 13.x / PHP 8.4+  
> **Database:** MySQL (`gmtm_app`)  
> **Authoritative Baseline:** Confirmed directly from active codebase and live database schema  
> **Target Audience:** Senior Architects, Core Developers, Technical Leads, and New Engineers  

---

## Precision & Verification Standard

This technical reference strictly differentiates between verification tiers:
- **`[Confirmed]`**: Directly verified from executable source code, routes, controllers, models, or active MySQL database schema tables/columns/keys.
- **`[Inferred]`**: Deduced through logical synthesis of multiple interdependent code files, services, calculations, or relational flows.
- **`[Not Confirmed / Not Present]`**: Explicitly checked in the codebase and confirmed to be non-existent or legacy artifacts. Marked as `Not found in the analyzed code.`

---

## Table of Contents

1. [Executive Overview](#1-executive-overview)
2. [Mission Domain Overview](#2-mission-domain-overview)
3. [Folder Structure & Component Inventory](#3-folder-structure--component-inventory)
4. [Architecture Overview](#4-architecture-overview)
5. [Mission Data Model](#5-mission-data-model)
6. [Database Schema](#6-database-schema)
7. [Logical ERD](#7-logical-erd)
8. [Eloquent Models](#8-eloquent-models)
9. [Eloquent Relationships](#9-eloquent-relationships)
10. [Controllers](#10-controllers)
11. [Form Requests](#11-form-requests)
12. [Services / Actions / Use Cases](#12-services--actions--use-cases)
13. [Repositories](#13-repositories)
14. [Policies & Authorization](#14-policies--authorization)
15. [API Endpoints](#15-api-endpoints)
16. [Request Structures](#16-request-structures)
17. [Response Structures](#17-response-structures)
18. [Filtering Architecture](#18-filtering-architecture)
19. [Pagination Architecture](#19-pagination-architecture)
20. [Frontend Components & Templates](#20-frontend-components--templates)
21. [Mission List Flow](#21-mission-list-flow)
22. [Mission Create Flow](#22-mission-create-flow)
23. [Mission Update Flow](#23-mission-update-flow)
24. [Mission Delete Flow](#24-mission-delete-flow)
25. [Mission Orders Subsystem](#25-mission-orders-subsystem)
26. [Site Relationship](#26-site-relationship)
27. [Contract Relationship](#27-contract-relationship)
28. [Access Route Relationship (Audit & Verification)](#28-access-route-relationship-audit--verification)
29. [Attachments Relationship & Revenue Flow](#29-attachments-relationship--revenue-flow)
30. [Reports Relationship & Metrological Chain](#30-reports-relationship--metrological-chain)
31. [Charges Relationship & Cost Accounting](#31-charges-relationship--cost-accounting)
32. [Authentication & Authorization Guard Rails](#32-authentication--authorization-guard-rails)
33. [Validation Suite](#33-validation-suite)
34. [Business Rules Inventory](#34-business-rules-inventory)
35. [Exception Handling Architecture](#35-exception-handling-architecture)
36. [HTTP Status Codes](#36-http-status-codes)
37. [Source of Truth Matrix](#37-source-of-truth-matrix)
38. [Dependency Map](#38-dependency-map)
39. [End-to-End Execution Traces](#39-end-to-end-execution-traces)
40. [Mission Lifecycle & State Machine](#40-mission-lifecycle--state-machine)
41. [Architectural Observations](#41-architectural-observations)
42. [Technical Debt & Critical Anomalies](#42-technical-debt--critical-anomalies)
43. [Final Architecture Summary](#43-final-architecture-summary)

---

## 1. Executive Overview

`[Confirmed]` The **Missions Module** is the operational backbone and central execution hub of the ENGI-MATE enterprise system. In petrochemical metrology and industrial instrumentation services, field calibration operations are mobilized as formalized **Missions** (`missions`).

A Mission serves as the nexus binding:
1. **The Physical Installation (`sites`):** Industrial oil & gas facilities, metering stations, and terminals where inspection takes place.
2. **The Commercial Framework (`contracts`):** Active client agreements that fund the operation and establish billing parameters.
3. **The Human Resource Deployment (`employees` & `mission_orders`):** Certified field engineers, technicians, and team leaders mobilizing with formal travel orders (*Ordres de Mission*).
4. **The Technical Tooling & Logistics (`equipment`, `deployments`, and `movement_equipment`):** Precision calibration instruments, deadweight testers, multimeters, and designated transport vehicles.
5. **Operational Deliverables (`reports` & calibration verifications):** Metrological loop inspection reports, gas chromatograph calibrations, transmitter verifications, and flow computer audits.
6. **Commercial Exploitation (`attachments` & `attachment_items`):** Work progress billing attachments (*Attachements*) quantifying services executed against contractual unit rate prices.
7. **Economic Unit Performance (`charges` & `MissionStatisticsService`):** Comprehensive Unit Economics tracking gross revenues, direct mission charges, employee per-diems, overhead allocation, and net profit margins.

---

## 2. Mission Domain Overview

### 2.1 Core Mission Purpose
A Mission represents a scheduled or active operational deployment executed by company engineers at a designated client site over a bounded calendar interval.

```text
                                  ┌──────────────────────────┐
                                  │         CUSTOMER         │
                                  └─────────────┬────────────┘
                                                │ 1:N
                        ┌───────────────────────┴───────────────────────┐
                        │ 1:N                                           │ 1:N
              ┌─────────▼─────────┐                           ┌─────────▼─────────┐
              │     CONTRACTS     │                           │       SITES       │
              └─────────┬─────────┘                           └─────────┬─────────┘
                        │ 1:N                                           │ 1:N
                        └───────────────────────┬───────────────────────┘
                                                │
                                       ┌────────▼────────┐
                                       │    MISSIONS     │
                                       └────────┬────────┘
             ┌───────────────────┬──────────────┼──────────────┬───────────────────┐
             │ 1:N               │ 1:N          │ 1:N          │ 1:N               │ 1:N
   ┌─────────▼─────────┐   ┌─────▼──────┐   ┌───▼────┐   ┌─────▼──────┐   ┌────────▼────────┐
   │  MISSION ORDERS   │   │DEPLOYMENTS │   │REPORTS │   │ATTACHMENTS │   │     CHARGES     │
   │   (Employees)     │   │(Equipment) │   │        │   │ (Revenue)  │   │  (Expenses)     │
   └─────────┬─────────┘   └─────┬──────┘   └───┬────┘   └─────┬──────┘   └─────────────────┘
             │ N:1               │ N:1          │              │ 1:N
   ┌─────────▼─────────┐   ┌─────▼──────┐       │ 1:N    ┌─────▼──────────┐
   │     EMPLOYEES     │   │ EQUIPMENT  │       │        │ATTACHMENT_ITEMS│
   │  (Daily Rates)    │   │ (Vehicles) │       │        └─────┬──────────┘
   └───────────────────┘   └────────────┘       │              │ N:1
                                                │        ┌─────▼──────────┐
                                                │        │ ITEM_CONTRACTS │
                                                │        └────────────────┘
                                                │
                 ┌──────────────────────────────┼──────────────────────────────┐
                 │ 1:N                          │ 1:N                          │ 1:N
       ┌─────────▼─────────┐          ┌─────────▼─────────┐          ┌─────────▼─────────┐
       │   TRANSMITTER     │          │       PROBE       │          │   FLOW COMPUTER   │
       │  VERIFICATIONS    │          │   VERIFICATIONS   │          │   VERIFICATIONS   │
       └───────────────────┘          └───────────────────┘          └───────────────────┘
```

### 2.2 Domain Boundary Questions & Answers
- **What data does a Mission represent?** `[Confirmed]` Reference code (`reference`), associated installation site (`site_id`), associated contract (`contract_id`), departure date (`start_date`), completion date (`end_date`), mobilization/demobilization transit allowance days (`mob_dmob_days`), and lifecycle state (`status`).
- **Who creates a Mission?** `[Confirmed]` Users holding the Spatie permission `create missions` (typically Manager or Senior Engineer roles).
- **Who modifies a Mission?** `[Confirmed]` Users holding `edit missions`. Editing is restricted to `planned` missions; attempting to modify `completed` or `cancelled` missions triggers a domain exception.
- **What entities depend on Mission?** `[Confirmed]`
  1. `mission_orders` (Foreign key constraint `fk_om_missions` with `CASCADE DELETE`).
  2. `deployments` (Foreign key constraint `fk_deployments_missions` with `CASCADE DELETE`).
  3. `attachments` (Foreign key constraint `fk_attachments_missions` with `CASCADE DELETE`).
  4. `charges` (Foreign key constraint `fk_charges_missions` with `CASCADE DELETE`).
  5. `reports` (Foreign key constraint `reports_ibfk_1` with `RESTRICT / NO ACTION` on delete).
- **What does Mission depend on?** `[Confirmed]`
  1. `sites` (Foreign key `site_id`, `RESTRICT / NO ACTION` on delete).
  2. `contracts` (Foreign key `contract_id`, `CASCADE DELETE` on contract deletion).

---

## 3. Folder Structure & Component Inventory

```text
missions/ (Conceptual Module Scope)
├── app/
│   ├── Enums/
│   │   └── MissionStatus.php
│   ├── Exceptions/
│   │   └── MissionConflictException.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Admin/
│   │   │       ├── MissionController.php
│   │   │       └── MissionOrderController.php
│   │   └── Requests/
│   │       └── Mission/
│   │           ├── StoreMissionRequest.php
│   │           ├── UpdateMissionRequest.php
│   │           ├── PrintMissionOrderRequest.php
│   │           └── UpdateMissionOrderRequest.php
│   ├── Models/
│   │   ├── Mission.php
│   │   └── MissionOrder.php
│   ├── Observers/
│   │   └── MissionObserver.php
│   ├── Services/
│   │   ├── MissionReferenceGenerator.php
│   │   └── MissionStatisticsService.php
│   └── UseCases/
│       ├── Mission/
│       │   ├── CreateMissionUseCase.php
│       │   ├── UpdateMissionUseCase.php
│       │   ├── ActivateMissionUseCase.php
│       │   ├── CompleteMissionUseCase.php
│       │   ├── DeleteMissionUseCase.php
│       │   └── RevertMissionUseCase.php
│       └── MissionOrder/
│           └── GenerateMissionOrderReferenceUseCase.php
├── resources/
│   └── views/
│       └── admin/
│           └── missions/
│               ├── index.blade.php
│               ├── create.blade.php
│               ├── edit.blade.php
│               ├── show.blade.php
│               ├── employees.blade.php
│               ├── equipments.blade.php
│               ├── edit_order.blade.php
│               ├── print_order.blade.php
│               └── statistics.blade.php
└── tests/
    └── Feature/
        └── Controllers/
            └── Admin/
                └── MissionControllerTest.php
```

### Detailed Component Inventory

| File Path | Type | Responsibility | Class | Primary Methods | Dependencies |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `app/Models/Mission.php` | Eloquent Model | Core entity representation, casts, Eloquent relationships | `Mission` | `casts()`, `site()`, `contract()`, `attachments()`, `employees()`, `equipments()`, `getStatusBadgeClassAttribute()` | `Site`, `Contract`, `Attachment`, `Employee`, `Equipment`, `MissionStatus` |
| `app/Models/MissionOrder.php` | Eloquent Model | Pivot model for employee assignment & travel order | `MissionOrder` | `vehicle()`, `mission()`, `employee()` | `Mission`, `Employee`, `Equipment` |
| `app/Enums/MissionStatus.php` | PHP 8.4 Backed Enum | Enumeration of lifecycle states (`planned`, `active`, `completed`) | `MissionStatus` | Cases: `Planned`, `Active`, `Completed` | Native PHP Enum |
| `app/Http/Controllers/Admin/MissionController.php` | Web Controller | Handles HTTP actions for Mission lifecycle, UI orchestration | `MissionController` | `index()`, `create()`, `store()`, `activate()`, `complete()`, `revert()`, `edit()`, `update()`, `destroy()`, `show()`, `employeesList()`, `equipmentsList()`, `statistics()` | Use Cases, Form Requests, `MissionStatisticsService`, Models |
| `app/Http/Controllers/Admin/MissionOrderController.php` | Web Controller | Handles printing, updating travel itinerary, vehicle assignment | `MissionOrderController` | `print()`, `edit()`, `update()`, `getAccompanistsText()` | `GenerateMissionOrderReferenceUseCase`, Form Requests, Models |
| `app/Http/Requests/Mission/StoreMissionRequest.php` | Form Request | Validation rules & single-leader business invariant for creation | `StoreMissionRequest` | `rules()`, `withValidator()`, `messages()` | Laravel `FormRequest` |
| `app/Http/Requests/Mission/UpdateMissionRequest.php` | Form Request | Validation rules & leader inclusion invariant for updating | `UpdateMissionRequest` | `authorize()`, `rules()`, `messages()` | Laravel `FormRequest` |
| `app/Http/Requests/Mission/PrintMissionOrderRequest.php` | Form Request | Sanitization and validation for travel order print vehicle param | `PrintMissionOrderRequest` | `authorize()`, `prepareForValidation()`, `rules()` | Laravel `FormRequest` |
| `app/Http/Requests/Mission/UpdateMissionOrderRequest.php` | Form Request | Validation of destination itinerary and travel dates | `UpdateMissionOrderRequest` | `authorize()`, `rules()` | Laravel `FormRequest` |
| `app/UseCases/Mission/CreateMissionUseCase.php` | Application Action | Creates Mission, generates reference, binds team & equipment | `CreateMissionUseCase` | `execute(array $data)` | `MissionReferenceGenerator`, `Mission`, `Site`, `Employee`, `DB` |
| `app/UseCases/Mission/UpdateMissionUseCase.php` | Application Action | Atomic update of attributes, synchronizing employees/equipment | `UpdateMissionUseCase` | `execute(int $missionId, array $data)` | `Mission`, `Site`, `Employee`, `DB` |
| `app/UseCases/Mission/ActivateMissionUseCase.php` | Application Action | Temporal conflict detection for humans & assets, activates state | `ActivateMissionUseCase` | `execute(int $missionId)` | `Mission`, `MissionConflictException`, `DB` |
| `app/UseCases/Mission/CompleteMissionUseCase.php` | Application Action | Closes active mission, releases employee and equipment pivots | `CompleteMissionUseCase` | `execute(int $missionId)` | `Mission`, `DB` |
| `app/UseCases/Mission/RevertMissionUseCase.php` | Application Action | Re-opens completed mission back to planned/active status | `RevertMissionUseCase` | `execute(int $missionId)` | `Mission`, `MissionConflictException`, `DB` |
| `app/UseCases/Mission/DeleteMissionUseCase.php` | Application Action | Cascades pivot deletions and deletes mission | `DeleteMissionUseCase` | `execute(int $id)` | `Mission`, `DB` |
| `app/UseCases/MissionOrder/GenerateMissionOrderReferenceUseCase.php` | Application Action | Generates atomic sequential travel order reference (`NNN/ALG/YY`) | `GenerateMissionOrderReferenceUseCase` | `execute(int $missionId, int $employeeId)` | `MissionOrder`, `DB` |
| `app/Services/MissionReferenceGenerator.php` | Domain Service | Calculates next sequential Mission reference (`M-YYYY-NNN`) | `MissionReferenceGenerator` | `generate(): string` | `Mission`, `DB` |
| `app/Services/MissionStatisticsService.php` | Domain Service | Enterprise Unit Economics: costs, charges, revenue, profitability | `MissionStatisticsService` | `calculate(Mission $mission, ?int $year)`, `calculateCompanyStats(?int $year)` | `Mission`, `DB` |
| `app/Observers/MissionObserver.php` | Eloquent Observer | Auditing and group notifications upon mission lifecycle transitions | `MissionObserver` | `created()`, `updated()`, `deleted()`, `getPayload()` | `NotificationService`, `HasAdminAuditor` |
| `app/Exceptions/MissionConflictException.php` | Custom Exception | Formats detailed multiline error when resource overlaps occur | `MissionConflictException` | `__construct(array $employeeConflicts, array $equipmentConflicts)` | `Exception` |

---

## 4. Architecture Overview

The Missions module follows a hybrid architecture combining **Clean Architecture Use Cases (Command Handlers)** with **Laravel MVC**:

```text
[ Browser / Client View ]
         │
         ▼
[ HTTP Request: /admin/missions/... ]
         │
         ▼
[ Middleware Pipeline: auth -> checkStatus -> permission:... ]
         │
         ▼
[ Controller: MissionController / MissionOrderController ]
         │
         ▼
[ Form Request: StoreMissionRequest / UpdateMissionRequest ]
   ├── Technical Validation (required, date, exists, boolean)
   └── Custom Domain Validation (single leader enforcement)
         │
         ▼
[ Use Case Layer: App\UseCases\Mission\* ]
   ├── Database Transaction Encapsulation (DB::transaction)
   ├── Concurrency Control (lockForUpdate)
   ├── Domain Invariant Checks (Temporal Overlap Conflict Detection)
   └── Synchronous Pivot Orchestration (sync, attach, updateExistingPivot)
         │
         ▼
[ Eloquent Models & DB Layer ]
   ├── Model Persistence (missions, mission_orders, deployments)
   └── Database Observer Dispatch (MissionObserver via AppServiceProvider)
         │
         ▼
[ Notification Service & Auditor Trait ]
   └── System Notifications to 'view missions' Spatie Permission Group
         │
         ▼
[ Redirect Response with Flash Message / Blade View Render ]
```

---

## 5. Mission Data Model

### Table: `missions`
`[Confirmed]` The physical database structure of the `missions` table:

| Field | Type | Nullable | Default | Source | Purpose |
| :--- | :--- | :---: | :---: | :--- | :--- |
| `id` | `INT` | No | *AUTO_INCREMENT* | Database Sequence | Surrogate Primary Key |
| `site_id` | `INT` | Yes | `NULL` | Form Selection (`sites.id`) | Foreign key linking mission to target industrial installation |
| `contract_id` | `INT` | Yes | `NULL` | Form Selection (`contracts.id`) | Foreign key linking mission to governing commercial client contract |
| `reference` | `VARCHAR(255)` | No | `NULL` | `MissionReferenceGenerator` | Unique business code formatted as `M-YYYY-NNN` (e.g. `M-2026-001`) |
| `start_date` | `DATE` | Yes | `NULL` | Form Input (`start_date`) | Scheduled mobilization departure date |
| `end_date` | `DATE` | Yes | `NULL` | Form Input (`end_date`) | Scheduled completion / return date |
| `mob_dmob_days` | `INT` | Yes | `NULL` | Form Input (`mob_dmob_days`) | Number of transit days budgeted for travel (used in Unit Economics) |
| `status` | `ENUM('planned','active','completed')` | Yes | `'planned'` | Default or Lifecycle Actions | Current operational state mapped to `MissionStatus` Enum |
| `created_at` | `TIMESTAMP` | Yes | `NULL` | Eloquent Timestamps | Record creation audit timestamp |
| `updated_at` | `TIMESTAMP` | Yes | `NULL` | Eloquent Timestamps | Record update audit timestamp |

#### Field Specifics & Observations
- **`reference`:** Mandatory unique identifier generated autonomously during execution of `CreateMissionUseCase`.
- **`contract_id`:** Optional in schema, allowing missions to be programmed before commercial contracts are formalized.
- **`mob_dmob_days`:** Used by `MissionStatisticsService` to determine *Operational Working Days* ($\text{Total Days} - \text{Mob/Dmob Days}$) and *Mobility Cost* ($\text{Mob/Dmob Days} \times \text{Daily Team Rate}$).
- **`status`:** Cast to Backed Enum `App\Enums\MissionStatus`.
- **Soft Deletes:** `[Confirmed]` Not enabled. Neither the column `deleted_at` nor the `SoftDeletes` trait exists on `Mission`.
- **Description Column:** `[Confirmed]` Does **not** exist in MySQL table `missions`, despite appearing in `UpdateMissionRequest` and `UpdateMissionUseCase` (see [Technical Debt](#42-technical-debt--critical-anomalies)).

---

## 6. Database Schema

### 6.1 Master Table: `missions`
- **Primary Key:** `id` (`INT`, auto-increment)
- **Foreign Keys:**
  - `fk_missions_sites`: `site_id` $\to$ `sites(id)` — `ON UPDATE CASCADE`, `ON DELETE NO ACTION (RESTRICT)`
  - `fk_missions_contracts`: `contract_id` $\to$ `contracts(id)` — `ON UPDATE NO ACTION`, `ON DELETE CASCADE`
- **Indexes:**
  - `PRIMARY`: (`id`)
  - `fk_missions_sites`: (`site_id`)
  - `fk_missions_contracts`: (`contract_id`)

### 6.2 Pivot Table: `mission_orders` (Employees Assignment)
- **Primary Key:** `id` (`INT`, auto-increment)
- **Purpose:** Stores employee assignments, daily rates, destinations, and travel order generation.

| Column | Type | Nullable | Default | Key | Relationship / Description |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `id` | `INT` | No | *AI* | PRI | Surrogate primary key |
| `mission_id` | `INT` | No | `NULL` | MUL | FK $\to$ `missions(id)` (`ON DELETE CASCADE`, `ON UPDATE CASCADE`) |
| `employee_id` | `INT` | No | `NULL` | MUL | FK $\to$ `employees(id)` (`ON DELETE NO ACTION`, `ON UPDATE CASCADE`) |
| `is_leader` | `VARCHAR(50)` | Yes | `NULL` | | Indicates mission chief (`'1'`, `'0'`, or boolean representation) |
| `status` | `ENUM('active','completed','cancelled')` | Yes | `'active'` | | Operational status of employee deployment |
| `started_at` | `DATE` | Yes | `NULL` | | Effective start date of employee deployment |
| `ended_at` | `DATE` | Yes | `NULL` | | Effective end date of employee deployment |
| `daily_rate` | `DECIMAL(15,2)` | Yes | `0.00` | | Per-diem rate snapshot from `employees.daily_rate` at assignment |
| `order_reference` | `VARCHAR(50)` | Yes | `NULL` | | Sequential travel order code (e.g. `001/ALG/26`) |
| `destination` | `VARCHAR(255)` | Yes | `NULL` | | Travel itinerary (e.g. `Base - Site Location - Base`) |
| `vehicle_id` | `INT` | Yes | `NULL` | MUL | FK $\to$ `equipment(id)` (`ON DELETE SET NULL`, `ON UPDATE NO ACTION`) |
| `all_vehicles` | `TINYINT(1)` | No | `0` | | Boolean flag authorizing driver to operate any company vehicle |
| `created_at` | `TIMESTAMP` | Yes | `NULL` | | Creation timestamp |
| `updated_at` | `TIMESTAMP` | Yes | `NULL` | | Last update timestamp |

### 6.3 Pivot Table: `deployments` (Equipment & Vehicle Allocation)
- **Primary Key:** `id` (`INT`, auto-increment)
- **Purpose:** Stores physical equipment and vehicles assigned to a Mission.

| Column | Type | Nullable | Default | Key | Relationship / Description |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `id` | `INT` | No | *AI* | PRI | Surrogate primary key |
| `mission_id` | `INT` | Yes | `NULL` | MUL | FK $\to$ `missions(id)` (`ON DELETE CASCADE`, `ON UPDATE CASCADE`) |
| `equipment_id` | `INT` | Yes | `NULL` | MUL | FK $\to$ `equipment(id)` (`ON DELETE NO ACTION`, `ON UPDATE NO ACTION`) |
| `status` | `ENUM('active','returned','damaged')` | Yes | `'active'` | | Custody status of deployed tool/vehicle |
| `deployed_at` | `TIMESTAMP` | Yes | `NULL` | | Check-out timestamp |
| `returned_at` | `TIMESTAMP` | Yes | `NULL` | | Return timestamp |
| `created_at` | `TIMESTAMP` | Yes | `NULL` | | Creation timestamp |
| `updated_at` | `TIMESTAMP` | Yes | `NULL` | | Update timestamp |

### 6.4 Related Dependent Tables

#### `reports`
- **Foreign Key:** `mission_id` $\to$ `missions(id)`
- **Constraint:** `reports_ibfk_1`
- **Delete Action:** `ON DELETE NO ACTION` (RESTRICT). **Critical:** Missions with linked reports cannot be hard-deleted until reports are deleted.

#### `attachments`
- **Foreign Key:** `mission_id` $\to$ `missions(id)`
- **Constraint:** `fk_attachments_missions`
- **Delete Action:** `ON DELETE CASCADE`.

#### `charges`
- **Foreign Key:** `mission_id` $\to$ `missions(id)`
- **Constraint:** `fk_charges_missions`
- **Delete Action:** `ON DELETE CASCADE`.

#### `movement_equipment`
- **Foreign Key:** `deployment_id` $\to$ `deployments(id)`
- **Constraint:** Links individual verification calibration batches to mission equipment deployments.

---

## 7. Logical ERD

```text
               +---------------------------------------+
               |                 SITES                 |
               +---------------------------------------+
               | id (PK)                               |
               | site_code, full_name, short_name      |
               | location, map_link                    |
               +-------------------┬-------------------+
                                   │ 1
                                   │
                                   │ N (FK: site_id)
               +-------------------▼-------------------+                  +-----------------------------------+
               |               MISSIONS                | 1              N |             CONTRACTS             |
               +---------------------------------------+◄─────────────────+-----------------------------------+
               | id (PK)                               | (FK: contract_id)| id (PK)                           |
               | reference (M-YYYY-NNN)                |                  | reference, object, date_signature |
               | start_date, end_date, mob_dmob_days   |                  | duree, montant_global_prevu       |
               | status ('planned','active','completed')|                  +-----------------------------------+
               +--┬──────────────┬──────────────┬───┬--+
                  │ 1            │ 1            │ 1 │ 1
                  │              │              │   │
        ┌─────────┘              │              │   └─────────┐
        │ N                      │ N            │ N           │ N
+-------▼---------------+ +------▼--------+ +---▼-------+ +---▼---------+
|    MISSION_ORDERS     | | DEPLOYMENTS   | |  REPORTS  | | ATTACHMENTS |
+-----------------------+ +---------------+ +-----------+ +-------------+
| id (PK)               | | id (PK)       | | id (PK)   | | id (PK)     |
| mission_id (FK)       | | mission_id(FK)| | mission_id| | mission_id  |
| employee_id (FK)      | | equipment_id  | | status    | | status      |
| is_leader, daily_rate | | status        | +-----+-----+ +------+------+
| order_reference       | +-------┬-------+       │              │ 1
| destination           |         │ 1             │ 1            │ N
+-----------┬-----------+         │ N             │ N     +------▼-----------+
            │ N                   │         +-----▼-----+ | ATTACHMENT_ITEMS |
            │ 1                   │         |VERIF      | +------------------+
+-----------▼-----------+         │         |TABLES     | | id (PK)          |
|       EMPLOYEES       |         │         +-----------+ | actual_quantity  |
+-----------------------+         │                       | item_contract_id |
| id (PK)               |         │                       +--------┬---------+
| full_name, daily_rate |         │                                │ N
+-----------------------+         │                                │ 1
                                  │                       +--------▼---------+
                                  │                       |  ITEM_CONTRACTS  |
                                  │                       +------------------+
                                  │                       | id (PK)          |
                                  │                       | unit_price       |
                                  │                       +------------------+
                                  │
                                  ▼
                         +-----------------+
                         |    EQUIPMENT    |
                         +-----------------+
                         | id (PK)         |
                         | full_name       |
                         | category        |
                         +-----------------+
```

---

## 8. Eloquent Models

### 8.1 Model: `App\Models\Mission`
- **Location:** `app/Models/Mission.php`
- **Mass Assignment:** `protected $guarded = ['id'];`
- **Attribute Casts:**
  ```php
  protected function casts(): array
  {
      return [
          'start_date' => 'date',
          'end_date' => 'date',
          'status' => MissionStatus::class,
      ];
  }
  ```
- **Accessors:**
  - `status_badge_class`: Returns `'badge-normal'` for `active`, `'badge-completed'` for `completed`, and `'badge-warning'` for `planned`.

### 8.2 Model: `App\Models\MissionOrder`
- **Location:** `app/Models/MissionOrder.php`
- **Table:** `protected $table = 'mission_orders';`
- **Mass Assignment:**
  ```php
  protected $fillable = [
      'mission_id', 'employee_id', 'is_leader', 'destination',
      'status', 'started_at', 'ended_at', 'daily_rate',
      'order_reference', 'vehicle_id', 'all_vehicles',
  ];
  ```

---

## 9. Eloquent Relationships

### Summary Table of Mission Relationships

| Relationship | Type | Target Model | Foreign Key | Local Key | Delete Behavior |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `site()` | `BelongsTo` | `Site` | `site_id` | `id` | DB: `RESTRICT` |
| `contract()` | `BelongsTo` | `Contract` | `contract_id` | `id` | DB: `CASCADE` |
| `attachments()` | `HasMany` | `Attachment` | `mission_id` | `id` | DB: `CASCADE` |
| `employees()` | `BelongsToMany` | `Employee` (via `mission_orders`) | `mission_id` $\to$ `employee_id` | `id` | DB: `CASCADE` |
| `equipments()` | `BelongsToMany` | `Equipment` (via `deployments`) | `mission_id` $\to$ `equipment_id` | `id` | DB: `CASCADE` |
| `reports()` | `HasMany` *(Implicit via DB)* | `Report` | `mission_id` | `id` | DB: `RESTRICT` |
| `charges()` | `HasMany` *(Implicit via DB)* | `Charge` | `mission_id` | `id` | DB: `CASCADE` |

#### Detailed Method Declarations on `Mission`:
```php
public function site(): BelongsTo
{
    return $this->belongsTo(Site::class);
}

public function contract(): BelongsTo
{
    return $this->belongsTo(Contract::class);
}

public function attachments(): HasMany
{
    return $this->hasMany(Attachment::class, 'mission_id');
}

public function employees(): BelongsToMany
{
    return $this->belongsToMany(Employee::class, 'mission_orders')
        ->withPivot('is_leader', 'status', 'started_at', 'ended_at', 'destination', 'daily_rate', 'order_reference')
        ->withTimestamps();
}

public function equipments(): BelongsToMany
{
    return $this->belongsToMany(Equipment::class, 'deployments')
        ->withPivot('status', 'deployed_at', 'returned_at')
        ->withTimestamps();
}
```

---

## 10. Controllers

### 10.1 Mission Routing & Controller Matrix

| HTTP Method | Route Name | Controller Action | Middleware / Permission | Input Parameters | Output Response |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `admin.missions.index` | `MissionController@index` | `auth, checkStatus, permission:view missions` | `site_id, contract_id, date_from, date_to, search, page` | `View: admin.missions.index` (200) |
| `GET` | `admin.missions.create` | `MissionController@create` | `auth, checkStatus, permission:create missions` | None | `View: admin.missions.create` (200) |
| `POST` | `admin.missions.store` | `MissionController@store` | `auth, checkStatus, permission:create missions` | `StoreMissionRequest` payload | `RedirectResponse` $\to$ `index` (302) |
| `GET` | `admin.missions.show` | `MissionController@show` | `auth, checkStatus, permission:view missions` | Route Param: `{mission}` (numeric) | `View: admin.missions.show` (200) |
| `GET` | `admin.missions.edit` | `MissionController@edit` | `auth, checkStatus, permission:edit missions` | Route Param: `{mission}` | `View: admin.missions.edit` (200) |
| `PUT` | `admin.missions.update` | `MissionController@update` | `auth, checkStatus, permission:edit missions` | `UpdateMissionRequest` payload | `RedirectResponse` $\to$ `index` (302) |
| `DELETE` | `admin.missions.destroy` | `MissionController@destroy` | `auth, checkStatus, permission:delete missions` | Route Param: `{mission}` | `RedirectResponse` $\to$ `back` (302) |
| `PATCH` | `admin.missions.activate` | `MissionController@activate` | `auth, checkStatus, permission:edit missions` | Route Param: `{mission}` | `RedirectResponse` $\to$ `back` (302) |
| `PATCH` | `admin.missions.complete` | `MissionController@complete` | `auth, checkStatus, permission:edit missions` | Route Param: `{mission}` | `RedirectResponse` $\to$ `back` (302) |
| `PATCH` | `admin.missions.revert` | `MissionController@revert` | `auth, checkStatus, permission:edit missions` | Route Param: `{mission}` | `RedirectResponse` $\to$ `back` (302) |
| `GET` | `admin.missions.employees` | `MissionController@employeesList` | `auth, checkStatus, permission:view missions` | Route Param: `{mission}` | `View: admin.missions.employees` (200) |
| `GET` | `admin.missions.equipments` | `MissionController@equipmentsList` | `auth, checkStatus, permission:view missions` | Route Param: `{mission}` | `View: admin.missions.equipments` (200) |
| `GET` | `admin.missions.statistics` | `MissionController@statistics` | `auth, checkStatus, permission:view missions` | Route Param: `{mission}`, Query: `year` | `View: admin.missions.statistics` (200) |
| `GET` | `admin.missions.print_order` | `MissionOrderController@print` | `auth, checkStatus, permission:view missions` | `{mission}, {employee}`, Query: `moyen` | `View: admin.missions.print_order` (200) |
| `GET` | `admin.missions.edit_order` | `MissionOrderController@edit` | `auth, checkStatus, permission:view missions` | Route Params: `{mission}, {employee}` | `View: admin.missions.edit_order` (200) |
| `PUT` | `admin.missions.update_order` | `MissionOrderController@update` | `auth, checkStatus, permission:edit missions` | `UpdateMissionOrderRequest` payload | `RedirectResponse` $\to$ `employees` (302) |

---

## 11. Form Requests

### 11.1 `StoreMissionRequest`
- **Namespace:** `App\Http\Requests\Mission\StoreMissionRequest`
- **Technical Validation Rules:**
  - `site_id`: `required | exists:sites,id`
  - `contract_id`: `nullable | exists:contracts,id`
  - `start_date`: `required | date`
  - `end_date`: `required | date | after_or_equal:start_date`
  - `mob_dmob_days`: `required | numeric | min:0`
  - `employees`: `required | array | min:1`
  - `employees.*.id`: `required | exists:employees,id`
  - `employees.*.is_leader`: `required | boolean`
  - `vehicle_id`: `nullable | exists:equipment,id`
  - `equipments`: `nullable | array`
  - `equipments.*.id`: `required | exists:equipment,id`
- **Domain Business Validation:**
  ```php
  public function withValidator($validator)
  {
      $validator->after(function ($validator) {
          $leaders = collect($this->employees)->where('is_leader', true)->count();
          if ($leaders !== 1) {
              $validator->errors()->add('employees', 'يجب تحديد قائد واحد فقط للمهمة.');
          }
      });
  }
  ```

### 11.2 `UpdateMissionRequest`
- **Namespace:** `App\Http\Requests\Mission\UpdateMissionRequest`
- **Validation Rules:**
  - `site_id`: `required | exists:sites,id`
  - `contract_id`: `nullable | exists:contracts,id`
  - `start_date`: `required | date`
  - `end_date`: `required | date | after_or_equal:start_date`
  - `mob_dmob_days`: `required | numeric | min:0`
  - `vehicle_id`: `nullable | exists:equipment,id`
  - `employees`: `required | array | min:1`
  - `employees.*`: `exists:employees,id`
  - `chief_id`: `required | exists:employees,id | in_array:employees.*`
  - `equipments`: `nullable | array`
  - `equipments.*`: `exists:equipment,id`
  - `description`: `nullable | string | max:1000` *(Note: Unmapped in DB schema)*

### 11.3 `PrintMissionOrderRequest`
- **Pre-validation hook:** Converts string `"0"` for `moyen` to `null`.
- **Validation Rules:**
  - `moyen`: `['nullable', 'integer', 'exists:equipment,id']`

### 11.4 `UpdateMissionOrderRequest`
- **Validation Rules:**
  - `destination`: `['nullable', 'string', 'max:255']`
  - `started_at`: `['nullable', 'date']`
  - `ended_at`: `['nullable', 'date']`
  - `vehicle_id`: `['nullable', 'integer', 'exists:equipment,id']`
  - `all_vehicles`: `['boolean']`

---

## 12. Services / Actions / Use Cases

The business logic of the Mission module is organized into specialized Use Cases:

### 12.1 `CreateMissionUseCase`
- **Location:** `app/UseCases/Mission/CreateMissionUseCase.php`
- **Transaction:** Wraps entire logic in `DB::transaction()`.
- **Steps:**
  1. Invokes `MissionReferenceGenerator::generate()` to obtain unique code (`M-YYYY-NNN`).
  2. Resolves destination landmark center from `Site::where('id', $data['site_id'])->value('location') ?? 'مسار غير محدد'`.
  3. Creates `Mission` record with `planned` default status.
  4. Loops through `employees`: constructs composite destination (`"{employee.address} - {site.location} - {employee.address}"`), snaps `daily_rate`, sets `started_at` and `ended_at` to mission dates, and attaches to `mission_orders`.
  5. Attaches `vehicle_id` (if present) to `deployments` with `status => 'active'`.
  6. Attaches `equipments` array to `deployments` with `status => 'active'`.

### 12.2 `UpdateMissionUseCase`
- **Location:** `app/UseCases/Mission/UpdateMissionUseCase.php`
- **State Guard:**
  ```php
  if (in_array($mission->status->value, ['completed', 'cancelled'])) {
      throw new Exception(__('missions.cannot_update_closed_mission'));
  }
  ```
- **Sync Logic:** Synchronizes `employees` preserving custom part-time dates already saved in `mission_orders`. Synchronizes `equipments` and vehicles.

### 12.3 `ActivateMissionUseCase`
- **Location:** `app/UseCases/Mission/ActivateMissionUseCase.php`
- **State Guard:** Verifies `status === 'planned'`.
- **Conflict Algorithm:** Computes date overlap with any other `active` mission:
  $$\text{Overlap Condition: } (\text{mission.start\_date} \le \text{target.end\_date}) \land (\text{mission.end\_date} \ge \text{target.start\_date})$$
  - Checks if any assigned employee is already locked in an overlapping active mission.
  - Checks if any assigned equipment or vehicle is already locked in an overlapping active mission.
  - If conflicts exist, throws `MissionConflictException` carrying conflicting employee and equipment lists.
  - If conflict-free, updates `missions.status = 'active'`, `mission_orders.status = 'active'`, and `deployments.status = 'active'`.

### 12.4 `CompleteMissionUseCase`
- **Location:** `app/UseCases/Mission/CompleteMissionUseCase.php`
- **State Guard:** Verifies `status === 'active'`.
- **Action:** Sets `missions.status = 'completed'`, updates `mission_orders.status = 'completed'`, and releases equipment setting `deployments.status = 'returned'`.

### 12.5 `RevertMissionUseCase`
- **Location:** `app/UseCases/Mission/RevertMissionUseCase.php`
- **Purpose:** Re-opens a closed mission back to active execution.
- **Analysis:** Contains state query and status update logic (see [Technical Debt](#42-technical-debt--critical-anomalies)).

### 12.6 `DeleteMissionUseCase`
- **Location:** `app/UseCases/Mission/DeleteMissionUseCase.php`
- **Action:** Explicitly deletes rows from `deployments` and `mission_orders`, then deletes `Mission`.

### 12.7 `GenerateMissionOrderReferenceUseCase`
- **Location:** `app/UseCases/MissionOrder/GenerateMissionOrderReferenceUseCase.php`
- **Action:** Uses `lockForUpdate()`. Inspects current year's highest reference matching `'%/ALG/' . date('y')`. Increments sequence and updates `order_reference` to `sprintf('%03d/%s/%02d', $next, 'ALG', $year)`.

### 12.8 `MissionStatisticsService`
- **Location:** `app/Services/MissionStatisticsService.php`
- **Calculations:**
  - $\text{Total Mission Days} = (\text{end\_date} - \text{start\_date}) + 1$
  - $\text{Total Paid Days} = \sum (\text{ended\_at} - \text{started\_at} + 1)$ across `mission_orders`
  - $\text{Operational Days} = \max(0, \text{Total Mission Days} - \text{mob\_dmob\_days})$
  - $\text{Total HR Cost} = \sum (\text{daily\_rate} \times (\text{ended\_at} - \text{started\_at} + 1))$
  - $\text{Direct Charges} = \sum \text{charges.amount where mission\_id} = M$
  - $\text{Mission Revenue} = \sum (\text{attachment\_items.actual\_quantity} \times \text{item\_contracts.unit\_price})$
  - $\text{Gross Profit} = \text{Mission Revenue} - (\text{Total HR Cost} + \text{Direct Charges})$
  - Company overhead absorption rate ($G_{\text{rate}}$) applied against mission duration to compute Net Profit.

---

## 13. Repositories

`[Confirmed]` **Not found in the analyzed code.**  
The application does not use a Repository Pattern for the Missions module. Instead, queries are composed directly using Eloquent ORM within Controllers and Use Case actions.

---

## 14. Policies & Authorization

`[Confirmed]` **No `MissionPolicy.php` exists.**  
Authorization is enforced via Spatie Laravel-Permission:
1. **Route Middleware Guards:**
   - `permission:view missions`: Guards `index`, `show`, `employeesList`, `equipmentsList`, `statistics`, `print_order`, `edit_order`.
   - `permission:create missions`: Guards `create`, `store`.
   - `permission:edit missions`: Guards `edit`, `update`, `activate`, `complete`, `revert`, `update_order`.
   - `permission:delete missions`: Guards `destroy`.
2. **Blade UI Directives:**
   - `@can('create missions')`: Renders create mission button.
   - `@can('edit missions')`: Renders edit, activate, complete, and revert action buttons.
   - `@can('delete missions')`: Renders delete button and modal form.

---

## 15. API Endpoints

`[Confirmed]` There are **no public REST API endpoints** for missions registered under `routes/api.php`.  
However, the system provides **internal JSON endpoints** consumed by other modules:

### 15.1 AJAX: Mission Details for Report Creation
- **URL:** `GET /admin/reports/mission-details/{mission}`
- **Route Name:** `admin.reports.mission_details`
- **Controller Action:** `App\Http\Controllers\Admin\ReportController@getMissionDetails`
- **Middleware:** `auth, checkStatus`
- **Purpose:** Fetches mission reference, site details, and list of reportable site instruments for loop calibration reports.

### 15.2 AJAX: Mission Equipment for Calibrations
- **URL:** `GET /admin/movements/get-equipments/{missionId}`
- **Route Name:** `admin.movements.get_equipments`
- **Controller Action:** `App\Http\Controllers\Admin\MovementEquipmentController@getEquipmentsByMission`
- **Purpose:** Returns list of deployed equipment under the mission that require laboratory calibration.

---

## 16. Request Structures

### 16.1 Create Mission Form Payload (`POST /admin/missions`)
```json
{
  "_token": "CSRF_TOKEN_STRING",
  "start_date": "2026-10-01",
  "end_date": "2026-10-10",
  "mob_dmob_days": "2",
  "contract_id": 4,
  "site_id": 12,
  "vehicle_id": 18,
  "employees": [
    { "id": 5, "is_leader": 1 },
    { "id": 8, "is_leader": 0 }
  ],
  "equipments": [
    { "id": 22 },
    { "id": 35 }
  ]
}
```

### 16.2 Update Mission Form Payload (`PUT /admin/missions/{id}`)
```json
{
  "_token": "CSRF_TOKEN_STRING",
  "_method": "PUT",
  "start_date": "2026-10-01",
  "end_date": "2026-10-12",
  "mob_dmob_days": "2",
  "contract_id": 4,
  "site_id": 12,
  "vehicle_id": 18,
  "employees": [5, 8],
  "chief_id": 5,
  "equipments": [22, 35],
  "description": "Optional notes"
}
```

---

## 17. Response Structures

### 17.1 Standard Web Controller Responses
All state-modifying actions return `Illuminate\Http\RedirectResponse`:
- **Success:** `redirect()->route(...)->with('success', __('translation_key'))`
- **Validation Failure:** Automatic 302 redirect back with `$errors` bag and `old()` inputs.
- **Resource Conflict:** `redirect()->back()->with('conflict_error', $e->getMessage())`

### 17.2 Report Mission Details JSON Response (`admin.reports.mission_details`)
```json
{
  "mission": {
    "id": 14,
    "reference": "M-2026-004"
  },
  "site": {
    "id": 3,
    "name": "Hassi Messaoud North",
    "short_name": "HMD-N",
    "full_name": "Hassi Messaoud North Production Facility",
    "site_code": "HMD-01",
    "location": "Ouargla, Algeria"
  },
  "instruments": [
    {
      "id": 102,
      "tag_number": "PT-1001",
      "serial_number": "SN-982341",
      "instrument_type": "Transmitter",
      "image_path": "pt_1001.jpg",
      "image_url": "http://domain.test/assets/img_instruments/pt_1001.jpg",
      "status": "active",
      "has_data": false
    }
  ],
  "calibrators": []
}
```

---

## 18. Filtering Architecture

`[Confirmed]` Located in `MissionController::index()`:
```text
User / Browser Filter Form
         │
         ▼ (HTTP GET /admin/missions?site_id=...&contract_id=...&date_from=...&date_to=...&search=...)
MissionController::index()
         │
         ├── where('site_id', $request->site_id)
         ├── where('contract_id', $request->contract_id)
         ├── whereDate('start_date', '>=', $request->date_from)
         ├── whereDate('end_date', '<=', $request->date_to)
         └── where(search):
                 whereHas('equipments', full_name LIKE %search%)
                 orWhereHas('employees', full_name LIKE %search%)
         │
         ▼
orderByDesc('start_date') -> orderByDesc('created_at')
         │
         ▼
paginate(10)->withQueryString()
```

---

## 19. Pagination Architecture

- **Page Size:** Fixed at `10` records per page (`paginate(10)`).
- **Query Preservation:** Utilizes `->withQueryString()` to maintain active filter parameters across pagination navigation.
- **Frontend Template:** Rendered via `$missions->links('vendor.pagination.custom')`.
- **Metadata Available in View:** `$missions->total()`, `$missions->currentPage()`, `$missions->lastPage()`.

---

## 20. Frontend Components & Templates

| View Path | Purpose | Key UI Controls & Libraries |
| :--- | :--- | :--- |
| `resources/views/admin/missions/index.blade.php` | Main listings, KPI badge counts, filter bar, table actions | Filter form, status badges, action icons, SweetAlert delete confirmation |
| `resources/views/admin/missions/create.blade.php` | Multi-select creation form with leader selection | `Choices.js` multi-select, Dynamic Site filter linked to Contract, JS hidden input generator |
| `resources/views/admin/missions/edit.blade.php` | Mission modification form | `Choices.js`, Chief radio selector, quick package lot selectors (`Lot 01`, `Lot 02`) |
| `resources/views/admin/missions/show.blade.php` | A4 document print preview & inspection sheet | Status stamp (`stamp-planned`, `stamp-active`, `stamp-completed`), team table, equipment table |
| `resources/views/admin/missions/employees.blade.php` | Assigned employee roster management | Table with Leader star badge, link to print travel order, link to edit itinerary |
| `resources/views/admin/missions/equipments.blade.php` | Assigned technical equipment & vehicles | Vehicle segregation partition (`$mission->equipments->partition(...)`), certificate link |
| `resources/views/admin/missions/edit_order.blade.php` | Update itinerary and vehicle assignment | Itinerary input, custom start/end date overrides, vehicle dropdown, `all_vehicles` toggle |
| `resources/views/admin/missions/print_order.blade.php` | Official French travel order (*Ordre de Mission*) | A4 print stylesheet, official header logo, accompanists formatter, sign-off stamp blocks |
| `resources/views/admin/missions/statistics.blade.php` | Unit Economics & financial profitability dashboard | Glassmorphic KPI cards, annual cost breakdowns, profit margin meters, year selector |

---

## 21. Mission List Flow

```text
[ Browser ]
    │  GET /admin/missions
    ▼
[ routes/web.php ] ──▶ Middleware: ['auth', 'checkStatus', 'permission:view missions']
    │
    ▼
[ MissionController@index ]
    │  1. Fetches Sites: Site::orderBy('short_name')->get()
    │  2. Fetches Active Contracts: Contract::active()->orderBy('reference')->get()
    │  3. Builds Query with eager-loading:
    │     Mission::with(['site', 'contract', 'attachments', 'employees', 'equipments'])
    │  4. Applies conditional filters (site, contract, dates, search)
    │  5. Executes pagination: paginate(10)->withQueryString()
    │
    ▼
[ View: admin.missions.index ]
    │  Renders table with Status badges, actions, and KPI counts
    ▼
[ Browser HTML Rendered ]
```

---

## 22. Mission Create Flow

```text
[ User clicks "Create Mission" ]
    │  GET /admin/missions/create
    ▼
[ MissionController@create ]
    │  Provides: sites, active contracts, active employees, active equipment, active vehicles
    ▼
[ Browser renders create.blade.php ]
    │  1. User selects Contract -> JS auto-filters Sites matching contract's customer_id
    │  2. User selects multiple Employees via Choices.js
    │  3. JS dynamically generates Radio Buttons for each selected employee
    │  4. User marks exactly ONE radio button as Chief
    │  5. User selects transport vehicle and equipment
    │  6. Form submission intercepted by JS: generates hidden array inputs
    │
    ▼ POST /admin/missions
[ StoreMissionRequest ]
    │  Validates fields; withValidator checks count(is_leader == true) === 1
    ▼
[ MissionController@store ]
    │  Calls CreateMissionUseCase::execute($request->validated())
    ▼
[ CreateMissionUseCase ]
    │  DB::transaction:
    │  1. $ref = MissionReferenceGenerator::generate() -> M-2026-001
    │  2. Resolves destination landmark from Site.location
    │  3. Mission::create([...])
    │  4. Attaches employees with composite destination, daily_rate, and dates to mission_orders
    │  5. Attaches vehicle and equipment to deployments
    ▼
[ MissionObserver@created ]
    │  Dispatches notification to users with 'view missions' permission
    ▼
[ RedirectResponse to admin.missions.index with session('success') ]
```

---

## 23. Mission Update Flow

```text
[ User clicks "Edit Mission" ]
    │  GET /admin/missions/{id}/edit
    ▼
[ MissionController@edit ]
    │  Loads existing mission with employees and equipments
    │  Extracts vehicle from equipments collection (category == 'Vehicle')
    │  Passes existing employee IDs and leader ID to view
    ▼
[ Browser renders edit.blade.php ]
    │  User modifies dates, site, team, or equipment
    ▼ PUT /admin/missions/{id}
[ UpdateMissionRequest ]
    │  Validates input, checks chief_id in_array:employees.*
    ▼
[ MissionController@update ]
    │  Calls UpdateMissionUseCase::execute($id, $request->validated())
    ▼
[ UpdateMissionUseCase ]
    │  1. Guards state: throws exception if status is 'completed' or 'cancelled'
    │  2. DB::transaction:
    │     - Updates mission attributes
    │     - Synchronizes employees via sync(), preserving custom pivot dates
    │     - Synchronizes equipment and vehicles via sync()
    ▼
[ MissionObserver@updated ]
    │  Dispatches notification
    ▼
[ RedirectResponse to admin.missions.index with session('success') ]
```

---

## 24. Mission Delete Flow

```text
[ User clicks "Delete" on planned mission ]
    │  DELETE /admin/missions/{id}
    ▼
[ MissionController@destroy ]
    │  Calls DeleteMissionUseCase::execute($id)
    ▼
[ DeleteMissionUseCase ]
    │  DB::transaction:
    │  1. DB::table('deployments')->where('mission_id', $id)->delete()
    │  2. DB::table('mission_orders')->where('mission_id', $id)->delete()
    │  3. $mission->delete()
    ▼
[ Database Foreign Key Evaluation ]
    │  * If mission has linked reports -> MySQL triggers QueryException (RESTRICT)
    │  * If mission has attachments -> MySQL cascades deletion
    │  * If mission has charges -> MySQL cascades deletion
    ▼
[ MissionObserver@deleted ]
    │  Dispatches notification
    ▼
[ RedirectResponse back with success or error ]
```

---

## 25. Mission Orders Subsystem

The `mission_orders` table bridges Missions and Employees while managing official Algerian administrative travel orders:

1. **Sequential Order Reference:** Generated dynamically via `GenerateMissionOrderReferenceUseCase`:
   - Prefix: `ALG`
   - Format: `sprintf('%03d/%s/%02d', $number, 'ALG', $year)` $\to$ e.g. `001/ALG/26`.
   - Concurrency lock: `lockForUpdate()`.
2. **Dynamic Destination Formatting:**
   - Stored in `destination`.
   - Automatically generated on creation as:
     `"{Employee Address} - {Site Location} - {Employee Address}"` (e.g. `"Alger - Hassi Messaoud - Alger"`).
3. **Vehicle Assignment:**
   - Individual equipment item of category `'Vehicle'` stored in `vehicle_id`.
   - If `all_vehicles == 1`, the travel order prints `"Tous les véhicules de l'entreprise"` granting universal fleet driving authorization.
4. **Accompanists Calculation:**
   - Calculated in `MissionOrderController::getAccompanistsText()`:
   - Queries other employees on the same mission (`where('employee_id', '!=', $employeeId)`).
   - Formatted as `"Name 1 / Name 2"` or `"Seul"` (Alone) if travelling unaccompanied.

---

## 26. Site Relationship

- **Foreign Key:** `missions.site_id` $\to$ `sites.id`.
- **Cardinality:** Site (1) to Missions (N).
- **Mandatory vs Nullable:** Nullable in database schema (`site_id INT NULL`), but strictly enforced as `required` in `StoreMissionRequest` and `UpdateMissionRequest`.
- **Delete Constraint:** `ON DELETE NO ACTION (RESTRICT)`. A site cannot be deleted while missions are attached to it.
- **Eager Loading:** Loaded in `MissionController::index` via `with(['site'])`.
- **Domain Role:** Supplies physical geography, short name for badges, and site `location` for travel order itineraries.

---

## 27. Contract Relationship

- **Foreign Key:** `missions.contract_id` $\to$ `contracts.id`.
- **Cardinality:** Contract (1) to Missions (N).
- **Mandatory vs Nullable:** Nullable in both database and form requests (`nullable|exists:contracts,id`). Missions can run without an attached contract.
- **Delete Constraint:** `ON DELETE CASCADE`. If a contract is deleted, linked missions cascade delete.
- **Filtering Scope:** `Contract::active()` filters contracts where:
  $$\text{date\_signature} + \text{duree (months)} \ge \text{today}$$
- **UI Dynamic Filtering:** In `create.blade.php`, selecting a contract automatically restricts the site dropdown to sites sharing the contract's `customer_id`.

---

## 28. Access Route Relationship (Audit & Verification)

`[Confirmed Database Audit]`
1. **Database Schema:** The table `access_routes` **does not exist** in the active MySQL database.
2. **Missions Table:** The column `access_route_id` **does not exist** in table `missions`.
3. **Model:** There is **no** `accessRoute()` relationship defined in `App\Models\Mission`.
4. **Historical Artifacts:**
   - `_ide_helper_models.php` contains obsolete property docblocks `@property int|null $access_route_id`.
   - `tests/Feature/Controllers/Admin/MissionControllerTest.php` contains legacy mock assignment `'access_route_id' => $this->route->id`.
5. **Conclusion:** Access Routes is a decommissioned module. Geographic routing has been replaced by the `location` and `map_link` fields located directly on the `sites` table.

---

## 29. Attachments Relationship & Revenue Flow

- **Foreign Key:** `attachments.mission_id` $\to$ `missions.id` (Constraint `fk_attachments_missions`, `ON DELETE CASCADE`).
- **Cardinality:** Mission (1) to Attachments (N).
- **Revenue Calculation Chain:**
  ```text
  Mission
    │
    ▼ (1:N)
  attachments (Status: APPROVED)
    │
    ▼ (1:N)
  attachment_items
    │
    ▼ (N:1 via item_contract_id)
  item_contracts (unit_price)
  ```
- **Formula:**
  $$\text{Mission Gross Revenue} = \sum (\text{attachment\_items.actual\_quantity} \times \text{item\_contracts.unit\_price})$$

---

## 30. Reports Relationship & Metrological Chain

- **Foreign Key:** `reports.mission_id` $\to$ `missions.id` (Constraint `reports_ibfk_1`, `ON DELETE NO ACTION`).
- **Cardinality:** Mission (1) to Reports (N).
- **Metrological Cascade:** Creating a `Report` for a Mission triggers `Report::initializeVerificationPoints()`:
  - Discovers site instruments (`mission.site.instruments`).
  - Auto-initializes verification test runs for:
    1. `transmitter_verifications` (10-point calibration cycle).
    2. `probe_verifications` (Temperature calibration points).
    3. `flow_computer_verifications` (ADC channel simulations).
- **Exclusions:** Gas Chromatographs (`Chromatograph`), Pipe Provers (`Prover`), and Standard Gauges (`StandardGauge`) are permanently excluded from standard reports and managed in dedicated sub-systems.

---

## 31. Charges Relationship & Cost Accounting

- **Foreign Key:** `charges.mission_id` $\to$ `missions.id` (Constraint `fk_charges_missions`, `ON DELETE CASCADE`).
- **Cardinality:** Mission (1) to Charges (N).
- **Direct Expense Tracking:**
  $$\text{Direct Mission Expenses} = \sum \text{charges.amount where mission\_id} = M$$
- **UI Integration:** The Mission listing view exposes an action button (`add_charge`) directly linking to `admin.charges.create?mission_id={id}` for active missions.

---

## 32. Authentication & Authorization Guard Rails

1. **Authentication Guard:** Laravel Breeze session authentication via `Route::middleware(['auth', 'checkStatus'])`. Inactive users (`status != 'active'`) are barred from accessing missions.
2. **Spatie Permission Names:**
   - `view missions`
   - `create missions`
   - `edit missions`
   - `delete missions`
   - `view statistics`
3. **Role Seed Assignments:**
   - `manager`: Full permissions (`view`, `create`, `edit`, `delete`, `statistics`).
   - `senior_engineer`: Read and execute permissions (`view missions`).
   - `engineer`: Limited access depending on explicit assignment.

---

## 33. Validation Suite

### Technical vs Business Validation Comparison

| Field | Technical Validation | Business Validation Rule |
| :--- | :--- | :--- |
| `site_id` | `required\|exists:sites,id` | Must exist in active installations |
| `contract_id` | `nullable\|exists:contracts,id` | If provided, must match valid client contract |
| `start_date` | `required\|date` | Format must be valid ISO date |
| `end_date` | `required\|date` | Must be $\ge$ `start_date` (`after_or_equal:start_date`) |
| `mob_dmob_days` | `required\|numeric\|min:0` | Cannot be negative |
| `employees` | `required\|array\|min:1` | Mission must have at least one assigned team member |
| `employees.*.is_leader` | `required\|boolean` | Exactly ONE leader must be selected across the entire array |
| `chief_id` (on update) | `required\|exists:employees,id` | The designated chief must be included within the `employees` array |
| `vehicle_id` | `nullable\|exists:equipment,id` | Transport vehicle must exist in equipment catalog |
| `equipments` | `nullable\|array` | Assigned equipment must be valid equipment records |

---

## 34. Business Rules Inventory

| Rule Ref | Rule Description | Enforcing Location | Trigger | System Effect |
| :--- | :--- | :--- | :--- | :--- |
| **BR-01** | Single Mission Leader | `StoreMissionRequest::withValidator` | Mission Store Submission | Halts execution if leader count $\ne 1$ |
| **BR-02** | Leader Team Inclusion | `UpdateMissionRequest::rules` | Mission Update Submission | Validates `chief_id in_array:employees.*` |
| **BR-03** | Chronological Integrity | Form Requests (`after_or_equal`) | Store / Update Submission | Rejects end date preceding start date |
| **BR-04** | Closed Mission Immutability | `UpdateMissionUseCase` | Update Attempt | Throws exception if status is `completed` or `cancelled` |
| **BR-05** | Activation State Guard | `ActivateMissionUseCase` | Activate Attempt | Throws exception if status $\ne$ `planned` |
| **BR-06** | Human Overlap Conflict Guard | `ActivateMissionUseCase` | Activate Execution | Detects concurrent active missions for assigned employees; aborts activation |
| **BR-07** | Asset Overlap Conflict Guard | `ActivateMissionUseCase` | Activate Execution | Detects concurrent active missions for assigned equipment/cars; aborts activation |
| **BR-08** | Completion State Guard | `CompleteMissionUseCase` | Complete Execution | Throws exception if status $\ne$ `active` |
| **BR-09** | Asset Release on Completion | `CompleteMissionUseCase` | Complete Execution | Updates `deployments.status` to `'returned'` freeing equipment |
| **BR-10** | Autonomous Reference Generation | `MissionReferenceGenerator` | Create Execution | Generates unique formatted sequence `M-YYYY-NNN` |
| **BR-11** | Travel Order Concurrency Lock | `GenerateMissionOrderReferenceUseCase` | Print Order Execution | Locks row using `lockForUpdate()` preventing duplicate order numbers |
| **BR-12** | Report Deletion Protection | MySQL Foreign Key `reports_ibfk_1` | Delete Execution | Rejects mission deletion if loop reports exist (`RESTRICT`) |

---

## 35. Exception Handling Architecture

1. **`App\Exceptions\MissionConflictException`:**
   - Inherits from `\Exception`.
   - Constructs localized, multiline error listing specific employees and equipment locked in overlapping missions.
   - Handled in `MissionController@activate`: caught and flashed to session as `conflict_error`. Rendered in `index.blade.php` as a prominent dismissible alert.
2. **`Illuminate\Database\Eloquent\ModelNotFoundException`:**
   - Caught in `MissionOrderController@update` when updating non-existent mission orders. Logged and redirected with warning.
3. **`Illuminate\Database\QueryException`:**
   - Triggered when attempting to delete a mission with existing reports (due to `reports_ibfk_1` RESTRICT constraint). Handled gracefully with error flash.
4. **General `\Exception`:**
   - Caught in controller try-catch blocks; redirects back with `error` flash message containing `$e->getMessage()`.

---

## 36. HTTP Status Codes

| Operation | HTTP Status | Context / Meaning |
| :--- | :---: | :--- |
| `GET /admin/missions` | `200 OK` | Successful page render |
| `POST /admin/missions` | `302 Found` | Successful store; redirects to index |
| `PUT /admin/missions/{id}` | `302 Found` | Successful update; redirects to index |
| `DELETE /admin/missions/{id}` | `302 Found` | Successful delete; redirects back |
| `PATCH /admin/missions/{id}/activate` | `302 Found` | Successful state transition; redirects back |
| `Validation Failure` | `302 Found` | Laravel validation redirect back with input and errors |
| `GET /admin/missions/{invalid_id}` | `404 Not Found` | Handled by Eloquent `findOrFail()` |
| `Unauthorized Access` | `403 Forbidden` | Triggered by Spatie permission middleware if user lacks permission |
| `Unauthenticated Access` | `302 Found` | Redirects to `/login` |

---

## 37. Source of Truth Matrix

| Attribute / Metric | Definitive Source of Truth | Location / Calculation |
| :--- | :--- | :--- |
| Mission Reference | Database Column | `missions.reference` |
| Mission Status | Database Column | `missions.status` (Enum: `MissionStatus`) |
| Start / End Dates | Database Columns | `missions.start_date`, `missions.end_date` |
| Transit / Mob Days | Database Column | `missions.mob_dmob_days` |
| Installation Name | Database Column | `sites.full_name` / `sites.short_name` |
| Site Location | Database Column | `sites.location` |
| Contract Code | Database Column | `contracts.reference` |
| Team Leader Flag | Pivot Column | `mission_orders.is_leader` |
| Employee Per-Diem | Pivot Column | `mission_orders.daily_rate` (Snapshot from `employees.daily_rate`) |
| Travel Order Number | Pivot Column | `mission_orders.order_reference` |
| Travel Itinerary | Pivot Column | `mission_orders.destination` |
| Vehicle Assignment | Pivot Column | `mission_orders.vehicle_id` |
| Tool Custody Status | Pivot Column | `deployments.status` |
| Total Paid Days | Calculated Aggregate | `sum(DATEDIFF(ended_at, started_at) + 1)` from `mission_orders` |
| Direct Mission Cost | Calculated Aggregate | `sum(amount)` from `charges where mission_id = M` |
| Mission Revenue | Calculated Aggregate | `sum(actual_quantity * unit_price)` via `attachments` & `attachment_items` |
| Net Mission Profit | Domain Calculation | $\text{Gross Profit} - (\text{Duration} \times \text{Daily Office Expense Rate})$ |

---

## 38. Dependency Map

```text
[ MissionController ]
       │
       ├── imports ──▶ [ StoreMissionRequest / UpdateMissionRequest ]
       │
       ├── invokes ──▶ [ CreateMissionUseCase ] ──▶ [ MissionReferenceGenerator ]
       │                                         ──▶ [ Mission ]
       │                                         ──▶ [ Employee ]
       │                                         ──▶ [ Site ]
       │
       ├── invokes ──▶ [ UpdateMissionUseCase ] ──▶ [ Mission ]
       │
       ├── invokes ──▶ [ ActivateMissionUseCase ] ──▶ [ MissionConflictException ]
       │
       ├── invokes ──▶ [ CompleteMissionUseCase ] ──▶ [ Mission ]
       │
       ├── invokes ──▶ [ RevertMissionUseCase ] ──▶ [ MissionConflictException ]
       │
       ├── invokes ──▶ [ DeleteMissionUseCase ] ──▶ [ Mission ]
       │
       ├── invokes ──▶ [ MissionStatisticsService ] ──▶ [ Mission ]
       │                                            ──▶ [ charges ]
       │                                            ──▶ [ attachments / items ]
       │                                            ──▶ [ mission_orders ]
       │
       └── observes ─▶ [ MissionObserver ] ──▶ [ NotificationService ]
```

---

## 39. End-to-End Execution Traces

### 39.1 Trace: Activating a Planned Mission (`PATCH /admin/missions/{id}/activate`)
1. User clicks Play icon button on `index.blade.php`.
2. Form submits `PATCH /admin/missions/{id}/activate`.
3. Middleware `permission:edit missions` validates user rights.
4. `MissionController@activate` resolves `$id` and injects `ActivateMissionUseCase`.
5. `ActivateMissionUseCase::execute($id)` executes:
   - Queries `Mission::with(['employees', 'equipments'])->findOrFail($id)`.
   - Asserts `$mission->status->value === 'planned'`.
   - Queries `mission_orders` joined with `missions` for any employee conflict during dates.
   - Queries `deployments` joined with `missions` for any equipment/vehicle conflict during dates.
   - If clean: `DB::transaction` updates `missions.status = 'active'`, `mission_orders.status = 'active'`, `deployments.status = 'active'`.
6. `MissionController@syncRelatedMovements` executes:
   - Finds related `movement_equipment` records and syncs status to `PhaseCheckin`.
7. `MissionObserver@updated` intercepts commit:
   - Detects `wasChanged('status')`.
   - Dispatches notification to `'view missions'` group.
8. Controller returns `back()->with('success', ...)`.

---

## 40. Mission Lifecycle & State Machine

```text
       [ CREATE MISSION ]
               │
               ▼
       ┌───────────────┐
       │    PLANNED    │ ◄─────────────────────────┐
       └───────┬───────┘                           │
               │                                   │
               │ [ Activate Action ]               │
               │ (Conflict Check: OK)              │
               │                                   │
               ▼                                   │
       ┌───────────────┐                           │ [ Revert Action ]
       │    ACTIVE     │                           │ (Conflict Check: OK)
       └───────┬───────┘                           │
               │                                   │
               │ [ Complete Action ]               │
               │ (Releases Assets)                 │
               │                                   │
               ▼                                   │
       ┌───────────────┐                           │
       │   COMPLETED   │ ──────────────────────────┘
       └───────────────┘
```

### State Definitions & Transitions
1. **`planned` (مخطط لها):**
   - Initial state upon creation.
   - Resources (employees, vehicles, equipment) are reserved but not active.
   - Modifiable via `admin.missions.edit`.
   - Removable via `admin.missions.destroy`.
2. **`active` (نشطة):**
   - Triggered via `ActivateMissionUseCase`.
   - Personnel and assets locked against double-booking.
   - Field operations ongoing. Reports and charges can be logged.
   - Modification blocked.
3. **`completed` (مكتملة):**
   - Triggered via `CompleteMissionUseCase`.
   - Personnel and equipment released back to active inventory pool.
   - Final financial accounting computed via `MissionStatisticsService`.

---

## 41. Architectural Observations

1. **Clean Use Case Separation:** The extraction of business actions (`CreateMissionUseCase`, `ActivateMissionUseCase`, etc.) from the controller is an excellent architectural achievement that keeps the controller lean and prevents fat controllers.
2. **Temporal Conflict Detection:** The implementation of two-dimensional date overlap checking for both human engineers and technical hardware before activation demonstrates mature enterprise domain modeling.
3. **Audit & Notification Decoupling:** Using `MissionObserver` with `$afterCommit = true` guarantees notifications and audit logs are only dispatched after MySQL successfully commits transactions.
4. **Unit Economics Depth:** `MissionStatisticsService` offers an advanced financial engine calculating operational gross margins and factoring corporate office absorption rates.

---

## 42. Technical Debt & Critical Anomalies

### 42.1 Critical Bug: Phantom `description` Column
- **Location:** `app/Http/Requests/Mission/UpdateMissionRequest.php` (line 44) & `app/UseCases/Mission/UpdateMissionUseCase.php` (line 33).
- **Issue:** The update request allows `'description' => 'nullable|string|max:1000'`, and `UpdateMissionUseCase` passes `'description' => $data['description'] ?? null` to `$mission->update()`.
- **Database Status:** The `missions` table **does not have a `description` column**.
- **Impact:** Since `Mission::$guarded = ['id']`, passing `description` will trigger `QueryException: 1054 Unknown column 'description' in 'field list'` if populated.

### 42.2 Critical Bug: Empty String Status & Inverted Revert Logic
- **Location:** `app/UseCases/Mission/RevertMissionUseCase.php` (lines 26, 40, and 52).
- **Issue 1:** When checking if employees or equipment are busy, lines 26 & 40 execute:
  `->where('missions.status', '')` instead of `->where('missions.status', 'active')`! Comparing against empty string means conflict detection on revert is completely bypassed.
- **Issue 2:** Line 52 sets `$mission->update(['status' => 'planned'])`, whereas the method docblock and controller assume it reverts to `'active'`.

### 42.3 Request Payload Asymmetry
- **Create:** `StoreMissionRequest` expects `employees` as an array of objects `[['id' => 1, 'is_leader' => 1]]`.
- **Update:** `UpdateMissionRequest` expects `employees` as a flat array of IDs `[1, 2]` with a separate `chief_id` field.
- **Architectural Debt:** Unnecessary cognitive load and lack of symmetry between store and update APIs.

### 42.4 Cascade Deletion Trap with Reports
- **Location:** `DeleteMissionUseCase`.
- **Issue:** The use case manually deletes `deployments` and `mission_orders`, then calls `$mission->delete()`. However, the foreign key on `reports` (`reports_ibfk_1`) has `ON DELETE NO ACTION` (RESTRICT). If any report exists for the mission, deletion crashes with a foreign key constraint violation.

### 42.5 Table Column Alignment Defect in Index View
- **Location:** `resources/views/admin/missions/index.blade.php` (line 303).
- **Issue:** The empty state table cell declares `<td colspan="5">`, but the table header defines **6 columns** (`Reference`, `Site`, `Contract`, `Dates`, `Status`, `Actions`).

### 42.6 Legacy Access Route Artifacts
- **Location:** `_ide_helper_models.php` & `tests/Feature/Controllers/Admin/MissionControllerTest.php`.
- **Issue:** Dead references to `access_route_id` and `AccessRoute` persist in test fixtures and IDE helpers despite the table and column being purged from the application schema.

---

## 43. Final Architecture Summary

The **Missions Module** represents an enterprise-grade operational workflow system engineered to orchestrate field calibration campaigns. Its data model maintains strict relational integrity across client installations (`sites`), commercial obligations (`contracts`), personnel deployments (`mission_orders`), and precision equipment (`deployments`).

### Architecture Strengths
- Solid transaction boundaries (`DB::transaction`).
- High-level conflict detection preventing double-booking of field engineers or calibration standards.
- Sophisticated financial analytics measuring real-time profitability and team per-diems.

### Immediate Action Items for Engineering
1. Remove `'description'` from `UpdateMissionRequest` and `UpdateMissionUseCase` (or execute a migration to add it to `missions`).
2. Correct `RevertMissionUseCase` to check `where('missions.status', 'active')` and clarify the target reverted state.
3. Clean up legacy `access_route_id` references from `MissionControllerTest.php`.
4. Add pre-flight checks in `DeleteMissionUseCase` to guard against deletion when reports exist.
5. Standardize table styling and button tokens to conform to the project's Unified Design System (`<x-table>` and `<x-primary-button>`).
