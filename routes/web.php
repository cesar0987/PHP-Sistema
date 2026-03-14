<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});
Route::get('/pos/cash-registers/{cashRegister}/print', [\App\Http\Controllers\CashRegisterController::class, 'print'])->name('cash-register.print')->middleware('auth');
