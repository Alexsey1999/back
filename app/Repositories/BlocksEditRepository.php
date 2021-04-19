<?php

namespace App\Repositories;

use App\Interfaces\DbInterface;
use App\Interfaces\Pages\BlocksEditRepositoryInterface;

use App\Formatters\MongoDBFormatter;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use DomainException;

class BlocksEditRepository implements BlocksEditRepositoryInterface
{

    const COLLECTION_NAME = 'blocks_edit';

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

    public function getBlock(string $block_id): array 
    {
        $criteria = [
            '_id' => $this->formatter->getObjectId($block_id),
        ];

        $options = [];

        $collection = $this->getCollection();
        $document = $collection->findOne($criteria, $options);

        $block = iterator_to_array($document);

        $block['id'] = $document['_id']->__toString();
        unset($block['_id']);

        $block['page_id'] = $document['page_id']->__toString();

        $block['created']['datetime'] = $block['created']['datetime']->toDateTime()->format('Y-m-d\TH:i:s\Z'); //Приводим к UTC
        $block['updated']['datetime'] = $block['updated']['datetime']->toDateTime()->format('Y-m-d\TH:i:s\Z'); //Приводим к UTC

        return $block;
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
            
            /**
             * Все внутренние BSON объекты преобразуем к массиву
             * BSON Document расширяет класс ArrayObject - поэтому для него доступен каст через (array) 
             */
            if (isset($block['button'])) {
                $block['button'] = (array) $block['button'];
            }

            if (isset($block['items'])) {
                foreach($block['items'] as $index => $item) {
                    $block['items'][$index] = (array) $item;
                    if (isset($item['button'])) {
                        $block['items'][$index]['button'] = (array) $item['button'];
                    }

                    if (isset($item['img'])) {
                        $block['items'][$index]['img'] = (array) $item['img'];
                    }
                }
            }

            if (isset($block['content'])) {
                $block['content'] = (array) $block['content'];
            }

            if (isset($block['background'])) {
                $block['background'] = (array) $block['background'];
            }

            $block['id'] = $document['_id']->__toString();
            unset($block['_id']);

            $block['page_id'] = $document['page_id']->__toString();

            $block['created']['datetime'] = $block['created']['datetime']->toDateTime()->format('Y-m-d\TH:i:s\Z'); //Приводим к UTC
            $block['updated']['datetime'] = $block['updated']['datetime']->toDateTime()->format('Y-m-d\TH:i:s\Z'); //Приводим к UTC
            $res[] = $block;
        }

        return $res;
    }

    public function addBlock(string $page_id, array $block_data, array $params): array
    {
        
        $data = $block_data;

        $data['page_id'] = $this->formatter->getObjectId($page_id);
        $data['vk_group_id'] = $params['vk_group_id'];
        $data['sort'] = isset($params['sort_value']) && is_int($params['sort_value']) ? $params['sort_value'] : $this->getNextSortValueForPage($page_id);
        $data['status'] = 1;

        $data['created'] = [
            'datetime' => $this->formatter->getUTCDateTime(time() * 1000),
            'vk_user_id' => $params['vk_user_id'] 
        ];
        
        $data['updated'] = [
            'datetime' => $this->formatter->getUTCDateTime(time() * 1000),
            'vk_user_id' => $params['vk_user_id'] 
        ];

        unset($data['id']);

        $collection = $this->getCollection();

        $insert_result = $collection->insertOne($data);
        $inserted_id = $insert_result->getInsertedId();
        if (!$inserted_id) {
            throw new \DomainException('Ошибка при создании блока');
        }

        /**
         * Назначим id для кнопок
         */
        if (isset($data['button']) && is_array($data['button'])) {
            $data['button']['id'] = $inserted_id . '0';
            $collection->updateOne([
                '_id' => $inserted_id
            ], [
                '$set' => [
                    'button' => $data['button']
                ]
            ]);
        }

        if ($data['type'] === 'products' && isset($data['items']) && is_array($data['items'])) {
            foreach($data['items'] as $index => &$item) {
                if (isset($item['button']) && is_array($item['button'])) {
                    $item['button']['id'] = 'p' . $inserted_id . (string)$index;
                }
            }
            $collection->updateOne([
                '_id' => $inserted_id
            ], [
                '$set' => [
                    'items' => $data['items']
                ]
            ]);
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

    public function deleteBlock(string $block_id, int $vk_group_id) 
    {

        $collection = $this->getCollection();

        $criteria = [
            '_id' => $this->formatter->getObjectId($block_id),
            'vk_group_id' => $vk_group_id
        ];

        $options = [];

        $result = $collection->deleteOne($criteria, $options);

        return $result->isAcknowledged();
    }

    public function updateBlockFields(string $block_id, array $fields, array $params) 
    {

        $collection = $this->getCollection();
        
        $criteria = [
            '_id' => $this->formatter->getObjectId($block_id),
            'vk_group_id' => (int)$params['vk_group_id']
        ];

        $block_exists = $collection->find($criteria, [])->toArray();

        if (!isset($block_exists) || count($block_exists) <= 0) {
            throw new DomainException('Block do not exists');
        }

        $fields['updated'] = [
            'datetime' => $this->formatter->getUTCDateTime(time() * 1000),
            'vk_user_id' => (int)$params['vk_user_id'] 
        ];

        $options = [
            '$set' => $fields
        ];


        $result = $collection->updateOne($criteria, $options);

        if (!$result->isAcknowledged()) {
            throw new DomainException('Ошибка при обновлении блока');
        }
        
        return $fields;
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

    public function getCountBlocksForPage(string $page_id): int
    {
        $collection = $this->getCollection();

        $criteria = [
            'page_id' => $this->formatter->getObjectId($page_id),
        ];
        
        $options = [];

        $result = $collection->find($criteria, $options)->toArray();

        return count($result);
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