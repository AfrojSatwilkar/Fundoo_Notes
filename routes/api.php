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
    Route::post('addprofilepic', [UserController::class, 'addProfileImage']);

    Route::post('forgotpassword', [ForgotPasswordController::class, 'forgotPassword']);
    Route::post('resetpassword', [ForgotPasswordController::class, 'resetPassword']);

    Route::post('note', [NoteController::class, 'createNote']);
    Route::get('note', [NoteController::class, 'readAllNotes']);
    Route::put('note', [NoteController::class, 'editNote']);
    Route::delete('note', [NoteController::class, 'deleteNote']);
    Route::get('paginatenote', [NoteController::class, 'paginationNote']);
    Route::post('trashnote', [NoteController::class, 'trashNote']);
    Route::post('untrashnote', [NoteController::class, 'untrashNote']);
    Route::get('gettrashnote', [NoteController::class, 'getTrashNote']);
    Route::post('addreminder', [NoteController::class, 'addReminder']);
    Route::get('getremindernote', [NoteController::class, 'getAllReminder']);
    Route::put('editreminder', [NoteController::class, 'editReminder']);
    Route::delete('deletereminder', [NoteController::class, 'deleteReminder']);
    Route::post('pinnote', [NoteController::class, 'pinNoteById']);
    Route::post('unpinnote', [NoteController::class, 'unpinNoteById']);
    Route::get('getpinnote', [NoteController::class, 'getAllPinnedNotes']);
    Route::post('archivenote', [NoteController::class, 'archiveNoteById']);
    Route::post('unarchivenote', [NoteController::class, 'unarchiveNoteById']);
    Route::get('getarchivednote', [NoteController::class, 'getAllArchivedNotes']);
    Route::post('colournote', [NoteController::class, 'colourNoteById']);
    Route::post('searchnotes', [NoteController::class, 'searchAllNotes']);

    Route::post('label', [LabelController::class, 'createLabel']);
    Route::get('label', [LabelController::class, 'readAllLabel']);
    Route::put('label', [LabelController::class, 'updateLabel']);
    Route::delete('label', [LabelController::class, 'deleteLabel']);
    Route::post('notelabel', [LabelController::class, 'addNoteLabel']);
    Route::delete('notelabel', [LabelController::class, 'deleteNoteLabel']);

    Route::post('addcolab', [CollaboratorController::class, 'addCollaboratorByNoteId']);
    Route::post('updatecolab', [CollaboratorController::class, 'updateNoteByCollaborator']);
    Route::post('deletecolab', [CollaboratorController::class, 'removeCollaborator']);


});
