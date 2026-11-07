<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Vérifie que l'API et la base de données répondent
    Route::get('/health', function () {
        DB::select('select 1');

        return response()->json([
            'status' => 'ok',
            'database' => DB::connection()->getDatabaseName(),
        ]);
    });

    // Utilisateur connecté (route fournie par Sanctum, protégée par token)
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');
});
