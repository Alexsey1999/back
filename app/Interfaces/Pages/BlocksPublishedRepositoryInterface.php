<?php

namespace App\Interfaces\Pages;

interface BlocksPublishedRepositoryInterface 
{
    public function addBlock(string $page_id, array $block_data, array $params): array;

    public function addBlocksFromState(array $blocks);

    public function getPageBlocks(string $page_id): array;

    public function clearPageBlocks(string $page_id): bool;

    /**
     * Получение статистики использования по типам
     */
    public function getUsageStatistic(): array;

    public function getPublishedPagesCount(): int;
}