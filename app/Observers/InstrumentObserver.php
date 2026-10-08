<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Instrument;
use App\Services\MediaOptimizationService;
use Illuminate\Support\Facades\Storage;

class InstrumentObserver
{
    /**
     * Handle the Instrument "saving" event.
     * Automatically computes or clears the image_hash in the background based on image_path.
     */
    public function saving(Instrument $instrument): void
    {
        if ($instrument->isDirty('image_path')) {
            $oldPath = $instrument->getOriginal('image_path');
            if (! blank($oldPath) && $oldPath !== $instrument->image_path) {
                app(MediaOptimizationService::class)->safeDelete((string) $oldPath, 'public', $instrument->id);
            }

            if (blank($instrument->image_path)) {
                $instrument->image_hash = null;
            } else {
                $disk = Storage::disk('public');
                if ($disk->exists($instrument->image_path)) {
                    $instrument->image_hash = hash('sha256', (string) $disk->get($instrument->image_path));
                } else {
                    $instrument->image_hash = hash('sha256', (string) $instrument->image_path);
                }
            }
        }
    }

    /**
     * Handle the Instrument "forceDeleted" event.
     */
    public function forceDeleted(Instrument $instrument): void
    {
        $mediaService = app(MediaOptimizationService::class);
        if (! blank($instrument->image_path)) {
            $mediaService->safeDelete($instrument->image_path, 'public', $instrument->id);
        }
    }
}
