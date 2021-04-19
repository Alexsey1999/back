<?php

namespace App\Repositories;

use App\Interfaces\WidgetsRepositoryInterface;
use App\Interfaces\DbInterface;
use MongoDB\Model\BSONDocument;
use MongoDB\BSON\ObjectId;
use App\Widgets\Models\BaseWidget;
use App\Widgets\WidgetCollection;
use App\Widgets\WidgetFactory;
use Exception;

class WidgetsRepository implements WidgetsRepositoryInterface
{

    const COLLECTION_NAME = 'widget';

    private $db;
    private $widgetFactory;

    public function __construct(DbInterface $db, WidgetFactory $widgetFactory)
    {
        $this->db = $db;
        $this->widgetFactory = $widgetFactory;
    }

    public function getOne(string $id): BaseWidget
    {
        $collection = $this->getCollection();
        $criteria = [
            '_id' => new ObjectId($id),
        ];

        $result = $collection->findOne($criteria);

        if (!($result instanceof BSONDocument)) {
            return false;
        }

        $document = iterator_to_array($result);
        $document['id'] = $document['_id']->__toString();
        unset($document['_id']);

        $widget = $this->widgetFactory->createWidget($document['type']);
        $widget->loadFromDocument($document);

        return $widget;
    }

    public function getMany(array $ids): WidgetCollection
    {

        $collection = $this->getCollection();

        $mongoIds = array_map(function($id) {
            return new ObjectId($id);
        }, $ids);

        $criteria = [
            '_id' => ['$in' => $mongoIds]
        ];

        $widgets = $collection->find($criteria)->toArray();

        $widgetCollection = new WidgetCollection();

        foreach($widgets as $model)
        {
            $doc = iterator_to_array($model);
            $doc['id'] = $doc['_id']->__toString();
            unset($doc['_id']);

            $widget = $this->widgetFactory->createWidget($doc['type']);
            $widget->loadFromDocument($doc);

            $widgetCollection->set($widget);
        }

        return $widgetCollection;
    }

    public function deleteOne(string $id, int $group_id): bool 
    {
        $collection = $this->getCollection();
        
        $criteria = [
            '_id' => new ObjectId($id),
            'group_id' => $group_id
        ];

        $result = $collection->deleteOne($criteria);

        return $result->isAcknowledged();
    }

    public function deleteMany(array $ids, int $group_id): bool 
    {
        $collection = $this->getCollection();
        $object_ids = array_map(function ($id) {
            return new ObjectId($id);
        }, $ids);
        
        $criteria = [
            '_id' => [
                '$in' => $object_ids
            ],
            'group_id' => $group_id
        ];

        $result = $collection->deleteMany($criteria);

        return $result->isAcknowledged();
    }

    /**
     * Get group widgets by given id
     */
    public function getGroupWidgets(int $group_id): WidgetCollection
    {
        
        $widgetCollection = new WidgetCollection();

        try {

            $collection = $this->getCollection();

            $criteria = [
                'group_id' => $group_id,
            ];
            $options = [
                'sort' => [ 'sort' => 1 ]
            ];

            $widgets = $collection->find($criteria, $options)->toArray();


            foreach($widgets as $model)
            {
                $doc = iterator_to_array($model);
                $doc['id'] = $doc['_id']->__toString();
                unset($doc['_id']);
                
                $widget = $this->widgetFactory->createWidget($doc['type']);
                $widget->loadFromDocument($doc);

                $widgetCollection->set($widget);
            }

        } catch (\Throwable $e) {
            throw $e;
        }

        return $widgetCollection;
    }

    public function createWidget($data): BaseWidget 
    {

        $widget = $this->widgetFactory->createWidget($data['type']);
        $widget->create($data);

        $sortValue = $this->getNextSortValueForGroup($widget->group_id, $widget->type);
        $widget->setInitialSort($sortValue);

        $write_data = $widget->toArray();

        unset($write_data['id']);

        $collection = $this->getCollection();

        $insertResult = $collection->insertOne($write_data);

        if (!$insertResult->getInsertedId()) {
            throw new \DomainException('Ошибка при создании виджета');
        }

        $widget->id = $insertResult->getInsertedId()->__toString();

        return $widget;

    }

    public function sortWidgets(array $widgets, int $group_id)
    {
        $collection = $this->getCollection();

        foreach($widgets as $widget) {

            $criteria = [
                '_id' => new ObjectId($widget['id']),
                'group_id' => $group_id
            ];

            $values = ['$set' => [
                'sort' => (int) $widget['sort']
            ]];

            $result = $collection->updateOne($criteria, $values);
        }

        return true;
    }

    public function updateWidget(BaseWidget $widget)
    {
        $collection = $this->getCollection();

        $criteria = [
            '_id' => new ObjectId($widget->id),
        ];

        $values = ['$set' => [
            'name' => $widget->name,
            'status' => (int)$widget->status,
            'code' => $widget->code,
            'audience' => $widget->audience,
            'last_published_state' => (string) $widget->last_published_state,
            'updated' => $widget->updated
        ]];

        $result = $collection->updateOne($criteria, $values);

        return $result->isAcknowledged();
    }

    public function updateWidgetStatus(string $id, int $status): bool
    {
        $collection = $this->getCollection();

        $criteria = [
            '_id' => new ObjectId($id),
        ];

        $values = ['$set' => [
            'status' => (int) $status
        ]];

        $result = $collection->updateOne($criteria, $values);

        return $result->isAcknowledged();
    }

    public function updateWidgetName($widget, string $name): bool
    {
        $collection = $this->getCollection();

        $id = $widget->id;

        $criteria = [
            '_id' => new ObjectId($id),
        ];

        $values = ['$set' => [
            'name' => $name,
            'updated' => $widget->updated
        ]];

        $result = $collection->updateOne($criteria, $values);

        return $result->isAcknowledged();
    }

    public function getNextSortValueForGroup(int $group_id, string $type): int
    {
        $collection = $this->getCollection();

        $criteria = [
            'group_id' => $group_id,
            'type' => $type
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

    public function countGroupWidgets(int $group_id): int
    {
        $collection = $this->getCollection();

        $criteria = [
            'group_id' => $group_id
        ];

        $result = $collection->count($criteria);

        return $result;
    }

    public function getCollection()
    {
        return $this->db->getConnection()->{self::COLLECTION_NAME};
    }
}