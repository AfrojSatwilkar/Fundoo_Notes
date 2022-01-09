<?php

use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::group(['middleware' => 'api'], function() {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('login', [UserController::class, 'login']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/profile', [UserController::class, 'profile']);

    Route::post('forgotpassword', [UserController::class, 'forgotPassword']);
    Route::post('resetpassword', [UserController::class, 'resetPassword']);

    Route::post('createnote', [NoteController::class, 'createNote']);
    Route::get('readnote', [NoteController::class, 'readAllNotes']);
    Route::post('editnote', [NoteController::class, 'editNote']);
    Route::post('deletenote', [NoteController::class, 'deleteNote']);
});
