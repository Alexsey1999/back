<?php

namespace App\Interfaces\Pages;

interface PageStatisticRepositoryInterface
{
    /**
     * Получение базовой статистики для страницы с id = $page_id
     */
    public function getSummary(array $page_ids): array;

    /**
     * Сохранение данных посещения
     */
    public function saveHit(array $hit_data);

    /**
     * Сохранение данных целевого действия
     */
    public function saveGoal(array $hit_data);

    /**
     * Получение списка ID сообществ по количеству просмотров страниц
     */
    public function getMostActiveCommunitiesByHits(array $params = []): array;

    /**
     * Получение списка ID сообществ по количеству достигнутых целевых действий
     */
    public function getMostActiveCommunitiesByGoals(array $params = []): array;

    /**
     * Получение списка id страниц с самым большим количеством просмотров
     */
    public function getMostActivePagesByViews(array $params = []): array;

    /**
     * Получение списка id страниц с самым большим количеством достигнутых целевых действий
     */
    public function getMostActivePagesByGoals(array $params = []): array;
}