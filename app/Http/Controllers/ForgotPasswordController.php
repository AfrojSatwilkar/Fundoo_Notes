<?php

namespace App\Http\Controllers;

use App\Exceptions\FundooNoteException;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\PasswordResetRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class ForgotPasswordController extends Controller
{
     /**
     *  @OA\Post(
     *   path="/api/forgotpassword",
     *   summary="forgot password",
     *   description="forgot user password",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"email"},
     *               @OA\Property(property="email", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="we have mailed your password reset link to respective E-mail"),
     *   @OA\Response(response=404, description="we can not find a user with that email address"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
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
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:100',
            ]);

            if($validator->fails()) {
                return response()->json([
                    'validation_error' => $validator->errors(),
                ]);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user)
            {
                throw new FundooNoteException("we can not find a user with that email address", 404);
            }

            $token = Auth::fromUser($user);

            if ($user)
            {
                $delay = now()->addSeconds(5);
                $user->notify((new PasswordResetRequest($user->email, $token))->delay($delay));
            }

            return response()->json([
                'status' => 200,
                'message' => 'we have mailed your password reset link to respective E-mail'
            ],200);
        } catch (FundooNoteException $exception) {
            return $exception->message();
        }
    }

    /**
     *   @OA\Post(
     *   path="/api/resetpassword",
     *   summary="reset password",
     *   description="reset user password",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"new_password","confirm_password"},
     *               @OA\Property(property="new_password", type="password"),
     *               @OA\Property(property="confirm_password", type="password"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=200, description="Password reset successfull!"),
     *   @OA\Response(response=400, description="we can't find the user with that e-mail address"),
     *   @OA\Response(response=401, description="This token is invalid"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This API Takes the request which has new password and confirm password and validates both of them
     * if validation fails returns failure resonse and if it passes it checks with DB whether the token
     * is there or not if not returns a failure response and checks the user email also if everything is
     * good resets the password successfully.
     */
    public function resetPassword(Request $request)
    {
        try {
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

            $passwordReset = JWTAuth::parseToken()->authenticate();

            if (!$passwordReset)
            {
                throw new FundooNoteException("This token is invalid", 401);
            }

            $user = User::where('email', $passwordReset->email)->first();

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
                    'status' => 200,
                    'message' => 'Password reset successfull!'
                ],200);
            }
        } catch (FundooNoteException $exception) {
            return $exception->message();
        }

    }
}
?>
