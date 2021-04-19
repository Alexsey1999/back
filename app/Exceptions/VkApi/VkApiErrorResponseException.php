<?php

namespace App\Exceptions\VkApi;

use DomainException;
use Throwable;

class VkApiErrorResponseException extends DomainException
{
    public function __construct($message = "Ошибка при обращению к методу vk api", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
