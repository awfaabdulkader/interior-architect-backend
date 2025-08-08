<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\Skill;
use Illuminate\Http\Request;
use App\Http\Requests\SkillRequest;
use Illuminate\Container\Attributes\Auth;
use App\Services\ImageStorageService;
use Illuminate\Support\Facades\Log;

class SkillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Optimize query with pagination and selective loading
            $skills = Skill::select('id', 'name', 'logo', 'created_at')
                ->orderBy('created_at', 'desc')
                ->paginate(20); // Load 20 skills per page

            // Transform data for frontend
            $skills->getCollection()->transform(function ($skill) {
                return [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'logo' => $skill->logo,
                    'created_at' => $skill->created_at
                ];
            });

            return response()->json([
                'status' => 'success',
                'skills' => $skills->items(),
                'pagination' => [
                    'current_page' => $skills->currentPage(),
                    'last_page' => $skills->lastPage(),
                    'per_page' => $skills->perPage(),
                    'total' => $skills->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching skills: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch skills'
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SkillRequest $request)
    {
        // validate data
        $skillData = $request->validated();
        $skillData['user_id'] = auth()->id(); // get the current user ID


        //handle file upload if logo is provided
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');

            // Store image in database using ImageStorageService
            $imageStorageService = app(\App\Services\ImageStorageService::class);
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = 'skills/' . $filename;

            $imageStorage = $imageStorageService->storeImage($file, $path);

            //save the path in the validated data
            $skillData['logo'] = $path;
        }

        // check if already exists
        $existingSkill = Skill::where('name', $skillData['name'])
            ->where('logo', $skillData['logo'])
            ->first();
        if ($existingSkill) {
            return response()->json([
                'message' => 'Skill already exists',
                'skill' => $existingSkill,
            ], 409); // Conflict status code
        }

        // create new skill
        $skill = Skill::create($skillData);

        // response Api
        return response()->json([
            'message' => 'Skill created successfully',
            'skill' => $skill,
        ], 201); // Created status code
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // find skill by id
        $skill = Skill::find($id);

        // check if skill exists
        if (!$skill) {
            return response()->json(['message' => 'Skill not found'], 404);
        }

        // response Api
        return response()->json([
            'message' => 'Skill retrieved successfully',
            'skill' => $skill,
        ], 200); // OK status code
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Skill $skill)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SkillRequest $request, $id)
    {
        //find skill by id
        $skill = Skill::findOrFail($id);

        // check if skill exists
        if (!$skill) {
            return response()->json(['message' => 'Skill not found'], 404);
        }

        // validate data
        $skillData = $request->validated();

        // check if new file is uploaded
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');

            // Delete old image from database if exists
            if ($skill->logo) {
                $imageStorageService = app(ImageStorageService::class);
                $imageStorageService->deleteImage($skill->logo);
            }

            // Store new image in database using ImageStorageService
            $imageStorageService = app(ImageStorageService::class);
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = 'skills/' . $filename;

            $imageStorage = $imageStorageService->storeImage($file, $path);

            // update the logo path in skill data
            $skillData['logo'] = $path;
        }

        // update skill
        $skill->update($skillData);

        // response Api
        return response()->json([
            'message' => 'Skill updated successfully',
            'skill' => $skill,
        ], 200); // OK status code
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //find skill by id
        $skill = Skill::find($id);

        // check if skill exists
        if (!$skill) {
            return response()->json(['message' => 'Skill not found'], 404);
        }

        // Delete image from database if exists
        if ($skill->logo) {
            $imageStorageService = app(ImageStorageService::class);
            $imageStorageService->deleteImage($skill->logo);
        }

        // delete skill
        $skill->delete();

        // response Api
        return response()->json([
            'message' => 'Skill deleted successfully',
        ], 200); // OK status code
    }
}
