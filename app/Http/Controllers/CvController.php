<?php

namespace App\Http\Controllers;

use App\Http\Requests\CvRequest;
use App\Models\Cv;
use Illuminate\Http\Request;

class CvController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // display all CVs
        $cvs = Cv::all();

        // check if CVs exist
        if ($cvs->isEmpty()) {
            return response()->json(['message' => 'No CVs found'], 404);
        }

        // response Api 
        return response()->json([
            'message' => 'CVs retrieved successfully',
            'cvs' => $cvs,
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
    public function store(CvRequest $request)
    {
        //validate data
        $cvData = $request->validated();
        // check if CV already exists for the user
        $existingCv = Cv::where('user_id', $cvData['user_id'])->first();
        if ($existingCv) {
            return response()->json([
                'message' => 'CV already exists for this user',
                'cv' => $existingCv,
            ], 409); // Conflict status code
        }
        // store cv fr
        $cvFrPath= $request->file('cv_fr')->store('cvs', 'public');

        // store cv en
        $cvEnPath = $request->file('cv_en')->store('cvs', 'public');

        // create new CV
        $cv = Cv::create([
            'user_id' => $cvData['user_id'],
            'cv_fr' => $cvFrPath,
            'cv_en' => $cvEnPath,
        ]);
        // response Api
        return response()->json([
            'message' => 'CV created successfully',
            'cv' => $cv,
        ], 201); // Created status code
        
    }

    /**
     * Display the specified resource.
     */
    public function show(Cv $cv)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
   
    /**
     * Update the specified resource in storage.
     */
   public function update(CvRequest $request, $id)
{
    // find cv by id
    $cv = Cv::find($id);

    // check if exists
    if (!$cv) {
        return response()->json(['message' => 'CV not found'], 404);
    }

    // validate data
    $cvData = $request->validated();

    // update new cv_fr if uploaded
    if ($request->hasFile('cv_fr')) {
        $cvFrPath = $request->file('cv_fr')->store('cvs', 'public');
        $cv->cv_fr = $cvFrPath;
    }

    // update new cv_en if uploaded
    if ($request->hasFile('cv_en')) {
        $cvEnPath = $request->file('cv_en')->store('cvs', 'public');
        $cv->cv_en = $cvEnPath;
    }

    // update user_id
    $cv->user_id = $cvData['user_id'];
    
    $cv->save();

    return response()->json([
        'message' => 'CV updated successfully',
        'cv' => $cv,
    ], 200);
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //find by id
        $cv = Cv::find($id);
        //check if exists
        if (!$cv) {
            return response()->json(['message' => 'CV not found'], 404);
        }
        //delete cv
        $cv->delete();
        // response Api
        return response()->json([
            'message' => 'CV deleted successfully',
        ], 200); // OK status code
    }
}
