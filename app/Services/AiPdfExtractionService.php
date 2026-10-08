<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalibrationCertificateExtraction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * AI-powered PDF extraction service using Google Gemini Multimodal API with Multi-Key Rotation Pool.
 */
class AiPdfExtractionService
{
    /**
     * System prompt specifying ISO/IEC 17025 calibration certificate extraction schema.
     */
    protected const SYSTEM_PROMPT = <<<'PROMPT'
You are an expert Metrology & Calibration Certificate Analysis Assistant conforming to ISO/IEC 17025 standards.
Analyze the provided calibration certificate document and extract ALL metrological information into a strict JSON object.

CRITICAL INSTRUCTIONS FOR MULTI-PAGE COMPLETENESS:
1. Calibration certificates often have multiple pages (e.g., 2, 3, 4, 5, or more pages). You MUST inspect and process EVERY SINGLE PAGE from Page 1 to the final page.
2. In many calibration certificates, the latter pages (such as Page 3, Page 4, Page 5, Annexes) contain extensive measurement and generation/source tables (for example: Resistance in Ohms/Ω, Temperature in °C with RTD Pt-100 or Thermocouples, Pressure, Current, Voltage, etc.).
3. YOU MUST EXTRACT EVERY SINGLE ROW / CALIBRATION POINT FROM EVERY TABLE ACROSS ALL PAGES.
4. STRICT PROHIBITION AGAINST SAMPLING OR TRUNCATING:
   - NEVER omit intermediate points.
   - NEVER output only min/max or summary points.
   - If a table has 10, 16, 20 or more rows, you MUST output all rows/points completely.
5. Create separate entries in the "standards" array for each table or quantity, clearly distinguishing "measurement" (Mesure) from "source" (Génération).
   For example:
   - Voltage (V) [mode: "measurement"]
   - Voltage (V) [mode: "source"]
   - Current (mA) [mode: "measurement"]
   - Current (mA) [mode: "source"]
   - Resistance (Ω) [mode: "measurement"]
   - Resistance (Ω) [mode: "source"]
   - Temperature (°C) [mode: "measurement"]
   - Temperature (°C) [mode: "source"]

Required JSON Structure:
{
  "header": {
    "reference": string or null (Certificate number/identifier, e.g. "CERT-2026-0891"),
    "laboratory_name": string or null (Issuing calibration laboratory / organization),
    "calibration_date": string or null (Format: YYYY-MM-DD),
    "expiry_date": string or null (Format: YYYY-MM-DD if specified, else null),
    "validity_period_months": integer or null (e.g. 12, 24),
    "environmental_conditions": string or null (e.g. "Temperature: 23°C ± 1°C, Humidity: 45% ± 5%"),
    "remarks": string or null (General observations, traceability, method statement)
  },
  "device": {
    "designation": string or null (Instrument description/designation, e.g. "Calibrateur de process multifonctions"),
    "model": string or null (Model name or number, e.g. "ADT 223A"),
    "serial_number": string or null (Serial number / N° de série, e.g. "223A2134002"),
    "manufacturer": string or null (Manufacturer / Brand / Constructeur, e.g. "Additel"),
    "identification_code": string or null (Client internal code, asset tag, or identification, e.g. "E 255 / 2026 Acc")
  },
  "standards": [
    {
      "grandeur_symbol": string (Physical quantity unit symbol, e.g. "V", "mV", "mA", "A", "Ω", "kΩ", "MΩ", "°C", "bar", "psi", "Hz", "kHz"),
      "mode": string ("measurement" or "source", default "measurement"),
      "points": [
        {
          "nominal_value": float (The standard reference/nominal test point),
          "reading_value": float (The actual value displayed/measured by the unit under test),
          "correction": float (Calculated correction: nominal - reading, or as indicated in table),
          "uncertainty": float (Expanded uncertainty U, k=2),
          "status": string ("compliant", "non_compliant", or "unspecified")
        }
      ]
    }
  ]
}

Extraction Guidelines:
1. Normalize dates to ISO YYYY-MM-DD.
2. In measurement tables, carefully parse nominal values, displayed/indicated values, corrections, and expanded uncertainties (U).
3. If uncertainty is presented with ±, extract only the absolute numerical value.
4. If correction is not explicitly given, calculate: correction = nominal_value - reading_value.
5. Replace commas with dots in decimal numbers (e.g. 88,2214 -> 88.2214).
6. If no standards or points are present in the certificate, return an empty array for "standards".
7. Output pure JSON only without any markdown formatting or explanations.
PROMPT;

    /**
     * Extract structured calibration certificate data for an extraction record.
     *
     * @return array<string, mixed>
     */
    public function extractForRecord(CalibrationCertificateExtraction $extraction): array
    {
        $filePath = $extraction->file_path;
        $absolutePath = $this->resolveAbsolutePath($filePath);

        if (! File::exists($absolutePath)) {
            throw new RuntimeException("Calibration certificate file not found at [{$absolutePath}].");
        }

        return $this->extractFromPdf($absolutePath, $extraction);
    }

