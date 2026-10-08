<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Relevé Brut — Chromatographe en Phase Gazeuse</title>
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

        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
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
    $verification = $chromatographVerification ?? $verification ?? null;
    $instrument = $verification?->instrument;
    $report = $verification->report;
    $mission = $report?->mission;
    $site = $instrument?->site ?? $mission?->site;
    $customer = $site?->customer ?? $mission?->site?->customer;

    $reportNumber = $verification->reference_number ?: ($report?->report_number ?? ('VERIF-GC-' . str_pad((string)$verification->id, 5, '0', STR_PAD_LEFT)));
    $customerName = $customer?->company_name ?? ($customer?->short_name ?? 'Client');
    $siteName = $site?->site_name ?? ($site?->name ?? ($site?->short_name ?? 'Site'));
    $verificationDate = $verification->verification_date ? \Carbon\Carbon::parse($verification->verification_date) : now();
    $verificationDateFormatted = $verificationDate->format('d/m/Y');

    $logoPath = public_path('images/Logo-black.png');
    $logoSrc = null;
    if (file_exists($logoPath)) {
        $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    } else {
        $logoSrc = asset('images/Logo-black.png');
    }

    $chromatoService = app(\App\Services\ChromatographMetrologyService::class);

    $defaultOrderMap = [
        'C6+' => 1,
        'Propane' => 2,
        'i-Butane' => 3,
        'n-Butane' => 4,
        'Neopentane' => 5,
        'i-Pentane' => 6,
        'n-Pentane' => 7,
        'Nitrogen' => 8,
        'Methane' => 9,
        'Carbon Dioxide' => 10,
        'Ethane' => 11,
        'Helium' => 12,
    ];
    $resolveCanonicalName = function($symbol, $name) {
        $str = strtolower(trim(($symbol ?? '') . ' ' . ($name ?? '')));
        return match(true) {
            str_contains($str, 'c6') || str_contains($str, 'hexane') => 'C6+',
            str_contains($str, 'c3') || str_contains($str, 'propane') => 'Propane',
            str_contains($str, 'ic4') || str_contains($str, 'iso-butane') || str_contains($str, 'i-butane') => 'i-Butane',
            str_contains($str, 'nc4') || str_contains($str, 'normal-butane') || str_contains($str, 'n-butane') => 'n-Butane',
            str_contains($str, 'neoc5') || str_contains($str, 'neo-pentane') || str_contains($str, 'neopentane') => 'Neopentane',
            str_contains($str, 'ic5') || str_contains($str, 'iso-pentane') || str_contains($str, 'i-pentane') => 'i-Pentane',
            str_contains($str, 'nc5') || str_contains($str, 'normal-pentane') || str_contains($str, 'n-pentane') => 'n-Pentane',
            str_contains($str, 'n2') || str_contains($str, 'nitrogen') || str_contains($str, 'azote') => 'Nitrogen',
            str_contains($str, 'c1') || str_contains($str, 'methane') || str_contains($str, 'méthane') => 'Methane',
            str_contains($str, 'co2') || str_contains($str, 'carbon dioxide') || str_contains($str, 'dioxyde') => 'Carbon Dioxide',
            str_contains($str, 'c2') || str_contains($str, 'ethane') => 'Ethane',
            str_contains($str, 'he') || str_contains($str, 'helium') => 'Helium',
            default => $name ?: $symbol,
        };
    };
    $compPoints = $verification->compositionPoints->sortBy(function($pt) use ($resolveCanonicalName, $defaultOrderMap) {
        $canon = $resolveCanonicalName($pt->component_symbol, $pt->component_name);
        return $defaultOrderMap[$canon] ?? ($pt->step_order ?? 99);
    });
    $fmtComp = function($val, $maxDec = 4) {
        if ($val === null || $val === '' || is_nan((float)$val)) return '-';
        $s = number_format((float)$val, $maxDec, '.', '');
        $trimmed = rtrim(rtrim($s, '0'), '.');
        return $trimmed === '' ? '0' : $trimmed;
    };
    $physPropOrderMap = [
        'PCS' => 1,
        'PCI' => 2,
        'Pb' => 3,
        'rho' => 3,
        'Zb' => 4,
        'Z' => 4,
    ];
    $physProps = $verification->physicalProperties->sortBy(function($prop) use ($physPropOrderMap) {
        return $physPropOrderMap[$prop->property_symbol] ?? 99;
    });
    $calibrators = $verification->calibrators;
