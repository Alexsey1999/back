<?php

namespace App\Services;

use App\Interfaces\HttpClientInterface;

class SubscriptionsService
{
    private $client;
    private $host = 'https://senler.ru';

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getSubscriptionsForUser(array $params): array
    {
        $body = json_encode($params);
        $response = $this->client->request('POST', $this->host . '/ajax/vkapp/GetSubscriptions', [
            'body' => $body
        ]);

        return json_decode($response, true);
    }

    public function getPromoPageData(array $params): array
    {
        $body = json_encode($params);
        $response = $this->client->request('POST', $this->host . '/ajax/vkapp/GetPromoPageData', [
            'body' => $body
        ]);

        return json_decode($response, true);
    }

    public function getSubscriptionsForAdmin(array $params): array
    {
        $body = json_encode($params);

        $response = $this->client->request('POST', $this->host . '/ajax/vkapp/GetAdminSubscriptions', [
            'body' => $body
        ]);

        return json_decode($response, true);
    }

    public function subscribe(array $params)
    {
        $body = json_encode($params);

        $response = $this->client->request('POST', $this->host . '/ajax/vkapp/Subscribe', [
            'body' => $body
        ]);

        return json_decode($response, true);
    }

    public function unSubscribe(array $params)
    {
        $body = json_encode($params);

        $response = $this->client->request('POST', $this->host . '/ajax/vkapp/UnSubscribe', [
            'body' => $body
        ]);

        return json_decode($response, true);
    }

    public function unSubscribeAll(array $params)
    {
        $body = json_encode($params);

        $response = $this->client->request('POST', $this->host . '/ajax/vkapp/UnSubscribeAll', [
            'body' => $body
        ]);

        return json_decode($response, true);
    }

    public function addToBot(array $params)
    {
        $body = json_encode($params);

        $response = $this->client->request('POST', $this->host . '/ajax/vkapp/AddToBot', [
            'body' => $body
        ]);

        return json_decode($response, true);
    }

    public function createOrder(array $params)
    {
        $body = json_encode($params);

        $response = $this->client->request('POST', $this->host . '/ajax/vkapp/CreateOrder', [
            'body' => $body
        ]);

        return json_decode($response, true);
    }

    public function checkOrder(array $params)
    {
        $body = json_encode($params);

        $response = $this->client->request('POST', $this->host . '/ajax/vkapp/CheckOrder', [
            'body' => $body
        ]);

        return json_decode($response, true);
    }

    public function saveLead(array $params)
    {

        $response = $this->client->request('POST', $this->host . '/ajax/vkapp/SaveLead', [
            'query' => [
                'data' => json_encode($params['data']),
                'params' => json_encode($params['params'])
            ],
            'timeout' => 10, // Response timeout
            'connect_timeout' => 10, // Connection timeout
        ]);

        return json_decode($response, true);
    }

    public function saveLeadTrigger(array $params)
    {

        $response = $this->client->request('POST', $this->host . '/ajax/vkapp/SaveLeadTrigger', [
            'query' => [
                'data' => json_encode($params['data']),
                'params' => json_encode($params['params'])
            ],
            'timeout' => 10, // Response timeout
            'connect_timeout' => 10, // Connection timeout
        ]);

        return json_decode($response, true);
    }

}
