<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\loginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    
    public function Register(RegisterRequest $request)
    {
        $registerData = $request->validated();

        //hash password
        $registerData['password'] = bcrypt($registerData['password']);

        //create user
        $CreateUser = User::create($registerData);
        
        //create token
        $token = $CreateUser->createToken('auth_token')->plainTextToken;

        //response Api
        return response()->json
        ([
            'message' => 'User created successfully',
            'user' => $CreateUser,
            'token' => $token,
        ], 201);
        
       }


       public function Login(loginRequest $request)
       {
        
        $LoginData = $request->validated();

        //find user
        $user = User::where('email' , $LoginData['email'])->first();

        //check if user exists and password matches
        if(!$user || !Hash::check($LoginData['password'], $user->password))
        {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }
        //create token
        $token = $user->createToken('auth_token')->plainTextToken;

        //response Api
        return response()->json
        ([
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
       }

       public function Logout(Request $request)
       {
        // delete current access token
        $request->user()->currentAccessToken()->delete();

        //response Api
        return response()->json([
            'message' => 'Logout successful',
        ], 200);
       }
   
}
