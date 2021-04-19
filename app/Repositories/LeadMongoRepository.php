<?php

namespace App\Repositories;

use DomainException;

use App\Interfaces\LeadRepositoryInterface;
use App\Formatters\MongoDBFormatter;
use App\Interfaces\DbInterface;

class LeadMongoRepository implements LeadRepositoryInterface
{
    const COLLECTION_NAME = 'landing_leads';

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

    public function saveLead(array $data, array $options): bool
    {
        $lead_data = [
            'vk_user_id' => (int)$options['vk_user_id'], // Пользователь, оставивший заявку
            'vk_group_id' => (int)$options['vk_group_id'], // Сообщество, в котором оставлена заявка
            'datetime_utc' => $this->formatter->getUTCDateTime(time() * 1000) // Дата заявки по utc
        ];

        if (isset($data['phone_number'])) {
            $lead_data['phone_number'] = $data['phone_number'];
        }

        $collection = $this->getCollection();

        $insertResult = $collection->insertOne($lead_data);

        if (!$insertResult->getInsertedId()) {
            throw new DomainException('Ошибка при создании страницы');
        }

        return $insertResult->getInsertedId()->__toString();
    }

    /**
     * Получение заявок для конкретной страницы
     * @param string $page_id - ID страницы
     * @param array $params - дополнительный параметры (временной отрезок, тип, пользователь, реф, платформа)
     */
    public function getPageLeads(string $page_id, array $params = []): array
    {
        return [];
    }

    public function getCollection()
    {
        return $this->db->getConnection()->{self::COLLECTION_NAME};
    }
}