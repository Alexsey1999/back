<?php

namespace App\Exceptions\Pages;

use DomainException;

class PageMaxCountException extends DomainException
{
    protected $message = 'Достигнуто максимальное количество лендингов для группы';
}
