<?php

namespace App\Interfaces;

use App\Widgets\Models\BaseWidget;
use App\Widgets\WidgetCollection;

interface WidgetsRepositoryInterface
{
    public function createWidget(array $data): BaseWidget;

    public function getOne(string $id): BaseWidget;

    public function getMany(array $ids): WidgetCollection;
    
    public function getGroupWidgets(int $group_id): WidgetCollection;
    
    public function updateWidget(BaseWidget $widget);

    public function deleteOne(string $id, int $group_id): bool;

    public function deleteMany(array $ids, int $group_id): bool;

    public function sortWidgets(array $widgets, int $group_id);

    public function countGroupWidgets(int $group_id): int;
}