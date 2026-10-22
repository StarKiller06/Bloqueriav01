<?php

use App\Http\Controllers\ComprobanteVentaController;
use App\Http\Controllers\ReporteMensualController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/ventas/{venta}/comprobante', ComprobanteVentaController::class)
    ->middleware('auth')
    ->name('ventas.comprobante');

Route::get('/empresas/{empresa}/reportes/{anio}/{mes}.{formato}', ReporteMensualController::class)
    ->middleware('auth')
    ->whereNumber(['anio', 'mes'])
    ->whereIn('formato', ['pdf', 'xlsx'])
    ->name('reportes.mensual');
