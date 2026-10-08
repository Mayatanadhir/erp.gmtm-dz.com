# Contract Code Details

> **مستوى التوثيق:** Code-Level Technical Reference  
> **المشروع:** ENGI-GMTM (app.gmtm-dz.com)  
> **Laravel:** 13.x · **PHP:** 8.4+  
> **تاريخ التحليل:** 2026-09-26  
> **المحلِّل:** Senior Software Architect — Static Code Analysis  

---

## 1. Executive Overview

Module **Contracts** هو وحدة إدارة العقود التجارية داخل تطبيق ENGI-GMTM.  
يمثّل العقد الوثيقة القانونية المُبرمة بين شركة GMTM وعميل (Customer)، ويحدّد نطاق الخدمات المُقدَّمة والمبالغ المالية والمدّة الزمنية.

**الموقع المعماري:**
- **Backend:** `app/Http/Controllers/Admin/ContractController.php`
- **Views:** `resources/views/admin/contracts/`
- **Routes:** `routes/web.php` — prefix `admin/contracts`, name prefix `admin.contracts.`
- **Model:** `app/Models/Contract.php`
- **Observer:** `app/Observers/ContractObserver.php`
- **Service:** `app/Services/ContractStatisticsService.php`
- **Requests:** `app/Http/Requests/Contract/`

**لا يوجد:**
- Repository Pattern ❌
- API Resource ❌ (Web-only Blade application)
- REST API endpoints للعقود ❌ (routes/api.php لا تحتوي عقوداً)
- Soft Deletes ❌

---

## 2. Contract Domain Overview

### ما هو العقد داخل النظام؟

العقد (`Contract`) هو كيان تجاري يربط شركة GMTM بعميل (`Customer`) لتقديم خدمات محدودة المدّة والقيمة.

### المعلومات الأساسية (Confirmed)
- **Reference:** معرّف فريد للعقد (UNI في قاعدة البيانات)
- **Object:** موضوع/وصف العقد
- **Date Signature:** تاريخ التوقيع
- **Duree:** المدة بالأشهر
- **Montant Global Prevu:** المبلغ الإجمالي المخطَّط

### من ينشئه؟ (Confirmed)
المستخدمون الذين لديهم صلاحية `create contracts`.

### من يعدّله؟ (Confirmed)
المستخدمون الذين لديهم صلاحية `edit contracts`.

### الكيانات المرتبطة (Confirmed)

```
                    CUSTOMER
                  /    |    \
                 /     |     \
                ▼      |      ▼
          CONTRACTS  SITES   GARANTIES
          /   |  \     
         /    |   \
        ▼     ▼    ▼
   GARANTIE ITEMS MISSIONS
              │       │
              ▼       ▼
         ITEM_TYPES  CHARGES
              │       │
              ▼       ▼
         ATTACHMENT ATTACHMENTS
            ITEMS      │
                       ▼
                  ATTACHMENT_ITEMS
```

**ملاحظة معمارية مهمة:** `Contract` ليس مرتبطاً بـ `Site` مباشرةً في قاعدة البيانات. لا يوجد عمود `site_id` في جدول `contracts`. العلاقة مع Site تمرّ عبر `Customer → sites`. الكود يحتوي على تعليق بهذا الشأن (سيتم توضيحه في §10).

---

## 3. Folder Structure

```
contracts module/
├── app/
│   ├── Http/
│   │   ├── Controllers/Admin/
│   │   │   └── ContractController.php        ← Main controller (final class)
│   │   └── Requests/Contract/
│   │       ├── StoreContractRequest.php       ← Validation for create
│   │       └── UpdateContractRequest.php      ← Validation for update
│   ├── Models/
│   │   ├── Contract.php                       ← Eloquent model
│   │   └── ItemContract.php                   ← Pivot/child model
│   ├── Observers/
│   │   └── ContractObserver.php               ← CRUD event → Notification
│   ├── Services/
│   │   └── ContractStatisticsService.php      ← Financial stats calculation
│   └── Enums/
│       └── BillingCycle.php                   ← annuelle | semestrielle
│
├── resources/views/admin/contracts/
│   ├── index.blade.php                        ← List + filters
│   ├── create.blade.php                       ← Create form
│   ├── edit.blade.php                         ← Edit form
│   ├── show.blade.php                         ← Detail page
│   └── statistics.blade.php                   ← Financial statistics
│
├── routes/web.php                             ← Route definitions (lines 254-276)
│
└── config/
    └── permissions_groups.php                 ← RBAC group: contrat_management
```

---

## 4. Architecture Overview

```
Browser (Blade)
      │
      ▼ HTTP Request
routes/web.php  [middleware: auth, checkStatus, permission:*]
      │
      ▼ Route → Controller
ContractController  [final class, no explicit Policy file]
      │
      ├── StoreContractRequest / UpdateContractRequest  [Form Request Validation]
      │
      ├── Contract::query() / Contract::create()        [Direct Eloquent, no Repository]
      │
      ├── ContractStatisticsService                     [Service — statistics only]
      │
      └── DB::transaction()                             [Manual transactions for CRUD]
            │
            ▼
        MySQL (contracts, item_contracts, ...)
            │
            ▼ Observer (after commit)
        ContractObserver → NotificationService → Users with 'view contracts'
```

**No Repository Pattern:** موثَّق صراحةً في `AppServiceProvider.php` (line 60):  
> "تم تفريغ هذه الدالة لأن النظام يعتمد على UseCases و Models مباشرة ولا يستخدم نمط Repositories الخارجي"

---

## 5. Contract Data Model

### جدول `contracts` (Confirmed from docs/database/database-schema.md)

| Field | Type | Nullable | Default | Key | Source | Purpose |
|-------|------|----------|---------|-----|--------|---------|
| `id` | `int` | No | NULL | PRI (auto_increment) | Database | Primary identifier |
| `reference` | `varchar(110)` | Yes | NULL | UNI | User input | Unique contract identifier |
| `object` | `varchar(200)` | Yes | NULL | — | User input | Contract subject/description |
| `date_signature` | `date` | Yes | NULL | — | User input | Contract signing date |
| `duree` | `int` | Yes | NULL | — | User input | Duration in **months** |
| `montant_global_prevu` | `decimal(15,2)` | Yes | NULL | — | User input | Planned global amount (DA) |
| `customer_id` | `int` | Yes | NULL | MUL | FK → customers | Client FK |
| `garantie_id` | `int` | Yes | NULL | MUL | FK → garanties | Bank guarantee FK |
| `created_at` | `timestamp` | Yes | NULL | — | Eloquent | Created timestamp |
| `updated_at` | `timestamp` | Yes | NULL | — | Eloquent | Updated timestamp |

**غائب:** لا يوجد `site_id`، لا يوجد `status` enum، لا يوجد `deleted_at` (لا soft delete).

---

## 6. Database Schema

### جدول `contracts`

| Column | Type | Nullable | Default | Key | Relationship | Description |
|--------|------|----------|---------|-----|--------------|-------------|
| `id` | `int` | No | — | PRI | — | Auto-increment PK |
| `reference` | `varchar(110)` | Yes | NULL | UNI | — | Unique contract ref |
| `object` | `varchar(200)` | Yes | NULL | — | — | Contract object |
| `date_signature` | `date` | Yes | NULL | — | — | Signature date |
| `duree` | `int` | Yes | NULL | — | — | Duration in months |
| `montant_global_prevu` | `decimal(15,2)` | Yes | NULL | — | — | Planned global amount |
| `customer_id` | `int` | Yes | NULL | MUL | `customers.id` | Client FK |
| `garantie_id` | `int` | Yes | NULL | MUL | `garanties.id` | Guarantee FK |
| `created_at` | `timestamp` | Yes | NULL | — | — | Timestamps |
| `updated_at` | `timestamp` | Yes | NULL | — | — | Timestamps |

**Foreign Keys:**
- `customer_id` → `customers.id` (`fk_contract_sites` — اسم مُضلِّل في قاعدة البيانات)
- `garantie_id` → `garanties.id` (`fk_contract_garanties`)

**Indexes:** `UNI` on `reference`, `MUL` on `customer_id`, `MUL` on `garantie_id`

**لا يوجد:**  Soft deletes, status column, site_id column

---

### جدول `item_contracts`

| Column | Type | Nullable | Default | Key | Relationship | Description |
|--------|------|----------|---------|-----|--------------|-------------|
| `id` | `int` | No | — | PRI | — | Auto-increment PK |
| `contract_id` | `int` | Yes | NULL | MUL | `contracts.id` | Parent contract FK |
| `item_type_id` | `int` | Yes | NULL | MUL | `item_types.id` | Item type FK |
| `designation` | `varchar(200)` | Yes | NULL | — | — | Item description |
| `quantity` | `int` | Yes | NULL | — | — | Planned quantity |
| `unit_price` | `decimal(15,2)` | Yes | NULL | — | — | Sale price per unit |
| `type` | `varchar(45)` | Yes | NULL | — | — | Nature: `service` / `supply` |
| `unit_cost` | `decimal(15,2)` | Yes | NULL | — | — | Purchase cost per unit |
| `frequency` | `enum('annuelle','semestrielle')` | Yes | NULL | — | — | Billing cycle |
| `created_at` | `timestamp` | Yes | NULL | — | — | Timestamps |
| `updated_at` | `timestamp` | Yes | NULL | — | — | Timestamps |

**Foreign Keys:**
- `contract_id` → `contracts.id` (`fk_item_contract_contracts`)
- `item_type_id` → `item_types.id` (`fk_item_contract_types`)

---

### جدول `garanties`

