<?php

namespace App\Pages;

use App\Pages\Page;
use DateTime;

class PagesFactory
{
    public function create(string $name, int $vk_group_id, int $vk_user_id): Page
    {
        $page = new Page();

        $page->setId('');
        $page->setSort(1);

        $page->setName($name);
        $page->setVkGroupId($vk_group_id);
        $page->setAuthorVkUserId($vk_user_id);

        $page->setCreatedAt(new DateTime('now'));
        $page->setUpdatedAt(new DateTime('now'));

        $page->disable();

        return $page;
    }

    public function loadFromDocument(array $data):  Page
    {
        $page = new Page();

        $page->setId($data['id']);
        $page->setSort($data['sort']);

        $page->setName($data['name']);
        $page->setVkGroupId($data['vk_group_id']);
        $page->setAuthorVkUserId($data['author_vk_user_id']);

        $page->setCreatedAt($data['created_at']);
        $page->setUpdatedAt($data['updated_at']);

        $page->setStatus($data['status']);

        return $page;
    }
}