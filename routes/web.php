<?php

use App\Http\Controllers\ComprobanteVentaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/ventas/{venta}/comprobante', ComprobanteVentaController::class)
    ->middleware('auth')
    ->name('ventas.comprobante');
