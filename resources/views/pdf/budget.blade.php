<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Presupuesto #{{ $receipt->number }}</title>
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
        <div class="empresa" style="font-size: 16px; margin-bottom: 5px;">PRESUPUESTO</div>
        <div class="empresa">TERRACOTA</div>
        <div style="font-size: 9px; margin-top: 3px;">Materiales y Ferreteria</div>
        <div style="font-size: 9px;">RUC: {{ $company->ruc ?? '-' }}</div>
        <div style="font-size: 9px;">Tel: {{ $company->phone ?? '' }}</div>
    </div>

    <div class="separator"></div>

    <div>
        <div>Presupuesto #: {{ $receipt->number }}</div>
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
                <th style="text-align: left; width: 15%;">Cant</th>
                <th style="text-align: left; width: 50%;">Producto</th>
                <th class="text-right" style="width: 35%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td style="vertical-align: top;">{{ $item->quantity }}</td>
                <td style="word-break: break-all;">{{ $item->productVariant->product->name }}</td>
                <td class="text-right" style="vertical-align: top;">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="separator"></div>

    <div>
        @if($sale->discount > 0)
        <div class="text-right">Subtotal: {{ number_format($sale->subtotal, 0, ',', '.') }} Gs</div>
        <div class="text-right">Descuento: -{{ number_format($sale->discount, 0, ',', '.') }} Gs</div>
        @endif
        <div class="text-right bold" style="font-size: 13px;">Total Estimado: {{ number_format($sale->total, 0, ',', '.') }} Gs</div>
    </div>
    
    <div class="separator"></div>

    <div class="text-center" style="margin-top: 8px;">
        <p class="bold" style="font-size: 10px;">Validez sujeta a variaciones del mercado.</p>
        <p style="font-size: 9px; margin-top: 5px;">Este documento NO es válido para crédito fiscal ni comprobante de venta.</p>
        <p style="font-size: 8px; margin-top: 5px;">Impreso: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
