<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExtractionStatus;
use App\Models\CalibrationCertificateExtraction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalibrationCertificateExtraction>
 */
class CalibrationCertificateExtractionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'equipment_id' => null,
            'file_path' => 'certificates/extractions/sample-'.fake()->uuid().'.pdf',
            'file_hash' => fake()->sha256(),
            'file_name' => 'calibration-cert-'.fake()->numerify('####').'.pdf',
            'file_size' => fake()->numberBetween(50000, 5000000),
            'status' => ExtractionStatus::Pending,
            'ai_model' => 'gemini-3.6-flash',
            'extracted_data' => null,
            'error_message' => null,
            'is_applied' => false,
            'applied_at' => null,
            'applied_certificate_id' => null,
        ];
    }

    /**
     * Mark the extraction as completed with sample ISO 17025 data.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExtractionStatus::Completed,
            'extracted_data' => [
                'header' => [
                    'reference' => 'CERT-'.fake()->numerify('####-####'),
                    'laboratory_name' => 'ONML - Office National de Métrologie Légale',
                    'calibration_date' => fake()->date('Y-m-d'),
                    'expiry_date' => fake()->dateTimeBetween('+6 months', '+2 years')->format('Y-m-d'),
                    'validity_period_months' => 12,
                    'environmental_conditions' => 'Temperature: 23°C ± 1°C, Humidity: 45% ± 5%',
                    'remarks' => 'Traceable to national standards.',
                ],
                'standards' => [
                    [
                        'grandeur_symbol' => 'V',
                        'mode' => 'measurement',
                        'points' => [
                            ['nominal_value' => 1.0, 'reading_value' => 1.001, 'correction' => -0.001, 'uncertainty' => 0.002, 'status' => 'compliant'],
                            ['nominal_value' => 5.0, 'reading_value' => 5.003, 'correction' => -0.003, 'uncertainty' => 0.005, 'status' => 'compliant'],
                            ['nominal_value' => 10.0, 'reading_value' => 10.005, 'correction' => -0.005, 'uncertainty' => 0.008, 'status' => 'compliant'],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Mark the extraction as failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExtractionStatus::Failed,
            'error_message' => 'All Gemini API keys and models exhausted during extraction.',
        ]);
    }

    /**
     * Mark the extraction as applied to a certificate.
     */
    public function applied(): static
    {
        return $this->completed()->state(fn (array $attributes) => [
            'is_applied' => true,
            'applied_at' => now(),
        ]);
    }
}
