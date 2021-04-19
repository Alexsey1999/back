<?php

namespace App\Interfaces\Pages;

use App\Pages\Page;
use App\Pages\PagesCollection;
use App\Dto\CreateNewPageData;

interface PagesRepositoryInterface
{
    public function create(CreateNewPageData $data): Page;

    public function getOne(string $pageId): Page;

    public function getGroupPages(int $vk_group_id): PagesCollection;

    public function getGroupPagesList(int $vk_group_id): array;

    public function updateMetaData(Page $page): Page;

    public function deleteMany(array $ids, int $vk_group_id): bool;

    public function getCountByUserReport(): array;

    public function countGroupPages(int $vk_group_id): int;

    public function find(array $params = [], array $options = []): PagesCollection;
}