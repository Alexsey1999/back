<?php

namespace App\Services;

use App\Dto\CreateNewPageData;

use DateTime;
use App\Pages\Page;
use App\Pages\PagesCollection;
use App\Interfaces\Pages\PagesRepositoryInterface;
use App\Interfaces\Pages\BlocksEditRepositoryInterface;
use App\Interfaces\Pages\BlocksPublishedRepositoryInterface;
use App\Interfaces\Pages\PageStatesRepositoryInterface;
use App\Interfaces\Pages\BlocksUsageLogRepositoryInterface;
use App\Interfaces\Pages\PageStatisticRepositoryInterface;
use App\Interfaces\LoggerInterface;
use App\Workers\PageBlock;


use DomainException;
use App\Exceptions\Pages\PageAccessDeniedException;
use App\Exceptions\Pages\PageMetaUpdateException;
use App\Exceptions\Pages\PageDeleteException;
use App\Exceptions\Pages\PageNotFoundException;
use App\Exceptions\Pages\PageMaxBlockCountException;
use App\Exceptions\Pages\PageIllegalTemplateException;
use App\Exceptions\Pages\PageMaxBlockItemsCountException;

class PageService
{

    const MAX_GROUP_PAGES_COUNT = 20; // Максимальное количество промостраниц для сообщества
    const MAX_PAGE_BLOCKS_COUNT = 30; // Максимальное количество блоков для страницы

    const MAX_PRODUCTS_ITEMS_COUNT = 20;
    const MAX_ADVANTAGES_ITEMS_COUNT = 10;
    const MAX_REVIEWS_ITEMS_COUNT = 10;
    const MAX_IMAGES_ITEMS_COUNT = 10;

    private PagesRepositoryInterface $pagesRepository;
    private BlocksEditRepositoryInterface $blocksEditRepository;
    private BlocksPublishedRepositoryInterface $blocksPublishedRepository;
    private PageStatesRepositoryInterface $pageStatesRepository;
    private BlocksUsageLogRepositoryInterface $blocksUsageRepository;
    private PageStatisticRepositoryInterface $pageStatisticRepository;
    private LoggerInterface $logger;

    public function __construct(
        PagesRepositoryInterface $pagesRepository,
        BlocksEditRepositoryInterface $blocksEditRepository,
        BlocksPublishedRepositoryInterface $blocksPublishedRepository,
        PageStatesRepositoryInterface $pageStatesRepository,
        BlocksUsageLogRepositoryInterface $blocksUsageRepository,
        PageStatisticRepositoryInterface $pageStatisticRepository,
        LoggerInterface $logger
    )
    {
        $this->pagesRepository = $pagesRepository;
        $this->blocksEditRepository = $blocksEditRepository;
        $this->blocksPublishedRepository = $blocksPublishedRepository;
        $this->pageStatesRepository = $pageStatesRepository;
        $this->blocksUsageRepository = $blocksUsageRepository;
        $this->pageStatisticRepository = $pageStatisticRepository;
        $this->logger = $logger;
    }

    public function createPage(array $data): Page
    {
        $page = $this->pagesRepository->create(new CreateNewPageData($data));
        return $page;
    }

    /**
     * Получение страницы без лишних для отображения пользователю
     */
    public function getOneProd(string $id, int $vk_group_id): Page
    {
        $page = $this->pagesRepository->getOne($id);

        if ($page->isDeleted()) {
            throw new PageNotFoundException('Лендинг не найден');
        }

        if ($page->getVkGroupId() !== $vk_group_id) {
            throw new PageAccessDeniedException('Доступ запрещен');
        }

        $blocks_published = $this->blocksPublishedRepository->getPageBlocks($page->getId());

        if (!$page->isDeactivated()) {
            $page->setBlocks($blocks_published);
        }

        return $page;
    }

