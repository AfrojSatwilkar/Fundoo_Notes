<?php

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
    Route::put('note', [NoteController::class, 'editNote']);
    Route::delete('note', [NoteController::class, 'deleteNote']);

    Route::post('label', [LabelController::class, 'createLabel']);
    Route::get('label', [LabelController::class, 'readAllLabel']);
    Route::put('label', [LabelController::class, 'updateLabel']);
    Route::delete('label', [LabelController::class, 'deleteLabel']);
    Route::post('notelabel', [LabelController::class, 'addNoteLabel']);
    Route::delete('notelabel', [LabelController::class, 'deleteNoteLabel']);

});
