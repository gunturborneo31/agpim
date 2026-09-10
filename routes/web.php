<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PublicProposalController;
use App\Http\Controllers\PublicAgendaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaBlastController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/agenda-internal', PublicAgendaController::class)->name('public-agendas.index');
Route::get('/usulan-tanpa-login', [PublicProposalController::class, 'create'])->name('public-proposals.create');
Route::post('/usulan-tanpa-login', [PublicProposalController::class, 'store'])->name('public-proposals.store');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/account/password', [AccountController::class, 'editPassword'])->name('account.password.edit');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::get('/agendas', [AgendaController::class, 'index'])->name('agendas.index');
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
    Route::post('/agendas/{agenda}/send-invitation', [AgendaController::class, 'sendInvitation'])
        ->middleware('role:super_admin,admin_prokopim,bupati,wakil_bupati,sekda')
        ->name('agendas.send-invitation');
    Route::get('/agendas/{agenda}/media', [\App\Http\Controllers\AgendaMediaController::class, 'index'])
        ->name('agendas.media.index');
    Route::post('/agendas/{agenda}/media', [\App\Http\Controllers\AgendaMediaController::class, 'store'])
        ->name('agendas.media.store');
    Route::put('/agendas/{agenda}/media/bulk', [\App\Http\Controllers\AgendaMediaController::class, 'bulkUpdate'])
        ->name('agendas.media.bulk-update');
    Route::delete('/agendas/{agenda}/media/bulk', [\App\Http\Controllers\AgendaMediaController::class, 'bulkDestroy'])
        ->name('agendas.media.bulk-destroy');
    Route::put('/agendas/{agenda}/media/{document}', [\App\Http\Controllers\AgendaMediaController::class, 'update'])
        ->name('agendas.media.update');
    Route::delete('/agendas/{agenda}/media/{document}', [\App\Http\Controllers\AgendaMediaController::class, 'destroy'])
        ->name('agendas.media.destroy');
    Route::get('/agendas/{agenda}/draft/{type}', [\App\Http\Controllers\AgendaDraftController::class, 'edit'])
        ->name('agendas.draft.edit');
    Route::put('/agendas/{agenda}/draft/{type}', [\App\Http\Controllers\AgendaDraftController::class, 'update'])
        ->name('agendas.draft.update');
    Route::post('/agendas/{agenda}/prokopim-status', [AgendaController::class, 'updateProkopimStatus'])
        ->middleware('role:admin_prokopim,super_admin')
        ->name('agendas.prokopim.update');
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
