<?php

namespace App\Interfaces;

use App\Models\Guide;

interface GuideRepositoryInterface
{
    public function get(array $params);
    public function create(Guide $guide);
    public function update(Guide $guide);
    public function delete(Guide $guide);
}