    public function getOne(string $id, int $vk_group_id): Page
    {
        $page = $this->pagesRepository->getOne($id);

        if ($page->isDeleted()) {
            throw new PageNotFoundException('Лендинг не найден');
        }

        if ($page->getVkGroupId() !== $vk_group_id) {
            throw new PageAccessDeniedException('Доступ запрещен');
        }

        $blocks_edit = $this->blocksEditRepository->getPageBlocks($page->getId());
        $blocks_published = $this->blocksPublishedRepository->getPageBlocks($page->getId());
        $states = $this->pageStatesRepository->getPageStates($page->getId());

        $page->setBlocksEdit($blocks_edit);
        $page->setBlocks($blocks_published);
        $page->setStates($states);

        return $page;
    }

    public function getAll(int $vk_group_id): PagesCollection
    {
        $collection = $this->pagesRepository->getGroupPages($vk_group_id);

        $page_ids = [];

        foreach($collection as $page) {
            $blocks_edit = $this->blocksEditRepository->getPageBlocks($page->getId());
            $blocks_published = $this->blocksPublishedRepository->getPageBlocks($page->getId());
            $states = $this->pageStatesRepository->getPageStates($page->getId());

            $page->setBlocksEdit($blocks_edit);
            $page->setBlocks($blocks_published);
            $page->setStates($states);
            $page_ids[] = $page->getId();
        }

        try {
            $pageStatisticSummary = $this->pageStatisticRepository->getSummary($page_ids);
            foreach($collection as $page) {
                if (isset($pageStatisticSummary[$page->getId()])) {
                    $data = $pageStatisticSummary[$page->getId()];
                    $page->setStatisticSummary($data);
                }
            }
        } catch (\Exception $e) {
            /**
             * @TODO - log error
             */
        }

        return $collection;
    }

    public function getList(int $vk_group_id): array
    {
        return $this->pagesRepository->getGroupPagesList($vk_group_id);
    }

    public function rename(string $id, $params): Page
    {
        $page = $this->getOne($id, $params['vk_group_id']);

        if ($page->isDeleted()) {
            throw new PageNotFoundException('Лендинг не найден');
        }

        $page->setName($params['name']);
        $page->setUpdatedAt(new \DateTime('now'));

        $updatedPage = $this->pagesRepository->updateMetaData($page);

        if (!$updatedPage) {
            throw new PageMetaUpdateException('Ошибка при переименовании');
        }

        return $updatedPage;
    }

    public function deletePages(array $ids, array $params)
    {

        foreach($ids as $id)
        {
            $page = $this->getOne($id, $params['vk_group_id']);

            $page->delete();
            $page->setUpdatedAt(new \DateTime('now'));

            $updatedPage = $this->pagesRepository->updateMetaData($page);

            if (!$updatedPage) {
                throw new PageDeleteException('Ошибка при удалении');
            }
        }

        return true;

    }

    public function addBlock(string $page_id, array $block_data, array $params)
    {

        $block_count = $this->blocksEditRepository->getCountBlocksForPage($page_id);

        if ($block_count >= self::MAX_PAGE_BLOCKS_COUNT) {
            throw new PageMaxBlockCountException();
        }

        $block = $this->blocksEditRepository->addBlock($page_id, $block_data, $params);

        $this->updateStates($page_id);
        $this->blocksUsageRepository->saveItem([
            'type' => $block_data['type'],
            'sub_type' => $block_data['sub_type'],
            'key' => $block_data['key'],
            'vk_group_id' => $params['vk_group_id'],
            'page_id' => $page_id
        ]);

        return $block;
    }

    /**
     * TODO - стоит попробовать оптимзировать метод - через групповое добавление
     */
    public function addTemplate(string $page_id, array $blocks_data, array $params)
    {

        $page = $this->getOne($page_id, $params['vk_group_id']);

        if (count($page->getBlocksEdit()) > 0) {
            throw new PageIllegalTemplateException();
        }

        $new_blocks = [];

        foreach($blocks_data as $block_item_data) {
            $block = $this->blocksEditRepository->addBlock($page_id, $block_item_data, $params);
            $new_blocks[] = $block;
        }

        return $new_blocks;
    }

