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
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|same:password',
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors(), 400);
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
                'error' => 'Unauthorized'
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
        auth()->logout();

        return response()->json([
            'status' => 201,
            'message' => 'User successfully logged out.'
        ],201);
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

     /**
     * This API Takes the request which is the email id and validates it and check where that email id
     * is present in DB or not if it is not,it returns failure with the appropriate response code and
     * checks for password reset model once the email is valid and by creating an object of the
     * sendEmail function which is there in App\Http\Requests\SendEmailRequest and calling the function
     * by passing args and successfully sending the password reset link to the specified email id.
     *
     * @return success reponse about reset link.
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:100',
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user)
        {
            return response()->json([
                'status'=> 404,
                'message' => 'we can not find a user with that email address'
            ],404);
        }

        $token = Auth::fromUser($user);
        if ($user)
        {
            $sendEmail = new SendEmailRequest();
            $sendEmail->sendEmail($user,$token);
        }

        return response()->json([
            'status' => 200,
            'message' => 'we have mailed your password reset link to respective E-mail'
        ],200);
    }

    /**
     * This API Takes the request which has new password and confirm password and validates both of them
     * if validation fails returns failure resonse and if it passes it checks with DB whether the token
     * is there or not if not returns a failure response and checks the user email also if everything is
     * good resets the password successfully.
     */
    public function resetPassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'new_password' => 'min:6|required|',
            'confirm_password' => 'required|same:new_password'
        ]);

        if ($validate->fails())
        {
            return response()->json([
                'status'=> 400,
                 'message' => "Password doesn't match"
                ],400);
        }

        $passwordReset = Auth::user();

        if (!$passwordReset)
        {
            return response()->json([
                'status'=> 401,
                'message' => 'This token is invalid'
            ],401);
        }

        $user = User::where('email', 'afrozsatvilkar2016@gmail.com')->first();

        if (!$user)
        {
            return response()->json([
                'status' => 400,
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
