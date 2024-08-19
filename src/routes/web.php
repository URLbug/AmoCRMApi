<?php

use Illuminate\Support\Facades\Route;

Route::match(
    ['get', 'post',],
    '/', 
    'App\Http\Controllers\ClientController@index'
)->name('client');
