# Documentation & Living Memory Rules (docs/**)

## 1. Mandatory Documentation-First Protocol (حظر مسح الكود العشوائي)
- **STRICT PROHIBITION:** The AI assistant is strictly forbidden from opening or scanning raw PHP/Blade code files to discover database schemas, models, relations, routes, or architectural patterns.
- **Single Source of Truth:** You MUST inspect `docs/project_state.md` and specific domain documentation files (`docs/* Code Details.md`) first to understand existing entities, relationships, and business logic before touching any code.
- **Specific Living Domain Connections:**
  - `ContractItem` (`contract_items`) is the single entity connecting Contracts (`contracts`) with Item Types (`item_types` / catalogue of article types).
  - Contracts dropdown selection across views and forms is strictly restricted to **ACTIVE contracts only** (`is_active` / unexpired); expired or terminated contracts must never appear in selection lists.
- **NEVER read `docs/ARCHITECTURE_LOG.md` or `docs/changelog.md` in full.**
- When specific context is needed, use `grep_search` or slice notation (`StartLine` and `EndLine`) to read only the relevant ADR or changelog section.

## 2. Mandatory Post-Flight Update Protocol (إلزامية التحديث الفوري للوثائق)
- **A task is NOT finished until documentation is synchronized.**
- **`docs/project_state.md`**: You MUST update this file whenever models, migrations, relationships, routes, services, repositories, or system configurations are added, modified, or removed. Failing to update `docs/project_state.md` leaves memory stale and wastes tokens in future interactions.
- **`docs/changelog.md`**: Always log concise entries under the current release/timestamp for all meaningful code changes, refactors, and feature additions.
- **`docs/ARCHITECTURE_LOG.md`**: Record a new ADR ONLY for major structural decisions (e.g. new domain module, architectural redesign, new third-party engine). NEVER create ADRs for routine bug fixes, styling tweaks, minor refactors, or copy changes.

## 3. Strict Token-Economy Response Directive
- **Zero Document Echoing:** Never output full file contents or large excerpts of `docs/` files in chat messages.
- Always report documentation updates as a concise summary:
  - Target file path
  - 3 to 5 bullet points summarizing changes
- Deliver exact code, diffs, and test results directly without conversational filler.

## 4. Archival Standards
- Historical releases prior to `v1.0.36` are archived in `docs/archive/core_kernel_archive.md`.
- Historical pre-Point-Zero logs are archived in `docs/archive/legacy_changelog.md` and `docs/archive/legacy_ARCHITECTURE_LOG.md`.