| Column | Type | Nullable | Default | Key | Description |
|--------|------|----------|---------|-----|-------------|
| `id` | `int` | No | — | PRI | — |
| `reference` | `varchar(100)` | No | — | UNI | Unique guarantee ref |
| `bank_name` | `varchar(50)` | Yes | NULL | — | Issuing bank |
| `amount` | `decimal(15,2)` | No | `0.00` | — | Guarantee amount |
| `started_at` | `date` | Yes | NULL | — | Start date |
| `status` | `varchar(50)` | No | — | — | active/expired/released/pending |
| `type` | `varchar(50)` | Yes | NULL | — | garantie_bonne_execution / etc. |
| `created_at` / `updated_at` | `timestamp` | Yes | NULL | — | — |

---

### جدول `customers`

| Column | Type | Nullable | Default | Key | Description |
|--------|------|----------|---------|-----|-------------|
| `id` | `int` | No | — | PRI | — |
| `reference` | `varchar(100)` | Yes | NULL | UNI | Customer identifier |
| `company_name` | `varchar(150)` | Yes | NULL | — | Full name |
| `short_name` | `varchar(50)` | Yes | NULL | — | Short name |
| `address` | `varchar(255)` | Yes | NULL | — | Address |
| `phone` | `varchar(100)` | Yes | NULL | — | Phone |
| `email` | `varchar(100)` | Yes | NULL | — | Email |
| `website` | `varchar(100)` | Yes | NULL | — | Website |
| `registration_number` | `varchar(100)` | Yes | NULL | — | Company registration |
| `notes` | `text` | Yes | NULL | — | Notes |
| `created_at` / `updated_at` | `timestamp` | Yes | NULL | — | — |

---

### جدول `sites`

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| `id` | `int` | No | PRI | — |
| `customer_id` | `int` | Yes | MUL | FK → customers.id |
| `site_code` | `varchar(100)` | Yes | UNI | Unique site identifier |
| `full_name` | `varchar(150)` | Yes | — | Site full name |
| `short_name` | `varchar(50)` | Yes | — | Short name |
| `location` | `varchar(255)` | Yes | — | Location |
| `map_link` | `text` | Yes | — | Google Maps URL |
| `created_at` / `updated_at` | `timestamp` | Yes | — | — |

---

### جدول `item_types`

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| `id` | `int` | No | PRI | — |
| `designation` | `varchar(200)` | No | — | Type description |
| `created_at` / `updated_at` | `timestamp` | Yes | — | — |

---

### جدول `charges`

| Column | Type | Nullable | Default | Key | Description |
|--------|------|----------|---------|-----|-------------|
| `id` | `int` | No | — | PRI | — |
| `amount` | `decimal(15,2)` | No | — | — | Charge amount |
| `date` | `date` | No | — | — | Charge date |
| `description` | `varchar(255)` | Yes | NULL | — | Description |
| `mission_id` | `int` | Yes | NULL | MUL | FK → missions.id |
| `contract_id` | `int` | Yes | NULL | MUL | FK → contracts.id |
| `attachment_item_id` | `int` | Yes | NULL | MUL | FK → attachment_items.id |
| `item_type_id` | `int` | Yes | NULL | MUL | FK → item_types.id |
| `type` | `enum('mission','contract','item','gmtm','prisma')` | Yes | NULL | — | Affiliation |
| `ChargeType` | `enum('fixed','variable')` | Yes | NULL | — | Fixed or variable |
| `created_at` / `updated_at` | `timestamp` | Yes | NULL | — | — |

---

### جدول `missions`

| Column | Type | Nullable | Default | Key | Description |
|--------|------|----------|---------|-----|-------------|
| `id` | `int` | No | — | PRI | — |
| `site_id` | `int` | Yes | NULL | MUL | FK → sites.id |
| `contract_id` | `int` | Yes | NULL | MUL | FK → contracts.id |
| `reference` | `varchar(255)` | No | — | — | Mission reference |
| `start_date` | `date` | Yes | NULL | — | Start date |
| `end_date` | `date` | Yes | NULL | — | End date |
| `mob_dmob_days` | `int` | Yes | NULL | — | Mobilization days |
| `status` | `enum('planned','active','completed')` | Yes | `planned` | — | Mission status |
| `created_at` / `updated_at` | `timestamp` | Yes | NULL | — | — |

---

### جدول `attachments`

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| `id` | `int` | No | PRI | — |
| `mission_id` | `int` | Yes | MUL | FK → missions.id |
| `date` | `date` | Yes | — | Attachment date |
| `ods` | `varchar(45)` | Yes | — | ODS number |
| `code_ref` | `varchar(45)` | Yes | — | Code reference |
| `type` | `varchar(45)` | Yes | — | Type |
| `frequency` | `varchar(50)` | Yes | — | Frequency |
| `status` | `varchar(50)` | Yes | — | draft/submitted/approved |
| `created_at` / `updated_at` | `timestamp` | Yes | — | — |

---

### جدول `attachment_items`

| Column | Type | Nullable | Key | Description |
|--------|------|----------|-----|-------------|
| `id` | `int` | No | PRI | — |
| `attachment_id` | `int` | Yes | MUL | FK → attachments.id |
| `item_contract_id` | `int` | Yes | MUL | FK → item_contracts.id |
| `actual_quantity` | `float` | Yes | — | Actual executed quantity |
| `planned_quantity` | `float` | Yes | — | Planned quantity |
| `created_at` / `updated_at` | `timestamp` | Yes | — | — |

---

## 7. Logical ERD

```
CUSTOMERS
   │ 1
   │
   │ N
CONTRACTS ─────────────────── GARANTIES
   │ 1                              │
   │                    (1 garantie = 0 or many contracts)
   │ N
ITEM_CONTRACTS ── N ── 1 ── ITEM_TYPES
   │ 1
   │
   │ N
ATTACHMENT_ITEMS
   │ N
   │ 1
ATTACHMENTS ── N ── 1 ── MISSIONS ── N ── 1 ── CONTRACTS
                              │ 1
                              │ N
                            CHARGES ── N ── 1 ── CONTRACTS (direct)
```

### Cardinality Details

| Relationship | Foreign Key | Local Key | Delete Behavior | Update Behavior |
|---|---|---|---|---|
| `Contract` belongsTo `Customer` | `contracts.customer_id` | `customers.id` | **Not confirmed** (no cascade in schema docs) | Not confirmed |
| `Contract` belongsTo `Garantie` | `contracts.garantie_id` | `garanties.id` | **nullify** (via Garantie Observer booted) | Not confirmed |
| `Contract` hasMany `ItemContract` | `item_contracts.contract_id` | `contracts.id` | **Manual delete** in controller before contract delete | — |
| `Contract` hasMany `Mission` | `missions.contract_id` | `contracts.id` | **Not protected** — missions remain (Inferred) | — |
| `Contract` hasMany `Charge` | `charges.contract_id` | `contracts.id` | **Not protected** — charges remain (Inferred) | — |
| `Contract` hasManyThrough `Attachment` via `Mission` | `attachments.mission_id` / `missions.contract_id` | — | Via mission lifecycle | — |
| `ItemContract` hasMany `AttachmentItem` | `attachment_items.item_contract_id` | `item_contracts.id` | Protected — cannot delete if has attachment items | — |

---

## 8. Contract Model

