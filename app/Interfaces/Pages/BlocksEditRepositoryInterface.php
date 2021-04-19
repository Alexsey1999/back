<?php

namespace App\Interfaces\Pages;

interface BlocksEditRepositoryInterface
{
    public function getBlock(string $block_id): array;

    public function getPageBlocks(string $page_id): array;

    public function addBlock(string $page_id, array $block_data, array $params): array;

    public function addBlocksFromState(array $blocks);

    public function deleteBlock(string $block_id, int $vk_group_id);

    public function updateBlockFields(string $block_id, array $fields, array $params);

    public function clearPageBlocks(string $page_id): bool;

    public function getCountBlocksForPage(string $page_id): int;
}