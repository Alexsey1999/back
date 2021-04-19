<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class CreatedUpdatedFieldsTest extends TestCase
{
    /**
     * @group createUpdateFieldsTest
     * Tests widget created and updated fields
     */
    public function testFileds()
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
            "params" => json_encode(Mocks::getParams())
        ];

        // First of all, a new widget is created

        $response = $this->call('POST', '/create', $createBody, [], [], $headers);

        $createResponse = json_decode($response->getContent(), true);
        $createdWidget = $createResponse['response'];

        // Make sure that widget has fields "created" and "updated"
        $this->assertArrayHasKey('created', $createdWidget);
        $this->assertArrayHasKey('updated', $createdWidget);

        // Make sure that new fields has corrent value
        $this->assertTrue(is_numeric($createdWidget['created']['timestamp']));
        $this->assertTrue(is_numeric($createdWidget['updated']['timestamp']));

        // Make sure that "created" filed contains correct value 
        $this->assertEquals('4871362', $createdWidget['created']['user_id']);

        sleep(1);

        $updateCodeBody = [
            'vk_group_id' => 168143554,
            'code' => json_encode([
                "title" => "test",
                "title_url" => "",
                "more" => "",
                "more_url" => "",
                "rows" => []
            ]),
            "params" => json_encode(Mocks::getParams())
        ];

        // Then we update the newly created widget

        $updateResponse = $this->call('POST', '/update/' . $createdWidget['id'], $updateCodeBody, [], [], $headers);

        $parsedUpdateResponse = json_decode($updateResponse->getContent(), true);
        $updatedWidget = $parsedUpdateResponse['response'];

        // Make sure that widget still has fields "created" and "updated"
        $this->assertArrayHasKey('created', $updatedWidget);
        $this->assertArrayHasKey('updated', $updatedWidget);

        // Make sure that new fields still contains correct values
        $this->assertTrue(is_numeric($updatedWidget['updated']['timestamp']));
        $this->assertEquals('4871362', $updatedWidget['updated']['user_id']);

        // Make sure that timestamp value of field "created" didn't chnaged after update
        $this->assertEquals($createdWidget['created']['timestamp'], $updatedWidget['created']['timestamp']);

        sleep(1);

        // Than we update audience value of newly created widget

        $updateAudienceBody = [
            'vk_group_id' => 168143554,
            'audience' => json_encode(["sex" => 1 ]),
            "params" => json_encode(Mocks::getParams())
        ];

        $updateAudienceResponse = $this->call('POST', '/update-audience/' . $createdWidget['id'], $updateAudienceBody, [], [], $headers);

        $parsedAudienceResponse = json_decode($updateAudienceResponse->getContent(), true);

        $updatedAudienceWidget = $parsedAudienceResponse['response'];

        // Make sure that we still have fields "created" and "updated"
        $this->assertArrayHasKey('created', $updatedAudienceWidget);
        $this->assertArrayHasKey('updated', $updatedAudienceWidget);

        // Make sure that new fields still contains correct values
        $this->assertTrue(is_numeric($updatedAudienceWidget['updated']['timestamp']));
        $this->assertEquals('4871362', $updatedAudienceWidget['updated']['user_id']);

        // Make sure that timestamp value of field "created" didn't changed after update
        $this->assertEquals($createdWidget['created']['timestamp'], $updatedAudienceWidget['created']['timestamp']);

        // Make sure that timestamp value of field "updated" changed after update
        $this->assertNotEquals($createdWidget['updated']['timestamp'], $updatedAudienceWidget['updated']['timestamp']);
        $this->assertGreaterThan($createdWidget['updated']['timestamp'], $updatedAudienceWidget['updated']['timestamp']);

        $deleteBody = [
            'ids' => [
                $createdWidget['id']
            ],
            'vk_group_id' => "168143554",
            "params" => json_encode(Mocks::getParams())
        ];

        $deleteResponse = $this->call('POST', '/delete', $deleteBody, [], [], $headers);
        $deleteResult = json_decode($deleteResponse->getContent(), true)['response'];

        $this->assertEquals(
            $deleteResult,
            true
        );
       
    }
}