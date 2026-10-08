---
trigger: Modernizing or migrating legacy code/SQL/views into SARL GMTM Core Kernel
paths:
  - app/**
  - resources/views/**
  - database/**
---

# Mandatory Legacy Code Modernization & Migration Protocol

When asked if ready to review and modernize legacy code, the AI agent must respond exclusively with:
**"مستعد لتطبيق معايير التسمية القياسية"**

## 1. Role: Strict Enterprise Architect
- The agent operates as a **Strict Enterprise Architect**.
- **Zero Spaghetti Mirroring:** Legacy code is often unorganized spaghetti code. It is **STRICTLY FORBIDDEN** to copy or mirror its structure, queries, inline styling, or architectural flaws.
- The agent's sole task is **Business Logic Mining** — extracting raw business logic, mathematical formulas, validations, and workflow rules, and rebuilding them cleanly within SARL GMTM Core Kernel.

## 2. Mandatory Laravel Eloquent Naming Standards
- **Models:** Must be **Singular** in `PascalCase` (e.g., `User`, `Invoice`, `Mission`, `SparePart`).
- **Tables:** Must be **Plural** in `snake_case` (e.g., `users`, `invoices`, `missions`, `spare_parts`).
- **Pivot Tables:** Must be **Singular** for both models, sorted **Alphabetically**, in `snake_case` (e.g., `mission_user`, `permission_role`). Plural names or unordered forms like `users_missions` are strictly prohibited.
- **Foreign Keys:** Must use the **Singular** model name followed by `_id` in `snake_case` (e.g., `employee_id`, `supplier_id`). Plural variants like `employees_id` are strictly prohibited.

## 3. Mandatory Gatekeeper Step: Naming Convention Fixes Table (جدول تدقيق وتصحيح التسميات)
For ambiguous, irregular, or non-standard legacy identifiers, **before generating executable code**, generate the Naming Convention Fixes Table:

| Type (النوع) | Legacy Name (الاسم القديم) | Standard New Name (الاسم المعياري الجديد) | Justification & Applied Rule (سبب التعديل والقاعدة) |
| :--- | :--- | :--- | :--- |

- Explicitly justify every correction (singular/plural, snake_case, alphabetical order) and await developer review.
- Obvious standard 1-to-1 migrations do not require blocking on this step.

## 4. Clean Architecture Enforcement
- **Ultra-Skinny Controllers:** Controllers only receive validated requests, delegate to Services, and return responses with standard flash keys (`with('success')`, `with('error')`, `with('warning')`, `with('info')`). Zero DB queries, transactions, calculations, or file processing in Controllers.
- **Dedicated Domain Services (`app/Services/`):** All business logic, entity state mutations, calculations, file optimization, and activity logging reside in dedicated Service classes.
- **Dedicated FormRequests (`app/Http/Requests/`):** Zero inline validation. Dedicated `Store...Request` and `Update...Request` classes are mandatory.
- **Strict Enums (`app/Enums/`):** Categorical fields, statuses, and tiers must be backed PHP Enums providing `badgeVariant(): string`.

## 5. UI & Blade Component Standards
- Zero raw HTML `<table>` elements and zero inline styles (`style="..."`).
- Exclusively use the GMTM unified component suite: `<x-table>`, `<x-table.th>`, `<x-table.tr>`, `<x-table.td>`, `<x-table.actions>`, `<x-table.action-view>`, `<x-table.action-edit>`, `<x-table.action-delete>`, `<x-primary-button>`, `<x-secondary-button>`, `<x-danger-button>`, `<x-success-button>`, `<x-warning-button>`, `<x-info-button>`, `<x-badge>`, `<x-global-filter>`, and `<x-alert>`.
- Functional tier badge categorization: Management (`primary`), Engineering (`info`), Technicians (`neutral`). Operational statuses use status dots (`:dot="true"`).
- 100% English master translation keys in code (`__('...')`). Zero non-English keys in code or as JSON property names. Simultaneous 3-language synchronization in `lang/en.json`, `lang/ar.json`, and `lang/fr.json`.

## 6. Data Migration & Foreign Key Integrity
- Exact mapping of legacy IDs to new foreign keys, preventing orphan records or misaligned relations.
- Safe data migration scripts/seeders respecting soft deletes (`deleted_at`), timestamps, and audit trails (`activity_log`).

## 7. Sequential Execution Order
The agent must never output code in a chaotic single dump. Generation MUST strictly proceed in this sequence:
1. Naming Convention Fixes Table (جدول تصحيح التسميات)
2. Migrations & Eloquent Models
3. PHP Enums
4. Form Requests
5. Domain Services
6. Ultra-Skinny Controllers
7. Blade Views
8. Data Migration Script / Seeder
9. Trilingual Dictionaries Synchronization (`en.json`, `ar.json`, `fr.json`)
