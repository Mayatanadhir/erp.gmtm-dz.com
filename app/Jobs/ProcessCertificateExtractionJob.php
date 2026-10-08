<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ExtractionStatus;
use App\Models\CalibrationCertificateExtraction;
use App\Notifications\SystemActivityAlert;
use App\Services\AiPdfExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessCertificateExtractionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CalibrationCertificateExtraction $extraction
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AiPdfExtractionService $aiService): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        @ini_set('max_execution_time', '300');

        $this->extraction->update([
            'status' => ExtractionStatus::Processing,
        ]);

        try {
            $extractedData = $aiService->extractForRecord($this->extraction);

            $this->extraction->update([
                'status' => ExtractionStatus::Completed,
                'extracted_data' => $extractedData,
                'error_message' => null,
            ]);

            // Optional: notify the user if available
            if ($this->extraction->user) {
                try {
                    $this->extraction->user->notify(new SystemActivityAlert(
                        title: 'Certificate AI Extraction Ready',
                        message: "AI extraction for certificate [{$this->extraction->file_name}] completed successfully.",
                        type: 'info',
                        causer: 'Gemini AI',
                        extra: ['extraction_id' => $this->extraction->id]
                    ));
                } catch (Throwable $e) {
                    Log::warning("Could not dispatch notification for extraction [{$this->extraction->id}]: {$e->getMessage()}");
                }
            }
        } catch (Throwable $e) {
            Log::error("AI PDF extraction failed for record [{$this->extraction->id}]: {$e->getMessage()}");

            $this->extraction->update([
                'status' => ExtractionStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
