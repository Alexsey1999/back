<?php

use App\Services\WidgetService;
use App\Widgets\WidgetFactory;
use App\Widgets\WidgetCollection;
use App\Repositories\Fakes\WidgetFakeRepository;
use App\Widgets\Models\BaseWidget;

class WidgetServiceDomainLayerTest extends TestCase
{
    /**
     * @group testWidgetCreate
     */
    public function testWidgetCreate() 
    {
        $repo = new WidgetFakeRepository();

        $creationData = [
            'type' => 'text',
            'group_id' => 123456,
            'name' => 'created_widget',
            'type_api' => 'message',
            'code' => [
                'title' => ''
            ],
            'audience' => [],
            'vk_user_id' => 1
        ];

        $widget = $repo->createWidget($creationData);

        $this->assertEquals(123456, $widget->group_id);
        $this->assertEquals('text', $widget->type);
        $this->assertEquals('created_widget', $widget->name);
        $this->assertEquals('message', $widget->type_api);
        $this->assertEquals(1, $widget->created['user_id']);
        $this->assertEquals(1, $widget->updated['user_id']);
    }

    /**
     * @group testWidgetPublicMethods
     */
    public function testWidgetPublicMethods()
    {
        $repo = new WidgetFakeRepository();

        $widget = $repo->getOne(1);

        $widget->setName('New name');

        $this->assertEquals('New name', $widget->name);

        $widget->disable();

        $this->assertEquals(0, $widget->status);

        $widget->enable();

        $this->assertEquals(1, $widget->status);

        $widget->setInitialSort(5);

        $this->assertEquals(5, $widget->sort);

        $newAudience = [
            'sex' => 1, 
            'ageFrom' => 20,
            'ageTo' => 30
        ];

        $widget->updateAudience($newAudience);

        $this->assertEqualsCanonicalizing($newAudience, $widget->audience);

        $newCode = [
            'descr' => "New descr",
            'more' => "New more",
            'more_url' => "https://vk.com/feed1",
            'text' => "New text",
            'title' => "New title",
            'title_url' => "https://vk.com/feed2",
        ];

        $widget->setBody($newCode);

        $this->assertEqualsCanonicalizing($newCode, $widget->code);

    }
    
    /**
     * @group testDiscardWidgetChanges
     */
    public function testDiscardWidgetChanges()
    {
        $repo = new WidgetFakeRepository();
        $widgetService = new WidgetService($repo);

        /**
         * Создаим тестовую сущность виджета
         */
        $testWidget = $repo->getOne('1');

        /**
         * Отменим изменения у этой же сущности виджета, но через прикладной сервис cервис
         */
        $updatedWidget = $widgetService->discardWidgetChanges('1', [
            'group_id' => 1
        ]);

        /**
         * Проверим изначальные данные тестового виджета
         */
        $this->assertEquals('Test 2', $testWidget->code['title']);
        $this->assertEquals('Test 2', $testWidget->code['text']);

        /**
         * Проверим данные этого же виджета, но после отката для последнегно опубликованного состояния
         */
        $this->assertEquals('Test 1', $updatedWidget->code['title']);
        $this->assertEquals('Test 1', $updatedWidget->code['text']);

    }

    /**
     * @group testWidgetCollection
     */
    public function testWidgetCollection()
    {
        $repo = new WidgetFakeRepository();
        $collection = $repo->getGroupWidgets(1);
        
        $this->assertEquals(true, $collection instanceof WidgetCollection);

        $item = $collection->current();
        $this->assertEquals('1', $item->id);

        $collection->next();
        $item = $collection->current();
        $this->assertEquals('2', $item->id);

        $collection->rewind();
        $item = $collection->current();
        $this->assertEquals('1', $item->id);

        foreach($collection as $widget) {
            $this->assertEquals(true, $widget instanceof BaseWidget);
        }

        $resultArray = $collection->toArray();

        $this->assertEquals(true, is_array($resultArray));

        foreach($resultArray as $item) {
            $this->assertEquals(true, is_array($item));
        }

    }

    /**
     * @group testPublishWidgets
     */
    public function testPublishWidgets()
    {
        $repo = new WidgetFakeRepository();
        $service = new WidgetService($repo);

        $service->publishWidgets(['1', '2'], [], [], [
            'group_id' => 1,
            'vk_user_id' => 1
        ]);

        $widget = $repo->getOne('1');
        $this->assertEquals(true, $widget->isActive());

        $widget = $repo->getOne('2');
        $this->assertEquals(true, $widget->isActive());

        $widget = $repo->getOne('3');
        $this->assertEquals(false, $widget->isActive());
    }

    /**
     * @group testUpdateAudience
     */
    public function updateAudienceTest()
    {
        $repo = new WidgetFakeRepository();
        $service = new WidgetService($repo);

        $newAudience = [
            'sex' => 1,
            'ageTo' => 40
        ];

        $service->updateWidgetAudience('2', [
            'group_id' => 1,
            'vk_user_id' => 444,
            'audience' => $newAudience
        ]);

        $widget = $repo->getOne('2');

        $this->assertEqualsCanonicalizing($newAudience, $widget->audience);
    }
}