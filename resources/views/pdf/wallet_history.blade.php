<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Historial de Transacciones - ElectroPoint</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #00c853; padding-bottom: 10px; }
        .header h1 { color: #00c853; margin: 0; font-size: 24px; }
        .user-info { margin-bottom: 20px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f5f5f5; color: #666; text-align: left; padding: 10px; border-bottom: 1px solid #ddd; font-size: 12px; }
        td { padding: 10px; border-bottom: 1px solid #eee; font-size: 11px; }
        .amount { font-weight: bold; }
        .credit { color: #00c853; }
        .debit { color: #f44336; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; }
        .status { padding: 2px 5px; border-radius: 3px; font-size: 9px; text-transform: uppercase; }
        .status-completed { background: #e8f5e9; color: #2e7d32; }
        .status-pending { background: #fff3e0; color: #ef6c00; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ELECTROPOINT</h1>
        <p>{{ isset($is_single) && $is_single ? 'Recibo de Pago' : 'Reporte de Historial de Billetera' }}</p>
    </div>

    <div class="user-info">
        <strong>Usuario:</strong> {{ $user->name }} ({{ $user->email }})<br>
        <strong>Fecha de Reporte:</strong> {{ date('d/m/Y H:i') }}<br>
        <strong>Billetera:</strong> ElectroWallet BOB
    </div>

    <table>
        <thead>
            <tr>
                <th>FECHA</th>
                <th>CONCEPTO / DESCRIPCIÓN</th>
                <th>TIPO</th>
                <th>ESTADO</th>
                <th>MONTO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $tx)
            <tr>
                <td>{{ \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y H:i') }}</td>
                <td>{{ $tx->description ?? ($tx->type == 'RECHARGE' ? 'Recarga de Saldo' : 'Consumo de Energía') }}</td>
                <td>{{ $tx->type }}</td>
                <td>
                    <span class="status {{ strtolower($tx->status ?? 'COMPLETED') == 'completed' ? 'status-completed' : 'status-pending' }}">
                        {{ $tx->status ?? 'COMPLETED' }}
                    </span>
                </td>
                <td class="amount {{ $tx->type == 'RECHARGE' ? 'credit' : 'debit' }}">
                    {{ $tx->type == 'RECHARGE' ? '+' : '-' }} {{ number_format($tx->amount, 2) }} BOB
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Este documento es un comprobante oficial de ElectroPoint. Para consultas, contacte a soporte@evbol.com.
    </div>
</body>
</html>
