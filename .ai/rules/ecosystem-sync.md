---
trigger: Creating or modifying any new Service or new Page/View
paths:
  - app/Services/**
  - resources/views/**
  - app/Http/Controllers/**
  - routes/**
---

# Mandatory Comprehensive Ecosystem & Dependency Synchronization

When adding or modifying any **new service** (`app/Services/`, Actions, Repositories, Jobs) or **new page/view** (`resources/views/`, Controllers, Routes), the task is **not complete** until all 9 checkpoints below are systematically reviewed, updated, and synchronized:

## 1. Trilingual Localization Synchronization (`lang/en.json`, `lang/ar.json`, `lang/fr.json`)
- Extract 100% of user-facing strings, table headers, form labels, validation error messages, badge labels, modal titles, button captions, and activity log descriptions.
- Author every translation master key strictly in English (`__('...')`).
- Simultaneously add the master keys and their accurate translations to all 3 dictionary files maintaining exact 1-to-1 parity and zero non-English dictionary keys.

## 2. Alerts, Flash Messages & UI Feedback (`<x-alert>`, Controllers)
- Standardize all controller flash redirects using uniform keys: `with('success', __('...'))`, `with('error', __('...'))`, `with('warning', __('...'))`, and `with('info', __('...'))`.
- The corresponding Blade template MUST contain `<x-alert>` components bound to session status/errors to guarantee immediate visual feedback.

## 3. Audit Trail Forensics & Description Localization (`activity_log`, `SystemTableService`)
- If the new service or page executes domain mutations (create, update, delete, toggle, process), it must record Spatie activity logs with actor (`causedBy`), subject (`performedOn`), and payload properties.
- Any new dynamic activity log description format MUST immediately be registered with regex matchers in `SystemTableService::translateActivityDescription()` and translated across Arabic, French, and English.

## 4. Notifications & System Activity Alerts (`app/Notifications/`, `SystemActivityAlert`)
- If the service dispatches notifications, it must use the structured `SystemActivityAlert` schema (`title`, `message`, `type`, `causer`, `extra`).
- Scoped strictly by the 3 functional tiers: Management & Executive Leadership (`isManagement()`) for administrative notices, Engineering & Specialist Roles (`isEngineer()`) for technical events, and Field Operations & Technicians (`isTechnician()`) for logistics/field tasks.

## 5. Search, Filter & Query State Persistence (`<x-global-filter>`, Repositories)
- If the new page presents tabular or list data, it MUST integrate `<x-global-filter>`.
- Position selectors MUST be categorized into the 3 functional `<optgroup>` tiers.
- All paginators must append `->withQueryString()`, and all CRUD redirect actions must preserve `$request->query()`.

## 6. Unified Design System & Semantic Color Tokens (`<x-table>`, `<x-badge>`, `<x-*-button>`)
- Tables must use the standardized `<x-table>` suite (`<x-table.th>`, `<x-table.tr>`, `<x-table.td>`, `<x-table.actions>`).
- Action buttons must use standardized polymorphic components (`<x-table.action-view>`, `<x-table.action-edit>`, `<x-table.action-delete>`).
- Badges must use `<x-badge>` mapped to functional tiers (`primary`, `info`, `neutral`).
- Zero inline styles (`style="..."` strictly forbidden).

## 7. RBAC Permissions & Navigation Hierarchy (`config/permissions.php`, Menus, Tabs)
- Declare the entity permissions in `config/permissions.php` under `$groups` and synchronize using `php artisan permissions:sync-tables`.
- Guard routes via `permission:...` middleware and UI elements with `@can`.
- Integrate the new page into appropriate navigation hubs, sidebar tabs (`<x-*-tabs>`), and tool icons (`<x-tool-icon>`).

## 8. Automated Feature Verification & Pint Formatting
- Create or extend automated tests (`tests/Feature/`) verifying page rendering, permissions, CRUD flows, query state persistence, and translation parity.
- Run `vendor/bin/pint --dirty --format agent` to format all modified PHP code.
- Run `php artisan test --compact` with narrowed filter to verify all tests pass.

## 9. Mandatory Post-Flight Living Memory Updates
- `docs/changelog.md`: Always append concise entries for all meaningful code changes, refactors, and bug fixes.
- `docs/project_state.md`: Update ONLY when models, schema, routes, services, commands, or system configurations are created, modified, or deleted.
- `docs/ARCHITECTURE_LOG.md`: Record a new ADR ONLY for major structural/architectural decisions. NEVER for routine bug fixes, styling tweaks, minor refactors, or copy changes.
