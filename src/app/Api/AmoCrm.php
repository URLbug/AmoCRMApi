<?php

namespace App\Api;

use Exception;

final class AmoCrm
{
    private string $subDomain;
    private string $client_id;
    private string $client_secret;
    private string $code;
    private string $redirect_uri;
    private string $access_token;

    private string $token_file = 'TOKEN.txt';

    function __construct()
    {
        $this->subDomain = env('SUB_DOMAIN');
        $this->client_id = env('CLIENT_ID');
        $this->client_secret = env('CLIENT_KEY');
        $this->code = env('CODE');
        $this->redirect_uri = env('REDIRECT_URL');
        
        if(file_exists($this->token_file)) 
        {
            // Через сколько будет исчезнит токен
            $expires_in = json_decode(file_get_contents($this->token_file))->{'expires_in'};
            
            if($expires_in < time()) 
            {
                $this->access_token = json_decode(file_get_contents($this->token_file))->{'access_token'};
                $this->GetToken(true); // Обновляем токен если время истекло
            }
            else
            {
                $this->access_token = json_decode(file_get_contents($this->token_file))->{'access_token'};
            }
        }
        else
        {
            $this->GetToken(); // Получаем токен если его нету
        }
    }   

    /**
     * @param string $link
     * @param string $method
     * @param array $data
     * @return string|bool
     */
    public function CurlRequest(
        string $link, 
        string $method, 
        array $data=[]
    ): string {
        /** 
         * Формируем заголовки 
         */
        if(isset($this->access_token))
        { 
            $headers = [
                'Authorization: Bearer ' . $this->access_token,
                'Content-Type: application/json'
            ];
        }

        $curl = curl_init();
        
        /**
         * Настройка
         */
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Молчит об ошибки
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        if($method == 'POST' || $method == 'PATCH') 
        {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
                
        if(isset($this->access_token))
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        else
        {
            curl_setopt($curl, CURLOPT_HEADER, false); 
        }

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        
        $out = curl_exec($curl); // Инициируем запрос к API и сохраняем ответ в переменную
        
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        $this->codeError($code, $link);

        return $out;
    }

    /**
     * Создание сделки
     * 
     * @param string $name
     * @param int $price
     * @return array
     */
    function addLead(string $name, int $price, ?int $contacts_id = null): array
    {
        $link = 'https://' . $this->subDomain . '.amocrm.ru/api/v4/leads';

        return json_decode($this->CurlRequest($link, 'POST', [
            [
                'name' => $name,
                'price' => $price,
                '_embedded' => [
                    'contacts' =>
                    [
                        [
                            'id' => $contacts_id,
                        ]
                    ]
                ],
            ],
        ]), true);
    }

    /**
     * Создание контакта
     * 
     * @param string $name
     * @param string $email
     * @param string $phone
     * @return array
     */
    function addContact(string $name, string $email, string $phone, bool $isMore): array
    {
        $link = 'https://' . $this->subDomain . '.amocrm.ru/api/v4/contacts';

        return json_decode($this->CurlRequest($link, 'POST', [
            [
               'name' => $name,
               'custom_fields_values' => [
                        [
                            'field_code' => 'PHONE',
                            'values' => [
                                [
                                    'value' => $phone,
                                    'enum_code' => 'WORK'       
                                ] ,
                            ],
                        ],
                        [
                            'field_code' => 'EMAIL',
                            'values' => [
                                [
                                    'value' => $email,
                                    'enum_code' => 'WORK'
                                ],
                            ],                        
                        ],
                        [
                            'field_id' => (int)env('CUSTOM'),
                            'values' => [
                                [
                                    'value' => (int)$isMore,
                                ]
                            ]
                        ]
                        
                    ],
                ],
            ],
        ), true);
    }

    /**
     * Получение и сброс токена
     * 
     * @param bool $refresh
     * @return void
     */
    private function GetToken(bool $refresh = false): void
    {
        // Формируем URL для запроса
        $link = 'https://' . $this->subDomain . '.amocrm.ru/oauth2/access_token'; 

        // Соберем данные для запроса
        $data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => $refresh ? 'refresh_token' : 'authorization_code',
            'redirect_uri' => $this->redirect_uri
        ];

        // Проверяем есть ли токен. Если нет исполбзуем код
        if($refresh)
        {
            $data['refresh_token'] = json_decode(
                file_get_contents($this->token_file)
            )->{'refresh_token'};
        }
        else
        {
            $data['code'] = $this->code;
        }

        $out = $this->CurlRequest($link, 'POST', $data);

        /**
         * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
         * нам придётся перевести ответ в формат, понятный PHP
         */
        $response = json_decode($out, true);

        $this->access_token = $response['access_token'];

        $token = [
            'access_token' => $response['access_token'], // Access токен
            'refresh_token' => $response['refresh_token'], // Refresh токен
            'token_type' => $response['token_type'], // Тип токена
            'expires_in' => time() + $response['expires_in'] // Через сколько действие токена истекает
        ];

        file_put_contents($this->token_file, json_encode($token));
    }

    /**
     * Проверка на ошибку в обратном запросе
     * 
     * @param string $code
     * @param string $link
     * @throws \Exception
     * @return void
     */
    private function codeError(string $code, string $link): void
    {
        $code = (int)$code;
        
        $errors = [
            301 => 'Moved permanently',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];
 
         try
         {
             if ($code != 200 && $code != 204) 
             {
                 throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
             }
 
         } 
         catch(Exception $e) 
         {
             $this->error('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode() . $link);
         }
    }

    /**
     * Запись логов об ошибки
     * 
     * @param string $e
     * @return void
     */
    private function error(string $e): void
    {
        file_put_contents('ERROR_LOG.txt', $e);
    }
}
