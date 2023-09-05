<?php

namespace SbscPackage\Ecommerce\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class CurlService
{
    public static function requestService($planUrl, $body, $method= null){

        $baseUrl = "https://apps.wemabank.com/";
        $url = $baseUrl.$planUrl;

        // echo $response->getBody();
        $baseHeader = [
            'Content-Type: application/json',
        ];

        $client = new Client();

        // $client = new \GuzzleHttp\Client(['verify' => false]);
        if (strtolower($method) == "post"){
            $response = $client->request('post', $url, ['headers' => ['Accept' => 'application/json'], 'form_params' => $body],);
        }else{
            $response = $client->request($method, $url, ['headers' => ['Accept' => 'application/json'] ]);
        }

        $response = json_decode($response->getBody(), TRUE);
        Log::info($response);
        return $response;
    }


}


