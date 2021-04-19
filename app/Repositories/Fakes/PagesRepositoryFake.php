<?php

namespace App\Repositories\Fakes;

use App\Dto\CreateNewPageData;
use App\Interfaces\Pages\PagesRepositoryInterface;
use App\Pages\Page;
use App\Pages\PagesCollection;
use App\Pages\PagesFactory;
use App\Formatters\MongoDBFormatter;

class PagesRepositoryFake implements PagesRepositoryInterface 
{
    private $storage = [];

    private PagesFactory $pagesFactory;
    private MongoDBFormatter $formatter;

    public function __construct()
    {
        $this->pagesFactory = new PagesFactory();
        $this->formatter = new MongoDBFormatter();
    }

    public function create(CreateNewPageData $data): Page
    {
        $page = $this->pagesFactory->create(
            $data->name, 
            $data->vk_group_id, 
            $data->vk_user_id
        );
        
        $sortValue = 1;
        $page->setSort($sortValue);
        $page->setId('id1');

        $insertData = $page->toArray();

        $insertData['created_at'] = $this->formatter->getUTCDateTime($page->getCreatedAt()->getTimestamp() * 1000);
        $insertData['updated_at'] = $this->formatter->getUTCDateTime($page->getUpdatedAt()->getTimestamp() * 1000);
        unset($insertData['id']);

        $this->storage['id1'] = $page;

        return $page;
    }

    public function getOne(string $pageId): Page 
    {
        if (isset($this->storage[$pageId])) {
            $page = $this->storage[$pageId];
            $page->setBlocks([]);
            $page->setBlocksEdit([]);
            $page->setStatisticSummary([]);
            $page->setStates([]);
            return $page;
        }

        return null;
    }

    public function getGroupPages(int $vk_group_id): PagesCollection
    {
        return new PagesCollection();
    }

    public function getGroupPagesList(int $vk_group_id): array
    {
        return [];
    }

    public function updateMetaData(Page $page): Page
    {
        return new Page();
    }

    public function deleteMany(array $ids, int $vk_group_id): bool
    {
        return true;
    }

    public function getCountByUserReport(): array
    {
        return [];
    }

    public function countGroupPages(int $vk_group_id): int
    {
        return 10;
    }

    public function find(array $params = [], array $options = []): PagesCollection
    {
        return new PagesCollection();
    }
}