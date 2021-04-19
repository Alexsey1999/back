<?php

namespace App\Repositories\Fakes;

use App\Interfaces\Pages\BlocksUsageLogRepositoryInterface;

class BlocksUsageLogRepositoryFake implements BlocksUsageLogRepositoryInterface
{
    private $storage = [];

    public function saveItem(array $params)
    {
        if (count($this->storage) >= self::MAX_GROUP_ITEMS) {
            array_shift($this->storage);
        }

        $this->storage[] = $params;
    }

    public function getRecent(int $vk_group_id, $count = 50)
    {
        return $this->storage;
    }
}