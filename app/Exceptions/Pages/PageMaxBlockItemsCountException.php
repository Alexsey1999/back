<?php

namespace App\Exceptions\Pages;

use DomainException;

class PageMaxBlockItemsCountException extends DomainException
{
    protected $message = 'Достигнуто максимальное количество элементов для блока';
}
