<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rapport d'Étalonnage & Vérification - {{ $report->report_number }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm 10mm 20mm 10mm;
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
            bottom: -16mm;
            left: 0;
            right: 0;
            height: 15mm;
            border-top: 1.5px solid #0f766e;
            padding-top: 4px;
            font-size: 9.5px;
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
            padding-bottom: 5px;
            margin-bottom: 8px;
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
            height: 48px;
            max-width: 290px;
            display: inline-block;
            vertical-align: middle;
        }

        .brand-badge-box {
            display: inline-block;
            padding: 3px 0 3px 10px;
            border-left: 4px solid #0f766e;
        }

        .brand-title {
            font-size: 18px;
            font-weight: bold;
            color: #0f766e;
            letter-spacing: 1px;
            margin: 0;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 8.5px;
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
            font-size: 13px;
            font-weight: bold;
            color: #0f766e;
            text-transform: uppercase;
            margin: 0 0 3px 0;
            white-space: nowrap;
            letter-spacing: -0.2px;
            line-height: 1.2;
        }

        .report-meta-sub {
            font-size: 10px;
            color: #64748b;
            line-height: 1.4;
        }

        /* 2-Column Section Layout */
        .grid-2col {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .grid-2col td {
            border: none;
            padding: 0 5px;
            vertical-align: top;
        }

        .grid-2col td:first-child {
            padding-left: 0;
            padding-right: 5px;
        }

        .grid-2col td:last-child {
            padding-left: 5px;
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
            font-size: 10px;
            padding: 4.5px 8px;
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
            padding: 5px 8px;
            font-size: 9.5px;
            line-height: 1.35;
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
            font-size: 10.5px;
            font-weight: bold;
            color: #0f766e;
            border-bottom: 1.5px solid #0f766e;
            padding-bottom: 3px;
            margin: 6px 0 5px 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 6px;
        }

        .data-table th, .data-table td {
            border: 1px solid #94a3b8;
            padding: 6px 5px;
            text-align: center;
            font-size: 9.5px;
            line-height: 1.3;
        }

        .data-table th {
            background-color: #0f766e;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.3px;
            padding: 7px 5px;
        }

        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .data-table tr.row-nok {
            background-color: #fef2f2;
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
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .section-header-banner-title {
            font-size: 13.5px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .section-header-banner-sub {
            font-size: 9.5px;
            opacity: 0.92;
            margin-top: 2px;
        }

        .procedure-card {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-bottom: 6px;
        }

        .procedure-card-header {
            background-color: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: bold;
            color: #0f766e;
            text-transform: uppercase;
        }

        .procedure-card-body {
            padding: 6px 10px;
            font-size: 9.5px;
            line-height: 1.42;
            color: #1e293b;
        }

        .procedure-card-body ol, .procedure-card-body ul {
            margin: 3px 0 3px 18px;
            padding: 0;
        }

        .procedure-card-body li {
            margin-bottom: 3px;
        }

        .procedure-note {
            background-color: #eff6ff;
            border-left: 3px solid #3b82f6;
            padding: 3px 6px;
            margin-top: 4px;
            font-size: 9px;
            color: #1e40af;
            font-style: italic;
        }

        /* ── Courbe d'Erreur Métrologique (inline SVG) ── */
        .curve-infobar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            background: #f0fdf4;
            border: 1px solid #0f766e;
            border-radius: 3px;
        }
        .curve-infobar td {
            padding: 5px 10px;
            font-size: 9.5px;
            font-weight: bold;
            color: #0f766e;
            border: none;
        }
        .curve-infobar .curve-infobar-badge {
            text-align: right;
            font-size: 10px;
            font-weight: bold;
        }
        .curve-infobar .ok  { color: #15803d; }
        .curve-infobar .nok { color: #b91c1c; }
        .curve-svg-box {
            width: 100%;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            padding: 2px;
        }
        .curve-svg-box svg {
            display: block;
            width: 100%;
            height: auto;
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
    $logoPath = public_path('images/Logo-black.png');
    $logoSrc = null;
    if (file_exists($logoPath)) {
        $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    } else {
        $logoSrc = asset('images/Logo-black.png');
    }

    $totalVerifications = $report->transmitterVerifications->count() 
        + $report->probeVerifications->count() 
        + $report->flowComputerVerifications->count()
        ;
@endphp

@if($totalVerifications === 0)
    <div class="page-break">
        <div class="report-header">
            <table class="header-table">
                <tr>
                    <td style="width: 50%;">
                       
                            <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                      
                    </td>
                    <td class="report-title-box" style="width: 50%;">
                        <div class="report-title-main">Rapport de Vérification Métrologique</div>
                        <div class="report-meta-sub">N° : <strong>{{ $report->report_number }}</strong> | Date : {{ date('d/m/Y') }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="info-card" style="margin-top: 20px;">
            <div class="info-card-header">Informations Générales de la Mission</div>
            <table class="info-table">
                <tr>
                    <th>N° de Rapport</th>
                    <td class="text-bold">{{ $report->report_number }}</td>
                    <th>Mission Associée</th>
                    <td>{{ $report->mission->reference ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Client / Compagnie</th>
                    <td>{{ $report->mission?->site?->customer?->company_name ?? ($report->mission?->site?->customer?->short_name ?? 'N/A') }}</td>
                    <th>Site / Installation</th>
                    <td>{{ $report->mission?->site?->full_name ?? ($report->mission?->site?->short_name ?? ($report->mission?->site?->site_name ?? 'N/A')) }}</td>
                </tr>
                <tr>
                    <th>Statut du Rapport</th>
                    <td colspan="3">{{ $report->status_label }}</td>
                </tr>
            </table>
        </div>

        <div style="text-align: center; padding: 35px; color: #64748b; font-size: 11px; border: 1px dashed #cbd5e1; margin-top: 20px; line-height: 1.6;">
            <p><strong>Aucune donnée d'étalonnage enregistrée pour le moment.</strong></p>
            <p>Veuillez saisir les points de mesure des instruments associés pour générer les certificats de vérification.</p>
        </div>
    </div>
@endif

{{-- =========================================================================
     1. أجهزة الإرسال (Transmetteurs de Pression / Température) - Landscape Groupés
     ========================================================================= --}}
@php
    $sortOrder = [
        'Temperature___Standard' => 1,
        'Pressure___Relative'     => 2,
        'Pressure___Absolute'     => 3,
        'Pressure___Differential' => 4,
    ];

    $groupedTransmitters = $report->transmitterVerifications->groupBy(function($verif) {
        $inst = $verif->instrument;
        $specs = $inst?->specifications?->first();
        $grandeur = $specs?->grandeur?->name ?? 'Pression';
        $symbol = $specs?->grandeur?->symbol ?? 'bar';
        
        $rawPv = $inst?->process_variable;
        $pv = null;
        if ($rawPv instanceof \BackedEnum) {
            $pv = ucfirst(strtolower((string) $rawPv->value));
        } elseif ($rawPv instanceof \UnitEnum) {
            $pv = $rawPv->name;
        } elseif (is_string($rawPv) && !blank($rawPv)) {
            $pv = ucfirst(strtolower($rawPv));
        }

        if (!$pv) {
            $pv = (stripos($grandeur, 'Temp') !== false || in_array(strtolower($symbol), ['°c', 'c', 'k'])) ? 'Temperature' : 'Pressure';
        }

        if ($pv === 'Temperature') {
            return 'Temperature___Standard';
        }
        
        $rawMt = $inst?->measurement_type;
        $mt = $rawMt instanceof \BackedEnum ? (string) $rawMt->value : (string) ($rawMt ?? '');
        if ($pv === 'Pressure') {
            $pressureType = in_array($mt, ['Absolute', 'Differential']) ? $mt : 'Relative';
            return 'Pressure___' . $pressureType;
        }
        
        return $pv . '___' . ($mt ?: 'Standard');
    })->sortBy(function ($group, $key) use ($sortOrder) {
        return $sortOrder[$key] ?? 99;
    });
@endphp

@foreach($groupedTransmitters as $groupKey => $verificationsInGroup)
    @php
        list($groupPV, $groupMT) = explode('___', $groupKey, 2);
        
        if ($groupPV === 'Pressure') {
            if ($groupMT === 'Absolute') {
                $sectionTitle = 'Section I : Transmetteurs de Pression Absolue';
                $sectionSub = 'Protocole de Vérification Métrologique — Pression Absolue (Boucle 4-20 mA)';
                $categoryName = 'Transmetteurs de Pression Absolue';
                $sheetTitle = 'Vérification — Transmetteur de Pression Absolue';
                $typeBadge = 'Pression Absolue';
                
              
            } elseif ($groupMT === 'Differential') {
                $sectionTitle = 'Section I : Transmetteurs de Pression Différentielle (ΔP)';
                $sectionSub = 'Protocole de Vérification Métrologique — Pression Différentielle (Boucle 4-20 mA)';
                $categoryName = 'Transmetteurs de Pression Différentielle (ΔP)';
                $sheetTitle = 'Vérification — Transmetteur de Pression Différentielle (ΔP)';
                $typeBadge = 'Pression Différentielle (ΔP)';
                
               
            } else {
                $sectionTitle = 'Section I : Transmetteurs de Pression Relative (Gauge)';
                $sectionSub = 'Protocole de Vérification Métrologique — Pression Relative / Manométrique (Boucle 4-20 mA)';
                $categoryName = 'Transmetteurs de Pression Relative';
                $sheetTitle = 'Vérification — Transmetteur de Pression Relative';
                $typeBadge = 'Pression Relative';
                
               
            }
        } elseif ($groupPV === 'Temperature') {
            $sectionTitle = 'Section I : Transmetteurs de Température';
            $sectionSub = 'Protocole de Vérification & Procédures Métrologiques Température (Boucle 4-20 mA)';
            $categoryName = 'Transmetteurs de Température';
            $sheetTitle = 'Vérification — Transmetteur de Température';
            $typeBadge = '';
            
           
        } else {
            $sectionTitle = 'Section I : Transmetteurs de ' . $groupPV;
            $sectionSub = 'Protocole de Vérification & Procédures Métrologiques (Boucle 4-20 mA)';
            $categoryName = 'Transmetteurs de ' . $groupPV;
            $sheetTitle = 'Vérification — Transmetteur de ' . $groupPV;
            $typeBadge = $groupMT;
            
            
        }

        $rawGroupFluid = $verificationsInGroup->first()?->instrument?->fluid_type;
        $groupFluidVal = $rawGroupFluid instanceof \BackedEnum ? ucfirst(strtolower((string) $rawGroupFluid->value)) : (string) ($rawGroupFluid ?? 'Liquid');
        $groupOverviewFluidDisplay = in_array($groupFluidVal, ['Gas', 'Gaz']) ? 'Gaz' : 'Liquide';
    @endphp

    <!-- Procedure Page for Group: {{ $groupKey }} -->
    <div class="page-break">
        <!-- Header -->
        <div class="report-header">
            <table class="header-table">
                <tr>
                    <td style="width: 30%;">
                       
                            <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                        
                    </td>
                    <td class="report-title-box" style="width: 70%;">
                        <div class="report-title-main">Rapport de Vérification Métrologique</div>
                        <div class="report-meta-sub">
                            Rapport N° : <strong>{{ $report->report_number }}</strong> | Date : {{ date('d/m/Y') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Banner -->
         <div style="padding-top: 20%;"   >
         <div class="section-header-banner">

           
                <table style="width: 100%; border-collapse: collapse; ">
                    <tr>
                        <td style="width: 100%;">
                            <div style="font-size: 23px; font-weight: bold;" class="section-header-banner-title">{{ $sectionTitle }}</div>
                            <div style="font-size: 10px; font-weight: bold;" class="section-header-banner-sub">{{ $sectionSub }}</div>
                        </td>
                        <td style="width: 10%; text-align: right; font-size: 10px; font-weight: bold;">
                            {{ $verificationsInGroup->count() }} Instrument(s)
                        </td>
                    </tr>
                </table>
                </div>
            </div>
        </div>

        <!-- 2 Columns Grid -->
      
    </div>

    <!-- Individual Verification Sheets in this Group -->
    @foreach($verificationsInGroup as $vIndex => $verification)
        @php
            $instrument = $verification->instrument;
            $specs = $instrument?->specifications?->first();
            $grandeur = $specs?->grandeur?->name ?? $groupPV;
            $symbol = $specs?->grandeur?->symbol ?? ($groupPV === 'Temperature' ? '°C' : 'bar');
            $rangeMin = (float)($specs->range_min ?? 0);
            $rangeMax = (float)($specs->range_max ?? 100);
            $span = (float)($rangeMax - $rangeMin);
            $isTemp = ($groupPV === 'Temperature') || (stripos($grandeur, 'Temp') !== false) || in_array(strtolower($symbol), ['°c', 'c', 'k']);
            $isAbsolute = (!$isTemp) && ($groupMT === 'Absolute');

            $rawInstFluid = $instrument?->fluid_type;
            $instFluidVal = $rawInstFluid instanceof \BackedEnum ? ucfirst(strtolower((string) $rawInstFluid->value)) : (string) ($rawInstFluid ?? 'Liquid');
            $instFluidNormalized = in_array($instFluidVal, ['Gas', 'Gaz']) ? 'Gas' : 'Liquid';

            $oamService = app(\App\Services\OamMetrologyService::class);
            $evalSample = $oamService->evaluateTransmitter(
                $span, $rangeMin, $rangeMin, 4.0, $rangeMin,
                $instFluidNormalized, $instrument->technology, $grandeur,
                $groupMT, 0.0
            );
            $isRelative = ($evalSample['error_type'] ?? 'Relative') === 'Relative';
            $errorHeader = $isRelative ? 'Erreur (%)' : 'Erreur (' . $symbol . ')';
            $emtHeader = $isRelative ? 'EMT (%)' : 'EMT (' . $symbol . ')';
            $emtUnit = $isRelative ? '%' : $symbol;
        @endphp
        <div class="page-break">
            <!-- Header -->
            <div class="report-header">
                <table class="header-table">
                    <tr>
                        <td style="width: 45%;">
                          
                                <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                           
                        </td>
                        <td class="report-title-box" style="width: 55%;">
                            <div class="report-title-main">{{ $sheetTitle }}</div>
                            <div class="report-meta-sub">
                                Rapport N° : <strong>{{ $report->report_number }}</strong> | Tag : <strong style="color:#0f766e;">{{ $instrument->tag_number }}</strong> 
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 2 Columns: Instrument Info + Calibrators -->
            <table class="grid-2col">
                <tr>
                    <td style="width: 50%;">
                        <div class="info-card">
                            <div class="info-card-header">1. Informations de l'Instrument</div>
                            <table class="info-table">
                                <tr>
                                    <th>Tag & N° Série</th>
                                    <td class="text-bold">{{ $instrument->tag_number }} (S/N: {{ $instrument->serial_number ?? 'N/A' }})</td>
                                </tr>
                                <tr>
                                    <th>Fluide </th>
                                    <td>{{ in_array($instFluidVal, ['Gas', 'Gaz']) ? 'Gaz' : 'Liquide' }}</td>
                                </tr>
                                <tr>
                                    <th>Échelle & Type</th>
                                    <td class="text-bold">{{ $rangeMin }} à {{ $rangeMax }} {{ $symbol }} (Span: {{ $span }} {{ $symbol }}){{ ($groupPV === 'Pressure' && !empty($typeBadge)) ? ' [' . $typeBadge . ']' : '' }}</td>
                                </tr>
                                <tr>
                                        <th>Date Vérification</th>
                                        <td class="text-bold">{{ \Carbon\Carbon::parse($verification->verification_date)->format('d/m/Y') }}</td>
                                    </tr>
                            </table>
                        </div>
                    </td>
                    <td style="width: 50%;">
                        <div class="info-card">
                            <div class="info-card-header">2. Étalons de Référence Utilisés</div>
                            <table class="info-table">
                                @php
                                    $uniqueCalibrators = $verification->calibrators->unique('id')->values();
                                @endphp
                                @forelse($uniqueCalibrators as $cIdx => $calibrator)
                                <tr>
                                    <th>{{ $uniqueCalibrators->count() > 1 ? 'Étalon ' . ($cIdx + 1) : 'Étalon' }}</th>
                                    <td>
                                        <strong>{{ $calibrator->full_name ?? ($calibrator->designation ?? $calibrator->internal_code) }}</strong><br>
                                        <span class="text-muted">S/N: {{ $calibrator->serial_number ?? $calibrator->internal_code }} | Cert: {{ $calibrator->latestCertificate?->reference ?? ($calibrator->calibrationCertificates?->last()?->reference ?? 'Agréé') }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <th>Étalons</th>
                                    <td class="text-muted">Étalons standards GMTM agréés.</td>
                                </tr>
                                @endforelse
                            </table>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- 3. Metrology Table (Full Width) -->
            <div class="section-heading">3. Résultats des Mesures & Conformité Métrologique</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 11%;">Appliqué (%)</th>
                        <th style="width: 15%;">Valeur Réf. ({{ $symbol }})</th>
                        <th style="width: 15%;">Signal Mesuré (mA)</th>
                        <th style="width: 16%;">Valeur Calculée ({{ $symbol }})</th>
                        <th style="width: 12%;">{{ $errorHeader }}</th>
                        <th style="width: 14%;">{{ $emtHeader }}</th>
                        <th style="width: 12%;">Décision</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($verification->points->sortBy('step_order') as $point)
                    @php
                        $mA = (float)($point->corrected_signal ?? $point->measured_signal ?? 4.0);
                        $refVal = (float)($point->corrected_reference_value ?? $point->reference_value);
                        $calcVal = ($point->indicated_value !== null && $point->indicated_value !== '')
                            ? (float)$point->indicated_value
                            : ($span !== 0.0 ? ($rangeMin + (($mA - 4.0) / 16.0) * $span) : $refVal);
                        $absErr = $point->absolute_error ?? ($calcVal - $refVal);
                        $relErr = ($span !== 0.0) ? (($absErr / $span) * 100.0) : 0.0;
                        $displayError = $isRelative
                            ? (($relErr >= 0 ? '+' : '') . number_format($relErr, 4) . ' %')
                            : (($absErr >= 0 ? '+' : '') . number_format($absErr, 4) . ' ' . $symbol);
                        $displayEmt = '±' . number_format($point->emt_limit, 4) . ' ' . $emtUnit;
                    @endphp
                    <tr class="{{ !$point->is_conforme ? 'row-nok' : '' }}">
                        <td>{{ $point->step_order }}</td>
                        <td class="text-bold">{{ number_format($point->applied_percentage, 1) }} %</td>
                        <td>
                            {{ number_format($point->reference_value, 4) }}
                            @if(isset($point->calibrator_1_correction) && abs((float)$point->calibrator_1_correction) > 0.00001)
                                <div style="font-size: 7.5px; color: #475569;">Corr: {{ number_format($point->corrected_reference_value, 4) }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $point->measured_signal !== null ? number_format($point->measured_signal, 4) : '---' }}
                            @if(isset($point->calibrator_2_correction) && abs((float)$point->calibrator_2_correction) > 0.00001)
                                <div style="font-size: 7.5px; color: #475569;">Corr: {{ number_format($point->corrected_signal, 4) }}</div>
                            @endif
                        </td>
                        <td>{{ number_format($calcVal, 4) }}</td>
                        <td class="text-bold">{{ $displayError }}</td>
                        <td>{{ $displayEmt }}</td>
                        <td>
                            @if($point->is_conforme)
                                <span class="badge-ok">VALID</span>
                            @else
                                <span class="badge-nok">INVALID</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ── Courbe d'Erreur — page indépendante ── --}}
        @php
            $tChartService = app(\App\Services\SvgChartService::class);
            $tCurvePoints  = $verification->points->sortBy('step_order');
            $tEmt          = (float)($tCurvePoints->first()?->emt_limit ?? 0);
            $tMaxErr       = $tCurvePoints->max(fn($p) => abs((float)($p->absolute_error ?? 0.0))) ?? 0.0;
            $tConformity   = ($tMaxErr <= abs($tEmt)) ? 'Conforme' : 'Non Conforme';
            $tCurveImg     = $tChartService->generateErrorCurvePng($tCurvePoints, $tEmt, $rangeMin, $rangeMax, 900, 380);
        @endphp
        <div class="page-break">
            <!-- Header -->
            <div class="report-header">
                <table class="header-table">
                    <tr>
                        <td style="width: 45%;">
                           
                                <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                           
                        </td>
                        <td class="report-title-box" style="width: 55%;">
                            <div class="report-title-main">Courbe d'Erreur Métrologique</div>
                            <div class="report-meta-sub">
                                Rapport N° : <strong>{{ $report->report_number }}</strong>
                                | Tag : <strong style="color:#0f766e;">{{ $instrument->tag_number }}</strong>
                                | Date : {{ \Carbon\Carbon::parse($verification->verification_date)->format('d/m/Y') }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <!-- Info Bar -->
            <table class="curve-infobar">
                <tr>
                    <td>
                        Instrument : <strong>{{ $instrument->tag_number }}</strong>
                        &nbsp;|&nbsp; Étendue : <strong>{{ $rangeMin }} → {{ $rangeMax }} {{ $symbol }}</strong>
                        &nbsp;|&nbsp; EMT : <strong>±{{ number_format(abs($tEmt), 4) }} {{ $emtUnit }}</strong>
                    </td>
                    <td class="curve-infobar-badge">
                        D&#233;cision :
                        @if($tConformity === 'Conforme')
                            <span class="ok">&#10003; VALID</span>
                        @else
                            <span class="nok">&#10007; INVALID</span>
                        @endif
                    </td>
                </tr>
            </table>
            <!-- SVG Curve -->
            <div class="curve-svg-box">
                <img src="{{ $tCurveImg }}" style="width:100%; display:block;" alt="Courbe d'erreur" />
            </div>
        </div>
    @endforeach
@endforeach

{{-- =========================================================================
     2. مسابير المقاومة الحرارية (Sondes PT100) - Landscape
     ========================================================================= --}}
@if($report->probeVerifications->isNotEmpty())
    <div class="page-break">
        <!-- Header -->
        <div class="report-header">
            <table class="header-table">
                <tr>
                    <td style="width: 30%;">
                      
                            <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                      
                    </td>
                    <td class="report-title-box" style="width: 70%;">
                        <div class="report-title-main">Rapport de Vérification Métrologique</div>
                        <div class="report-meta-sub">
                            Rapport N° : <strong>{{ $report->report_number }}</strong> | Date : {{ date('d/m/Y') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Banner -->
        <div class="section-header-banner">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 75%;">
                        <div class="section-header-banner-title">Section II : Sondes de Température (PT100)</div>
                        <div class="section-header-banner-sub">Protocole de Vérification Résistance & Température (Norme IEC 60751)</div>
                    </td>
                    <td style="width: 25%; text-align: right; font-size: 10px; font-weight: bold;">
                        {{ $report->probeVerifications->count() }} Sonde(s)
                    </td>
                </tr>
            </table>
        </div>

        <!-- 2 Columns Grid -->
        <table class="grid-2col">
           
        </table>
    </div>
@endif

@foreach($report->probeVerifications as $pIndex => $verification)
    @php
        $instrument = $verification->instrument;
        $specs = $instrument?->specifications?->first();
        $rangeMin = $specs->range_min ?? -50;
        $rangeMax = $specs->range_max ?? 200;
    @endphp
    <div class="page-break">
        <!-- Header -->
        <div class="report-header">
            <table class="header-table">
                <tr>
                    <td style="width: 30%;">
                      
                            <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                    
                    </td>
                    <td class="report-title-box" style="width: 70%;">
                        <div class="report-title-main">Vérification — Sonde PT100</div>
                        <div class="report-meta-sub">
                            Rapport N° : <strong>{{ $report->report_number }}</strong> | Tag : <strong style="color:#0f766e;">{{ $instrument->tag_number }}</strong> | Date : {{ \Carbon\Carbon::parse($verification->verification_date)->format('d/m/Y') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 2 Columns: Instrument Info + Calibrators -->
        <table class="grid-2col">
            <tr>
                <td style="width: 50%;">
                    <div class="info-card">
                        <div class="info-card-header">1. Informations de la Sonde de Température</div>
                        <table class="info-table">
                            <tr>
                                <th>Tag & N° Série</th>
                                <td class="text-bold">{{ $instrument->tag_number }} (S/N: {{ $instrument->serial_number ?? 'N/A' }})</td>
                            </tr>
                            <tr>
                                <th>Technologie</th>
                                <td>Sonde PT100</td>
                            </tr>
                            <tr>
                                <th>Plage d'Utilisation</th>
                                <td class="text-bold">{{ $rangeMin }} °C à {{ $rangeMax }} °C</td>
                            </tr>
                            <tr>
                                <th>Client & Site</th>
                                <td>{{ $report->mission->site->customer->company_name ?? 'Client' }} — {{ $report->mission->site->site_name ?? 'Site' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="info-card">
                        <div class="info-card-header">2. Étalons de Référence Utilisés</div>
                        <table class="info-table">
                            @php
                                $uniqueCalibrators = $verification->calibrators->unique('id')->values();
                            @endphp
                            @forelse($uniqueCalibrators as $cIdx => $calibrator)
                            <tr>
                                <th>{{ $uniqueCalibrators->count() > 1 ? 'Étalon ' . ($cIdx + 1) : 'Étalon' }}</th>
                                <td>
                                    <strong>{{ $calibrator->full_name ?? ($calibrator->designation ?? $calibrator->internal_code) }}</strong><br>
                                    <span class="text-muted">S/N: {{ $calibrator->serial_number ?? $calibrator->internal_code }} | Cert: {{ $calibrator->latestCertificate?->reference ?? ($calibrator->calibrationCertificates?->last()?->reference ?? 'Agréé') }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <th>Étalons</th>
                                <td class="text-muted">Étalons standards GMTM agréés.</td>
                            </tr>
                            @endforelse
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- 3. Metrology Table (Full Width) -->
        <div class="section-heading">3. Résultats des Mesures</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th style="width: 16%;">Temp. Étalon (°C)</th>
                    <th style="width: 16%;">R. Mesurée (&Omega;)</th>
                    <th style="width: 16%;">Temp. Calculée (°C)</th>
                    <th style="width: 15%;">Erreur (°C)</th>
                    <th style="width: 16%;">EMT Classe A (±°C)</th>
                    <th style="width: 15%;">Décision</th>
                </tr>
            </thead>
            <tbody>
                @foreach($verification->points->sortBy('step_order') as $point)
                <tr class="{{ !$point->is_conforme ? 'row-nok' : '' }}">
                    <td>{{ $point->step_order }}</td>
                    <td class="text-bold">
                        {{ number_format($point->reference_temperature, 3) }}
                        @if(isset($point->calibrator_1_correction) && abs((float)$point->calibrator_1_correction) > 0.00001)
                            <div style="font-size: 7.5px; color: #475569;">Corr: {{ number_format($point->corrected_reference_temperature ?? ($point->reference_temperature + $point->calibrator_1_correction), 3) }}</div>
                        @endif
                    </td>
                    <td>
                        {{ number_format($point->measured_resistance, 4) }}
                        @if(isset($point->calibrator_2_correction) && abs((float)$point->calibrator_2_correction) > 0.00001)
                            <div style="font-size: 7.5px; color: #475569;">Corr: {{ number_format($point->corrected_measured_resistance ?? ($point->measured_resistance + $point->calibrator_2_correction), 4) }}</div>
                        @endif
                    </td>
                    <td class="text-bold" style="color:#1d4ed8;">{{ number_format($point->indicated_temperature, 3) }}</td>
                    <td class="text-bold">{{ ($point->absolute_error >= 0 ? '+' : '') . number_format($point->absolute_error, 4) }}</td>
                    <td>±{{ number_format($point->emt_limit, 4) }}</td>
                    <td>
                        @if($point->is_conforme)
                            <span class="badge-ok">VALID</span>
                        @else
                            <span class="badge-nok">INVALID</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Courbe d'Erreur Sonde PT100 — page indépendante ── --}}
    @php
        $pChartService = app(\App\Services\SvgChartService::class);
        $pCurvePoints  = $verification->points->sortBy('step_order');
        $pEmt          = (float)($pCurvePoints->max('emt_limit') ?? 0.15);
        $pRangeMin     = (float)($specs->range_min ?? -50);
        $pRangeMax     = (float)($specs->range_max ?? 200);
        $pMaxErr       = $pCurvePoints->max(fn($p) => abs((float)($p->absolute_error ?? 0.0))) ?? 0.0;
        $pConformity   = ($pMaxErr <= abs($pEmt)) ? 'Conforme' : 'Non Conforme';
        $pCurveImg     = $pChartService->generateErrorCurvePng($pCurvePoints, $pEmt, $pRangeMin, $pRangeMax, 900, 380);
    @endphp
    <div class="page-break">
        <!-- Header -->
        <div class="report-header">
            <table class="header-table">
                <tr>
                    <td style="width: 45%;">
                      
                            <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                      
                    </td>
                    <td class="report-title-box" style="width: 55%;">
                        <div class="report-title-main">Courbe d'Erreur — Sonde PT100</div>
                        <div class="report-meta-sub">
                            Rapport N° : <strong>{{ $report->report_number }}</strong>
                            | Tag : <strong style="color:#0f766e;">{{ $instrument->tag_number }}</strong>
                            | Date : {{ \Carbon\Carbon::parse($verification->verification_date)->format('d/m/Y') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <!-- Info Bar -->
        <table class="curve-infobar">
            <tr>
                <td>
                    Sonde : <strong>{{ $instrument->tag_number }}</strong>
                    &nbsp;|&nbsp; Plage : <strong>{{ $pRangeMin }} °C → {{ $pRangeMax }} °C</strong>
                    &nbsp;|&nbsp; EMT max : <strong>±{{ number_format($pEmt, 4) }} °C</strong>
                </td>
                <td class="curve-infobar-badge">
                    Décision :
                    @if($pConformity === 'Conforme')
                        <span class="ok">&#10003; VALID</span>
                    @else
                        <span class="nok">&#10007; INVALID</span>
                    @endif
                </td>
            </tr>
        </table>
        <!-- SVG Curve -->
        <div class="curve-svg-box">
            <img src="{{ $pCurveImg }}" style="width:100%; display:block;" alt="Courbe d'erreur PT100" />
        </div>
    </div>
@endforeach

{{-- =========================================================================
     3. حاسبات التدفق ومحاكاة الإشارات (Flow Computers / ADC) - Landscape
     ========================================================================= --}}
@if($report->flowComputerVerifications->isNotEmpty())
    <div class="page-break">
        <!-- Header -->
        <div class="report-header">
            <table class="header-table">
                <tr>
                    <td style="width: 30%;">
                      
                            <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                      
                    </td>
                    <td class="report-title-box" style="width: 70%;">
                        <div class="report-title-main">Rapport de Vérification Métrologique</div>
                        <div class="report-meta-sub">
                            Rapport N° : <strong>{{ $report->report_number }}</strong> | Date : {{ date('d/m/Y') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Banner -->
        <div class="section-header-banner">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 75%;">
                        <div class="section-header-banner-title">Section III : (ADC) Calculateurs de Débit (Flow Computers)</div>
                        <div class="section-header-banner-sub">Protocole de Simulation & Vérification des Voies d'Entrée Analogiques (Conversion ADC 4-20 mA)</div>
                    </td>
                    <td style="width: 25%; text-align: right; font-size: 10px; font-weight: bold;">
                        {{ $report->flowComputerVerifications->count() }} ADC
                    </td>
                </tr>
            </table>
        </div>

        <!-- 2 Columns Grid -->
        <table class="grid-2col">
            <tr>
                <!-- Col 1: Principe & Raccordement -->
                <td style="width: 50%;">
                    <div class="procedure-card">
                        <div class="procedure-card-header">Informations Générales</div>
                        <div class="procedure-card-body">
                           
                            <table class="info-table" style="margin-top: 4px;">
                                <tr>
                                    <th>Catégorie</th>
                                    <td>Calculateurs de Débit / Unités de Traitement ADC</td>
                                </tr>
                                <tr>
                                    <th>Fluide</th>
                                    @php
                                        $rawFcFluid = $report->flowComputerVerifications->first()?->simulatedTransmitter?->fluid_type ?? ($report->flowComputerVerifications->first()?->instrument?->fluid_type ?? 'Liquid');
                                        $fcFluidVal = $rawFcFluid instanceof \BackedEnum ? ucfirst(strtolower((string) $rawFcFluid->value)) : (string) ($rawFcFluid ?? 'Liquid');
                                        $fcOverviewFluidDisplay = in_array($fcFluidVal, ['Gas', 'Gaz']) ? 'Gaz' : 'Liquide';
                                    @endphp
                                    <td>{{ $fcOverviewFluidDisplay }}</td>
                                </tr>
                                <tr>
                                    <th>Client / Site</th>
                                    <td>{{ $report->mission->site->customer->company_name ?? ($report->mission->site->customer->short_name ?? 'Client') }} — {{ $report->mission->site->site_name ?? 'Site' }}</td>
                                </tr>
                                <tr>
                                    <th>Référence</th>
                                    <td>Rapport N° {{ $report->report_number }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                   
                </td>

               
            </tr>
        </table>
    </div>
@endif

@foreach($report->flowComputerVerifications as $fcIndex => $verification)
    @php
        $instrument = $verification->instrument;
        $simTransmitter = $verification->simulatedTransmitter;
        $simSpecs = $simTransmitter?->specifications?->first();
        $simGrandeur = $simSpecs?->grandeur?->name ?? 'Mesure';
        $simSymbol = $simSpecs?->grandeur?->symbol ?? 'bar';
        $simMin = (float)($simSpecs->range_min ?? 0);
        $simMax = (float)($simSpecs->range_max ?? 100);
        $simSpan = (float)($simMax - $simMin);

        $oamService = app(\App\Services\OamMetrologyService::class);
        $rawSimFluid = $simTransmitter?->fluid_type ?? ($instrument?->fluid_type ?? 'Liquid');
        $simFluidVal = $rawSimFluid instanceof \BackedEnum ? ucfirst(strtolower((string) $rawSimFluid->value)) : (string) ($rawSimFluid ?? 'Liquid');
        $fluidDisplay = in_array($simFluidVal, ['Gas', 'Gaz']) ? 'Gaz' : 'Liquide';
        $simFluidNormalized = in_array($simFluidVal, ['Gas', 'Gaz']) ? 'Gas' : 'Liquid';
        $evalSampleADC = $oamService->evaluateADC($simSpan, $simMin, 4.0, 250.0, $simMin, $simFluidNormalized, $simGrandeur);
        $isRelativeADC = ($evalSampleADC['error_type'] ?? 'Relative') === 'Relative';
        $errorHeaderADC = $isRelativeADC ? 'Erreur (%)' : 'Erreur (' . $simSymbol . ')';
        $emtHeaderADC = $isRelativeADC ? 'EMT (%)' : 'EMT (' . $simSymbol . ')';
        $emtUnitADC = $isRelativeADC ? '%' : $simSymbol;
    @endphp
    <div class="page-break">
        <!-- Header -->
        <div class="report-header">
            <table class="header-table">
                <tr>
                    <td style="width: 30%;">
                      
                            <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                       
                    </td>
                    <td class="report-title-box" style="width: 70%;">
                        <div class="report-title-main">Vérification ADC </div>
                        <div class="report-meta-sub">
                            Rapport N° : <strong>{{ $report->report_number }}</strong> | Tag : <strong style="color:#0f766e;">{{ $instrument->tag_number }}</strong> | Voie : {{ $simTransmitter->tag_number ?? 'Ch1' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 2 Columns: Flow Computer Channel Info + Calibrators -->
        <table class="grid-2col">
            <tr>
                <td style="width: 50%;">
                    <div class="info-card">
                        <div class="info-card-header">1. Informations du Calculateur et de la Voie</div>
                        <table class="info-table">
                            <tr>
                                <th>Tag Calculateur</th>
                                <td class="text-bold">{{ $instrument->tag_number }} (S/N: {{ $instrument->serial_number ?? 'N/A' }})</td>
                            </tr>
                            <tr>
                                <th>Voie & Transmetteur</th>
                                <td>{{ $simTransmitter->tag_number ?? 'Voie' }} ({{ $simGrandeur }} )</td>
                            </tr>
                            <tr>
                                <th>Échelle Associée & Shunt</th>
                                <td class="text-bold">{{ $simMin }} à {{ $simMax }} {{ $simSymbol }} (Span: {{ $simSpan }} {{ $simSymbol }})</td>
                            </tr>
                            <tr>
                                <th>Date de Vérification</th>
                                <td>{{ \Carbon\Carbon::parse($verification->verification_date)->format('d/m/Y') }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="info-card">
                        <div class="info-card-header">2. Étalon / Générateur de Signal</div>
                        <table class="info-table">
                            @php
                                $uniqueCalibrators = $verification->calibrators->unique('id')->values();
                            @endphp
                            @forelse($uniqueCalibrators as $cIdx => $calibrator)
                            <tr>
                                <th>{{ $uniqueCalibrators->count() > 1 ? 'Étalon ' . ($cIdx + 1) : 'Étalon' }}</th>
                                <td>
                                    <strong>{{ $calibrator->full_name ?? ($calibrator->designation ?? $calibrator->internal_code) }}</strong><br>
                                    <span class="text-muted">S/N: {{ $calibrator->serial_number ?? $calibrator->internal_code }} | Cert: {{ $calibrator->latestCertificate?->reference ?? ($calibrator->calibrationCertificates?->last()?->reference ?? 'Agréé') }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <th>Étalon</th>
                                <td class="text-muted">Générateur de courant de précision GMTM agréé.</td>
                            </tr>
                            @endforelse
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- 3. Metrology Table (Full Width) -->
        <div class="section-heading">3. Résultats de la Conversion Analogique-Numérique (ADC)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 12%;">Appliqué (%)</th>
                    <th style="width: 15%;">Courant lu (mA)</th>
                    <th style="width: 16%;">Valeur Prévue ({{ $simSymbol }})</th>
                    <th style="width: 16%;">Afficheur Calc. ({{ $simSymbol }})</th>
                    <th style="width: 13%;">{{ $errorHeaderADC }}</th>
                    <th style="width: 12%;">{{ $emtHeaderADC }}</th>
                    <th style="width: 11%;">Décision</th>
                </tr>
            </thead>
            <tbody>
                @foreach($verification->points->sortBy('step_order') as $point)
                @php
                    $absErrADC = (float)$point->indicated_value - (float)$point->expected_value;
                    $relErrADC = ($simSpan !== 0.0) ? (($absErrADC / $simSpan) * 100.0) : 0.0;
                    $displayErrorADC = $isRelativeADC
                        ? (($relErrADC >= 0 ? '+' : '') . number_format($relErrADC, 4) . ' %')
                        : (($absErrADC >= 0 ? '+' : '') . number_format($absErrADC, 4) . ' ' . $simSymbol);
                    $displayEmtADC = '±' . number_format($point->emt_limit, 4) . ' ' . $emtUnitADC;
                @endphp
                <tr class="{{ !$point->is_conforme ? 'row-nok' : '' }}">
                    <td>{{ $point->step_order }}</td>
                    <td class="text-bold">{{ number_format($point->applied_percentage, 1) }} %</td>
                    <td>
                        {{ number_format($point->measured_signal, 4) }}
                        @if(isset($point->calibrator_1_correction) && abs((float)$point->calibrator_1_correction) > 0.00001)
                            <div style="font-size: 7.5px; color: #475569;">Corr: {{ number_format($point->corrected_signal ?? ($point->measured_signal + $point->calibrator_1_correction), 4) }}</div>
                        @endif
                    </td>
                    <td>
                        {{ number_format($point->expected_value, 4) }}
                        @if(isset($point->calibrator_2_correction) && abs((float)$point->calibrator_2_correction) > 0.00001)
                            <div style="font-size: 7.5px; color: #475569;">Corr: {{ number_format($point->corrected_expected_value ?? ($point->expected_value + $point->calibrator_2_correction), 4) }}</div>
                        @endif
                    </td>
                    <td class="text-bold" style="color:#0f766e;">{{ number_format($point->indicated_value, 4) }}</td>
                    <td class="text-bold">{{ $displayErrorADC }}</td>
                    <td>{{ $displayEmtADC }}</td>
                    <td>
                        @if($point->is_conforme)
                            <span class="badge-ok">VALID</span>
                        @else
                            <span class="badge-nok">INVALID</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Courbe ADC — page indépendante ── --}}
    @php
        $fcChartService = app(\App\Services\SvgChartService::class);
        $fcCurvePoints  = $verification->points->sortBy('step_order');
        $fcEmt          = (float)($fcCurvePoints->first()?->emt_limit ?? 0);
        $fcMaxErr       = $fcCurvePoints->max(fn($p) => abs((float)($p->absolute_error ?? 0.0))) ?? 0.0;
        $fcConformity   = ($fcMaxErr <= abs($fcEmt)) ? 'Conforme' : 'Non Conforme';
        $fcCurveImg     = $fcChartService->generateErrorCurvePng($fcCurvePoints, $fcEmt, $simMin, $simMax, 900, 380);
    @endphp
    <div class="page-break">
        <!-- Header -->
        <div class="report-header">
            <table class="header-table">
                <tr>
                    <td style="width: 45%;">
                       
                            <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                       
                    </td>
                    <td class="report-title-box" style="width: 55%;">
                        <div class="report-title-main">Courbe ADC — Calculateur de Débit</div>
                        <div class="report-meta-sub">
                            Rapport N° : <strong>{{ $report->report_number }}</strong>
                            | Tag FC : <strong style="color:#0f766e;">{{ $instrument->tag_number }}</strong>
                            | Voie : <strong>{{ $simTransmitter->tag_number ?? 'Ch' }}</strong>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <!-- Info Bar -->
        <table class="curve-infobar">
            <tr>
                <td>
                    Calculateur : <strong>{{ $instrument->tag_number }}</strong>
                    &nbsp;|&nbsp; Voie : <strong>{{ $simTransmitter->tag_number ?? '–' }}</strong>
                    &nbsp;|&nbsp; Étendue : <strong>{{ $simMin }} → {{ $simMax }} {{ $simSymbol }}</strong>
                    &nbsp;|&nbsp; EMT : <strong>±{{ number_format(abs($fcEmt), 4) }} {{ $emtUnitADC }}</strong>
                </td>
                <td class="curve-infobar-badge">
                    Décision :
                    @if($fcConformity === 'Conforme')
                        <span class="ok">&#10003; VALID</span>
                    @else
                        <span class="nok">&#10007; INVALID</span>
                    @endif
                </td>
            </tr>
        </table>
        <!-- SVG Curve -->
        <div class="curve-svg-box">
            <img src="{{ $fcCurveImg }}" style="width:100%; display:block;" alt="Courbe ADC" />
        </div>
    </div>
@endforeach



</body>
</html>