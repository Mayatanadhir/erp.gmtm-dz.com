<?php

declare(strict_types=1);

namespace App\Http\Controllers\Metrology;

use App\Enums\ExtractionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Metrology\StoreCertificateExtractionRequest;
use App\Jobs\ProcessCertificateExtractionJob;
use App\Models\CalibrationCertificateExtraction;
use App\Services\MediaOptimizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalibrationCertificateExtractionController extends Controller
{
    /**
     * Upload a calibration certificate PDF and dispatch the asynchronous extraction job.
     */
    public function upload(
        StoreCertificateExtractionRequest $request,
        MediaOptimizationService $mediaService
    ): JsonResponse {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        @ini_set('max_execution_time', '300');

        $file = $request->file('certificate_file');
        $fileHash = hash_file('sha256', $file->getRealPath());
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        $storedPath = $mediaService->optimizePdf($file, 'certificates/extractions');

        /** @var CalibrationCertificateExtraction $extraction */
        $extraction = CalibrationCertificateExtraction::create([
            'user_id' => $request->user()->id,
            'equipment_id' => $request->input('equipment_id'),
            'file_path' => $storedPath,
            'file_hash' => $fileHash,
            'file_name' => $originalName,
            'file_size' => $fileSize,
            'status' => ExtractionStatus::Pending,
            'ai_model' => (string) config('services.gemini.model', 'gemini-3.6-flash'),
        ]);

        $queueConnection = (string) config('services.gemini.queue_connection', 'sync');

        ProcessCertificateExtractionJob::dispatch($extraction)
            ->onConnection($queueConnection);

        $extraction->refresh();

        $isCompleted = ($extraction->status === ExtractionStatus::Completed);

        return response()->json([
            'id' => $extraction->id,
            'status' => $extraction->status->value,
            'file_name' => $extraction->file_name,
            'extracted_data' => $extraction->extracted_data,
            'message' => $isCompleted
                ? __('Certificate extracted successfully.')
                : __('Certificate uploaded successfully. Extraction started in background.'),
        ], $isCompleted ? 200 : 202);
    }

    /**
     * Check extraction status and progress.
     */
    public function status(CalibrationCertificateExtraction $extraction): JsonResponse
    {
        return response()->json([
            'id' => $extraction->id,
            'status' => $extraction->status->value,
            'error_message' => $extraction->error_message,
            'is_applied' => $extraction->is_applied,
            'ai_key_index' => $extraction->ai_key_index,
            'ai_model' => $extraction->ai_model,
        ]);
    }

    /**
     * Get extracted data preview.
     */
    public function preview(CalibrationCertificateExtraction $extraction): JsonResponse
    {
        return response()->json([
            'id' => $extraction->id,
            'status' => $extraction->status->value,
            'file_name' => $extraction->file_name,
            'file_path' => $extraction->file_path,
            'extracted_data' => $extraction->extracted_data,
            'is_applied' => $extraction->is_applied,
            'error_message' => $extraction->error_message,
        ]);
    }

    /**
     * Mark the extracted data as applied to a certificate form.
     */
    public function markApplied(Request $request, CalibrationCertificateExtraction $extraction): JsonResponse
    {
        $extraction->update([
            'is_applied' => true,
            'applied_at' => now(),
            'applied_certificate_id' => $request->input('certificate_id'),
        ]);

        return response()->json([
            'success' => true,
            'is_applied' => true,
            'message' => __('Extraction marked as applied.'),
        ]);
    }

    /**
     * List recent unapplied extractions for quick re-use or selection.
     */
    public function unapplied(Request $request): JsonResponse
    {
        $query = CalibrationCertificateExtraction::query()
            ->unapplied()
            ->latest()
            ->limit(10);

        if ($request->filled('equipment_id')) {
            $query->where(function ($q) use ($request): void {
                $q->where('equipment_id', $request->integer('equipment_id'))
                    ->orWhereNull('equipment_id');
            });
        }

        $extractions = $query->get(['id', 'file_name', 'equipment_id', 'status', 'extracted_data', 'created_at']);

        return response()->json([
            'extractions' => $extractions,
        ]);
    }
}
