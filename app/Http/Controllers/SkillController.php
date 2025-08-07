<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\Skill;
use Illuminate\Http\Request;
use App\Http\Requests\SkillRequest;
use Illuminate\Container\Attributes\Auth;

class SkillController extends Controller
{
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
        $skill->logo = asset('storage/' . $skill->logo);  
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
        // validate data
        $skillData = $request->validated();
        $skillData['user_id'] = auth()->id(); // get the current user ID


        //handle file upload if logo is provided
        if($request->hasFile('logo'))
        {
            $file = $request->file('logo');

            //generate a custom filename
            $filename = time() . '_' . $file->getClientOriginalName();

            //save to public/logos
            $filePath = $file->storeAs('logos' , $filename, 'public');

            //save the path in the validated data
            $skillData['logo'] = $filePath;
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
    public function update(SkillRequest $request , $id)
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
        if($request->hasFile('logo'))
        {
            $file = $request->file('logo');

            // generate a  new file name
            $filename= time(). '_' . $file->getClientOriginalName();

            // store new file
            $filePath = $file->storeAs('logos', $filename, 'public');

            // delete old file if exists
            if ($skill->logo && Storage::disk('public')->exists($skill->logo)) {
                Storage::disk('public')->delete($skill->logo);
            }

            // update the logo path in skill data
            $skillData['logo'] = $filePath;
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

        // delete skill
        $skill->delete();

        // response Api
        return response()->json([
            'message' => 'Skill deleted successfully',
        ], 200); // OK status code
    }
}
