<?php

use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\ProductImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});
Route::get('/pos/cash-registers/{cashRegister}/print', [CashRegisterController::class, 'print'])->name('cash-register.print')->middleware('auth');
Route::get('/products/import/template', [ProductImportController::class, 'template'])->name('products.import.template')->middleware('auth');
Route::get('/stock/import/template', [ProductImportController::class, 'stockTemplate'])->name('stock.import.template')->middleware('auth');
