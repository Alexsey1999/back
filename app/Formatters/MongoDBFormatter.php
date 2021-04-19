<?php

namespace App\Formatters;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

use \MongoDB\Driver\Exception\InvalidArgumentException;
use App\Exceptions\MongoDB\InvalidObjectIdException;

class MongoDBFormatter 
{
    public function getUTCDateTime(int $timestamp)
    {
        return new UTCDateTime($timestamp);
    }

    public function getObjectId(string $id): ObjectId
    {
        try {
            return new ObjectId($id);
        } catch (\Throwable $e) {
            throw new InvalidObjectIdException($e->getMessage());
        }
    }
}