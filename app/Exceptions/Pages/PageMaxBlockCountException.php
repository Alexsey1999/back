<?php

namespace App\Exceptions\Pages;

use DomainException;

class PageMaxBlockCountException extends DomainException
{
    protected $message = 'Достигнуто максимальное количество блоков для страницы';
}