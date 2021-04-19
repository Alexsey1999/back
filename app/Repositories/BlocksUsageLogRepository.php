<?php

namespace App\Repositories;

use App\Interfaces\Pages\BlocksUsageLogRepositoryInterface;
use App\Interfaces\DbInterface;

use App\Formatters\MongoDBFormatter;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use DomainException;

class BlocksUsageLogRepository implements BlocksUsageLogRepositoryInterface
{
    const COLLECTION_NAME = 'blocks_usage_log';

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

    /**
     * Добавить новую запись в лог использования блоков
     */
    public function saveItem(array $params)
    {

        $collection = $this->getCollection();

        // Подсчитаем количество блоков в логе для сообщества
        $count_items = $collection->count([
            'vk_group_id' => (int)$params['vk_group_id']    
        ]);

        // Если количество превышает максимальное количество
        if ($count_items >= self::MAX_GROUP_ITEMS) {

            // Удалим самый старый элемент
            $collection->deleteOne([
                'vk_group_id' => (int)$params['vk_group_id']
            ], [
                'sort' => [
                    '_id' => 1
                ],
                'limit' => 1
            ]);
        }

        $data = [];

        $data['page_id'] = $this->formatter->getObjectId($params['page_id']);
        $data['vk_group_id'] = (int)$params['vk_group_id'];
        $data['type'] = $params['type'];
        $data['key'] = $params['key'];
        $data['sub_type'] = $params['sub_type'];
        $data['created'] = $this->formatter->getUTCDateTime((new DateTime())->getTimestamp() * 1000);

        $insert_result = $collection->insertOne($data);

        if (!$insert_result->getInsertedId()) {
            throw new \DomainException('Ошибка при создании блока');
        }

        return true;
    }

    public function getRecent(int $vk_group_id, $count = 50)
    {

        $collection = $this->getCollection();

        $criteria = [
            'vk_group_id' => $vk_group_id
        ];

        $options = [
            'sort' => [
                '_id' => -1
            ],
            'projection' => [
                'type' => 1,
                'sub_type' => 1,
                'key' => 1
            ],
            'limit' => $count
        ];

        $results = $collection->find($criteria, $options)->toArray();

        $data = [];

        foreach($results as $item) {
            if (!isset($data[$item['key']])) {
                unset($item['_id']);
                $data[$item['key']] = $item;
            }
        }

        return array_values($data);
    }

    public function getCollection()
    {
        return $this->db->getConnection()->{self::COLLECTION_NAME};
    }
}