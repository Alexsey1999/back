<?php

namespace App\Services;

use App\Interfaces\HttpClientInterface;

class VkAppSettingsService 
{

    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Сохранить настройки каталога приложения
     */
    public function saveAppSettings(array $params): array
    {
        $response = $this->client->request('POST', 'https://senler.ru/ajax/vkapp/SaveAppSettings', [
            'form_params' => $params
        ]);
        return json_decode($response, true);
    }

    /**
     * Сохранить настройки систем аналитики приложения
     */
    public function saveAppMetrics(array $params): array
    {
        $response = $this->client->request('POST', 'https://senler.ru/ajax/vkapp/SaveAppMetrics', [
            'form_params' => $params
        ]);
        return json_decode($response, true);
    }

    /**
     * Сохранить настройки списка групп подписчиков
     */
    public function updateSubscriptionsList(array $params): array 
    {
        $body = json_encode($params);
        $response = $this->client->request('POST', 'https://senler.ru/ajax/vkapp/UpdateSubscriptionsList', [
            'body' => $body
        ]);
        return json_decode($response, true);
    }

    /**
     * Загрузить баннер для каталога
     */
    public function saveAppBannerFile(array $form_params, string $file_path): array
    {
        $response = $this->client->request('POST', 'https://senler.ru/ajax/vkapp/SaveAppBannerFile', [
            'multipart' => [
                [
                    'name' => 'params',
                    'contents' => $form_params['params'],
                ],
                [
                    'name' => 'file',
                    'contents' => fopen($file_path, 'r')
                ]
            ]
        ]);
        return json_decode($response, true);
    }

}