<?php

namespace App\Repositories\Fakes;

use App\Interfaces\Pages\PageStatisticRepositoryInterface;

class PageStatisticFakeRepository implements PageStatisticRepositoryInterface
{
    
    public function getSummary(array $page_ids): array
    {
        return [];
    }

    public function saveHit(array $hit_data)
    {
        return true;
    }

    public function saveGoal(array $hit_data)
    {
        return true;
    }

    public function getMostViewedPages(array $params = []): array
    {
        return [];
    }

    public function getMostActiveCommunitiesByHits(array $params = []): array
    {
        return [];
    }

    public function getMostActiveCommunitiesByGoals(array $params = []): array
    {
        return [];
    }

    /**
     * Получение списка id страниц с самым большим количеством просмотров
     */
    public function getMostActivePagesByViews(array $params = []): array
    {
        return [];
    }

    /**
     * Получение списка id страниц с самым большим количеством достигнутых целевых действий
     */
    public function getMostActivePagesByGoals(array $params = []): array
    {
        return [];
    }
}