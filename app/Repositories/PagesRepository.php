<?php

namespace App\Repositories;

use App\Interfaces\Pages\PagesRepositoryInterface;
use App\Interfaces\DbInterface;

use App\Pages\Page;
use App\Pages\PagesFactory;
use App\Pages\PagesCollection;
use App\Formatters\MongoDBFormatter;

use DateTime;
use MongoDB\BSON\UTCDateTime;

use App\Dto\CreateNewPageData;

use App\Exceptions\Pages\PageNotFoundException;
use App\Exceptions\Pages\PageMetaUpdateException;

class PagesRepository implements PagesRepositoryInterface
{

    const COLLECTION_NAME = 'pages';

    private $db;
    private PagesFactory $pagesFactory;
    private MongoDBFormatter $formatter;

    public function __construct(
        DbInterface $db,
        PagesFactory $pagesFactory,
        MongoDBFormatter $formatter
    )
    {
        $this->db = $db;
        $this->pagesFactory = $pagesFactory;
        $this->formatter = $formatter;
    }

    public function create(CreateNewPageData $data): Page
    {
        $page = $this->pagesFactory->create(
            $data->name,
            $data->vk_group_id,
            $data->vk_user_id
        );

        $sortValue = $this->getNextSortValueForGroup($data->vk_group_id);
        $page->setSort($sortValue);

        $insertData = $page->toArray();

        $insertData['created_at'] = $this->formatter->getUTCDateTime($page->getCreatedAt()->getTimestamp() * 1000);
        $insertData['updated_at'] = $this->formatter->getUTCDateTime($page->getUpdatedAt()->getTimestamp() * 1000);
        unset($insertData['id']);
        unset($insertData['blocks']);
        unset($insertData['blocks_edit']);
        unset($insertData['statisticSummary']);
        unset($insertData['states']);


        $collection = $this->getCollection();

        $insertResult = $collection->insertOne($insertData);

        if (!$insertResult->getInsertedId()) {
            throw new \DomainException('Ошибка при создании страницы');
        }

        $page->setId($insertResult->getInsertedId());
        $page->setCreatedAt($insertData['created_at']->toDateTime()); //Приводим к UTC
        $page->setUpdatedAt($insertData['updated_at']->toDateTime()); //Приводим к UTC

        return $page;
    }

    public function getOne(string $pageId): Page
    {
        $collection = $this->getCollection();
        $mongoObjectId = $this->formatter->getObjectId($pageId);

        $criteria = [
            '_id' => $mongoObjectId, 
            // 'status' => [ // Статусы проверяем на уровне сервиса
            //     '$lt' => Page::STATUS_DELETED
            // ]
        ];

        $pageData = $collection->findOne($criteria);

        if (!$pageData) {
            throw new PageNotFoundException('Лендинг не найден');
        }

        $pageData = iterator_to_array($pageData);

        $pageData['id'] = $pageData['_id']->__toString();
        unset($pageData['_id']);

        $pageData['created_at'] = $pageData['created_at']->toDateTime();
        $pageData['updated_at'] = $pageData['updated_at']->toDateTime();

        $page = $this->pagesFactory->loadFromDocument($pageData);

        return $page;

    }

    public function getGroupPages(int $vk_group_id): PagesCollection
    {
        $collection = $this->getCollection();

        $criteria = [
            'vk_group_id' => $vk_group_id,
            'status' => [
                '$ne' => Page::STATUS_DELETED
            ]
        ];

        $options = [
            'sort' => [ 'sort' => -1 ]
        ];

        $pagesCollection = new PagesCollection();
        $pages = $collection->find($criteria, $options);

        foreach($pages as $pageData) {
            $pageData = iterator_to_array($pageData);

            $pageData['id'] = $pageData['_id']->__toString();
            unset($pageData['_id']);

            $pageData['created_at'] = $pageData['created_at']->toDateTime();
            $pageData['updated_at'] = $pageData['updated_at']->toDateTime();

            $page = $this->pagesFactory->loadFromDocument($pageData);

            $pagesCollection->set($page);
        }

        return $pagesCollection;
    }

