<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

/**
 * Получение виджетов сообщества
 * С каждым запросом должен передаваться ID сообщества
 * Чтобы пройти авторизацию
 */

class GetWidgetsTest extends TestCase
{
    /**
     * Запрос на получение виджетов сообщества
     * без авторизационных данных 
     * без id сообщества
     * @group blank-widgets-request
     */
    public function testBlankRequest()
    {

        $response = $this->call('POST', '/get-all', []);
        $this->assertEquals(
            403,
            $response->status()
        );
    }

    /**
     * Запрос на получение виджетов сообщества
     * без id сообщества
     */
    public function testNoGroupIdRequest()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $response = $this->call('POST', '/get-all', [
            'params' => json_encode(Mocks::getParams())
        ], [], [], $headers);
        $this->assertEquals(
            404,
            $response->status()
        );
    }

    /**
     * Запрос на получение виджетов сообщества
     * с некорректным id сообщества
     */
    public function testBadRequest()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $response = $this->call('POST', '/get-all/somestring', [
            'params' => json_encode(Mocks::getParams())
        ], [], [], $headers);
        $this->assertEquals(
            400,
            $response->status()
        );
    }

    /**
     * Попытка инъекции с помощью которой можно получить 
     * все виджеты, кроме группы с id = 2
     */
    public function testInjectionRequest()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $response = $this->call('POST', '/get-all/[\'$ne\'=>2]', [
            'params' => json_encode(Mocks::getParams())
        ], [], [], $headers);

        $this->assertEquals(
            400,
            $response->status()
        );
    }

    public function testSuccessWidgetsGetWithData()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $response = $this->call('POST', '/get-all/168143554', [
            'params' => json_encode(Mocks::getParams())
        ], [], [], $headers);

        $this->assertEquals(
            200,
            $response->status()
        );
    }


    /**
     * Попытка получения виджетов сообщества 
     * от лица администратора другого сообщества
     * В урле подменен ID сообщества на 999999999
     * Хотя запрос делается от имени администратора сообщества c ID 168143554
     */
    public function testFakeVkGroupId()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $response = $this->call('POST', '/get-all/999999999', [
            'params' => json_encode(Mocks::getParams())
        ], [], [], $headers);

        $this->assertEquals(
            403,
            $response->status()
        );
    }


    /**
     * Попытка получения виджетов сообщества
     * от лица администратора другого сообщества
     * В урле подменен ID сообщества на 999999999
     * Хотя запрос делается от имени администратора сообщества c ID 168143554
     * Пробуем подделать vk_group_id в подписи запроса
     */
    public function testFakeSingVkGroupId()
    {

        $headers = [
            'HTTP_REFERER' => Mocks::getFakeAdminReferer()
        ];

        $response = $this->call('POST', '/get-all/999999999', [
            'params' => json_encode(Mocks::getFakeParams())
        ], [], [], $headers);

        $this->assertEquals(
            403,
            $response->status()
        );
    }

    /**
     * Запрос с коррректными данными в подписи
     * и корректным group_id к которому есть доступ у автора запроса
     */
    public function testSuccessRequest()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $response = $this->call('POST', '/get-all/168143554', [
            'params' => json_encode(Mocks::getParams())
        ], [], [], $headers);

        $this->assertEquals(
            200,
            $response->status()
        );
    }
}