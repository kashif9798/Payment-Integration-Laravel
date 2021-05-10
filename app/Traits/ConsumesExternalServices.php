<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait ConsumesExternalServices
{
    /**
     * This will make use of the guzzleHTTP library to make requests to the external services/APIs
     * We are developing this trait so it can be used dynamically to amke requests
     * @param $method - will define the method i.e get post update delete patch put etc
     * @param $requestUrl - this will be the url to which we are sending the request to
     * @param $queryParams - the parameters of the url
     * @param $formParams - the parameters of the forms
     * @param $headers - the header of the request
     * @param $isJsonRequest - is the request data in json
     */
    public function makeRequest($method, $requestUrl, $queryParams = [], $formParams = [], $headers = [], $isJsonRequest = false)
    {
        $client = new Client([
            'base_uri'  => $this->baseUri,
        ]);

        // if method exist in the class that is using this trait hence $this as the first parameter and methdd of that class name in the second then apply this authorization. this is because we need to be flexiable as to not use authroization for services that do not need it
        if(method_exists($this,'resolveAuthorization')){
            // we can autherize this request by calling a custom method resolveAutorization() that will be present in the component that is using this trait and we are sending $queryParams, $headers, $formParams in this authorization step because some apis use $queryParams some use headers while some use formparams so we need o be flexiable for all apis 
            $this->resolveAuthorization($queryParams, $headers, $formParams);
        }

        /**
         * This is the actual request that uses the dynamic data passed to the make request above
         */
        $response = $client->request($method, $requestUrl, [
            // If it is a json request then specify that key other form_params key
            $isJsonRequest ? 'json' : 'form_parmas' => $formParams,
            'headers'                               => $headers,
            'query'                                 => $queryParams
        ]);

        $response = $response->getBody()->getContents();

        // if method exist in the class that is using this trait hence $this as the first parameter and methdd of that class name in the second then apply this authorization. this is because we need to be flexiable as to not use decode for services that do not need it
        if(method_exists($this,'decodeResponse')){
            // as each services or payment apis decode there response differently again a custom method to decode that response oif its json xml etc
            $response = $this->decodeResponse($response);
        }

        return $response;

    }
}