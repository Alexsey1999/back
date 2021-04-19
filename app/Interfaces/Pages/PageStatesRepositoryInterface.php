<?php

namespace App\Interfaces\Pages;

interface PageStatesRepositoryInterface
{
    public function addState(array $data): bool;

    public function getPageStates(string $page_id): array;

    public function deleteState(string $state_id);
}