<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura de Carga #{{ $record->id }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.5; margin: 0; padding: 0; }
        .container { padding: 40px; }
        .header { border-bottom: 2px solid #0076D6; padding-bottom: 20px; margin-bottom: 30px; }
        .header table { width: 100%; }
        .logo { font-size: 24px; font-weight: bold; color: #0076D6; }
        .invoice-title { font-size: 28px; font-weight: bold; text-align: right; color: #555; }
        .details table { width: 100%; margin-bottom: 40px; }
        .details td { width: 50%; vertical-align: top; }
        .label { font-size: 10px; text-transform: uppercase; color: #888; font-weight: bold; margin-bottom: 5px; }
        .value { font-size: 14px; font-weight: 500; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .items-table th { background: #f8f9fa; padding: 12px; text-align: left; font-size: 12px; border-bottom: 2px solid #dee2e6; }
        .items-table td { padding: 12px; border-bottom: 1px solid #dee2e6; font-size: 13px; }
        .total-section { float: right; width: 250px; }
        .total-row { padding: 10px 0; border-bottom: 1px solid #eee; }
        .total-row.final { border-bottom: none; font-size: 18px; font-weight: bold; color: #0076D6; }
        .total-row span { float: right; }
        .footer { position: fixed; bottom: 30px; width: 100%; text-align: center; font-size: 10px; color: #aaa; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table>
                <tr>
                    <td class="logo">ElectroPoint EV</td>
                    <td class="invoice-title">RECIBO DE CARGA</td>
                </tr>
            </table>
        </div>

        <div class="details">
            <table>
                <tr>
                    <td>
                        <div class="label">CLIENTE</div>
                        <div class="value">{{ $record->user->name ?? 'Usuario Final' }}</div>
                        <div class="value">{{ $record->user->email ?? '-' }}</div>
                        @if($record->user->billing_document)
                            <div class="value">NIT/CI: {{ $record->user->billing_document }}</div>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div class="label">DETALLES DEL RECIBO</div>
                        <div class="value">ID Transacción: #{{ $record->transaction_id ?? $record->id }}</div>
                        <div class="value">Fecha: {{ $record->stop_time?->format('d/m/Y H:i') ?? $record->created_at->format('d/m/Y H:i') }}</div>
                        <div class="value">Estación: {{ $record->station->name ?? 'Desconocida' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th style="text-align: right;">Cantidad</th>
                    <th style="text-align: right;">Precio Unit.</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $details = $record->billing_details;
                    $breakdown = $details['breakdown'] ?? [];
                @endphp

                @if(!empty($breakdown))
                    @foreach($breakdown as $item)
                    <tr>
                        <td>Consumo Energía - Bloque Horario #{{ $item['block'] }}</td>
                        <td style="text-align: right;">{{ number_format($item['energy_kwh'], 2) }} kWh</td>
                        <td style="text-align: right;">{{ number_format($item['rate'], 2) }}</td>
                        <td style="text-align: right;">{{ number_format($item['cost'], 2) }}</td>
                    </tr>
                    @endforeach
                @elseif($record->energy_cost > 0)
                <tr>
                    <td>Consumo de Energía Eléctrica</td>
                    <td style="text-align: right;">{{ number_format($record->total_energy_kwh, 2) }} kWh</td>
                    <td style="text-align: right;">{{ number_format($record->rate_kwh, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($record->energy_cost, 2) }}</td>
                </tr>
                @endif
                
                @if($record->session_fee > 0)
                <tr>
                    <td>Cargo por Inicio de Sesión (Parking Fee)</td>
                    <td style="text-align: right;">1</td>
                    <td style="text-align: right;">-</td>
                    <td style="text-align: right;">{{ number_format($record->session_fee, 2) }}</td>
                </tr>
                @endif

                @if($record->time_fee > 0)
                <tr>
                    <td>Recargo por Ocupación de Tiempo</td>
                    <td style="text-align: right;">1</td>
                    <td style="text-align: right;">-</td>
                    <td style="text-align: right;">{{ number_format($record->time_fee, 2) }}</td>
                </tr>
                @endif

                @if($record->discount_amount > 0)
                <tr>
                    <td style="color: #28a745; font-weight: bold;">Descuento Aplicado</td>
                    <td style="text-align: right;">1</td>
                    <td style="text-align: right;">-</td>
                    <td style="text-align: right; color: #28a745;">-{{ number_format($record->discount_amount, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-row final">
                TOTAL <span>Bs {{ number_format($record->total_cost, 2) }}</span>
            </div>
            <div style="font-size: 10px; color: #888; text-align: right; margin-top: 5px;">
                Pagado mediante Billetera Virtual (RFID: {{ $record->rfidTag->tag_code ?? 'App' }})
            </div>
        </div>

        <div class="footer">
            Este documento es un comprobante de servicio digital generado por el sistema ElectroPoint.<br>
            {{ config('app.url') }} - Soporte: contacto@evbol.com
        </div>
    </div>
</body>
</html>
