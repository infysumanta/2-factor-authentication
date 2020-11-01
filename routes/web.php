<?php

use Illuminate\Routing\Router;

Route::get('auth/login', 'Sumantablog\LaravelAdmin\Google\GoogleAuthenticator\Http\Controllers\GoogleAuthenticatorController@login');
Route::post('auth/login', 'Sumantablog\LaravelAdmin\Google\GoogleAuthenticator\Http\Controllers\GoogleAuthenticatorController@postLogin');
Route::resource('auth/users', 'Sumantablog\LaravelAdmin\Google\GoogleAuthenticator\Http\Controllers\UserController')->names('admin.auth.users');