    public function insertBlock(string $page_id, array $block_data, string $block_insert_after, array $params)
    {

        /**
         * Проверить на уязвиомсть BOLA
         */

        $blocks = $this->blocksEditRepository->getPageBlocks($page_id);

        if (count($blocks) >= self::MAX_PAGE_BLOCKS_COUNT) {
            throw new PageMaxBlockCountException();
        }

        $new_block_sort_value = null;

        /**
         * Пройдем по всем блокам и увеличим у них значение sort
         * Если они ниже по сортировке чем $block_insert_after
         */
        foreach($blocks as &$block) {
            if ($block['id'] === $block_insert_after) {
                $new_block_sort_value = $block['sort'] + 1;
            } else {
                if ($new_block_sort_value && $block['sort'] >= $new_block_sort_value) {

                    $block['sort']++;

                    $this->blocksEditRepository->updateBlockFields($block['id'], [
                        'sort' => $block['sort']
                    ], $params);
                }
            }
        }


        if (!$new_block_sort_value) {
            $new_block_sort_value = 0;
        }

        $new_block = $this->blocksEditRepository->addBlock($page_id, $block_data, array_merge($params, ['sort_value' => $new_block_sort_value]));

        array_push($blocks, $new_block);

        usort($blocks, function ($item1, $item2) {
            return $item1['sort'] <=> $item2['sort'];
        });

        $this->updateStates($page_id);
        $this->blocksUsageRepository->saveItem([
            'type' => $block_data['type'],
            'sub_type' => $block_data['sub_type'],
            'key' => $block_data['key'],
            'vk_group_id' => $params['vk_group_id'],
            'page_id' => $page_id
        ]);

        return [
            'new_block' => $new_block,
            'blocks' => $blocks
        ];
    }

    public function deleteBlock(string $block_id, array $params)
    {
        $res = $this->blocksEditRepository->deleteBlock($block_id, $params['vk_group_id']);
        $this->updateStates($params['page_id']);
        return $res;
    }

    public function sortBlocks(string $page_id, array $sort_data, $params)
    {

        $page = $this->getOne($page_id, $params['vk_group_id']);

        $errors = [];

        foreach($sort_data as $block) {
            $updResult = $this->blocksEditRepository->updateBlockFields($block['id'], [
                'sort' => $block['sort']
            ], $params);

            if (!$updResult) {
                $errors[$block['id']] = [
                    'message' => 'Ошибка при обновлении блока',
                    'block_id' => $block['id']
                ];
            }
        }

        $this->updateStates($page_id);

        return $errors;
    }

    public function updateBlock(array $block_data, array $params)
    {

        $page = $this->getOne($block_data['page_id'], $params['vk_group_id']);

        $update_data = [];

        if (isset($block_data['content'])) {
            $update_data['content'] = $block_data['content'];
        }

        if (isset($block_data['meta'])) {
            $update_data['meta'] = $block_data['meta'];
        }

        if (isset($block_data['background'])) {
            $update_data['background'] = $block_data['background'];
        }

        if (isset($block_data['video'])) {
            $update_data['video'] = $block_data['video'];
        }

        if (isset($block_data['items'])) {

            $itemsMaxCounters = $this->getBlocksItemsMaxCounters();

            if (isset($itemsMaxCounters[$block_data['type']]) &&
                count($block_data['items']) > $itemsMaxCounters[$block_data['type']]) {
                    throw new PageMaxBlockItemsCountException();
                }

            $update_data['items'] = $block_data['items'];
        }

        /**
         * Флаг - блок использует кнопку-действие
         */
        if (isset($block_data['has_button'])) {
            $update_data['has_button'] = $block_data['has_button'];
        }

        /**
         * Флаг - блок использует заголовок и описание
         */
        if (isset($block_data['has_title'])) {
            $update_data['has_title'] = $block_data['has_title'];
        }

        /**
         * Флаг - блок использует фоновое изображение
         */
        if (isset($block_data['has_background'])) {
            $update_data['has_background'] = $block_data['has_background'];
        }
        

        if (isset($block_data['button'])) {
            $update_data['button'] = $block_data['button'];
        }

        $upd_result = $this->blocksEditRepository->updateBlockFields($block_data['id'], $update_data, $params);

        $updated_block = array_merge($block_data, $upd_result);

        $this->updateStates($block_data['page_id']);

        return $updated_block;
    }

