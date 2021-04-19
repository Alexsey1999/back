<?php

namespace App\Interfaces\Pages;

interface BlocksUsageLogRepositoryInterface
{   

    const MAX_GROUP_ITEMS = 10;

    /**
     * Метод для сохрарения новой записи
     */
    public function saveItem(array $params);

    /**
     * Метод для получения поледних $count записей для $vk_group_id
     */
    public function getRecent(int $vk_group_id, $count = 50);
}