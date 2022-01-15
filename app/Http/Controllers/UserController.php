<?php

namespace App\Http\Controllers;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Request-Method: POST");

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\SendEmailRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
     * @OA\Post(
     *   path="/api/register",
     *   summary="register",
     *   description="register the user for login",
     *   @OA\RequestBody(
        *         @OA\JsonContent(),
        *         @OA\MediaType(
        *            mediaType="multipart/form-data",
        *            @OA\Schema(
        *               type="object",
        *               required={"firstname","lastname","email", "password", "confirm_password"},
        *               @OA\Property(property="firstname", type="string"),
        *               @OA\Property(property="lastname", type="string"),
        *               @OA\Property(property="email", type="string"),
        *               @OA\Property(property="password", type="password"),
        *               @OA\Property(property="confirm_password", type="password")
        *            ),
        *        ),
        *    ),
     *   @OA\Response(response=201, description="User successfully registered"),
     *   @OA\Response(response=401, description="The email has already been taken"),
     * )
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

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            return response()->json([
                'message' => 'The email has already been taken'
            ], 401);
        }

        User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        Log::info('Registered user Email : '.'Email Id :'.$request->email );

        return response()->json([
            'status' => 201,
            'message' => 'User successfully registered',
        ], 201);
    }

    /**
     * /**
     * @OA\Post(
     *   path="/api/login",
     *   summary="login",
     *   description="user login",
     *   @OA\RequestBody(
        *         @OA\JsonContent(),
        *         @OA\MediaType(
        *            mediaType="multipart/form-data",
        *            @OA\Schema(
        *               type="object",
        *               required={"email", "password"},
        *               @OA\Property(property="email", type="string"),
        *               @OA\Property(property="password", type="string"),
        *            ),
        *        ),
        *    ),
     *   @OA\Response(response=201, description="login Success"),
     *   @OA\Response(
     *              response=401,
     *              description="we can not find the user with that e-mail address You need to register first"),
     * )
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
            Log::error('User failed to login.', ['Email id' => $request->email]);
            return response()->json([
                'error' => 'we can not find the user with that e-mail address You need to register first'
            ], 401);
        }

        Log::info('Login Success : '.'Email Id :'.$request->email );
        return response()->json([
            'access_token' => $token,
            'message' => 'login Success',
            'token_type' => 'bearer',
        ], 201);
    }

    /**
     * Logout user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $user = auth()->logout();

        if (!$user) {
            return response()->json([
                'status' => 201,
                'message' => 'User successfully logged out.'
            ], 201);
        }
        return response()->json([
            'status' => $user,
            'message' => 'Invalid authorization token'
        ], 404);
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
