<?php

namespace App\Exceptions\Pages;

use DomainException;

class UserIsNotGroupEditorException extends DomainException
{
    protected $message = 'Пользователь не является редактором в группе';
}
