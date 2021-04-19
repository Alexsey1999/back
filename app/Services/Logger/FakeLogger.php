<?php

namespace App\Services\Logger;

use App\Interfaces\LoggerInterface;

class FakeLogger implements LoggerInterface
{
    private $hosts;
    private $client;
    
    public function save(array $error)
    {
        
    }
}