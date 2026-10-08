---
paths:
  - config/permissions.php
  - routes/**
  - app/Http/Controllers/**
  - resources/views/system/**
  - app/Services/SystemTableService.php
---

# Security, Quarantine & RBAC Architecture

## 1. Strict Security Quarantine & Mandatory Developer Verification (System Module)
- **High-Security Quarantine Targets:**
  - `resources/views/system/**`
  - `app/Http/Controllers/SystemTableController.php`
  - `app/Services/SystemTableService.php`
  - `system-tables.*` routes
- **Operational Quarantine Rules:**
  - This module is strictly private, confidential, and security-critical for system forensics, audit trails, and infrastructure inspection.
  - **Zero Code Mixing with Standard Pages:** Completely isolated from public pages, user dashboards, and everyday application features. Never leak or embed system tables, background queue details, or raw forensic logs into regular user interfaces.
  - **Mandatory Developer Pre-Approval for Any Additions:** Any new feature, route, query, or view modification touching this security module must be done under explicit developer supervision.
  - **Mandatory Agent Check Question (السؤال التأكيدي الإلزامي):**
    Before adding or modifying any feature that interacts with system tables or security data, the AI agent **MUST explicitly ask the developer**:
    > *"هل هذه الإضافة تنتمي إلى هذا الملف/القسم الأمني السري أم لا؟"*
    > ("Does this addition belong to this private security module or not?")
    and await developer clarification and explicit approval before modifying or creating files.

## 2. Mandatory RBAC Authorization & Static Permissions Registry Integration
- **Mandatory Permission Shield for All New Services & Pages:**
  - Every new service, business feature, administrative dashboard, route, or web page MUST be strictly integrated with and guarded by the system's RBAC permissions architecture.
  - Unprotected, unguarded public access to domain features or management pages is strictly forbidden.
  - Routes must be protected via permission middleware (e.g. `middleware('permission:view ...')`) or controller-level authorization (`Gate::authorize(...)`, `$this->authorize(...)`, `$user->can(...)`).
  - Blade navigation links, action buttons, and UI controls must be wrapped in authorization gates (`@can(...)` or `@if(Auth::user()->can(...))`).
- **Mandatory Registration in the Static Permissions Registry (`config/permissions.php`):**
  - The permissions system is strictly code-first and static. Database schema introspection is deprecated.
  - Any new service or page entity MUST be formally declared in `config/permissions.php` under the appropriate `$groups` entry with its icon, translation key, and entity name.
  - Standard CRUD permissions (`view [entity]`, `create [entity]`, `edit [entity]`, `delete [entity]`) or specialized action permissions must be explicitly registered.
- **Mandatory Permissions Synchronization:**
  - After updating `config/permissions.php`, the agent MUST run:
    ```bash
    php artisan permissions:sync-tables
    ```
    to persist newly registered permissions in the database, automatically assign them to `Super-Admin`, and prune any obsolete permissions.
- **Mandatory Developer Clarification Protocol (Strict Inquire-First Rule):**
  - If unsure, in doubt, or if the exact permission names, verbs, or entity grouping to apply for any new service or page are unrecognized, the AI assistant **MUST explicitly ask the developer for clarification before writing code or modifying files**:
    > *"ما هي أسماء وصيغ الصلاحيات المعتمدة لهذه الخدمة/الصفحة الجديدة لإضافتها إلى القائمة الثابتة (`config/permissions.php`)؟"*
    > ("What are the approved permission names and format for this new service/page to add to the static catalog?")
  - The agent MUST wait for developer clarification and approval before proceeding with file creation or modifications.
