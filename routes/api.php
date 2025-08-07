<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\S3ExampleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// public routes
Route::post('/register', [AuthController::class, 'Register']);
Route::post('/login', [AuthController::class, 'Login']);
Route::get('/categories/{id}/projects', [CategoryController::class, 'getProjectsByCategory']);
Route::post('/contact', [ContactController::class, 'store']);
Route::get('/admin/contacts', [ContactController::class, 'index']);


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

// S3 Example Routes (for testing and demonstration)
Route::prefix('s3')->group(function () {
   Route::post('/upload-single', [S3ExampleController::class, 'uploadSingle']);
   Route::post('/upload-multiple', [S3ExampleController::class, 'uploadMultiple']);
   Route::delete('/delete', [S3ExampleController::class, 'deleteFile']);
   Route::get('/file-info', [S3ExampleController::class, 'getFileInfo']);
   Route::get('/list-files', [S3ExampleController::class, 'listFiles']);
   Route::get('/config', [S3ExampleController::class, 'getConfig']);
});
