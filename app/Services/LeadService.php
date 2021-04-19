<?php

namespace App\Services;

use App\Interfaces\LeadServiceInterface;
use App\Interfaces\LeadRepositoryInterface;

class LeadService implements LeadServiceInterface
{

    private LeadRepositoryInterface $leadRepository;

    public function __construct(
        LeadRepositoryInterface $leadRepository
    )
    {
        $this->leadRepository = $leadRepository;
    }

    /**
     * Сохранение заявки
     */
    public function saveLead(array $data, array $options): bool
    {
        return $this->leadRepository->saveLead($data, $options);
    }

    /**
     * Получение заявок для конкретной страницы
     * @param string $page_id - ID страницы
     * @param array $params - дополнительный параметры (временной отрезок, тип, пользователь, реф, платформа)
     */
    public function getPageLeads(string $page_id, array $params = []): array
    {
        return $this->leadRepository->getPageLeads($page_id, $params);
    }
}