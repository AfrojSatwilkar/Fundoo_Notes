<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\SendEmailRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Register user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|min:2|max:20',
            'lastname' => 'required|string|min:2|max:20',
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|same:password',
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::where('email', $request->email)->first();
        if ($user)
        {
            return response()->json([
                'message' => 'The email has already been taken'
            ],401);
        }

        User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            ]);

        return response()->json([
            'status' => 201,
            'message' => 'User successfully registered',
        ], 201);
    }

    /**
     * login user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json([
                'error' => 'we can not find the user with that e-mail address You need to register first'
            ], 401);
        }

        return response()->json([
            'access_token' => $token,
            'message' => 'login Success',
            'token_type' => 'bearer',
        ],201);


    }

    /**
     * Logout user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $user = auth()->logout();

        if(!$user)
        {
            return response()->json([
                'status' => 201,
                'message' => 'User successfully logged out.'
            ],201);
        }
        return response()->json([
            'status' => $user,
            'message' => 'Invalid authorization token'
        ],404);
    }

    /**
     * Get user profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        return response()->json(auth()->user());
    }
}
