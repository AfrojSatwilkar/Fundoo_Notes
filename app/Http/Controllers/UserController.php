<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendEmailRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register','verifyEmail']]);
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

        $userDetail = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'verifytoken' => Str::random(60),
        ]);

        if($userDetail) {
            $sendEmail = new SendEmailRequest();
            $sendEmail->sendVerifyEmail($userDetail);
        }

        Log::channel('customLog')->info('Registered user Email : ' . 'Email Id :' . $request->email);

        Cache::remember('users', 1, function () {
            return DB::table('users')->get();
        });

        return response()->json([
            'status' => 201,
            'message' => 'User successfully registered',
        ], 201);
    }

    /**
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

        if ($token = auth()->attempt($validator->validated())) {
            $user = Auth::user();
            if($user->email_verified_at === null) {
                return  response()->json([
                    'message' => 'Email Not verified'
                ],211);
            }
            Log::channel('customLog')->info('Login Success : ' . 'Email Id :' . $request->email);
            return response()->json([
                'access_token' => $token,
                'message' => 'login Success',
            ], 201);
        }

        Log::channel('customLog')->error('User failed to login.', ['Email id' => $request->email]);
            return response()->json([
                'error' => 'we can not find the user with that e-mail address You need to register first'
            ], 401);
    }

    /**
     * * @OA\Post(
     *   path="/api/logout",
     *   summary="logout",
     *   description="logout user",
     *   @OA\RequestBody(
     *    ),
     *   @OA\Response(response=201, description="User successfully registered"),
     *   @OA\Response(response=401, description="The email has already been taken"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
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
     *   @OA\Get(
     *   path="/api/profile",
     *   summary="profile",
     *   description="user profile",
     *   @OA\RequestBody(
     *    ),
     *   @OA\Response(response=201, description="User successfully registered"),
     *   @OA\Response(response=401, description="The email has already been taken"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * Get user profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        return response()->json(auth()->user());
    }

    /**
     *   @OA\Get(
     *   path="/api/verifyemail/{token}",
     *   summary="verify email",
     *   description="user email verify",
     *   @OA\Parameter(
     *         description="token",
     *         in="path",
     *         name="token",
     *         required=true,
     *         @OA\Schema(
     *           type="string"
     *         )
     *     ),
     *   @OA\Response(response=201, description="Email is Successfully verified"),
     *   @OA\Response(response=202, description="Email Already verified"),
     *   @OA\Response(response=200, description="Not a Registered Email")
     * )
     **/
    public function verifyEmail($token){
        $user = User::where('verifytoken', $token)->first();
        if(!$user){
            return response()->json([
                'message' => "Not a Registered Email"
            ], 200);
        }elseif($user->email_verified_at === null){
            $user->email_verified_at = now();
            $user->save();
            return response()->json([
                'message' => "Email is Successfully verified"
            ],201);
        }else{
            return response()->json([
                'message' => "Email Already verified"
            ],202);
        }
    }
}
