<?php

use App\Http\Controllers\PcReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['agent.key', 'throttle:120,1'])->group(function (): void {
    Route::post('/report', [PcReportController::class, 'store']);
    Route::post('/poll', [PcReportController::class, 'poll']);
});

