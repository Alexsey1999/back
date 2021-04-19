<?php

namespace App\Interfaces;

interface AppConfigProviderInterface
{
    public function __construct(int $vk_app_id, array $vk_mini_apps, array $vk_test_user);

    public function getEnv(): string;

    public function getVKAppId(): int;

    public function getClientSecret(): string;

    public function getServiceKey(): string;

    public function getTestVkUserId(): int;

    public function getTestUserToken(): string;
}