<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use Barryvdh\DomPDF\Facade\Pdf;

class CashRegisterController extends Controller
{
    public function print(CashRegister $cashRegister)
    {
        $sales = $cashRegister->sales()->with('customer')->orderBy('created_at', 'asc')->get();

        $pdf = Pdf::loadView('pdf.cash_register_report', [
            'cashRegister' => $cashRegister,
            'sales' => $sales,
        ]);

        return $pdf->stream('reporte_caja_'.$cashRegister->id.'.pdf');
    }
}
