<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserSkillController;
use App\Http\Controllers\MatchController;

// Home
Route::get('/', fn () => redirect()->route('matches.index'));

// Guest-only (login / register)
Route::middleware(['guest'])->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Profile Skills
    Route::get('/profile/skills', [UserSkillController::class, 'edit'])->name('profile.skills.edit');
    Route::post('/profile/skills', [UserSkillController::class, 'update'])->name('profile.skills.update');

    // Matching Search Page
    Route::get('/matches', [MatchController::class, 'index'])->name('matches.index');
});
