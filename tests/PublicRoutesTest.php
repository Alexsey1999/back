<?php


/**
 * Эндпоинты, которые должны работать для всех пользователей
 */
class PublicRoutesTest extends TestCase
{
    /**
     * @group testPageStatisticRoutes
     * Тестируем доступность эндпоинтов для учета статистики промо-страниц
     * Запрос выполняем от имени тестового пользователя
     * То есть попись можно не передавать, нужны только данные запроса
     */
    public function testPageStatisticRoutes()
    {
        $hit_body = [
            "data" => json_encode(Mocks::getPageHitData())
        ];

        $hit_response = $this->call('POST', '/stat/hit', $hit_body, [], [], []);
        $hit_data = json_decode($hit_response->getContent(), true);
        $this->assertEquals(200, $hit_response->status());
        $this->assertTrue($hit_data['success']);


        $goal_body = [
            "data" => json_encode(Mocks::getPageGoalData())
        ];

        $goal_response = $this->call('POST', '/stat/goal', $goal_body, [], [], []);
        $goal_data = json_decode($goal_response->getContent(), true);
        $this->assertEquals(200, $goal_response->status());
        $this->assertTrue($goal_data['success']);
    }
}