    /**
     * Extract structured data from a local PDF file.
     *
     * @return array<string, mixed>
     */
    public function extractFromPdf(string $absolutePath, ?CalibrationCertificateExtraction $extraction = null): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        @ini_set('max_execution_time', '300');

        if (! File::exists($absolutePath)) {
            throw new RuntimeException("PDF file does not exist at [{$absolutePath}].");
        }

        $fileContent = File::get($absolutePath);
        $base64Data = base64_encode($fileContent);

        return $this->executeWithRotation($base64Data, $extraction);
    }

    /**
     * Execute Gemini API call with Multi-Key Rotation Pool and model fallback.
     *
     * @return array<string, mixed>
     */
    protected function executeWithRotation(string $base64Pdf, ?CalibrationCertificateExtraction $extraction = null): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        @ini_set('max_execution_time', '300');

        $keys = (array) config('services.gemini.api_keys', []);
        $primaryModel = (string) config('services.gemini.model', 'gemini-3.6-flash');
        $fallbackModels = (array) config('services.gemini.fallback_models', ['gemini-flash-latest', 'gemini-3.5-flash']);
        $timeout = (int) config('services.gemini.timeout', 60);
        $maxDuration = max(1, (int) config('services.gemini.max_duration', 120));
        $deadline = microtime(true) + $maxDuration;

        if (empty($keys)) {
            throw new RuntimeException('No Google Gemini API keys configured in services.gemini.api_keys.');
        }

        $models = array_unique(array_merge([$primaryModel], $fallbackModels));
        $errors = [];

        foreach ($models as $model) {
            foreach ($keys as $index => $apiKey) {
                $remaining = (int) ceil($deadline - microtime(true));
                if ($remaining <= 0) {
                    break 2;
                }

                $extraction?->update([
                    'ai_key_index' => $index + 1,
                    'ai_model' => $model,
                ]);

                try {
                    $response = $this->callGeminiApi($apiKey, $model, $base64Pdf, min($timeout, $remaining));

                    if ($response->successful()) {
                        $parsed = $this->parseGeminiResponse($response);
                        if (! empty($parsed)) {
                            return $parsed;
                        }
                    }

                    $status = $response->status();
                    $errorBody = $response->json('error.message') ?? $response->body();

                    Log::warning("Gemini API call failed with Key #{$index} on model [{$model}]: HTTP {$status} - {$errorBody}");

                    // 429: Rate limit, 503: Model overloaded -> try next key or fallback model
                    if (in_array($status, [429, 500, 503], true)) {
                        $errors[] = "Key #{$index} [{$model}] HTTP {$status}: {$errorBody}";

                        // Brief pause before trying the next key to reduce pressure on overloaded model
                        sleep(2);

                        continue;
                    }

                    $errors[] = "Key #{$index} [{$model}] HTTP {$status}: {$errorBody}";
                } catch (ConnectionException $e) {
                    Log::warning("Gemini API timeout or connection failure with Key #{$index} on model [{$model}]: {$e->getMessage()}");
                    $errors[] = "Key #{$index} [{$model}] ConnectionException: {$e->getMessage()}";
                } catch (Throwable $e) {
                    Log::error("Gemini API unexpected error with Key #{$index} on model [{$model}]: {$e->getMessage()}");
                    $errors[] = "Key #{$index} [{$model}] Error: {$e->getMessage()}";
                }
            }
        }

        throw new RuntimeException('All Gemini API keys and models exhausted during extraction: '.implode('; ', $errors));
    }

    /**
     * Send HTTP request to Google Gemini Developer API.
     */
    protected function callGeminiApi(string $apiKey, string $model, string $base64Pdf, int $timeout): Response
    {
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => 'application/pdf',
                                'data' => $base64Pdf,
                            ],
                        ],
                        [
                            'text' => self::SYSTEM_PROMPT,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'temperature' => 0.1,
                'maxOutputTokens' => 16384,
                'thinkingConfig' => [
                    'thinkingBudget' => 0,
                ],
            ],
        ];

        return Http::connectTimeout(min(3, $timeout))->timeout($timeout)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($endpoint, $payload);
    }

    /**
     * Parse and normalize the Gemini response into validated ISO 17025 schema array.
     *
     * @return array<string, mixed>
     */
    protected function parseGeminiResponse(Response $response): array
    {
        $data = $response->json();
        $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (! is_string($rawText) || trim($rawText) === '') {
            return [];
        }

        $decoded = json_decode($rawText, true);

        if (! is_array($decoded)) {
            // Attempt cleaning markdown block if wrapped
            $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($rawText));
            $decoded = json_decode((string) $cleaned, true);
        }

        if (! is_array($decoded)) {
            Log::warning('Gemini returned unparseable JSON payload: '.substr($rawText, 0, 300));

            return [];
        }

        return $this->normalizeExtractedData($decoded);
    }

    /**
     * Normalize and validate schema keys.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeExtractedData(array $data): array
    {
        $header = (array) ($data['header'] ?? []);
        $standards = (array) ($data['standards'] ?? []);

        $normalizedStandards = [];
        foreach ($standards as $standard) {
            if (! is_array($standard)) {
                continue;
            }

            $rawSymbol = (string) ($standard['grandeur_symbol'] ?? '');
            $symbol = $this->normalizeUnitSymbol($rawSymbol);
            $mode = in_array(strtolower((string) ($standard['mode'] ?? '')), ['source', 'source_mode'], true) ? 'source' : 'measurement';

            $points = [];
            foreach ((array) ($standard['points'] ?? []) as $pt) {
                if (! is_array($pt)) {
                    continue;
                }

                $nominal = isset($pt['nominal_value']) ? (float) $pt['nominal_value'] : null;
                $reading = isset($pt['reading_value']) ? (float) $pt['reading_value'] : null;

                if ($nominal === null && $reading === null) {
                    continue;
                }

                $nominal = $nominal ?? $reading ?? 0.0;
                $reading = $reading ?? $nominal;

                $correction = isset($pt['correction']) ? (float) $pt['correction'] : (float) round($nominal - $reading, 6);
                $uncertainty = isset($pt['uncertainty']) ? abs((float) $pt['uncertainty']) : 0.0;
                $status = (string) ($pt['status'] ?? 'compliant');

                $points[] = [
                    'nominal_value' => $nominal,
                    'reading_value' => $reading,
                    'correction' => $correction,
                    'uncertainty' => $uncertainty,
                    'status' => in_array($status, ['compliant', 'non_compliant', 'unspecified'], true) ? $status : 'compliant',
                ];
            }

            if (! empty($points) || $symbol !== '') {
                $normalizedStandards[] = [
                    'grandeur_symbol' => $symbol,
                    'mode' => $mode,
                    'points' => $points,
                ];
            }
        }

        $device = (array) ($data['device'] ?? []);
        $normalizedDevice = [
            'designation' => isset($device['designation']) && trim((string) $device['designation']) !== '' ? trim((string) $device['designation']) : null,
            'model' => isset($device['model']) && trim((string) $device['model']) !== '' ? trim((string) $device['model']) : null,
            'serial_number' => isset($device['serial_number']) && trim((string) $device['serial_number']) !== '' ? trim((string) $device['serial_number']) : null,
            'manufacturer' => isset($device['manufacturer']) && trim((string) $device['manufacturer']) !== '' ? trim((string) $device['manufacturer']) : null,
            'identification_code' => isset($device['identification_code']) && trim((string) $device['identification_code']) !== '' ? trim((string) $device['identification_code']) : null,
        ];

        return [
            'header' => [
                'reference' => isset($header['reference']) ? trim((string) $header['reference']) : null,
                'laboratory_name' => isset($header['laboratory_name']) ? trim((string) $header['laboratory_name']) : null,
                'calibration_date' => isset($header['calibration_date']) ? trim((string) $header['calibration_date']) : null,
                'expiry_date' => isset($header['expiry_date']) ? trim((string) $header['expiry_date']) : null,
                'validity_period_months' => isset($header['validity_period_months']) ? (int) $header['validity_period_months'] : null,
                'environmental_conditions' => isset($header['environmental_conditions']) ? trim((string) $header['environmental_conditions']) : null,
                'remarks' => isset($header['remarks']) ? trim((string) $header['remarks']) : null,
            ],
            'device' => $normalizedDevice,
            'standards' => $normalizedStandards,
        ];
    }

    /**
     * Normalize physical quantity unit symbols.
     */
    protected function normalizeUnitSymbol(string $symbol): string
    {
        $map = [
            'degc' => '°C',
            'c' => '°C',
            'celsius' => '°C',
            'ohm' => 'Ω',
            'ohms' => 'Ω',
            'kohm' => 'kΩ',
            'mohm' => 'MΩ',
            'volts' => 'V',
            'volt' => 'V',
            'mv' => 'mV',
            'amperes' => 'A',
            'amps' => 'A',
            'ma' => 'mA',
            'hertz' => 'Hz',
            'khz' => 'kHz',
        ];

        $cleaned = trim($symbol);
        $lower = strtolower($cleaned);

        return $map[$lower] ?? $cleaned;
    }

    /**
     * Resolve filesystem absolute path from relative or absolute input.
     */
    protected function resolveAbsolutePath(string $path): string
    {
        // Detect absolute path: starts with / (Unix) or drive letter X:\ (Windows)
        $isAbsolute = str_starts_with($path, '/')
            || (strlen($path) >= 3 && ctype_alpha($path[0]) && $path[1] === ':');

        if ($isAbsolute) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->path($path);
        }

        return storage_path('app/public/'.$path);
    }
}
