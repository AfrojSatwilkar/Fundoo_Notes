<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Models\Note;
use Illuminate\Support\Facades\Log;

/**
 * @since 04-jan-2022
 *
 * This controller is responsible for performing CRUD operations
 * on notes.
 */
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

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $note = new Note;
            $note->title = $request->input('title');
            $note->description = $request->input('description');
            $note->user_id = Auth::user()->id;
            $note->save();
        } catch (Exception $e) {
            Log::error('Invalid User');
            return response()->json([
                'status' => 404,
                'message' => 'Invalid authorization token'
            ], 404);
        }

        Log::info('notes created',['user_id'=>$note->user_id]);
        return response()->json([
            'status' => 201,
            'message' => 'notes created successfully'
        ], 201);
    }

    /**
     * This function takes access token and note id and finds
     * if there is any note existing on that User id and note id if so
     * it successfully returns that note id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function readAllNotes()
    {
        $user = Auth::user();
        if (!$user) {
            Log::error('Invalid User');
            return response()->json([
                'status' => 404,
                'message' => 'Invalid authorization token'
            ], 404);
        }

        $notes = Note::with('label')->where('user_id', Auth::user()->id)->get();
        if (!$notes) {
            return response()->json([
                'status' => 404,
                'message' => 'Notes not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Fetched Notes Successfully',
            'Notes' => $notes
        ], 201);
    }

     /**
     * This function takes the User access token and note id which
     * user wants to update and finds the note id if it is existed
     * or not if so, updates it successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();
        if (!$user) {
            Log::error('Invalid User');
            return response()->json([
                'status' => 404,
                'message' => 'Invalid authorization token'
            ], 404);
        }

        $notes = Note::where('id', $request->id)->first();
        if (!$notes) {
            return response()->json([
                'status' => 404,
                'message' => 'Notes not found'
            ], 404);
        }

        $notes->update([
            'id' => $request->id,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        Log::info('Note updated',['user_id' => $user->id]);
        return response()->json([
            'status' => 201,
            'message' => "Note successfully updated"
        ], 201);
    }

     /**
     * This function takes the User access token and note id which
     * user wants to delete and finds the note id if it is existed
     * or not if so, deletes it successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();
        if (!$user) {
            Log::error('Invalid User');
            return response()->json([
                'status' => 404,
                'message' => 'Invalid authorization token'
            ], 404);
        }

        $notes = Note::where('id', $request->id)->first();
        if (!($notes->user_id == $user->id) || !$notes) {
            Log::error('Notes Not Found',['id'=>$request->id]);
            return response()->json([
                'status' => 404,
                'message' => 'Notes not found'
            ], 404);
        }

        $notes->delete($notes->id);
        Log::info('notes deleted',['user_id'=>$user->id,'note_id'=>$request->id]);
        return response()->json([
            'status' => 201,
            'message' => 'Note successfully deleted'
        ], 201);
    }
}
