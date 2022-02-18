<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendEmailRequest;
use App\Models\Collaborator;
use App\Models\Note;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @since 18-Feb-2022
 *
 * This controller is responsible for performing CRUD operations
 * on collabarators.
 */
class CollaboratorController extends Controller
{
    /**
     * This function takes User access token and checks if it is
     * authorised or not if so and takes note_id, email if those
     * parameters are valid it will successfully creates a
     * collaborator.
     *
     * @return JsonResponse
     */

    public function addCollaboratorByNoteId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'note_id' => 'required',
            'email' => 'required|string|email|max:100',
        ]);

        if($validator->fails())
        {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $currentUser = JWTAuth::parseToken()->authenticate();
        $note = Note::where('id', $request->note_id)->first();
        $user = User::where('email', $request->email)->first();

        if($currentUser )
        {
            if($note)
            {
                if($user)
                {
                    $collabUser = Collaborator::select('id')->where([
                        ['note_id','=',$request->input('note_id')],
                        ['email','=',$request->input('email')]
                    ])->get();

                    if($collabUser != '[]')
                    {
                        return response()->json(['message' => 'Collaborater Already Created' ], 404);
                    }

                    $collab = new Collaborator();
                    $collab->note_id = $request->get('note_id');
                    $collab->email = $request->get('email');
                    $collaborator = Note::select('id','title','description')->where([['id','=',$request->note_id]])->get();
                    if($currentUser->collaborators()->save($collab))
                    {
                        $sendEmail = new SendEmailRequest();
                        $sendEmail->sendEmailToCollab($request->email,$collaborator,$currentUser->email);
                        return response()->json([
                            'status' => 201,
                            'message' => 'Collaborator created Sucessfully'
                        ], 201);
                    }
                    return response()->json(['message' => 'Could not add collab'], 404);
                }
               return response()->json(['message' => 'User Not Registered'], 404);
            }
           return response()->json([ 'message' => 'Notes not found'], 404);
        }
        return response()->json([ 'message' => 'Invalid authorization token'], 404);
    }
}
