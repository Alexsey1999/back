<?php

namespace App\Services;

use App\Widgets\WidgetFactory;
use App\Interfaces\WidgetsRepositoryInterface;

use App\Exceptions\Widgets\WidgetCreateException;
use App\Exceptions\Widgets\WidgetAccessException;
use App\Exceptions\Widgets\WidgetUpdateException;
use App\Exceptions\Widgets\WidgetDeleteException;
use App\Exceptions\Widgets\WidgetNotFoundException;
use App\Exceptions\Widgets\WidgetGroupOverloadException;

class WidgetService 
{

    const GROUP_MAX_COUNT_WIDGETS = 100;

    private $repository;

    public function __construct(
        WidgetsRepositoryInterface $repository
    )
    {
        $this->repository = $repository;
    }

    /**
     * Cоздания нового виджета
     */
    public function createWidget(array $data)
    {

        $countWidgets = $this->repository->countGroupWidgets($data['group_id']);

        if ($countWidgets >= self::GROUP_MAX_COUNT_WIDGETS) {
            throw new WidgetGroupOverloadException('Достигнуто максимальное количество виджетов для сообщества');
        }

        $widget = $this->repository->createWidget($data); 

        if (!$widget) {
            throw new WidgetCreateException('Ошибка при создании виджета');
        }

        return $widget;
    }


    /**
     * Обновление виджета
     */
    public function updateWidget(string $widget_id, array $data)
    {

        $widget = $this->getWidgetModel($widget_id);

        if ($widget->group_id !== $data['group_id']) {
            throw new WidgetAccessException('Нет доступа к виджету');
        }

        $widget
            ->setBody($data['code'])
            ->setUpdatedAt(time(), (int) $data['vk_user_id']);

        $isUpdated = $this->repository->updateWidget($widget);

        if (!$isUpdated) {
            throw new WidgetUpdateException('Ошибка при обновлении виджета');
        }

        return $widget;
    }


    /**
     * Обновление аудитрии заданного виджета
     */
    public function updateWidgetAudience(string $widget_id, array $data)
    {
        $widget = $this->getWidgetModel($widget_id);

        if ($widget->group_id !== $data['group_id']) {
            throw new WidgetAccessException('Нет доступа к виджету');
        }

        $widget
            ->updateAudience($data['audience'])
            ->setUpdatedAt(time(), (int)$data['vk_user_id']);

        $isUpdated = $this->repository->updateWidget($widget);

        if (!$isUpdated) {
            throw new WidgetUpdateException('Ошибка при обновлении аудитории виджета');
        }

        return $widget;

    }

    /** 
     * 
     */
    public function updateWidgetName(string $widget_id, array $data) 
    {
        $widget = $this->getWidgetModel($widget_id);

        if ($widget->group_id !== $data['group_id']) {
            throw new WidgetAccessException('Нет доступа к виджету');
        }

        $widget
            ->setName($data['name'])
            ->setUpdatedAt(time(), (int) $data['vk_user_id']);

        $isUpdated = $this->repository->updateWidget($widget);

        if (!$isUpdated) {
            throw new WidgetUpdateException('Ошибка при обновлении имени виджета');
        }

        return $widget;
    }

    /**
     * 
     */
    public function fetchAllGroupWidgets(int $group_id)
    {
        $widgetCollection = $this->repository->getGroupWidgets($group_id);
        return $widgetCollection->toArray();
    }

    public function cloneWidget(string $widget_id, array $data) 
    {
        $group_widgets_count = $this->repository->countGroupWidgets((int) $data['group_id']);

        if ($group_widgets_count >= self::GROUP_MAX_COUNT_WIDGETS) {
            throw new WidgetGroupOverloadException('Достигнуто максимальное количество виджетов для сообщества');
        }

        $widget = $this->getWidgetModel($widget_id);

        if ($widget->group_id !== $data['group_id']) {
            throw new WidgetAccessException('Нет доступа к копируемому виджету');
        }

        $clone = $widget->clone();

        $result = $this->repository->createWidget(array_merge($clone->toArray(), [
            'vk_user_id' => $data['vk_user_id']
        ]));

        if (!$result) {
            throw new WidgetCreateException('Ошибка при создании виджета');
        }

        return $result;
    }

