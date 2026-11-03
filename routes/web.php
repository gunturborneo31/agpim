<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AgendaRevisionController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PublicProposalController;
use App\Http\Controllers\PublicAgendaController;
use App\Http\Controllers\ScheduleCheckController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaBlastController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/usulan-tanpa-login', [PublicProposalController::class, 'create'])->name('public-proposals.create');
Route::post('/usulan-tanpa-login', [PublicProposalController::class, 'store'])->name('public-proposals.store');

// Cek bentrok jadwal saat mengisi form pengajuan (peringatan saja, tanpa login).
Route::get('/cek-jadwal', ScheduleCheckController::class)->middleware('throttle:60,1')->name('schedule.check');

// Link revisi sekali pakai dari WhatsApp (tanpa login).
Route::middleware('throttle:30,1')->group(function (): void {
    Route::get('/revisi/{token}', [AgendaRevisionController::class, 'edit'])->name('revisions.edit');
    Route::put('/revisi/{token}', [AgendaRevisionController::class, 'update'])->name('revisions.update');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/agenda-internal', PublicAgendaController::class)->name('public-agendas.index');
    Route::get('/account/password', [AccountController::class, 'editPassword'])->name('account.password.edit');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::get('/agendas', [AgendaController::class, 'index'])->name('agendas.index');
    // Harus di atas /agendas/{agenda} supaya "incoming" tidak dianggap ID agenda.
    Route::get('/agendas/incoming', [AgendaController::class, 'incoming'])->name('agendas.incoming');
    Route::get('/agendas/{agenda}', [AgendaController::class, 'show'])->name('agendas.show');
    Route::get('/agendas/{agenda}/edit', [AgendaController::class, 'edit'])
        ->middleware('role:opd')
        ->name('agendas.edit');
    Route::put('/agendas/{agenda}', [AgendaController::class, 'update'])
        ->middleware('role:opd')
        ->name('agendas.update');
    Route::post('/agendas', [AgendaController::class, 'store'])
        ->middleware('role:opd,verifikator')
        ->name('agendas.store');
    Route::post('/agendas/{agenda}/verify', [AgendaController::class, 'verify'])
        ->middleware('role:super_admin,admin_prokopim,bupati,wakil_bupati,sekda,verifikator')
        ->name('agendas.verify');
    Route::post('/agendas/{agenda}/whatsapp-status', [AgendaController::class, 'whatsappStatus'])
        ->middleware('role:super_admin,admin_prokopim,bupati,wakil_bupati,sekda,verifikator')
        ->name('agendas.whatsapp-status');
    Route::post('/agendas/{agenda}/send-invitation', [AgendaController::class, 'sendInvitation'])
        ->middleware('role:super_admin,admin_prokopim,bupati,wakil_bupati,sekda')
        ->name('agendas.send-invitation');
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('role:super_admin')
        ->name('users.index');
    Route::get('/wa-blasts', [WaBlastController::class, 'index'])
        ->middleware('role:super_admin,admin_prokopim')
        ->name('wa-blasts.index');
    Route::put('/wa-blasts/settings', [WaBlastController::class, 'updateSettings'])
        ->middleware('role:super_admin,admin_prokopim')
        ->name('wa-blasts.settings.update');
    Route::post('/wa-blasts', [WaBlastController::class, 'store'])
        ->middleware('role:super_admin,admin_prokopim')
        ->name('wa-blasts.store');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
