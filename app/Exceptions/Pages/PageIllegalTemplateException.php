<?php

namespace App\Exceptions\Pages;

use DomainException;

class PageIllegalTemplateException extends DomainException
{
    protected $message = 'Шаблон можно применить только к новой странице';
}