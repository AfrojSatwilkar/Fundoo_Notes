<?php

use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::group(['middleware' => 'api'], function() {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/profile', [UserController::class, 'profile']);

    Route::post('forgotpassword', [ForgotPasswordController::class, 'forgotPassword']);
    Route::post('resetpassword', [ForgotPasswordController::class, 'resetPassword']);

    Route::post('note', [NoteController::class, 'createNote']);
    Route::get('note', [NoteController::class, 'readAllNotes']);
    Route::post('updatenote', [NoteController::class, 'editNote']);
    Route::post('deletenote', [NoteController::class, 'deleteNote']);
    Route::post('trashnote', [NoteController::class, 'trashNote']);
    Route::post('restorenote', [NoteController::class, 'untrashNote']);
    Route::get('gettrashnote', [NoteController::class, 'getTrashNote']);

    Route::post('label', [LabelController::class, 'createLabel']);
    Route::get('label', [LabelController::class, 'readAllLabel']);
    Route::post('updatelabel', [LabelController::class, 'updateLabel']);
    Route::post('deletelabel', [LabelController::class, 'deleteLabel']);
    Route::post('notelabel', [LabelController::class, 'addNoteLabel']);
    Route::delete('notelabel', [LabelController::class, 'deleteNoteLabel']);

    Route::post('addcolab', [CollaboratorController::class, 'addCollaboratorByNoteId']);
    Route::post('updatecolab', [CollaboratorController::class, 'updateNoteByCollaborator']);
    Route::post('deletecolab', [CollaboratorController::class, 'removeCollaborator']);


});
