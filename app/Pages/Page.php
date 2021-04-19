<?php

namespace App\Pages;

use DateTime;
use App\Traits\Copyable;

class Page
{

    use Copyable;

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    
    /**
     * Помечаем страницу как удаленную, но не удаляем. С таким статусом она не видна никому. Только в БД
     */
    const STATUS_DELETED = 2;

    /**
     * Страница деактивирована пользователем
     */
    const STATUS_DEACTIVATED = 3;


    const BLOCK_DEFAULT_IMAGE = 'https://i.yapx.ru/IkVBn.gif';

    private string $id;
    private string $name;
    private int $vk_group_id;
    private DateTime $created_at;
    private DateTime $updated_at;
    private int $status;
    private int $author_vk_user_id;
    private int $sort;

    private $blocks = [];
    private $blocks_edit = [];
    private $statisticSummary = [];
    private $states = [];


    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_DELETED,
            self::STATUS_DEACTIVATED
        ];
    }


    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setBlocksEdit(array $blocks_edit)
    {
        $this->blocks_edit = $blocks_edit;
    }

    public function getBlocksEdit(): array
    {
        return $this->blocks_edit;
    }

    public function setBlocks(array $blocks)
    {
        $this->blocks = $blocks;
    }

    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function setStates(array $states)
    {
        $this->states = $states;
    }

    public function getStates()
    {
        return $this->states;
    }

    public function setStatisticSummary(array $data)
    {
        $this->statisticSummary = $data;
    }

    public function getStatisticSummary(): array
    {
        return $this->statisticSummary;
    }

    public function setSort(int $sort)
    {
        $this->sort = $sort;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setVkGroupId(int $vk_group_id)
    {
        $this->vk_group_id = $vk_group_id;
    }

    public function getVkGroupId(): int
    {
        return $this->vk_group_id;
    }

    public function setAuthorVkUserId(int $author_vk_user_id)
    {
        $this->author_vk_user_id = $author_vk_user_id;
    }

    public function setStatus(int $status)
    {
        $this->status = $status;
    }

    public function disable()
    {
        $this->status = self::STATUS_INACTIVE;
    }

    public function enable()
    {
        $this->status = self::STATUS_ACTIVE;
    }

    public function delete()
    {
        $this->status = self::STATUS_DELETED;
    }

    public function isDeleted()
    {
        return $this->status === self::STATUS_DELETED;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTime $dateTime)
    {
        $this->created_at = $dateTime;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(DateTime $dateTime)
    {
        $this->updated_at = $dateTime;
    }

    public function isDeactivated()
    {
        return (int)$this->status === self::STATUS_DEACTIVATED;
    }

    public function getSubscriptionIds(): array
    {
        $ids = [];

        foreach($this->blocks as $block) {
            if (isset($block['button']) && $block['button']['action'] === 'subscribe' && is_numeric($block['button']['subscription_id'])) {
                $ids[] = (int)$block['button']['subscription_id'];
            }

            if ($block['type'] === 'products') {
                foreach($block['items'] as $item) {
                    if (isset($item['button']) && $item['button']['action'] === 'subscribe' && is_numeric($item['button']['subscription_id'])) {
                        $ids[] = (int)$item['button']['subscription_id'];
                    }
                }
            }
        }

        return array_unique($ids);
    }

    /**
     * Приведение сущности к массиву
     */
    public function toArray()
    {
        $reflectionClass = new \ReflectionClass(get_class($this));

        $array = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $property->setAccessible(true);
            $propertyName = $property->getName();
            $value = $property->getValue($this);

            if ($value instanceof DateTime) {
                $array[$propertyName] = $value->format('Y-m-d\TH:i:s');
            } else {
                $array[$propertyName] = $value;
            }

            $property->setAccessible(false);
        }

        return $array;
    }

    /**
     * Приведение сущности к массиву на прод - для пользователя
     * Удаляем не нужные поля
     */
    public function toArrayProd()
    {
        $page_data = $this->toArray();

        unset($page_data['author_vk_user_id']);
        unset($page_data['blocks_edit']);
        unset($page_data['updated_at']);
        unset($page_data['created_at']);
        unset($page_data['states']);
        unset($page_data['statisticSummary']);
        
        foreach($page_data['blocks'] as &$block) {
            unset($block['created']);
            unset($block['updated']);
        }

        return $page_data;
    }

}
