<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccuracyType;
use App\Enums\InstrumentStatus;
use App\Enums\InstrumentType;
use App\Interfaces\InstrumentRepositoryInterface;
use App\Models\Instrument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class InstrumentService extends BaseService
{
    public function __construct(
        protected InstrumentRepositoryInterface $instrumentRepository,
        protected MediaOptimizationService $mediaService
    ) {}

    /**
     * Create an instrument record, optimize image, and sync specifications.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int|string, mixed>|null  $params
     * @param  array<int, mixed>|null  $transmitters
     * @param  array<string, mixed>|null  $standardGaugeSpec
     * @param  array<string, mixed>|null  $proverSpec
     */
    public function createInstrument(
        array $data,
        ?UploadedFile $image = null,
        ?array $params = null,
        ?array $transmitters = null,
        ?array $standardGaugeSpec = null,
        ?array $proverSpec = null
    ): Instrument {
        return $this->executeInTransaction(function () use (
            $data,
            $image,
            $params,
            $transmitters,
            $standardGaugeSpec,
            $proverSpec
        ): Instrument {
            if ($image !== null) {
                $storedPath = $this->mediaService->optimizeImage($image, 'instruments/images');
                $data['image_path'] = $storedPath;

                $disk = Storage::disk('public');
                if ($disk->exists($storedPath)) {
                    $data['image_hash'] = hash('sha256', (string) $disk->get($storedPath));
                }
            }

            $cleanData = Arr::except($data, [
                'image',
                'params',
                'transmitters',
                'standard_gauge_spec',
                'prover_spec',
            ]);

            /** @var Instrument $instrument */
            $instrument = $this->instrumentRepository->create($cleanData);

            $this->syncSpecifications($instrument, $params);
            $this->syncTransmitters($instrument, $transmitters);
            $this->syncStandardGaugeSpecification($instrument, $standardGaugeSpec);
            $this->syncProverSpecification($instrument, $proverSpec);

            return $instrument;
        });
    }

    /**
     * Update an instrument record, handle image updates/removal, and sync specifications.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int|string, mixed>|null  $params
     * @param  array<int, mixed>|null  $transmitters
     * @param  array<string, mixed>|null  $standardGaugeSpec
     * @param  array<string, mixed>|null  $proverSpec
     */
    public function updateInstrument(
        Instrument $instrument,
        array $data,
        ?UploadedFile $image = null,
        ?array $params = null,
        ?array $transmitters = null,
        ?array $standardGaugeSpec = null,
        ?array $proverSpec = null,
        bool $removeImage = false
    ): bool {
        return $this->executeInTransaction(function () use (
            $instrument,
            $data,
            $image,
            $params,
            $transmitters,
            $standardGaugeSpec,
            $proverSpec,
            $removeImage
        ): bool {
            if ($removeImage) {
                if (! blank($instrument->image_path)) {
                    $this->mediaService->safeDelete($instrument->image_path, 'public', $instrument->id);
                    $data['image_path'] = null;
                    $data['image_hash'] = null;
                }
            } elseif ($image !== null) {
                if (! blank($instrument->image_path)) {
                    $this->mediaService->safeDelete($instrument->image_path, 'public', $instrument->id);
                }
                $storedPath = $this->mediaService->optimizeImage($image, 'instruments/images');
                $data['image_path'] = $storedPath;

                $disk = Storage::disk('public');
                if ($disk->exists($storedPath)) {
                    $data['image_hash'] = hash('sha256', (string) $disk->get($storedPath));
                }
            }

            $cleanData = Arr::except($data, [
                'image',
                'remove_image',
                'params',
                'transmitters',
                'standard_gauge_spec',
                'prover_spec',
            ]);

            $updated = $this->instrumentRepository->update($instrument->id, $cleanData);

            $this->syncSpecifications($instrument, $params);
            $this->syncTransmitters($instrument, $transmitters);
            $this->syncStandardGaugeSpecification($instrument, $standardGaugeSpec);
            $this->syncProverSpecification($instrument, $proverSpec);

            return $updated;
        });
    }

    /**
     * Delete an instrument record by validating constraints and isolating category-specific cleanup.
     *
     * @throws \DomainException
     */
    public function deleteInstrument(Instrument $instrument): bool
    {
        return match ($instrument->instrument_type) {
            InstrumentType::Transmitter => $this->deleteTransmitter($instrument),
            InstrumentType::FlowComputer => $this->deleteFlowComputer($instrument),
            InstrumentType::Probe => $this->deleteProbe($instrument),
            InstrumentType::Chromatograph => $this->deleteChromatograph($instrument),
            InstrumentType::StandardGauge => $this->deleteStandardGauge($instrument),
            InstrumentType::Prover => $this->deleteProver($instrument),
            default => $this->deleteGenericInstrument($instrument),
        };
    }

    /**
     * Delete a transmitter instrument after verifying report constraints and cleaning dependencies.
     *
     * @throws \DomainException
     */
    public function deleteTransmitter(Instrument $instrument): bool
    {
        if ($instrument->isLinkedToReportsOrVerifications()) {
            throw new \DomainException(__('Cannot delete measuring instrument because associated metrological calibration reports exist.'));
        }

        return $this->executeInTransaction(function () use ($instrument): bool {
            $instrument->flowComputers()->detach();
            $instrument->specifications()->delete();
            $this->cleanInstrumentMedia($instrument);

            return (bool) $instrument->delete();
        });
    }

    /**
     * Delete a flow computer instrument after verifying report constraints and detaching transmitters.
     *
     * @throws \DomainException
     */
    public function deleteFlowComputer(Instrument $instrument): bool
    {
        if ($instrument->isLinkedToReportsOrVerifications()) {
            throw new \DomainException(__('Cannot delete measuring instrument because associated metrological calibration reports exist.'));
        }

        return $this->executeInTransaction(function () use ($instrument): bool {
            $instrument->linkedTransmitters()->detach();
            $instrument->specifications()->delete();
            $this->cleanInstrumentMedia($instrument);

            return (bool) $instrument->delete();
        });
    }

    /**
     * Delete a temperature / RTD probe instrument after verifying report constraints.
     *
     * @throws \DomainException
     */
    public function deleteProbe(Instrument $instrument): bool
    {
        if ($instrument->isLinkedToReportsOrVerifications()) {
            throw new \DomainException(__('Cannot delete measuring instrument because associated metrological calibration reports exist.'));
        }

        return $this->executeInTransaction(function () use ($instrument): bool {
            $instrument->specifications()->delete();
            $this->cleanInstrumentMedia($instrument);

            return (bool) $instrument->delete();
        });
    }

    /**
     * Delete a gas chromatograph instrument after verifying report constraints.
     *
     * @throws \DomainException
     */
    public function deleteChromatograph(Instrument $instrument): bool
    {
        if ($instrument->isLinkedToReportsOrVerifications()) {
            throw new \DomainException(__('Cannot delete measuring instrument because associated metrological calibration reports exist.'));
        }

        return $this->executeInTransaction(function () use ($instrument): bool {
            $instrument->specifications()->delete();
            $this->cleanInstrumentMedia($instrument);

            return (bool) $instrument->delete();
        });
    }

    /**
     * Delete a standard volumetric gauge (Jauge étalon) after verifying prover verifications and cleaning its dedicated specifications.
     *
     * @throws \DomainException
     */
    public function deleteStandardGauge(Instrument $instrument): bool
    {
        if ($instrument->isLinkedToReportsOrVerifications()) {
            throw new \DomainException(__('Cannot delete standard test measure gauge because it is referenced in prover calibration verifications.'));
        }

        return $this->executeInTransaction(function () use ($instrument): bool {
            $instrument->standardGaugeSpecification()?->delete();
            $instrument->specifications()->delete();
            $this->cleanInstrumentMedia($instrument);

            return (bool) $instrument->delete();
        });
    }

    /**
     * Delete a pipe / SVP prover instrument after verifying prover verifications and cleaning its dedicated specifications.
     *
     * @throws \DomainException
     */
    public function deleteProver(Instrument $instrument): bool
    {
        if ($instrument->isLinkedToReportsOrVerifications()) {
            throw new \DomainException(__('Cannot delete pipe prover because associated calibration verifications exist.'));
        }

        return $this->executeInTransaction(function () use ($instrument): bool {
            $instrument->proverSpecification()?->delete();
            $instrument->specifications()->delete();
            $this->cleanInstrumentMedia($instrument);

            return (bool) $instrument->delete();
        });
    }

    /**
     * Delete a generic measuring instrument fallback after verifying report constraints.
     *
     * @throws \DomainException
     */
    public function deleteGenericInstrument(Instrument $instrument): bool
    {
        if ($instrument->isLinkedToReportsOrVerifications()) {
            throw new \DomainException(__('Cannot delete measuring instrument because associated metrological calibration reports exist.'));
        }

        return $this->executeInTransaction(function () use ($instrument): bool {
            $instrument->specifications()->delete();
            $this->cleanInstrumentMedia($instrument);

            return (bool) $instrument->delete();
        });
    }

    /**
     * Safely delete the instrument's image from storage if present.
     */
    protected function cleanInstrumentMedia(Instrument $instrument): void
    {
        if (! blank($instrument->image_path)) {
            $this->mediaService->safeDelete($instrument->image_path, 'public', $instrument->id);
        }
    }

    /**
     * Synchronize physical specifications.
     *
     * @param  array<int|string, mixed>|null  $params
     */
    public function syncSpecifications(Instrument $instrument, ?array $params): void
    {
        $keptIds = [];

        if (is_array($params)) {
            foreach ($params as $grandeurId => $details) {
                if (empty($details['selected'])) {
                    continue;
                }

                $spec = $instrument->specifications()->updateOrCreate(
                    ['grandeur_id' => (int) $grandeurId],
                    [
                        'range_min' => isset($details['min']) && $details['min'] !== '' ? (float) $details['min'] : null,
                        'range_max' => isset($details['max']) && $details['max'] !== '' ? (float) $details['max'] : null,
                        'accuracy_value' => isset($details['acc']) && $details['acc'] !== '' ? (float) $details['acc'] : null,
                        'accuracy_type' => isset($details['acc_type']) && $details['acc_type'] !== '' ? $details['acc_type'] : AccuracyType::Percentage->value,
                    ]
                );

                $keptIds[] = $spec->id;
            }
        }

        $instrument->specifications()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Synchronize linked transmitters for flow computer.
     *
     * @param  array<int, mixed>|null  $transmitters
     */
    public function syncTransmitters(Instrument $instrument, ?array $transmitters): void
    {
        if ($instrument->instrument_type !== InstrumentType::FlowComputer) {
            $instrument->linkedTransmitters()->detach();

            return;
        }

        $syncData = [];
        if (is_array($transmitters)) {
            foreach ($transmitters as $item) {
                $transmitterId = (int) ($item['transmitter_id'] ?? $item['id'] ?? 0);
                $channel = trim((string) ($item['channel'] ?? $item['channel_number'] ?? ''));

                if ($transmitterId > 0) {
                    $syncData[$transmitterId] = ['channel_number' => $channel !== '' ? $channel : 'Ch1'];
                }
            }
        }

        $instrument->linkedTransmitters()->sync($syncData);
    }

    /**
     * Synchronize standard gauge specifications.
     *
     * @param  array<string, mixed>|null  $specData
     */
    public function syncStandardGaugeSpecification(Instrument $instrument, ?array $specData): void
    {
        if ($instrument->instrument_type !== InstrumentType::StandardGauge) {
            $instrument->standardGaugeSpecification()?->delete();

            return;
        }

        if (empty($specData) || ! isset($specData['nominal_capacity_liters'])) {
            return;
        }

        $instrument->standardGaugeSpecification()->updateOrCreate(
            ['instrument_id' => $instrument->id],
            [
                'nominal_capacity_liters' => (float) $specData['nominal_capacity_liters'],
                'neck_scale_sensitivity' => isset($specData['neck_scale_sensitivity']) && $specData['neck_scale_sensitivity'] !== '' ? (float) $specData['neck_scale_sensitivity'] : null,
                'cubical_expansion_coef_gcm' => isset($specData['cubical_expansion_coef_gcm']) && $specData['cubical_expansion_coef_gcm'] !== '' ? (float) $specData['cubical_expansion_coef_gcm'] : null,
                'vessel_material' => $specData['vessel_material'] ?? 'Stainless Steel',
                'base_reference_temperature' => isset($specData['base_reference_temperature']) && $specData['base_reference_temperature'] !== '' ? (float) $specData['base_reference_temperature'] : 20.00,
                'calibration_certificate_number' => $specData['calibration_certificate_number'] ?? null,
                'calibration_date' => $specData['calibration_date'] ?? null,
                'calibration_expiry_date' => $specData['calibration_expiry_date'] ?? null,
            ]
        );
    }

    /**
     * Synchronize prover specifications.
     *
     * @param  array<string, mixed>|null  $specData
     */
    public function syncProverSpecification(Instrument $instrument, ?array $specData): void
    {
        if ($instrument->instrument_type !== InstrumentType::Prover) {
            $instrument->proverSpecification()?->delete();

            return;
        }

        if (empty($specData)) {
            return;
        }

        $instrument->proverSpecification()->updateOrCreate(
            ['instrument_id' => $instrument->id],
            [
                'type' => $specData['type'] ?? 'bidirectional_pipe',
                'inner_diameter' => isset($specData['inner_diameter']) && $specData['inner_diameter'] !== '' ? (float) $specData['inner_diameter'] : null,
                'wall_thickness' => isset($specData['wall_thickness']) && $specData['wall_thickness'] !== '' ? (float) $specData['wall_thickness'] : null,
                'nominal_base_volume' => isset($specData['nominal_base_volume']) && $specData['nominal_base_volume'] !== '' ? (float) $specData['nominal_base_volume'] : null,
                'cubical_expansion_coef' => isset($specData['cubical_expansion_coef']) && $specData['cubical_expansion_coef'] !== '' ? (float) $specData['cubical_expansion_coef'] : null,
                'elasticity_modulus' => isset($specData['elasticity_modulus']) && $specData['elasticity_modulus'] !== '' ? (float) $specData['elasticity_modulus'] : null,
                'area_expansion_coef' => isset($specData['area_expansion_coef']) && $specData['area_expansion_coef'] !== '' ? (float) $specData['area_expansion_coef'] : null,
                'linear_expansion_coef' => isset($specData['linear_expansion_coef']) && $specData['linear_expansion_coef'] !== '' ? (float) $specData['linear_expansion_coef'] : null,
                'material' => $specData['material'] ?? 'Mild Steel',
                'pulse_interpolation' => (bool) ($specData['pulse_interpolation'] ?? false),
            ]
        );
    }

    /**
     * Get aggregate statistics for measuring instruments.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total' => Instrument::count(),
            'active' => Instrument::where('status', InstrumentStatus::Active)->count(),
            'transmitters' => Instrument::where('instrument_type', InstrumentType::Transmitter)->count(),
            'probes' => Instrument::where('instrument_type', InstrumentType::Probe)->count(),
            'flow_computers' => Instrument::where('instrument_type', InstrumentType::FlowComputer)->count(),
            'chromatographs' => Instrument::where('instrument_type', InstrumentType::Chromatograph)->count(),
            'standard_gauges' => Instrument::where('instrument_type', InstrumentType::StandardGauge)->count(),
            'provers' => Instrument::where('instrument_type', InstrumentType::Prover)->count(),
        ];
    }
}
