<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MeetingPointController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/plan/{id}', [MeetingPointController::class, 'viewPlan']);

