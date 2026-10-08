<?php

declare(strict_types=1);

namespace Tests\Feature\Metrology;

use App\Enums\ExtractionStatus;
use App\Jobs\ProcessCertificateExtractionJob;
use App\Models\CalibrationCertificateExtraction;
use App\Models\User;
use App\Services\AiPdfExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CalibrationCertificateExtractionTest extends TestCase
{
    use RefreshDatabase;

    protected User $authorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permissions:sync-tables');

        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);

        $this->authorizedUser = User::factory()->create(['email_verified_at' => now()]);
        $this->authorizedUser->assignRole($superRole);
    }

    /**
     * An authenticated user with permission can upload a PDF and dispatch the extraction job.
     */
    public function test_authorized_user_can_upload_pdf_and_dispatch_job(): void
    {
        Storage::fake('public');
        Queue::fake();

        $fakePdf = UploadedFile::fake()->create('calibration-cert.pdf', 512, 'application/pdf');

        $response = $this->actingAs($this->authorizedUser)
            ->postJson(route('metrology.extractions.upload'), [
                'certificate_file' => $fakePdf,
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure(['id', 'status', 'file_name', 'message'])
            ->assertJsonPath('status', ExtractionStatus::Pending->value);

        $this->assertDatabaseHas('calibration_certificate_extractions', [
            'user_id' => $this->authorizedUser->id,
            'status' => ExtractionStatus::Pending->value,
            'file_name' => 'calibration-cert.pdf',
        ]);

        Queue::assertPushed(ProcessCertificateExtractionJob::class);
        Queue::assertPushed(ProcessCertificateExtractionJob::class, fn (ProcessCertificateExtractionJob $job): bool => $job->connection === (string) config('services.gemini.queue_connection', 'sync'));
    }

    /**
     * Unauthenticated requests to the upload endpoint are rejected.
     */
    public function test_unauthenticated_user_cannot_upload_pdf(): void
    {
        Storage::fake('public');

        $fakePdf = UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf');

        $this->postJson(route('metrology.extractions.upload'), [
            'certificate_file' => $fakePdf,
        ])->assertUnauthorized();
    }

    /**
     * The upload endpoint validates that the uploaded file must be a PDF.
     */
    public function test_upload_rejects_non_pdf_files(): void
    {
        Storage::fake('public');

        $fakeImage = UploadedFile::fake()->create('cert.jpg', 100, 'image/jpeg');

        $this->actingAs($this->authorizedUser)
            ->postJson(route('metrology.extractions.upload'), [
                'certificate_file' => $fakeImage,
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['certificate_file']);
    }

    /**
     * The status endpoint returns the current extraction status.
     */
    public function test_status_endpoint_returns_extraction_status(): void
    {
        $extraction = CalibrationCertificateExtraction::factory()->create([
            'user_id' => $this->authorizedUser->id,
            'ai_key_index' => 2,
            'ai_model' => 'gemini-flash-latest',
            'status' => ExtractionStatus::Processing,
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->getJson(route('metrology.extractions.status', $extraction));

        $response->assertOk()
            ->assertJsonPath('id', $extraction->id)
            ->assertJsonPath('ai_key_index', 2)
            ->assertJsonPath('ai_model', 'gemini-flash-latest')
            ->assertJsonPath('status', ExtractionStatus::Processing->value);
    }

    /**
     * The preview endpoint returns structured extracted_data for completed extractions.
     */
    public function test_preview_endpoint_returns_extracted_data(): void
    {
        $extraction = CalibrationCertificateExtraction::factory()->completed()->create([
            'user_id' => $this->authorizedUser->id,
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->getJson(route('metrology.extractions.preview', $extraction));

        $response->assertOk()
            ->assertJsonStructure([
                'id', 'status', 'file_name', 'extracted_data' => [
                    'header' => ['reference', 'laboratory_name', 'calibration_date'],
                    'standards',
                ],
            ]);
    }

    /**
     * The mark-applied endpoint transitions is_applied to true and sets applied_at.
     */
    public function test_mark_applied_endpoint_updates_extraction_state(): void
    {
        $extraction = CalibrationCertificateExtraction::factory()->completed()->create([
            'user_id' => $this->authorizedUser->id,
            'is_applied' => false,
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->postJson(route('metrology.extractions.mark-applied', $extraction));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_applied', true);

        $this->assertDatabaseHas('calibration_certificate_extractions', [
            'id' => $extraction->id,
            'is_applied' => true,
        ]);

        $this->assertNotNull($extraction->fresh()->applied_at);
    }

    /**
     * The unapplied endpoint lists only completed, not-yet-applied extractions.
     */
    public function test_unapplied_endpoint_returns_only_unapplied_completed_extractions(): void
    {
        // Should appear
        $unapplied = CalibrationCertificateExtraction::factory()->completed()->create([
            'user_id' => $this->authorizedUser->id,
            'is_applied' => false,
        ]);

        // Should NOT appear
        CalibrationCertificateExtraction::factory()->applied()->create([
            'user_id' => $this->authorizedUser->id,
        ]);

        // Should NOT appear (still pending)
        CalibrationCertificateExtraction::factory()->create([
            'user_id' => $this->authorizedUser->id,
            'status' => ExtractionStatus::Pending,
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->getJson(route('metrology.extractions.unapplied'));

        $response->assertOk()
            ->assertJsonCount(1, 'extractions')
            ->assertJsonPath('extractions.0.id', $unapplied->id);
    }

    /**
     * The AI extraction service rotates to key #2 when key #1 returns HTTP 429.
     */
    public function test_ai_service_rotates_keys_on_rate_limit(): void
    {
        $fakeExtractedJson = json_encode([
            'header' => [
                'reference' => 'CERT-2026-TEST',
                'laboratory_name' => 'Test Lab',
                'calibration_date' => '2026-01-15',
                'expiry_date' => '2027-01-15',
                'validity_period_months' => 12,
                'environmental_conditions' => 'T: 23°C',
                'remarks' => null,
            ],
            'standards' => [],
        ]);

        // Key #1 returns 429 (rate limit), Key #2 succeeds
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['error' => ['message' => 'RATE_LIMIT_EXCEEDED']], 429)
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [['text' => $fakeExtractedJson]],
                        ],
                    ]],
                ], 200),
        ]);

        Storage::fake('public');

        $fakePdfPath = Storage::disk('public')->path('test-cert.pdf');
        Storage::disk('public')->put('test-cert.pdf', '%PDF-1.4 fake pdf content');

        $service = app(AiPdfExtractionService::class);
        $result = $service->extractFromPdf($fakePdfPath);

        $this->assertArrayHasKey('header', $result);
        $this->assertEquals('CERT-2026-TEST', $result['header']['reference']);
        $this->assertEquals('Test Lab', $result['header']['laboratory_name']);
    }

    /**
     * The ProcessCertificateExtractionJob stores structured JSON and marks status as completed.
     */
    public function test_extraction_job_stores_structured_json_and_completes(): void
    {
        $fakeExtractedJson = json_encode([
            'header' => [
                'reference' => 'CERT-JOB-2026',
                'laboratory_name' => 'CETIM Calibration',
                'calibration_date' => '2026-09-01',
                'expiry_date' => '2027-09-01',
                'validity_period_months' => 12,
                'environmental_conditions' => 'T: 22°C ±1°C, RH: 50%',
                'remarks' => 'Traceable to SI.',
            ],
            'standards' => [
                [
                    'grandeur_symbol' => 'mA',
                    'mode' => 'measurement',
                    'points' => [
                        ['nominal_value' => 4.0, 'reading_value' => 4.002, 'correction' => -0.002, 'uncertainty' => 0.003, 'status' => 'compliant'],
                        ['nominal_value' => 20.0, 'reading_value' => 20.005, 'correction' => -0.005, 'uncertainty' => 0.006, 'status' => 'compliant'],
                    ],
                ],
            ],
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => $fakeExtractedJson]]],
                ]],
            ], 200),
        ]);

        Storage::fake('public');
        Storage::disk('public')->put('certificates/extractions/test.pdf', '%PDF-1.4 fake pdf content');

        $extraction = CalibrationCertificateExtraction::factory()->create([
            'user_id' => $this->authorizedUser->id,
            'status' => ExtractionStatus::Pending,
            'file_path' => 'certificates/extractions/test.pdf',
        ]);

        (new ProcessCertificateExtractionJob($extraction))->handle(
            app(AiPdfExtractionService::class)
        );

        $extraction->refresh();

        $this->assertEquals(ExtractionStatus::Completed, $extraction->status);
        $this->assertEquals(1, $extraction->ai_key_index);
        $this->assertEquals((string) config('services.gemini.model', 'gemini-3.6-flash'), $extraction->ai_model);
        $this->assertNotNull($extraction->extracted_data);
        $this->assertEquals('CERT-JOB-2026', $extraction->extracted_data['header']['reference']);
        $this->assertCount(1, $extraction->extracted_data['standards']);
        $this->assertCount(2, $extraction->extracted_data['standards'][0]['points']);
    }

    /**
     * When all Gemini API keys fail, the job marks the extraction as failed with an error message.
     */
    public function test_extraction_job_marks_status_failed_when_api_exhausted(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                ['error' => ['message' => 'Service Unavailable']], 503
            ),
        ]);

        Storage::fake('public');
        Storage::disk('public')->put('certificates/extractions/fail.pdf', '%PDF-1.4 fake content');

        $extraction = CalibrationCertificateExtraction::factory()->create([
            'user_id' => $this->authorizedUser->id,
            'status' => ExtractionStatus::Pending,
            'file_path' => 'certificates/extractions/fail.pdf',
        ]);

        try {
            (new ProcessCertificateExtractionJob($extraction))->handle(
                app(AiPdfExtractionService::class)
            );
        } catch (\Throwable) {
            // The job re-throws after recording the failure — expected behaviour.
        }

        $extraction->refresh();

        $this->assertEquals(ExtractionStatus::Failed, $extraction->status);
        $this->assertNotNull($extraction->error_message);
    }

    /**
     * The extraction service normalizes device metadata for equipment compatibility verification.
     */
    public function test_extraction_normalizes_device_metadata_for_compatibility_verification(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'header' => [
                                            'reference' => 'CERT-2026-DEV',
                                            'laboratory_name' => 'GMTM Lab',
                                            'calibration_date' => '2026-03-24',
                                        ],
                                        'device' => [
                                            'designation' => 'Process Calibrator',
                                            'model' => 'ADT 223A',
                                            'serial_number' => 'SN-998877',
                                            'manufacturer' => 'Additel',
                                            'identification_code' => 'EQ-CAL-01',
                                        ],
                                        'standards' => [
                                            [
                                                'grandeur_symbol' => 'V',
                                                'mode' => 'measurement',
                                                'points' => [
                                                    ['nominal_value' => 10.0, 'reading_value' => 10.002, 'correction' => -0.002, 'uncertainty' => 0.001],
                                                ],
                                            ],
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Storage::fake('public');
        Storage::disk('public')->put('certificates/extractions/dev.pdf', '%PDF-1.4 fake content');

        $extraction = CalibrationCertificateExtraction::factory()->create([
            'user_id' => $this->authorizedUser->id,
            'status' => ExtractionStatus::Pending,
            'file_path' => 'certificates/extractions/dev.pdf',
        ]);

        (new ProcessCertificateExtractionJob($extraction))->handle(
            app(AiPdfExtractionService::class)
        );

        $extraction->refresh();

        $this->assertEquals(ExtractionStatus::Completed, $extraction->status);
        $this->assertNotNull($extraction->extracted_data);
        $this->assertArrayHasKey('device', $extraction->extracted_data);
        $this->assertEquals('ADT 223A', $extraction->extracted_data['device']['model']);
        $this->assertEquals('SN-998877', $extraction->extracted_data['device']['serial_number']);
        $this->assertEquals('Additel', $extraction->extracted_data['device']['manufacturer']);
        $this->assertEquals('EQ-CAL-01', $extraction->extracted_data['device']['identification_code']);
    }
}