    /**
     * Удаление одного или нескольких виджетов
     */
    public function delete(array $widget_ids, array $data)
    {
        $res = $this->repository->deleteMany($widget_ids, (int) $data['group_id']);
        
        if (!$res) {
            throw new WidgetDeleteException('Ошибка при удалении виджета');
        }

        return true;
    }

    /**
     * Сортировка виджетов в рамках одного сообщества
     */
    public function sortWidgets($widgets, $data)
    {
        $res = $this->repository->sortWidgets($widgets, (int) $data['group_id']);
        return $res;
    }

    /**
     * Копировать виджеты в любое сообщество
     */
    public function copyWidgets($widget_ids, $data)
    {

        $newWidgetsIds = [];
        $errors = [];

        foreach($widget_ids as $widget_id) {
            $widget = $this->repository->getOne((string) $widget_id);

            if (!$widget) {
                throw new WidgetNotFoundException('Виджет не найден');
            }

            // Если попытка скорпировать чужой виджет
            if ($widget->group_id !== $data['source_group_id']) {
                throw new WidgetAccessException('Нет доступа в данному виджету');
            }

            $destCommunityId = $data['target_group_id'];
            
            // Если копируем в то же самое сообщество
            if ($data['source_group_id'] == $data['target_group_id']) {
                $clone = $widget->clone();
                $destCommunityId = $data['source_group_id'];
            } else {
                // Если копируем в другое сообщество
                $clone = $widget->cloneToCommunity($destCommunityId);
            }

            $groupWidgetsCount = $this->repository->countGroupWidgets((int) $destCommunityId);

            if ($groupWidgetsCount + count($widget_ids) >= self::GROUP_MAX_COUNT_WIDGETS) {
                throw new WidgetGroupOverloadException('Будет достигнуто максимальное количество виджетов для сообщества (100)');
            }

            /**
             * @TO DO
             */
            $result = $this->repository->createWidget(array_merge($clone->toArray(), [
                'vk_user_id' => $data['vk_user_id']
            ]));  
            
            if (!$result) {
                $errors[] = 'Error while copying widget ' . $widget_id;
            } else {
                $newWidgetsIds[] = $result->id;
            }

            return $newWidgetsIds;
        }
    }

    /**
     * 
     */
    public function publishWidgets(array $widget_ids, $delete_ids, array $ids_to_update_last_published_state, array $data)
    {
        $widgetCollection = $this->repository->getGroupWidgets($data['group_id']);

        foreach($widgetCollection as $widget)
        {

            if (in_array($widget->id, $widget_ids)) {

                $widget->enable();
                
                if (in_array($widget->id, $ids_to_update_last_published_state)) {
                    $widget->last_published_state = json_encode([
                        'code' => $widget->code,
                        'audience' => $widget->audience
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

            } else {

                $widget->disable();
            
            }

            $widget->setUpdatedAt(time(), (int) $data['vk_user_id']);

            $this->repository->updateWidget($widget);
        }

        if (isset($delete_ids)) {
            foreach($delete_ids as $delete_id) {
                $result = $this->repository->deleteOne($delete_id, $data['group_id']);
            }
        }
    }

    /**
     * Отменить изменения в виджете
     */
    public function discardWidgetChanges(string $widget_id, array $data)
    {
        $widget = $this->getWidgetModel($widget_id);

        if ((int)$widget->group_id !== $data['group_id']) {
            throw new WidgetAccessException('Нет доступа к редактируемому виджету');
        }

        if (empty($widget->last_published_state)) {
            throw new WidgetUpdateException('Виджет не имеет неопубликованных изменений');
        }

        $widget->discard();

        $isUpdated = $this->repository->updateWidget($widget);

        if (!$isUpdated) {
            throw new WidgetUpdateException('Ошибка при изменении виджета');
        }

        return $widget;
    }

    private function getWidgetModel(string $widget_id)
    {
        $widget = $this->repository->getOne((string) $widget_id);

        if (!$widget) {
            throw new WidgetNotFoundException('Виджет не найден');
        }

        return $widget;
    }
}