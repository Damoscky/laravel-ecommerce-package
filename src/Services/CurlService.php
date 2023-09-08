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


    public static function getRequest($url, $headerParams = null)
    {
        $curl = curl_init();
        // Header parameter
        $baseHeader = [
            'Content-Type: application/json',
        ];

        if (is_null($headerParams)) {
            $headers = $baseHeader;
        } else {
            $headers = array_merge($baseHeader, $headerParams);
        }

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        return json_decode($response, true);
        curl_close($curl);

    }

    public static function postRequest($url, $postdata, $headerParams = null)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata)); //Post Fields
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 200);
        curl_setopt($ch, CURLOPT_TIMEOUT, 200);

        // Header parameter
        $baseHeader = [
            'Content-Type: application/json',
        ];

        if (is_null($headerParams)) {
            $headers = $baseHeader;
        } else {
            $headers = array_merge($baseHeader, $headerParams);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $request = curl_exec($ch);

        if ($request) {
            $result = json_decode($request, true);
            return $result;
        }else{
            if(curl_error($ch)){
                return 'error:' . curl_error($ch);
            }
        }

        curl_close($ch);
    }

    public static function putRequest($url, $postdata, $headerParams = null)
    {
        $curl = curl_init();
        // Header parameter
        $baseHeader = [
            'Content-Type: application/json',
        ];

        if (is_null($headerParams)) {
            $headers = $baseHeader;
        } else {
            $headers = array_merge($baseHeader, $headerParams);
        }

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        return json_decode($response, true);
        curl_close($curl);

    }


}


