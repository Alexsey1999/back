<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class PublishWidgetsTest extends TestCase
{
    public function testPublishRouteWithFakeId()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $body = [
            'group_id' => 999999999,
            'ids' => [],
            'code' => 'test',
            'token' => 'testtoken',
            'params' => json_encode(Mocks::getParams())
        ];

        $response = $this->call('POST', '/publish', $body, [], [], $headers);
        $this->assertEquals(
            403,
            $response->status()
        );
    }

    public function testPublishRouteWithFakeSign() {
        $headers = [
            'HTTP_REFERER' => Mocks::getFakeAdminReferer()
        ];

        $body = [
            'group_id' => 999999999,
            'ids' => [],
            'code' => 'test',
            'token' => 'testtoken',
            'params' => json_encode(Mocks::getFakeParams())
        ];

        $response = $this->call('POST', '/publish', $body, [], [], $headers);
        $this->assertEquals(
            403,
            $response->status()
        );
    }

    public function testDiscardRoute()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $body = [
            'group_id' => 999999999,
            'ids' => [],
            'code' => 'test',
            'token' => 'testtoken',
            'params' => json_encode(Mocks::getParams())
        ];

        $response = $this->call('POST', '/publish', $body, [], [], $headers);
        $this->assertEquals(
            403,
            $response->status()
        );
    }

    public function testDeleteRouteWithSameGroupId()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $body = [
            'vk_group_id' => 168143554,
            'ids' => ['5e16e4b61815b91c634fc910'],
            'params' => json_encode(Mocks::getParams())
        ];

        $response = $this->call('POST', '/delete', $body, [], [], $headers);
        
        $this->assertEquals(
            200,
            $response->status()
        );
    }

    public function testDeleteRouteWithFakeSign()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getFakeAdminReferer()
        ];

        $body = [
            'group_id' => 999999999,
            'ids' => [1,2,3],
            'params' => json_encode(Mocks::getFakeParams())
        ];

        $response = $this->call('POST', '/delete', $body, [], [], $headers);
        $this->assertEquals(
            403,
            $response->status()
        );
    }

    public function testDiscardRouteWithNotAccessedWidgetId()
    {
        $createHeaders = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $createBody = [
            "type" => "tiles",
            "type_api" => "carousel",
            "name" => "fgh",
            "group_id" => 168143554,
            "vk_group_id" => 168143554,
            "params" => json_encode(Mocks::getParams())
        ];

        $createResponse = $this->call('POST', '/create', $createBody, [], [], $createHeaders);

        $discardHeaders = [
            'HTTP_REFERER' => Mocks::getFakeAdminReferer()
        ];

        $createdId = $createResponse->getData()->response->id;

        $response = $this->call('POST', '/discard/' . $createdId, [
            'params' => json_encode(Mocks::getFakeParams())
        ], [], [], $discardHeaders);

        $this->assertEquals(
            403,
            $response->status()
        );

        $deleteBody = [
            'ids' => [ $createdId ],
            'vk_group_id' => 168143554,
            'params' => json_encode(Mocks::getParams())
        ];

        $deleteResponse = $this->call('POST', '/delete', $deleteBody, [], [], $createHeaders);
        $deleteResult = json_decode($deleteResponse->getContent(), true)['response'];

        $this->assertEquals(
            $deleteResult,
            true
        );
    }
}

?>