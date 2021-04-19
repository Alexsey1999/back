<?php

namespace App\Repositories;

use App\Interfaces\DbInterface;
use App\Interfaces\Pages\BlocksPublishedRepositoryInterface;

use App\Formatters\MongoDBFormatter;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use DomainException;

class BlocksPublishedRepository implements BlocksPublishedRepositoryInterface
{

    const COLLECTION_NAME = 'blocks';

    private $db;
    private MongoDBFormatter $formatter;

    public function __construct(
        DbInterface $db, 
        MongoDBFormatter $formatter
    )
    {
        $this->db = $db;
        $this->formatter = $formatter;
    }

    public function addBlock(string $page_id, array $block_data, array $params): array
    {
        $data = $block_data;

        $data['page_id'] = $this->formatter->getObjectId($page_id);
        $data['vk_group_id'] = $params['vk_group_id'];
        $data['sort'] = $block_data['sort'];
        $data['status'] = 1;
        
        $data['created'] = [
            'datetime' => $this->formatter->getUTCDateTime((new DateTime($block_data['created']['datetime']))->getTimestamp() * 1000),
            'vk_user_id' => $block_data['created']['vk_user_id'] 
        ];
        
        $data['updated'] = [
            'datetime' => $this->formatter->getUTCDateTime((new DateTime($block_data['updated']['datetime']))->getTimestamp() * 1000),
            'vk_user_id' => $block_data['created']['vk_user_id'] 
        ];

        unset($data['id']);

        $collection = $this->getCollection();

        $insert_result = $collection->insertOne($data);

        if (!$insert_result->getInsertedId()) {
            throw new \DomainException('Ошибка при создании блока');
        }

        $data['id'] = $insert_result->getInsertedId()->__toString();
        $data['page_id'] = $page_id;
        $data['created']['datetime'] = $data['created']['datetime']->toDateTime()->format('Y-m-d\TH:i:s\Z'); //Приводим к UTC
        $data['updated']['datetime'] = $data['updated']['datetime']->toDateTime()->format('Y-m-d\TH:i:s\Z'); //Приводим к UTC

        return $data;
    }

    public function addBlocksFromState(array $blocks)
    {
        $documents = [];

        foreach($blocks as $block) {
            $b = $block;
            unset($b['id']);

            $page_id = $this->formatter->getObjectId($b['page_id']);

            $b['created'] = [
                'datetime' => $this->formatter->getUTCDateTime((new DateTime($b['created']['datetime']))->getTimestamp() * 1000),
                'vk_user_id' => (int)$b['created']['vk_user_id']
            ];
            
            $b['updated'] = [
                'datetime' => $this->formatter->getUTCDateTime((new DateTime($b['updated']['datetime']))->getTimestamp() * 1000),
                'vk_user_id' => (int)$b['updated']['vk_user_id']
            ];

            $b['page_id'] = $page_id;

            $documents[] = $b;
        }

        $collection = $this->getCollection();

        $insert_result = $collection->insertMany($documents);

        return $insert_result->isAcknowledged();
    }

    public function getPageBlocks(string $page_id): array
    {
        $criteria = [
            'page_id' => $this->formatter->getObjectId($page_id),
        ];

        $options = [
            'sort' => [
                'sort' => 1
            ]
        ];

        $collection = $this->getCollection();
        $cursor = $collection->find($criteria, $options);

        $items = iterator_to_array($cursor);

        $res = [];

        foreach($items as $document) {

            $block = iterator_to_array($document);

            $block['id'] = $document['_id']->__toString();
            unset($block['_id']);

            $block['page_id'] = $document['page_id']->__toString();

            $block['created']['datetime'] = $block['created']['datetime']->toDateTime()->format('Y-m-d\TH:i:s\Z'); //Приводим к UTC
            $block['updated']['datetime'] = $block['updated']['datetime']->toDateTime()->format('Y-m-d\TH:i:s\Z'); //Приводим к UTC
            $res[] = $block;
        }

        return $res;
    }

    public function clearPageBlocks(string $page_id): bool
    {
        $criteria = [
            'page_id' => $this->formatter->getObjectId($page_id),
        ];

        $collection = $this->getCollection();

        $result = $collection->deleteMany($criteria, []);

        return $result->isAcknowledged();
    }

    public function getUsageStatistic(): array
    {

        $collection = $this->getCollection();

        $data = $collection->aggregate([
            [ '$sortByCount' => '$key' ],
            ['$limit' => 50]
        ]);

        return iterator_to_array($data);
    }

    /**
     * Получение общего количетства страниц, для которых есть опубликованные блоки
     */
    public function getPublishedPagesCount(): int
    {
        $collection = $this->getCollection();

        $data = $collection->aggregate([
            [ 
                '$group' => [
                    '_id' => '$page_id'
                ] 
            ],
            [ 
                '$group' => [
                    '_id' => 1,
                    'count' => [
                        '$sum' => 1
                    ]
                ]
            ]
        ]);

        $d = iterator_to_array($data);
        return $d[0]['count'];
    }

    public function getNextSortValueForPage(string $page_id): int
    {
        $collection = $this->getCollection();

        $criteria = [
            'page_id' => $this->formatter->getObjectId($page_id),
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
}