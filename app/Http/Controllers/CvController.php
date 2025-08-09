<?php

namespace App\Http\Controllers;

use App\Http\Requests\CvRequest;
use App\Models\Cv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CvController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // display all CVs with user information
            $cvs = Cv::with('user:id,name,email')->orderBy('created_at', 'desc')->get();

            // Always return successful response even if empty
            return response()->json([
                'message' => 'CVs retrieved successfully',
                'cvs' => $cvs,
            ], 200)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            Log::error('Error fetching CVs: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error retrieving CVs data',
                'error' => $e->getMessage()
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
    public function store(CvRequest $request)
    {
        try {
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

            $cvStoreData = [
                'user_id' => $cvData['user_id'],
            ];

            // Process French CV if uploaded
            if ($request->hasFile('cv_fr')) {
                $frFile = $request->file('cv_fr');
                $cvStoreData['cv_fr_data'] = base64_encode(file_get_contents($frFile->getRealPath()));
                $cvStoreData['cv_fr_filename'] = $frFile->getClientOriginalName();
                $cvStoreData['cv_fr_mime_type'] = $frFile->getMimeType();
                $cvStoreData['cv_fr_size'] = $frFile->getSize();
                $cvStoreData['cv_fr_uploaded_at'] = now();
            }

            // Process English CV if uploaded
            if ($request->hasFile('cv_en')) {
                $enFile = $request->file('cv_en');
                $cvStoreData['cv_en_data'] = base64_encode(file_get_contents($enFile->getRealPath()));
                $cvStoreData['cv_en_filename'] = $enFile->getClientOriginalName();
                $cvStoreData['cv_en_mime_type'] = $enFile->getMimeType();
                $cvStoreData['cv_en_size'] = $enFile->getSize();
                $cvStoreData['cv_en_uploaded_at'] = now();
            }

            // create new CV
            $cv = Cv::create($cvStoreData);

            Log::info('CV created successfully with ID: ' . $cv->id);

            // response Api
            return response()->json([
                'message' => 'CV created successfully',
                'cv' => $cv,
            ], 201); // Created status code
        } catch (\Exception $e) {
            Log::error('Error creating CV: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error creating CV',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // find cv by id
        $cv = Cv::with('user:id,name,email')->find($id);

        // check if exists
        if (!$cv) {
            return response()->json(['message' => 'CV not found'], 404);
        }

        // response Api
        return response()->json([
            'message' => 'CV retrieved successfully',
            'cv' => $cv,
        ], 200);
    }

    /**
     * Get the default/active CV for public download
     */
    public function getActiveCV()
    {
        try {
            // Get the most recent CV or you can add an 'is_active' field later
            $cv = Cv::orderBy('created_at', 'desc')->first();

            if (!$cv) {
                return response()->json(['message' => 'No CV available for download'], 404);
            }

            return response()->json([
                'message' => 'Active CV retrieved successfully',
                'cv' => $cv,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching active CV: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error retrieving CV',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Serve CV file from database
     */
    public function downloadCV($id, $language)
    {
        try {
            $cv = Cv::find($id);

            if (!$cv) {
                return response()->json(['message' => 'CV not found'], 404);
            }

            $dataField = $language === 'fr' ? 'cv_fr_data' : 'cv_en_data';
            $filenameField = $language === 'fr' ? 'cv_fr_filename' : 'cv_en_filename';
            $mimeTypeField = $language === 'fr' ? 'cv_fr_mime_type' : 'cv_en_mime_type';

            if (!$cv->$dataField) {
                return response()->json(['message' => "CV $language not available"], 404);
            }

            $fileData = base64_decode($cv->$dataField);
            $filename = $cv->$filenameField;
            $mimeType = $cv->$mimeTypeField;

            return response($fileData)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($fileData));
        } catch (\Exception $e) {
            Log::error('Error downloading CV: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error downloading CV',
                'error' => $e->getMessage()
            ], 500);
        }
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
