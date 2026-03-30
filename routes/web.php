<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth');
});

Route::view('/auth', 'auth');

Route::get('/dashboard', function () {
    return view('wallet-app');
});
