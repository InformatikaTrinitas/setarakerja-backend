<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JobListingController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NeedController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SkillController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/jobs', [JobListingController::class, 'index']);
Route::get('/jobs/{job}', [JobListingController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/photo', [ProfileController::class, 'photo']);

    Route::post('/jobs', [JobListingController::class, 'store']);
    Route::post('/jobs/{job}/apply', [ApplicationController::class, 'store']);

    Route::get('/applications/mine', [ApplicationController::class, 'mine']);
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/applications/{application}', [ApplicationController::class, 'show']);
    Route::patch('/applications/{application}/status', [ApplicationController::class, 'updateStatus']);
    Route::post('/applications/{application}/reveal', [ApplicationController::class, 'reveal']);

    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);

    Route::get('/needs', [NeedController::class, 'index']);
    Route::post('/needs', [NeedController::class, 'store']);
    Route::put('/needs/{need}', [NeedController::class, 'update']);
    Route::patch('/needs/{need}', [NeedController::class, 'update']);
    Route::delete('/needs/{need}', [NeedController::class, 'destroy']);

    Route::get('/portfolios', [PortfolioController::class, 'index']);
    Route::post('/portfolios', [PortfolioController::class, 'store']);
    Route::delete('/portfolios/{portfolio}', [PortfolioController::class, 'destroy']);

    Route::get('/skills', [SkillController::class, 'index']);
    Route::post('/skills', [SkillController::class, 'store']);
    Route::patch('/skills/{skill}', [SkillController::class, 'update']);
    Route::put('/skills/{skill}', [SkillController::class, 'update']);
    Route::post('/skills/{skill}/verify', [SkillController::class, 'verify']);
    Route::delete('/skills/{skill}', [SkillController::class, 'destroy']);
});
