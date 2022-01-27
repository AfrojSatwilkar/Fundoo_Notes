<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\LabelNotes;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @since 09-jan-2022
 *
 * This controller is responsible for performing CRUD operations
 * on Labels.
 */
class LabelController extends Controller
{
    /**
     *   @OA\Post(
     *   path="/api/label",
     *   summary="create label",
     *   description="create user label",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"labelname"},
     *               @OA\Property(property="labelname", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Label added Sucessfully"),
     *   @OA\Response(response=401, description="Label Name already exists"),
     *   @OA\Response(response=404, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes User access token and checks if it is
     * authorised or not if so and it procees for the note creation
     * and created it successfully.
     * @return \Illuminate\Http\JsonResponse
     */
    public function createLabel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'labelname' => 'required|string|between:2,15',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = JWTAuth::parseToken()->authenticate();

        if ($user) {
            $labelName = Label::where('labelname', $request->labelname)->first();
            if ($labelName) {
                return response()->json([
                    'message' => 'Label Name already exists'
                ], 401);
            }

            $label = new Label();
            $label->labelname = $request->get('labelname');

            if ($user->labels()->save($label)) {
                return response()->json([
                    'message' => 'Label added Sucessfully',
                ], 201);
            }
        }

        return response()->json([
            'status' => 404,
            'message' => 'Invalid authorization token'
        ], 404);
    }

    /**
     *   @OA\Get(
     *   path="/api/label",
     *   summary="read label",
     *   description="read user label",
     *   @OA\RequestBody(
     *    ),
     *   @OA\Response(response=201, description="Labels Fetched  Successfully"),
     *   @OA\Response(response=401, description="Notes not found"),
     *   @OA\Response(response=404, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes access token and finds
     * if there is any label existing on that User id and if so
     * it successfully returns label id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function readAllLabel()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Invalid authorization token'
            ], 404);
        }

        $label = Cache::remember('labels', 60*60*24, function() {
            return Label::where('user_id', Auth::user()->id)->get();
        });

        if (!$label) {
            return response()->json([
                'status' => 404,
                'message' => 'Notes not found'
            ], 401);
        }

        return response()->json([
            'message' => 'Labels Fetched  Successfully',
            'Label' => $label
        ], 201);
    }

    /**
     *   @OA\Put(
     *   path="/api/label",
     *   summary="update label",
     *   description="update user label",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="application/x-www-form-urlencoded",
     *            @OA\Schema(
     *               type="object",
     *               required={"id","labelname"},
     *               @OA\Property(property="id", type="integer"),
     *               @OA\Property(property="labelname", type="string"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Label updated Sucessfully"),
     *   @OA\Response(response=404, description="Label Name already exists"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes the User access token and label id which
     * user wants to update and finds the label id if it is existed
     * or not if so, updates it successfully.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLabel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'labelname' => 'required|string|between:2,15',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Invalid authorization token'
            ], 401);
        }

        $notes = Label::where('id', $request->id)->first();
        if (!$notes) {
            return response()->json([
                'status' => 404,
                'message' => 'Label not Found'
            ], 404);
        }

        $notes->update([
            'id' => $request->id,
            'labelname' => $request->labelname,
        ]);

        Cache::forget('labels');
        Cache::forget('notes');
        return response()->json([
            'status' => 201,
            'message' => "Label updated Sucessfully"
        ], 201);
    }

    /**
     *   @OA\Delete(
     *   path="/api/label",
     *   summary="delete label",
     *   description="delete user label",
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
     *   @OA\Response(response=201, description="Label successfully deleted"),
     *   @OA\Response(response=404, description="Label Name already exists"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * This function takes the User access token and label id and
     * and deleted that particular label id.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteLabel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Invalid authorization token'
            ], 401);
        }

        $labels = Label::where('id', $request->id)->first();
        if (!($labels->user_id == $user->id) || !$labels) {
            return response()->json([
                'status' => 404,
                'message' => 'Label not found'
            ], 404);
        }

        $labels->delete($labels->id);
        Cache::forget('labels');
        Cache::forget('notes');
        return response()->json([
            'status' => 201,
            'message' => 'Label successfully deleted'
        ], 201);
    }

    /**
     *   @OA\Post(
     *   path="/api/notelabel",
     *   summary="add note label",
     *   description="add note label",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"label_id","note_id"},
     *               @OA\Property(property="label_id", type="integer"),
     *               @OA\Property(property="note_id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Label note added Sucessfully"),
     *   @OA\Response(response=409, description="Note Already have a label"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * function to add the label to the given note
     * @param Request
     * @return json object of labels_notes
     */
    public function addNoteLabel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label_id' => 'required',
            'note_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = JWTAuth::parseToken()->authenticate();

        if ($user) {
            $labelnote = LabelNotes::where('note_id', $request->note_id)->where('label_id', $request->label_id)->first();
            if ($labelnote) {
                return response()->json([
                    'status' => 409,
                    'message' => 'Note Already have a label'
                ], 409);
            }

            $labelnotes = new LabelNotes();
            $labelnotes->label_id = $request->label_id;
            $labelnotes->note_id = $request->note_id;
            if ($user->label_notes()->save($labelnotes)) {
                Cache::forget('notes');
                return response()->json([
                    'message' => 'Label note added Sucessfully',
                ], 201);
            }
        }

        return response()->json([
            'status' => 401,
            'message' => 'Invalid authorization token'
        ], 401);
    }

    /**
     *   @OA\Delete(
     *   path="/api/notelabel",
     *   summary="delete note label",
     *   description="delete note label",
     *   @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="application/x-www-form-urlencoded",
     *            @OA\Schema(
     *               type="object",
     *               required={"label_id","note_id"},
     *               @OA\Property(property="label_id", type="integer"),
     *               @OA\Property(property="note_id", type="integer"),
     *            ),
     *        ),
     *    ),
     *   @OA\Response(response=201, description="Label successfully deleted"),
     *   @OA\Response(response=404, description="Note not found with this label"),
     *   @OA\Response(response=401, description="Invalid authorization token"),
     *   security={
     *       {"Bearer": {}}
     *     }
     * )
     * function to delete the label from the note
     *
     * @var req Request
     */
    public function deleteNoteLabel(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'label_id' => 'required',
            'note_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = JWTAuth::parseToken()->authenticate();

        if ($user) {
            $labelnote = LabelNotes::where('label_id', $req->label_id)->where('note_id', $req->note_id)->first();
            if (!$labelnote) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Note not found with this label'
                ], 404);
            }

            $labelnote->delete($labelnote->id);
            Cache::forget('notes');
            return response()->json([
                'status' => 201,
                'message' => 'Label successfully deleted'
            ], 201);
        }

        return response()->json([
            'status' => 401,
            'message' => 'Invalid authorization token'
        ], 401);
    }
}
