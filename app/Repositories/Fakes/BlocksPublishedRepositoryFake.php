<?php

namespace App\Repositories\Fakes;

use App\Interfaces\Pages\BlocksPublishedRepositoryInterface;

class BlocksPublishedRepositoryFake implements BlocksPublishedRepositoryInterface
{
    private $storage = [];

    public function addBlock(string $page_id, array $block_data, array $params): array
    {
        $data = $block_data;
        $data['page_id'] = $page_id;
        $data['vk_group_id'] = $params['vk_group_id'];
        $data['sort'] = 1;
        $data['status'] = 1;

        $data['id'] = 'id-test';

        if (!isset($this->storage['id1'])) {
            $this->storage['id1'] = [];
        }

        array_push($this->storage['id1'], $data);

        return $data;
    }

    public function addBlocksFromState(array $blocks)
    {

    }

    public function getPageBlocks(string $page_id): array
    {
        if (isset($this->storage[$page_id])) {
            return $this->storage[$page_id];
        }

        return [];
    }

    public function clearPageBlocks(string $page_id): bool
    {
        if (isset($this->storage[$page_id])) {
            $this->storage[$page_id] = [];
        }

        return true;
    }

    public function getPublishedPagesCount(): int
    {
        return 1;
    }

    public function getUsageStatistic(): array
    {
        return [];
    }
}