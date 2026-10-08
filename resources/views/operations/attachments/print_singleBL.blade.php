<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>BON DE LIVRAISON — {{ $attachment->code_ref ?? $attachment->ods }}</title>

    <style>
        /* Page settings for printing */
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 20px;
            background: #fff;
            color: #000;
        }

        .wrapper {
            border: 3px solid #000;
            padding: 5px;
        }

        .header img {
            width: 100%;
            max-height: 80px;
            object-fit: contain;
        }

        .title {
            text-align: center;
            margin: 10px 0;
        }

        .title h1 {
            display: inline-block;
            border: 2px solid #000;
            padding: 8px 50px;
            font-size: 20pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .ref-text {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .info td {
            border: 1.5px solid #000;
            padding: 8px 12px;
            font-size: 10pt;
            vertical-align: top;
            line-height: 1.5;
        }

        .label {
            font-weight: bold;
        }

        .divider {
            height: 15px;
            background: #000;
        }

        .items th {
            border-bottom: 2px solid #000;
            border-right: 1.5px solid #000;
            padding: 6px;
            font-size: 9pt;
            background: #f0f0f0;
            text-align: center;
        }

        .items th:last-child {
            border-right: none;
        }

        .items td {
            border-bottom: 1px dotted #000;
            border-right: 1px solid #000;
            padding: 6px;
            height: 30px;
            text-align: center;
            font-size: 9.5pt;
        }

        .items td:last-child {
            border-right: none;
        }

        .no { width: 45px; font-weight: bold; }
        .des { text-align: left !important; padding-left: 10px !important; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .qty { width: 80px; font-weight: bold; }
        .obs { width: 140px; font-size: 9pt; color: #444; }

        .footer {
            border: 1.5px solid #000;
            margin-top: 10px;
        }

        .footer td {
            border-right: 1.5px solid #000;
            vertical-align: top;
            width: 33.33%;
            padding: 0;
        }

        .footer td:last-child {
            border-right: none;
        }

        .footer-header {
            border-bottom: 1.5px solid #000;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            background: #f0f0f0;
        }

        .footer-body {
            padding: 10px;
            min-height: 95px;
            font-size: 9.5pt;
            line-height: 1.6;
        }

        /* Print actions bar */
        @media print {
            .print-actions { display: none !important; }
            body { margin: 0; }
            .wrapper { border-width: 3px; }
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
            background-color: #0d9488;
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
            background-color: #0f766e;
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
        <span>🖨️</span> {{ __('Print Bon de Livraison') }}
    </button>
</div>

<div class="wrapper">

    <div class="header">
        <img src="{{ asset('images/entete.png') }}" alt="GMTM Entete">
    </div>

    <div class="title">
        <h1>BON DE LIVRAISON</h1>
        <div class="ref-text">REF: {{ $attachment->code_ref ?? $attachment->ods }}</div>
    </div>

    <table class="info">
        <tr>
            <td>
                <span class="label">Client :</span> {{ $contract?->customer?->company_name ?? ($contract?->customer?->short_name ?? '—') }}
            </td>
            <td>
                <span class="label">Date :</span> {{ $attachment->date ? $attachment->date->format('d/m/Y') : now()->format('d/m/Y') }}
            </td>
            <td>
                <span class="label">Lieu :</span> {{ $attachment->mission?->site?->full_name ? ($attachment->mission->site->full_name . ' - ' . $attachment->mission->site->location) : ($attachment->mission?->site?->short_name ?? '—') }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Contrat N° :</span> {{ $contract?->reference ?? '—' }}
            </td>
            <td>
                <span class="label">ODS N° :</span> {{ $attachment->ods ?? '—' }}
            </td>
            <td>
                <span class="label">Mission :</span> {{ $attachment->mission?->reference ?? '—' }}
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <span class="label">Objet / Référence :</span> {{ $contract?->object ?? 'Prestations de maintenance et travaux d’exploitation' }}
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    @php
        $max = 16;
        $items = $attachment->items->take($max)->pad($max, null);
    @endphp

    <table class="items">
        <thead>
            <tr>
                <th class="no">N°</th>
                <th class="des">DESIGNATION DES PRESTATIONS & FOURNITURES</th>
                <th class="qty">QTE</th>
                <th class="obs">CATEGORIE / OBS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
            <tr>
                <td class="no">{{ $item ? str_pad((string)($i+1), 2, '0', STR_PAD_LEFT) : '' }}</td>
                <td class="des" @if(!$item) style="text-align:center !important;" @endif>
                    {{ $item ? ($item->contractItem?->designation ?? ($item->designation ?? '—')) : '//' }}
                </td>
                <td class="qty">
                    {{ $item ? ((float)$item->actual_quantity ?: ((float)$item->planned_quantity ?: 'Forfait')) : '' }}
                </td>
                <td class="obs">
                    {{ $item ? ($item->contractItem?->itemType?->designation ?? ($item->contractItem?->type ?? '')) : '' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="footer">
        <tr>
            <td>
                <div class="footer-header">LIVREUR (POUR GMTM)</div>
                <div class="footer-body">
                    Nom & Prénom: .......................................<br><br>
                    Date & Visa: ...........................................<br><br>
                    Signature:
                </div>
            </td>
            <td>
                <div class="footer-header">RECEPTIONNAIRE (POUR {{ $attachment->mission?->site?->short_name ?? ($contract?->customer?->short_name ?? 'CLIENT') }})</div>
                <div class="footer-body">
                    Nom & Prénom: .......................................<br><br>
                    Date & Visa: ...........................................<br><br>
                    Signature:
                </div>
            </td>
            <td>
                <div class="footer-header">OBSERVATIONS & RESERVES</div>
                <div class="footer-body">
                    ......................................................................<br><br>
                    ......................................................................
                </div>
            </td>
        </tr>
    </table>

</div>

</body>
</html>
