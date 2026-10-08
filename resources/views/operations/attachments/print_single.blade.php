<!DOCTYPE html>
<html lang="{{ $lang ?? 'fr' }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>ATTACHEMENT DE PRESTATION — {{ $attachment->code_ref ?? $attachment->ods }}</title>
    <style>
        /* Page settings for printing */
        @page { 
            size: A4 landscape; 
            margin: 8mm; 
        }

        /* Reset default settings */
        * { 
            box-sizing: border-box; 
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact;
        }

        body { 
            font-family: 'Arial', 'Helvetica', sans-serif; 
            margin: 20px; 
            padding: 0; 
            background-color: #fff;
            color: #000;
        }

        /* Document container with solid border */
        .document-wrapper {
            width: 100%;
            border: 3px solid #000;
            padding: 4px;
        }

        /* Main title */
        .main-title-container {
            text-align: center;
            margin: 10px 0;
        }

        .main-title-container h1 {
            display: inline-block;
            border: 2px solid #000;
            padding: 8px 60px;
            font-size: 20pt;
            text-transform: uppercase;
            margin: 0;
            font-weight: bold;
            letter-spacing: 1px;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .info-table td {
            border: 1.5px solid #000;
            padding: 8px 12px;
            vertical-align: top;
            font-size: 10.5pt;
            line-height: 1.5;
        }

        .label-text { font-weight: bold; }
        .value-text { font-weight: normal; }

        /* Black divider row */
        .black-row-divider {
            background-color: #000 !important;
            height: 16px;
        }

        /* Grid section for columns */
        .grid-section {
            border-left: 1.5px solid #000;
            border-right: 1.5px solid #000;
            display: flex;
        }

        .column-data {
            flex: 1;
            border-right: 2px solid #000;
        }

        .column-data:last-child { border-right: none; }

        /* Data table inside columns */
        .data-table {
            width: 100%;
        }

        .data-table th {
            border-bottom: 2px solid #000;
            padding: 6px 2px;
            font-size: 9pt;
            text-align: center;
            background: #f0f0f0;
        }

        .data-table td {
            border-bottom: 1px dotted #000;
            padding: 7px 4px;
            font-size: 9.5pt;
            height: 32px;
            text-align: center;
        }

        .td-no { width: 35px; font-weight: bold; border-right: 1.5px solid #000; }
        .td-des { text-align: left !important; padding-left: 8px !important; border-right: 1.5px solid #000; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .td-qty { width: 50px; font-weight: bold; }

        /* Footer section - Visas */
        .footer-section {
            border: 1.5px solid #000;
            border-top: 2px solid #000;
        }

        .visa-side-label {
            width: 80px;
            font-weight: bold;
            font-size: 14pt;
            text-align: center;
            border-right: 1.5px solid #000;
            writing-mode: horizontal-tb;
            background: #f8f8f8;
        }

        .signature-column {
            border-right: 1.5px solid #000;
            padding: 0;
            width: 30%;
        }

        .signature-header {
            border-bottom: 1.5px solid #000;
            padding: 4px 10px;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            background: #f0f0f0;
        }

        .signature-body {
            padding: 8px 10px;
            min-height: 95px;
        }

        .sig-line {
            margin-bottom: 6px;
            font-size: 9pt;
        }

        /* Print actions */
        @media print {
            .print-actions { display: none !important; }
            body { margin: 0; }
            .document-wrapper { border-width: 3px; }
        }

        .print-actions {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.96);
            padding: 10px 16px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.18);
            z-index: 9999;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .print-btn {
            background-color: #10b981;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.2s ease-in-out;
        }

        .print-btn:hover {
            background-color: #059669;
        }

        .back-btn {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .back-btn:hover {
            background-color: #e5e7eb;
        }
    </style>
</head>
<body>

    <div class="print-actions">
        <a href="{{ route('operations.attachments.show', $attachment->id) }}" class="back-btn">
            ← {{ __('Back') }}
        </a>
        <button class="print-btn" onclick="window.print()">
            <span>🖨️</span> {{ __('Print Document') }}
        </button>
    </div>

    {{-- Company header banner --}}
    <div style="width: 100%; text-align: center; margin-bottom: 5px;">
        <img src="{{ asset('images/entete.png') }}" style="width: 100%; max-height: 80px; object-fit: contain;" alt="GMTM Entete">
    </div>

    <div class="document-wrapper">
        
        <div class="main-title-container">
            <h1>ATTACHEMENT DE {{ $attachment->type === 'supply' ? 'FOURNITURE' : 'PRESTATION' }}</h1> <br>
            <span style="font-size: 12pt; margin-top: 8px; font-weight: bold;">REF: {{ $attachment->code_ref ?? '—' }}</span>
        </div>

        {{-- Top Information Table --}}
        <table class="info-table">
            <tr>
                <td>
                    <div><span class="label-text">✦ Client :</span> <span class="value-text">{{ $contract?->customer?->company_name ?? ($contract?->customer?->short_name ?? '—') }}</span></div>
                    <div style="margin-top: 8px;"><span class="label-text">✦ Contrat :</span> <span class="value-text">{{ $contract?->object ?? '—' }}</span></div>
                </td>
                <td>
                    <div><span class="label-text">✦ Mois :</span> <span class="value-text">{{ \Illuminate\Support\Str::upper($attachment->date ? $attachment->date->translatedFormat('F Y') : '') }}</span></div>
                    <div style="margin-top: 4px;"><span class="label-text">✦ Contrat N° :</span> <span class="value-text">{{ $contract?->reference ?? '—' }}</span></div>
                    <div style="margin-top: 4px;"><span class="label-text">✦ ODS N° :</span> <span class="value-text">{{ $attachment->ods ?? '—' }}</span></div>
                </td>
                <td>
                    <div><span class="label-text">✦ Lieu :</span> <span class="value-text">{{ $attachment->mission?->site?->full_name ? ($attachment->mission->site->full_name . ' - ' . $attachment->mission->site->location) : ($attachment->mission?->site?->short_name ?? '—') }}</span></div>
                    <div style="margin-top: 8px;"><span class="label-text">✦ {{ $attachment->type === 'supply' ? 'Fourniture' : 'Prestation' }} :</span> <span class="value-text">{{ $contract?->object ?? '—' }}</span></div>
                </td>
            </tr>
        </table>

        {{-- Black divider --}}
        <div class="black-row-divider"></div>

        {{-- 3-Column Layout: 6 items per column (total 18 slots) --}}
        @php
            $items = $attachment->items;
            $maxRows = 18;
            $rowsPerColumn = 6;
            
            $displayItems = $items->take($maxRows)->pad($maxRows, null);
            $columns = $displayItems->chunk($rowsPerColumn);
        @endphp

        <div class="grid-section">
            @foreach($columns as $colIndex => $columnChunk)
                <div class="column-data">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="td-no">N°</th>
                                <th class="td-des">DESIGNATION</th>
                                <th class="td-qty">Qté</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($columnChunk as $rowIndex => $item)
                                @php $currentIndex = ($colIndex * $rowsPerColumn) + $rowIndex + 1; @endphp
                                <tr>
                                    <td class="td-no">{{ $item ? str_pad((string) $currentIndex, 2, '0', STR_PAD_LEFT) : '' }}</td>
                                    <td class="td-des" @if(!$item) style="text-align:center !important;" @endif>
                                        {{ $item ? ($item->contractItem?->designation ?? ($item->designation ?? '—')) : '//' }}
                                    </td>
                                    <td class="td-qty">{{ $item ? ((float) $item->actual_quantity ?: 'Forfait') : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>

        {{-- Signatures & Visas Table --}}
        <table class="footer-section">
            <tr>
                <td rowspan="2" class="visa-side-label">VISAS</td>
                <td class="signature-column">
                    <div class="signature-header">POUR GMTM</div>
                </td>
                <td class="signature-column">
                    <div class="signature-header">POUR {{ $attachment->mission?->site?->short_name ?? ($contract?->customer?->short_name ?? 'CLIENT') }}</div>
                </td>
                <td class="signature-column" style="border-right: none;">
                    <div class="signature-header">OBSERVATIONS</div>
                </td>
            </tr>
            <tr>
                <td class="signature-column">
                    <div class="signature-body">
                        <div class="sig-line">Date : ............................................</div>
                        <div class="sig-line">Nom : ............................................</div>
                        <div class="sig-line">Fonction : .......................................</div>
                        <div class="sig-line">Signature : .......................................</div>
                    </div>
                </td>
                <td class="signature-column">
                    <div class="signature-body">
                        <div class="sig-line">Date : ............................................</div>
                        <div class="sig-line">Nom : ............................................</div>
                        <div class="sig-line">Fonction : .......................................</div>
                        <div class="sig-line">Signature : .......................................</div>
                    </div>
                </td>
                <td class="signature-column" style="border-right: none;">
                    <div class="signature-body">
                        <div class="sig-line">......................................................</div>
                        <div class="sig-line">......................................................</div>
                        <div class="sig-line">......................................................</div>
                        <div class="sig-line">......................................................</div>
                    </div>
                </td>
            </tr>
        </table>

    </div>
</body>
</html>
