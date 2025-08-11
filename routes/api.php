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
Route::get('/category/{id}/projects', [CategoryController::class, 'getProjectsByCategory']);
Route::post('/contact', [ContactController::class, 'store']);
Route::get('/admin/contacts', [ContactController::class, 'index']);
Route::delete('/admin/contacts/{id}', [ContactController::class, 'destroy']);

// Public GET routes for viewing content
Route::get('/category', [CategoryController::class, 'index']);
Route::get('/category/{id}', [CategoryController::class, 'show']);
Route::post('/category/images', [CategoryController::class, 'loadCategoryImages']); // Lazy load images
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{id}', [ProjectController::class, 'show']);
Route::post('/projects/images', [ProjectController::class, 'loadProjectImages']); // Lazy load images
Route::get('/projects/test/images', [ProjectController::class, 'testImageServing']); // Test image serving
Route::get('/projects/test/performance', [ProjectController::class, 'testPerformanceAndBase64']); // Test performance and base64
Route::get('/skills', [SkillController::class, 'index']);
Route::get('/skills/{id}', [SkillController::class, 'show']);
Route::post('/skills/logos', [SkillController::class, 'loadSkillLogos']); // Lazy load logos
Route::get('/education', [EducationController::class, 'index']);
Route::get('/education/{id}', [EducationController::class, 'show']);
Route::get('/experience', [ExperienceController::class, 'index']);
Route::get('/experience/{id}', [ExperienceController::class, 'show']);

// Public CV routes
Route::get('/cv/active', [CvController::class, 'getActiveCV']);
Route::get('/cvs', [CvController::class, 'index']);
Route::get('/cvs/{id}', [CvController::class, 'show']);
Route::get('/cvs/{id}/download/{language}', [CvController::class, 'downloadCV']);

// Image serving routes (public)
Route::get('/images/{path}', [ImageController::class, 'show'])->name('api.images.show')->where('path', '.*');
Route::get('/images/{path}/info', [ImageController::class, 'info'])->where('path', '.*');


// protected routes
Route::middleware('auth:sanctum')->group(function () {
   Route::post('/logout', [AuthController::class, 'Logout']);

   // Admin operations for categories (POST, PUT, DELETE)
   Route::post('/category', [CategoryController::class, 'store']);
   Route::put('/category/{id}', [CategoryController::class, 'update']);
   Route::delete('/category/{id}', [CategoryController::class, 'destroy']);

   // Admin operations for projects (POST, PUT, DELETE)
   Route::post('/projects', [ProjectController::class, 'store']);
   Route::put('/projects/{project}', [ProjectController::class, 'update']);
   Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
   Route::post('/projects/bulk-delete', [ProjectController::class, 'bulkDestroy']);
   Route::put('/projects/{project}/cover', [ProjectController::class, 'setCoverImage']);

   // Admin operations for skills (POST, PUT, DELETE)
   Route::post('/skills', [SkillController::class, 'store']);
   Route::put('/skills/{skill}', [SkillController::class, 'update']);
   Route::delete('/skills/{skill}', [SkillController::class, 'destroy']);
   Route::post('/skills/bulk-delete', [SkillController::class, 'bulkDestroy']);

   // Admin operations for education (POST, PUT, DELETE)
   Route::post('/education', [EducationController::class, 'store']);
   Route::put('/education/{education}', [EducationController::class, 'update']);
   Route::delete('/education/{education}', [EducationController::class, 'destroy']);

   // Admin operations for experience (POST, PUT, DELETE)
   Route::post('/experience', [ExperienceController::class, 'store']);
   Route::put('/experience/{experience}', [ExperienceController::class, 'update']);
   Route::delete('/experience/{experience}', [ExperienceController::class, 'destroy']);

   Route::apiResource('/cvs', CvController::class);
});
