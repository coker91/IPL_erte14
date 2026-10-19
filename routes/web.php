<?php

use App\Http\Controllers\PaymentProofController;
use App\Services\DashboardData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request, DashboardData $data) {
    abort_unless($request->user()->is_active, 403);
    $validated = $request->validate(['year' => 'nullable|integer|between:2000,2100', 'q' => 'nullable|string|max:100']);

    return view('dashboard', $data->build((int) ($validated['year'] ?? today()->year), $validated['q'] ?? ''));
})->middleware('auth')->name('home');
Route::get('/proofs/{proof}', PaymentProofController::class)->middleware('auth')->name('proofs.show');
