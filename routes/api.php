<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MeetingPointController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/calculate', [MeetingPointController::class, 'calculate']);
Route::post('/find', [MeetingPointController::class, 'find']);
Route::post('/share', [MeetingPointController::class, 'share']);
Route::get('/plan/{id}/status', [MeetingPointController::class, 'getTrackerStatus']);
Route::post('/plan/{id}/status', [MeetingPointController::class, 'updateTrackerStatus']);
