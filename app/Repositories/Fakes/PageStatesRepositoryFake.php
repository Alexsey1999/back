<?php

namespace App\Repositories\Fakes;

use App\Interfaces\Pages\PageStatesRepositoryInterface;

class PageStatesRepositoryFake implements PageStatesRepositoryInterface
{
    private $storage = [];

    public function addState(array $data): bool
    {
        return true;
    }

    public function getPageStates(string $page_id): array
    {
        return [];
    }

    public function deleteState(string $state_id)
    {
        return null;
    }
}