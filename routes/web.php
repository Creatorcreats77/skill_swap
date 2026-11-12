<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SwapRequestController;
use App\Http\Controllers\UserSkillController;
use Illuminate\Support\Facades\Route;

// Home
Route::get('/', fn () => redirect()->route('matches.index'));

// Guest-only (login / register)
Route::middleware(['guest'])->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated users
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/users/{user}', [ProfileController::class, 'show'])
        ->whereNumber('user')
        ->name('users.show');

    // Profile Skills
    Route::get('/profile/skills', [UserSkillController::class, 'index'])->name('profile.skills.index');
    Route::get('/profile/skills/create', [UserSkillController::class, 'create'])->name('profile.skills.create');
    Route::get('/profile/skills/edit', [UserSkillController::class, 'edit'])->name('profile.skills.edit');
    Route::put('/profile/skills', [UserSkillController::class, 'update'])->name('profile.skills.update');
    Route::post('/profile/skills', [UserSkillController::class, 'store'])->name('profile.skills.store');
    Route::get('/profile/skills/{skill}', [UserSkillController::class, 'show'])
        ->whereNumber('skill')
        ->name('profile.skills.show');
    Route::delete('/profile/skills/{skill}', [UserSkillController::class, 'destroy'])
        ->whereNumber('skill')
        ->name('profile.skills.destroy');

    // Matching Search Page
    Route::get('/matches', [MatchController::class, 'index'])->name('matches.index');

    // Swap Requests
    Route::get('/swap-requests', [SwapRequestController::class, 'index'])->name('swap.request.index');
    Route::post('/swap-requests', [SwapRequestController::class, 'store'])->name('swap.request.store');
    Route::get('/swap-requests/{swapRequest}', [SwapRequestController::class, 'show'])
        ->whereNumber('swapRequest')
        ->name('swap.request.show');
    Route::patch('/swap-requests/{swapRequest}', [SwapRequestController::class, 'update'])
        ->whereNumber('swapRequest')
        ->name('swap.request.update');

    // Direct messages
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{user}', [MessageController::class, 'show'])
        ->whereNumber('user')
        ->name('messages.show');
    Route::post('/messages/{user}', [MessageController::class, 'store'])
        ->whereNumber('user')
        ->middleware('throttle:30,1')
        ->name('messages.store');
});
