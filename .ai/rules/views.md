---
paths:
  - 'resources/views/**'
---

# Views

## Mandatory Unified Button Components and Semantic Color System
Enforce unified button components and semantic color tokens across all Blade views. The AI assistant must strictly use:
1. `<x-primary-button>` (Brand Green `.btn-primary`) for main calls to action (Submit, Save, Create, Login, Filter).
2. `<x-secondary-button>` (Gray Border `.btn-secondary`) for Cancel, Dismiss, Close, Back to List, and neutral actions.
3. `<x-edit-button>` (Solid Amber `.btn-edit`) with white pencil SVG icon for Edit and Modify actions in page headers, cards, and views.
4. `<x-danger-button>` (Rose Red `.btn-danger`) for Delete, Terminate, and destructive operations.
5. `<x-success-button>` (Emerald Green `.btn-success`) for Approve, Resolve, Mark as Read, Download PDF.
6. `<x-warning-button>` (Amber `.btn-warning`) for Retry, Pause, Cautionary actions.
7. `<x-info-button>` (Indigo `.btn-info`) for Inspect, View Changes, Payload, and Technical details.
Never use unstyled or ad-hoc `<button>` or `<a>` elements with arbitrary colors. Zero inline style attributes.

## Mandatory Trilingual Localization & 100% English Keys Standard (AR, EN, FR)
All translation keys across all Blade templates, components, and controllers must strictly be written in English as the single unified master key language (e.g. `__('English Key')`). In addition, ALL keys in `lang/en.json`, `lang/ar.json`, and `lang/fr.json` must strictly be in English. Writing Arabic or French dictionary keys in code or as JSON property names is strictly prohibited with zero tolerance. Arabic and French text may only appear as the translated values in `ar.json` and `fr.json`. Zero hardcoded text. Every new English key MUST immediately be registered and translated into all 3 languages maintaining 100% 1-to-1 key parity with zero missing keys or English fallbacks in Arabic/French.

## Mandatory Single Source of Truth for Badges and Functional Classification
All badges and chips must exclusively use <x-badge :variant="..."> backed by tokens.css and badgeVariant() on Enums. No ad-hoc Tailwind classes or raw <span>. Categorize roles strictly by functional hierarchy: Management uses 'primary' (brand token), Engineering uses 'info', Technicians use 'neutral'. Operational statuses must use state semantics with :dot="true" ('success', 'danger', 'warning'). Light and dark modes must use curated alpha tokens (bg-*/10, text-* dark:text-*) with zero manual dark overrides like dark:bg-*-950/60.

## Mandatory Unified Search, Filter & State Persistence Architecture
The AI assistant must strictly adhere to unified search and filtering standards across all tabular pages:
1. Unified Source (<x-global-filter>): All search bars, filter dropdowns, and date filters must exclusively use <x-global-filter>. Ad-hoc search/filter markup is strictly prohibited.
2. Functional Hierarchy Classification: In all position filters and dropdown selects, positions must be grouped into the 3 standardized functional tiers via <optgroup>: Management & Executive Leadership (`isManagement()`), Engineering & Specialist Roles (`isEngineer()`), and Field Operations & Technicians (`isTechnician()`).
3. Mandatory State Persistence: Active query strings (`search`, `position`, `status`, etc.) must be strictly preserved across:
   - Language switching (via <x-language-switcher> / LaravelLocalization).
   - Pagination (all paginators must append `->withQueryString()`).
   - Create operations (modal form action URLs pass `request()->query()` and controller store redirects back with `$request->query()`).
   - Edit operations (modal form action URLs pass `request()->query()` and controller update redirects back with `$request->query()`).
   - Delete operations (delete modal actions pass `request()->query()` and controller destroy redirects back with `$request->query()`).

## Mandatory Unified Alerts, Notifications, Functional Classification & Trilingual Parity
The AI assistant must strictly adhere to unified alert and notification standards:
1. Single Source of Truth for Alerts (<x-alert>): All in-page alerts, operational notices, and flash feedback must exclusively use <x-alert :variant="..."> (`success`, `danger`, `warning`, `info`, `primary`). Ad-hoc alert containers or inline-styled boxes are strictly prohibited. Standardized session flash keys: `with('success')`, `with('error')`, `with('warning')`, `with('info')`.
2. Notification Architecture: System activity notifications (`SystemActivityAlert`) must follow the unified schema (`title`, `message`, `type`, `causer`, `extra`) mapping to semantic badge variants (`created` -> `success`, `updated` -> `warning`, `deleted` -> `danger`, `info` -> `info`).
3. Functional Classification: Notifications and alerts must be scoped by the 3 functional tiers: Management & Executive Leadership (`isManagement()`) for administrative notices, Engineering & Specialist Roles (`isEngineer()`) for technical and calibration alerts, Field Operations & Technicians (`isTechnician()`) for logistics/field tasks. Role chips in notifications must use <x-badge> with functional tier variants (`primary`, `info`, `neutral`). State dots (`:dot="true"`) must be reserved for operational status (Read/Unread).
4. Trilingual Translation: All alert messages and notification titles/bodies must be authored with 100% English master keys in `__('...')` and registered simultaneously in `lang/en.json`, `lang/ar.json`, and `lang/fr.json` maintaining 100% 1-to-1 parity with zero non-English dictionary keys.

## Mandatory Comprehensive Ecosystem & Dependency Synchronization Upon Adding New Views
Whenever creating or modifying any view/page:
1. **Trilingual Localization:** Extract 100% of user-facing text, headers, labels, placeholders, errors, and modal text into English master keys in `__('...')`, and simultaneously synchronize with `lang/en.json`, `lang/ar.json`, and `lang/fr.json` (1-to-1 parity, 0 non-English keys).
2. **Alerts & UI Feedback:** Standardize controller flash redirects (`with('success')`, `with('error')`, `with('warning')`, `with('info')`) and ensure corresponding `<x-alert>` components are rendered in the view.
3. **Table & Query State Persistence:** If tabular, use `<x-table>`, polymorphic `<x-table.action-*>`, `<x-global-filter>`, 3 functional `<optgroup>` tiers, and ensure query parameters persist across pagination (`->withQueryString()`) and CRUD redirects (`$request->query()`).
4. **Design System & Badges:** Use `<x-badge>` functional variants (`primary`, `info`, `neutral`), standardized `<x-*-button>` components, and zero inline styles.
5. **RBAC & Navigation:** Guard routes and view actions with `@can`, declare permissions in `config/permissions.php`, and wire into navigation sidebars/tabs.
6. **Automated Verification:** Add Feature tests, run `vendor/bin/pint --dirty --format agent`, and update living memory (`docs/changelog.md`, `docs/project_state.md`, `docs/ARCHITECTURE_LOG.md`).
