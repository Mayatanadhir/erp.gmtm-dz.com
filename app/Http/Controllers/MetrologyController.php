<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Metrology\EquipmentController;
use App\Http\Controllers\Metrology\GrandeurUnitController;
use App\Http\Controllers\Metrology\InstrumentController;
use App\Http\Controllers\Metrology\ReportController;
use App\Http\Requests\Metrology\StoreEquipmentRequest;
use App\Http\Requests\Metrology\StoreGrandeurRequest;
use App\Http\Requests\Metrology\StoreInstrumentRequest;
use App\Http\Requests\Metrology\UpdateEquipmentRequest;
use App\Http\Requests\Metrology\UpdateGrandeurRequest;
use App\Http\Requests\Metrology\UpdateInstrumentRequest;
use App\Models\Equipment;
use App\Models\Grandeur;
use App\Models\Instrument;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MetrologyController extends Controller
{
    /**
     * Display the Metrology & Equipments dashboard.
     */
    public function index(): View
    {
        Gate::authorize('view metrology');

        return view('metrology.index');
    }

    /**
     * Display the Measuring Instruments explorer.
     */
    public function instruments(Request $request): View
    {
        return app(InstrumentController::class)->index($request);
    }

    /**
     * Display the specified instrument details view.
     */
    public function showInstrument(Instrument $instrument): View
    {
        return app(InstrumentController::class)->show($instrument);
    }

    /**
     * Store a newly created instrument record.
     */
    public function storeInstrument(StoreInstrumentRequest $request): RedirectResponse
    {
        return app(InstrumentController::class)->store($request);
    }

    /**
     * Update the specified instrument record.
     */
    public function updateInstrument(UpdateInstrumentRequest $request, Instrument $instrument): RedirectResponse
    {
        return app(InstrumentController::class)->update($request, $instrument);
    }

    /**
     * Remove the specified instrument from storage.
     */
    public function destroyInstrument(Request $request, Instrument $instrument): RedirectResponse
    {
        return app(InstrumentController::class)->destroy($request, $instrument);
    }

    /**
     * Display the Equipment explorer.
     */
    public function equipment(Request $request): View
    {
        return app(EquipmentController::class)->index($request);
    }

    /**
     * Display the specified equipment details view.
     */
    public function showEquipment(Equipment $equipment): View
    {
        return app(EquipmentController::class)->show($equipment);
    }

    /**
     * Store a newly created equipment record.
     */
    public function storeEquipment(StoreEquipmentRequest $request): RedirectResponse
    {
        return app(EquipmentController::class)->store($request);
    }

    /**
     * Update the specified equipment record.
     */
    public function updateEquipment(UpdateEquipmentRequest $request, Equipment $equipment): RedirectResponse
    {
        return app(EquipmentController::class)->update($request, $equipment);
    }

    /**
     * Remove the specified equipment from storage.
     */
    public function destroyEquipment(Equipment $equipment): RedirectResponse
    {
        return app(EquipmentController::class)->destroy($equipment);
    }

    /**
     * Display the Calibrator Movements explorer.
     */
    public function calibratorMovements(): View
    {
        Gate::authorize('view calibrator movements');

        return view('metrology.calibrator-movements');
    }

    /**
     * Display the Calibration Certificates explorer.
     */
    public function calibrationCertificates(): View
    {
        Gate::authorize('view calibration certificates');

        return view('metrology.calibration-certificates');
    }

    /**
     * Display the Quantities & Units explorer.
     */
    public function units(Request $request): View
    {
        return app(GrandeurUnitController::class)->index($request);
    }

    /**
     * Store a newly created physical quantity and unit.
     */
    public function storeUnit(StoreGrandeurRequest $request): RedirectResponse
    {
        return app(GrandeurUnitController::class)->store($request);
    }

    /**
     * Update the specified physical quantity and unit.
     */
    public function updateUnit(UpdateGrandeurRequest $request, Grandeur $grandeur): RedirectResponse
    {
        return app(GrandeurUnitController::class)->update($request, $grandeur);
    }

    /**
     * Remove the specified physical quantity and unit.
     */
    public function destroyUnit(Grandeur $grandeur): RedirectResponse
    {
        return app(GrandeurUnitController::class)->destroy($grandeur);
    }

    /**
     * Display the Metrology Reports explorer.
     */
    public function reports(): View
    {
        return app(ReportController::class)->index(request());
    }
}
