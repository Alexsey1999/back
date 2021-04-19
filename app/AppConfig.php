<?php

namespace App;

use App\Interfaces\AppConfigProviderInterface;

class AppConfig implements AppConfigProviderInterface
{

    /**
     * Все доступные vk mini apps приложения с которыми ведется работа 
     * и с которых можно принимать запросы
     */
    private $vk_apps = [];
    
    /**
     * Тестовый пользователь вк с полями
     * - ID
     * - Токен доступа пользователя
     */
    private $vk_test_user = [];
    
    /**
     * 
     */
    private string $vk_app_id;
    
    /**
     * 
     */
    private string $client_secret;
    
    /**
     * 
     */
    private string $service_key;

    /**
     *  Окружение
     */
    private string $env;

    public function __construct(int $vk_app_id, array $vk_mini_apps, array $vk_test_user)
    {

        $this->vk_apps = $vk_mini_apps;
        $this->vk_test_user = $vk_test_user;

        $config = $this->getAppConfig($vk_app_id);

        $this->vk_app_id = $vk_app_id;
        $this->client_secret = $config['client_secret'];
        $this->service_key = $config['service_key'];
    }


    public function setEnv(string $env)
    {
        $this->$env = $env;
    }

    public function getEnv(): string
    {
        return $this->env;
    }

    public function getVKAppId(): int
    {
        return $this->vk_app_id;
    }

    public function getClientSecret(): string 
    {
        return $this->client_secret;
    }

    public function getServiceKey(): string
    {
        return $this->service_key;
    }

    public function getTestVkUserId(): int
    {
        return $this->vk_test_user['vk_user_id'];
    }

    public function getTestUserToken(): string
    {
        return $this->vk_test_user['access_token'];
    }

    private function getAppConfig(int $vk_app_id): array
    {
        if (!isset($this->vk_apps[$vk_app_id])) {
            throw new \InvalidArgumentException('Invalid app id');
        }

        return $this->vk_apps[$vk_app_id];
    }
}
