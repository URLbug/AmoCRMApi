<?php

namespace App\Http\Controllers;

use App\Api\AmoCrm;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
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
     * Метод для создании заявки
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    function store(Request $request): RedirectResponse
    {
        // Валидирую данные
        $data = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|phone:RU',
                'price' => 'required|int|min:0',
            ], 
            [
                'name.required' => 'Поле "Имя" обязательно для заполнения',
                'email.required' => 'Поле "Email" обязательно для заполнения',
                'phone.required' => 'Поле "Телефон" обязательно для заполнения',
                'price.required' => 'Поле "Цена" обязательно для заполнения',
                'name.max' => 'Поле "Имя" должно быть не более 255 символов',
                'email.email' => 'Поле "Email" должно быть в формате email',
                'phone.phone' => 'Поле "Телефон" должно быть в формате РФ номера телефона',
                'price.min' => 'Поле "Цена" должно быть не менее 0',
                'price.integer' => 'Поле "Цена" должно быть числовым типом',
            ]
        );

        // Если присутствует ошибка, то отправляю ее пользователю
        if($data->fails())
        {
            return back()->withErrors($data);
        }

        $data = $data->getData();

        // Создаю данные для контакта
        $contact = $this->amoCrm->addContact(
            $data['name'],
            $data['email'],
            $data['phone'],
            $this->IsMore30Second() // Если человек на сайте более 30 секунд, то тоже отправляю данные
        );

        // Если в запросе есть ошибка, то отправляю обратно ответ
        if(isset($contact['status']))
        {
            return back()
            ->withErrors('Неудалось отправить заявку');
        }

        // Создаю сделку с контактом
        $this->amoCrm->addLead(
            $data['name'],
            $data['price'],
            $contact['_embedded']['contacts'][0]['id']
        );

        return back()
        ->with('success', 'Успешно создано');
    }

    /**
     * Метод для проверки длительности на сайте
     * 
     * @return bool
     */
    function IsMore30Second(): bool
    {
        // Проверка последний активности у пользователя
        $session = DB::table(config('session.table'))
        ->where('id', session()->getId())
        ->where('last_activity',  '>', Carbon::now()->subSeconds(30)->getTimestamp())
        ->first();

        // Если такая сессия есть, то true, иначе false
        return isset($session);
    }
}
