<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Factura #{{ $sale->invoice_number ?? $receipt->number }}</title>
    <style>
        @page { margin: 15mm 18mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10px; color: #2d2d2d; line-height: 1.4; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }

        /* ===== HEADER / MEMBRETE ===== */
        .header-table { width: 100%; margin-bottom: 0; }
        .header-table td { vertical-align: top; }

        .logo-cell { width: 80px; padding-right: 12px; }
        .logo-cell img { max-width: 70px; max-height: 70px; }

        .empresa-nombre { font-size: 20px; font-weight: 700; color: #B45309; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 2px; }
        .empresa-slogan { font-size: 9px; color: #777; font-style: italic; }
        .empresa-datos { font-size: 9px; color: #555; margin-top: 6px; line-height: 1.5; }

        .factura-box { width: 200px; text-align: center; border: 2px solid #B45309; padding: 0; }
        .factura-titulo { background: #B45309; color: #fff; font-size: 14px; font-weight: 700; padding: 6px 0; letter-spacing: 3px; }
        .factura-numero { font-size: 12px; font-weight: 700; color: #B45309; padding: 6px 0; }
        .factura-timbrado { font-size: 8px; color: #666; padding-bottom: 5px; }

        .header-divider { border: none; border-top: 3px solid #B45309; margin: 12px 0 15px 0; }

        /* ===== INFO SECTION ===== */
        .info-section { margin-bottom: 15px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 8px; font-size: 10px; }
        .info-table .label { font-weight: 700; color: #444; width: 110px; background: #f5f0eb; border: 1px solid #e5ddd3; }
        .info-table .value { border: 1px solid #e5ddd3; }

        /* ===== ITEMS TABLE ===== */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .items-table thead th {
            background: #B45309;
            color: #fff;
            padding: 7px 8px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            border: 1px solid #9a4408;
        }
        .items-table tbody td {
            padding: 5px 8px;
            border: 1px solid #e5ddd3;
            font-size: 10px;
        }
        .items-table tbody tr:nth-child(even) td { background: #faf8f5; }
        .items-table tbody tr:hover td { background: #f5f0eb; }
        .item-number { text-align: center; color: #888; width: 30px; }
        .item-qty { text-align: center; width: 40px; }
        .item-exenta { text-align: right; width: 70px; }
        .item-iva5 { text-align: right; width: 70px; }
        .item-iva10 { text-align: right; width: 70px; }
        .item-subtotal { text-align: right; width: 80px; font-weight: 600; }

        /* ===== TOTALES ===== */
        .totales-wrapper { margin-top: 15px; width: 100%; }
        .totales-notes { width: 40%; vertical-align: top; padding-right: 20px; }
        .totales-table-wrapper { width: 60%; vertical-align: top; }
        .totales-table { width: 100%; border-collapse: collapse; }
        .totales-table td { padding: 4px 8px; font-size: 10px; }
        .totales-table .total-label { text-align: right; color: #555; font-weight: 600; }
        .totales-table .total-value { text-align: right; width: 120px; }
        .totales-table .total-final td {
            border-top: 2px solid #B45309;
            font-size: 13px;
            font-weight: 700;
            color: #B45309;
            padding-top: 6px;
        }

        .notes-box { background: #faf8f5; border: 1px solid #e5ddd3; padding: 8px; font-size: 9px; color: #555; border-radius: 3px; }
        .notes-title { font-weight: 700; color: #444; margin-bottom: 3px; }

        /* ===== FOOTER ===== */
        .footer { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; }
        .footer-info { font-size: 8px; color: #999; text-align: center; line-height: 1.6; }
        .footer-copies { font-size: 9px; color: #666; font-weight: 600; text-align: center; margin-bottom: 5px; }
    </style>
</head>
<body>
    {{-- ===== MEMBRETE ===== --}}
    <table class="header-table">
        <tr>
            <td>
                <table>
                    <tr>
                        @if(!empty($company->logo))
                        <td class="logo-cell">
                            <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo">
                        </td>
                        @endif
                        <td>
                            <div class="empresa-nombre">{{ $company->name ?? 'Mi Empresa' }}</div>
                            <div class="empresa-datos">
                                @if($company->ruc ?? false)RUC: {{ $company->ruc }}<br>@endif
                                @if($company->address ?? false){{ $company->address }}<br>@endif
                                @if($company->phone ?? false)Tel: {{ $company->phone }}@endif
                                @if($company->email ?? false) | {{ $company->email }}@endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="text-align: right;">
                <table style="margin-left: auto;">
                    <tr>
                        <td>
                            <div class="factura-box">
                                <div class="factura-titulo">FACTURA</div>
                                <div class="factura-numero">N° {{ $sale->invoice_number ?? $receipt->number }}</div>
                                @if($sale->timbrado)
                                <div class="factura-timbrado">Timbrado: {{ $sale->timbrado }}</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr class="header-divider">

    {{-- ===== DATOS DE LA VENTA ===== --}}
    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="label">Fecha:</td>
                <td class="value">{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                <td class="label">Condición:</td>
                <td class="value">{{ ucfirst($sale->payment_method ?? 'Contado') }}</td>
            </tr>
            <tr>
                <td class="label">Cliente:</td>
                <td class="value">{{ $sale->customer->name ?? 'Consumidor Final' }}</td>
                <td class="label">RUC / CI:</td>
                <td class="value">{{ $sale->customer->document ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Dirección:</td>
                <td class="value" colspan="3">{{ $sale->customer->address ?? '-' }}</td>
            </tr>
            @if($sale->cdc)
            <tr>
                <td class="label">CDC:</td>
                <td class="value" colspan="3" style="font-family: monospace; font-size: 9px;">{{ $sale->cdc }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- ===== DETALLE DE PRODUCTOS ===== --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="item-number">#</th>
                <th class="item-qty">Cant.</th>
                <th>Descripción</th>
                <th class="item-exenta">Exentas</th>
                <th class="item-iva5">5%</th>
                <th class="item-iva10">10%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $index => $item)
            @php
                $itemTaxPct = $item->tax_percentage ?? 10;
                $exenta = $itemTaxPct == 0 ? $item->subtotal : 0;
                $iva5 = $itemTaxPct == 5 ? $item->subtotal : 0;
                $iva10 = $itemTaxPct == 10 ? $item->subtotal : 0;
            @endphp
            <tr>
                <td class="item-number">{{ $index + 1 }}</td>
                <td class="item-qty">{{ $item->quantity }}</td>
                <td>
                    {{ $item->productVariant->product->name }}
                    @if($item->productVariant->color) <span style="color:#888;">- {{ $item->productVariant->color }}</span> @endif
                    @if($item->productVariant->size) <span style="color:#888;">- {{ $item->productVariant->size }}</span> @endif
                </td>
                <td class="item-exenta">{{ $exenta > 0 ? number_format($exenta, 0, ',', '.') : '' }}</td>
                <td class="item-iva5">{{ $iva5 > 0 ? number_format($iva5, 0, ',', '.') : '' }}</td>
                <td class="item-iva10">{{ $iva10 > 0 ? number_format($iva10, 0, ',', '.') : '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales-table" style="margin-top: 10px; border-top: 1px solid #B45309;">
        <tr>
            <td class="total-label" style="text-align: left;">Suma de Subtotales:</td>
            <td class="total-value">{{ number_format($sale->subtotal_exenta ?? 0, 0, ',', '.') }}</td>
            <td class="total-value">{{ number_format($sale->subtotal_5 ?? 0, 0, ',', '.') }}</td>
            <td class="total-value">{{ number_format($sale->subtotal_10 ?? 0, 0, ',', '.') }}</td>
        </tr>
    </table>

    {{-- ===== TOTALES + NOTAS ===== --}}
    <table class="totales-wrapper">
        <tr>
            <td class="totales-notes">
                @if($sale->notes)
                <div class="notes-box">
                    <div class="notes-title">Observaciones:</div>
                    {{ $sale->notes }}
                </div>
                @endif
                <div class="notes-box" style="margin-top: 5px;">
                    <div class="notes-title">Liquidación del IVA:</div>
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td style="font-size: 9px;">(5%):</td>
                            <td style="font-size: 9px; text-align: right;">{{ number_format($sale->tax_5 ?? 0, 0, ',', '.') }}</td>
                            <td style="font-size: 9px; padding-left: 10px;">(10%):</td>
                            <td style="font-size: 9px; text-align: right;">{{ number_format($sale->tax_10 ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" style="font-size: 9px; font-weight: bold; text-align: right; padding-top: 3px;">Total IVA:</td>
                            <td style="font-size: 9px; font-weight: bold; text-align: right; padding-top: 3px;">{{ number_format($sale->tax ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td class="totales-table-wrapper">
                <table class="totales-table">
                    <tr>
                        <td class="total-label">Total Operación:</td>
                        <td class="total-value">{{ number_format($sale->subtotal, 0, ',', '.') }} Gs</td>
                    </tr>
                    @if($sale->discount > 0)
                    <tr>
                        <td class="total-label">Descuento:</td>
                        <td class="total-value">-{{ number_format($sale->discount, 0, ',', '.') }} Gs</td>
                    </tr>
                    @endif
                    <tr class="total-final">
                        <td class="total-label">TOTAL A PAGAR:</td>
                        <td class="total-value">{{ number_format($sale->total, 0, ',', '.') }} Gs</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== QR SIFEN ===== --}}
    @if(!empty($qr_image))
    <table style="width: 100%; margin-top: 15px; border-top: 1px dashed #ccc; padding-top: 10px;">
        <tr>
            <td style="vertical-align: middle; width: 70px;">
                <img src="{{ $qr_image }}" style="width: 65px; height: 65px;" alt="QR SET">
            </td>
            <td style="vertical-align: middle; padding-left: 10px; font-size: 8px; color: #666;">
                <strong style="font-size: 9px; color: #444;">Consulta electrónica SET</strong><br>
                Escanee el código QR para verificar este<br>
                documento en el portal de la SET Paraguay.<br>
                @if($sale->cdc)
                <span style="font-family: monospace; font-size: 7px; color: #888;">CDC: {{ $sale->cdc }}</span>
                @endif
            </td>
        </tr>
    </table>
    @endif

    {{-- ===== PIE DE PAGINA ===== --}}
    <div class="footer">
        <div class="footer-copies">Original: Cliente &nbsp;|&nbsp; Duplicado: Archivo</div>
        <div class="footer-info">
            Vendedor: {{ $sale->user->name ?? '-' }} &nbsp;|&nbsp;
            Sucursal: {{ $sale->branch->name ?? '-' }} &nbsp;|&nbsp;
            Impreso: {{ now()->format('d/m/Y H:i:s') }}<br>
            {{ $company->name ?? '' }} — Gracias por su preferencia
        </div>
    </div>
</body>
</html>
