---
paths:
  - resources/views/**
  - resources/css/**
  - resources/js/**
  - app/Enums/**
---

# UI Design System & Component Architecture

## 1. Strict Separation of Concerns (Backend, Frontend & CSS)
- **Zero Code Mixing:** Never mix Backend logic, Frontend markup, and CSS styling within the same file or block.
- **Backend Isolation (`app/`, `routes/`, `database/`):** Pure PHP/Laravel logic only. No HTML tags, inline styles, or frontend scripts inside Controllers, Actions, Services, or Models.
- **Frontend Isolation (`resources/views/`, `resources/js/`):** UI markup resides exclusively in designated frontend directories. Zero database queries or Eloquent calls inside Blade views. Pass data strictly via controllers/view-data.
- **CSS & Styling Isolation (`resources/css/`, Tailwind):**
  - **No Inline Styles:** The use of `style="..."` in HTML/Blade is **strictly prohibited**.
  - **Utility-First / External CSS:** Use Tailwind CSS utility classes directly in markup or clean CSS/SCSS files in `resources/css/`.
  - Never inject `<style>` blocks or raw CSS rules inside PHP files or JavaScript components.
- **Multi-File Response Mandate:** Output backend and frontend/CSS changes as completely separate code blocks with distinct file paths.

## 2. Mandatory Unified Button & Semantic Color System
All buttons must exclusively use the standardized Blade button components. Never write raw `<button>` or `<a>` elements with arbitrary, non-standard background classes or inline styles:
- `<x-primary-button>`: Main calls to action, submit, save, create, confirm, login, register, apply search/filters. Visual Token: GMTM Brand Green (`.btn-primary` -> `bg-brand-600 hover:bg-brand-700 dark:bg-brand-700 dark:hover:bg-brand-600 text-white`).
- `<x-secondary-button>`: Cancel, dismiss, close modals, reset forms, back to list/inventory, neutral secondary actions. Visual Token: Neutral bordered gray (`.btn-secondary` -> `bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300`).
- `<x-edit-button>`: Modifying or editing records/entities in page headers, cards, and detail views. Visual Token: Solid Amber (`.btn-edit` -> `bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 text-white shadow-sm shadow-amber-500/20`) with integrated white pencil SVG icon.
- `<x-danger-button>`: Destructive actions, delete account, purge records, terminate background jobs, drop entities. Visual Token: Destructive Rose/Red (`.btn-danger` -> `bg-red-600 hover:bg-red-700 dark:bg-red-700 text-white`).
- `<x-success-button>`: Positive confirmations, approvals, resolving errors, marking items as read, download PDF. Visual Token: Emerald Green (`.btn-success` -> `bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-600 text-white`).
- `<x-warning-button>`: Cautionary tasks, retry operations, temporary holds/pauses. Visual Token: Amber Yellow (`.btn-warning` -> `bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 text-white`).
- `<x-info-button>`: Data inspection, view changes/diffs, preview payloads, examine stack traces. Visual Token: Indigo Blue (`.btn-info` -> `bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-600 text-white`).

## 3. Mandatory Unified Table Component Architecture
Never write raw HTML `<table>` elements or ad-hoc raw buttons with inline SVGs for row operations. Exclusively use the unified `<x-table>` suite:
- `<x-table>`: Provides container (`rounded-xl border border-gray-100 dark:border-gray-700/60 bg-white dark:bg-gray-800 shadow-sm`), auto-scroll, and named slots (`<x-slot:toolbar>`, `<x-slot:header>`, default slot for `<tbody>`, `<x-slot:pagination>`).
- `<x-table.th>`: Standard uppercase typography, start alignment, and padding (`px-5 py-3 text-start font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-wider whitespace-nowrap`).
- `<x-table.tr>`: Standard table body row with subtle hover state transitions (`hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors duration-150`).
- `<x-table.td>`: Uniform padding, text tokens, vertical alignment (`px-5 py-3.5 text-xs text-gray-600 dark:text-gray-300 align-middle`).
- `<x-table.empty>`: Standard centered SVG icon, customizable `colspan`, and translated message (`<x-table.empty :colspan="6" :message="__('No records found.')" />`).
- `<x-table.actions>`: Actions wrapper (`inline-flex items-center gap-1.5 whitespace-nowrap`).
- `<x-table.action>`: Polymorphic button or link (`href="..."`) with automatic semantic themes, SVG icons, tooltips, and accessibility.
- `<x-table.action-view>`: Semantic Indigo button with eye SVG for inspecting, previews, or viewing details.
- `<x-table.action-edit>`: Semantic Amber button with pencil SVG for editing or modifying records.
- `<x-table.action-delete>`: Semantic Rose/Red button with trash SVG for deleting or terminating records.

## 4. Single Source of Truth for Colors, Badges & Semantic Functional Classification
- **Single Source of Truth (`<x-badge>` & `tokens.css`):**
  - Authoritative source: `resources/css/tokens.css` and `resources/views/components/badge.blade.php`.
  - Raw `<span>` with ad-hoc Tailwind classes (`bg-amber-50`, `bg-cyan-50`, etc.) is strictly forbidden.
  - Exclusively use `<x-badge :variant="...">`.
  - **Pure PHP Isolation in Enums:** Enums must never return raw HTML or CSS class strings. Enums must declare `badgeVariant(): string` returning a semantic token (`primary`, `info`, `neutral`, `success`, `danger`, `warning`).
- **3 Functional Role & Hierarchy Tiers (No "Rainbow / Confetti UI"):**
  - **Management & Executive Leadership (`isManagement()`):** Must use `primary` (`bg-brand-600/10 text-brand-800 dark:text-brand-300 border-brand-500/20`).
  - **Engineering & Specialist Roles (`isEngineer()`):** Must use `info` (`bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-500/20`).
  - **Field Operations & Technicians (`isTechnician()`):** Must use `neutral` (`bg-gray-500/10 text-gray-700 dark:text-gray-300 border-gray-500/20 dark:border-gray-600/30`).
- **Decoupling State from Role:**
  - Status indicators use state semantics paired with status dots (`:dot="true"`):
    - `success` (Emerald / Brand) with dot for Active / Completed.
    - `danger` (Rose / Red) with dot for Inactive / Suspended / Failed.
    - `warning` (Amber) with dot for On Leave / Pending / Paused.
- **Harmonized Light & Dark Mode:**
  - Badges use alpha-transparency tokens (`bg-*/10`, `border-*/20`, `text-* dark:text-*`). Never inject opaque `dark:bg-*-950/60`.
  - Form inputs, select dropdowns, and filters must use brand focus states (`focus:border-brand-600 focus:ring-brand-600`) across all themes.

## 5. Mandatory Unified Search, Filter & State Persistence
- **Single Source of Truth (`<x-global-filter>`):** The `<x-global-filter>` component is the sole standard for table search inputs, status/position dropdowns, and date-range filters. Custom one-off filter containers are strictly forbidden.
- **Mandatory Functional Role Grouping in Selectors (`<optgroup>`):**
  - Position filters and dropdowns must group options into the 3 standardized tiers:
    - `<optgroup label="{{ __('Management & Executive Leadership') }}">` (`isManagement()`)
    - `<optgroup label="{{ __('Engineering & Specialist Roles') }}">` (`isEngineer()`)
    - `<optgroup label="{{ __('Field Operations & Technicians') }}">` (`isTechnician()`)
- **Query State Persistence Across All Lifecycle Events:**
  - **Language Switching:** Retain all active parameters (`LaravelLocalization::getLocalizedURL($localeCode, null, [], true)`).
  - **Pagination:** Repositories and controllers returning paginated results MUST append `->withQueryString()`.
  - **Record Creation:** Form actions pass active parameters (`route('...', request()->query())`), and controller store actions redirect back preserving `$request->query()`.
  - **Record Editing:** Form actions pass active parameters (`route('...', array_merge(['model' => $id], request()->query()))`), and controller update actions redirect back preserving `$request->query()`.
  - **Record Deletion:** Delete confirmation forms pass active parameters, and controller destroy actions redirect back preserving `$request->query()`.
