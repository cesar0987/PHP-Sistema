<?php

namespace App\Http\Controllers;

use App\Services\ProductImportService;
use App\Services\StockImportService;
use Illuminate\Http\Response;

class ProductImportController extends Controller
{
    public function template(): Response
    {
        $csv = ProductImportService::buildTemplate();

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_importacion_productos.csv"',
        ]);
    }

    public function stockTemplate(): Response
    {
        $csv = StockImportService::buildTemplate();

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_actualizacion_stock.csv"',
        ]);
    }
}
