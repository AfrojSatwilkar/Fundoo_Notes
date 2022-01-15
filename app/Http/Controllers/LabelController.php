<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\LabelNotes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    public function del()
    {
        return response()->json([
            'message' => 'afroj satwilkar'
        ]);
    }

    /**
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

        $label = Label::where('user_id', $user->id)->get();
        if (!$label) {
            return response()->json([
                'status' => 404,
                'message' => 'Notes not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Labels Fetched  Successfully',
            'Label' => $label
        ], 201);
    }

    /**
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
                'status' => 404,
                'message' => 'Invalid authorization token'
            ], 404);
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

        return response()->json([
            'status' => 201,
            'message' => "Label updated Sucessfully"
        ], 201);
    }

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
                'status' => 404,
                'message' => 'Invalid authorization token'
            ], 404);
        }

        $labels = Label::where('id', $request->id)->first();
        if (!($labels->user_id == $user->id) || !$labels) {
            return response()->json([
                'status' => 404,
                'message' => 'Label not found'
            ], 404);
        }

        $labels->delete($labels->id);
        return response()->json([
            'status' => 201,
            'message' => 'Label successfully deleted'
        ], 201);
    }

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
                    'message' => 'Note Already have a label'
                ]);
            }

            $labelnotes = new LabelNotes();
            $labelnotes->label_id = $request->label_id;
            $labelnotes->note_id = $request->note_id;
            if ($user->label_notes()->save($labelnotes)) {
                return response()->json([
                    'message' => 'Label note added Sucessfully',
                ], 201);
            }
        }

        return response()->json([
            'status' => 404,
            'message' => 'Invalid authorization token'
        ], 404);
    }

    /**
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
                    'message' => 'Note not found with this label'
                ]);
            }

            $labelnote->delete($labelnote->id);
            return response()->json([
                'status' => 201,
                'message' => 'Label successfully deleted'
            ], 201);
        }

        return response()->json([
            'status' => 404,
            'message' => 'Invalid authorization token'
        ], 404);
    }
}
