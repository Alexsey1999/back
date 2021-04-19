<?php

namespace App\Services;

use App\Interfaces\WidgetsRepositoryInterface;
use App\Repositories\SharedRepository;
use DomainException;

class SharedService
{

    private $sharedRepository;
    private $widgetRepository;

    public function __construct(
        SharedRepository $sharedRepository,
        WidgetsRepositoryInterface $widgetRepository
    )
    {
        $this->sharedRepository = $sharedRepository;
        $this->widgetRepository = $widgetRepository;
    }

    public function createCollection(array $params): int
    {

        $data = [
            'widget_ids' => $params['widget_ids'],
            'vk_group_id' => $params['vk_group_id'],
            'vk_user_id' => $params['vk_user_id'],
            'created_at' => time(),
            'request_count' => 0
        ];

        $res = $this->sharedRepository->createCollection($data);

        if (!$res) {
            throw new DomainException('Ошибка при создании коллекции виджетов');
        }

        return $res;
    }

    public function getCollection(int $collection_id, array $params)
    {
        $collection = $this->sharedRepository->getCollection((int) $collection_id);

        if (!$collection) {
            throw new DomainException('Коллекция виджетов не найдена');
        }

        // Если запрос к коллекции делается не со страницы приложения, от имени которого была создана коллекция
        if ($collection['vk_group_id'] != $params['vk_group_id']) {
            throw new DomainException('Доступ запрещен');
        }

        $ids = $collection['widget_ids'];
        $widget_collection = $this->widgetRepository->getMany($ids);

        return $widget_collection->toArray();
    }

    public function copyCollection(int $collection_id, int $target_vk_group_id, array $params): array
    {
        // Получим копируемую коллекцию
        $collection = $this->sharedRepository->getCollection($collection_id);

        // Если id группы коллекции не совпадает с id группы, от которой идет запрос
        if ($collection['vk_group_id'] != $params['vk_group_id']) {
            throw new DomainException('Доступ запрещен');
        }

        $ids = $collection['widget_ids'];

        // Получим количество виджетов в сообществе, которое копируем
        $target_group_widgets_count = $this->widgetRepository->countGroupWidgets($target_vk_group_id);

        if ($target_group_widgets_count + count($ids) > 100) {
            throw new DomainException('Будет достигнуто максимальное количество виджетов для сообщества (100)');
        }

        $errors = [];

        foreach($ids as $widget_id) {
            $widget = $this->widgetRepository->getOne((string) $widget_id);

            if (!$widget) {
                $errors[] = 'Виджет не найден - ' . $widget_id;
                continue;
            }

            $widget_data = $widget->toArray();
            $widget_data['group_id'] = $target_vk_group_id;
            $widget_data['vk_user_id'] = $params['vk_user_id'];

            $newWidget = $this->widgetRepository->createWidget($widget_data);  
            
            if (!$newWidget) {
                $errors[] = 'Ошибка при копировании виджета ' . $widget_id;
            }
        }

        $request_count = $collection['request_count'] + 1;

        $this->sharedRepository->updateCollection($collection_id, [ 'request_count' => $request_count ]);

        return $errors;

    }
}