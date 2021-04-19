<?php

use App\Pages\Page;
use App\Services\PageService;
use App\Services\Logger\FakeLogger;

use App\Repositories\Fakes\PagesRepositoryFake;
use App\Repositories\Fakes\BlocksEditRepositoryFake;
use App\Repositories\Fakes\BlocksPublishedRepositoryFake;
use App\Repositories\Fakes\PageStatesRepositoryFake;
use App\Repositories\Fakes\BlocksUsageLogRepositoryFake;
use App\Repositories\Fakes\PageStatisticFakeRepository;

use App\Exceptions\Pages\PageMaxBlockCountException;

class PageServiceTest extends TestCase
{

    private Pageservice $pageService;

    public function setUp(): void
    {
        parent::setUp();

        $this->pageService = new PageService(
            new PagesRepositoryFake(),
            new BlocksEditRepositoryFake(),
            new BlocksPublishedRepositoryFake(),
            new PageStatesRepositoryFake(),
            new BlocksUsageLogRepositoryFake(),
            new PageStatisticFakeRepository(),
            new FakeLogger()
        );

    }

    private function getBlock(string $page_id):array 
    {
        return [
            "background" => ["url" => "https://www.tomswallpapers.com/pic/201610/1920x1200/tomswallpapers.com-68077.jpg"],
            "url" => "https://www.tomswallpapers.com/pic/201610/1920x1200/tomswallpapers.com-68077.jpg",
            "button" => ["text" => "Оставить заявку", "action" => "lead", "uuid" => "f6d02001-93ed-4f84-add2-2e215ce4e9b3"],
            "action" => "lead",
            "text" => "Оставить заявку",
            "uuid" => "f6d02001-93ed-4f84-add2-2e215ce4e9b3",
            "content" => ["title" => "Заголовок", "text" => "Описание, раскрывающее содержание заголовка."],
            "text" => "Описание, раскрывающее содержание заголовка.",
            "title" => "Заголовок",
            "vk_user_id" => 4871362,
            "id" => 1,
            "key" => "c1",
            "page_id" => $page_id,
            "status" => 1,
            "sort" => 1,
            "sub_type" => "cover_base",
            "type" => "cover"
        ];
    }

    private function getBlockWithItems(string $page_id):array
    {
        return [
            "button" => [],
            "items" => [
                
            ],
            "text" => "Оставить заявку",
            "uuid" => "f6d02001-93ed-4f84-add2-2e215ce4e9b3",
            "content" => ["title" => "Заголовок", "text" => "Описание, раскрывающее содержание заголовка."],
            "text" => "Описание, раскрывающее содержание заголовка.",
            "title" => "Заголовок",
            "vk_user_id" => 4871362,
            "id" => 1,
            "key" => "r1",
            "page_id" => $page_id,
            "status" => 1,
            "sub_type" => "reviews_base",
            "type" => "reviews"
        ];
    }

    private function getItem()
    {
        return [
            "button" => ["id" => "p5f646e71fc3e65539e3f44c10", "text" => "Подробнее", "action" => "url", "url" => "https://senler.ru"],
            "action" => "url",
            "id" => "p5f646e71fc3e65539e3f44c10",
            "text" => "Подробнее",
            "ur" => "https://senler.ru",
            "category" => "Категория",
            "img" => ["url" => "https://i.yapx.ru/IkVBn.gif"],
            "url" => "https://i.yapx.ru/IkVBn.gif",
            "name" => "Название товара",
            "price" => "от 10 000 руб.",
            "text" => "Краткое описание товара, особенности и характеристики",
            "uuid" => "a46c1cd0-ae56-4db9-b80a-02827bd93e40",
        ];
    }

    /**
     * @TODO - сделать динамические id для страниц и блоков
     */


    /**
     * @group publish-page-blocks
     */
    public function testPublishEditBlocks()
    {

        $page_id = 'id1';

        $init_page_data = [
            'name' => 'test',
            'vk_user_id' => 234,
            'vk_group_id' => 123
        ];

        $this->pageService->createPage($init_page_data);

        $block_data = $this->getBlock($page_id);

        for($i = 0; $i < 5; $i++) {
            $this->pageService->addBlock($page_id, $block_data, [
                "vk_group_id" => 123,
                "vk_user_id" => 234,
                "sort_value" => $i + 1
            ]);
        }

        $this->pageService->publish($page_id, [
            'vk_user_id' => 234,
            'vk_group_id' => 123
        ]);
            
        $page = $this->pageService->getOne($page_id, 123);
        $this->assertEquals(5, count($page->getBlocksEdit()));
        $this->assertEquals(5, count($page->getBlocks()));
    }
    

    /**
     * @group add-insert-page-block
     */
    public function testAddBlockMaxCount()
    {
        $this->expectException(PageMaxBlockCountException::class);

        $page_id = '123';

        $block_data = $this->getBlock($page_id);

        for ($i = 1; $i <= PageService::MAX_PAGE_BLOCKS_COUNT; $i++) {
            $this->pageService->addBlock('123', $block_data, [
                'vk_group_id' => 123,
                'vk_user_id' => 123,
                'sort_value' => $i
            ]);
        }

        $this->pageService->addBlock('123', $block_data, [
            'vk_group_id' => 123,
            'vk_user_id' => 123,
            'sort_value' => 6
        ]);
    }

