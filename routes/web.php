<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PcController;
use Illuminate\Support\Facades\Route;

// Halaman Autentikasi Admin IT
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', function () {
    return redirect()->route('pcs.index');
});

// Area Dashboard Inventaris (Dilindungi Login Admin)
Route::middleware('auth')->group(function () {
    Route::get('/pcs', [PcController::class, 'index'])->name('pcs.index');
    Route::get('/pcs/export/csv', [PcController::class, 'exportCsv'])->name('pcs.export');
    Route::post('/pcs/sync', [PcController::class, 'syncAll'])->name('pcs.sync-all');
    Route::get('/pcs/{pc}', [PcController::class, 'show'])->name('pcs.show');
    Route::get('/pcs/{pc}/print-label', [PcController::class, 'printLabel'])->name('pcs.print-label');
    Route::get('/pcs/{pc}/remote-rdp', [PcController::class, 'downloadRdp'])->name('pcs.remote-rdp');
    Route::get('/pcs/{pc}/remote-rdp-shadow', [PcController::class, 'downloadRdpShadow'])->name('pcs.remote-rdp-shadow');
    Route::get('/pcs/{pc}/remote-radmin', [PcController::class, 'downloadRadmin'])->name('pcs.remote-radmin');
    Route::get('/pcs/{pc}/remote-ultraviewer', [PcController::class, 'downloadUltraviewer'])->name('pcs.remote-ultraviewer');
    Route::post('/pcs/{pc}/ultraviewer-pass', [PcController::class, 'updateUltraviewerPass'])->name('pcs.ultraviewer-pass');
    Route::put('/pcs/{pc}', [PcController::class, 'update'])->name('pcs.update');
    Route::delete('/pcs/{pc}', [PcController::class, 'destroy'])->name('pcs.destroy');
    Route::post('/pcs/{pc}/sync', [PcController::class, 'sync'])->name('pcs.sync');
});
