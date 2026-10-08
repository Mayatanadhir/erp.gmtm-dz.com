<?php

declare(strict_types=1);

use App\Models\CalibrationPoint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $mapRanges = function (int $certId, array $ranges): void {
                $pts = CalibrationPoint::where('calibration_certificate_id', $certId)
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($pts as $idx => $pt) {
                    $num = $idx + 1;
                    foreach ($ranges as [$start, $end, $specId]) {
                        if ($num >= $start && $num <= $end) {
                            if ($pt->equipment_specification_id !== $specId) {
                                $pt->update(['equipment_specification_id' => $specId]);
                            }
                            break;
                        }
                    }
                }
            };

            // Equipment 12: ADT221A (Specs: 18..21 Measurement, 22..25 Source)
            $eq12_88_ranges = [
                [1, 16, 18],   // Temp Mesure
                [17, 22, 19],  // Courant Mesure
                [23, 28, 20],  // Tension Mesure
                [29, 44, 21],  // Resistance Mesure
                [45, 60, 22],  // Temp Source
                [61, 66, 23],  // Courant Source
                [67, 72, 24],  // Tension Source
                [73, 88, 25],  // Resistance Source
            ];
            $mapRanges(169, $eq12_88_ranges);
            $mapRanges(171, $eq12_88_ranges);
            $mapRanges(221, $eq12_88_ranges);

            $eq12_89_ranges = [
                [1, 16, 18],
                [17, 22, 19],
                [23, 29, 20],
                [30, 45, 21],
                [46, 61, 22],
                [62, 67, 23],
                [68, 73, 24],
                [74, 89, 25],
            ];
            $mapRanges(172, $eq12_89_ranges);

            $eq12_64_ranges = [
                [1, 10, 18],
                [11, 16, 19],
                [17, 26, 20],
                [27, 32, 21],
                [33, 42, 22],
                [43, 48, 23],
                [49, 58, 24],
                [59, 64, 25],
            ];
            $mapRanges(173, $eq12_64_ranges);

            $eq12_77_ranges = [
                [1, 13, 18],
                [14, 22, 19],
                [23, 35, 20],
                [36, 45, 21],
                [46, 55, 22],
                [56, 61, 23],
                [62, 71, 24],
                [72, 77, 25],
            ];
            $mapRanges(174, $eq12_77_ranges);

            $eq12_63_ranges = [
                [1, 10, 18],
                [11, 16, 19],
                [17, 26, 20],
                [27, 32, 21],
                [33, 42, 22],
                [43, 48, 23],
                [49, 57, 24],
                [58, 63, 25],
            ];
            $mapRanges(175, $eq12_63_ranges);

            // Equipment 13: ADT223A (Specs: 28,26,29,27 Measurement, 32,30,33,31 Source)
            $eq13_88_ranges = [
                [1, 16, 28],   // Temp Mesure
                [17, 22, 26],  // Courant Mesure
                [23, 28, 29],  // Tension Mesure
                [29, 44, 27],  // Resistance Mesure
                [45, 60, 32],  // Temp Source
                [61, 66, 30],  // Courant Source
                [67, 72, 33],  // Tension Source
                [73, 88, 31],  // Resistance Source
            ];
            $mapRanges(170, $eq13_88_ranges);
            $mapRanges(177, $eq13_88_ranges);
            $mapRanges(178, $eq13_88_ranges);

            $eq13_64_ranges = [
                [1, 10, 28],
                [11, 16, 26],
                [17, 26, 29],
                [27, 32, 27],
                [33, 42, 32],
                [43, 48, 30],
                [49, 58, 33],
                [59, 64, 31],
            ];
            $mapRanges(179, $eq13_64_ranges);
            $mapRanges(181, $eq13_64_ranges);

            $eq13_219_ranges = [
                [1, 6, 26],
                [7, 22, 27],
                [23, 38, 28],
                [39, 44, 29],
                [45, 50, 30],
                [51, 66, 31],
                [67, 82, 32],
                [83, 88, 33],
            ];
            $mapRanges(219, $eq13_219_ranges);

            // Pressure Gauges: Reassign 0-nominal points back to Pressure specifications
            CalibrationPoint::whereIn('calibration_certificate_id', [163, 183, 200])
                ->where('equipment_specification_id', 3)
                ->where('nominal_value', 0)
                ->update(['equipment_specification_id' => 2]);

            CalibrationPoint::whereIn('calibration_certificate_id', [165, 184, 201])
                ->where('equipment_specification_id', 6)
                ->where('nominal_value', 0)
                ->update(['equipment_specification_id' => 4]);

            CalibrationPoint::whereIn('calibration_certificate_id', [162, 199])
                ->where('equipment_specification_id', 3)
                ->whereIn('id', [933, 934, 2492, 2493])
                ->update(['equipment_specification_id' => 1]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Historical data remapping - no-op reverse to preserve data integrity
    }
};
