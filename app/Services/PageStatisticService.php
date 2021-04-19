<?php

namespace App\Services;

use App\Interfaces\Pages\PageStatisticRepositoryInterface;

class PageStatisticService 
{

    private PageStatisticRepositoryInterface $pageStatisticRepository;

    public function __construct(
        PageStatisticRepositoryInterface $pageStatisticRepository
    )
    {
        $this->pageStatisticRepository = $pageStatisticRepository;
    }

    public function saveHit(array $hit_data): bool
    {
        $result = $this->pageStatisticRepository->saveHit($hit_data);
        return $result;
    }

    public function saveGoal(array $goal_data): bool
    {
        $result = $this->pageStatisticRepository->saveGoal($goal_data);
        return $result;
    }

    public function getSummary($page_id): array
    {
        return $this->pageStatisticRepository->getSummary($page_id);
    }

}