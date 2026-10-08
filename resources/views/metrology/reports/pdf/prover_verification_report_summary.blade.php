<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Vérification Métrologique — Tube Étalon (Prover)</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm 10mm 18mm 10mm;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.45;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        /* Fixed Page Footer on Every Page */
        .page-footer {
            position: fixed;
            bottom: -15mm;
            left: 0;
            right: 0;
            height: 14mm;
            border-top: 1.5px solid #0f766e;
            padding-top: 4px;
            font-size: 9px;
            line-height: 1.35;
            color: #334155;
            text-align: justify;
        }

        .page-footer .footer-note-title {
            font-weight: bold;
            color: #0f766e;
        }

        .page-break {
            page-break-after: always;
        }

        .page-break:last-child {
            page-break-after: avoid;
        }

        /* Header */
        .report-header {
            width: 100%;
            border-bottom: 2px solid #0f766e;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-bottom: 0;
        }

        .header-table td {
            border: none;
            padding: 0 4px;
            vertical-align: middle;
        }

        .logo-img {
            height: 44px;
            max-width: 270px;
            display: inline-block;
            vertical-align: middle;
        }

        .brand-badge-box {
            display: inline-block;
            padding: 3px 0 3px 10px;
            border-left: 4px solid #0f766e;
        }

        .brand-title {
            font-size: 17px;
            font-weight: bold;
            color: #0f766e;
            letter-spacing: 1px;
            margin: 0;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .report-title-box {
            text-align: right;
        }

        .report-title-main {
            font-size: 12.5px;
            font-weight: bold;
            color: #0f766e;
            text-transform: uppercase;
            margin: 0 0 2px 0;
            white-space: nowrap;
            letter-spacing: -0.2px;
            line-height: 1.2;
        }

        .report-meta-sub {
            font-size: 9.5px;
            color: #64748b;
            line-height: 1.35;
        }

        /* 2-Column Section Layout */
        .grid-2col {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .grid-2col td {
            border: none;
            padding: 0 4px;
            vertical-align: top;
        }

        .grid-2col td:first-child {
            padding-left: 0;
            padding-right: 4px;
        }

        .grid-2col td:last-child {
            padding-left: 4px;
            padding-right: 0;
        }

        /* Info Card */
        .info-card {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            margin-bottom: 0;
        }

        .info-card-header {
            background-color: #0f766e;
            color: #ffffff;
            font-weight: bold;
            font-size: 9.5px;
            padding: 3.5px 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .info-table th, .info-table td {
            border: 1px solid #e2e8f0;
            padding: 3.5px 6px;
            font-size: 8.5px;
            line-height: 1.3;
        }

        .info-table th {
            background-color: #f1f5f9;
            color: #334155;
            text-align: left;
            width: 27%;
            font-weight: 600;
        }

        .info-table td {
            background-color: #ffffff;
            color: #0f172a;
        }

        /* Section Headings */
        .section-heading {
            font-size: 9.5px;
            font-weight: bold;
            color: #0f766e;
            border-bottom: 1.5px solid #0f766e;
            padding-bottom: 2px;
            margin: 4px 0 3px 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            margin-bottom: 4px;
        }

        .data-table th, .data-table td {
            border: 1px solid #94a3b8;
            padding: 2.5px 2px;
            text-align: center;
            font-size: 7.5px;
            line-height: 1.25;
        }

        .data-table th {
            background-color: #0f766e;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5px;
            letter-spacing: 0.2px;
            padding: 3.5px 2px;
        }

        .data-table thead tr.top-hdr th {
            background-color: #115e59;
            font-size: 8px;
            padding: 3px 2px;
        }

        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .data-table tr.row-nok {
            background-color: #fef2f2;
        }

        .data-table td.vol-base {
            font-weight: bold;
            color: #0f766e;
            background-color: #f0fdfa;
        }

        /* Badges & Status */
        .badge-ok {
            color: #15803d;
            font-weight: bold;
        }

        .badge-nok {
            color: #b91c1c;
            font-weight: bold;
        }

        .text-bold { font-weight: bold; }
        .text-center { text-align: center; }
        .text-muted { color: #64748b; }

        /* Section Divider Cover Page Enhanced */
        .section-header-banner {
            background-color: #0f766e;
            color: #ffffff;
            padding: 7px 10px;
            border-radius: 4px;
            margin-bottom: 6px;
        }

        .section-header-banner-title {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .section-header-banner-sub {
            font-size: 9px;
            opacity: 0.92;
            margin-top: 2px;
        }

        .procedure-card {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-bottom: 5px;
        }

        .procedure-card-header {
            background-color: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            padding: 4px 8px;
            font-size: 9.5px;
            font-weight: bold;
            color: #0f766e;
            text-transform: uppercase;
        }

        .procedure-card-body {
            padding: 6px 10px;
            font-size: 9px;
            line-height: 1.4;
            color: #1e293b;
        }

        .procedure-card-body ol, .procedure-card-body ul {
            margin: 3px 0 3px 16px;
            padding: 0;
        }

        .procedure-card-body li {
            margin-bottom: 2.5px;
        }

        .procedure-note {
            background-color: #eff6ff;
            border-left: 3px solid #3b82f6;
            padding: 3px 6px;
            margin-top: 4px;
            font-size: 8.5px;
            color: #1e40af;
            font-style: italic;
        }

        /* Summary Card */
        .summary-card {
            border: 1px solid #0f766e;
            background-color: #f8fafc;
            margin-top: 4px;
            margin-bottom: 4px;
            padding: 5px 8px;
            border-radius: 4px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            border: none;
            padding: 2.5px 6px;
            vertical-align: middle;
            font-size: 8.5px;
        }

        .summary-value {
            font-family: 'DejaVu Sans', monospace;
            font-weight: bold;
            font-size: 9.5px;
            color: #0f766e;
        }

        /* Signatures block */
        .signature-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .signature-grid td {
            border: none;
            padding: 0 10px;
            vertical-align: top;
            text-align: center;
            width: 33.33%;
        }
        .sig-line {
            border-top: 1px solid #94a3b8;
            margin-top: 14px;
            margin-bottom: 2px;
        }
        .sig-label {
            font-size: 7.5px;
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <!-- Fixed Footer on Every Page -->
    <footer class="page-footer">
        <span class="footer-note-title">Observation :</span> <em> Les témoins susmentionnés figurent dans la liste principale située en en-tête du document. </em>
        <br>
        <span class="footer-note-title">Note importante :</span> <em>Nous tenons à souligner que ces résultats sont fournis uniquement à titre informatif. La conformité effective de ces instruments de mesure aux normes et aux réglementations en vigueur ne peut être attestée que par un organisme réglementaire, tel que L'Office Algérien de Métrologie (OAM).</em>
    </footer>

@php
    $verification = $proverVerification;
    $prover = $proverVerification->prover;
    $jauge  = $proverVerification->jauge;
    $standard_gauge_specifications = $jauge?->standardGaugeSpecification;
    $calibration_certificate_number = $standard_gauge_specifications?->calibration_certificate_number;
    $jaugeCalibrationDate = $standard_gauge_specifications?->calibration_date
        ? \Carbon\Carbon::parse($standard_gauge_specifications->calibration_date)->format('d/m/Y')
        : null;
    $jaugeExpiryDate = $standard_gauge_specifications?->calibration_expiry_date
        ? \Carbon\Carbon::parse($standard_gauge_specifications->calibration_expiry_date)->format('d/m/Y')
        : null;

    $site     = $prover?->site ?? $jauge?->site;
    $customer = $site?->customer;

    
    $reportNumber = $verification->report?->reference_number ?: ('VERIF-PROVER-' . str_pad((string)$verification->report?->id, 3, '001', STR_PAD_LEFT));
   
    $customerName         = $customer?->company_name ?? ($customer?->short_name ?? 'Client');
    $siteName             = $site?->site_name ?? ($site?->name ?? ($site?->short_name ?? 'Site'));
    $calibrationDate      = $proverVerification->calibration_date
        ? \Carbon\Carbon::parse($proverVerification->calibration_date)
        : \Carbon\Carbon::parse($proverVerification->created_at);
    $calibrationDateFormatted = $calibrationDate->format('d/m/Y');

     $date = \Carbon\Carbon::now()->format('d/m/Y');

    $logoPath = public_path('images/Logo-black.png');
    $logoSrc = null;
    if (file_exists($logoPath)) {
        $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    } else {
        $logoSrc = asset('images/Logo-black.png');
    }

    $proverSpecType = $prover?->proverSpecification?->prover_type ?? 'bidirectional_pipe';
    $isSvp          = $proverSpecType === 'compact_svp'
        || $proverVerification->runs->contains(fn($r) => $r->shaft_temperature !== null);
    $overallOk = (bool)$proverVerification->is_conforme;

    $prevBpv  = $prover?->proverSpecification?->nominal_base_volume;
    $hasPrev  = $prevBpv !== null && (float) $prevBpv > 0;
    $deltaV   = $hasPrev ? round((float) $proverVerification->base_prover_volume - (float) $prevBpv, 5) : null;
    $driftPct = $hasPrev ? round(($deltaV / (float) $prevBpv) * 100, 4) : null;
@endphp

    {{-- =========================================================================
         PAGE 1 : PROTOCOLE DE VÉRIFICATION MÉTROLOGIQUE (SECTION IV : TUBES ÉTALONS)
         ========================================================================= --}}
    <div class="page-break">
        <!-- Header -->
         <div class="report-header">
            <table class="header-table">
                <tr>
                    <td style="width: 30%;">
                      
                            <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                       
                    </td>
                    <td class="report-title-box" style="width: 70%;">
                        <div class="report-title-main">Vérification Métrologique — Tube Étalon (Prover)</div>
                        <div class="report-meta-sub">
                            Rapport N° : <strong>{{ $reportNumber }}</strong> | Tag : <strong style="color:#0f766e;">{{ $prover?->tag_number ?? 'N/A' }}</strong> 
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Section Banner -->
        <div style="padding-top: 20%;">
        <div class="section-header-banner">
            <table style="width: 100%; border: none; margin: 0;">
                <tr>
                    <td style="width: 100%; border: none; padding: 0;">
                        <div style="font-size: 20px; font-weight: bold;" class="section-header-banner-title">Section IV : Vérification des Tubes Étalons &amp; Rapport de Prouving</div>
                        <div style="font-size: 10px; font-weight: bold;" class="section-header-banner-sub">Protocole d'Étalonnage &amp; Détermination du Volume de Base (BPV) — Méthode Water Draw (API MPMS 12.2.4 / OIML R 140)</div>
                    </td>
                    <td style="width: 10%; text-align: right; font-size: 10px; font-weight: bold; border: none; padding: 0;">
                        1 Tube Étalon
                    </td>
                </tr>
            </table>
        </div>

        <!-- 2 Columns Grid -->
       
        </div>
    </div>

    {{-- =========================================================================
         PAGE 2 : FICHE DE VÉRIFICATION MÉTROLOGIQUE DU PROVER
         ========================================================================= --}}
    <div class="page-break">
        <!-- Header -->
        <div class="report-header">
            <table class="header-table">
                <tr>
                    <td style="width: 30%;">
                       
                            <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                       
                    </td>
                    <td class="report-title-box" style="width: 70%;">
                        <div class="report-title-main">Vérification Métrologique — Tube Étalon (Prover)</div>
                        <div class="report-meta-sub">
                            Rapport N° : <strong>{{ $reportNumber }}</strong> | Tag : <strong style="color:#0f766e;">{{ $prover?->tag_number ?? 'N/A' }}</strong>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 1. General & Instrument Info (2 cols) -->
        <table class="grid-2col" style="margin-bottom: 5px;">
            <tr>
                <td style="width: 50%;">
                    <div class="info-card">
                        <div class="info-card-header">
                            @php
                                $proverTypeLabel = match($proverSpecType) {
                                    'compact_svp'          => 'Small Volume Prover (SVP)',
                                    'unidirectional_pipe'  => 'Unidirectional Pipe Prover',
                                    default                => 'Bidirectional Pipe Prover',
                                };
                            @endphp
                            1. Identification du Prover — {{ $proverTypeLabel }}
                        </div>
                        <table class="info-table">
                            <tr>
                                <th>Tag No.</th>
                                <td class="text-bold" style="color:#0f766e;">{{ $prover?->tag_number ?? 'N/A' }}</td>
                                <th>N° de Série</th>
                                <td class="text-bold">{{ $prover?->serial_number ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Marque / Type</th>
                                <td>{{ $prover?->type ?? 'Daniel' }} {{ $prover?->manufacturer ?? '' }}</td>
                                <th>Site / Unité</th>
                                <td>{{ $siteName }}</td>
                            </tr>
                            <tr>
                                <th>Diamètre Int. (ID)</th>
                                <td>{{ number_format($prover?->proverSpecification?->inner_diameter ?? 0, 2) }} mm</td>
                                <th>Épaisseur (Ép.)</th>
                                <td>{{ number_format($prover?->proverSpecification?->wall_thickness ?? 0, 2) }} mm</td>
                            </tr>
                            <tr>
                                <th>Coef. Dilat. Cubique (Gc)</th>
                                <td colspan="3">{{ sprintf('%.2e', $prover?->proverSpecification?->cubical_expansion_coef ?? 0) }} 1/°C
                                    @if($proverSpecType === 'compact_svp' || $prover?->proverSpecification?->area_expansion_coef)
                                        &nbsp;|&nbsp; Surf: {{ sprintf('%.2e', $prover?->proverSpecification?->area_expansion_coef ?? 0) }} — Lin: {{ sprintf('%.2e', $prover?->proverSpecification?->linear_expansion_coef ?? 0) }} 1/°C
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Méthode</th>
                                <td>Water Draw (API 12.2.4)</td>
                                <th>Temp. Réf. (Tb)</th>
                                <td>{{ number_format($proverVerification->reference_temperature, 1) }} °C </td>
                            </tr>
                            <tr>
                                
                                <th>Date Vérification Prover</th>
                                <td class="text-bold">{{ $calibrationDateFormatted }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="info-card">
                        <div class="info-card-header">2. Jauge Étalon (Standard Gauge) &amp; Conditions d'Essai</div>
                        <table class="info-table">
                            <tr>
                                <th>N° de Série Jauge</th>
                                <td class="text-bold" style="color:#0f766e;">{{ $jauge?->serial_number ?? '-' }}</td>
                                <th>Tag Jauge</th>
                                <td class="text-bold">{{ $jauge?->tag_number ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Capacité Nominale (BMV)</th>
                                <td><strong>{{ number_format($standard_gauge_specifications?->nominal_capacity_liters ?? 0, 4) }}</strong> L</td>
                                <th>Réf. Certificat</th>
                                <td class="text-bold">{{ $calibration_certificate_number ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Coef. Dilatation (Gcm)</th>
                                <td>{{ sprintf('%.2e', $standard_gauge_specifications?->cubical_expansion_coef_gcm ?? 0) }} 1/°C</td>
                                <th>Temp. Réf. Jauge</th>
                                <td>15 °C</td>
                            </tr>
                            <tr>
                                <th>Sensibilité Col (Scol)</th>
                                <td>{{ $standard_gauge_specifications?->neck_scale_sensitivity ? number_format($standard_gauge_specifications->neck_scale_sensitivity, 6) . ' L/mm' : '-' }}</td>
                                
                            </tr>
                            <tr>
                                <th>Date d'Étalonnage (Jauge)</th>
                                <td class="text-bold">{{ $jaugeCalibrationDate ?? '-' }}</td>
                                <th>Validité Certificat</th>
                                <td class="text-bold">{{ $jaugeExpiryDate ?? '-' }}</td>
                            </tr>
                           
                        </table>
                    </div>
                </td>
            </tr>
        </table> <br>

        <!-- 2. Test Results Section -->
        <div class="section-heading">3. RÉSULTATS DES ESSAIS (PASSES / RUNS) — DÉTERMINATION DU VOLUME DE BASE (BPV) :</div>
        <table class="data-table" style="margin-top: 3px; margin-bottom: 4px;">
            <thead>
                <tr class="top-hdr">
                    <th rowspan="2" style="width: 4%; vertical-align: middle; background-color: #115e59;">#</th>
                    <th colspan="4">LECTURES ET CORRECTIONS DE LA JAUGE ÉTALON</th>
                    <th colspan="{{ $isSvp ? 6 : 5 }}">LECTURES ET CORRECTIONS DU TUBE ÉTALON</th>
                    <th rowspan="2" style="width: {{ $isSvp ? '11%' : '13%' }}; vertical-align: middle; background-color: #115e59;">
                        VOLUME DE BASE (Vb) (L)
                    </th>
                </tr>
                <tr>
                    <th style="width: 9%;">Vol. Jauge (L)</th>
                    <th style="width: 8%;">Temp. Jauge (°C)</th>
                    <th style="width: 8%;">CTSm</th>
                    <th style="width: 8%;">CTDW</th>
                    <th style="width: {{ $isSvp ? '8%' : '10%' }};">Temp. Prover (°C)</th>
                    @if($isSvp)
                        <th style="width: 8%;">Temp. Shaft (°C)</th>
                    @endif
                    <th style="width: {{ $isSvp ? '8%' : '9%' }};">Pression ({{ $proverVerification->pressure_unit ?? 'bar' }})</th>
                    <th style="width: 8%;">CTSp</th>
                    <th style="width: 8%;">CPSp</th>
                    <th style="width: {{ $isSvp ? '8%' : '10%' }};">CPLp</th>
                </tr>
            </thead>
            <tbody>
                @foreach($proverVerification->runs as $runIndex => $run)
                    <tr>
                        <td class="text-bold">{{ $runIndex + 1 }}</td>
                        <td>
                            {{ number_format($run->indicated_volume, 3) }}
                            @if($run->scale_reading_mm !== null)
                                <br><span style="font-size: 6.5px; color: #64748b;">({{ number_format($run->scale_reading_mm, 1) }} mm)</span>
                            @endif
                        </td>
                        <td>{{ number_format($run->gauge_temperature, 2) }}</td>
                        <td>{{ sprintf('%.6f', $run->c_tsm) }}</td>
                        <td>{{ sprintf('%.6f', $run->c_tdw) }}</td>
                        <td>{{ number_format($run->prover_temperature, 2) }}</td>
                        @if($isSvp)
                            <td>{{ $run->shaft_temperature !== null ? number_format($run->shaft_temperature, 2) : '-' }}</td>
                        @endif
                        <td>{{ number_format($run->prover_pressure, 2) }}</td>
                        <td>{{ sprintf('%.6f', $run->c_tsp) }}</td>
                        <td>{{ sprintf('%.6f', $run->c_psp) }}</td>
                        <td>{{ sprintf('%.6f', $run->c_plp) }}</td>
                        <td class="vol-base">{{ number_format($run->corrected_volume, 5) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table> <br>

        <!-- 3. Summary Card -->
        <div class="summary-card" style="margin-top: 5px; margin-bottom: 5px;">
            <table class="summary-table">
                <tr>
                    <td style="width: 33%;">
                        <span class="text-muted">Volume de Base (Moyen) du Prover :</span>
                        <span class="summary-value">{{ number_format($proverVerification->base_prover_volume, 5) }} Litres</span>
                    </td>
                    <td style="width: 35%;">
                        <span class="text-muted">Répétabilité (r %) :</span>
                        <span class="summary-value">{{ number_format($proverVerification->repeatability_percent, 4) }} %</span>
                        <span style="font-size: 7.5px; color: #64748b;">(≤ 0.020%)</span>
                    </td>
                    
                </tr>
               
            </table>
        </div>

       

    </div>

</body>
</html>
