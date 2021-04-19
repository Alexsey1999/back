<?php

namespace App\Formatters;

class PageVariableReplacer 
{

    const RELATIONS = array(
        array('не указано', 'не женат', 'есть подруга', 'помолвлен', 'женат', 'всё сложно', 'в активном поиске', 'влюблён', 'в гражданском браке'),
        array('не указано', 'не замужем', 'есть друг', 'помолвлена', 'замужем', 'всё сложно', 'в активном поиске', 'влюблена', 'в гражданском браке'),
    );

    private $vk_user = [];

    public  function setVkUserData(array $vk_user)
    {
        $this->vk_user = $vk_user;
    }

    public function replace(array $blocks): array
    {

        $data = [];

        foreach($blocks as $block)
        {

            if(isset($block['content'])) {
                $block['content']['title'] = $this->dateReplacement($block['content']['title']);
                $block['content']['title'] = $this->variableReplacement($block['content']['title']);

                $block['content']['text'] = $this->dateReplacement($block['content']['text']);
                $block['content']['text'] = $this->variableReplacement($block['content']['text']);
            }
    
            if(isset($block['button'])) {
                $block['button']['text'] = $this->dateReplacement($block['button']['text']);
                $block['button']['text'] = $this->variableReplacement($block['button']['text']);
            }

            $data[] = $block;
        }

        return $data;
    }

    /**
     * Замена переменных даты в строке
     */
    private function dateReplacement(string $str): string
    {
        return (string)preg_replace_callback("/(\[date\]([^\|\[]*)\|?([^\[]*)\[\/date\])/im", function ($matches) {

            $format = $matches[2];
            $date = (isset($matches[3]) && trim($matches[3]) ? strtotime(trim($matches[3])) : time());

            $vars = [
                '%month' => explode("|", '|января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря'),
                '%Month' => explode("|", '|Января|Февраля|Марта|Апреля|Мая|Июня|Июля|Августа|Сентября|Октября|Ноября|Декабря'),
            ];

            foreach ($vars as $key => $value) {
                switch ($key) {
                    case '%month':
                    case '%Month':
                        $format = preg_replace("~\\$key~", $value[date('n', $date)], $format);
                        break;
                }
            }

            $format = preg_replace("~%e~", date('j', $date), $format); // день месяца без ведущего нуля и пробела

            return strftime($format, $date);
        }, $str);
    }

    /**
     * Замена переменнных пользователя в строке
     */
    private function variableReplacement(string $str): string
    {
        if ($this->vk_user) {
            // [gender]
            $gender_index = 1;
            if ($this->vk_user['sex'] == 1) $gender_index = 2; // для девочек
            $str = preg_replace('/\[gender\]([^\|]+)\|?([^\|]+)?\[\/gender\]/im', "$$gender_index", $str);
            $str = preg_replace('/\[gender\]([^\|]+)\|?([^\|]+)?\[\/gender\]/im', "$1", $str); // если первая замена не прошла

            // [city]
            $city_index = 1;
            if (!$this->vk_user['city']['title']) $city_index = 2; // для тех у кого нет города
            $str = preg_replace('/\[city\]([^\|]+)\|?([^\|]+)?\[\/city\]/im', "$$city_index", $str);
            $str = preg_replace('/\[city\]([^\|]+)\|?([^\|]+)?\[\/city\]/im', "$1", $str); // если первая замена не прошла

            // [country]
            $country_index = 1;
            if (!$this->vk_user['country']['title']) $country_index = 2; // для тех у кого нет страны
            $str = preg_replace('/\[country\]([^\|]+)\|?([^\|]+)?\[\/country\]/im', "$$country_index", $str);
            $str = preg_replace('/\[country\]([^\|]+)\|?([^\|]+)?\[\/country\]/im', "$1", $str); // если первая замена не прошла

            // [relation]
            $relation_index = 1;
            if (!$this->vk_user['relation']) $relation_index = 2; // для тех у кого нет семейного положения
            $str = preg_replace('/\[relation\]([^\|]+)\|?([^\|]+)?\[\/relation\]/im', "$$relation_index", $str);
            $str = preg_replace('/\[relation\]([^\|]+)\|?([^\|]+)?\[\/relation\]/im', "$1", $str); // если первая замена не прошла

            // simple
            if (preg_match('~%city%~', $str)) $str = str_replace('%city%', $this->vk_user['city']['title'], $str);
            if (preg_match('~%country%~', $str)) $str = str_replace('%country%', $this->vk_user['country']['title'], $str);
            if (preg_match('~%userid%~', $str)) $str = str_replace('%userid%', $this->vk_user['id'], $str);
            if (preg_match('~%username%~', $str)) $str = str_replace('%username%', $this->vk_user['first_name'], $str);
            if (preg_match('~%fullname%~', $str)) $str = str_replace('%fullname%', "{$this->vk_user['first_name']} {$this->vk_user['last_name']}", $str);
            if (preg_match('~%relation%~', $str)) $str = str_replace('%relation%', $this->getRelation($this->vk_user), $str);
        }
        return (string)$str;
    }

    public function getRelation($vk_user)
    {
        $index = 0;
        if ($vk_user['sex'] == 1) $index = 1; // для девочек

        if (!isset(self::RELATIONS[$index][$vk_user['relation']])) return '';
        return self::RELATIONS[$index][$vk_user['relation']];
    }
}