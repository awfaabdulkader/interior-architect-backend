<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\Skill;
use Illuminate\Http\Request;
use App\Http\Requests\SkillRequest;
use Illuminate\Container\Attributes\Auth;
use App\Services\S3Service;

class SkillController extends Controller
{
    protected $s3Service;

    public function __construct(S3Service $s3Service)
    {
        $this->s3Service = $s3Service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // display all skills
        $skills = Skill::all();


        // check if skills exist
        if ($skills->isEmpty()) {
            return response()->json(['message' => 'No skills found'], 404);
        }
        $skills->transform(function ($skill) {
            if ($skill->logo) {
                $skill->logo = $this->s3Service->getFileUrl($skill->logo);
            }
            return $skill;
        });


        // response Api 
        return response()->json([
            'message' => 'Skills retrieved successfully',
            'skills' => $skills,
        ], 200);
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
        try {
            $skillData = $request->validated();
            $skillData['user_id'] = auth()->id();
    
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filePath = $this->s3Service->uploadFile($file, 'skills');
                $skillData['logo'] = $filePath;
            }
    
            $existingSkill = Skill::where('name', $skillData['name'])
                ->where('logo', $skillData['logo'])
                ->first();
    
            if ($existingSkill) {
                return response()->json([
                    'message' => 'Skill already exists',
                    'skill' => $existingSkill,
                ], 409);
            }
    
            $skill = Skill::create($skillData);
    
            return response()->json([
                'message' => 'Skill created successfully',
                'skill' => $skill,
                'logo_path' => $skill->logo,
                'logo_url' => $skill->logo ? $this->s3Service->getFileUrl($skill->logo) : null,
            ], 201);
    
        } catch (\Throwable $e) {
            // Log the error
            \Log::error('Error creating skill: ' . $e->getMessage());
    
            // Return error message in API response
            return response()->json([
                'message' => 'Server error',
                'error' => $e->getMessage(), // You can remove this later
            ], 500);
        }
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

        // Add S3 URL to logo if exists
        if ($skill->logo) {
            $skill->logo = $this->s3Service->getFileUrl($skill->logo);
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

            // Upload new file to S3
            $filePath = $this->s3Service->uploadFile($file, 'skills');

            // delete old file if exists
            if ($skill->logo) {
                $this->s3Service->deleteFile($skill->logo);
            }

            // update the logo path in skill data
            $skillData['logo'] = $filePath;
        }

        // update skill
        $skill->update($skillData);

        // Add S3 URL to logo if exists
        if ($skill->logo) {
            $skill->logo = $this->s3Service->getFileUrl($skill->logo);
        }

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

        // delete logo from S3 if exists
        if ($skill->logo) {
            $this->s3Service->deleteFile($skill->logo);
        }

        // delete skill
        $skill->delete();

        // response Api
        return response()->json([
            'message' => 'Skill deleted successfully',
        ], 200); // OK status code
    }
}
