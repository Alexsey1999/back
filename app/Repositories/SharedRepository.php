<?php

namespace App\Repositories;

use App\Interfaces\DbInterface;
use MongoDB\Model\BSONDocument;

class SharedRepository
{

    const COLLECTION_NAME = 'shared';

    private $db;

    public function __construct(DbInterface $db)
    {
        $this->db = $db;
    }

    public function createCollection(array $data)
    {
        $collection = $this->getDBCollection();
        $incrementId = $this->getNextSharedIncrementId();

        $data['index'] = $incrementId;

        $result = $collection->insertOne($data);

        if ($result->getInsertedId()) {
            return $data['index'];
        } else {
            return 0;
        }
    }

    public function getNextSharedIncrementId()
    {
        $collection = $this->getDBCollection();

        $criteria = [];

        $options = [
            'sort' => [
                'index' => -1
            ],
            'limit' => 1
        ];

        $lastDocument = $collection->find($criteria, $options)->toArray();
        
        if (count($lastDocument) > 0) {
            return $lastDocument[0]->index + 1;
        } else {
            return 1;
        }
    }

    public function getCollection(int $collection_id)
    {
        $collection = $this->getDBCollection();

        $criteria = [
            'index' => $collection_id,
        ];

        $res = $collection->findOne($criteria);

        if (!($res instanceof BSONDocument)) {
            return false;
        }

        $coll = iterator_to_array($res);
        $coll['widget_ids'] = iterator_to_array($coll['widget_ids']);

        return $coll;
    }

    public function updateCollection(int $collection_id, array $values)
    {
        $collection = $this->getDBCollection();

        $criteria = [
            'index' => $collection_id,
        ];

        $values = ['$set' => $values];

        $result = $collection->updateOne($criteria, $values);

        return $result->isAcknowledged();
    }

    public function getDBCollection()
    {
        return $this->db->getConnection()->{self::COLLECTION_NAME};
    }
}