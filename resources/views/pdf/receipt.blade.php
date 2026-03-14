<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recibo #{{ $receipt->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; width: 210mm; margin: 0 auto; padding: 15mm 20mm; color: #333; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }

        .membrete { border-bottom: 3px solid #D97706; padding-bottom: 15px; margin-bottom: 25px; }
        .membrete-empresa { font-size: 22px; font-weight: bold; color: #D97706; text-transform: uppercase; letter-spacing: 2px; }
        .membrete-slogan { font-size: 10px; color: #666; margin-top: 2px; }
        .membrete-datos { font-size: 10px; color: #555; margin-top: 8px; }

        .recibo-titulo { font-size: 18px; font-weight: bold; color: #D97706; text-align: center; margin-bottom: 5px; }
        .recibo-numero { font-size: 14px; text-align: center; margin-bottom: 20px; color: #555; }

        .info-box { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .info-box p { margin-bottom: 5px; }

        table { width: 100%; border-collapse: collapse; }
        .detail-table th { background: #D97706; color: #fff; padding: 8px 10px; text-align: left; }
        .detail-table td { padding: 8px 10px; border-bottom: 1px solid #eee; }

        .firma { margin-top: 60px; }
        .firma-line { border-top: 1px solid #000; padding-top: 5px; width: 200px; text-align: center; display: inline-block; }

        .footer { margin-top: 40px; border-top: 1px solid #ddd; padding-top: 10px; text-align: center; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    {{-- Membrete --}}
    <div class="membrete">
        <div class="membrete-empresa">Terracota Construcciones</div>
        <div class="membrete-slogan">Materiales de construccion y ferreteria</div>
        <div class="membrete-datos">
            RUC: {{ $company->ruc ?? '-' }} |
            {{ $company->address ?? '' }} |
            Tel: {{ $company->phone ?? '' }}
        </div>
    </div>

    <div class="recibo-titulo">RECIBO DE PAGO</div>
    <div class="recibo-numero">N° {{ $receipt->number }}</div>

    {{-- Info de la transaccion --}}
    <div class="info-box">
        <p><strong>Fecha:</strong> {{ $sale->sale_date->format('d/m/Y') }}</p>
        <p><strong>Recibi de:</strong> {{ $sale->customer->name ?? 'Consumidor Final' }}</p>
        <p><strong>RUC / CI:</strong> {{ $sale->customer->document ?? '-' }}</p>
        <p><strong>La suma de:</strong> <span class="bold" style="font-size: 14px; color: #D97706;">{{ number_format($sale->total, 0, ',', '.') }} Guaranies</span></p>
        <p><strong>En concepto de:</strong> Venta de materiales</p>
    </div>

    {{-- Detalle --}}
    <table class="detail-table">
        <thead>
            <tr>
                <th>Concepto</th>
                <th style="text-align: right; width: 150px;">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Venta #{{ $sale->id }} - {{ $sale->items->count() }} producto(s)</td>
                <td class="text-right bold">{{ number_format($sale->total, 0, ',', '.') }} Gs</td>
            </tr>
        </tbody>
    </table>

    {{-- Firmas --}}
    <div class="firma">
        <table>
            <tr>
                <td style="width: 50%; text-align: center;">
                    <div class="firma-line">Firma del Cliente</div>
                </td>
                <td style="width: 50%; text-align: center;">
                    <div class="firma-line">Firma y Sello</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Terracota Construcciones - Gracias por su preferencia</p>
    </div>
</body>
</html>
