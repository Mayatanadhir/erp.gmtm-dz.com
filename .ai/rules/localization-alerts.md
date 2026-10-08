---
paths:
  - lang/**
  - resources/views/**
  - app/Http/**
  - app/Notifications/**
---

# Localization, Alerts & Notifications Architecture

## 1. Mandatory Trilingual Localization Across All Views & Features
- **100% English Translation Master Keys (إلزام كتابة كل مفاتيح الترجمة بالإنجليزية حصراً):**
  - **In Code:** Every single translation key passed to `__('...')`, `@lang('...')`, Form Requests, validation attributes, status flashes, or notification messages **MUST be written in English as a single unified master key language** (e.g. `__('Users')`, `__('Confirm Password')`, `__('User created successfully.')`).
  - **In JSON Dictionaries:** In all three dictionary files (`lang/en.json`, `lang/ar.json`, `lang/fr.json`), the **key** (the property name on the left side of the colon `:`) **MUST strictly and exclusively be written in the English language**.
  - **Strict Zero-Tolerance Prohibition:** Writing keys in Arabic, French, or any non-English language as dictionary keys or inside `__('...')` in code is **strictly forbidden with zero tolerance**. Arabic and French text MUST ONLY appear as the **translated values** (on the right side of `:`) in `lang/ar.json` and `lang/fr.json`.
  - **Canonical Structure:**
    - `lang/en.json`: `"English Key": "English Key"`
    - `lang/ar.json`: `"English Key": "الترجمة باللغة العربية"`
    - `lang/fr.json`: `"English Key": "Traduction en français"`
- **Zero Hardcoded Strings:**
  - Strictly forbidden to output raw, unlocalized user-facing text, button captions, table headers, form labels, placeholders, badges, alerts, or tooltips inside Blade templates (`resources/views/**`), scripts (`resources/js/**`), or backend response messages.
  - All user-facing text must be wrapped in `__('...')` or `@lang('...')`.
- **Mandatory 3-Language Dictionary Synchronization (Arabic, English, French):**
  - Whenever any new English translation key is added, immediately and simultaneously add the key and its accurate translation to all three dictionary files (`lang/en.json`, `lang/ar.json`, `lang/fr.json`).
- **Strict Prohibition of Missing Keys & English Fallbacks:**
  - Never rely on default fallback to English for missing keys in Arabic or French views.
  - Maintain exact 1-to-1 key parity, identical key counts, and identical English key names across all three files at all times.
- **Technical Terminology Precision:**
  - Translate system, forensic, and technical terms with standard professional precision in both Arabic and French (e.g. TTL, Atomic Locks, UUID, Session Tracker, Audit Log, Queue Daemons, Stack Trace).
- **Pre-Completion Audit Verification:**
  - A feature is incomplete and non-compliant until verified that zero translation keys are missing in any of the 3 languages and zero non-English keys exist as dictionary keys.

## 2. Mandatory Unified Alerts & Notifications
- **Single Source of Truth for In-Page Alerts (`<x-alert>`):**
  - All in-page alerts, operational notices, and flash feedback MUST exclusively use the `<x-alert>` component (`<x-alert :variant="...">`).
  - Ad-hoc alert boxes, raw `<div>` containers with custom background colors, and inline styles are strictly prohibited.
  - **Semantic Variants:**
    - `success` (Emerald Green): Positive operational confirmations, record creation, update, and deletion success.
    - `danger` (Rose Red): Errors, operation failures, deletion blockers, validation summaries.
    - `warning` (Amber Yellow): Cautionary alerts, retry prompts, data sensitivity warnings, quarantine alerts.
    - `info` (Indigo Blue): Informational tips, technical details, preview notes.
    - `primary` (Brand Green): System-wide executive announcements.
- **Standardized Session Flash Keys:**
  - Controllers must strictly pass standardized session keys: `with('success', __('...'))`, `with('error', __('...'))`, `with('warning', __('...'))`, and `with('info', __('...'))`.
  - Views rendering session messages must map these keys directly into the corresponding `<x-alert>` variant.
- **Unified Notification Architecture (`SystemActivityAlert` & Database Notifications):**
  - All system activity notifications must follow the structured schema: `title`, `message`, `type`, `causer`, `extra`.
  - The `type` field must strictly map to semantic tokens: `created` / `success` (`success`), `updated` / `warning` (`warning`), `deleted` / `danger` (`danger`), `info` (`info`).
- **3-Tier Functional Role Classification & Recipient Scoping:**
  - **Targeting & Recipient Scoping:**
    - `Management & Executive Leadership` (`isManagement()`): Administrative events, security warnings, user management, and employee compensation notices dispatched exclusively to `Super-Admin` and `Admin`.
    - `Engineering & Specialist Roles` (`isEngineer()`): Technical notifications, instrument movement, calibration statuses, and metrology alerts.
    - `Field Operations & Technicians` (`isTechnician()`): Operational notices, field mission tasks, and unit maintenance events.
  - **Role Badge Harmonization:** Inside notification details, modals, and audit feeds, actor roles and employee positions MUST strictly use `<x-badge>` categorized into the 3 functional tiers (`primary` for Management, `info` for Engineering, `neutral` for Technicians).
  - **Decoupling State from Role:** Operational states of notifications (Read/Unread) must use `:dot="true"` and never be visually confused with functional job positions.
