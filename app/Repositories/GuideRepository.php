<?php

namespace App\Repositories;

use App\Interfaces\GuideRepositoryInterface;
use App\Interfaces\DbInterface;
use App\Models\Guide;

class GuideRepository implements GuideRepositoryInterface
{

    private $db;

    public function __construct(DbInterface $db)
    {
        $this->db = $db;
    }

    public function getCollection()
    {
        return $this->db->getConnection()->guide;
    }

    public function get(array $params)
    {
        $collection = $this->db->getConnection()->guide;

        $criteria = [
            'group_id' => isset($params['vk_group_id']) ? (int) $params['vk_group_id'] : 0
        ];

        $data = $collection->find($criteria)->toArray();

        if (!$data) {
            return null;
        }

        return new Guide($data[0]);
    }

    public function create(Guide $guide)
    {
        $collection = $this->db->getConnection()->guide;

        $doc = [
            'group_id' => $guide->group_id,
            'seen_title_tooltip' => $guide->seen_title_tooltip,
            'seen_context_tooltip' => $guide->seen_context_tooltip,
            'visited_initial_settings' => $guide->visited_initial_settings
        ];

        $res = $collection->insertOne($doc);

        return $res->isAcknowledged();
    }

    public function update(Guide $guide)
    {
        $collection = $this->db->getConnection()->guide;

        $criteria = [
            'group_id' => $guide->group_id
        ];

        $values = ['$set' => [
            'seen_title_tooltip' => $guide->seen_title_tooltip,
            'seen_context_tooltip' => $guide->seen_context_tooltip,
            'visited_initial_settings' => $guide->visited_initial_settings
        ]];

        $result = $collection->updateOne($criteria, $values);

        return $result->isAcknowledged();
    }

    public function delete(Guide $guide)
    {
        $collection = $this->db->getConnection()->guide;

        $criteria = [
            'group_id' => $guide->group_id
        ];

        $result = $collection->deleteOne($criteria);

        return $result->isAcknowledged();
    }
}