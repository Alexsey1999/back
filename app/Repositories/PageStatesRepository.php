<?php

namespace App\Repositories;

use App\Interfaces\DbInterface;
use App\Interfaces\Pages\PageStatesRepositoryInterface;

use App\Formatters\MongoDBFormatter;

use DomainException;

/**
 * Хранилице состояний страницы
 */
class PageStatesRepository implements PageStatesRepositoryInterface
{

    const COLLECTION_NAME = 'pages_states';
    const MAX_STATES_COUNT = 15;

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

    public function addState(array $data): bool
    {

        $state = $data;

        $page_id = (string)$state['page_id'];

        $state['page_id'] = $this->formatter->getObjectId($page_id);
        $state['created_at'] = $this->formatter->getUTCDateTime(time() * 1000);

        $states = $this->getPageStates($page_id);

        if (count($states) >= self::MAX_STATES_COUNT) {
            $delete_state_id = $states[count($states) - 1]['id'];
            $this->deleteState($delete_state_id);
        }

        $collection = $this->getCollection();
        $insert_result = $collection->insertOne($state);

        return $insert_result->getInsertedId() ? true : false;
    }

    public function getPageStates(string $page_id): array
    {

        $criteria = [
            'page_id' => $this->formatter->getObjectId($page_id),
        ];

        $options = [
            'sort' => [
                '_id' => -1
            ]
        ];

        $collection = $this->getCollection();
        $cursor = $collection->find($criteria, $options);

        $items = iterator_to_array($cursor);

        $res = [];

        foreach($items as $document) {

            $state = iterator_to_array($document);

            $state['id'] = $document['_id']->__toString();
            unset($state['_id']);

            $state['page_id'] = $document['page_id']->__toString();

            $state['created_at'] = $state['created_at']->toDateTime()->format('Y-m-d\TH:i:s\Z'); //Приводим к UTC
            $res[] = $state;
        }

        return $res;
    }

    public function deleteState(string $state_id)
    {

        $collection = $this->getCollection();

        $criteria = [
            '_id' => $this->formatter->getObjectId($state_id),
        ];

        $options = [];

        $result = $collection->deleteOne($criteria, $options);

        return $result->isAcknowledged();
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