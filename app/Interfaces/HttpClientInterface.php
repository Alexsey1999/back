<?php

namespace App\Interfaces;

interface HttpClientInterface
{
    public function request(string $method, string $url, array $params);
}