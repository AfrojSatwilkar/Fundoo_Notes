<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendEmailRequest;
use App\Jobs\VerificationMailjob;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\VerificationMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @since 01-jan-2022
 *
 * This controller is for user registration, login, logout
 * and emailverify
 */
class UserController extends Controller
{
    /**
     * Create a new AuthController instance.
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

        $userArray = array(
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        );

        if ($validator->fails()) {
            return response()->json([
                'validation_error' => $validator->errors(),
            ]);
        }

        $userObject = new User();
        $user = $userObject->userEmailValidation($request->email);
        if ($user) {
            return response()->json([
                'status' => 401,
                'message' => 'The email has already been taken'
            ], 401);
        }

        $userDetail = $userObject->saveUserDetails($userArray);

        $token = Auth::fromUser($userDetail);
        if($userDetail) {
            $delay = now()->addSeconds(5);
            $userDetail->notify((new VerificationMail($userDetail->email, $token))->delay($delay));
        }

        Log::channel('customLog')->info('Registered user Email : ' . 'Email Id :' . $request->email);

        return response()->json([
            'status' => 201,
            'message' => 'User successfully registered! please check your mail and verify email',
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
     *   @OA\Response(response=401, description="we can not find the user with that e-mail address You need to register first"),
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
            return response()->json([
                'validation_error' => $validator->errors(),
             ]);
        }
        $user = User::where('email', $request->email)->first();
        if (!$user || ! Hash::check($request->password, $user->password)) {
            Log::channel('customLog')->error('User failed to login.', ['Email id' => $request->email]);
            return response()->json([
                'status' => 402,
                'message' => 'we can not find the user with that e-mail address You need to register first'
            ]);
        } else {
            if($user->verifyemail === 'inactive') {
                $token = Auth::fromUser($user);

                $sendEmail = new SendEmailRequest();
                $sendEmail->sendVerifyEmail($user,$token);

                return  response()->json([
                    'status' => 211,
                    'message' => 'Email Not verified'
                ],211);
            }

            $token = JWTAuth::fromUser($user);
            Log::channel('customLog')->info('Login Success : ' . 'Email Id :' . $request->email);
            return response()->json([
                'status' => 201,
                'token' => $token,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'message' => 'login Success',
            ], 201);
        }
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
                'status' => 200,
                'message' => 'User successfully logged out.'
            ], 200);
        }
        return response()->json([
            'status' => 404,
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
     *   @OA\Response(response=404, description="Not a Registered Email")
     * )
     **/
    public function verifyEmail($token){
        $user = JWTAuth::parseToken()->authenticate($token);
        $user = User::where('email', $user->email)->first();
        if(!$user){
            return response()->json([
                'message' => "Not a Registered Email"
            ], 404);
        }elseif($user->verifyemail === 'inactive'){
            $user->verifyemail = 'active';
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

     /**
     * This function will take image
     * as input and save in AWS S3
     * and will save link in database
     * @return \Illuminate\Http\JsonResponse
     */

    public function addProfileImage(Request $request)
    {
        $request->validate([

            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);
        $user = Auth::user();

        $user = User::where('email', $user->email)->first();
        if ($user) {
            $imageName = time() . '.' . $request->image->extension();

            $path = Storage::disk('s3')->put('images', $request->image);
            $url = env('AWS_URL') . $path;
            $temp = User::where('email', $user->email)
                ->update(['profilepic' => $url]);
            return response()->json(['message' => 'Profilepic Successsfully Added', 'URL' => $url], 201);
        } else {
            return response()->json(['message' => 'We cannot find a user'], 400);
        }
    }

    /**
     * This function will take image
     * as input and save in AWS S3
     * and will save link in database
     * @return \Illuminate\Http\JsonResponse
     */

    public function updateProfileImage(Request $request)
    {
        $request->validate([

            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);
        $user = Auth::user();

        $user = User::where('email', $user->email)->first();
        if ($user) {
            $imageName = time() . '.' . $request->image->extension();
            $profile_pic = $user->profilepic;
            // $path = str_replace(env('AWS_URL'), '', $user->profilepic);
            // if(Storage::disc('s3')->exists($path)){
            //     Storage::disk('s3')->delete($path);
            // }

            $path = Storage::disk('s3')->put('images', $request->image);
            $url = env('AWS_URL') . $path;
            $temp = User::where('email', $user->email)
                ->update(['profilepic' => $url]);
            return response()->json([
                'piv' => $profile_pic,
                'message' => 'Profilepic Successsfully update', 'URL' => $url], 201);
        } else {
            return response()->json(['message' => 'We cannot find a user'], 400);
        }
    }
}
?>