    /**
     * @group add-insert-page-block
     */
    public function testInsertBlockMaxCount()
    {
        $this->expectException(PageMaxBlockCountException::class);

        $page_id = '123';

        $block_data = $this->getBlock($page_id);

        for ($i = 1; $i <= PageService::MAX_PAGE_BLOCKS_COUNT; $i++) {
            $this->pageService->insertBlock('123', $block_data,'5f4f3a84a87ce7796a33eec8', [
                'vk_group_id' => 123,
                'vk_user_id' => 123,
                'sort_value' => $i
            ]);
        }

        $this->pageService->insertBlock('123', $block_data,'5f4f3a84a87ce7796a33eec8', [
            'vk_group_id' => 123,
            'vk_user_id' => 123,
            'sort_value' => 6
        ]);
    }

    /**
     * @group block-usage-log
     */
    public function testUpdateBlockUsage()
    {
        $page_id = '123';

        $block_data = $this->getBlock($page_id);

        for ($i = 1; $i <= 5; $i++) {
            $this->pageService->addBlock('123', $block_data, [
                'vk_group_id' => 123,
                'vk_user_id' => 123,
                'sort_value' => $i
            ]);
        }

        $recent_blocks = $this->pageService->getRecentBlocks(123);

        /**
         * Stored recent blocks are equal 5
         */
        $this->assertEquals(5, count($recent_blocks));

    }

    /**
     * @group block-usage-log
     */
    public function testUpdateBlockUsageMaxCount()
    {
        $page_id = '123';

        $block_data = $this->getBlock($page_id);

        /**
         * Add blocks usage log max value
         */
        for ($i = 1; $i <= BlocksUsageLogRepositoryFake::MAX_GROUP_ITEMS; $i++) {
            $this->pageService->addBlock('123', $block_data, [
                'vk_group_id' => 123,
                'vk_user_id' => 123,
                'sort_value' => $i
            ]);
        }

        /**
         * Try to add one additional item
         */
        $this->pageService->addBlock('123', $block_data, [
            'vk_group_id' => 123,
            'vk_user_id' => 123,
            'sort_value' => 987
        ]);
        
        $recent_blocks = $this->pageService->getRecentBlocks((int) $page_id);

        /**
         * Expect that the number of elements is not more than the allowed maximum number
         */
        $this->assertEquals(BlocksUsageLogRepositoryFake::MAX_GROUP_ITEMS, count($recent_blocks));

    }

    /**
     * @group update-block
     */

    public function testUpdateBlockMaxItemsCount()
    {

        $this->expectException(App\Exceptions\Pages\PageMaxBlockItemsCountException::class);

        $page_id = 'id1';

        $init_page_data = [
            'name' => 'test',
            'vk_user_id' => 234,
            'vk_group_id' => 123
        ];

        $this->pageService->createPage($init_page_data);

        $block_data = $this->getBlockWithItems($page_id);

        for ($i = 0; $i <= 11; $i++) {
            $block_data['items'][] = $this->getItem();
        }

        $this->pageService->updateBlock($block_data, [
            'vk_group_id' => 123
        ]);

    }

    /**
     * Получение данных страницы для конечного пользователя - только необходимые данные, без лишних блоков, статистики и состояний
     * @group get-pages
     */
    public function testGetPageProd()
    {
        $page_id = 'id1';

        $init_page_data = [
            'name' => 'test',
            'vk_user_id' => 234,
            'vk_group_id' => 123
        ];

        $this->pageService->createPage($init_page_data);

        $page = $this->pageService->getOneProd($page_id, 123);

        $block_data = $this->getBlock($page_id);

        for($i = 0; $i < 5; $i++) {
            $this->pageService->addBlock($page_id, $block_data, [
                "vk_group_id" => 123,
                "vk_user_id" => 234,
                "sort_value" => $i + 1
            ]);
        }

        $this->pageService->publish($page_id, [
            'vk_user_id' => 234,
            'vk_group_id' => 123
        ]);
            
        $page = $this->pageService->getOneProd($page_id, 123);

        $this->assertEquals(0, count($page->getBlocksEdit()));
        $this->assertEquals(0, count($page->getStatisticSummary()));
        $this->assertEquals(0, count($page->getStates()));

        $page_data = $page->toArrayProd();

        $this->assertEquals(isset($page_data['blocks_edit']), false);
        $this->assertEquals(isset($page_data['states']), false);
        $this->assertEquals(isset($page_data['statisticSummary']), false);
        $this->assertEquals(isset($page_data['author_vk_user_id']), false);
        $this->assertEquals(isset($page_data['updated_at']), false);
        $this->assertEquals(isset($page_data['created_at']), false);
        $this->assertEquals(isset($page_data['status']), false);

        foreach($page_data['blocks'] as $block) {
            $this->assertEquals(isset($block['created']), false);
        }

    }
}