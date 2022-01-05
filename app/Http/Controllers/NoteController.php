<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Models\Note;
use Tymon\JWTAuth\Facades\JWTAuth;

class NoteController extends Controller
{
    /**
     * This function takes User access token and checks if it is
     * authorised or not if so and it procees for the note creation
     * and created it successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|between:2,50',
            'description' => 'required|string|between:3,1000',
        ]);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 400);
        }

        try
		{
            $note = new Note;
            $note->title = $request->input('title');
            $note->description = $request->input('description');
            $note->user_id = Auth::user()->id;
            $note->save();

        }
		catch (Exception $e)
		{
            return response()->json([
                'status' => 404,
                'message' => 'Invalid authorization token'
            ], 404);
        }

        return response()->json([
		'status' => 201,
		'message' => 'notes created successfully'
        ],201);
    }

    /**
     * This function takes JWT access token and note id and finds
     * if there is any note existing on that User id and note id if so
     * it successfully returns that note id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function displayNoteById(Request $request)
    {
        try
        {
            $id = $request->input('id');
            $User = Auth::parseToken()->authenticate();

            $notes = $User->notes()->find($id);
            if($notes == '')
            {
                return response()->json([ 'message' => 'Notes not found'], 404);
            }
        }
        catch(Exception $e)
        {
            return response()->json(['message' => 'Invalid authorization token' ], 404);
        }

        return $notes;
    }
}