**File:** [`app/Models/Contract.php`](file:///d:/HARD%20Project/app.gmtm-dz.com/app/Models/Contract.php)

```php
class Contract extends Model
{
    use HasFactory;
}
```

### `$table`
```php
protected $table = 'contracts';
```
**Source:** Confirmed.

### `$fillable`
```php
protected $fillable = [
    'reference',
    'object',
    'date_signature',
    'duree',
    'montant_global_prevu',
    'customer_id',
    'garantie_id',
];
```

### `$guarded`
Not declared (uses `$fillable` instead).

### `$casts`
```php
protected $casts = [
    'date_signature'       => 'date',
    'montant_global_prevu' => 'decimal:2',
    'duree'                => 'integer',
];
```

### `$hidden`
Not declared.

### `$appends`
Not declared (accessors exist but are not appended to JSON automatically).

### Scopes

#### `scopeActive(Builder $query): Builder`
```php
return $query->whereRaw(
    'DATE_ADD(date_signature, INTERVAL duree MONTH) >= ?',
    [now()->startOfDay()]
);
```
**Purpose:** Returns only contracts that have not yet expired (end date >= today).

### Accessors (Attribute API — Laravel 9+ style)

#### `remainDays(): Attribute`
- **Calculated from:** `date_signature` + `duree` months → diff from today
- **Type:** `int|null`
- **Returns `null` if:** either `date_signature` or `duree` is missing

#### `percentRemaining(): Attribute`
- **Calculated from:** (remainDays / totalDays) × 100
- **Type:** `float`
- **Returns `0` if:** expired or missing dates

#### `expiryStatus(): Attribute`
- **Returns:** one of `'unknown'`, `'start'`, `'mid'`, `'end'`, `'archive'`
- **Logic:**
  - `null` dates → `'unknown'`
  - `remain_days <= 0` → `'archive'`
  - `percent_remaining > 66%` → `'start'`
  - `percent_remaining > 33%` → `'mid'`
  - else → `'end'`

### Non-accessor Methods

#### `getExpiryBadgeClassAttribute(): string` *(Legacy Accessor)*
Returns CSS class: `badge-glass-success`, `badge-glass-warning`, or `badge-glass-danger`.

#### `formatCurrency($amount): string`
Helper — formats amount as `"1 234 567.89 DA"`.

#### `total_invoiced(): string`
**Calculated** — sum of `(actual_quantity × unit_price)` for APPROVED attachments only.  
Uses `AttachmentItem` join with `item_contracts`.

#### `total_consumed(): string`
**Calculated** — same formula but includes ALL statuses (draft + submitted + approved).

#### `total_planned(): string`
**Calculated** — sum of `(unit_price × quantity)` across all `ItemContract` items.

#### `attachments_count(): int`
**Calculated** — counts `Attachment` records that have at least one `AttachmentItem` linked to this contract's items.

### Traits
- `HasFactory`

### SoftDeletes
**Not used.** No `deleted_at` column, no `SoftDeletes` trait.

### Observer
`ContractObserver` is registered in `AppServiceProvider::boot()`.  
Fires notifications on `created`, `updated`, `deleted`.  
`$afterCommit = true` — notifications fire only after DB transaction commits.

---

## 9. Eloquent Relationships

### On `Contract` model

| Method | Type | Related Model | FK | Local Key | Business Meaning |
|--------|------|---------------|----|-----------|------------------|
| `customer()` | `BelongsTo` | `Customer` | `contracts.customer_id` | `customers.id` | The client of the contract |
| `site()` | `BelongsTo` | `Site` | *(missing column — see §10)* | `sites.id` | **Problematic** |
| `garantie()` | `BelongsTo` | `Garantie` | `contracts.garantie_id` | `garanties.id` | Bank guarantee |
| `items()` | `HasMany` | `ItemContract` | `item_contracts.contract_id` | `contracts.id` | Contract line items |
| `missions()` | `HasMany` | `Mission` | `missions.contract_id` | `contracts.id` | Field missions under contract |
| `charges()` | `HasMany` | `Charge` | `charges.contract_id` | `contracts.id` | Direct expenses on contract |
| `attachments()` | `HasManyThrough` | `Attachment` | via `Mission` | — | Invoicing documents |

### On `ItemContract` model

| Method | Type | Related Model | FK | Business Meaning |
|--------|------|---------------|----|-----------------|
| `contract()` | `BelongsTo` | `Contract` | `item_contracts.contract_id` | Parent contract |
| `itemType()` | `BelongsTo` | `ItemType` | `item_contracts.item_type_id` | Catalog category |
| `attachmentItems()` | `HasMany` | `AttachmentItem` | `attachment_items.item_contract_id` | Consumption records |

### On `Garantie` model (inverse)

| Method | Type | Related Model | FK | Business Meaning |
|--------|------|---------------|----|-----------------|
| `contracts()` | `HasMany` | `Contract` | `contracts.garantie_id` | Contracts using this guarantee |

---

## 10. Site Relationship

### ⚠️ Confirmed Issue

**The `contracts` table does NOT have a `site_id` column.**

The `Contract` model has a `site()` method:
```php
public function site(): BelongsTo
{
    // Note: The 'site_id' column is missing from the database schema for contracts.
    // This relationship may need to be removed or updated to link via customer.
    return $this->belongsTo(Site::class);
}
```

This method uses Laravel's convention to look for `site_id` on `contracts`, but this column does not exist in the database.

**Consequences:**
- `$contract->site` will always return `null` at runtime
- In `ContractObserver::getPayload()`:  
  `$siteName = $contract->site->short_name ?? 'غير محدد';` → always falls back to `'غير محدد'`
- In `ContractStatisticsService::calculate()`:  
  `'site' => $contract->site->short_name ?? '—'` → always returns `'—'`

**How Site is actually accessed:**  
Site is accessible via `Contract → Customer → Sites`:
- `Customer` hasMany `Site` (`customer_id` on `sites`)
- `Contract` belongsTo `Customer` (`customer_id` on `contracts`)
- Therefore: `$contract->customer->sites` (collection, not single)

**Is Site mandatory?** No — `customer_id` is nullable in `contracts`.

**Can Site be changed after contract creation?** Not applicable — no `site_id` on contract.

---

## 11. Garantie Relationship

### Confirmed

```
Contract (N)
    └── garantie_id
    └──→ Garantie (1)
```

**Cardinality:** One Garantie can belong to many Contracts (Garantie hasMany Contract).  
**In practice enforced:** The controller filters garanties with `doesntHave('contracts')` when creating — meaning in normal flow, a garantie is linked to only one active contract at a time.  
But this is a **business rule enforced in the controller**, not a DB unique constraint.

### Validation (Confirmed)
```php
'garantie_id' => 'nullable|exists:garanties,id'
```
Garantie is **optional** (nullable).

### Selection in Create Form (Confirmed)
```php
$garanties = Garantie::where('status', 'active')
    ->where('type', 'garantie_bonne_execution')
    ->doesntHave('contracts')
    ->orderBy('reference')
    ->get();
```
Only `active` + type `garantie_bonne_execution` + not yet linked = available for selection.

### Selection in Edit Form (Confirmed)
```php
$garanties = Garantie::where(function ($q) {
    $q->where('status', 'active')
      ->where('type', 'garantie_bonne_execution')
      ->doesntHave('contracts');
})->orWhere('id', $contract->garantie_id)  // ← include currently linked one
->orderBy('reference')->get();
```

### Garantie Fields (from `garanties` table)
- `reference` (UNI, required) — unique bank reference
- `bank_name` — issuing bank
- `amount` — guarantee amount in DA
- `started_at` — start date
- `status` — `active` | `expired` | `released` | `pending`
- `type` — `garantie_bonne_execution` | other types

### Delete Behavior (Confirmed — Garantie::booted())
When a Garantie is deleted, `garantie_id` on all linked contracts is **set to NULL**:
```php
$garantie->contracts()->update(['garantie_id' => null]);
```

### Is Garantie:
- **Obligatoire?** No — nullable
- **Optionnelle?** Yes
- **Réutilisable?** Technically yes (no DB unique), but controller enforces one-at-a-time via `doesntHave`
- **Liée à un seul contrat?** Enforced in controller, not in DB

---

## 12. Item Types

**Table:** `item_types`  
**Model:** [`app/Models/ItemType.php`](file:///d:/HARD%20Project/app.gmtm-dz.com/app/Models/ItemType.php)

`ItemType` is a **master catalogue** of service/supply categories.  
It has only one field: `designation`.

```
ItemType (1)
    └──→ ItemContract (N)   [item_type_id on item_contracts]
    └──→ Charge (N)         [item_type_id on charges]
```

**Where defined:** `item_types` table — managed separately (with its own Controller and Observer).  
**Where assigned:** On `ItemContract` records, when creating/editing a Contract's items.

**Difference:**
```
ItemType         = catalog entry (e.g., "Inspection de sécurité", "Maintenance corrective")
    ↓
ItemContract     = contract-specific line item (e.g., qty=12, unit_price=50000 DA)
    ↓
AttachmentItem   = actual execution record (e.g., actual_qty=3 in this billing period)
```

---

## 13. Item Contracts

**Table:** `item_contracts`  
**Model:** [`app/Models/ItemContract.php`](file:///d:/HARD%20Project/app.gmtm-dz.com/app/Models/ItemContract.php)

```
CONTRACT (1)
    │
    │ N
ITEM_CONTRACTS
    │ N
    │ 1
ITEM_TYPES
```

### Fields (Confirmed)

| Field | Type | Cast | Business Meaning |
|-------|------|------|-----------------|
| `contract_id` | int | — | Parent contract |
| `item_type_id` | int | — | Category (optional) |
| `designation` | varchar(200) | — | Description of the item |
| `quantity` | int | `integer` | Planned total quantity |
| `unit_price` | decimal(15,2) | `decimal:2` | Sale price per unit (DA) |
| `type` | varchar(45) | — | `service` or `supply` |
| `unit_cost` | decimal(15,2) | `decimal:2` | Purchase cost per unit (DA) |
| `frequency` | enum | `BillingCycle::class` | `annuelle` or `semestrielle` |

### Enums
```php
enum BillingCycle: string {
    case ANNUELLE     = 'annuelle';
    case SEMESTRIELLE = 'semestrielle';
}
```

### Accessors on ItemContract

| Accessor | Calculation | Return |
|----------|-------------|--------|
| `getTotal` | `unit_price × quantity` | `float` |
| `getTotalPurchaseCost` | `unit_cost × quantity` | `float` |
| `getConsumptionPercentage` | `(actual_qty / quantity) × 100` | `float` |
| `getConsumptionStatusClass` | Based on percentage | CSS class string |

### Delete Behavior
- Items can be deleted only if they have **no `attachment_items`** linked.
- In `ContractController::update()`, protected items (those with attachment_items) are never deleted even if removed from the form.
- In `ContractController::destroy()`, all items are deleted before the contract, but only if there are no attachments.

---

## 14. Contract Financial Structure

### Financial Values

| Value | Source | Where Calculated | Display |
|-------|--------|-----------------|---------|
| `montant_global_prevu` | **Stored** in `contracts.montant_global_prevu` | — | Show page, Edit form |
| `total_planned` | **Calculated** — sum of (unit_price × quantity) for all items | `Contract::total_planned()` | Show page, Statistics |
| `total_invoiced` | **Calculated** — sum of (actual_qty × unit_price) for APPROVED attachments | `Contract::total_invoiced()` | Show page |
| `total_consumed` | **Calculated** — same but ALL statuses | `Contract::total_consumed()` | Show page |
| Item `total` | **Calculated** — `unit_price × quantity` per item | `ItemContract::getTotal()` | Show page items table |
| Item `total_purchase_cost` | **Calculated** — `unit_cost × quantity` | `ItemContract::getTotalPurchaseCost()` | Show page |
| Item `consumption_percentage` | **Calculated** — `actual_qty / quantity` | `ItemContract::getConsumptionPercentage()` | Show page progress bar |

### Financial Data Flow

```
Database
   ├── contracts.montant_global_prevu           ← Stored planned amount
   ├── item_contracts.unit_price × quantity     ← Calculated total_planned
   └── attachment_items.actual_quantity
         × item_contracts.unit_price
         WHERE attachments.status = APPROVED    ← Calculated total_invoiced
         (or ALL statuses)                       ← Calculated total_consumed
         ↓
   Contract model methods
         ↓
   Blade template (show.blade.php)
         ↓
   User screen
```

### Statistics Service Flow (ContractStatisticsService)

```
Contract
   ├── date_signature + duree → start/end dates, elapsed days
   ├── items → planned qty, planned revenue
   ├── attachment_items × unit_price → actual revenue (lifetime)
   ├── mission_orders (daily_rate × days) → HR costs
   ├── charges (direct on contract OR on missions) → direct expenses
   └── gmtm overhead charges (by year) → net profit calculation
         ↓
   Returns: array of stats for statistics.blade.php
```

---

## 15. Contract → Mission

### Confirmed

```
CONTRACT (1)
    │
    │ N
MISSIONS
```

**FK:** `missions.contract_id` → `contracts.id`  
**Eloquent (Contract side):** `Contract::missions()` → `hasMany(Mission::class)`  
**Eloquent (Mission side):** `Mission::contract()` → `belongsTo(Contract::class)`

### Display in show.blade.php
All linked missions are listed in a table with: reference, start_date, end_date, status badge.  
Each mission reference links to `admin.missions.show`.

### Filtering
Not confirmed — no filter by contract in MissionController was analyzed.

### Deletion Restrictions
Missions are **not** restricted from deletion based on contract presence.  
Contract deletion is **not** restricted based on mission presence (only attachment presence is checked).

### Contract Statistics Service use
```php
$missions = $contract->missions;  // Eager loaded
$missionIds = $missions->pluck('id');
// → used for HR cost calculation, charge aggregation
```

---

## 16. Contract → Charges

### Confirmed

```
CONTRACT (1)
    │ N
CHARGES
```

**FK:** `charges.contract_id` → `contracts.id`  
**Eloquent:** `Contract::charges()` → `hasMany(Charge::class)`  
**Inverse:** `Charge::contract()` → `belongsTo(Contract::class)`

Charges can be affiliated at multiple levels (`type` enum):
- `'contract'` — direct contract overhead
- `'mission'` — mission-specific expense
- `'item'` — item-specific expense
- `'gmtm'` — company-wide overhead
- `'prisma'` — Prisma-specific overhead

### In Statistics Service (Confirmed)
```php
$directCharges = DB::table('charges')
    ->where(function ($query) use ($contract, $missionIds) {
        $query->where('contract_id', $contract->id)     // direct charges on contract
              ->orWhereIn('mission_id', $missionIds);    // charges on linked missions
    })
    ->sum('amount');
```
Both contract-level and mission-level charges are summed for total contract cost.

---

## 17. Contract → Attachments

### Confirmed Path

```
Contract
    └──→ Mission (hasMany via missions.contract_id)
               └──→ Attachment (hasMany via attachments.mission_id)
                          └──→ AttachmentItem (hasMany via attachment_items.attachment_id)
                                     └──→ ItemContract (belongsTo via attachment_items.item_contract_id)
```

**Direct relationship (HasManyThrough):**
```php
public function attachments(): HasManyThrough
{
    return $this->hasManyThrough(Attachment::class, Mission::class);
}
```
This uses: `missions.contract_id` and `attachments.mission_id`.

**No direct `contract_id` on `attachments` table** — the path is always via Mission.

**`attachments_count()` method:**  
Uses a different query — counts Attachments that have AttachmentItems referencing this contract's items:
```php
Attachment::whereHas('items', function ($query) {
    $query->whereIn('item_contract_id', $this->items()->pluck('id'));
})->count();
```
This is more accurate than `hasManyThrough` for counting.

---

## 18. Contract Lifecycle

### Confirmed

There is **no explicit `status` column** or status machine in the `contracts` table.

The contract "lifecycle" is **temporal** — based purely on `date_signature` and `duree`:

```
Created
   ↓
start  (> 66% of duration remaining)
   ↓
mid    (33% - 66% remaining)
   ↓
end    (< 33% remaining)
   ↓
archive (0% remaining — expired)
```

| Phase | Meaning | Assigned By | Side Effects |
|-------|---------|-------------|--------------|
| `start` | Contract in early phase | Auto-calculated accessor | Green badge |
| `mid` | Contract in middle phase | Auto-calculated accessor | Blue badge |
| `end` | Contract near expiry | Auto-calculated accessor | Orange badge |
| `archive` | Contract expired | Auto-calculated accessor | Gray badge, Edit/Delete buttons hidden |
| `unknown` | Missing dates | Auto-calculated accessor | No badge |

**Important:** The `archive` phase **does NOT prevent editing** at the server side — only the Blade view hides edit/delete buttons for archived contracts. A direct HTTP PUT/DELETE request would still work if the user has the permission.

---

## 19. Contract Duration

### Confirmed

**Field:** `contracts.duree` — type `int`, nullable, unit = **MONTHS**.

### Validation
```php
'duree' => 'nullable|integer|min:1'
```

### End Date Calculation (Backend)
```php
$endDate = $contract->date_signature->copy()->addMonths($duree);
```

### Active Scope
```sql
DATE_ADD(date_signature, INTERVAL duree MONTH) >= CURDATE()
```

### Display
```
date_signature + duree (months) = calculated end date
```
End date is **not stored** — always calculated.

### remainDays Accessor
```
end_date - today = remain_days (int, can be negative)
```

---

## 20. Controllers

**File:** [`app/Http/Controllers/Admin/ContractController.php`](file:///d:/HARD%20Project/app.gmtm-dz.com/app/Http/Controllers/Admin/ContractController.php)  
**Class:** `final class ContractController extends Controller`

| HTTP | Route | Method | Permission | Request | Output |
|------|-------|--------|------------|---------|--------|
| GET | `admin/contracts` | `index` | `view contracts` | `Request` | View `admin.contracts.index` |
| GET | `admin/contracts/create` | `create` | `create contracts` | none | View `admin.contracts.create` |
| POST | `admin/contracts` | `store` | `create contracts` | `StoreContractRequest` | Redirect to index |
| GET | `admin/contracts/{id}` | `show` | `view contracts` | none | View `admin.contracts.show` |
| GET | `admin/contracts/{id}/edit` | `edit` | `edit contracts` | none | View `admin.contracts.edit` |
| PUT | `admin/contracts/{id}` | `update` | `edit contracts` | `UpdateContractRequest` | Redirect to show |
| DELETE | `admin/contracts/{id}` | `destroy` | `delete contracts` | none | Redirect to index |
| GET | `admin/contracts/{id}/statistics` | `statistics` | `view contracts` | none | View `admin.contracts.statistics` |

### `index(Request $request)`
- Eager loads: `customer`, `items`, `garantie`
- `withCount('items')`
- Filters: `customer_id`, free-text search on `reference` + `object`
- Orders: active contracts first (not expired), then by `created_at DESC`
- Pagination: 10 per page (`paginate(10)->withQueryString()`)
- Passes: `$contracts`, `$customers` to view

### `store(StoreContractRequest $request)`
- Wrapped in `DB::transaction()`
- Creates `Contract` via mass assignment (excluding `items`)
- Loops through `items` array and calls `$contract->items()->create()`
- Only creates items if `designation` is not empty
- Redirects to `admin.contracts.index` with flash `success`

### `destroy(Contract $contract)`
- Explicit Gate check: `Gate::authorize('delete contracts')`
- **Guard:** Checks `Attachment::whereHas('items', ...)` — if any attachments exist, returns `back()->with('error', ...)`
- Wrapped in `DB::transaction()`: deletes items first, then contract
- No soft delete

### `statistics(Contract $contract, ContractStatisticsService $statisticsService)`
- Service injection
- Calls `$statisticsService->calculate($contract)`
- Returns `admin.contracts.statistics` view

---

## 21. Form Requests

### StoreContractRequest

**File:** [`app/Http/Requests/Contract/StoreContractRequest.php`](file:///d:/HARD%20Project/app.gmtm-dz.com/app/Http/Requests/Contract/StoreContractRequest.php)

**Authorization:** Not overridden → defaults to `true` (relies on route middleware).

#### Technical Validation

| Field | Rules |
|-------|-------|
| `reference` | `required`, `string`, `max:110`, `unique:contracts,reference` |
| `object` | `nullable`, `string`, `max:200` |
| `date_signature` | `nullable`, `date` |
| `duree` | `nullable`, `integer`, `min:1` |
| `montant_global_prevu` | `nullable`, `numeric`, `min:0` |
| `customer_id` | `nullable`, `exists:customers,id` |
| `garantie_id` | `nullable`, `exists:garanties,id` |
| `items` | `nullable`, `array` |
| `items.*.item_type_id` | `nullable`, `exists:item_types,id` |
| `items.*.designation` | `required_with:items`, `string`, `max:200` |
| `items.*.quantity` | `nullable`, `integer`, `min:1` |
| `items.*.unit_price` | `nullable`, `numeric`, `min:0` |
| `items.*.type` | `nullable`, `string`, `max:45` |
| `items.*.unit_cost` | `nullable`, `numeric`, `min:0` |
| `items.*.frequency` | `nullable`, `string`, `in:annuelle,semestrielle` |

#### Business Rules
- `reference` must be globally unique across all contracts
- An item's `designation` is required if any item is submitted

### UpdateContractRequest

**File:** [`app/Http/Requests/Contract/UpdateContractRequest.php`](file:///d:/HARD%20Project/app.gmtm-dz.com/app/Http/Requests/Contract/UpdateContractRequest.php)

**Difference from Store:**
- `reference` ignores the **current contract's id** for uniqueness check
- Adds `items.*.id` → `nullable|integer|exists:item_contracts,id` (to identify existing items)

---

## 22. Services / Actions

### ContractStatisticsService

**File:** [`app/Services/ContractStatisticsService.php`](file:///d:/HARD%20Project/app.gmtm-dz.com/app/Services/ContractStatisticsService.php)

**Responsibility:** Calculate lifetime financial and operational statistics for a single contract.

**Method:** `calculate(Contract $contract): array`

**Inputs:** A `Contract` model (with `items` and `missions` already loaded or lazy-loaded)

**Output:**
```php
[
    'contract_info' => [
        'reference', 'object', 'start_date', 'end_date',
        'total_days', 'elapsed_days', 'remaining_days',
        'customer', 'site'   // ← site always '—' (see §10)
    ],
    'revenue' => [
        'total', 'planned', 'consumption_rate', 'remaining'
    ],
    'costs' => [
        'total_hr', 'direct_charges', 'total_expenses'
    ],
    'profit' => [
        'gross', 'gross_margin'
    ],
    'operational' => [
        'missions_count', 'attachments_count', 'missions_detail',
        'average_mob_cost', 'average_mob_days', 'total_mob_days',
        'average_mission_mob_and_expenses'
    ]
]
```

**Dependencies:**
- `AttachmentItem` — for revenue calculation
- `DB::table('mission_orders')` — for HR costs
- `DB::table('charges')` — for direct expenses
- `DB::table('income_forecasts')` — for GMTM overhead rate
- `DB::table('attachments')` — for attachment count

**Transactions:** None (read-only).

---

## 23. Repositories

**Repository Pattern: NOT USED** — Confirmed from `AppServiceProvider.php` comment:
> "تم تفريغ هذه الدالة لأن النظام يعتمد على UseCases و Models مباشرة ولا يستخدم نمط Repositories الخارجي"

All database access goes directly through Eloquent models in the Controller.

---

## 24. Policies & Authorization

**No `ContractPolicy` file exists.** Authorization is handled through:

1. **Route Middleware (primary):**
```php
Route::middleware(['permission:view contracts'])
Route::middleware(['permission:create contracts'])
Route::middleware(['permission:edit contracts'])
Route::middleware(['permission:delete contracts'])
```

2. **Explicit Gate in destroy (secondary):**
```php
Gate::authorize('delete contracts');
```

3. **Blade `@can` directives:**
```blade
@can('create contracts') → Show "Add Contract" button
@can('edit contracts')   → Show "Edit" button
@can('delete contracts') → Show "Delete" form
```

4. **RBAC Group (config/permissions_groups.php):**
```php
'contrat_management' => [
    'icon' => 'fa-file-contract',
    'lang' => 'contracts',
    'perms' => ['view contrats', 'create contrats', 'edit contrats', 'delete contrats']
]
```
**⚠️ Naming Inconsistency:** The permissions group uses `contrats` (French spelling) while routes use `contracts` (English). This is a confirmed naming mismatch.

5. **SuperAdmin bypass:** Registered in `AppServiceProvider::registerSuperAdminBypass()` — Super-Admin bypasses all permission checks.

---

## 25. API Endpoints

**There are no REST API endpoints for Contracts.** All endpoints are Web (Blade) routes.  
`routes/api.php` only contains AGA8 gas calculation endpoints.

**Web Routes (all under middleware `auth`, `checkStatus`, prefix `admin/`, name prefix `admin.contracts.`):**

| HTTP | URL | Name | Permission |
|------|-----|------|------------|
| GET | `/admin/contracts` | `admin.contracts.index` | `view contracts` |
| GET | `/admin/contracts/create` | `admin.contracts.create` | `create contracts` |
| POST | `/admin/contracts` | `admin.contracts.store` | `create contracts` |
| GET | `/admin/contracts/{id}` | `admin.contracts.show` | `view contracts` |
| GET | `/admin/contracts/{id}/edit` | `admin.contracts.edit` | `edit contracts` |
| PUT | `/admin/contracts/{id}` | `admin.contracts.update` | `edit contracts` |
| DELETE | `/admin/contracts/{id}` | `admin.contracts.destroy` | `delete contracts` |
| GET | `/admin/contracts/{id}/statistics` | `admin.contracts.statistics` | `view contracts` |

---

## 26. Request Structures

### Create Form Fields

| Input Name | HTML Type | Validation | Maps To |
|---|---|---|---|
| `reference` | text | required, unique | `contracts.reference` |
| `object` | text | nullable | `contracts.object` |
| `date_signature` | date | nullable | `contracts.date_signature` |
| `duree` | number (min:1) | nullable, integer | `contracts.duree` |
| `montant_global_prevu` | number (step:0.01) | nullable, numeric | `contracts.montant_global_prevu` |
| `customer_id` | select | nullable, exists | `contracts.customer_id` |
| `garantie_id` | select | nullable, exists | `contracts.garantie_id` |
| `items[N][designation]` | text | required_with:items | `item_contracts.designation` |
| `items[N][item_type_id]` | select | nullable, exists | `item_contracts.item_type_id` |
| `items[N][type]` | select (service/supply) | nullable | `item_contracts.type` |
| `items[N][quantity]` | number | nullable, min:1 | `item_contracts.quantity` |
| `items[N][unit_price]` | number | nullable, min:0 | `item_contracts.unit_price` |
| `items[N][unit_cost]` | number | nullable, min:0 | `item_contracts.unit_cost` |
| `items[N][frequency]` | select | nullable, in:annuelle,semestrielle | `item_contracts.frequency` |

Items are added **dynamically via JavaScript** (`addItem()` function).

---

## 27. Response Structures

This is a **Blade/Web application** — no JSON API responses for contract CRUD.

**Flash messages:**
- Success on create: `__('contract_created')`
- Success on update: `__('contract_updated')`
- Success on delete: `__('contract_deleted')`
- Error on delete (has attachments): `__('contract_has_attachments', ['count' => N])`

**Validation errors:** Displayed inline via Blade `@if($errors->any())` blocks.

**No JSON responses** from ContractController (unlike API modules).

---

## 28. Contract Listing

**View:** `resources/views/admin/contracts/index.blade.php`  
**Route:** `GET /admin/contracts`

### Table Columns (Confirmed)
1. `reference` — contract reference (bold)
2. `customer.company_name` — client name
3. `date_signature` — formatted `d/m/Y`
4. `duree` — in months
5. Contract Phase — badge (start/mid/end/archive) + progress bar
6. Remaining Time — in days
7. Guarantee Status — guarantee badge (active/expired/released/pending)
8. Actions — view, statistics, edit (if not archived + `@can`), delete (if not archived + `@can`)

### Filters
| Filter | Input | Query |
|--------|-------|-------|
| Customer | `<select name="customer_id">` → auto-submit | `->where('customer_id', ...)` |
| Free text search | `<input name="search">` | `->where('reference', LIKE) ->orWhere('object', LIKE)` |

### Sorting (Confirmed)
```sql
ORDER BY CASE WHEN DATE_ADD(date_signature, INTERVAL duree MONTH) >= CURDATE() THEN 0 ELSE 1 END ASC,
         created_at DESC
```
Active contracts appear first.

### Pagination
- 10 per page
- Custom pagination view: `vendor.pagination.custom`
- `withQueryString()` preserves filters across pages

### Data Flow
```
User requests /admin/contracts?customer_id=5&search=ref
      ↓
ContractController::index()
      ↓
Contract::with(['customer', 'items', 'garantie'])->withCount('items')
         ->where('customer_id', 5)
         ->where(reference LIKE '%ref%' OR object LIKE '%ref%')
         ->orderByRaw(...)
         ->paginate(10)
      ↓
view('admin.contracts.index', compact('contracts', 'customers'))
      ↓
Blade loops $contracts → renders table
      ↓
Phase calculation done in Blade @php block (not in model/controller)
```

---

## 29. Contract Details

**View:** `resources/views/admin/contracts/show.blade.php`  
**Route:** `GET /admin/contracts/{id}`

### Data loaded (Confirmed)
```php
$contract->load([
    'customer',
    'items' => function ($q) {
        $q->withSum('attachmentItems as attachment_items_sum_quantity', 'actual_quantity')
          ->with('itemType');
    },
    'missions',
    'charges',
    'garantie',
    'attachments',
]);
```

### Page Sections
1. **Contract Info Card:** reference, object, customer, date_signature, duree, remain_days, montant_global_prevu, missions_count, attachments_count, total_invoiced, total_consumed
2. **Guarantee Card:** reference (link to guarantees list), type, amount, started_at, status badge
3. **Items Table:** #, designation, itemType, frequency, quantity, consumed (with progress bar), unit_price, unit_cost, purchase_cost, total — with footer totals
4. **Linked Missions Table:** reference (link), start_date, end_date, status
5. **Linked Attachments Table:** code_ref (link), date, ods, type, status

### Source of Truth for Each UI Field

| UI Field | JSON/PHP Field | Source |
|----------|----------------|--------|
| Reference | `$contract->reference` | `contracts.reference` |
| Object | `$contract->object` | `contracts.object` |
| Customer | `$contract->customer->company_name` | `customers.company_name` |
| Signature Date | `$contract->date_signature` | `contracts.date_signature` |
| Duration | `$contract->duree` | `contracts.duree` |
| Remaining Days | `$contract->remain_days` | **Calculated** in `remainDays()` accessor |
| Planned Amount | `$contract->montant_global_prevu` | `contracts.montant_global_prevu` |
| Missions Count | `$contract->missions->count()` | **Calculated** count of `missions` relation |
| Attachments Count | `$contract->attachments_count()` | **Calculated** via custom method |
| Total Invoiced | `$contract->total_invoiced()` | **Calculated** in model method |
| Total Consumed | `$contract->total_consumed()` | **Calculated** in model method |
| Guarantee Reference | `$contract->garantie->reference` | `garanties.reference` |
| Guarantee Amount | `$contract->garantie->amount` | `garanties.amount` |
| Item Consumed Qty | `$item->attachment_items_sum_quantity` | **Calculated** via `withSum()` |
| Item Total | `$item->total` | **Calculated** → `unit_price × quantity` |

---

## 30. Contract Create Flow

```
User opens /admin/contracts/create
      ↓ GET
ContractController::create()
      ↓
$customers = Customer::orderBy('short_name')->get()
$itemTypes = ItemType::orderBy('designation')->get()
$garanties = Garantie::where('status','active')
                     ->where('type','garantie_bonne_execution')
                     ->doesntHave('contracts')
                     ->orderBy('reference')->get()
      ↓
view('admin.contracts.create', compact(...))
      ↓
User fills form + adds items dynamically (JavaScript addItem())
      ↓ POST /admin/contracts
StoreContractRequest validates all fields
      ↓ (if fails → redirect back with errors)
ContractController::store()
      ↓
DB::transaction():
    Contract::create($validated except 'items')    → INSERT into contracts
    foreach items:
        $contract->items()->create([...])          → INSERT into item_contracts
      ↓
ContractObserver::created() fires (after commit):
    NotificationService::sendToGroup('view contracts', ...)
      ↓
redirect(admin.contracts.index)->with('success', __('contract_created'))
```

---

## 31. Contract Update Flow

```
User opens /admin/contracts/{id}/edit
      ↓ GET
ContractController::edit()
      ↓
$contract->load(['items.itemType', 'garantie.contracts'])
$customers, $itemTypes, $garanties loaded (including current garantie)
      ↓
view('admin.contracts.edit', compact(...))
      ↓
User modifies fields / items (JS pre-populates existing items)
      ↓ PUT /admin/contracts/{id}
UpdateContractRequest validates (reference unique ignoring self)
      ↓
ContractController::update()
      ↓
DB::transaction():
    $contract->update($validated except 'items')
    
    // Find protected items (have attachment_items)
    $protectedItemIds = DB::table('attachment_items')
        ->whereIn('item_contract_id', $contract->items()->pluck('id'))
        ->pluck('item_contract_id')->unique()->toArray()
    
    // Delete non-protected items not in form
    $contract->items()->whereNotIn('id', $protectedItemIds)->delete()
    
    // Update or create items from form
    foreach items:
        if item has ID and is protected → UPDATE existing
        else → CREATE new item
      ↓
ContractObserver::updated() fires (after commit):
    if wasChanged() → send notification
      ↓
redirect(admin.contracts.show, $contract->id)->with('success', __('contract_updated'))
```

### Editable Fields
All fields in `$fillable` are editable: `reference`, `object`, `date_signature`, `duree`, `montant_global_prevu`, `customer_id`, `garantie_id`, plus all item fields.

### Protected Fields
Items that already have `attachment_items` cannot be fully deleted — they can be updated but their `id` is preserved. Their financial data (unit_price, unit_cost) CAN be modified even if they have attachment_items.

---

## 32. Contract Delete Flow

```
User clicks Delete button (only visible if not archived AND @can('delete contracts'))
      ↓ DELETE /admin/contracts/{id}  (via POST form with @method('DELETE'))
ContractController::destroy()
      ↓
Gate::authorize('delete contracts')  ← explicit check (double-guarded)
      ↓
Guard: Count attachments linked to contract items:
    $attachmentsCount = Attachment::whereHas('items', fn($q) =>
        $q->whereIn('item_contract_id', $contract->items()->pluck('id'))
    )->count()
    
    if ($attachmentsCount > 0):
        return back()->with('error', __('contract_has_attachments', ['count' => N]))
        ← CONTRACT NOT DELETED
      ↓ (if 0 attachments)
DB::transaction():
    $contract->items()->delete()  ← DELETE all item_contracts for this contract
    $contract->delete()           ← DELETE the contract
      ↓
ContractObserver::deleted() fires (after commit):
    send notification to 'view contracts' group
      ↓
redirect(admin.contracts.index)->with('success', __('contract_deleted'))
```

### Impact on Related Records

| Related | Impact |
|---------|--------|
| `item_contracts` | **Hard deleted** before contract |
| `missions` | **Not deleted** — FK `missions.contract_id` is set to nullable, missions remain |
| `charges` | **Not protected/deleted** — charges with `contract_id` remain orphaned (Inferred risk) |
| `garanties` | **Not deleted** — only `garantie_id` on contract is lost |
| `attachments` | **Block deletion** — if any exist, contract cannot be deleted |

---

## 33. Contract Items UI

### Create Form (Confirmed)
- Items section: "Ajouter un bénéficiaire" button → calls JavaScript `addItem()` 
- `addItem()` dynamically injects a full row with all fields
- One empty row added automatically on page load (`addItem()` called in script)
- Remove row: `<button onclick="this.closest('.item-row').remove()">`
- `itemTypes` passed from controller as `@json($itemTypes)` for JS dropdown population

### Edit Form
- Existing items rendered from PHP in a hidden way, pre-populated via JS:
  - Items are available as `$contract->items` 
  - Edit form uses same JavaScript as create but with pre-populated data
  - Items have a hidden `items[N][id]` field to identify existing records

### Show Page Items Table Columns
1. `#` (row number)
2. `designation` (truncated to 50 chars)
3. `itemType.designation` (badge)
4. `frequency` (translated)
5. `quantity` (planned)
6. `consumed` (actual_qty / planned_qty with progress bar)
7. `unit_price` (formatted as DA)
8. `unit_cost` (formatted as DA)
9. `purchase_cost` (calculated: unit_cost × consumed_qty)
10. `total` (unit_price × quantity)

---

## 34. Filtering

### Confirmed Filters on Listing Page

| Filter | Query Param | Input | DB Query |
|--------|-------------|-------|----------|
| Customer | `customer_id` | `<select>` (auto-submit on change) | `->where('customer_id', $request->customer_id)` |
| Free Text | `search` | `<input type="text">` | `->where('reference', LIKE '%...%') ->orWhere('object', LIKE '%...%')` |

### Not Confirmed
- Status filter ❌ (no status field)
- Date range filter ❌
- Amount filter ❌
- Duration filter ❌

### Filter Flow
```
Frontend select/input
      ↓ GET /admin/contracts?customer_id=X&search=Y
ContractController::index(Request $request)
      ↓
if ($request->filled('customer_id')) → $query->where(...)
if ($request->filled('search'))      → $query->where(fn() → LIKE)
      ↓
$query->paginate(10)->withQueryString()  ← preserves filters on page change
      ↓
view('admin.contracts.index')
```

---

## 35. Pagination

- **Implementation:** Laravel `paginate(10)`
- **Per page:** 10 (hardcoded)
- **Query strings preserved:** `withQueryString()`
- **Custom pagination view:** `vendor.pagination.custom`
- **Total count displayed:** `$contracts->total()` shown in header badge
- **Metadata available (standard Laravel):** `total`, `per_page`, `current_page`, `last_page`, `from`, `to`

---

## 36. Frontend Components

### Files

| File | Responsibility | Backend Data | API Calls |
|------|----------------|-------------|-----------|
| `index.blade.php` | List all contracts with filters | `$contracts`, `$customers` | None (GET form) |
| `create.blade.php` | Create form with dynamic items | `$customers`, `$itemTypes`, `$garanties` | None (POST form) |
| `edit.blade.php` | Edit form with pre-populated items | `$contract`, `$customers`, `$itemTypes`, `$garanties` | None (PUT form) |
| `show.blade.php` | Contract details with all relations | `$contract` (with loaded relations) | None |
| `statistics.blade.php` | Financial statistics dashboard | `$contract`, `$stats` (array) | None |

### JavaScript (create.blade.php & edit.blade.php)
- **No AJAX/Fetch calls** — pure form submission
- `addItem()` function: dynamically appends an item row to `#itemsContainer`
- `buildItemTypeOptions(selected)` — builds `<option>` list from PHP-injected `itemTypes` JSON
- `itemTypes` injected via: `const itemTypes = @json($itemTypes);`

### No Alpine.js or Livewire used in Contract views.

---

## 37. Frontend → Backend Flow

```
User fills form (create.blade.php)
      │
      │ POST /admin/contracts
      │ Content-Type: application/x-www-form-urlencoded
      │ Body: reference, object, date_signature, duree, montant_global_prevu,
      │       customer_id, garantie_id, items[0][designation], items[0][quantity], ...
      │ Headers: X-CSRF-Token (_token field)
      ↓
Laravel routes/web.php → middleware [auth, checkStatus, permission:create contracts]
      ↓
StoreContractRequest::authorize() → true (relies on middleware)
StoreContractRequest::rules() → validates all fields
      ↓ (422 on failure → back with errors)
ContractController::store($request)
      ↓
DB::transaction → Contract::create + items()->create
      ↓
redirect to index
```

---

## 38. Backend → Database Flow

```
ContractController::store()
      ↓
DB::transaction(function () use ($request) {
    Contract::create([               ← Eloquent mass assignment
        'reference'             => $request->reference,
        'object'                => $request->object,
        'date_signature'        => $request->date_signature,
        'duree'                 => $request->duree,
        'montant_global_prevu'  => $request->montant_global_prevu,
        'customer_id'           => $request->customer_id,
        'garantie_id'           => $request->garantie_id,
    ]);
    foreach items:
        $contract->items()->create([...]);   ← Inserts with contract_id auto-set
})
      ↓
MySQL INSERT INTO contracts (...)
MySQL INSERT INTO item_contracts (...) [for each item]
      ↓ (after commit)
ContractObserver::created() → NotificationService
```

---

## 39. Database → Frontend Flow

```
MySQL contracts table
      ↓
Contract::with(['customer', 'items' => fn($q) → withSum + with('itemType'), 
                'missions', 'charges', 'garantie', 'attachments'])
      ↓
Contract Eloquent model
  ├── $contract->remain_days          (Attribute accessor calculation)
  ├── $contract->percent_remaining    (Attribute accessor calculation)
  ├── $contract->expiry_status        (Attribute accessor calculation)
  ├── $contract->expiry_badge_class   (Legacy accessor)
  ├── $contract->total_invoiced()     (Model method → DB query)
  ├── $contract->total_consumed()     (Model method → DB query)
  └── $contract->total_planned()      (Model method → collection sum)
      ↓
view('admin.contracts.show', compact('contract'))
      ↓
Blade template renders HTML
      ↓
Browser displays to user
```

---

## 40. Contract Financial Flow

```
contracts.montant_global_prevu          ← Stored: planned global amount
      ↓
item_contracts (unit_price, quantity)   ← Stored per item
      ↓
Contract::total_planned()               ← Calculated: sum(unit_price × quantity)
      ↓
attachment_items (actual_quantity)      ← Stored per billing period
      ↓
Contract::total_invoiced()              ← Calculated: sum(actual_qty × unit_price) [APPROVED only]
Contract::total_consumed()              ← Calculated: sum(actual_qty × unit_price) [ALL statuses]
      ↓
mission_orders (daily_rate × days)      ← HR cost source
charges.amount                          ← Direct expenses source
      ↓
ContractStatisticsService::calculate()  ← Aggregates all into stats array
      ↓
statistics.blade.php                    ← Displays financial KPIs and mission breakdown
```

**No automated financial calculations are triggered on contract creation or update.**  
All financial aggregations are **read-only queries** computed on demand.

---

## 41. Authentication & Authorization

### Authentication
- **System:** Laravel Breeze (Session-based)
- **Middleware stack:** `auth` → validates session, `checkStatus` → validates account active

### Authorization for Contracts
| Operation | Guard |
|-----------|-------|
| View list/detail/statistics | Route middleware `permission:view contracts` |
| Create form | Route middleware `permission:create contracts` |
| Save new contract | Route middleware `permission:create contracts` |
| Edit form | Route middleware `permission:edit contracts` |
| Update contract | Route middleware `permission:edit contracts` |
| Delete contract | Route middleware `permission:delete contracts` + `Gate::authorize('delete contracts')` |

### Blade Gates
```blade
@can('create contracts') → Add button
@can('edit contracts')   → Edit button (+ only if not 'archive' phase)
@can('delete contracts') → Delete button (+ only if not 'archive' phase)
```

### SuperAdmin
SuperAdmin bypasses all permission checks (registered in `AppServiceProvider`).

### Roles
Managed via `spatie/laravel-permission`. Contract permissions must be assigned to roles through the admin panel.

---

## 42. Validation

### Technical Validation (Confirmed)

| Rule | Field | Details |
|------|-------|---------|
| required | `reference` | Must be present |
| unique | `reference` | Global uniqueness in `contracts.reference` |
| max:110 | `reference` | varchar(110) constraint match |
| nullable | `object`, `date_signature`, `duree`, etc. | All nullable |
| date | `date_signature` | Valid date format |
| integer, min:1 | `duree` | Positive integer months |
| numeric, min:0 | `montant_global_prevu`, `unit_price`, `unit_cost` | Non-negative numbers |
| exists:customers,id | `customer_id` | Must reference valid customer |
| exists:garanties,id | `garantie_id` | Must reference valid garantie |
| exists:item_types,id | `items.*.item_type_id` | Must reference valid item type |
| in:annuelle,semestrielle | `items.*.frequency` | Enum validation |

### Business Rules in Validation
- `reference.unique` → triggers custom message: `__('contract_ref_unique')`
- `items.*.designation.required_with` → triggers: `__('item_designation_required')`

### Business Rules in Controller (not in Request)
- Garantie must be `active` + type `garantie_bonne_execution` + not linked (enforced in `create()`/`edit()` query, not validated in Request)
- Contract cannot be deleted if it has attachments (enforced in `destroy()`)

---

## 43. Exception Handling

### Validation Failure
**Source:** `StoreContractRequest` / `UpdateContractRequest` validation fails  
**Flow:** Laravel automatically redirects back with `$errors` bag  
**Frontend:** `@if($errors->any())` block displays all error messages

### Authorization Failure (Middleware)
**Source:** User lacks permission  
**Response:** 403 Forbidden (Laravel default)

### Authorization Failure (Gate)
**Source:** `Gate::authorize('delete contracts')` fails  
**Response:** 403 Forbidden

### Business Rule Violation (delete with attachments)
**Source:** `$attachmentsCount > 0` in `destroy()`  
**Response:** `back()->with('error', ...)` — stays on same page with error flash message

### Database Transaction Failure
**Source:** `DB::transaction()` throws exception  
**Response:** Transaction auto-rollback, exception propagates to Laravel error handler

### Model Not Found
**Source:** Route model binding for `{contract}` that doesn't exist  
**Response:** 404 Not Found (Laravel default)

---

## 44. HTTP Status Codes

| Operation | HTTP Status | Method |
|-----------|------------|--------|
| List contracts | `200 OK` | GET |
| Show contract | `200 OK` | GET |
| Show create form | `200 OK` | GET |
| Show edit form | `200 OK` | GET |
| Show statistics | `200 OK` | GET |
| Store (success) | `302 Redirect` to index | POST |
| Update (success) | `302 Redirect` to show | PUT |
| Destroy (success) | `302 Redirect` to index | DELETE |
| Destroy (has attachments) | `302 Redirect` back with error | DELETE |
| Validation failure (store/update) | `302 Redirect` back with errors | POST/PUT |
| Authorization failure | `403 Forbidden` | Any |
| Not found | `404 Not Found` | Any |

---

## 45. Business Rules

| Business Rule | Location | Trigger | Effect |
|---|---|---|---|
| Contract reference must be globally unique | `StoreContractRequest` rule `unique:contracts,reference` | On create | 422 validation error |
| Contract reference must be unique ignoring self | `UpdateContractRequest` rule `Rule::unique()->ignore($id)` | On update | 422 validation error |
| Garantie must be `active` + `garantie_bonne_execution` + unlinked | `ContractController::create/edit()` query | On form load | Only eligible garanties shown |
| Item designation required if items submitted | `StoreContractRequest` `required_with:items` | On submit | 422 error |
| Cannot delete contract with linked attachments | `ContractController::destroy()` guard | On delete | Redirect back with error |
| Protected items (with attachment_items) cannot be deleted | `ContractController::update()` | On update | Items kept in DB |
| Archive phase hides edit/delete buttons | `index.blade.php` `@if($phase !== 'archive')` | On render | Buttons hidden |
| Garantie is nullified when Garantie model deleted | `Garantie::booted()` | On garantie delete | `contracts.garantie_id` → NULL |
| Notifications sent to all `view contracts` users on CRUD | `ContractObserver` | After commit | Broadcast notifications |

---

## 46. Source of Truth

| Information | Source |
|---|---|
| Contract Reference | `contracts.reference` |
| Contract Object | `contracts.object` |
| Signature Date | `contracts.date_signature` |
| Duration | `contracts.duree` (in months) |
| Planned Amount | `contracts.montant_global_prevu` |
| Customer | `customers` via `contracts.customer_id` |
| Guarantee | `garanties` via `contracts.garantie_id` |
| Contract Items | `item_contracts` (child records) |
| Item Type | `item_types` via `item_contracts.item_type_id` |
| End Date | **Calculated** — `date_signature + duree months` (not stored) |
| Remaining Days | **Calculated** — `remainDays()` accessor |
| Phase (start/mid/end/archive) | **Calculated** — `expiryStatus()` accessor |
| Total Planned Revenue | **Calculated** — `Contract::total_planned()` |
| Total Invoiced | **Calculated** — `Contract::total_invoiced()` |
| Total Consumed | **Calculated** — `Contract::total_consumed()` |
| Missions under contract | `missions` via `missions.contract_id` |
| Attachments under contract | `attachments` via `missions.mission_id` (through) |
| Direct charges on contract | `charges` via `charges.contract_id` |
| Site info | **Not directly stored on contract** — accessible via `Customer → sites` |

---

## 47. Data Ownership

| Entity | Owns |
|--------|------|
| `contracts` | reference, object, date_signature, duree, montant_global_prevu |
| `customers` | company_name, short_name, address, phone, email, website, registration_number |
| `garanties` | reference, bank_name, amount, started_at, status, type |
| `item_contracts` | designation, quantity, unit_price, type, unit_cost, frequency |
| `item_types` | designation (master catalogue) |
| `missions` | reference, start_date, end_date, mob_dmob_days, status |
| `charges` | amount, date, description, type, ChargeType |
| `attachments` | date, ods, code_ref, type, frequency, status |
| `attachment_items` | actual_quantity, planned_quantity |

---

## 48. Dependency Map

```
ContractController (final)
        │
        ├── StoreContractRequest / UpdateContractRequest
        │         (validation rules → DB: contracts, garanties, customers, item_types, item_contracts)
        │
        ├── Contract (Model)
        │         ├── Customer (BelongsTo → customers)
        │         ├── Garantie (BelongsTo → garanties)
        │         ├── ItemContract (HasMany → item_contracts)
        │         │         ├── ItemType (BelongsTo → item_types)
        │         │         └── AttachmentItem (HasMany → attachment_items)
        │         ├── Mission (HasMany → missions)
        │         │         └── Attachment (HasMany → attachments)
        │         └── Charge (HasMany → charges)
        │
        ├── ContractStatisticsService  [statistics action only]
        │         ├── AttachmentItem (direct Eloquent)
        │         ├── DB::table('mission_orders')
        │         ├── DB::table('charges')
        │         └── DB::table('income_forecasts')
        │
        ├── Attachment (direct Model — for delete guard)
        │
        ├── Customer (direct Model — for form dropdowns)
        ├── Garantie (direct Model — for form dropdowns)
        ├── ItemType (direct Model — for form dropdowns)
        │
        └── DB::transaction() [store, update, destroy]
                │
                ▼
             MySQL (contracts, item_contracts)

ContractObserver [registered in AppServiceProvider]
        │
        └── NotificationService → users with 'view contracts'
```

---

## 49. End-to-End Execution Traces

### List Contracts

```
Browser → GET /admin/contracts?search=ABC&customer_id=3
  → middleware [auth, checkStatus, permission:view contracts]
  → ContractController::index(Request)
  → Contract::with(['customer','items','garantie'])
      ->withCount('items')
      ->where('customer_id', 3)
      ->where(fn → reference/object LIKE '%ABC%')
      ->orderByRaw(active first)
      ->paginate(10)
  → DB: SELECT contracts.*, COUNT(item_contracts) FROM contracts
         LEFT JOIN customers, item_contracts, garanties
         WHERE customer_id=3 AND (reference LIKE... OR object LIKE...)
         ORDER BY ... LIMIT 10
  → view('admin.contracts.index', compact('contracts', 'customers'))
  → Blade: loop $contracts → for each: compute phase in @php → render rows
  → HTML response to browser
```

### View Contract

```
Browser → GET /admin/contracts/42
  → middleware [auth, checkStatus, permission:view contracts]
  → ContractController::show(Contract $contract)   [route model binding]
  → $contract->load(['customer', 'items' => withSum + with('itemType'),
                      'missions', 'charges', 'garantie', 'attachments'])
  → DB: multiple SELECTs for each relation
  → Contract model: accessors computed lazily on access
  → view('admin.contracts.show', compact('contract'))
  → Blade: renders info cards, items table, missions table, attachments table
```

### Create Contract

```
Browser → GET /admin/contracts/create
  → middleware [permission:create contracts]
  → ContractController::create()
  → DB: SELECT customers, item_types, garanties (filtered)
  → view('admin.contracts.create', compact('customers','itemTypes','garanties'))
  → User fills form, adds items via JS, submits

Browser → POST /admin/contracts
  → StoreContractRequest validates
  → (fail) → redirect back with errors
  → (pass) → ContractController::store()
  → DB::transaction():
      INSERT INTO contracts (reference, object, ...) → returns $contract
      for each item: INSERT INTO item_contracts (contract_id, ...) 
  → (after commit) ContractObserver::created() → notifications
  → redirect to admin.contracts.index with flash success
```

### Update Contract

```
Browser → GET /admin/contracts/42/edit
  → middleware [permission:edit contracts]
  → ContractController::edit(Contract $contract)
  → $contract->load(['items.itemType', 'garantie.contracts'])
  → view 'admin.contracts.edit'
  → User modifies data, submits

Browser → PUT /admin/contracts/42
  → UpdateContractRequest validates
  → ContractController::update()
  → DB::transaction():
      UPDATE contracts SET ...
      SELECT protected item IDs (those with attachment_items)
      DELETE item_contracts WHERE contract_id=42 AND id NOT IN (protected)
      for each item in form:
          if protected → UPDATE item_contracts SET ...
          else → INSERT INTO item_contracts
  → ContractObserver::updated() → notifications if changed
  → redirect to admin.contracts.show/42
```

### Delete Contract

```
Browser → DELETE /admin/contracts/42  (via form with @method('DELETE'))
  → middleware [permission:delete contracts]
  → ContractController::destroy(Contract $contract)
  → Gate::authorize('delete contracts') → pass or 403
  → Guard: SELECT COUNT(attachments) linked via items
      if count > 0 → redirect back with error → STOP
  → DB::transaction():
      DELETE FROM item_contracts WHERE contract_id=42
      DELETE FROM contracts WHERE id=42
  → ContractObserver::deleted() → notifications
  → redirect to admin.contracts.index with flash success
```

### View Statistics

```
Browser → GET /admin/contracts/42/statistics
  → middleware [permission:view contracts]
  → ContractController::statistics(Contract $contract, ContractStatisticsService $service)
  → $stats = $service->calculate($contract)
      → multiple DB::table queries for revenue, costs, missions breakdown
  → view('admin.contracts.statistics', compact('contract', 'stats'))
```

---

## 50. Architectural Observations

### Confirmed Issues

1. **`site()` relationship with missing column**  
   `Contract::site()` uses `belongsTo(Site::class)` but `contracts.site_id` does not exist in the DB. This method always returns `null`. The note in the code itself says: *"The 'site_id' column is missing from the database schema for contracts."*

2. **Permission naming inconsistency**  
   Routes use `view contracts` (English), but `permissions_groups.php` declares `view contrats` (French spelling). This may cause mismatches in RBAC sync.

3. **Phase calculation duplicated in Blade**  
   The Contract phase/expiry logic is duplicated in:
   - `Contract::expiryStatus()` accessor (PHP)
   - `index.blade.php` `@php` block (Blade PHP)  
   Both calculate the same thing with slightly different implementations (thresholds differ: Model uses percentage, Blade uses thirds of totalDays).

4. **`charges.contract_id` orphan risk**  
   When a contract is deleted, charges with `contract_id` are NOT deleted and NOT nullified — they become orphaned records.

5. **Business rule enforced in view, not backend**  
   Archive phase hides edit/delete buttons only in the Blade view. A user could bypass the UI and send a direct HTTP PUT/DELETE request to edit or delete an archived contract.

### Potential Risks

1. **No soft delete** — deleted contracts and their items are permanently gone.
2. **Garantie linked to multiple contracts** — the "one garantie per contract" rule is enforced at the controller level only; there is no DB unique constraint.
3. **Missing cascade on contract delete for charges** — if charges exist for a contract (without `mission_id`), they are orphaned.

### Technical Debt

1. `Contract::site()` relationship is dead code referencing a non-existent column.
2. Phase calculation duplicated between model accessor and Blade `@php` block.
3. `permission_maps.php` appears to be a legacy file that doesn't include contract permissions — this file is outdated.

### Coupling

- `ContractController` is tightly coupled to 5 models (Contract, Customer, Garantie, ItemType, Attachment) and does direct DB queries via `DB::table()` in the delete guard. This is moderate coupling but manageable given the CRUD nature.
- `ContractStatisticsService` has high coupling to many tables (`mission_orders`, `charges`, `income_forecasts`, `attachment_items`, `attachments`) — this is appropriate for an analytics service.

### Separation Issues

- The Blade `@php` block in `index.blade.php` (lines 95-142) contains business logic (phase calculation, CSS class mapping). This belongs in the Model or a Blade Component.
- `ContractObserver::getPayload()` references `$contract->site->short_name` which always returns null (dead code + silent bug).

### Naming Issues

1. FK name `fk_contract_sites` on `customer_id` column is misleading (should be `fk_contract_customers`).
2. `permissions_groups.php` key `contrat_management` with permissions `view contrats` vs route middleware `view contracts` — inconsistent spelling.
3. `ChargeType` column in `charges` table uses PascalCase instead of snake_case.

---

## 51. Technical Debt Summary

| ID | Item | Risk | Location |
|----|------|------|---------|
| TD-1 | `Contract::site()` references non-existent DB column | High — silent null, misleading code | `Contract.php:140-145` |
| TD-2 | Phase logic duplicated in Model + Blade | Medium — risk of divergence | `Contract.php:98-121` + `index.blade.php:96-142` |
| TD-3 | No soft delete on contracts | Medium — data unrecoverable | Schema |
| TD-4 | Orphaned charges on contract delete | Medium — data integrity | `ContractController::destroy()` |
| TD-5 | Garantie uniqueness by convention only | Low — no DB constraint | `contracts.garantie_id` |
| TD-6 | `ContractObserver::getPayload()` has dead site code | Low — notifications show 'غير محدد' always | `ContractObserver.php:32` |
| TD-7 | Archive protection exists only in Blade UI | Low — bypassable via direct HTTP | `index.blade.php:229` |
| TD-8 | `permission_maps.php` is a legacy file out of sync | Low — appears unused for contracts | `config/permission_maps.php` |
| TD-9 | FK constraint naming `fk_contract_sites` misleading | Low — documentation/maintenance | DB schema |
| TD-10 | `permissions_groups.php` uses French `contrats` vs English `contracts` | Medium — potential RBAC mismatch | `config/permissions_groups.php:19` |

---

## 52. Final Architecture Summary

### Module Identity

| Attribute | Value |
|-----------|-------|
| Module Name | Contracts (العقود) |
| Controller | `App\Http\Controllers\Admin\ContractController` (final) |
| Model | `App\Models\Contract` |
| Table | `contracts` |
| Child Table | `item_contracts` |
| Views | `resources/views/admin/contracts/` (5 files) |
| Service | `App\Services\ContractStatisticsService` |
| Observer | `App\Observers\ContractObserver` |
| Form Requests | `StoreContractRequest`, `UpdateContractRequest` |
| Pattern | MVC — no Repository, no API Resource, no Livewire |
| Auth | Session (Breeze) + spatie/laravel-permission middleware |
| API | None — Web-only (Blade) |
| Soft Delete | No |
| Events | Observer (created, updated, deleted → Notification) |

### Direct Dependencies

| Module | How |
|--------|-----|
| `customers` | BelongsTo — contract belongs to a customer |
| `garanties` | BelongsTo — optional bank guarantee |
| `item_types` | Referenced by ItemContract — catalogue |
| `sites` | **Inferred only** — via Customer.sites; no direct DB link |

### Dependents (modules that use Contract data)

| Module | How |
|--------|-----|
| `missions` | HasMany — missions reference contracts via `contract_id` |
| `charges` | HasMany — charges reference contracts via `contract_id` |
| `attachments` | Via Mission (hasManyThrough) |
| `attachment_items` | Via ItemContract |
| Statistics Service | Aggregates contract data for KPIs |
| Notification system | ContractObserver sends CRUD notifications |
