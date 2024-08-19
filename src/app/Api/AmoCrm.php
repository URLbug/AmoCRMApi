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
            $expires_in = json_decode(file_get_contents("TOKEN.txt"))->{'expires_in'};
            
            if($expires_in < time()) 
            {
                $this->access_token = json_decode(file_get_contents("TOKEN.txt"))->{'access_token'};
                $this->GetToken(true);
            }
            else
            {
                $this->access_token = json_decode(file_get_contents("TOKEN.txt"))->{'access_token'};
            }
        }
        else
        {
            $this->GetToken();
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
    ): string|bool {
        /** 
         * Формируем заголовки 
         */
        $headers = [
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ];

        $curl = curl_init();
        
        /**
         * Настройка
         */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
        curl_setopt($curl,CURLOPT_URL, $link);
        
        if($method == 'POST' || $method == 'PATCH') 
        {
            curl_setopt($curl ,CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        
        $out = curl_exec($curl); // Инициируем запрос к API и сохраняем ответ в переменную
        
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        $this->codeError($code, $link);

        return $out;
    }

    function get()
    {

    }

    /**
     * @param bool $refresh
     * @return void
     */
    private function GetToken(bool $refresh = false): void
    {
        $link = 'https://' . $this->subDomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса

        /** Соберем данные для запроса */
        $data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => $refresh ? 'refresh_token' : 'authorization_code',
            'refresh_token' => $refresh ? json_decode(file_get_contents("TOKEN.txt"))->{'refresh_token'} : $this->code,
            'redirect_uri' => $this->redirect_uri
        ];

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

    private function error(string $e): void
    {
        file_put_contents('ERROR_LOG.txt', $e);
    }
}
