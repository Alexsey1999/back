<?php

namespace App\Interfaces;

interface LeadRepositoryInterface 
{
     /**
     * Сохранение заявки
     */
    public function saveLead(array $lead_data, array $options): bool;

    /**
     * Получение заявок для конкретной страницы
     * @param string $page_id - ID страницы
     * @param array $params - дополнительный параметры (временной отрезок, тип, пользователь, рефб платформа)
     */
    public function getPageLeads(string $page_id, array $params = []): array;
}