@endphp

    {{-- =========================================================================
         PAGE 1 : PROTOCOLE DE VÉRIFICATION MÉTROLOGIQUE (SECTION IV : GC)
         ========================================================================= --}}
    <div class="page-break">
        <!-- Header -->
        <div class="report-header">
            <table class="header-table">
                <tr>
                    <td style="width: 50%;">
                       
                            <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                       
                    </td>
                    <td class="report-title-box" style="width: 50%;">
                        <div class="report-title-main"> Vérification Métrologique</div>
                        <div class="report-meta-sub">N° : <strong>{{ $reportNumber }}</strong> | Date : {{ $verificationDateFormatted }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Section Banner -->
        <div style="padding-top: 20%;"   >
        <div class="section-header-banner">
            <table style="width: 100%; border: none; margin: 0;">
                <tr>
                    <td style="width: 100%; border: none; padding: 0;">
                        <div  style="font-size: 19px; font-weight: bold;" class="section-header-banner-title">Section IV : Vérification du Chromatographe en Phase Gazeuse (GC)</div>
                        <div  style="font-size: 10px; font-weight: bold;" class="section-header-banner-sub">Protocole de Vérification Métrologique — Composition &amp; Propriétés Énergétiques (OIML R 140 / ASTM D 1945 / ISO 6974-2)</div>
                    </td>
                    <td style="width: 15%; text-align: right; font-size: 10px; font-weight: bold; border: none; padding: 0;">
                        1 Chromatographe
                    </td>
                </tr>
            </table>
        </div>

        <!-- 2 Columns Grid -->
        <table class="grid-2col">
           
        </table>
        </div>
    </div>

    {{-- =========================================================================
         PAGE 2 : RELEVÉ BRUT DU CHROMATOGRAPHE EN PHASE GAZEUSE (NOT EMT)
         ========================================================================= --}}
    <div class="page-break">
        <!-- Header -->
        <div class="report-header">
            <table class="header-table">
                <tr>
                    <td style="width: 30%;">
                        @if($logoSrc)
                            <img src="{{ $logoSrc }}" class="logo-img" alt="GMTM Logo">
                        @else
                            <div class="brand-badge-box">
                                <div class="brand-title">GMTM SERVICES</div>
                                <div class="brand-subtitle">Global Metrology &amp; Technical Management</div>
                            </div>
                        @endif
                    </td>
                    <td class="report-title-box" style="width: 70%;">
                        <div class="report-title-main">Relevé Brut — Chromatographe en Phase Gazeuse</div>
                        <div class="report-meta-sub">
                            Rapport N° : <strong>{{ $reportNumber }}</strong> | Tag : <strong style="color:#0f766e;">{{ $instrument?->tag_number ?? 'N/A' }}</strong> 
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 1. General & Cylinder Info -->
        <table class="grid-2col" style="margin-bottom: 5px;">
            <tr>
                <td style="width: 50%;">
                    <div class="info-card">
                        <div class="info-card-header">1. Identification de l'Instrument &amp; Conditions d'Essai</div>
                        <table class="info-table">
                            <tr>
                                <th>Tag No.</th>
                                <td class="text-bold" style="color:#0f766e;">{{ $instrument?->tag_number ?? 'N/A' }}</td>
                                <th>N° de Série</th>
                                <td class="text-bold">{{ $instrument?->serial_number ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Constructeur / Modèle</th>
                                <td>{{ $instrument?->manufacturer ?? '-' }} {{ $instrument?->model ?? '' }}</td>
                                <th>Fluide Analysé</th>
                                <td>{{ $instrument?->fluid ?? 'Gaz Naturel' }}</td>
                            </tr>
                            <tr>
                                <th>Site / Unité</th>
                                <td>{{ $siteName }}</td>
                                <th>Conditions d'Ambiance</th>
                                <td>{{ $verification->ambient_temperature ?? '20' }} °C | {{ $verification->ambient_pressure ?? '1013.2' }} mbar</td>
                            </tr>
                            <tr>
                                <th>Date Vérification </th>
                                <td class="text-bold">{{ $verificationDateFormatted }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="info-card">
                        <div class="info-card-header">2. Données du Mélange Gazeux Étalon Certifié</div>
                        <table class="info-table">
                            <tr>
                                <th>N° de Bouteille</th>
                                <td class="text-bold">{{ $verification->standard_gas_bottle_number ?? 'N/A' }}</td>
                                <th>N° de Certificat</th>
                                <td class="text-bold">{{ $verification->certificate_number ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Date de Validité</th>
                                <td>{{ $verification->cylinder_validity_date ? \Carbon\Carbon::parse($verification->cylinder_validity_date)->format('d/m/Y') : 'En cours' }}</td>
                                <th>Conditions de Réf.</th>
                                <td>{{ $verification->reference_conditions ?? '15°C / 101.325 kPa' }}</td>
                            </tr>
                            <tr>
                                <th>Pression Bouteille</th>
                                <td>{{ $verification->cylinder_pressure_bar ? $verification->cylinder_pressure_bar . ' bar' : 'N/A' }}</td>
                                <th>Équipement Référence</th>
                                <td>
                                    @forelse($calibrators as $cal)
                                        {{ $cal->full_name ?? ($cal->designation ?? $cal->internal_code) }}@if(!$loop->last), @endif
                                    @empty
                                        Chaîne d'injection étalon GMTM
                                    @endforelse
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- 2. Composition Metrology Table (Raw - NotEMT) -->
        <div class="section-heading">3. EXACTITUDE ET REPETABILITE DES ANALYSES CHROMATOGRAPHIQUES :</div>
        <table class="data-table" style="margin-top: 4px; margin-bottom: 5px;">
            <thead>
                <tr style="background-color: #115e59; color: #ffffff;">
                    <th colspan="2" rowspan="2" style="width: 20%; font-size: 8px; padding: 3px 2px; vertical-align: middle;">MELANGE ETALON</th>
                    <th colspan="5" style="width: 38%; font-size: 8px; padding: 3px 2px;">ANALYSES CHROMATOGRAPHIQUES</th>
                    <th colspan="2" style="width: 22%; font-size: 8px; padding: 3px 2px;">EXACTITUDE ISO 6974-2</th>
                    <th style="width: 20%; font-size: 8px; padding: 3px 2px;">REPETABILITE ASTM 1945-14</th>
                </tr>
                <tr style="background-color: #0f766e; color: #ffffff;">
                    <th style="width: 7.6%; font-size: 7.5px; padding: 3px 2px;">Analyse 1</th>
                    <th style="width: 7.6%; font-size: 7.5px; padding: 3px 2px;">Analyse 2</th>
                    <th style="width: 7.6%; font-size: 7.5px; padding: 3px 2px;">Analyse 3</th>
                    <th style="width: 7.6%; font-size: 7.5px; padding: 3px 2px;">Analyse 4</th>
                    <th style="width: 7.6%; font-size: 7.5px; padding: 3px 2px;">Analyse 5</th>

                    <th style="width: 11%; font-size: 7.5px; padding: 3px 2px;">Moyenne</th>
                    <th style="width: 11%; font-size: 7.5px; padding: 3px 2px;">Erreur</th>

                    <th style="width: 20%; font-size: 7.5px; padding: 3px 2px;">Repetabilité</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumRef = 0;
                    $sumMean = 0;
                @endphp
                @foreach($compPoints as $pt)
                @php
                    $canonName = $resolveCanonicalName($pt->component_symbol, $pt->component_name);
                    $runs = [
                        (float)$pt->run_1,
                        (float)$pt->run_2,
                        (float)$pt->run_3,
                        (float)$pt->run_4,
                        (float)$pt->run_5,
                    ];
                    $evalPt = $chromatoService->evaluateComponent($runs, (float)$pt->reference_value);

                    $sumRef += (float)$pt->reference_value;
                    $meanVal = (float)($pt->mean_value ?? $evalPt['mean_value']);
                    $sumMean += $meanVal;

                    $rVal = (float)$evalPt['relative_error_percent'];
                    $errStr = abs($rVal) < 0.0005 ? '0.0 %' : number_format($rVal, 3) . ' %';
                    $repVal = (float)$evalPt['repeatability'];
                @endphp
                <tr>
                    <td style="width: 12%; text-align: left; padding-left: 5px; font-size: 8px;">
                        <strong>{{ $canonName }}</strong>
                    </td>
                    <td class="text-bold" style="width: 8%; font-size: 7.5px; padding: 2.5px 2px;">{{ $fmtComp($pt->reference_value) }}</td>
                    <td style="width: 7.6%; font-size: 7.5px; padding: 2.5px 2px;">{{ $fmtComp($pt->run_1) }}</td>
                    <td style="width: 7.6%; font-size: 7.5px; padding: 2.5px 2px;">{{ $fmtComp($pt->run_2) }}</td>
                    <td style="width: 7.6%; font-size: 7.5px; padding: 2.5px 2px;">{{ $fmtComp($pt->run_3) }}</td>
                    <td style="width: 7.6%; font-size: 7.5px; padding: 2.5px 2px;">{{ $fmtComp($pt->run_4) }}</td>
                    <td style="width: 7.6%; font-size: 7.5px; padding: 2.5px 2px;">{{ $fmtComp($pt->run_5) }}</td>
                    <td class="text-bold" style="width: 11%; color: #0f766e; font-size: 7.5px; padding: 2.5px 2px;">{{ $fmtComp($meanVal) }}</td>
                    <td class="text-bold" style="width: 11%; font-size: 7.5px; padding: 2.5px 2px;">{{ $errStr }}</td>
                    <td style="width: 20%; font-size: 7.5px; padding: 2.5px 2px;">{{ $fmtComp($repVal, 3) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold; font-size: 7.5px;">
                    <td style="width: 12%; text-align: right; padding-right: 5px;">TOTAL SOMME :</td>
                    <td class="text-bold" style="width: 8%;">{{ number_format($sumRef, 4) }} %</td>
                    <td colspan="5" class="text-muted" style="width: 38%; font-size: 7px;">Fractions molaires normalisées</td>
                    <td class="text-bold" style="width: 11%; color: #0f766e;">{{ number_format($sumMean, 4) }} %</td>
                    <td colspan="2" style="width: 31%;"></td>
                </tr>
            </tfoot>
        </table>

        <!-- 3. Physical Properties (Raw - NotEMT) -->
        @if($physProps->isNotEmpty())
        <div class="section-heading" style="margin-top: 5px;">4. EXACTITUDE ET REPETABILITE DES PROPRIETES PHYSIQUES :</div>
        <table class="data-table" style="margin-top: 3px; margin-bottom: 5px;">
            <thead>
                <tr style="background-color: #115e59; color: #ffffff;">
                    <th colspan="2" rowspan="2" style="width: 20%; font-size: 8px; padding: 3px 2px; vertical-align: middle;">PROPRIETES PHYSIQUES</th>
                    <th colspan="5" style="width: 38%; font-size: 8px; padding: 3px 2px;">ANALYSES CHROMATOGRAPHIQUES</th>
                    <th colspan="2" style="width: 22%; font-size: 8px; padding: 3px 2px;">EXACTITUDE ISO 6976</th>
                    <th style="width: 20%; font-size: 8px; padding: 3px 2px;">REPETABILITE OIML R 140</th>
                </tr>
                <tr style="background-color: #0f766e; color: #ffffff;">
                    <th style="width: 7.6%; font-size: 7.5px; padding: 3px 2px;">Analyse 1</th>
                    <th style="width: 7.6%; font-size: 7.5px; padding: 3px 2px;">Analyse 2</th>
                    <th style="width: 7.6%; font-size: 7.5px; padding: 3px 2px;">Analyse 3</th>
                    <th style="width: 7.6%; font-size: 7.5px; padding: 3px 2px;">Analyse 4</th>
                    <th style="width: 7.6%; font-size: 7.5px; padding: 3px 2px;">Analyse 5</th>

                    <th style="width: 11%; font-size: 7.5px; padding: 3px 2px;">Moyenne</th>
                    <th style="width: 11%; font-size: 7.5px; padding: 3px 2px;">Erreur</th>

                    <th style="width: 20%; font-size: 7.5px; padding: 3px 2px;">Repetabilité</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $formatPropVal = function ($val) {
                        if ($val === null || $val === '') return '-';
                        $num = (float) $val;
                        $str = rtrim(rtrim(number_format($num, 12, '.', ''), '0'), '.');
                        $parts = explode('.', $str);
                        if (count($parts) === 1 || strlen($parts[1]) < 4) {
                            return number_format($num, 4, '.', '');
                        }
                        return $str;
                    };
                @endphp
                @foreach($physProps as $prop)
                @php
                    $runs = [
                        (float)$prop->run_1,
                        (float)$prop->run_2,
                        (float)$prop->run_3,
                        (float)$prop->run_4,
                        (float)$prop->run_5,
                    ];
                    $emtLimit = (float)($prop->emt_limit_percent ?? 0.50);
                    $isPcs = strtoupper($prop->property_symbol) === 'PCS';
                    $repLimit = ($isPcs && $prop->repeatability_limit !== null) ? (float)$prop->repeatability_limit : ($isPcs ? 0.10 : null);
                    $evalProp = $chromatoService->evaluatePhysicalProperty($runs, (float)$prop->reference_value, $emtLimit, $repLimit);

                    $meanVal = $prop->mean_value !== null ? $prop->mean_value : $evalProp['mean_value'];
                    $rVal = (float)$evalProp['relative_error_percent'];
                    $errStr = number_format($rVal, 2, '.', '') . ' %';
                    $repVal = $evalProp['repeatability'];

                    $displaySymbol = match($prop->property_symbol) {
                        'rho' => 'Pb',
                        'Z' => 'Zb',
                        default => $prop->property_symbol,
                    };
                @endphp
                <tr>
                    <td style="width: 12%; text-align: center; font-size: 8px; padding: 2.5px 2px;">
                        <strong>{{ $displaySymbol }}</strong>
                    </td>
                    <td class="text-bold" style="width: 8%; font-size: 7.5px; padding: 2.5px 2px;">{{ $formatPropVal($prop->reference_value) }}</td>
                    <td style="width: 7.6%; font-size: 7.5px; padding: 2.5px 2px;">{{ $formatPropVal($prop->run_1) }}</td>
                    <td style="width: 7.6%; font-size: 7.5px; padding: 2.5px 2px;">{{ $formatPropVal($prop->run_2) }}</td>
                    <td style="width: 7.6%; font-size: 7.5px; padding: 2.5px 2px;">{{ $formatPropVal($prop->run_3) }}</td>
                    <td style="width: 7.6%; font-size: 7.5px; padding: 2.5px 2px;">{{ $formatPropVal($prop->run_4) }}</td>
                    <td style="width: 7.6%; font-size: 7.5px; padding: 2.5px 2px;">{{ $formatPropVal($prop->run_5) }}</td>

                    <td class="text-bold" style="width: 11%; color: #0f766e; font-size: 7.5px; padding: 2.5px 2px;">{{ number_format((float)$meanVal, 6, '.', '') }}</td>
                    <td class="text-bold" style="width: 11%; font-size: 7.5px; padding: 2.5px 2px;">{{ $errStr }}</td>

                    @if($loop->first)
                        <td style="width: 20%; font-size: 7.5px; padding: 2.5px 2px;">{{ number_format((float)$repVal, 1, '.', '') }}</td>
                    @elseif($loop->iteration === 2)
                        <td rowspan="{{ $physProps->count() - 1 }}" style="width: 20%; background: #ffffff;"></td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Observations & Signatures -->
        @if($verification->remarks)
            <div style="margin-top: 6px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff;">
                <div style="font-weight: bold; font-size: 9px; color: #475569; margin-bottom: 2px;">Observations &amp; Remarques :</div>
                <div style="font-size: 8.5px; color: #334155; line-height: 1.35;">
                    {{ $verification->remarks }}
                </div>
            </div>
        @endif

        
    </div>

</body>
</html>
