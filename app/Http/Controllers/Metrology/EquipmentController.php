<?php

declare(strict_types=1);

namespace App\Http\Controllers\Metrology;

use App\Enums\GrandeurType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Metrology\StoreEquipmentRequest;
use App\Http\Requests\Metrology\UpdateEquipmentRequest;
use App\Interfaces\EquipmentRepositoryInterface;
use App\Models\Equipment;
use App\Models\Grandeur;
use App\Services\EquipmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EquipmentController extends Controller
{
    public function __construct(
        protected EquipmentService $equipmentService,
        protected EquipmentRepositoryInterface $equipmentRepository,
    ) {}

    /**
     * Display the Equipment explorer.
     */
    public function index(Request $request): View
    {
        Gate::authorize('view equipment');

        $equipment = $this->equipmentRepository->paginateWithFilter($request->all(), 15);
        $stats = $this->equipmentService->getStatistics();
        $measurementGrandeurs = Grandeur::where('type', GrandeurType::Measurement)->orderBy('name')->get();
        $sourceGrandeurs = Grandeur::where('type', GrandeurType::Source)->orderBy('name')->get();

        return view('metrology.equipment', compact('equipment', 'stats', 'measurementGrandeurs', 'sourceGrandeurs'));
    }

    /**
     * Display the specified equipment details view.
     */
    public function show(Equipment $equipment): View
    {
        Gate::authorize('view equipment');

        $equipment->load([
            'specifications.grandeur',
            'grandeurs',
            'activities.causer',
            'calibrationCertificates.calibrationPoints',
        ]);

        $measurementGrandeurs = Grandeur::where('type', GrandeurType::Measurement)->orderBy('name')->get();
        $sourceGrandeurs = Grandeur::where('type', GrandeurType::Source)->orderBy('name')->get();

        return view('metrology.equipment.show', compact('equipment', 'measurementGrandeurs', 'sourceGrandeurs'));
    }

    /**
     * Store a newly created equipment record.
     */
    public function store(StoreEquipmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $image = $request->file('image');
        $certificate = $request->file('certificate');
        $params = $request->validated('params');

        $this->equipmentService->createEquipment($data, $image, $certificate, $params);

        return redirect()->route('metrology.equipment', $request->query())
            ->with('success', __('Equipment created successfully.'));
    }

    /**
     * Update the specified equipment record.
     */
    public function update(UpdateEquipmentRequest $request, Equipment $equipment): RedirectResponse
    {
        $data = $request->validated();
        $image = $request->file('image');
        $certificate = $request->file('certificate');
        $params = $request->validated('params');
        $removeImage = (bool) $request->boolean('remove_image');
        $removeCertificate = (bool) $request->boolean('remove_certificate');

        $this->equipmentService->updateEquipment(
            $equipment,
            $data,
            $image,
            $certificate,
            $params,
            $removeImage,
            $removeCertificate
        );

        $redirectUrl = $request->input('_redirect');
        if (! blank($redirectUrl)) {
            $parsedHost = parse_url((string) $redirectUrl, PHP_URL_HOST);
            $isSafe = str_starts_with((string) $redirectUrl, '/') || $parsedHost === null || $parsedHost === $request->getHost();
            if (! $isSafe) {
                $redirectUrl = null;
            }
        }

        $targetUrl = $redirectUrl ?: route('metrology.equipment', $request->query());

        return redirect()->to($targetUrl)
            ->with('success', __('Equipment updated successfully.'));
    }

    /**
     * Remove the specified equipment from storage.
     */
    public function destroy(Equipment $equipment): RedirectResponse
    {
        Gate::authorize('delete equipment');

        $this->equipmentService->deleteEquipment($equipment);

        return redirect()->route('metrology.equipment', request()->query())
            ->with('success', __('Equipment deleted successfully.'));
    }
}