    public function publish(string $page_id, array $params)
    {
        $page = $this->getOne($page_id, $params['vk_group_id']);

        $this->blocksPublishedRepository->clearPageBlocks($page_id);

        $publishedBlocks = [];

        foreach($page->getBlocksEdit() as $block) {
            $publishedBlock = $this->blocksPublishedRepository->addBlock($page_id, $block, $params);
            $publishedBlocks[] = $publishedBlock;
        }

        return $publishedBlocks;
    }

    public function updateStates(string $page_id)
    {
        $blocks = $this->blocksEditRepository->getPageBlocks($page_id);
        $state = [
            'page_id' => $page_id,
            'blocks_edit' => $blocks
        ];

        $this->pageStatesRepository->addState($state);
    }

    public function getPageStates($page_id)
    {
        return $this->pageStatesRepository->getPageStates($page_id);
    }

    public function getPagesRepository(): PagesRepositoryInterface
    {
        return $this->pagesRepository;
    }

    public function saveState(string $page_id, int $state_index, array $params)
    {
        $page = $this->getOne($page_id, $params['vk_group_id']);

        $available_states = $page->getStates();

        if (!isset($available_states[$state_index])) {
            throw new DomainException('Версия страницы не существует');
        }

        $is_cleared = $this->blocksEditRepository->clearPageBlocks($page_id);

        if (!$is_cleared) {
            throw new DomainException('Ошибка при сохранении версии страницы');
        }

        $state_to_save = $available_states[$state_index];

        $is_updated = $this->blocksEditRepository->addBlocksFromState(iterator_to_array($state_to_save['blocks_edit']));

        if (!$is_updated) {
            throw new DomainException('Ошибка при обновлении версии страницы');
        }

        $this->updateStates($page_id);

        $new_blocks = $this->blocksEditRepository->getPageBlocks($page_id);

        return $new_blocks;
    }

    public function copyPage(string $page_id, array $params): Page
    {
        $page = $this->getOne($page_id, $params['vk_group_id']);

        $data = $page->toArray();

        $new_page = $this->pagesRepository->create(new CreateNewPageData([
            'name' => $page->getCopyName($data['name']),
            'vk_user_id' => (int)$params['vk_user_id'],
            'vk_group_id' => (int)$data['vk_group_id']
        ]));

        $blocks_edit = [];

        foreach($data['blocks_edit'] as $block_edit) {
            $new_block = $block_edit;

            $new_block['page_id'] = $new_page->getId();
            unset($new_block['id']);

            $new_block['created'] = [
                'datetime' => (new DateTime())->format('Y-m-d\TH:i:s\Z'),
                'vk_user_id' => (int)$params['vk_user_id']
            ];

            $new_block['updated'] = [
                'datetime' => (new DateTime())->format('Y-m-d\TH:i:s\Z'),
                'vk_user_id' => (int)$params['vk_user_id']
            ];

            $blocks_edit[] = $new_block;

        }

        $blocks_published = [];

        foreach($data['blocks'] as $block_published) {
            $new_block = $block_published;

            $new_block['page_id'] = $new_page->getId();
            unset($new_block['id']);

            $new_block['created'] = [
                'datetime' => (new DateTime())->format('Y-m-d\TH:i:s\Z'),
                'vk_user_id' => (int)$params['vk_user_id']
            ];

            $new_block['updated'] = [
                'datetime' => (new DateTime())->format('Y-m-d\TH:i:s\Z'),
                'vk_user_id' => (int)$params['vk_user_id']
            ];

            $blocks_published[] = $new_block;

        }

        if (count($blocks_edit) > 0) {
            $this->blocksEditRepository->addBlocksFromState($blocks_edit);
            $new_page_blocks_edit = $this->blocksEditRepository->getPageBlocks($new_page->getId());
            $new_page->setBlocksEdit($new_page_blocks_edit);
        }

        if (count($blocks_published)) {
            $this->blocksPublishedRepository->addBlocksFromState($blocks_published);
            $new_page_blocks = $this->blocksPublishedRepository->getPageBlocks($new_page->getId());
            $new_page->setBlocks($new_page_blocks);
        }

        return $new_page;
    }

