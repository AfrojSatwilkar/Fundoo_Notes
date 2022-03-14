<?php

namespace App\Http\Controllers;

use App\Exceptions\FundooNoteException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Note;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

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
            $value = Cache::remember('notes', 3600, function () {
                return DB::table('notes')->get();
            });
            if (!$user) {
                Log::error('Invalid User');
                throw new FundooNoteException("Invalid authorization token", 401);
            }

            $notes = Note::leftJoin('collaborators', 'collaborators.note_id', '=', 'notes.id')
                ->leftJoin('label_notes', 'label_notes.note_id', '=', 'notes.id')
                ->leftJoin('labels', 'labels.id', '=', 'label_notes.label_id')
                ->select('notes.id', 'notes.title', 'notes.description', 'notes.pin', 'notes.archive', 'labels.labelname', 'collaborators.email as Collaborator', 'notes.reminder')
                ->where('notes.user_id', Auth::user()->id)->where('trash', 0)->orWhere('collaborators.email', '=', $user->email)->get();

            if (!$notes) {
                throw new FundooNoteException("Notes not found", 404);
            }

            $paginate = $notes->paginate(3);

            return response()->json([
                'status' => 201,
                'message' => 'Fetched Notes Successfully',
                'Notes' => $paginate
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
                throw new FundooNoteException("Notes not found", 404);
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
            return $exception->message();
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
                throw new FundooNoteException("Notes not found", 404);
            }

            $notes->delete($notes->id);
            Cache::forget('notes');
            Log::channel('customLog')->info('notes deleted', ['user_id' => $user->id, 'note_id' => $request->id]);
            return response()->json([
                'status' => 200,
                'message' => 'Note successfully deleted'
            ], 200);
        } catch (FundooNoteException $exception) {
            return $exception->message();
        }
    }

    /**
     * @OA\Get(
     *   path="/api/paginatenote",
     *   summary="Display Paginate Notes",
     *   description=" Display Paginate Notes ",
     *   @OA\RequestBody(
     *
     *    ),
     *   @OA\Response(response=201, description="Pagination aplied to all Notes"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
     /**
     * Function used to view all notes
     * 3 notes per page wise will be displayed.
     */
    public function paginationNote()
    {
        $allNotes = Note::paginate(3);

        return response()->json([
            'status' => 201,
            'message' => 'Pagination aplied to all Notes',
            'notes' =>  $allNotes,
        ], 201);
    }

    /**
     * @OA\Post(
     *   path="/api/pinnote",
     *   summary="Pin Note",
     *   description=" Pin Note ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Note Pinned Sucessfully"),
     *   @OA\Response(response=404, description="Notes not Found"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
     /**
     * This function takes the User access token and checks if it
     * authorised or not and it takes the note_id and pins  it
     * successfully if notes is exist.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pinNoteById(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $noteObject = new Note();
            $currentUser = JWTAuth::parseToken()->authenticate();
            $note = $noteObject->noteId($request->id);

            if (!$note) {
                Log::error('Notes Not Found', ['user' => $currentUser, 'id' => $request->id]);
                throw new FundooNoteException("Notes not Found", 404);
            }

            if ($note->pin == 0) {
                if ($note->archive == 1) {
                    $note->archive = 0;
                    $note->save();
                }
                $note->pin = 1;
                $note->save();

                Log::channel('customLog')->info('notes Pinned', ['user_id' => $currentUser, 'note_id' => $request->id]);
                return response()->json([
                    'status' => 201,
                    'message' => 'Note Pinned Sucessfully'
                ], 201);
            } else {
                throw new FundooNoteException("Note already pinned", 401);
            }
        } catch (FundooNoteException $exception) {
            return $exception->message();
        }
    }

    /**
     * @OA\Post(
     *   path="/api/unpinnote",
     *   summary="Unpin Note",
     *   description=" Unpin Note ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Note Unpinned Sucessfully"),
     *   @OA\Response(response=404, description="Notes not Found"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
     /**
     * This function takes the User access token and checks if it
     * authorised or not and it takes the note_id and unpin  it
     * successfully if notes is exist.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unpinNoteById(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $noteObject = new Note();
            $currentUser = JWTAuth::parseToken()->authenticate();
            $note = $noteObject->noteId($request->id);

            if (!$note) {
                Log::error('Notes Not Found', ['user' => $currentUser, 'id' => $request->id]);
                throw new FundooNoteException("Notes not Found", 404);
            }

            if ($note->pin == 1) {
                $note->pin = 0;
                $note->save();

                Log::channel('customLog')->info('note unpin', ['user_id' => $currentUser, 'note_id' => $request->id]);
                return response()->json([
                    'status' => 201,
                    'message' => 'Note Unpinned Sucessfully'
                ], 201);
            } else {
                throw new FundooNoteException("Note already Unpinned", 401);
            }
        } catch (FundooNoteException $exception) {
            return $exception->message();
        }
    }

    /**
     * @OA\Get(
     *   path="/api/getpinnote",
     *   summary="Display All Pinned Notes",
     *   description=" Display All Pinned Notes ",
     *   @OA\RequestBody(
     *
     *    ),
     *   @OA\Response(response=404, description="Invalid token"),
     *   @OA\Response(response=201, description="Fetched Pinned Notes Successfully"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
     /**
     * This function takes the User access token and checks if it
     * authorised or not if so, it returns all the pinned notes
     * successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllPinnedNotes()
    {
        try {
            $notes = new Note();
            $notes->user_id = auth()->id();
            $currentUser = JWTAuth::parseToken()->authenticate();

            if ($notes->user_id == auth()->id()) {
                $usernotes = Note::leftJoin('collaborators', 'collaborators.note_id', '=', 'notes.id')
                ->leftJoin('label_notes', 'label_notes.note_id', '=', 'notes.id')
                ->leftJoin('labels', 'labels.id', '=', 'label_notes.label_id')
                ->select('notes.id', 'notes.title', 'notes.description', 'notes.pin', 'notes.archive', 'notes.colour', 'collaborators.email as Collaborator', 'labels.labelname')
                ->where([['notes.user_id', '=', $currentUser->id], ['pin', '=', 1]])->orWhere('collaborators.email', '=', $currentUser->email)->get();


                if ($usernotes == '[]') {
                    throw new FundooNoteException("Notes not Found", 404);
                }
                return response()->json([
                    'message' => 'Fetched Pinned Notes Successfully',
                    'notes' => $usernotes
                ], 201);
            } else {
                throw new FundooNoteException("Invalid token", 403);
            }

        } catch (FundooNoteException $exception) {
            return $exception->message();
        }
    }

    /**
     * @OA\Post(
     *   path="/api/archivenote",
     *   summary="Archive Note",
     *   description=" Archive Note ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Note Archived Sucessfully"),
     *   @OA\Response(response=404, description="Notes not Found"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
     /**
     * This function takes the User access token and checks if it
     * authorised or not and it takes the note_id and Archives it
     * successfully if notes exist.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function archiveNoteById(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }

            $noteObject = new Note();
            $currentUser = JWTAuth::parseToken()->authenticate();
            $note = $noteObject->noteId($request->id);

            if (!$note) {
                Log::error('Notes Not Found', ['user' => $currentUser, 'id' => $request->id]);
                throw new FundooNoteException("Notes not Found", 404);
            }

            if ($note->archive == 0) {
                if ($note->pin == 1) {
                    $note->pin = 0;
                    $note->save();
                }
                $note->archive = 1;
                $note->save();

                Log::info('notes Archived', ['user_id' => $currentUser, 'note_id' => $request->id]);
                return response()->json([
                    'status' => 201,
                    'message' => 'Note Archived Sucessfully'
                ], 201);
            } else {
                throw new FundooNoteException("Note already Archived", 401);
            }
        } catch (FundooNoteException $exception) {
            return $exception->message();
        }
    }

    /**
     * @OA\Post(
     *   path="/api/unarchivenote",
     *   summary="Unarchive Note",
     *   description=" Unarchive Note ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id"},
     *               @OA\Property(property="id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Note Unarchived Sucessfully"),
     *   @OA\Response(response=404, description="Notes not Found"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
     /**
     * This function takes the User access token and checks if it
     * authorised or not and it takes the note_id and Unarchives it
     * successfully if notes exist.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unarchiveNoteById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $noteObject = new Note();
        $currentUser = JWTAuth::parseToken()->authenticate();
        $note = $noteObject->noteId($request->id);

        if (!$note) {
            Log::error('Notes Not Found', ['user' => $currentUser, 'id' => $request->id]);
            return response()->json([
                'status' => 404,
                'message' => 'Notes not Found'
            ], 404);
        }

        if ($note->archive == 1) {
            $note->archive = 0;
            $note->save();

            Log::info('notes Archived', ['user_id' => $currentUser, 'note_id' => $request->id]);
            return response()->json([
                'status' => 201,
                'message' => 'Note Archived Sucessfully'
            ], 201);
        } else {
            return response()->json([
                'status' => 401,
                'message' => 'Note already Unarchived'
            ], 401);
        }
    }

    /**
     * @OA\Get(
     *   path="/api/getarchivednote",
     *   summary="Display All Archived Notes",
     *   description=" Display All Archived Notes ",
     *   @OA\RequestBody(
     *
     *    ),
     *   @OA\Response(response=404, description="Invalid token"),
     *   @OA\Response(response=201, description="Fetched Archived Notes"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
    /**
     * This function takes the User access token and checks if it
     * authorised or not if so, it returns all the Archived notes
     * successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllArchivedNotes()
    {
        $notes = new Note();
        $notes->user_id = auth()->id();
        $currentUser = JWTAuth::parseToken()->authenticate();

        if ($notes->user_id == auth()->id()) {
            $usernotes = Note::leftJoin('collaborators', 'collaborators.note_id', '=', 'notes.id')
            ->leftJoin('label_notes', 'label_notes.note_id', '=', 'notes.id')
            ->leftJoin('labels', 'labels.id', '=', 'label_notes.label_id')
            ->select('notes.id', 'notes.title', 'notes.description', 'notes.pin', 'notes.archive', 'notes.colour', 'collaborators.email as Collaborator', 'labels.labelname')
            ->where([['notes.user_id', '=', $currentUser->id], ['archive', '=', 1]])->orWhere('collaborators.email', '=', $currentUser->email)->get();

            if ($usernotes == '[]') {
                return response()->json(['message' => 'Notes not found'], 404);
            }
            return response()->json([
                'message' => 'Fetched Archived Notes',
                'notes' => $usernotes
            ], 201);
        }
        return response()->json(['message' => 'Invalid token'], 403);
    }

    /**
     * This function takes the User access token and checks if it
     * authorised or not and it takes the note_id and colours it
     * successfully if notes exist.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Post(
     *   path="/api/colournote",
     *   summary="Colour Note",
     *   description=" Colour Note ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"id" , "colour"},
     *               @OA\Property(property="id", type="integer"),
     *               @OA\Property(property="colour", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Note coloured Sucessfully"),
     *   @OA\Response(response=404, description="Notes not Found"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
    public function colourNoteById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'colour' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $noteObject = new Note();
        $currentUser = JWTAuth::parseToken()->authenticate();
        $note = $noteObject->noteId($request->id);


        if (!$note) {
            Log::error('Notes Not Found', ['user' => $currentUser, 'id' => $request->id]);
            return response()->json([
                'status' => 404,
                'message' => 'Notes not Found'
            ], 404);
        }

        $colours  =  array(
            'green' => 'rgb(0,255,0)',
            'red' => 'rgb(255,0,0)',
            'blue' => 'rgb(0,0,255)',
            'yellow' => 'rgb(255,255,0)',
            'grey' => 'rgb(128,128,128)',
            'purple' => 'rgb(128,0,128)',
            'brown' => 'rgb(165,42,42)',
            'orange' => 'rgb(255,165,0)',
            'pink' => 'rgb(255,192,203)',
            'black' => 'rgb(0,0,0)',
            'silver' => 'rgb(192,192,192)',
            'teal' => 'rgb(0,128,128)',
            'white' => 'rgb(255,255,255)',
        );

        $colour_name = strtolower($request->colour);

        if (isset($colours[$colour_name])) {
            $note->colour = $colours[$colour_name];
            $note->save();

            Log::info('notes coloured', ['user_id' => $currentUser, 'note_id' => $request->id]);
            return response()->json([
                'status' => 201,
                'message' => 'Note coloured Sucessfully'
            ], 201);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Colour Not Specified in the List'
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *   path="/api/searchnotes",
     *   summary="Search Note",
     *   description=" Search Note ",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"search"},
     *               @OA\Property(property="search", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Note Fetched Sucessfully"),
     *   @OA\Response(response=404, description="Notes not Found"),
     *   security = {
     * {
     * "Bearer" : {}}}
     * )
     */
    /**
     * This function takes the User access token and search key to search
     * if the access token is valid it returns all the notes which has given
     * search key for that particular user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchAllNotes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $searchKey = $request->input('search');
        $currentUser = JWTAuth::parseToken()->authenticate();

        if ($currentUser) {

            $usernotes = Note::leftJoin('collaborators', 'collaborators.note_id', '=', 'notes.id')->leftJoin('label_notes', 'label_notes.note_id', '=', 'notes.id')->leftJoin('labels', 'labels.id', '=', 'label_notes.label_id')
                ->select('notes.id', 'notes.title', 'notes.description', 'notes.pin', 'notes.archive', 'notes.colour', 'collaborators.email as Collaborator', 'labels.labelname')
                ->where('notes.user_id', '=', $currentUser->id)->Where('notes.title', 'like', '%' . $searchKey . '%')
                ->orWhere('notes.user_id', '=', $currentUser->id)->Where('notes.description', 'like', '%' . $searchKey . '%')
                ->orWhere('notes.user_id', '=', $currentUser->id)->Where('labels.labelname', 'like', '%' . $searchKey . '%')
                ->orWhere('collaborators.email', '=', $currentUser->email)->Where('notes.title', 'like', '%' . $searchKey . '%')
                ->orWhere('collaborators.email', '=', $currentUser->email)->Where('notes.description', 'like', '%' . $searchKey . '%')
                ->orWhere('collaborators.email', '=', $currentUser->email)->Where('labels.labelname', 'like', '%' . $searchKey . '%')
                ->get();

            if ($usernotes == '[]') {
                return response()->json([
                    'status' => 404,
                    'message' => 'No results'], 404);
            }
            return response()->json([
                'status' => 201,
                'message' => 'Fetched Notes Successfully',
                'notes' => $usernotes
            ], 201);
        }
        return response()->json([
            'status' => 403,
            'message' => 'Invalid authorization token'
        ], 403);
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
            $notes->reminder = null;
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

    public function addReminder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'reminder' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            Log::error('Invalid User');
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

        if ($notes->reminder == null) {
            $notes->reminder = $request->reminder;
            if ($notes->save())
                return response()->json([
                    'status' => 200,
                    'message' => 'Reminder added successfully'
                ], 200);
        }
    }

    public function getAllReminder()
    {
        $user = Auth::user();
        if (!$user) {
            Log::error('Invalid User');
            return response()->json([
                'status' => 401,
                'message' => 'Invalid authorization token'
            ], 401);
        }

        $notes = Note::leftJoin('collaborators', 'collaborators.note_id', '=', 'notes.id')
            ->leftJoin('label_notes', 'label_notes.note_id', '=', 'notes.id')
            ->leftJoin('labels', 'labels.id', '=', 'label_notes.label_id')
            ->select('notes.id', 'notes.title', 'notes.description', 'labels.labelname', 'collaborators.email as Collaborator', 'notes.reminder')
            ->where('notes.user_id', Auth::user()->id)->where('reminder', '!=', null)->orWhere('collaborators.email', '=', $user->email)->get();

        if (!$notes) {
            return response()->json([
                'status' => 404,
                'message' => 'Notes not found'
            ], 404);
        }

        return response()->json([
            'status' => 201,
            'message' => 'Fetched reminder Notes Successfully',
            'Notes' => $notes
        ], 201);
    }

    public function editReminder(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            Log::error('Invalid User');
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

        if($notes->reminder != null) {
            $notes->reminder = $request->reminder;
            $notes->save();
            return response()->json([
                'status' => 200,
                'message' => "Note reminder successfully updated"
            ], 200);
        }
    }

    public function deleteReminder(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            Log::error('Invalid User');
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

        if($notes->reminder != null) {
            $notes->reminder = null;
            $notes->save();

            return response()->json([
                'status' => 200,
                'message' => 'Note reminder deleted successfully'
            ], 200);
        }
    }
}
