<?php

namespace App\Repositories\Fakes;

use App\Interfaces\WidgetsRepositoryInterface;
use App\Widgets\Models\BaseWidget;
use App\Widgets\WidgetCollection;
use App\Widgets\WidgetFactory;

class WidgetFakeRepository implements WidgetsRepositoryInterface
{

    private $storage = [];

    public function getOne(string $id): BaseWidget 
    {
        $doc = $this->get($id);
        $widget = (new WidgetFactory())->createWidget($doc['type']);
        $widget->loadFromDocument($doc); 

        return $widget;
    }

    public function getMany(array $ids): WidgetCollection 
    {
        $collection = new WidgetCollection();
        $factory = new WidgetFactory();

        foreach($this->storage as $doc) {
            $widget = $factory->createWidget($doc['type']);
            $widget->loadFromDocument($doc);

            $collection->set($widget);
        }

        return $collection;
    }

    public function deleteOne(string $id, int $group_id): bool { return true; }

    public function deleteMany(array $ids, int $group_id): bool { return true; }

    public function getGroupWidgets(int $group_id): WidgetCollection 
    { 
        return $this->getMany([1]); 
    }

    public function createWidget(array $data): BaseWidget 
    { 
        $widget = (new WidgetFactory())->createWidget($data['type']);
        $widget->create($data);

        $sortValue = $this->getNextSortValueForGroup($widget->group_id, $widget->type);
        $widget->setInitialSort($sortValue);

        $write_data = $widget->toArray();

        return $widget; 
    }

    public function sortWidgets(array $widgets, int $group_id) {}

    public function updateWidget(BaseWidget $widget) 
    {
        $id = $widget->id;

        $this->storage[$id] = $widget->toArray();

        return true;
    }

    public function updateWidgetStatus(string $id, int $status): bool { return true; }

    public function updateWidgetName($widget, string $name): bool { return true; }

    public function getNextSortValueForGroup(int $group_id, string $type): int { return 1; }

    public function countGroupWidgets(int $group_id): int { return 1; }

    public function __construct()
    {
        $this->storage = [
            '1' => [
                'id' => '1',
                'sort' => 1,
                'name' => 'name',
                'type' => 'text',
                'group_id' => 1,
                'status' => 0,
                'type_api' => 'message',
                'code' => [
                    'descr' => "Test 2",
                    'more' => "Test 2",
                    'more_url' => "https://vk.com/feed",
                    'text' => "Test 2",
                    'title' => "Test 2",
                    'title_url' => "https://vk.com/feed",
                ],
                'audience' => [
                    'sex' => 0, 
                    'ageFrom' => 18,
                    'ageTo' => 26
                ],
                'last_published_state' => json_encode([
                    'code' => [
                        'descr' => "Test 1",
                        'more' => "Test 1",
                        'more_url' => "https://vk.com/feed",
                        'text' => "Test 1",
                        'title' => "Test 1",
                        'title_url' => "https://vk.com/feed",
                    ],
                    'audience' => [
                        'sex' => 1, 
                        'ageFrom' => 20,
                        'ageTo' => 35
                    ]
                ]),
                'created' => [],
                'updated' => []
            ],
            '2' => [
                'id' => '2',
                'sort' => 2,
                'name' => 'name 2',
                'type' => 'text',
                'group_id' => 1,
                'status' => 0,
                'type_api' => 'message',
                'code' => [
                    'descr' => "Test 3",
                    'more' => "Test 3",
                    'more_url' => "https://vk.com/feed2",
                    'text' => "Test 3",
                    'title' => "Test 3",
                    'title_url' => "https://vk.com/feed2",
                ],
                'audience' => [],
                'last_published_state' => json_encode([]),
                'created' => [],
                'updated' => []
            ],
            '3' => [
                'id' => '3',
                'sort' => 3,
                'name' => 'name 3',
                'type' => 'text',
                'group_id' => 1,
                'status' => 0,
                'type_api' => 'message',
                'code' => [
                    'descr' => "Test 4",
                    'more' => "Test 4",
                    'more_url' => "https://vk.com/feed4",
                    'text' => "Test 4",
                    'title' => "Test 4",
                    'title_url' => "https://vk.com/feed4",
                ],
                'audience' => [],
                'last_published_state' => json_encode([]),
                'created' => [],
                'updated' => []
            ]
        ];
    }

    private function get($id) 
    {
        if (isset($this->storage[$id])) {
            return $this->storage[$id];
        } else {
            return null;
        }
    }
}