    public function changePagesStatus(array $page_ids, int $status, array $params): array
    {
        $results = [];
        foreach($page_ids as $id)
        {   
            $page = $this->pagesRepository->getOne($id);

            if ($page->getVkGroupId() !== $params['vk_group_id']) {
                $results[$page->getId()] = false;
                break;
            }

            $page->setStatus($status);
            
            $updatedPage = $this->pagesRepository->updateMetaData($page);

            if (!$updatedPage) {
                $results[$page->getId()] = false;
            } else {
                $results[$page->getId()] = true;
            }
        }

        return $results;
    }

    public function isValidStatus(int $status): bool
    {
        return in_array($status, Page::getStatuses());
    }

    public function copyPageToGroup(string $page_id, int $target_vk_group_id, array $params): Page
    {
        $page = $this->getOne($page_id, $params['vk_group_id']);
        $data = $page->toArray();

        /**
         * Создадим новую страницу
         */
        $new_page = $this->pagesRepository->create(new CreateNewPageData([
            'name' => $page->getCopyName($data['name']),
            'vk_user_id' => (int)$params['vk_user_id'],
            'vk_group_id' => $target_vk_group_id
        ]));

        $block_image_replacer =
            new PageBlock\ImageReplacer('/userapi.com/', Page::BLOCK_DEFAULT_IMAGE);

        $block_button_replacer = new PageBlock\ButtonReplacer([
            'action' => 'url',
            'send_trigger' => false,
            'lead_admin' => 0,
            'text' => 'Перейти в беседу',
            'url' => 'https://vk.com/im?sel=-' . $target_vk_group_id,
            'bot_id' => 0,
            'subscription_id' => 0
        ]);

        /**
         * Перенесем блоки искомой страницы в новую
         */
        foreach($data['blocks_edit'] as $block_edit) {
            /**
             * Заменим все изображения, которые были загружены на сервера ВК, на стандартные
             */
            $new_block = $block_image_replacer->replace($block_edit);

            /**
             * Заменим все действия для кнопок, кроме перехода по ссылке, на переход по ссылке - диалог с сообществом
             */
            $new_block = $block_button_replacer->replace($new_block);

            $new_block['vk_group_id'] = $target_vk_group_id;

            unset($new_block['id']);

            $this->blocksEditRepository->addBlock($new_page->getId(), $new_block, $params);
        }

        return $new_page;
    }

    public function getRecentBlocks(int $vk_group_id): array
    {
        try {
            $recentBlocks = $this->blocksUsageRepository->getRecent($vk_group_id);
            return $recentBlocks;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getBlocksItemsMaxCounters()
    {
        return [
            'products' => self::MAX_PRODUCTS_ITEMS_COUNT,
            'advantages' => self::MAX_ADVANTAGES_ITEMS_COUNT,
            'reviews' => self::MAX_REVIEWS_ITEMS_COUNT,
            'image' => self::MAX_IMAGES_ITEMS_COUNT,
        ];
    }

    public function countGroupsPages(int $vk_group_id): int
    {
       return $this->pagesRepository->countGroupPages($vk_group_id); 
    }

    /**
     * Получение статистики использования блоков по типу
     */
    public function getBlocksUsageStatistic()
    {
        $data = $this->blocksPublishedRepository->getUsageStatistic();
        return $data;
    }

    /**
     * Получение общего количества тех страниц, которые публиковались хоть раз
     */
    public function getPublishedCount()
    {
        $data = $this->blocksPublishedRepository->getPublishedPagesCount();
        return $data;
    }

    /**
     * Метод для свободного поиска по заданным критериям
     */
    public function find($params = [], $options = []): PagesCollection
    {
        $data = $this->pagesRepository->find($params, $options);
        return $data;
    }
}
