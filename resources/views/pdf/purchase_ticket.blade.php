<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Compra #{{ $receipt->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 11px; width: 80mm; margin: 0 auto; padding: 8px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .separator { border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; }
        .header { margin-bottom: 10px; }
        .empresa { font-size: 14px; font-weight: bold; letter-spacing: 1px; }
    </style>
</head>
<body>
    <div class="header text-center">
        <div class="empresa">Comprobante de Ingreso (Compra)</div>
        <div style="font-size: 9px; margin-top: 3px;">{{ $company->name ?? 'Ferreteria' }}</div>
    </div>

    <div class="separator"></div>

    <div>
        <div>Ticket/Doc #: {{ $purchase->invoice_number ?? $receipt->number }}</div>
        <div>Fecha: {{ $purchase->purchase_date->format('d/m/Y') }}</div>
        <div>Responsable: {{ $purchase->user->name }}</div>
        @if($purchase->supplier)
        <div>Proveedor: {{ $purchase->supplier->name }}</div>
        @endif
    </div>

    <div class="separator"></div>

    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Cant</th>
                <th style="text-align: left;">Producto</th>
                <th class="text-right">Costo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $item)
            <tr>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->productVariant->product->name }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="separator"></div>

    <div>
        <div class="text-right bold" style="font-size: 13px;">TOTAL: {{ number_format($purchase->total, 0, ',', '.') }} Gs</div>
    </div>

    <div class="separator"></div>

    <div class="text-center" style="margin-top: 8px;">
        <p class="bold">COMPRA REGISTRADA EN SISTEMA</p>
        <p style="font-size: 8px;">Impreso el {{ now()->format('d/m/Y H:i:s') }}</p>
        
        @if(class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode'))
            @php
                $qrData = "Compra: " . $receipt->number . " | Total: " . number_format($purchase->total, 0, '', '') . " Gs | Fecha: " . $purchase->purchase_date->format('Y-m-d');
            @endphp
            <div style="margin-top: 10px;">
                <img src="data:image/png;base64, {!! base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(80)->generate($qrData)) !!} ">
                <div style="font-size: 8px; margin-top: 2px;">Escanear recibo</div>
            </div>
        @endif
    </div>
</body>
</html>
