---
paths:
  - app/Services/MediaOptimizationService.php
  - app/Http/Controllers/**
  - app/Http/Requests/**
  - app/Services/**
---

# Media Processing, CAS Deduplication & Zero Disk Space Leak Engine

## 1. Mandatory Centralized Gateway (`MediaOptimizationService`)
- Direct, uncompressed file storage via raw Laravel methods (e.g. `$request->file('...')->store('...')`, `Storage::putFile(...)`) is **strictly forbidden**.
- All uploaded images, avatars, profile photos, documents, and media attachments MUST pass through `app(MediaOptimizationService::class)`:
  - `optimizeImage($file, $directory, $disk)` — for images.
  - `optimizePdf($file, $directory, $disk)` — for PDF documents.
  - `optimize($file, $directory, $disk)` — for automatic MIME-based routing.

## 2. Compulsory WebP Conversion & Image Compression Standard
- All uploaded images (JPEG, PNG, JPG, etc.) must be compulsory converted to **WebP** format.
- Quality must be set to **80%**.
- Width proportionately scaled down capped at **1920px** (Full HD) using `scaleDown(width: 1920)`. Upscaling smaller images is strictly prohibited.
- All EXIF, camera metadata, and GPS geolocation data must be stripped for privacy and storage optimization (`strip: true`).

## 3. Ghostscript PDF Optimization
- PDF documents must be processed via Ghostscript (`gs`) using the `/ebook` profile (150dpi resolution, compatibility 1.4).
- Resilient fallback (`fallback_to_original`) is enforced if Ghostscript is unavailable on the host.

## 4. Mandatory Content-Addressable Storage (CAS) Deduplication
- All stored media artifacts must be named using their cryptographic **SHA-256 content hash** (`{sha256}.webp` / `{sha256}.pdf`).
- If an identical image or document is uploaded (by the same or different users), the system MUST detect the existing physical file on disk and reuse its path immediately, creating zero redundant duplicate files on disk.

## 5. Mandatory Reference-Aware Safe Deletion Protocol
- Direct, unconditional deletion via raw `Storage::delete($path)` is **strictly forbidden** for shared media assets.
- Before any file is unlinked from storage, the system MUST check whether other entities still reference that path using:
  - `MediaOptimizationService::isAssetInUse($path, $excludeEntityId)` — to check reference count.
  - `MediaOptimizationService::safeDelete($path, $disk, $excludeEntityId)` — to safely delete.
- If other records still reference the file, the physical file on disk MUST BE PRESERVED.
- The physical file is only permanently deleted (`Storage::delete()`) when the active reference count reaches zero.

## 6. Strict "Process & Destroy" Protocol (The Kill-Step)
- Temporary upload copies and intermediate scratch files in `storage/app/temp-media` or system temp must be immediately and permanently destroyed via `@unlink()` (`destroyTempFiles()`) upon completion.
- Zero raw, unoptimized, or temporary files may linger on the server disk.
