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
                <th style="text-align: left; width: 10%;">Cant</th>
                <th style="text-align: left; width: 40%;">Producto</th>
                <th class="text-right" style="width: 16%;">Exentas</th>
                <th class="text-right" style="width: 17%;">5%</th>
                <th class="text-right" style="width: 17%;">10%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $item)
            @php
                $itemTaxPct = $item->tax_percentage ?? 10;
                $exenta = $itemTaxPct == 0 ? $item->subtotal : 0;
                $iva5 = $itemTaxPct == 5 ? $item->subtotal : 0;
                $iva10 = $itemTaxPct == 10 ? $item->subtotal : 0;
            @endphp
            <tr>
                <td style="vertical-align: top;">{{ $item->quantity }}</td>
                <td style="word-break: break-all;">{{ $item->productVariant->product->name }}</td>
                <td class="text-right" style="vertical-align: top;">{{ $exenta > 0 ? number_format($exenta, 0, ',', '.') : '' }}</td>
                <td class="text-right" style="vertical-align: top;">{{ $iva5 > 0 ? number_format($iva5, 0, ',', '.') : '' }}</td>
                <td class="text-right" style="vertical-align: top;">{{ $iva10 > 0 ? number_format($iva10, 0, ',', '.') : '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="separator"></div>
    
    <!-- Subtotales -->
    <table>
        <tr>
            <td style="text-align: left;" class="bold">Subtotales</td>
            <td class="text-right">{{ number_format($purchase->subtotal_exenta ?? 0, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($purchase->subtotal_5 ?? 0, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($purchase->subtotal_10 ?? 0, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="separator"></div>

    <div>
        <div class="text-right bold" style="font-size: 13px;">TOTAL COMPRA: {{ number_format($purchase->total, 0, ',', '.') }} Gs</div>
    </div>
    
    <div class="separator"></div>

    <!-- Liquidación de IVA -->
    <div style="font-size: 10px;">
        <div class="text-center bold" style="margin-bottom: 3px;">Liquidación de IVA:</div>
        <div style="display: flex; justify-content: space-between;">
            <span>(5%): {{ number_format($purchase->tax_5 ?? 0, 0, ',', '.') }}</span>
            <span>(10%): {{ number_format($purchase->tax_10 ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="bold text-right" style="margin-top: 3px;">Total IVA: {{ number_format($purchase->tax ?? 0, 0, ',', '.') }} Gs</div>
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
