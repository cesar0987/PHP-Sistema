<?php

use App\Http\Controllers\CashRegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});
Route::get('/pos/cash-registers/{cashRegister}/print', [CashRegisterController::class, 'print'])->name('cash-register.print')->middleware('auth');
