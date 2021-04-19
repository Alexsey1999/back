<?php

namespace App\Interfaces;
use App\Models\Guide;

interface GuideServiceInterface 
{
    public function getOne(array $params): Guide;
    public function create(int $vk_group_id): Guide;
    public function update(int $vk_group_id, array $params);
    public function delete(int $vk_group_id);
}