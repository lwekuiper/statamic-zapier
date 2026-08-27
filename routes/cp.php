<?php

use Illuminate\Support\Facades\Route;
use Lwekuiper\StatamicZapier\Http\Controllers\AddonConfigController;
use Lwekuiper\StatamicZapier\Http\Controllers\FormConfigController;
use Lwekuiper\StatamicZapier\Integration;

Route::name(Integration::HANDLE.'.')->prefix(Integration::HANDLE)->group(function () {
    Route::get('/', [FormConfigController::class, 'index'])->name('index');

    if (Integration::hasMultisite()) {
        Route::get('/edit', [AddonConfigController::class, 'edit'])->name('edit');
        Route::patch('/edit', [AddonConfigController::class, 'update'])->name('update');
    }

    Route::name('form-config.')->group(function () {
        Route::get('/{form}/edit', [FormConfigController::class, 'edit'])->name('edit');
        Route::patch('/{form}', [FormConfigController::class, 'update'])->name('update');
        Route::delete('/{form}', [FormConfigController::class, 'destroy'])->name('destroy');
    });
});
