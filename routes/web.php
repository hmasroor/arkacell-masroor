<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/tenant/switch', function (Request $request) {
    $validated = $request->validate([
        'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
    ]);
    $request->session()->put('tenant_id', $validated['tenant_id']);

    return back();
})->name('tenant.switch');
