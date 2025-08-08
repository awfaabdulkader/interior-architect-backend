<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SkillController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// public routes
Route::post('/register', [AuthController::class, 'Register']);
Route::post('/login', [AuthController::class, 'Login']);
Route::get('/categories/{id}/projects', [CategoryController::class, 'getProjectsByCategory']);
Route::post('/contact', [ContactController::class, 'store']);
Route::get('/admin/contacts', [ContactController::class, 'index']);

// Image serving routes (public)
Route::get('/images/{path}', [ImageController::class, 'show'])->name('api.images.show')->where('path', '.*');
Route::get('/images/{path}/info', [ImageController::class, 'info'])->where('path', '.*');


// protected routes
Route::middleware('auth:sanctum')->group(function () {
   Route::post('/logout', [AuthController::class, 'Logout']);
   Route::apiResource("/category", CategoryController::class);
   Route::apiResource('/experience', ExperienceController::class);
   Route::apiResource('/projects', ProjectController::class);
   Route::apiResource('/education', EducationController::class);
   Route::apiResource('/skills', SkillController::class);
   Route::apiResource('/cvs', CvController::class);
});
