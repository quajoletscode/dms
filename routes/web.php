<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::singleton('/profile', ProfileController::class);
