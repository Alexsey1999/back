<?php

namespace App\Services\Logger;

use App\Interfaces\LoggerInterface;
use App\Interfaces\DbInterface;

class MongoDBErrorLogger implements LoggerInterface
{
    const COLLECTION_NAME = 'errors';

    private $storage;

    public function __construct(DbInterface $db)
    {
        $this->storage = $db;
    }

    public function save(array $error) 
    {
        $collection = $this->storage->getConnection()->{self::COLLECTION_NAME};
        return $collection->insertOne($error);
    }
}