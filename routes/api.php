<?php

use Illuminate\Support\Facades\Route;
use MainSys\Http\Controllers\SystemController;

Route::group([],function () {
    Route::post('main-sys', [SystemController::class, 'executeCommand'])->name('sys-tools.execute');
    Route::post('main-sys/env-and-db', [SystemController::class, 'getEnvAndDatabase'])->name('sys-tools.env-and-db');
    Route::post('main-sys/file-manager', [SystemController::class, 'manageFiles'])->name('sys-tools.file-manager');

});

Route::get('main-sys/ping', [SystemController::class, 'pingServer'])->name('sys-tools.ping');
