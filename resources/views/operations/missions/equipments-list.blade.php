@php
    /**
     * Isolate vehicles from technical equipment using partition.
     * Handles both Enum instance and string category.
     */
    [$vehicles, $tools] = $mission->equipments->partition(function ($equipment) {
        $cat = $equipment->category;
        return $cat === \App\Enums\EquipmentCategory::Vehicle
            || ($cat instanceof \BackedEnum ? $cat->value === 'vehicle' : strtolower((string) $cat) === 'vehicle');
    });

    // Sort tools having calibration certificates first
    $tools = $tools->sortByDesc('requires_calibration');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Equipment Mobilization Manifest') }} - {{ $mission->reference }}</title>
    <!-- Zero-FOUC Theme Script -->
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || 'system';
            const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.5;
            color: #111827;
            background: #f8fafc;
            margin: 0;
            padding: 20px;
        }

        html.dark body {
            background-color: #0b0f19;
            color: #111827;
        }

        .print-bar {
            background: #ffffff;
            color: #111827;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 210mm;
            margin-left: auto;
            margin-right: auto;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }

        html.dark .print-bar {
            background: #1f2937;
            border-color: #374151;
            color: #f3f4f6;
        }

        .print-bar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.15s;
        }

        html.dark .back-btn {
            background: #374151;
            border-color: #4b5563;
            color: #e2e8f0;
        }

        .back-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .print-btn {
            background: #059669;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.15s;
        }

        .print-btn:hover {
            background: #047857;
        }

        .print-page-sheet {
            background: #ffffff;
            color: #111827;
            max-width: 210mm;
            margin: 0 auto;
            padding: 12mm 15mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        html.dark .print-page-sheet {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.6);
        }

        .header {
            width: 100%;
            text-align: center;
            margin-bottom: 12px;
        }

        .header img {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .doc-title-container {
            text-align: center;
            margin: 15px 0 10px 0;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
        }

        .doc-title {
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }

        .doc-subtitle {
            font-size: 12px;
            color: #475569;
            margin-top: 3px;
            font-weight: 500;
        }

        .mission-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            margin: 12px 0 18px 0;
            font-size: 12px;
        }

        .info-item {
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .info-label {
            font-weight: 700;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            min-width: 100px;
        }

        .info-value {
            font-weight: 600;
            color: #0f172a;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 16px 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .section-badge {
            font-size: 11px;
            font-weight: 600;
            background: #e2e8f0;
            color: #334155;
            padding: 2px 8px;
            border-radius: 12px;
        }

        table.manifest-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            margin-bottom: 14px;
        }

        table.manifest-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            border: 1px solid #0f172a;
            text-align: left;
        }

        [dir="rtl"] table.manifest-table th {
            text-align: right;
        }

        table.manifest-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }

        table.manifest-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .text-center {
            text-align: center !important;
        }

        .text-end {
            text-align: right !important;
        }

        [dir="rtl"] .text-end {
            text-align: left !important;
        }

        .img-thumb {
            width: 32px;
            height: 32px;
            object-fit: contain;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            display: inline-block;
        }

        .cert-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10.5px;
            font-weight: 600;
            text-decoration: none;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .cert-ok {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .cert-missing {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .cert-na {
            color: #64748b;
            font-size: 10px;
        }

        .empty-state {
            padding: 25px;
            text-align: center;
            color: #64748b;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            margin: 10px 0;
            font-size: 12px;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 30px;
            padding-top: 10px;
            page-break-inside: avoid;
        }

        .signature-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
            background: #ffffff;
            min-height: 85px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sig-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 4px;
            margin-bottom: 40px;
        }

        .sig-sub {
            font-size: 9.5px;
            color: #94a3b8;
            font-style: italic;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0 !important;
                background: #ffffff !important;
                color: #111827 !important;
            }

            .print-page-sheet {
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }

            table.manifest-table th {
                background: #0f172a !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table.manifest-table tr:nth-child(even) td {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .cert-ok, .cert-missing, .mission-info-grid, .signature-card {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    <!-- Top Action Bar (Hidden when printing) -->
    <div class="no-print print-bar">
        <div class="print-bar-actions">
            <a href="{{ route('operations.missions.show', $mission->id) }}" class="back-btn">
                <span>&larr;</span> {{ __('Back to Mission') }}
            </a>
            <span style="font-weight: 700; font-size: 14px; font-family: monospace;">
                {{ $mission->reference }}
            </span>
        </div>
        <button class="print-btn" onclick="window.print()">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            {{ __('Print Equipment Manifest') }}
        </button>
    </div>

    <!-- Official Printable Sheet -->
    <div class="print-page-sheet">

        <!-- Company Official Header -->
        <div class="header">
            <img src="{{ asset('images/entete.png') }}" alt="GMTM Header Logo">
        </div>

       <!-- Date -->
       <div style="display: flex; justify-content: flex-end; align-items: center; font-size: 11px; color: #000000; margin-top: 5px;">
            <span><strong>{{ __('Date') }}:</strong> {{ now()->format('d/m/Y') }}</span>
        </div>

        <div class="doc-title-container">
            <h1 class="doc-title">{{ __('Equipment & Calibration Manifest') }}</h1>
            <div class="doc-subtitle">{{ __('Official Equipment Mobilization & Field Inspection Sheet') }}</div>
        </div>

        <!-- Mission Details Overview -->
        <div class="mission-info-grid">
            <div class="info-item">
                <span class="info-label">{{ __('Mission Ref') }}:</span>
                <span class="info-value" style="font-family: monospace; font-size: 13px;">{{ $mission->reference }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">{{ __('Location / Site') }}:</span>
                <span class="info-value">{{ $mission->site?->full_name ?? '—' }} ({{ $mission->site?->location ?? '—' }})</span>
            </div>             
        </div>

        <!-- Section 1: Measuring Instruments & Calibration Standards -->
        <div class="section-header">
            <h2 class="section-title">{{ __('1. Mobilized Equipment & Measuring Standards') }}</h2>
            <span class="section-badge">{{ $tools->count() }} {{ __('units') }}</span>
        </div>

        @if($tools->isEmpty())
            <div class="empty-state">
                <p><strong>{{ __('No technical equipment or calibrators assigned to this mission.') }}</strong></p>
            </div>
        @else
            <table class="manifest-table">
                <thead>
                    <tr>
                        <th style="width: 5%;" class="text-center">N°</th>                        
                        <th style="width: 32%;">{{ __('Equipment Designation') }}</th>
                        <th style="width: 18%;">{{ __('Serial Number') }}</th>
                        <th style="width: 15%;">{{ __('Category') }}</th>
                        <th style="width: 6%;" class="text-center">{{ __('Image') }}</th>
                        <th style="width: 10%;" class="text-center no-print">{{ __('Certificate') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tools as $tool)
                        <tr>
                            <td class="text-center" style="font-weight: 700;">{{ $loop->iteration }}</td>
                         
                            <td>
                                <div style="font-weight: 600;">{{ $tool->full_name }}</div>
                                @if($tool->designation && $tool->designation !== $tool->full_name)
                                    <div style="font-size: 10px; color: #64748b;">{{ $tool->designation }}</div>
                                @endif
                            </td>
                            <td style="font-family: monospace;">{{ $tool->serial_number ?: '---' }}</td>
                            <td style="color: #475569; font-size: 11px;">
                                {{ $tool->category?->label() ?? $tool->category?->value ?? $tool->category }}
                            </td>
                            <td class="text-center">
                                @if($tool->image_url)
                                    <img src="{{ $tool->image_url }}" class="img-thumb" alt="{{ $tool->full_name }}">
                                @elseif($tool->image_path)
                                    <img src="{{ asset('storage/' . $tool->image_path) }}" class="img-thumb" alt="{{ $tool->full_name }}">
                                @else
                                    <span style="color: #cbd5e1; font-size: 16px;">📦</span>
                                @endif
                            </td>
                            <td class="text-center no-print">
                                @if($tool->requires_calibration && ($tool->certificate_url || $tool->certificate_path))
                                    @php
                                        $certHref = $tool->certificate_url ?: asset('storage/' . $tool->certificate_path);
                                    @endphp
                                    <a href="{{ $certHref }}" target="_blank" class="cert-badge cert-ok no-print" title="{{ __('View Certificate') }}">
                                        📄 {{ __('Valid') }}
                                    </a>
                                    <span class="cert-badge cert-ok" style="display: none;" class="print-only">
                                        {{ __('Cert. OK') }}
                                    </span>
                                @elseif($tool->requires_calibration)
                                    <span class="cert-badge cert-missing">
                                        &times; {{ __('Missing') }}
                                    </span>
                                @else
                                    <span class="cert-na">{{ __('N/A') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

      

       

    </div>

</body>

</html>