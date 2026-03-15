<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Caja</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .totals { margin-top: 20px; text-align: right; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Reporte de Caja Registradora</h2>
        <p>{{ $cashRegister->name }}</p>
    </div>

    <div class="details">
        <p><strong>Cajero:</strong> {{ $cashRegister->user->name }}</p>
        <p><strong>Apertura:</strong> {{ $cashRegister->opened_at ? \Carbon\Carbon::parse($cashRegister->opened_at)->format('d/m/Y H:i') : '-' }}</p>
        <p><strong>Cierre:</strong> {{ $cashRegister->closed_at ? \Carbon\Carbon::parse($cashRegister->closed_at)->format('d/m/Y H:i') : '-' }}</p>
        <p><strong>Estado:</strong> {{ $cashRegister->status === 'open' ? 'Abierta' : 'Cerrada' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Nro Documento</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalSales = 0; 
                $customerPayments = \App\Models\CustomerPayment::whereHas('sale', fn($q) => $q->where('cash_register_id', $cashRegister->id))
                    ->whereBetween('created_at', [$cashRegister->opened_at, $cashRegister->closed_at ?? now()])
                    ->sum('amount');
            @endphp
            @foreach($sales as $sale)
                @if($sale->status === 'completed' && $sale->payment_method === 'contado')
                    @php $totalSales += $sale->total; @endphp
                @endif
                <tr>
                    <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $sale->document_type === 'invoice' ? 'Factura' : 'Ticket' }}</td>
                    <td>{{ $sale->invoice_number }}</td>
                    <td>{{ $sale->customer ? $sale->customer->name : 'Sin nombre' }}</td>
                    <td>{{ $sale->status === 'completed' ? 'Completado' : ($sale->status === 'cancelled' ? 'Cancelado' : $sale->status) }}</td>
                    <td>{{ number_format($sale->total, 0, ',', '.') }} Gs</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p>Monto de Apertura: {{ number_format($cashRegister->opening_amount, 0, ',', '.') }} Gs</p>
        <p>Ventas al Contado: {{ number_format($totalSales, 0, ',', '.') }} Gs</p>
        <p>Cobros de Créditos: {{ number_format($customerPayments, 0, ',', '.') }} Gs</p>
        <p>Efectivo Esperado: {{ number_format($cashRegister->opening_amount + $totalSales + $customerPayments, 0, ',', '.') }} Gs</p>
        @if($cashRegister->status === 'closed')
            <p>Efectivo Físico Declarado: {{ number_format($cashRegister->closing_amount, 0, ',', '.') }} Gs</p>
            <p>Diferencia: {{ number_format($cashRegister->closing_amount - ($cashRegister->opening_amount + $totalSales), 0, ',', '.') }} Gs</p>
        @endif
    </div>
</body>
</html>
