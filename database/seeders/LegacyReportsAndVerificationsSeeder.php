<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PDO;

class LegacyReportsAndVerificationsSeeder extends Seeder
{
    public function run(): void
    {
        $pdoLegacy = new PDO('mysql:host=127.0.0.1;dbname=gmtm_app;charset=utf8mb4', 'root', 'root');
        $pdoErp = DB::connection()->getPdo();

        // 1. Build Exact Instrument Mapping
        $legacyInst = $pdoLegacy->query('SELECT id, tag_number, serial_number FROM instruments')->fetchAll(PDO::FETCH_ASSOC);
        $erpInst = $pdoErp->query('SELECT id, tag_number, serial_number FROM instruments')->fetchAll(PDO::FETCH_ASSOC);

        $erpByTag = [];
        foreach ($erpInst as $i) {
            $erpByTag[trim($i['tag_number'])] = (int) $i['id'];
        }

        $instMap = [];
        foreach ($legacyInst as $i) {
            $tag = trim($i['tag_number']);
            if (isset($erpByTag[$tag])) {
                $instMap[(int) $i['id']] = $erpByTag[$tag];
            } elseif (trim($i['serial_number']) === '1918757') {
                $instMap[(int) $i['id']] = 67; // 05 TW 595 C - PROVER
            } elseif ($tag === 'Chromato-STAH' || (int) $i['id'] === 189) {
                $instMap[(int) $i['id']] = 78; // Chromatograph 1001A
            } elseif (isset($erpByTag[str_replace(' - CONDENSAT', '', $tag)])) {
                $instMap[(int) $i['id']] = $erpByTag[str_replace(' - CONDENSAT', '', $tag)];
            } else {
                $instMap[(int) $i['id']] = (int) $i['id'];
            }
        }

        // 2. Build Exact Equipment Mapping
        $legacyEq = $pdoLegacy->query('SELECT id, short_name, full_name, serial_number FROM equipment')->fetchAll(PDO::FETCH_ASSOC);
        $erpEq = $pdoErp->query('SELECT id, short_name, full_name, serial_number FROM equipment')->fetchAll(PDO::FETCH_ASSOC);

        $eqMap = [];
        foreach ($legacyEq as $leq) {
            $matched = null;
            if (! empty(trim($leq['serial_number'] ?? ''))) {
                foreach ($erpEq as $eeq) {
                    if (trim($eeq['serial_number'] ?? '') === trim($leq['serial_number'])) {
                        $matched = $eeq;
                        break;
                    }
                }
            }
            if (! $matched) {
                foreach ($erpEq as $eeq) {
                    if (trim($eeq['full_name'] ?? '') === trim($leq['full_name'] ?? '')) {
                        $matched = $eeq;
                        break;
                    }
                }
            }
            if (! $matched) {
                foreach ($erpEq as $eeq) {
                    if (! empty(trim($leq['short_name'] ?? '')) && trim($eeq['short_name'] ?? '') === trim($leq['short_name'])) {
                        $matched = $eeq;
                        break;
                    }
                }
            }
            if ($matched) {
                $eqMap[(int) $leq['id']] = (int) $matched['id'];
            } else {
                throw new \RuntimeException("Unmapped equipment ID {$leq['id']}");
            }
        }

        // 3. Disable Foreign Key Checks during import
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            // 4. Import reports
            $reports = $pdoLegacy->query('SELECT * FROM reports')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($reports as $r) {
                // Remap excluded_instrument_ids
                $excluded = json_decode($r['excluded_instrument_ids'] ?? '[]', true) ?: [];
                $remappedExcluded = array_map(fn ($id) => $instMap[(int) $id] ?? (int) $id, $excluded);

                // Remap default_calibrators
                $defCals = json_decode($r['default_calibrators'] ?? '[]', true) ?: [];
                $remappedDefCals = [];
                foreach ($defCals as $k => $calId) {
                    if ($calId !== null && isset($eqMap[(int) $calId])) {
                        $remappedDefCals[$k] = (string) $eqMap[(int) $calId];
                    } else {
                        $remappedDefCals[$k] = $calId;
                    }
                }

                DB::table('reports')->updateOrInsert(
                    ['id' => $r['id']],
                    [
                        'mission_id' => $r['mission_id'],
                        'report_number' => $r['report_number'],
                        'status' => $r['status'] ?? 'progress',
                        'excluded_instrument_ids' => json_encode($remappedExcluded),
                        'default_calibrators' => json_encode($remappedDefCals),
                        'created_at' => $r['created_at'],
                        'updated_at' => $r['updated_at'],
                    ]
                );
            }

            // 5. Import report_instruments
            $reportInstruments = $pdoLegacy->query('SELECT * FROM report_instruments')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($reportInstruments as $ri) {
                $remappedInstId = $instMap[(int) $ri['instrument_id']];
                DB::table('report_instruments')->updateOrInsert(
                    ['id' => $ri['id']],
                    [
                        'report_mission_id' => $ri['report_mission_id'],
                        'instrument_id' => $remappedInstId,
                        'sequence' => $ri['sequence'],
                        'created_at' => $ri['created_at'],
                        'updated_at' => $ri['updated_at'],
                    ]
                );
            }

            // 6. Import transmitter_verifications
            $tv = $pdoLegacy->query('SELECT * FROM transmitter_verifications')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tv as $row) {
                DB::table('transmitter_verifications')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'instrument_id' => $instMap[(int) $row['instrument_id']],
                        'report_mission_id' => $row['report_mission_id'],
                        'verification_date' => $row['verification_date'],
                        'ambient_temperature' => $row['ambient_temperature'],
                        'ambient_pressure' => $row['ambient_pressure'],
                        'overall_status' => $row['overall_status'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ]
                );
            }

            // 7. Import transmitter_verification_points
            $tvp = $pdoLegacy->query('SELECT * FROM transmitter_verification_points')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tvp as $row) {
                DB::table('transmitter_verification_points')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'verification_id' => $row['verification_id'],
                        'step_order' => $row['step_order'],
                        'cycle_phase' => $row['cycle_phase'],
                        'applied_percentage' => $row['applied_percentage'],
                        'reference_value' => $row['reference_value'],
                        'measured_signal' => $row['measured_signal'],
                        'indicated_value' => $row['indicated_value'],
                        'absolute_error' => $row['absolute_error'],
                        'emt_limit' => $row['emt_limit'],
                        'is_conforme' => $row['is_conforme'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ]
                );
            }

            // 8. Import transmitter_verification_calibrators
            $tvc = $pdoLegacy->query('SELECT * FROM transmitter_verification_calibrators')->fetchAll(PDO::FETCH_ASSOC);
            DB::table('transmitter_verification_calibrators')->truncate();
            foreach ($tvc as $row) {
                DB::table('transmitter_verification_calibrators')->insert([
                    'verification_id' => $row['verification_id'],
                    'calibrator_id' => $eqMap[(int) $row['calibrator_id']],
                    'role' => $row['role'],
                ]);
            }

            // 9. Import probe_verifications
            $pv = $pdoLegacy->query('SELECT * FROM probe_verifications')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($pv as $row) {
                DB::table('probe_verifications')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'instrument_id' => $instMap[(int) $row['instrument_id']],
                        'report_mission_id' => $row['report_mission_id'],
                        'verification_date' => $row['verification_date'],
                        'ambient_temperature' => $row['ambient_temperature'],
                        'ambient_pressure' => $row['ambient_pressure'],
                        'overall_status' => $row['overall_status'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ]
                );
            }

            // 10. Import probe_verification_points
            $pvp = $pdoLegacy->query('SELECT * FROM probe_verification_points')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($pvp as $row) {
                DB::table('probe_verification_points')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'verification_id' => $row['verification_id'],
                        'step_order' => $row['step_order'],
                        'cycle_phase' => $row['cycle_phase'],
                        'reference_temperature' => $row['reference_temperature'],
                        'measured_resistance' => $row['measured_resistance'],
                        'indicated_temperature' => $row['indicated_temperature'],
                        'absolute_error' => $row['absolute_error'],
                        'emt_limit' => $row['emt_limit'],
                        'is_conforme' => $row['is_conforme'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ]
                );
            }

            // 11. Import probe_verification_calibrators
            $pvc = $pdoLegacy->query('SELECT * FROM probe_verification_calibrators')->fetchAll(PDO::FETCH_ASSOC);
            DB::table('probe_verification_calibrators')->truncate();
            foreach ($pvc as $row) {
                DB::table('probe_verification_calibrators')->insert([
                    'verification_id' => $row['verification_id'],
                    'calibrator_id' => $eqMap[(int) $row['calibrator_id']],
                    'role' => $row['role'],
                ]);
            }

            // 12. Import flow_computer_verifications
            $fcv = $pdoLegacy->query('SELECT * FROM flow_computer_verifications')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($fcv as $row) {
                DB::table('flow_computer_verifications')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'instrument_id' => $instMap[(int) $row['instrument_id']],
                        'simulated_transmitter_id' => $instMap[(int) $row['simulated_transmitter_id']],
                        'shunt_resistance' => $row['shunt_resistance'],
                        'report_mission_id' => $row['report_mission_id'],
                        'verification_date' => $row['verification_date'],
                        'ambient_temperature' => $row['ambient_temperature'],
                        'ambient_pressure' => $row['ambient_pressure'],
                        'overall_status' => $row['overall_status'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ]
                );
            }

            // 13. Import flow_computer_verification_points
            $fcp = $pdoLegacy->query('SELECT * FROM flow_computer_verification_points')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($fcp as $row) {
                DB::table('flow_computer_verification_points')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'verification_id' => $row['verification_id'],
                        'step_order' => $row['step_order'],
                        'cycle_phase' => $row['cycle_phase'],
                        'applied_percentage' => $row['applied_percentage'],
                        'expected_signal' => $row['expected_signal'],
                        'measured_signal' => $row['measured_signal'],
                        'expected_value' => $row['expected_value'],
                        'indicated_value' => $row['indicated_value'],
                        'absolute_error' => $row['absolute_error'],
                        'emt_limit' => $row['emt_limit'],
                        'is_conforme' => $row['is_conforme'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ]
                );
            }

            // 14. Import flow_computer_verification_calibrators
            $fcc = $pdoLegacy->query('SELECT * FROM flow_computer_verification_calibrators')->fetchAll(PDO::FETCH_ASSOC);
            DB::table('flow_computer_verification_calibrators')->truncate();
            foreach ($fcc as $row) {
                DB::table('flow_computer_verification_calibrators')->insert([
                    'verification_id' => $row['verification_id'],
                    'calibrator_id' => $eqMap[(int) $row['calibrator_id']],
                    'role' => $row['role'],
                ]);
            }

            // 15. Import chromatograph_verifications
            $cv = $pdoLegacy->query('SELECT * FROM chromatograph_verifications')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cv as $row) {
                DB::table('chromatograph_verifications')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'reference_number' => $row['reference_number'] ?? null,
                        'report_mission_id' => $row['report_mission_id'] ?? null,
                        'instrument_id' => $instMap[(int) $row['instrument_id']],
                        'verification_date' => $row['verification_date'],
                        'standard_gas_bottle_number' => $row['standard_gas_bottle_number'] ?? ($row['gas_bottle_number'] ?? null),
                        'gas_bottle_number' => $row['standard_gas_bottle_number'] ?? ($row['gas_bottle_number'] ?? null),
                        'certificate_number' => $row['certificate_number'] ?? null,
                        'reference_conditions' => $row['reference_conditions'] ?? '15°C / 101.325 kPa',
                        'cylinder_validity_date' => $row['cylinder_validity_date'] ?? ($row['gas_bottle_expiry'] ?? null),
                        'gas_bottle_expiry' => $row['cylinder_validity_date'] ?? ($row['gas_bottle_expiry'] ?? null),
                        'cylinder_pressure_bar' => $row['cylinder_pressure_bar'] ?? ($row['gas_bottle_pressure'] ?? null),
                        'gas_bottle_pressure' => $row['cylinder_pressure_bar'] ?? ($row['gas_bottle_pressure'] ?? null),
                        'ambient_temperature' => $row['ambient_temperature'] ?? null,
                        'ambient_pressure' => $row['ambient_pressure'] ?? null,
                        'repeatability_status' => (bool) ($row['repeatability_status'] ?? false),
                        'composition_accuracy_status' => (bool) ($row['composition_accuracy_status'] ?? false),
                        'physical_properties_status' => (bool) ($row['physical_properties_status'] ?? false),
                        'overall_status' => (bool) $row['overall_status'],
                        'remarks' => $row['remarks'] ?? null,
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ]
                );
            }

            // 16. Import chromatograph_composition_points
            $ccp = $pdoLegacy->query('SELECT * FROM chromatograph_composition_points')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($ccp as $row) {
                DB::table('chromatograph_composition_points')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'verification_id' => $row['verification_id'],
                        'step_order' => $row['step_order'],
                        'component_name' => $row['component_name'],
                        'component_symbol' => $row['component_symbol'],
                        'reference_value' => $row['reference_value'],
                        'run_1' => $row['run_1'],
                        'run_2' => $row['run_2'],
                        'run_3' => $row['run_3'],
                        'run_4' => $row['run_4'],
                        'run_5' => $row['run_5'],
                        'mean_value' => $row['mean_value'],
                        'repeatability' => $row['repeatability'],
                        'repeatability_limit_astm' => $row['repeatability_limit_astm'],
                        'repeatability_is_conforme' => (bool) $row['repeatability_is_conforme'],
                        'relative_error_percent' => $row['relative_error_percent'],
                        'emt_limit_percent' => $row['emt_limit_percent'],
                        'error_is_conforme' => (bool) $row['error_is_conforme'],
                        'is_conforme' => (bool) $row['is_conforme'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ]
                );
            }

            // 17. Import chromatograph_physical_properties
            $cpp = $pdoLegacy->query('SELECT * FROM chromatograph_physical_properties')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cpp as $row) {
                DB::table('chromatograph_physical_properties')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'verification_id' => $row['verification_id'],
                        'property_name' => $row['property_name'],
                        'property_symbol' => $row['property_symbol'],
                        'unit' => $row['unit'],
                        'reference_value' => $row['reference_value'],
                        'run_1' => $row['run_1'],
                        'run_2' => $row['run_2'],
                        'run_3' => $row['run_3'],
                        'run_4' => $row['run_4'],
                        'run_5' => $row['run_5'],
                        'mean_value' => $row['mean_value'],
                        'relative_error_percent' => $row['relative_error_percent'],
                        'emt_limit_percent' => $row['emt_limit_percent'],
                        'repeatability' => $row['repeatability'],
                        'repeatability_limit' => $row['repeatability_limit'],
                        'is_conforme' => (bool) $row['is_conforme'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ]
                );
            }

            // 18. Import prover_verifications
            $prv = $pdoLegacy->query('SELECT * FROM prover_verifications')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($prv as $row) {
                DB::table('prover_verifications')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'calibration_date' => $row['calibration_date'],
                        'reference_number' => $row['reference_number'] ?? null,
                        'reference_temperature' => $row['reference_temperature'] ?? 20.0,
                        'pressure_unit' => $row['pressure_unit'] ?? 'bar',
                        'remarks' => $row['remarks'] ?? null,
                        'prover_id' => $row['prover_id'] ? $instMap[(int) $row['prover_id']] : null,
                        'jauge_id' => $row['jauge_id'] ? $instMap[(int) $row['jauge_id']] : null,
                        'base_prover_volume' => $row['base_prover_volume'],
                        'max_run_volume' => $row['max_run_volume'],
                        'min_run_volume' => $row['min_run_volume'],
                        'repeatability_percent' => $row['repeatability_percent'],
                        'is_conforme' => (bool) $row['is_conforme'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ]
                );
            }

            // 19. Import prover_verification_runs
            $pvr = $pdoLegacy->query('SELECT * FROM prover_verification_runs')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($pvr as $row) {
                DB::table('prover_verification_runs')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'prover_verification_id' => $row['prover_verification_id'],
                        'run_number' => $row['run_number'],
                        'fill_number' => $row['fill_number'] ?? 1,
                        'scale_reading_mm' => $row['scale_reading_mm'],
                        'indicated_volume' => $row['indicated_volume'],
                        'gauge_temperature' => $row['gauge_temperature'],
                        'prover_temperature' => $row['prover_temperature'],
                        'shaft_temperature' => $row['shaft_temperature'],
                        'prover_pressure' => $row['prover_pressure'] ?? 0.0,
                        'c_tdw' => $row['c_tdw'],
                        'c_tsm' => $row['c_tsm'],
                        'c_tsp' => $row['c_tsp'],
                        'c_psp' => $row['c_psp'],
                        'c_plp' => $row['c_plp'],
                        'corrected_volume' => $row['corrected_volume'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ]
                );
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
}
