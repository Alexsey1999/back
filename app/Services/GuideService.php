<?php

namespace App\Services;

use App\Models\Guide;
use App\Interfaces\GuideServiceInterface;
use App\Interfaces\GuideRepositoryInterface;
use App\Exceptions\GroupGuideNotFoundException;
use DomainException;

class GuideService implements GuideServiceInterface
{

    private $guideRepository;

    public function __construct(GuideRepositoryInterface $guideRepository)
    {
        $this->guideRepository = $guideRepository;
    }

    public function getOne(array $params): Guide
    {
        $guide = $this->guideRepository->get($params);
        if (!$guide) {
            throw new GroupGuideNotFoundException('Guide not found');
        }
        return $guide;
    }

    public function create(int $vk_group_id): Guide
    {
        $guide = new Guide([
            'group_id' => $vk_group_id,
            'seen_title_tooltip' => false,
            'seen_context_tooltip' => false,
            'visited_initial_settings' => false,
        ]);

        $createRes = $this->guideRepository->create($guide);

        if (!$createRes) {
            throw new DomainException('Error while creating new Guide');
        }

        return $guide;
    }

    public function update(int $vk_group_id, array $params)
    {
        $guide = $this->guideRepository->get(['vk_group_id' => $vk_group_id]);

        $guide->seen_context_tooltip = $params['seen_context_tooltip'];
        $guide->seen_title_tooltip = $params['seen_title_tooltip'];
        $guide->visited_initial_settings = $params['visited_initial_settings'];

        $updateRes = $this->guideRepository->update($guide);

        if (!$updateRes) {
            throw new DomainException('Error while updating Guide');
        }

        return $guide;
    }

    public function delete(int $vk_group_id) 
    {
        /**
         * Получаем сущность, которую необходимо удалить
         */
        $guide = $this->guideRepository->get(['vk_group_id' => $vk_group_id]);
        
        /**
         * Тут можно реализовать дополнительную логику
         * - Удаление\редактирование смежных сущностей
         */

        /**
         * Сам факт удаления из БД делегируем репозиторию
         */
        $res = $this->guideRepository->delete($guide);
        return $res;
    }

}