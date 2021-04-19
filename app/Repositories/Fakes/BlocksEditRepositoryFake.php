<?php

namespace App\Repositories\Fakes;

use App\Interfaces\Pages\BlocksEditRepositoryInterface;

class BlocksEditRepositoryFake implements BlocksEditRepositoryInterface
{
    private $storage = [];

    public function getBlock(string $block_id): array
    {
        return [];
    }

    public function getPageBlocks(string $page_id): array
    {
        if (!isset($this->storage[$page_id])) {
            return [];
        }
        
        return $this->storage[$page_id];
    }

    public function addBlock(string $page_id, array $block_data, array $params): array
    {

        $data = $block_data;

        $data['page_id'] = $page_id;
        $data['vk_group_id'] = $params['vk_group_id'];
        $data['sort'] = (int)$params['sort_value'];
        $data['status'] = 1;

        $data['created'] = [
            'datetime' => time() * 1000,
            'vk_user_id' => $params['vk_user_id'] 
        ];
        
        $data['updated'] = [
            'datetime' => time() * 1000,
            'vk_user_id' => $params['vk_user_id'] 
        ];

        unset($data['id']);
        $data['id'] = 1;
        $data['page_id'] = $page_id;

        if (!isset($this->storage[$page_id])) {
            $this->storage[$page_id] = [];
        }

        array_push($this->storage[$page_id], $block_data);
        
        return $data;
    }

    public function addBlocksFromState(array $blocks)
    {

    }

    public function deleteBlock(string $block_id, int $vk_group_id)
    {

    }

    public function updateBlockFields(string $block_id, array $fields, array $params)
    {
        
    }

    public function clearPageBlocks(string $page_id): bool
    {
        return true;
    }

    public function getCountBlocksForPage(string $page_id): int
    {
        if (!isset($this->storage[$page_id])) {
            return 0;
        }

        return count($this->storage[$page_id]);
    }

}