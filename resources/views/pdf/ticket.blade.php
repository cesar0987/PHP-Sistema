<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ticket #{{ $receipt->number }}</title>
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
        <div class="empresa">TERRACOTA</div>
        <div class="empresa" style="font-size: 10px;">CONSTRUCCIONES</div>
        <div style="font-size: 9px; margin-top: 3px;">Materiales y Ferreteria</div>
        <div style="font-size: 9px;">RUC: {{ $company->ruc ?? '-' }}</div>
        <div style="font-size: 9px;">{{ $company->address ?? '' }}</div>
        <div style="font-size: 9px;">Tel: {{ $company->phone ?? '' }}</div>
    </div>

    <div class="separator"></div>

    <div>
        <div>Ticket #: {{ $receipt->number }}</div>
        <div>Fecha: {{ $sale->sale_date->format('d/m/Y H:i') }}</div>
        <div>Vendedor: {{ $sale->user->name }}</div>
        @if($sale->customer)
        <div>Cliente: {{ $sale->customer->name }}</div>
        @endif
    </div>

    <div class="separator"></div>

    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Cant</th>
                <th style="text-align: left;">Producto</th>
                <th class="text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
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
        <div class="text-right">Subtotal: {{ number_format($sale->subtotal, 0, ',', '.') }} Gs</div>
        @if($sale->discount > 0)
        <div class="text-right">Descuento: -{{ number_format($sale->discount, 0, ',', '.') }} Gs</div>
        @endif
        @if($sale->tax > 0)
        <div class="text-right">IVA: {{ number_format($sale->tax, 0, ',', '.') }} Gs</div>
        @endif
        <div class="text-right bold" style="font-size: 13px;">TOTAL: {{ number_format($sale->total, 0, ',', '.') }} Gs</div>
    </div>

    <div class="separator"></div>

    <div class="text-center" style="margin-top: 8px;">
        <p class="bold">Gracias por su compra!</p>
        <p style="font-size: 8px;">{{ now()->format('d/m/Y H:i:s') }}</p>
        
        @if(class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode'))
            @php
                $qrData = "Comprobante: " . $receipt->number . " | Total: " . number_format($sale->total, 0, '', '') . " Gs | Fecha: " . $sale->sale_date->format('Y-m-d');
            @endphp
            <div style="margin-top: 10px;">
                <img src="data:image/png;base64, {!! base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(80)->generate($qrData)) !!} ">
                <div style="font-size: 8px; margin-top: 2px;">Escanear para verificar</div>
            </div>
        @endif
    </div>
</body>
</html>