    public function getGroupPagesList(int $vk_group_id): array
    {
        $collection = $this->getCollection();

        $criteria = [
            'vk_group_id' => $vk_group_id,
            'status' => [
                '$lt' => Page::STATUS_DELETED
            ]
        ];

        $options = [
            'sort' => [ 'sort' => -1 ],
            'projection' => [
                'id' => 1,
                'name' => 1,
                'author_vk_user_id' => 1
            ]
        ];

        $res = [];

        $pages = $collection->find($criteria, $options);

        foreach($pages as $pageData) {
            $res[] = [
                'id' => $pageData['_id']->__toString(),
                'name' => $pageData['name'],
                'vk_user_id' => $pageData['author_vk_user_id']
            ];
        }

        return $res;
    }

    public function updateMetaData(Page $page): Page
    {
        $collection = $this->getCollection();
        $criteria = [
            '_id' => $this->formatter->getObjectId($page->getId()),
        ];

        $values = $page->toArray();
        $values['updated_at'] = $this->formatter->getUTCDateTime($page->getUpdatedAt()->getTimestamp() * 1000);

        unset($values['id']);
        unset($values['created_at']);
        unset($values['blocks']);
        unset($values['blocks_edit']);
        unset($values['statisticSummary']);
        unset($values['states']);

        $options = [
            '$set' => $values
        ];

        $result = $collection->updateOne($criteria, $options);

        if (!$result->isAcknowledged()) {
            throw new PageMetaUpdateException('Ошибка при редактировании');
        }

        $page->setUpdatedAt($values['updated_at']->toDateTime());

        return $page;
    }

    public function deleteMany(array $ids, int $vk_group_id): bool
    {
        $collection = $this->getCollection();
        $object_ids = array_map(function ($id) {
            return $this->formatter->getObjectId($id);
        }, $ids);

        $criteria = [
            '_id' => [
                '$in' => $object_ids
            ],
            'vk_group_id' => $vk_group_id
        ];

        $result = $collection->deleteMany($criteria);

        return $result->isAcknowledged();
    }

    public function getNextSortValueForGroup(int $vk_group_id): int
    {
        $collection = $this->getCollection();

        $criteria = [
            'vk_group_id' => $vk_group_id,
        ];
        $options = [
            'projection' => [
                'sort' => 1
            ],
            'sort' => [
                'sort' => -1
            ],
            'limit' => 1
        ];

        $result = $collection->find($criteria, $options)->toArray();

        if (count($result) === 0) {
            return 1;
        } else {
            return $result[0]->sort + 1;
        }
    }

    public function getCollection()
    {
        return $this->db->getConnection()->{self::COLLECTION_NAME};
    }

    /**
     * Аггергация по количеству созданных промостраниц на пользователя
     * ТОП 30 по умолчанию
     */
    public function getCountByUserReport(): array
    {
        $collection = $this->getCollection();

        $data = $collection->aggregate([
            [ '$sortByCount' => '$author_vk_user_id' ],
            ['$limit' => 30]
        ]);

        $result = [];
        $items = $data->toArray();

        foreach($items as $item) {
            $result[] = [
                'vk_user_id' => $item->_id,
                'count' => $item->count
            ];
        }

        return $result;
    }

    public function countGroupPages(int $vk_group_id): int
    {
        $collection = $this->getCollection();
        $criteria = [
            'vk_group_id' => $vk_group_id,
            'status' => [
                '$lt' => Page::STATUS_DELETED
            ]
        ];

        $result = $collection->countDocuments($criteria);

        return $result;
    }

    public function find(array $params = [], array $options = []): PagesCollection
    {
        $collection = $this->getCollection();

        $criteria = array_merge([], $params);
        $opts = array_merge([], $options);

        if (isset($criteria['_id'])) {
            if (is_array($criteria['_id']) && isset($criteria['_id']['$in'])) {
                $criteria['_id']['$in'] = array_map(function ($i) {
                    return $this->formatter->getObjectId($i);
                }, $criteria['_id']['$in']);
            }
        }

        $pagesCollection = new PagesCollection();
        $pages = $collection->find($criteria, $opts);

        foreach($pages as $pageData) {
            $pageData = iterator_to_array($pageData);

            $pageData['id'] = $pageData['_id']->__toString();
            unset($pageData['_id']);

            $pageData['created_at'] = $pageData['created_at']->toDateTime();
            $pageData['updated_at'] = $pageData['updated_at']->toDateTime();

            $page = $this->pagesFactory->loadFromDocument($pageData);

            $pagesCollection->set($page);
        }

        return $pagesCollection;
    }
}
