<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Factura #{{ $receipt->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; width: 210mm; margin: 0 auto; padding: 15mm 20mm; color: #333; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }

        /* Membrete */
        .membrete { border-bottom: 3px solid #D97706; padding-bottom: 15px; margin-bottom: 20px; }
        .membrete-empresa { font-size: 22px; font-weight: bold; color: #D97706; text-transform: uppercase; letter-spacing: 2px; }
        .membrete-slogan { font-size: 10px; color: #666; margin-top: 2px; }
        .membrete-datos { font-size: 10px; color: #555; margin-top: 8px; }

        /* Factura titulo */
        .factura-titulo { background: #D97706; color: #fff; padding: 8px 15px; font-size: 16px; font-weight: bold; display: inline-block; margin-bottom: 15px; }

        /* Tablas */
        table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 10px; border: 1px solid #ddd; }
        .info-table .label { background: #f9f9f9; font-weight: bold; width: 120px; }
        .items-table { margin-top: 20px; }
        .items-table th { background: #D97706; color: #fff; padding: 8px 10px; text-align: left; font-size: 11px; }
        .items-table td { padding: 6px 10px; border-bottom: 1px solid #eee; }
        .items-table tr:nth-child(even) td { background: #fafafa; }

        /* Totales */
        .totales-table { margin-top: 15px; width: 45%; margin-left: auto; }
        .totales-table td { padding: 5px 10px; }
        .totales-table .total-final { border-top: 2px solid #D97706; font-size: 14px; font-weight: bold; }

        /* Footer */
        .footer { margin-top: 40px; border-top: 1px solid #ddd; padding-top: 10px; text-align: center; font-size: 9px; color: #888; }
        .footer-legal { margin-top: 5px; }
    </style>
</head>
<body>
    {{-- Membrete --}}
    <div class="membrete">
        <table>
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <div class="membrete-empresa">Terracota Construcciones</div>
                    <div class="membrete-slogan">Materiales de construccion y ferreteria</div>
                    <div class="membrete-datos">
                        RUC: {{ $company->ruc ?? '-' }}<br>
                        {{ $company->address ?? '' }}<br>
                        Tel: {{ $company->phone ?? '' }}
                        @if($company->email ?? false) | {{ $company->email }} @endif
                    </div>
                </td>
                <td style="width: 40%; text-align: right; vertical-align: top;">
                    <div class="factura-titulo">FACTURA</div><br>
                    <div style="font-size: 13px; font-weight: bold; color: #D97706;">N° {{ $receipt->number }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Datos de la venta --}}
    <table class="info-table">
        <tr>
            <td class="label">Fecha:</td>
            <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
            <td class="label">Condicion:</td>
            <td>Contado</td>
        </tr>
        <tr>
            <td class="label">Cliente:</td>
            <td>{{ $sale->customer->name ?? 'Consumidor Final' }}</td>
            <td class="label">RUC / CI:</td>
            <td>{{ $sale->customer->document ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Direccion:</td>
            <td colspan="3">{{ $sale->customer->address ?? '-' }}</td>
        </tr>
    </table>

    {{-- Detalle de productos --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 60px;">Cant.</th>
                <th>Descripcion</th>
                <th style="width: 100px; text-align: right;">Precio Unit.</th>
                <th style="width: 100px; text-align: right;">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->productVariant->product->name }}</td>
                <td class="text-right">{{ number_format($item->price, 0, ',', '.') }} Gs</td>
                <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }} Gs</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totales --}}
    <table class="totales-table">
        <tr>
            <td class="text-right">Subtotal:</td>
            <td class="text-right">{{ number_format($sale->subtotal, 0, ',', '.') }} Gs</td>
        </tr>
        @if($sale->discount > 0)
        <tr>
            <td class="text-right">Descuento:</td>
            <td class="text-right">-{{ number_format($sale->discount, 0, ',', '.') }} Gs</td>
        </tr>
        @endif
        @if($sale->tax > 0)
        <tr>
            <td class="text-right">IVA (10%):</td>
            <td class="text-right">{{ number_format($sale->tax, 0, ',', '.') }} Gs</td>
        </tr>
        @endif
        <tr class="total-final">
            <td class="text-right bold total-final">TOTAL:</td>
            <td class="text-right bold total-final">{{ number_format($sale->total, 0, ',', '.') }} Gs</td>
        </tr>
    </table>

    {{-- Pie de pagina --}}
    <div class="footer">
        <p>Original: Cliente | Duplicado: Archivo</p>
        <p class="footer-legal">Terracota Construcciones - Gracias por su preferencia</p>
        <p>Vendedor: {{ $sale->user->name ?? '-' }} | Sucursal: {{ $sale->branch->name ?? '-' }}</p>
    </div>
</body>
</html>
