<?php

namespace App\Traits;

/**
 * Трейт для сущностей, которые могут быть скопированы
 */
trait Copyable
{
    /**
     * Получить название для копии
     * Название -> Название (копия 1) -> Название (копия 2)
     */
    public function getCopyName(string $name)
    {   
        if (gettype($name) !== 'string') {
            return '';
        }

        $matches = [];
        preg_match('/(.+|)((\s|)\(копия(\s(\d+))?\))$/i', $name, $matches);

        $i = 1;

        if (isset($matches[1])) {
            if(isset($matches[5]) && is_numeric($matches[5])) {
                $i = ((int) $matches[5]) + 1;
            }
        } else {
            $matches[1] = $name;
        }

        return trim(trim($matches[1]) . ' (копия '. $i .')');
    }
}