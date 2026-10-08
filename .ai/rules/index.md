# Project Rules Index

Before planning or editing any file, find every row whose globs match the file path(s) in scope and read that rule file. Do not write code until you have read and are following every matching rule.

> **MANDATORY LIVING MEMORY DIRECTIVE:**
> - **Always consult `docs/project_state.md` FIRST** to understand models, relations (e.g., `ContractItem` linking contracts to `item_types`), and routes. Scanning raw code files blindly to discover system architecture is **STRICTLY PROHIBITED**.
> - **Always update `docs/project_state.md` and `docs/changelog.md`** before concluding any task that adds or modifies models, relationships, routes, or features.


| Applies to (Globs) | Rule file |
| :--- | :--- |
| `resources/views/**`, `resources/css/**`, `resources/js/**`, `app/Enums/**` | `.ai/rules/ui-design-system.md` |
| `lang/**`, `resources/views/**`, `app/Http/**`, `app/Notifications/**` | `.ai/rules/localization-alerts.md` |
| `config/permissions.php`, `routes/**`, `app/Http/Controllers/**`, `resources/views/system/**`, `app/Services/SystemTableService.php` | `.ai/rules/security-rbac.md` |
| `app/Services/MediaOptimizationService.php`, any Controller/Service/Request handling file/image/PDF uploads or deletion | `.ai/rules/media-storage.md` |
| Creating or modifying any new Service or new Page/View | `.ai/rules/ecosystem-sync.md` |
| Modernizing or migrating legacy code/SQL/views into SARL GMTM Core Kernel | `.ai/rules/legacy-modernization.md` |
| `app/Http/Controllers/SystemTableController.php` | `.ai/rules/controllers.md` |
| `app/Enums/**` | `.ai/rules/enums.md` |
| `app/Services/**`, `config/media.php`, `app/Observers/**` | `.ai/rules/media.md` |
| `app/Services/**`, `routes/**`, `config/permissions.php`, `app/Http/Controllers/**` | `.ai/rules/permissions.md` |
| `app/Services/**`, `resources/views/system/**`, `app/Http/Controllers/SystemTableController.php`, `app/Services/SystemTableService.php` | `.ai/rules/services.md` |
| `resources/views/system/**` | `.ai/rules/system.md` |
| `app/Notifications/**`, `resources/views/system/notifications.blade.php` | `.ai/rules/notifications.md` |
| `docs/**` | `.ai/rules/documentation.md` |

> **Grep Catch-All:** Run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses.
