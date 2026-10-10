<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\Financial\ExpenseController;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\Metrology\CalibrationCertificateController;
use App\Http\Controllers\Metrology\CalibrationCertificateExtractionController;
use App\Http\Controllers\Metrology\CalibrationInstrumentsController;
use App\Http\Controllers\Metrology\ChromatographVerificationController;
use App\Http\Controllers\Metrology\EquipmentController;
use App\Http\Controllers\Metrology\GrandeurUnitController;
use App\Http\Controllers\Metrology\InstrumentController;
use App\Http\Controllers\Metrology\ProverVerificationController;
use App\Http\Controllers\Metrology\ReportController as MetrologyReportController;
use App\Http\Controllers\MetrologyController;
use App\Http\Controllers\Operations\AttachmentController;
use App\Http\Controllers\Operations\MissionController;
use App\Http\Controllers\Operations\MissionOrderController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StorageFileController;
use App\Http\Controllers\SystemTableController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

$routes = function (): void {
    Route::get('lang/{locale}', function (string $locale) {
        if (in_array($locale, ['ar', 'en', 'fr'])) {
            session()->put('locale', $locale);

            return redirect(LaravelLocalization::getLocalizedURL($locale, null, [], true));
        }

        return redirect()->back();
    })->name('lang.switch');

    Route::get('/', function () {
        try {
            if (! Schema::hasTable('users') || User::count() === 0) {
                return redirect()->route('system-tables.setup');
            }
        } catch (Throwable) {
            // Graceful fallback if database connection is not yet configured
        }

        return view('welcome');
    })->name('welcome');

    Route::prefix('system-tables')->name('system-tables.')->group(function () {
        Route::get('/setup', [SystemTableController::class, 'setup'])->name('setup');
        Route::post('/setup', [SystemTableController::class, 'storeSetup'])->name('setup.store');
    });

    Route::get('/dashboard', function () {
        return view('workspace');
    })->middleware(['auth', 'verified'])->name('dashboard');

    // 🔬 Metrology & Equipments
    Route::middleware(['auth', 'verified'])->prefix('metrology')->name('metrology.')->group(function () {
        Route::get('/', [MetrologyController::class, 'index'])->name('index');
        Route::get('/instruments', [InstrumentController::class, 'index'])->name('instruments');
        Route::get('/instruments/create', [InstrumentController::class, 'create'])->name('instruments.create');
        Route::get('/instruments/create/{type}', [InstrumentController::class, 'createType'])->name('instruments.create.type');
        Route::post('/instruments', [InstrumentController::class, 'store'])->name('instruments.store');
        Route::get('/instruments/{instrument}', [InstrumentController::class, 'show'])->name('instruments.show');
        Route::get('/instruments/{instrument}/edit', [InstrumentController::class, 'edit'])->name('instruments.edit');
        Route::put('/instruments/{instrument}', [InstrumentController::class, 'update'])->name('instruments.update');
        Route::delete('/instruments/{instrument}', [InstrumentController::class, 'destroy'])->name('instruments.destroy');
        Route::get('/equipment', [EquipmentController::class, 'index'])->name('equipment');
        Route::post('/equipment', [EquipmentController::class, 'store'])->name('equipment.store');
        Route::get('/equipment/{equipment}', [EquipmentController::class, 'show'])->name('equipment.show');
        Route::put('/equipment/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update');
        Route::delete('/equipment/{equipment}', [EquipmentController::class, 'destroy'])->name('equipment.destroy');
        Route::get('/calibrator-movements', [MetrologyController::class, 'calibratorMovements'])->name('calibrator-movements');

        // Calibration Certificates Resource Suite
        Route::get('/calibration-certificates', [CalibrationCertificateController::class, 'index'])->name('calibration-certificates');
        Route::get('/calibration-certificates/create', [CalibrationCertificateController::class, 'create'])->name('calibration-certificates.create');
        Route::post('/calibration-certificates', [CalibrationCertificateController::class, 'store'])->name('calibration-certificates.store');
        Route::get('/calibration-certificates/{certificate}', [CalibrationCertificateController::class, 'show'])->name('calibration-certificates.show');
        Route::get('/calibration-certificates/{certificate}/edit', [CalibrationCertificateController::class, 'edit'])->name('calibration-certificates.edit');
        Route::put('/calibration-certificates/{certificate}', [CalibrationCertificateController::class, 'update'])->name('calibration-certificates.update');
        Route::delete('/calibration-certificates/{certificate}', [CalibrationCertificateController::class, 'destroy'])->name('calibration-certificates.destroy');
        Route::post('/calibration-certificates/{certificate}/approve', [CalibrationCertificateController::class, 'approve'])->name('calibration-certificates.approve');
        Route::post('/calibration-certificates/{certificate}/unlock', [CalibrationCertificateController::class, 'unlock'])->name('calibration-certificates.unlock');
        Route::get('/calibration-certificates/{certificate}/download', [CalibrationCertificateController::class, 'download'])->name('calibration-certificates.download');
        Route::post('/calibration-certificates/{certificate}/interpolation-grid', [CalibrationCertificateController::class, 'updateInterpolationGrid'])->name('calibration-certificates.interpolation-grid');

        // AI Certificate PDF Extractions
        Route::prefix('extractions')->name('extractions.')->group(function () {
            Route::post('/upload', [CalibrationCertificateExtractionController::class, 'upload'])->name('upload');
            Route::get('/unapplied', [CalibrationCertificateExtractionController::class, 'unapplied'])->name('unapplied');
            Route::get('/{extraction}/status', [CalibrationCertificateExtractionController::class, 'status'])->name('status');
            Route::get('/{extraction}/preview', [CalibrationCertificateExtractionController::class, 'preview'])->name('preview');
            Route::post('/{extraction}/mark-applied', [CalibrationCertificateExtractionController::class, 'markApplied'])->name('mark-applied');
        });

        Route::get('/units', [GrandeurUnitController::class, 'index'])->name('units');
        Route::post('/units', [GrandeurUnitController::class, 'store'])->name('units.store');
        Route::put('/units/{grandeur}', [GrandeurUnitController::class, 'update'])->name('units.update');
        Route::delete('/units/{grandeur}', [GrandeurUnitController::class, 'destroy'])->name('units.destroy');

        // Metrology Reports Suite
        Route::get('/reports', [MetrologyReportController::class, 'index'])->name('reports');
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/index', [MetrologyReportController::class, 'index'])->name('index');
            Route::get('/create', [MetrologyReportController::class, 'create'])->name('create');
            Route::post('/', [MetrologyReportController::class, 'store'])->name('store');
            Route::get('/mission-details/{mission}', [MetrologyReportController::class, 'getMissionDetails'])->name('mission-details');
            Route::get('/all-instruments-json', [MetrologyReportController::class, 'getAllInstrumentsJson'])->name('all-instruments-json');

            // Specialized Domain Pillar Hubs (MUST BE BEFORE wildcard /{report})
            Route::get('/report-instruments', [CalibrationInstrumentsController::class, 'index'])->name('report-instruments.index');

            // Prover & Standard Gauges Domain Routes (MUST BE BEFORE wildcard /{report})
            Route::prefix('report-prover')->name('report-prover.')->group(function () {
                Route::get('/', [ProverVerificationController::class, 'index'])->name('index');
                Route::get('/create', [ProverVerificationController::class, 'create'])->name('create');
                Route::post('/', [ProverVerificationController::class, 'store'])->name('store');
                Route::post('/calculate-preview', [ProverVerificationController::class, 'calculatePreview'])->name('preview');
                Route::get('/{proverVerification}', [ProverVerificationController::class, 'show'])->name('show')->whereNumber('proverVerification');
                Route::get('/{proverVerification}/edit', [ProverVerificationController::class, 'edit'])->name('edit')->whereNumber('proverVerification');
                Route::put('/{proverVerification}', [ProverVerificationController::class, 'update'])->name('update')->whereNumber('proverVerification');
                Route::delete('/{proverVerification}', [ProverVerificationController::class, 'destroy'])->name('destroy')->whereNumber('proverVerification');
                Route::get('/{proverVerification}/pdf', [ProverVerificationController::class, 'generatePdf'])->name('pdf')->whereNumber('proverVerification');
                Route::get('/{proverVerification}/pdf-not-emt', [ProverVerificationController::class, 'generatePdfNotEmt'])->name('pdf-not-emt')->whereNumber('proverVerification');
            });

            // Chromatograph (CPG) Domain Routes
            Route::prefix('report-chromatograph')->name('report-chromatograph.')->group(function () {
                Route::get('/', [ChromatographVerificationController::class, 'index'])->name('index');
                Route::get('/create', [ChromatographVerificationController::class, 'create'])->name('create');
                Route::post('/', [ChromatographVerificationController::class, 'store'])->name('store');
                Route::get('/{chromatographVerification}', [ChromatographVerificationController::class, 'show'])->name('show')->whereNumber('chromatographVerification');
                Route::get('/{chromatographVerification}/saisie', [ChromatographVerificationController::class, 'saisie'])->name('saisie')->whereNumber('chromatographVerification');
                Route::post('/{chromatographVerification}/saisie', [ChromatographVerificationController::class, 'store'])->name('saisie.store')->whereNumber('chromatographVerification');
                Route::delete('/{chromatographVerification}', [ChromatographVerificationController::class, 'destroy'])->name('destroy')->whereNumber('chromatographVerification');
                Route::get('/{chromatographVerification}/pdf', [ChromatographVerificationController::class, 'generatePdf'])->name('pdf')->whereNumber('chromatographVerification');
                Route::get('/{chromatographVerification}/pdf-not-emt', [ChromatographVerificationController::class, 'generatePdfNotEmt'])->name('pdf-not-emt')->whereNumber('chromatographVerification');
                Route::get('/{chromatographVerification}/excel', [ChromatographVerificationController::class, 'exportExcel'])->name('excel')->whereNumber('chromatographVerification');
                Route::get('/instruments/{instrument}/excel-template', [ChromatographVerificationController::class, 'exportTemplate'])->name('template')->whereNumber('instrument');
            });

            Route::get('/{report}', [MetrologyReportController::class, 'show'])->name('show')->whereNumber('report');
            Route::get('/{report}/edit', [MetrologyReportController::class, 'edit'])->name('edit')->whereNumber('report');
            Route::put('/{report}', [MetrologyReportController::class, 'update'])->name('update')->whereNumber('report');
            Route::delete('/{report}', [MetrologyReportController::class, 'destroy'])->name('destroy')->whereNumber('report');
            Route::get('/{report}/pdf', [MetrologyReportController::class, 'generatePdf'])->name('pdf')->whereNumber('report');
            Route::get('/{report}/pdf-summary', [MetrologyReportController::class, 'generatePdfSummary'])->name('pdf.summary')->whereNumber('report');

            // Verification Saisie & Curve Routes
            Route::get('/{report}/instruments/{instrument}/saisie', [CalibrationInstrumentsController::class, 'createSaisie'])->name('saisie');
            Route::post('/{report}/instruments/{instrument}/saisie-transmitter', [CalibrationInstrumentsController::class, 'storeTransmitterSaisie'])->name('saisie.transmitter');
            Route::post('/{report}/instruments/{instrument}/saisie-probe', [CalibrationInstrumentsController::class, 'storeProbeSaisie'])->name('saisie.probe');
            Route::post('/{report}/instruments/{instrument}/saisie-flow-computer', [CalibrationInstrumentsController::class, 'storeFlowComputerSaisie'])->name('saisie.flow_computer');
            Route::post('/{report}/instruments/{instrument}/saisie-chromatograph', [ChromatographVerificationController::class, 'store'])->name('saisie.chromatograph');
            Route::get('/{report}/instruments/{instrument}/curve', [CalibrationInstrumentsController::class, 'showCurve'])->name('curve');
        });
    });

    // Compatibility Alias for legacy admin.reports.* routes
    Route::middleware(['auth', 'verified'])->prefix('admin/reports')->name('admin.reports.')->group(function () {
        Route::get('/', [MetrologyReportController::class, 'index'])->name('index');
        Route::get('/report-instruments', [CalibrationInstrumentsController::class, 'index'])->name('report-instruments.index');
        Route::prefix('report-prover')->name('report-prover.')->group(function () {
            Route::get('/', [ProverVerificationController::class, 'index'])->name('index');
            Route::get('/create', [ProverVerificationController::class, 'create'])->name('create');
            Route::post('/', [ProverVerificationController::class, 'store'])->name('store');
            Route::post('/calculate-preview', [ProverVerificationController::class, 'calculatePreview'])->name('preview');
            Route::get('/{proverVerification}', [ProverVerificationController::class, 'show'])->name('show')->whereNumber('proverVerification');
            Route::get('/{proverVerification}/edit', [ProverVerificationController::class, 'edit'])->name('edit')->whereNumber('proverVerification');
            Route::put('/{proverVerification}', [ProverVerificationController::class, 'update'])->name('update')->whereNumber('proverVerification');
            Route::delete('/{proverVerification}', [ProverVerificationController::class, 'destroy'])->name('destroy')->whereNumber('proverVerification');
            Route::get('/{proverVerification}/pdf', [ProverVerificationController::class, 'generatePdf'])->name('pdf')->whereNumber('proverVerification');
            Route::get('/{proverVerification}/pdf-not-emt', [ProverVerificationController::class, 'generatePdfNotEmt'])->name('pdf-not-emt')->whereNumber('proverVerification');
        });
        Route::prefix('report-chromatograph')->name('report-chromatograph.')->group(function () {
            Route::get('/', [ChromatographVerificationController::class, 'index'])->name('index');
            Route::get('/create', [ChromatographVerificationController::class, 'create'])->name('create');
            Route::post('/', [ChromatographVerificationController::class, 'store'])->name('store');
            Route::get('/{chromatographVerification}', [ChromatographVerificationController::class, 'show'])->name('show')->whereNumber('chromatographVerification');
            Route::get('/{chromatographVerification}/saisie', [ChromatographVerificationController::class, 'saisie'])->name('saisie')->whereNumber('chromatographVerification');
            Route::delete('/{chromatographVerification}', [ChromatographVerificationController::class, 'destroy'])->name('destroy')->whereNumber('chromatographVerification');
            Route::get('/{chromatographVerification}/pdf', [ChromatographVerificationController::class, 'generatePdf'])->name('pdf')->whereNumber('chromatographVerification');
            Route::get('/{chromatographVerification}/pdf-not-emt', [ChromatographVerificationController::class, 'generatePdfNotEmt'])->name('pdf-not-emt')->whereNumber('chromatographVerification');
            Route::get('/{chromatographVerification}/excel', [ChromatographVerificationController::class, 'exportExcel'])->name('excel')->whereNumber('chromatographVerification');
            Route::get('/instruments/{instrument}/excel-template', [ChromatographVerificationController::class, 'exportTemplate'])->name('template')->whereNumber('instrument');
        });
        Route::get('/create', [MetrologyReportController::class, 'create'])->name('create');
        Route::post('/', [MetrologyReportController::class, 'store'])->name('store');
        Route::get('/mission-details/{mission}', [MetrologyReportController::class, 'getMissionDetails'])->name('mission-details');
        Route::get('/all-instruments-json', [MetrologyReportController::class, 'getAllInstrumentsJson'])->name('all-instruments-json');
        Route::get('/{report}', [MetrologyReportController::class, 'show'])->name('show');
        Route::get('/{report}/edit', [MetrologyReportController::class, 'edit'])->name('edit');
        Route::put('/{report}', [MetrologyReportController::class, 'update'])->name('update');
        Route::delete('/{report}', [MetrologyReportController::class, 'destroy'])->name('destroy');
        Route::get('/{report}/pdf', [MetrologyReportController::class, 'generatePdf'])->name('pdf');
        Route::get('/{report}/pdf-not-emt', [MetrologyReportController::class, 'generatePdfSummary'])->name('pdf_not_emt');
        Route::get('/{report}/instruments/{instrument}/saisie', [CalibrationInstrumentsController::class, 'createSaisie'])->name('saisie');
        Route::post('/{report}/instruments/{instrument}/saisie-transmitter', [CalibrationInstrumentsController::class, 'storeTransmitterSaisie'])->name('saisie.transmitter');
        Route::post('/{report}/instruments/{instrument}/saisie-probe', [CalibrationInstrumentsController::class, 'storeProbeSaisie'])->name('saisie.probe');
        Route::post('/{report}/instruments/{instrument}/saisie-flow-computer', [CalibrationInstrumentsController::class, 'storeFlowComputerSaisie'])->name('saisie.flow_computer');
        Route::post('/{report}/instruments/{instrument}/saisie/chromatograph', [ChromatographVerificationController::class, 'store'])->name('saisie.chromatograph');
        Route::get('/{report}/instruments/{instrument}/curve', [CalibrationInstrumentsController::class, 'showCurve'])->name('curve');
    });

    // Compatibility Alias for legacy admin.chromatograph_verifications.* routes
    Route::middleware(['auth', 'verified'])->prefix('admin/chromatograph-verifications')->name('admin.chromatograph_verifications.')->group(function () {
        Route::get('/', [ChromatographVerificationController::class, 'index'])->name('index');
        Route::get('/create', [ChromatographVerificationController::class, 'create'])->name('create');
        Route::post('/', [ChromatographVerificationController::class, 'store'])->name('store');
        Route::get('/{chromatographVerification}', [ChromatographVerificationController::class, 'show'])->name('show')->whereNumber('chromatographVerification');
        Route::get('/{chromatographVerification}/saisie', [ChromatographVerificationController::class, 'saisie'])->name('saisie')->whereNumber('chromatographVerification');
        Route::post('/{chromatographVerification}/saisie', [ChromatographVerificationController::class, 'store'])->name('save_saisie')->whereNumber('chromatographVerification');
        Route::delete('/{chromatographVerification}', [ChromatographVerificationController::class, 'destroy'])->name('destroy')->whereNumber('chromatographVerification');
        Route::get('/{chromatographVerification}/pdf', [ChromatographVerificationController::class, 'generatePdf'])->name('pdf')->whereNumber('chromatographVerification');
        Route::get('/{chromatographVerification}/pdf-not-emt', [ChromatographVerificationController::class, 'generatePdfNotEmt'])->name('pdf_not_emt')->whereNumber('chromatographVerification');
        Route::get('/{chromatographVerification}/excel', [ChromatographVerificationController::class, 'exportExcel'])->name('excel')->whereNumber('chromatographVerification');
        Route::get('/instruments/{instrument}/excel-template', [ChromatographVerificationController::class, 'exportTemplate'])->name('excel_template')->whereNumber('instrument');
    });

    // Compatibility Alias for legacy admin.prover_verifications.* routes
    Route::middleware(['auth', 'verified'])->prefix('admin/prover-verifications')->name('admin.prover_verifications.')->group(function () {
        Route::get('/', [ProverVerificationController::class, 'index'])->name('index');
        Route::get('/create', [ProverVerificationController::class, 'create'])->name('create');
        Route::get('/session/create', [ProverVerificationController::class, 'create']);
        Route::post('/', [ProverVerificationController::class, 'store'])->name('store');
        Route::post('/calculate-preview', [ProverVerificationController::class, 'calculatePreview'])->name('preview');
        Route::get('/{proverVerification}', [ProverVerificationController::class, 'show'])->name('show')->whereNumber('proverVerification');
        Route::delete('/{proverVerification}', [ProverVerificationController::class, 'destroy'])->name('destroy')->whereNumber('proverVerification');
        Route::get('/{proverVerification}/pdf', [ProverVerificationController::class, 'generatePdf'])->name('pdf')->whereNumber('proverVerification');
        Route::get('/{proverVerification}/pdf-not-emt', [ProverVerificationController::class, 'generatePdfNotEmt'])->name('pdf_not_emt')->whereNumber('proverVerification');
    });
    Route::middleware(['auth', 'verified'])->get('/dashboard/metrology', [MetrologyController::class, 'index'])->name('dashboard_metrology');

    // 💼 Operations & Projects
    Route::middleware(['auth', 'verified'])->prefix('operations')->name('operations.')->group(function () {
        Route::get('/', [OperationsController::class, 'index'])->name('index');

        // Missions Resource & Lifecycle Suite
        Route::get('/missions', [MissionController::class, 'index'])->name('missions');
        Route::prefix('missions')->name('missions.')->group(function () {
            Route::get('/create', [MissionController::class, 'create'])->name('create');
            Route::post('/', [MissionController::class, 'store'])->name('store');
            Route::get('/{mission}', [MissionController::class, 'show'])->name('show');
            Route::get('/{mission}/edit', [MissionController::class, 'edit'])->name('edit');
            Route::put('/{mission}', [MissionController::class, 'update'])->name('update');
            Route::delete('/{mission}', [MissionController::class, 'destroy'])->name('destroy');
            Route::post('/{mission}/activate', [MissionController::class, 'activate'])->name('activate');
            Route::post('/{mission}/complete', [MissionController::class, 'complete'])->name('complete');
            Route::post('/{mission}/revert', [MissionController::class, 'revert'])->name('revert');
            Route::get('/{mission}/statistics', [MissionController::class, 'statistics'])->name('statistics');
            Route::get('/{mission}/equipments', [MissionController::class, 'equipments'])->name('equipments');

            // Mission Orders & Travel Documents
            Route::get('/{mission}/orders/{order}/edit', [MissionOrderController::class, 'edit'])->name('orders.edit');
            Route::put('/{mission}/orders/{order}', [MissionOrderController::class, 'update'])->name('orders.update');
            Route::get('/{mission}/orders/{order}/print', [MissionOrderController::class, 'print'])->name('orders.print');
        });
        // 📄 Contracts (CRUD + Statistics)
        Route::get('/contracts', [ContractController::class, 'index'])
            ->middleware('permission:view contracts')->name('contracts');
        Route::redirect('/contracts/index', '/operations/contracts')->name('contracts.index');
        Route::prefix('contracts')->name('contracts.')->group(function (): void {
            Route::get('/create', [ContractController::class, 'create'])
                ->middleware('permission:create contracts')->name('create');
            Route::post('/', [ContractController::class, 'store'])
                ->middleware('permission:create contracts')->name('store');
            Route::get('/{contract}', [ContractController::class, 'show'])
                ->middleware('permission:view contracts')->name('show');
            Route::get('/{contract}/edit', [ContractController::class, 'edit'])
                ->middleware('permission:edit contracts')->name('edit');
            Route::put('/{contract}', [ContractController::class, 'update'])
                ->middleware('permission:edit contracts')->name('update');
            Route::delete('/{contract}', [ContractController::class, 'destroy'])
                ->middleware('permission:delete contracts')->name('destroy');
            Route::get('/{contract}/statistics', [ContractController::class, 'statistics'])
                ->middleware('permission:view contracts')->name('statistics');
        });

        // 📎 Attachments (CRUD + Print + Status Workflow)
        Route::get('/attachments', [AttachmentController::class, 'index'])
            ->middleware('permission:view attachments')->name('attachments');
        Route::redirect('/attachments/index', '/operations/attachments')->name('attachments.index');
        Route::prefix('attachments')->name('attachments.')->group(function (): void {
            Route::get('/create', [AttachmentController::class, 'create'])
                ->middleware('permission:create attachments')->name('create');
            Route::post('/', [AttachmentController::class, 'store'])
                ->middleware('permission:create attachments')->name('store');
            Route::get('/{attachment}', [AttachmentController::class, 'show'])
                ->middleware('permission:view attachments')->name('show');
            Route::get('/{attachment}/edit', [AttachmentController::class, 'edit'])
                ->middleware('permission:edit attachments')->name('edit');
            Route::put('/{attachment}', [AttachmentController::class, 'update'])
                ->middleware('permission:edit attachments')->name('update');
            Route::delete('/{attachment}', [AttachmentController::class, 'destroy'])
                ->middleware('permission:delete attachments')->name('destroy');
            Route::patch('/{attachment}/status', [AttachmentController::class, 'updateStatus'])
                ->middleware('permission:edit attachments')->name('update_status');
            Route::patch('/{attachment}/revert', [AttachmentController::class, 'revertToDraft'])
                ->middleware('permission:edit attachments')->name('revert');
            Route::get('/{attachment}/print', [AttachmentController::class, 'printSingle'])
                ->middleware('permission:view attachments')->name('print_single');
            Route::get('/{attachment}/print-bl', [AttachmentController::class, 'printSingleBL'])
                ->middleware('permission:view attachments')->name('print_bl');
        });
        Route::get('/warranties', [OperationsController::class, 'warranties'])->name('warranties');
        Route::post('/warranties', [OperationsController::class, 'storeWarranty'])->name('warranties.store');
        Route::put('/warranties/{warranty}', [OperationsController::class, 'updateWarranty'])->name('warranties.update');
        Route::delete('/warranties/{warranty}', [OperationsController::class, 'destroyWarranty'])->name('warranties.destroy');
        Route::get('/article-types', [OperationsController::class, 'articleTypes'])->name('article-types');
        Route::post('/article-types', [OperationsController::class, 'storeItemType'])->name('article-types.store');
        Route::put('/article-types/{item_type}', [OperationsController::class, 'updateItemType'])->name('article-types.update');
        Route::delete('/article-types/{item_type}', [OperationsController::class, 'destroyItemType'])->name('article-types.destroy');
    });
    Route::middleware(['auth', 'verified'])->get('/dashboard/operations', [OperationsController::class, 'index'])->name('dashboard_operations');

    // 📈 Internal and Analytical Management
    Route::middleware(['auth', 'verified'])->prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/expenses', [AnalyticsController::class, 'expenses'])->name('expenses');
        Route::get('/forecasts', [AnalyticsController::class, 'forecasts'])->name('forecasts');
        Route::post('/forecasts', [AnalyticsController::class, 'storeForecast'])->name('forecasts.store');
        Route::put('/forecasts/{forecast}', [AnalyticsController::class, 'updateForecast'])->name('forecasts.update');
        Route::delete('/forecasts/{forecast}', [AnalyticsController::class, 'destroyForecast'])->name('forecasts.destroy');
        Route::get('/statistics', [AnalyticsController::class, 'statistics'])->name('statistics');
    });
    Route::middleware(['auth', 'verified'])->get('/dashboard/analytics', [AnalyticsController::class, 'index'])->name('dashboard_analytics');

    // 💰 Financial Management
    Route::middleware(['auth', 'verified'])->prefix('financial')->name('financial.')->group(function () {
        Route::get('/', [FinancialController::class, 'index'])->name('index');
        Route::get('/warranties', [FinancialController::class, 'warranties'])->name('warranties');
        Route::post('/warranties', [FinancialController::class, 'storeWarranty'])->name('warranties.store');
        Route::put('/warranties/{warranty}', [FinancialController::class, 'updateWarranty'])->name('warranties.update');
        Route::delete('/warranties/{warranty}', [FinancialController::class, 'destroyWarranty'])->name('warranties.destroy');
        Route::get('/expenses', [ExpenseController::class, 'index'])->middleware('permission:view expenses')->name('expenses');
        Route::redirect('/expenses/index', '/financial/expenses')->name('expenses.index');
        Route::prefix('expenses')->name('expenses.')->group(function (): void {
            Route::get('/create', [ExpenseController::class, 'create'])->middleware('permission:create expenses')->name('create');
            Route::post('/', [ExpenseController::class, 'store'])->middleware('permission:create expenses')->name('store');
            Route::get('/{expense}', [ExpenseController::class, 'show'])->middleware('permission:view expenses')->name('show');
            Route::get('/{expense}/edit', [ExpenseController::class, 'edit'])->middleware('permission:edit expenses')->name('edit');
            Route::put('/{expense}', [ExpenseController::class, 'update'])->middleware('permission:edit expenses')->name('update');
            Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->middleware('permission:delete expenses')->name('destroy');
        });
    });
    Route::middleware(['auth', 'verified'])->get('/dashboard/financial', [FinancialController::class, 'index'])->name('dashboard_financial');

    // 🗂️ Master Data / Reference Data
    Route::middleware(['auth', 'verified'])->prefix('master-data')->name('master-data.')->group(function () {
        Route::get('/', [MasterDataController::class, 'index'])->name('index');
        Route::get('/clients', [MasterDataController::class, 'clients'])->name('clients');
        Route::post('/clients', [MasterDataController::class, 'storeClient'])->name('clients.store');
        Route::put('/clients/{customer}', [MasterDataController::class, 'updateClient'])->name('clients.update');
        Route::delete('/clients/{customer}', [MasterDataController::class, 'destroyClient'])->name('clients.destroy');
        Route::get('/employees', [MasterDataController::class, 'employees'])->name('employees');
        Route::post('/employees', [MasterDataController::class, 'storeEmployee'])->name('employees.store');
        Route::put('/employees/{employee}', [MasterDataController::class, 'updateEmployee'])->name('employees.update');
        Route::delete('/employees/{employee}', [MasterDataController::class, 'destroyEmployee'])->name('employees.destroy');
        Route::get('/sites', [MasterDataController::class, 'sites'])->name('sites');
        Route::post('/sites', [MasterDataController::class, 'storeSite'])->name('sites.store');
        Route::put('/sites/{site}', [MasterDataController::class, 'updateSite'])->name('sites.update');
        Route::delete('/sites/{site}', [MasterDataController::class, 'destroySite'])->name('sites.destroy');
    });
    Route::middleware(['auth', 'verified'])->get('/dashboard/master-data', [MasterDataController::class, 'index'])->name('dashboard_master_data');

    Route::middleware(['auth', 'verified', 'role:Super-Admin'])->prefix('system-tables')->name('system-tables.')->group(function () {
        Route::get('/', [SystemTableController::class, 'index'])->name('index');
        Route::get('/users', [SystemTableController::class, 'users'])->name('users');
        Route::post('/users', [SystemTableController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [SystemTableController::class, 'updateUser'])->name('users.update');
        Route::post('/users/{user}/toggle-status', [SystemTableController::class, 'toggleUserStatus'])->name('users.toggle-status');
        Route::delete('/users/{user}', [SystemTableController::class, 'destroyUser'])->name('users.destroy');
        Route::get('/roles', [SystemTableController::class, 'roles'])->name('roles');
        Route::post('/roles', [SystemTableController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{role}', [SystemTableController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [SystemTableController::class, 'destroyRole'])->name('roles.destroy');
        Route::post('/permissions', [SystemTableController::class, 'storePermission'])->name('permissions.store');
        Route::put('/permissions/{permission}', [SystemTableController::class, 'updatePermission'])->name('permissions.update');
        Route::delete('/permissions/{permission}', [SystemTableController::class, 'destroyPermission'])->name('permissions.destroy');
        Route::get('/activity-log', [SystemTableController::class, 'activityLog'])->name('activity-log');
        Route::get('/notifications', [SystemTableController::class, 'notifications'])->name('notifications');
        Route::get('/queues', [SystemTableController::class, 'queues'])->name('queues');
        Route::get('/cache', [SystemTableController::class, 'cache'])->name('cache');
        Route::get('/pruning', [SystemTableController::class, 'pruningSettings'])->name('pruning');
        Route::post('/pruning/settings', [SystemTableController::class, 'updatePruningSettings'])->name('pruning.update');
        Route::post('/pruning/dry-run', [SystemTableController::class, 'dryRunPruning'])->name('pruning.dry-run');
        Route::post('/pruning/execute', [SystemTableController::class, 'executePruning'])->name('pruning.execute');
        Route::post('/pruning/reset', [SystemTableController::class, 'resetPruningSettings'])->name('pruning.reset');
        Route::post('/pruning/tables', [SystemTableController::class, 'addPruningTable'])->name('pruning.tables.add');
        Route::delete('/pruning/tables/{table}', [SystemTableController::class, 'removePruningTable'])->name('pruning.tables.remove');
        Route::get('/trashed', [SystemTableController::class, 'trashedIndex'])->name('trashed');
        Route::get('/trashed/{table}/records', [SystemTableController::class, 'getTrashedRecords'])->name('trashed.records');
        Route::get('/trashed/{table}', [SystemTableController::class, 'trashedShow'])->name('trashed.show');
        Route::post('/trashed/{table}/purge-single/{id}', [SystemTableController::class, 'purgeSingleTrashedRecord'])->name('trashed.purge-single');
        Route::post('/trashed/{table}/purge', [SystemTableController::class, 'purgeTableTrashed'])->name('trashed.purge-table');
        Route::post('/trashed/purge-all', [SystemTableController::class, 'purgeAllTrashed'])->name('trashed.purge-all');
        Route::get('/pruning/trashed/{table}', [SystemTableController::class, 'getTrashedRecords'])->name('pruning.trashed.records');
        Route::post('/pruning/trashed/{table}/purge-single/{id}', [SystemTableController::class, 'purgeSingleTrashedRecord'])->name('pruning.trashed.purge-single');
        Route::post('/pruning/trashed/{table}/purge', [SystemTableController::class, 'purgeTableTrashed'])->name('pruning.trashed.purge-table');
        Route::post('/pruning/trashed/purge-all', [SystemTableController::class, 'purgeAllTrashed'])->name('pruning.trashed.purge-all');
        Route::get('/backups', [SystemTableController::class, 'backups'])->name('backups');
        Route::post('/backups/create', [SystemTableController::class, 'createBackup'])->name('backups.create');
        Route::get('/backups/download/{file}', [SystemTableController::class, 'downloadBackup'])->name('backups.download');
        Route::post('/backups/restore', [SystemTableController::class, 'restoreBackup'])->name('backups.restore');
        Route::post('/backups/restore-oldest', [SystemTableController::class, 'restoreOldestBackup'])->name('backups.restore-oldest');
        Route::delete('/backups/{file}', [SystemTableController::class, 'deleteBackup'])->name('backups.delete');
        Route::get('/settings', [SystemTableController::class, 'settings'])->name('settings');
        Route::post('/settings/toggle-registration', [SystemTableController::class, 'toggleRegistration'])->name('settings.toggle-registration');
        Route::post('/settings/update', [SystemTableController::class, 'updateSetting'])->name('settings.update');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    require __DIR__.'/auth.php';
};

if (app()->runningUnitTests()) {
    foreach (['en', 'fr'] as $locale) {
        Route::group([
            'prefix' => $locale,
            'as' => "{$locale}.",
            'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
        ], $routes);
    }
}

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
], $routes);

// Failsafe public storage delivery (especially for cPanel / environments where symlinks are missing or disabled)
Route::get('storage/{path}', [StorageFileController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.file');
