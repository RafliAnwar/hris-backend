<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Laravel\Fortify\Rules\Password;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function login(Request $request)
    {

        try {
            // validate request
            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);
            // find user by email
            
            // $credentials = request(['email', 'password']);
            // if (!Auth::attemp($credentials)) {
            //     return ResponseFormatter::error('Unauthorized', 401);
            // }

            $user = User::where('email', $request->email)->first();
            if (!Hash::check($request->password, $user->password)) {
                throw new Exception('Invalid password');
            }
            // generate token
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            //return response
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 'Authenticated');

            // return response
        } catch (Exception $e) {
            return ResponseFormatter::error('Authentication failed');
        }
    }

    public function register(Request $request)
    {
        try {
            //validate request
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'max:255', 'email', 'unique:users'],
                'password' => ['required', 'string', new Password],
            ]);
            //create users
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            //generate token
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            //return response
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 'Register Success');
        } catch (Exception $e) {
            //return error response
            return ResponseFormatter::error($e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        //revoke token
        $token = $request->user()->currentAccessToken()->delete();
        //return response
        return ResponseFormatter::success($token, 'Logout Success');
    }

    public function fetch(Request $request)
    {
        // get user
        $user = $request->user();
        //return response
        return ResponseFormatter::success($user, 'Fetch Success');
    }
}
