# Implementation Plan: AI-Powered Calibration Certificate PDF Data Extraction Engine

Integrate an asynchronous AI-powered PDF extraction system for Metrology Calibration Certificates using Google Gemini Flash Multimodal API with Multi-Key Rotation Pool, dedicated staging table `calibration_certificate_extractions`, and on-demand auto-fill in `Register Certificate` (`create.blade.php`).

## User Review Required

> [!IMPORTANT]
> **API Key Verification:** All 3 provided keys were tested and verified active (HTTP 200) against Google Gemini Developer API:
> - Key 1: `[REDACTED_GCP_API_KEY_1]`
> - Key 2: `[REDACTED_GCP_API_KEY_2]`
> - Key 3: `[REDACTED_GCP_API_KEY_3]`
> 
> They will be configured in `.env` as `GEMINI_API_KEYS` with an automatic Round-Robin and Rate-Limit Failover rotation mechanism.

---

## Proposed Changes

Grouped by component layer:

### 1. Configuration & Environment

#### [MODIFY] [.env](file:///d:/HARD%20Project/erp.gmtm-dz.com/.env) & [.env.example](file:///d:/HARD%20Project/erp.gmtm-dz.com/.env.example)
- Append `GEMINI_API_KEYS` containing all 3 comma-separated keys.
- Set `GEMINI_MODEL=gemini-2.5-flash` (or `gemini-1.5-flash`).
- Set `GEMINI_TIMEOUT=60`.

#### [MODIFY] [config/services.php](file:///d:/HARD%20Project/erp.gmtm-dz.com/config/services.php)
- Add `gemini` configuration array with `api_keys` parsed into an array, default model, and timeout.

---

### 2. Database & Domain Models

#### [NEW] [database/migrations/2026_09_23_210000_create_calibration_certificate_extractions_table.php](file:///d:/HARD%20Project/erp.gmtm-dz.com/database/migrations/2026_09_23_210000_create_calibration_certificate_extractions_table.php)
- Schema for `calibration_certificate_extractions`:
  - `id` (ULID, primary key)
  - `user_id` (foreign key to `users`, cascade on delete)
  - `equipment_id` (nullable foreign key to `equipment`, null on delete)
  - `file_path`, `file_hash` (char 64, indexed sha256), `file_name`, `file_size`
  - `status` (string/enum: `pending`, `processing`, `completed`, `failed`, indexed)
  - `ai_model` (string)
  - `extracted_data` (json, nullable)
  - `error_message` (text, nullable)
  - `is_applied` (boolean, default false, indexed)
  - `applied_at` (timestamp, nullable)
  - `applied_certificate_id` (nullable foreign key to `calibration_certificates`, null on delete)
  - `timestamps`

#### [NEW] [app/Enums/ExtractionStatus.php](file:///d:/HARD%20Project/erp.gmtm-dz.com/app/Enums/ExtractionStatus.php)
- Enum with cases: `Pending = 'pending'`, `Processing = 'processing'`, `Completed = 'completed'`, `Failed = 'failed'`.
- Method `badgeVariant(): string` (`neutral`, `info`, `success`, `danger`).
- Method `label(): string` using English master keys `__('...')`.

#### [NEW] [app/Models/CalibrationCertificateExtraction.php](file:///d:/HARD%20Project/erp.gmtm-dz.com/app/Models/CalibrationCertificateExtraction.php)
- Eloquent Model with ULID key type.
- Casts: `extracted_data` => `array`, `status` => `ExtractionStatus::class`, `is_applied` => `boolean`, `applied_at` => `datetime`.
- Relationships: `user()`, `equipment()`, `appliedCertificate()`.
- Scopes: `scopePending()`, `scopeCompleted()`, `scopeUnapplied()`.

---

### 3. AI Service with Multi-Key Rotation Pool

#### [NEW] [app/Services/AiPdfExtractionService.php](file:///d:/HARD%20Project/erp.gmtm-dz.com/app/Services/AiPdfExtractionService.php)
- Core service that orchestrates:
  1. Compressing the uploaded PDF using `MediaOptimizationService::optimizePdf()` (ADR-009) to reduce token payload.
  2. Loading the PDF file and converting to base64.
  3. Key Rotation Pool: Attempts request with Key #1; if HTTP 429 (rate-limit) or transient network error occurs, seamlessly falls back to Key #2, then Key #3.
  4. Prompt & Schema: Sends system instructions to Google Gemini Flash specifying exact ISO 17025 fields:
     - Header: `reference`, `laboratory_name`, `calibration_date`, `expiry_date`, `validity_period_months`, `environmental_conditions`, `remarks`.
     - Standards: Array of `{ grandeur_symbol, mode, points: [ { nominal_value, reading_value, correction, uncertainty, status } ] }`.
  5. Decodes and validates the structured JSON output.

---

### 4. Background Queue Job

#### [NEW] [app/Jobs/ProcessCertificateExtractionJob.php](file:///d:/HARD%20Project/erp.gmtm-dz.com/app/Jobs/ProcessCertificateExtractionJob.php)
- `implements ShouldQueue` with queue connection `database`.
- Constructor accepts `CalibrationCertificateExtraction $extraction`.
- Execution:
  - Updates status to `processing`.
  - Calls `AiPdfExtractionService::extract()`.
  - Saves `extracted_data` and marks status `completed`.
  - Logs forensic audit trail in `activity_log`.
  - Dispatches `SystemActivityAlert` notification upon failure/success.
  - On exception: records `error_message` and marks status `failed`.

