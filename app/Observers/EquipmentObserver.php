<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Equipment;
use App\Services\MediaOptimizationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Storage;

class EquipmentObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Equipment "saving" event.
     * Automatically computes or clears the image_hash in the background based on image_path.
     */
    public function saving(Equipment $equipment): void
    {
        if ($equipment->isDirty('image_path')) {
            if (blank($equipment->image_path)) {
                $equipment->image_hash = null;
            } else {
                $disk = Storage::disk('public');
                $fullPath = $disk->path($equipment->image_path);
                if (file_exists($fullPath)) {
                    $equipment->image_hash = hash_file('sha256', $fullPath);
                } elseif ($disk->exists($equipment->image_path)) {
                    $equipment->image_hash = hash('sha256', (string) $disk->get($equipment->image_path));
                }
            }
        }
    }

    /**
     * Handle the Equipment "updated" event.
     * Cleans up old replaced media files after transaction commit.
     */
    public function updated(Equipment $equipment): void
    {
        $mediaService = app(MediaOptimizationService::class);

        if ($equipment->wasChanged('image_path')) {
            $oldPath = $equipment->getOriginal('image_path');
            if (! blank($oldPath) && $oldPath !== $equipment->image_path) {
                $mediaService->safeDelete((string) $oldPath, 'public', $equipment->id);
            }
        }

        if ($equipment->wasChanged('certificate_path')) {
            $oldCert = $equipment->getOriginal('certificate_path');
            if (! blank($oldCert) && $oldCert !== $equipment->certificate_path) {
                $mediaService->safeDelete((string) $oldCert, 'public', $equipment->id);
            }
        }
    }

    /**
     * Handle the Equipment "forceDeleted" event.
     */
    public function forceDeleted(Equipment $equipment): void
    {
        $mediaService = app(MediaOptimizationService::class);
        if (! blank($equipment->image_path)) {
            $mediaService->safeDelete($equipment->image_path, 'public', $equipment->id);
        }
        if (! blank($equipment->certificate_path)) {
            $mediaService->safeDelete($equipment->certificate_path, 'public', $equipment->id);
        }
    }
}
