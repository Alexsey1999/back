<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class GuideTest extends TestCase
{

    /**
     * @group guideTestCases
     */

    public function testCreateGroupGuide()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $body = [
            'params' => json_encode(Mocks::getParams())
        ];

        $createResponse = $this->call('POST', '/guide/get/' . 168143554, $body, [], [], $headers);

        $createResult = json_decode($createResponse->getContent(), true);
        $createData = $createResult['data'];

        $this->assertEquals(200, $createResponse->status());
        $this->assertArrayHasKey('seen_title_tooltip', $createData);
        $this->assertArrayHasKey('seen_context_tooltip', $createData);
        $this->assertArrayHasKey('visited_initial_settings', $createData);
        // $this->assertEquals(false, $createData['seen_title_tooltip']);
        // $this->assertEquals(false, $createData['seen_context_tooltip']);
        // $this->assertEquals(false, $createData['visited_initial_settings']);

        $deleteResponse = $this->call('POST', '/guide/delete/' . 168143554, [
            'params' => json_encode(Mocks::getParams())
        ], [], [], $headers);
        $this->assertEquals(200, $deleteResponse->status());

    }

    /**
     * @group guideTestCases
     */
    public function testUpdateGroupGuide()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $body = [
            "params" => json_encode(Mocks::getParams())
        ];

        $createResponse = $this->call('POST', '/guide/get/' . 168143554, $body, [], [], $headers);

        $createResult = json_decode($createResponse->getContent(), true);
        $createData = $createResult['data'];

        $this->assertEquals(200, $createResponse->status());

        $createData['seen_title_tooltip'] = true;
        $createData['seen_context_tooltip'] = true;

        $body = [
            'guide' => json_encode($createData),
            "params" => json_encode(Mocks::getParams())
        ];

        $updateResponse = $this->call('POST', '/guide/update/' . 168143554, $body, [], [], $headers);
        $this->assertEquals(200, $updateResponse->status());
        $updateData = json_decode($updateResponse->getContent(), true);

        $this->assertEquals('success', $updateData['result']);

        $body = [
            "params" => json_encode(Mocks::getParams())
        ];

        $getAfterUpdate = $this->call('POST', '/guide/get/' . 168143554, $body, [], [], $headers);

        $getAfterUpdateResult = json_decode($getAfterUpdate->getContent(), true);
        $getAfterUpdateData = $getAfterUpdateResult['data'];

        $this->assertEquals(200, $getAfterUpdate->status());

        $this->assertEquals(true, $getAfterUpdateData['seen_title_tooltip']);
        $this->assertEquals(true, $getAfterUpdateData['seen_context_tooltip']);


        $deleteResponse = $this->call('POST', '/guide/delete/' . 168143554, [
            'params' => json_encode(Mocks::getParams())
        ], [], [], $headers);

        $resultAfterDelete = json_decode($deleteResponse->getContent(), true);

        $this->assertEquals(200, $deleteResponse->status());
        $this->assertEquals('success', $resultAfterDelete['result']);
    }

}