<?php

namespace App\Dto;

use DomainException;

class CreateNewPageData 
{
    public string $name;
    public int $vk_user_id;
    public int $vk_group_id;

    public function __construct($data)
    {
        if (!isset($data['name']) || !isset($data['vk_user_id']) || !isset($data['vk_group_id'])) {
            throw new DomainException('Неверные параметры - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        }

        $this->name = $data['name'];
        $this->vk_user_id = $data['vk_user_id'];
        $this->vk_group_id = $data['vk_group_id'];
    }
}