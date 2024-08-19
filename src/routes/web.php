<?php

use App\Api\AmoCrm;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $amo = new AmoCrm;

    dd($amo);

    return view('index');
});
