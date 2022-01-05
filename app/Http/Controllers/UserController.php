<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\SendEmailRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
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
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|same:password',
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            ]);

        return response()->json([
            'message' => 'User successfully registered',
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
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
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // $cookie = cookie('jwt', $token, 60 * 24);
        return response()->json([
            'access_token' => $token,
            'message' => 'login Success',
            'token_type' => 'bearer',
        ]);


    }

    /**
     * Logout user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'User successfully logged out.']);
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

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user)
        {
            return response()->json([
                'message' => 'we can not find a user with that email address'
            ],404);
        }

        $token = Auth::fromUser($user);
        if ($user)
        {
            $sendEmail = new SendEmailRequest();
            $sendEmail->sendEmail($user->email,$token);
        }

        return response()->json([
            'message' => 'we have mailed your password reset link to respective E-mail'
        ],200);
    }

    public function resetPassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'new_password' => 'min:6|required|',
            'confirm_password' => 'required|same:new_password'
        ]);

        if ($validate->fails())
        {
            return response()->json([
                 'message' => "Password doesn't match"
                ],400);
        }

        $passwordReset = Auth::user();

        if (!$passwordReset)
        {
            return response()->json(['message' => 'This token is invalid'],401);
        }

        $user = User::where('email', 'afrozsatvilkar2016@gmail.com')->first();

        if (!$user)
        {
            Log::error('Email not found.', ['id' => $request->email]);
            return response()->json([
                'message' => "we can't find the user with that e-mail address"
            ], 400);
        }
        else
        {
            $user->password = bcrypt($request->new_password);
            $user->save();
            return response()->json([
                'status' => 201,
                'message' => 'Password reset successfull!'
            ],201);
        }
    }
}
