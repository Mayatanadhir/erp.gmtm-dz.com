<!DOCTYPE html>
<html lang="fr" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordre de Mission - {{ $order->order_reference ?? $mission->reference }}</title>
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
            margin: 15mm 20mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #111827;
            background: #ffffff;
            margin: 0;
            padding: 20px;
        }

        html.dark body {
            background-color: #0b0f19;
            color: #111827;
        }

        .print-page-sheet {
            background: #ffffff;
            color: #111827;
            max-width: 210mm;
            margin: 0 auto;
            padding: 15mm 20mm;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            box-sizing: border-box;
        }

        html.dark .print-page-sheet {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.6);
        }

        .header {
            width: 100%;
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .company-meta {
            text-align: right;
            font-size: 11px;
            color: #4b5563;
            line-height: 1.4;
        }

        .order-title {
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 22px;
            font-weight: 800;
            color: #000000;
            margin: 15px 0 5px 0;
        }

        .order-reference {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            color: #374151;
            font-family: monospace;
            margin-bottom: 25px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .details-table td {
            padding: 16px 8px;
            vertical-align: top;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        .details-table tr:last-child td {
            border-bottom: none;
        }

        .label {
            font-weight: 700;
            width: 35%;
            color: #374151;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .value {
            font-weight: 600;
            color: #111827;
        }

        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-box {
            text-align: center;
            width: 250px;
            padding-top: 10px;
        }

        .signature-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 60px;
            color: #1f2937;
        }

        .signature-line {
            border-top: 1px solid #9ca3af;
            padding-top: 5px;
            font-size: 11px;
            color: #6b7280;
        }

        .print-bar {
            background: #f3f4f6;
            color: #111827;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 210mm;
            margin-left: auto;
            margin-right: auto;
        }

        html.dark .print-bar {
            background: #1f2937;
            border: 1px solid #374151;
            color: #f3f4f6;
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
        }

        .print-btn:hover {
            background: #047857;
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
            }
        }
    </style>
</head>

<body>

    <div class="no-print print-bar">
        <div>
            <strong>{{ __('Ordre de Mission') }} :</strong> {{ $order->order_reference ?? $mission->reference }}
        </div>
        <button class="print-btn" onclick="window.print()">
            🖨️ {{ __('Print Travel Order') }}
        </button>
    </div>

    <div class="print-page-sheet">

        <!-- Header -->
        <div class="header">
            <img src="{{ asset('images/entete.png') }}" alt="GMTM entete">
        </div>

        <div style="text-align: right; font-size: 12px; color: #4b5563; margin-bottom: 15px;">
            <strong>Date :</strong> {{ now()->format('d/m/Y') }}
        </div>

        <h1 class="order-title">Ordre de Mission</h1>
        <div class="order-reference">Réf. : {{ $order->order_reference ?? $mission->reference }}</div>

        <table class="details-table">
            <tr>
                <td class="label">Nom & Prénom :</td>
                <td class="value">{{ $order->employee?->full_name }}</td>
            </tr>
            <tr>
                <td class="label">Fonction :</td>
                <td class="value">{{ $order->employee?->position?->label() }}</td>
            </tr>
            <tr>
                <td class="label">Destination :</td>
                <td class="value">{{ $order->destination ?? $mission->site?->location }}</td>
            </tr>
            <tr>
                <td class="label">Motif de la Mission :</td>
                <td class="value" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 0;">
                    {{ $mission->site?->full_name ?? 'Prestation Métrologique' }}{{ $mission->description ? ' — ' . $mission->description : '' }}
                </td>
            </tr>
            <tr>
                <td class="label">Moyen de Transport :</td>
                <td class="value">
                    @if ($order->all_vehicles)
                        Tous les véhicules
                    @elseif ($order->vehicle)
                        {{ $order->vehicle->full_name }}
                        @if ($order->vehicle->serial_number)
                            (Immatriculation : {{ $order->vehicle->serial_number }})
                        @endif
                    @else
                         Tous les véhicules
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Date de départ :</td>
                <td class="value">
                    {{ $order->started_at ? $order->started_at->format('d/m/Y') : $mission->start_date?->format('d/m/Y') }}
                </td>
            </tr>
            <tr>
                <td class="label">Date de retour :</td>
                <td class="value">
                    Fin de mission
                </td>
            </tr>
           
            <tr>
                <td class="label">Observations :</td>
                <td class="value">............/.........../............/.........../.........../.........../...........</td>
            </tr>
        </table>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-title">Le Responsable</div>

            </div>
        </div>

    </div>

</body>

</html>