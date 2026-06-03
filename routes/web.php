<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PublicAgendaController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::get('/agendas', [AgendaController::class, 'index'])->name('agendas.index');
Route::get('/agendas/{agenda}', [AgendaController::class, 'show'])->name('agendas.show');
Route::post('/agendas', [AgendaController::class, 'store'])
    ->middleware(['auth', 'role:opd,admin_prokopim,super_admin'])
    ->name('agendas.store');
Route::get('/agenda-internal', PublicAgendaController::class)->name('public-agendas.index');
