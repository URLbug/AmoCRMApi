<?php

namespace App\Http\Controllers;

use App\Api\AmoCrm;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    private AmoCrm $amoCrm;

    function __construct()
    {
        $this->amoCrm = new AmoCrm;
    }

    /**
     * Метод для обработки запросов
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    function index(Request $request): View|RedirectResponse
    {
        if($request->isMethod('POST'))
        {
            return $this->store($request);
        }

        return view('index');
    }

    /**
     * Метод для созданий сделок
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|phone:RU',
            'price' => 'required|int|min:0',
        ]);

        $contact = json_decode($this->amoCrm->addContact(
            $data['name'],
            $data['email'],
            $data['phone'],
        ), true);

        $lead = $this->amoCrm->addLead(
            $data['name'],
            $data['price'],
            $contact['_embedded']['contacts'][0]['id']
        );

        dd($lead);

        return back()
        ->with('success', 'Успешно отправлено');
    }

    function last_activity()
    {
        return DB::table(config('session.table'))->get([
            'sessions.last_activity'
        ]);
    }
}
