<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class RoutesTest extends TestCase
{
    public function testCloneRoute()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $createBody = [
            "type" => "cover_list",
            "type_api" => "banners",
            "name" => "widget_test",
            "group_id" => "168143554",
            "vk_group_id" => "168143554",
            'params' => json_encode(Mocks::getParams())
        ];

        $response = $this->call('POST', '/create', $createBody, [], [], $headers);

        $createResponse = json_decode($response->getContent(), true);
        $createdWidget = $createResponse['response'];

        $getAllBody = [
            "vk_group_id" => "168143554",
            'params' => json_encode(Mocks::getParams())
        ];

        $getAllresponse = $this->call('POST', '/get-all/168143554', $getAllBody, [], [], $headers);

        $allWidgesAfterCreate = json_decode($getAllresponse->getContent(), true)['response'];

        $cloneBody = [
            "widget_id" => $createdWidget['id'],
            "vk_group_id" => "168143554",
            'params' => json_encode(Mocks::getParams())
        ];

        $cloneResponse = $this->call('POST', '/clone', $cloneBody, [], [], $headers);
        $allWidgetsAfterCloneAction = json_decode($cloneResponse->getContent(), true)['response']['all'];
        $clone = json_decode($cloneResponse->getContent(), true)['response']['new'];

        $this->assertEquals(
            200,
            $cloneResponse->status()
        );

        $this->assertEquals(
            true,
            (count($allWidgetsAfterCloneAction) > count($allWidgesAfterCreate))
        );

        $deleteBody = [
            'ids' => [
                $clone['id'], $createdWidget['id']
            ],
            'vk_group_id' => "168143554",
            'params' => json_encode(Mocks::getParams())
        ];

        $deleteResponse = $this->call('POST', '/delete', $deleteBody, [], [], $headers);
        $deleteResult = json_decode($deleteResponse->getContent(), true)['response'];
        
        $this->assertEquals(
            $deleteResult,
            true
        );
    }

    /**
     * @group testMaxCountWidgets
     * Tests an attempt to create a new widget and an attempt to copy when the limit is reached
     */
    public function testMaxCountWidgets()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $getAllBody = [
            "vk_group_id" => "168143554",
            'params' => json_encode(Mocks::getParams())
        ];

        // First we get all the community widgets and count their number
        $getAllresponse = $this->call('POST', '/get-all/168143554', $getAllBody, [], [], $headers);

        $initialAllWidgets = json_decode($getAllresponse->getContent(), true)['response'];
        $initialWidgetsCount = count($initialAllWidgets);

        // Calculate the required amount until full
        $requiredAmount = 100 - $initialWidgetsCount;

        $created_widgets = [];

        $createBody = [
            "type" => "cover_list",
            "type_api" => "banners",
            "name" => "widget_test",
            "group_id" => "168143554",
            "vk_group_id" => "168143554",
            'params' => json_encode(Mocks::getParams())
        ];

        // Fill the community with widgets to the maximum number
        for ($i = 0; $i < $requiredAmount; $i++ ) {
            $createResponse = $this->call('POST', '/create', $createBody, [], [], $headers);
            $widget = json_decode($createResponse->getContent(), true)['response'];
            $created_widgets[] = $widget['id'];
        }

        // Trying to overload community widgets
        $overloadCreateResponse = $this->call('POST', '/create', $createBody, [], [], $headers);

        // We expect that in response we get a 400 error
        $this->assertEquals(
            400,
            $overloadCreateResponse->status()
        );

        // Trying to clone random widget
        $cloneBody = [
            'vk_group_id' => '168143554',
            'widget_id' => $created_widgets[0],
            'params' => json_encode(Mocks::getParams())
        ];

        $overloadCopyResponse = $this->call('POST', 'clone', $cloneBody, [], [], $headers);

        // We expect that in response we get a 400 error
        $this->assertEquals(
            $overloadCopyResponse->status(),
            400
        );

        $deleteBody = [
            'ids' => $created_widgets,
            'vk_group_id' => '168143554',
            'params' => json_encode(Mocks::getParams())
        ];

        // Delete newly created widgets
        $deleteResponse = $this->call('POST', '/delete', $deleteBody, [], [], $headers);
        $deleteResult = json_decode($deleteResponse->getContent(), true)['response'];

        // We expect that response from delete action will be successed
        $this->assertEquals(
            $deleteResult,
            true
        );

        $getAllResponseEnd = $this->call('POST', '/get-all/168143554', $getAllBody, [], [], $headers);
        $endAllWidgets = json_decode($getAllResponseEnd->getContent(), true)['response'];
        $endWidgetsCount = count($endAllWidgets);

        // We expect that all newly created widgets were successfully removed 
        $this->assertEquals(
            $initialWidgetsCount,
            $endWidgetsCount
        );

    }

    /**
     * @group testWidgetRenameRoute
     * Tests an attempt to rename existing widget
     */
    public function testWidgetRenameRoute()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $createBody = [
            "type" => "cover_list",
            "type_api" => "banners",
            "name" => "widget_test",
            "group_id" => "168143554",
            "vk_group_id" => "168143554",
            'params' => json_encode(Mocks::getParams())
        ];

        $response = $this->call('POST', '/create', $createBody, [], [], $headers);

        $createResponse = json_decode($response->getContent(), true);
        $createdWidget = $createResponse['response'];

        $renameBody = [
            'name' => 'widget_test_new_name',
            'params' => json_encode(Mocks::getParams())
        ];

        $renameResponse = $this->call('POST', 'update-name/' . $createdWidget['id'], $renameBody, [], [], $headers);
        $renamedWidget = json_decode($renameResponse->getContent(), true)['response'];

        $this->assertEquals('widget_test_new_name', $renamedWidget['name']);

        $deleteBody = [
            'ids' => [ $renamedWidget['id']],
            'vk_group_id' => "168143554",
            'params' => json_encode(Mocks::getParams())
        ];

        $deleteResponse = $this->call('POST', '/delete', $deleteBody, [], [], $headers);
        $deleteResult = json_decode($deleteResponse->getContent(), true)['response'];
        
        $this->assertEquals(true, $deleteResult);
    }
}