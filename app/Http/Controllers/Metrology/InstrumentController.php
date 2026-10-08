<?php

declare(strict_types=1);

namespace App\Http\Controllers\Metrology;

use App\Enums\GrandeurType;
use App\Enums\InstrumentStatus;
use App\Enums\InstrumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Metrology\StoreInstrumentRequest;
use App\Http\Requests\Metrology\UpdateInstrumentRequest;
use App\Interfaces\InstrumentRepositoryInterface;
use App\Models\Grandeur;
use App\Models\Instrument;
use App\Models\Site;
use App\Services\InstrumentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InstrumentController extends Controller
{
    public function __construct(
        protected InstrumentService $instrumentService,
        protected InstrumentRepositoryInterface $instrumentRepository,
    ) {}

    /**
     * Display the Measuring Instruments explorer.
     */
    public function index(Request $request): View
    {
        Gate::authorize('view measuring instruments');

        $instruments = $this->instrumentRepository->paginateWithFilter($request->all(), 15);
        $stats = $this->instrumentService->getStatistics();
        $sites = Site::orderBy('short_name')->get();
        $measurementGrandeurs = Grandeur::where('type', GrandeurType::Measurement)->orderBy('name')->get();
        $sourceGrandeurs = Grandeur::where('type', GrandeurType::Source)->orderBy('name')->get();
        $transmitters = Instrument::where('instrument_type', InstrumentType::Transmitter)
            ->where('status', InstrumentStatus::Active)
            ->orderBy('tag_number')
            ->get();

        return view('metrology.instruments', compact(
            'instruments',
            'stats',
            'sites',
            'measurementGrandeurs',
            'sourceGrandeurs',
            'transmitters'
        ));
    }

    /**
     * Redirect to the measuring instruments explorer or delegate if specific type is requested.
     */
    public function create(Request $request): View|RedirectResponse
    {
        Gate::authorize('create measuring instruments');

        if ($request->has('type')) {
            return $this->createType((string) $request->query('type'));
        }

        return redirect()->route('metrology.instruments');
    }

    /**
     * Display the dedicated create form page for a specific instrument type.
     */
    public function createType(string $type): View
    {
        Gate::authorize('create measuring instruments');

        $normalizedType = str_replace('-', '_', strtolower(trim($type)));
        $instrumentType = InstrumentType::tryFromLegacy($normalizedType);

        if (! $instrumentType) {
            abort(404, __('Invalid instrument type.'));
        }

        $sites = Site::orderBy('short_name')->get();
        $measurementGrandeurs = Grandeur::where('type', GrandeurType::Measurement)->orderBy('name')->get();
        $sourceGrandeurs = Grandeur::where('type', GrandeurType::Source)->orderBy('name')->get();
        $transmitters = Instrument::where('instrument_type', InstrumentType::Transmitter)
            ->where('status', InstrumentStatus::Active)
            ->orderBy('tag_number')
            ->get();

        $viewSlug = str_replace('_', '-', $instrumentType->value);

        return view("metrology.instruments.create.{$viewSlug}", compact(
            'instrumentType',
            'sites',
            'measurementGrandeurs',
            'sourceGrandeurs',
            'transmitters'
        ));
    }

    /**
     * Display the specified instrument details view.
     */
    public function show(Instrument $instrument): View
    {
        Gate::authorize('view measuring instruments');

        $instrument->load([
            'site',
            'specifications.grandeur',
            'linkedTransmitters',
            'standardGaugeSpecification',
            'proverSpecification',
            'activities.causer',
        ]);

        $sites = Site::orderBy('short_name')->get();
        $measurementGrandeurs = Grandeur::where('type', GrandeurType::Measurement)->orderBy('name')->get();
        $sourceGrandeurs = Grandeur::where('type', GrandeurType::Source)->orderBy('name')->get();
        $transmitters = Instrument::where('instrument_type', InstrumentType::Transmitter)
            ->where('status', InstrumentStatus::Active)
            ->where('id', '!=', $instrument->id)
            ->orderBy('tag_number')
            ->get();

        return view('metrology.instruments.show', compact(
            'instrument',
            'sites',
            'measurementGrandeurs',
            'sourceGrandeurs',
            'transmitters'
        ));
    }

    /**
     * Display the full-featured edit instrument form page.
     */
    public function edit(Instrument $instrument): View
    {
        Gate::authorize('edit measuring instruments');

        $instrument->load([
            'site',
            'specifications.grandeur',
            'linkedTransmitters',
            'standardGaugeSpecification',
            'proverSpecification',
        ]);

        $sites = Site::orderBy('short_name')->get();
        $measurementGrandeurs = Grandeur::where('type', GrandeurType::Measurement)->orderBy('name')->get();
        $sourceGrandeurs = Grandeur::where('type', GrandeurType::Source)->orderBy('name')->get();
        $mappedSpecs = $instrument->specifications->keyBy('grandeur_id');

        $transmitters = Instrument::where('instrument_type', InstrumentType::Transmitter)
            ->where('status', InstrumentStatus::Active)
            ->where('id', '!=', $instrument->id)
            ->orderBy('tag_number')
            ->get();

        $linkedTransmitters = $instrument->linkedTransmitters
            ->mapWithKeys(fn ($t) => [$t->id => $t->pivot->channel_number]);

        $viewSlug = str_replace('_', '-', $instrument->instrument_type->value);

        if (view()->exists("metrology.instruments.edit.{$viewSlug}")) {
            return view("metrology.instruments.edit.{$viewSlug}", compact(
                'instrument',
                'sites',
                'measurementGrandeurs',
                'sourceGrandeurs',
                'mappedSpecs',
                'transmitters',
                'linkedTransmitters'
            ));
        }

        abort(404, __('Instrument edit view not found.'));
    }

    /**
     * Store a newly created instrument record.
     */
    public function store(StoreInstrumentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $image = $request->file('image');
        $params = $request->input('params');
        $transmitters = $request->input('transmitters');
        $standardGaugeSpec = $request->input('standard_gauge_spec');
        $proverSpec = $request->input('prover_spec');

        $this->instrumentService->createInstrument(
            $data,
            $image,
            $params,
            $transmitters,
            $standardGaugeSpec,
            $proverSpec
        );

        return redirect()->route('metrology.instruments', $request->query())
            ->with('success', __('Measuring instrument created successfully.'));
    }

    /**
     * Update the specified instrument record.
     */
    public function update(UpdateInstrumentRequest $request, Instrument $instrument): RedirectResponse
    {
        $data = $request->validated();
        $image = $request->file('image');
        $params = $request->input('params');
        $transmitters = $request->input('transmitters');
        $standardGaugeSpec = $request->input('standard_gauge_spec');
        $proverSpec = $request->input('prover_spec');
        $removeImage = (bool) $request->boolean('remove_image');

        $this->instrumentService->updateInstrument(
            $instrument,
            $data,
            $image,
            $params,
            $transmitters,
            $standardGaugeSpec,
            $proverSpec,
            $removeImage
        );

        $redirectUrl = $request->input('_redirect') ?: route('metrology.instruments', $request->query());

        return redirect($redirectUrl)
            ->with('success', __('Measuring instrument updated successfully.'));
    }

    /**
     * Remove the specified instrument from storage.
     */
    public function destroy(Request $request, Instrument $instrument): RedirectResponse
    {
        Gate::authorize('delete measuring instruments');

        try {
            $this->instrumentService->deleteInstrument($instrument);

            return redirect()->route('metrology.instruments', $request->query())
                ->with('success', __('Measuring instrument deleted successfully.'));
        } catch (\DomainException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