---

### 5. Web API & Controllers

#### [NEW] [app/Http/Requests/Metrology/StoreCertificateExtractionRequest.php](file:///d:/HARD%20Project/erp.gmtm-dz.com/app/Http/Requests/Metrology/StoreCertificateExtractionRequest.php)
- Validates:
  - `certificate_file`: `['required', 'file', 'mimes:pdf', 'max:20480']` (up to 20MB)
  - `equipment_id`: `['nullable', 'integer', 'exists:equipment,id']`

#### [NEW] [app/Http/Controllers/Metrology/CalibrationCertificateExtractionController.php](file:///d:/HARD%20Project/erp.gmtm-dz.com/app/Http/Controllers/Metrology/CalibrationCertificateExtractionController.php)
- Endpoints:
  - `POST /metrology/extractions/upload`: Stores PDF via `MediaOptimizationService`, creates record, dispatches `ProcessCertificateExtractionJob`, returns HTTP 202 JSON `{ id, status }`.
  - `GET /metrology/extractions/{extraction}/status`: Returns current status, error message (if any).
  - `GET /metrology/extractions/{extraction}/preview`: Returns JSON of extracted fields.
  - `POST /metrology/extractions/{extraction}/mark-applied`: Marks extraction as applied.
  - `GET /metrology/extractions/unapplied`: Returns recent unapplied extractions for the selected equipment.

#### [MODIFY] [routes/web.php](file:///d:/HARD%20Project/erp.gmtm-dz.com/routes/web.php)
- Register extraction routes under `metrology/extractions/` guarded by Spatie permission `create calibration certificates`.

---

### 6. Frontend Integration in `Register Certificate` & `Edit Certificate`

#### [MODIFY] [resources/views/metrology/certificates/create.blade.php](file:///d:/HARD%20Project/erp.gmtm-dz.com/resources/views/metrology/certificates/create.blade.php) & [resources/views/metrology/certificates/edit.blade.php](file:///d:/HARD%20Project/erp.gmtm-dz.com/resources/views/metrology/certificates/edit.blade.php)
- Integrate an **AI Smart Dropzone & On-Demand Import Hub** at the top of both creation and edit forms:
  - Animated Drag-and-Drop zone with brand colors and standard icons.
  - Asynchronous file upload with step progress bar (`Uploading...` -> `Compressing PDF...` -> `AI Extraction in progress...` -> `Extraction Ready!`).
  - Button to select previously uploaded/unapplied extractions for the selected equipment.
  - "Review & Apply" preview modal:
    - Displays extracted header fields.
    - Displays detected standards and points counts.
  - Single-Click "Apply to Form" action:
    - Injects header fields into form inputs (`reference`, `laboratory_name`, `calibration_date`, `expiry_date`, `environmental_conditions`, `remarks`).
    - Matches each extracted standard with the equipment's `specifications` (by unit symbol and mode: `measurement` vs `source`).
    - Automatically injects the extracted measurement points directly into Alpine's `pointsBySpec[specId]`.
    - Automatically updates tab counters (`N pts`), tolerance badges, and $[C - U, C + U]$ bounds in real time!
    - Calls `mark-applied` on the extraction.

---

### 7. Localization & Quality Control

#### [MODIFY] [lang/en.json](file:///d:/HARD%20Project/erp.gmtm-dz.com/lang/en.json), [lang/ar.json](file:///d:/HARD%20Project/erp.gmtm-dz.com/lang/ar.json), [lang/fr.json](file:///d:/HARD%20Project/erp.gmtm-dz.com/lang/fr.json)
- Add all required English master keys and their Arabic & French translations:
  - `"AI Certificate Auto-Fill"`
  - `"Extract data from PDF calibration certificate using AI"`
  - `"Drag and drop calibration certificate PDF or click to browse"`
  - `"Extracting Certificate Data..."`
  - `"Extraction Completed"`
  - `"Apply Extracted Data to Form"`
  - `"Data applied to certificate form successfully."`
  - etc.

---

## Verification Plan

### Automated Tests
1. **Feature Test:** `tests/Feature/Metrology/CalibrationCertificateExtractionTest.php`
   - `test_authorized_user_can_upload_pdf_and_dispatch_job`: Uploads a sample PDF, asserts record created in `calibration_certificate_extractions` with status `pending`, asserts job dispatched.
   - `test_ai_service_rotates_keys_on_rate_limit`: Mocks Gemini API returning 429 on Key #1 and 200 on Key #2, asserts request succeeds via rotation.
   - `test_extraction_job_stores_structured_json_and_completes`: Executes job with mock Gemini response, asserts `status = completed` and `extracted_data` matches ISO 17025 schema.
   - `test_mark_applied_endpoint_updates_extraction_state`: Verifies `is_applied` and `applied_at` transition.

### Manual Verification
1. Open `http://erp.gmtm-dz.com.test/en/metrology/calibration-certificates/create`.
2. Select an equipment with specifications (e.g. Fluke Calibrator or Temperature gauge).
3. Drag & drop a real or sample calibration certificate PDF into the AI Dropzone.
4. Watch the real-time progress indicator move through states until completed.
5. Click "Apply to Certificate Form" in the preview modal.
6. Verify that:
   - Header inputs are filled with the extracted data.
   - The multi-standard tabs contain the extracted points.
   - Calculated limits $[C - U, C + U]$ and tolerance indicators display correctly.
7. Submit the form and verify certificate creation in the database.
