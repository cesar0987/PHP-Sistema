<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Compra #{{ $purchase->invoice_number ?? $receipt->number }}</title>
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

        .empresa-nombre { font-size: 20px; font-weight: 700; color: #1e6b5a; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 2px; }
        .empresa-datos { font-size: 9px; color: #555; margin-top: 6px; line-height: 1.5; }

        .doc-box { width: 220px; text-align: center; border: 2px solid #1e6b5a; padding: 0; }
        .doc-titulo { background: #1e6b5a; color: #fff; font-size: 12px; font-weight: 700; padding: 6px 0; letter-spacing: 2px; }
        .doc-subtitulo { font-size: 9px; color: #fff; background: #1e6b5a; padding-bottom: 4px; font-weight: 400; }
        .doc-numero { font-size: 12px; font-weight: 700; color: #1e6b5a; padding: 6px 0; }
        .doc-extra { font-size: 8px; color: #666; padding-bottom: 5px; }

        .header-divider { border: none; border-top: 3px solid #1e6b5a; margin: 12px 0 15px 0; }

        .stamp { display: inline-block; border: 2px solid #1e6b5a; color: #1e6b5a; font-size: 9px; font-weight: 700; padding: 2px 10px; letter-spacing: 1px; text-transform: uppercase; margin-left: 10px; }

        /* ===== INFO SECTION ===== */
        .info-section { margin-bottom: 15px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 8px; font-size: 10px; }
        .info-table .label { font-weight: 700; color: #444; width: 120px; background: #eef6f3; border: 1px solid #d0e5de; }
        .info-table .value { border: 1px solid #d0e5de; }

        /* ===== ITEMS TABLE ===== */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .items-table thead th {
            background: #1e6b5a;
            color: #fff;
            padding: 7px 8px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            border: 1px solid #175a4b;
        }
        .items-table tbody td {
            padding: 5px 8px;
            border: 1px solid #d0e5de;
            font-size: 10px;
        }
        .items-table tbody tr:nth-child(even) td { background: #f5faf8; }
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
            border-top: 2px solid #1e6b5a;
            font-size: 13px;
            font-weight: 700;
            color: #1e6b5a;
            padding-top: 6px;
        }

        .notes-box { background: #f5faf8; border: 1px solid #d0e5de; padding: 8px; font-size: 9px; color: #555; border-radius: 3px; }
        .notes-title { font-weight: 700; color: #444; margin-bottom: 3px; }

        /* ===== FOOTER ===== */
        .footer { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; }
        .footer-info { font-size: 8px; color: #999; text-align: center; line-height: 1.6; }
        .footer-label { font-size: 9px; color: #666; font-weight: 600; text-align: center; margin-bottom: 5px; }
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
                            <div class="doc-box">
                                <div class="doc-titulo">COMPROBANTE DE COMPRA</div>
                                <div class="doc-numero">N° {{ $receipt->number }}</div>
                                @if($purchase->invoice_number)
                                <div class="doc-extra">Factura Prov.: {{ $purchase->invoice_number }}</div>
                                @endif
                                @if($purchase->timbrado)
                                <div class="doc-extra">Timbrado: {{ $purchase->timbrado }}</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr class="header-divider">

    {{-- ===== DATOS DE LA COMPRA ===== --}}
    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="label">Fecha:</td>
                <td class="value">{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                <td class="label">Condición:</td>
                <td class="value">{{ ucfirst($purchase->condition ?? 'Contado') }}</td>
            </tr>
            <tr>
                <td class="label">Proveedor:</td>
                <td class="value">{{ $purchase->supplier->name ?? '-' }}</td>
                <td class="label">RUC Prov.:</td>
                <td class="value">{{ $purchase->supplier->ruc ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Sucursal:</td>
                <td class="value">{{ $purchase->branch->name ?? '-' }}</td>
                <td class="label">Almacén:</td>
                <td class="value">{{ $purchase->warehouse->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Estado:</td>
                <td class="value">
                    @php
                        $statusLabel = match($purchase->status) {
                            'received' => 'Recibido',
                            'pending' => 'Pendiente',
                            'cancelled' => 'Cancelado',
                            default => $purchase->status,
                        };
                    @endphp
                    {{ $statusLabel }}
                    <span class="stamp">{{ $statusLabel }}</span>
                </td>
                <td class="label">Responsable:</td>
                <td class="value">{{ $purchase->user->name ?? '-' }}</td>
            </tr>
            @if($purchase->cdc)
            <tr>
                <td class="label">CDC:</td>
                <td class="value" colspan="3" style="font-family: monospace; font-size: 9px;">{{ $purchase->cdc }}</td>
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
            @foreach($purchase->items as $index => $item)
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

    <table class="totales-table" style="margin-top: 10px; border-top: 1px solid #1e6b5a;">
        <tr>
            <td class="total-label" style="text-align: left;">Suma de Subtotales:</td>
            <td class="total-value">{{ number_format($purchase->subtotal_exenta ?? 0, 0, ',', '.') }}</td>
            <td class="total-value">{{ number_format($purchase->subtotal_5 ?? 0, 0, ',', '.') }}</td>
            <td class="total-value">{{ number_format($purchase->subtotal_10 ?? 0, 0, ',', '.') }}</td>
        </tr>
    </table>

    {{-- ===== TOTALES + NOTAS ===== --}}
    <table class="totales-wrapper">
        <tr>
            <td class="totales-notes">
                @if($purchase->notes)
                <div class="notes-box">
                    <div class="notes-title">Observaciones:</div>
                    {{ $purchase->notes }}
                </div>
                @endif
                <div class="notes-box" style="margin-top: 5px;">
                    <div class="notes-title">Liquidación del IVA:</div>
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td style="font-size: 9px;">(5%):</td>
                            <td style="font-size: 9px; text-align: right;">{{ number_format($purchase->tax_5 ?? 0, 0, ',', '.') }}</td>
                            <td style="font-size: 9px; padding-left: 10px;">(10%):</td>
                            <td style="font-size: 9px; text-align: right;">{{ number_format($purchase->tax_10 ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" style="font-size: 9px; font-weight: bold; text-align: right; padding-top: 3px;">Total IVA:</td>
                            <td style="font-size: 9px; font-weight: bold; text-align: right; padding-top: 3px;">{{ number_format($purchase->tax ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td class="totales-table-wrapper">
                <table class="totales-table">
                    @php
                        $subtotalCalc = $purchase->items->sum('subtotal');
                    @endphp
                    <tr>
                        <td class="total-label">Subtotal Operación:</td>
                        <td class="total-value">{{ number_format($subtotalCalc, 0, ',', '.') }} Gs</td>
                    </tr>
                    @if($purchase->discount > 0)
                    <tr>
                        <td class="total-label">Descuento:</td>
                        <td class="total-value">-{{ number_format($purchase->discount, 0, ',', '.') }} Gs</td>
                    </tr>
                    @endif
                    <tr class="total-final">
                        <td class="total-label">TOTAL PAGO:</td>
                        <td class="total-value">{{ number_format($purchase->total, 0, ',', '.') }} Gs</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== PIE DE PAGINA ===== --}}
    <div class="footer">
        <div class="footer-label">DOCUMENTO INTERNO — NO VÁLIDO COMO FACTURA LEGAL</div>
        <div class="footer-info">
            Responsable: {{ $purchase->user->name ?? '-' }} &nbsp;|&nbsp;
            Sucursal: {{ $purchase->branch->name ?? '-' }} &nbsp;|&nbsp;
            Almacén: {{ $purchase->warehouse->name ?? '-' }}<br>
            Impreso: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
</body>
</html>
