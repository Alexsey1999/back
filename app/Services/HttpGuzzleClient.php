<?php

namespace App\Services;

use App\Interfaces\HttpClientInterface;
use GuzzleHttp\Client;

class HttpGuzzleClient implements HttpClientInterface
{

    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function request(string $method, string $url, array $params)
    {
        $response = $this->client->request($method, $url, $params);
        return $response->getBody()->getContents();
    }
}