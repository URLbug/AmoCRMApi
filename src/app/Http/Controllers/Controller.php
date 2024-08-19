<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

abstract class Controller
{
    function last_activiti()
    {
        return DB::table(config('session.table'))->get([
            'sessions.last_activity'
        ]);
    }
}
