<?php

namespace App\Http\Controllers;

use App\Exceptions\FundooNoteException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Note;
use Illuminate\Support\Facades\Cache;
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
     * @OA\Post(
     *   path="/api/note",
     *   summary="create note",
     *   description="create user note",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"title","description"},
     *               @OA\Property(property="title", type="string"),
     *               @OA\Property(property="description", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="notes created successfully"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes User access token and checks if it is
     * authorised or not if so and it procees for the note creation
     * and created it successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createNote(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|between:2,50',
                'description' => 'required|string|between:3,1000',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = Auth::user();
            if (!$user) {
                throw new FundooNoteException("Invalid authorization token", 401);
            } else {
                $note = new Note;
                $note->title = $request->input('title');
                $note->description = $request->input('description');
                $note->user_id = $user->id;
                $note->save();
            }

            Log::channel('customLog')->info('notes created', ['user_id' => $note->user_id]);
            return response()->json([
                'status' => 201,
                'message' => 'notes created successfully'
            ], 201);
        } catch (FundooNoteException $exception) {
            Log::channel('customLog')->error('Invalid User');
            return $exception->message();
        }
    }

    /**
     *   @OA\Get(
     *   path="/api/note",
     *   summary="read note",
     *   description="user read note",
     *   @OA\RequestBody(
     *    ),
     *   @OA\Response(response=201, description="User successfully registered"),
     *   @OA\Response(response=401, description="The email has already been taken"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes access token and note id and finds
     * if there is any note existing on that User id and note id if so
     * it successfully returns that note id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function readAllNotes()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                Log::error('Invalid User');
                throw new FundooNoteException("Invalid authorization token", 401);
            }

            // $notes = Cache::remember('notes', 864000, function() {
            //     return Note::leftJoin('label_notes', 'label_notes.note_id', '=', 'notes.id')
            //         ->leftJoin('labels', 'labels.id', '=', 'label_notes.label_id')
            //         ->select('notes.id', 'notes.title', 'notes.description', 'labels.labelname')
            //         ->where('notes.user_id', Auth::user()->id)->get();
            // });
            $notes = Note::leftJoin('label_notes', 'label_notes.note_id', '=', 'notes.id')
                ->leftJoin('labels', 'labels.id', '=', 'label_notes.label_id')
                ->select('notes.id', 'notes.title', 'notes.description', 'labels.labelname')
                ->where('notes.user_id', Auth::user()->id)->where('trash', 0)->get();

            if (!$notes) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Notes not found'
                ], 404);
            }

            return response()->json([
                'status' => 201,
                'message' => 'Fetched Notes Successfully',
                'Notes' => $notes
            ], 201);
        } catch (FundooNoteException $exception) {
            return $exception->message();
        }
    }

    /**
     *   @OA\Put(
     *   path="/api/note",
     *   summary="update note",
     *   description="update user note",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="application/x-www-form-urlencoded",
     *            @OA\Schema(
     *               type="object",
     *               required={"id","title","description"},
     *               @OA\Property(property="id"),
     *               @OA\Property(property="title", type="string"),
     *               @OA\Property(property="description", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=200, description="Note successfully updated"),
     *   @OA\Response(response=404, description="Notes not found"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes the User access token and note id which
     * user wants to update and finds the note id if it is existed
     * or not if so, updates it successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editNote(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = Auth::user();
            if (!$user) {
                Log::channel('customLog')->error('Invalid User');
                throw new FundooNoteException("Invalid authorization token", 401);
            }

            $notes = Note::where('user_id', Auth::user()->id)->where('id', $request->id)->first();
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

            Cache::forget('notes');
            Log::channel('customLog')->info('Note updated', ['user_id' => $user->id]);
            return response()->json([
                'status' => 200,
                'message' => "Note successfully updated"
            ], 200);
        } catch (FundooNoteException $exception) {
            $exception->message();
        }
    }

    /**
     *   @OA\Delete(
     *   path="/api/note",
     *   summary="delete note",
     *   description="delete user note",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="application/x-www-form-urlencoded",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=200, description="Note successfully deleted"),
     *   @OA\Response(response=404, description="Notes not found"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes the User access token and note id which
     * user wants to delete and finds the note id if it is existed
     * or not if so, deletes it successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteNote(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = Auth::user();
            if (!$user) {
                Log::channel('customLog')->error('Invalid User');
                throw new FundooNoteException("Invalid authorization token", 401);
            }

            $notes = Note::where('id', $request->id)->first();
            if (!($notes->user_id == $user->id) || !$notes) {
                Log::channel('customLog')->error('Notes Not Found', ['id' => $request->id]);
                return response()->json([
                    'status' => 404,
                    'message' => 'Notes not found'
                ], 404);
            }

            $notes->delete($notes->id);
            Cache::forget('notes');
            Log::channel('customLog')->info('notes deleted', ['user_id' => $user->id, 'note_id' => $request->id]);
            return response()->json([
                'status' => 200,
                'message' => 'Note successfully deleted'
            ], 200);
        } catch (FundooNoteException $exception) {
            $exception->message();
        }
    }

    public function trashNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();
        if (!$user) {
            Log::channel('customLog')->error('Invalid User');
            return response()->json([
                'status' => 401,
                'message' => 'Invalid authorization token'
            ], 401);
        }

        $notes = Note::where('id', $request->id)->first();
        if (!($notes->user_id == $user->id) || !$notes) {
            Log::channel('customLog')->error('Notes Not Found', ['id' => $request->id]);
            return response()->json([
                'status' => 404,
                'message' => 'Notes not found'
            ], 404);
        }

        if ($notes->trash === 0) {
            $notes->trash = 1;
            $notes->save();

            return response()->json([
                'status' => 200,
                'message' => 'Note trash successfully'
            ], 200);
        }
    }

    public function untrashNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = Auth::user();
        if (!$user) {
            Log::channel('customLog')->error('Invalid User');
            return response()->json([
                'status' => 401,
                'message' => 'Invalid authorization token'
            ], 401);
        }

        $notes = Note::where('id', $request->id)->first();
        if (!($notes->user_id == $user->id) || !$notes) {
            Log::channel('customLog')->error('Notes Not Found', ['id' => $request->id]);
            return response()->json([
                'status' => 404,
                'message' => 'Notes not found'
            ], 404);
        }

        if ($notes->trash == 1) {
            $notes->trash = 0;
            $notes->save();

            return response()->json([
                'status' => 200,
                'message' => 'Note restore successfully'
            ], 200);
        }
    }

    public function getTrashNote()
    {
        $user = Auth::user();
        if (!$user) {
            Log::error('Invalid User');
            return response()->json([
                'status' => 401,
                'message' => 'Invalid authorization token'
            ], 401);
        }

        $notes = Note::leftJoin('label_notes', 'label_notes.note_id', '=', 'notes.id')
            ->leftJoin('labels', 'labels.id', '=', 'label_notes.label_id')
            ->select('notes.id', 'notes.title', 'notes.description', 'labels.labelname')
            ->where('notes.user_id', Auth::user()->id)->where('trash', 1)->get();

        if (!$notes) {
            return response()->json([
                'status' => 404,
                'message' => 'Notes not found'
            ], 404);
        }

        return response()->json([
            'status' => 201,
            'message' => 'Fetched trash Notes Successfully',
            'Notes' => $notes
        ], 201);
    }
}
