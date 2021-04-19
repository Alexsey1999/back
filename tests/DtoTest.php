<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

use App\Dto\CreateNewPageData;

class DtoTest extends TestCase
{
    /**
     * @group testCorrectPageCreateDTO
     */
    public function testCorrectPageCreateDTO()
    {
        $dto = new CreateNewPageData([
            'name' => 'test',
            'vk_group_id' => 1,
            'vk_user_id' => 1,
        ]);

        $this->assertEquals('test', $dto->name);
        $this->assertEquals(1, $dto->vk_group_id);
        $this->assertEquals(1, $dto->vk_group_id);
    }

    /**
     * @group testIncorrectPageCreateDTO
     */
    public function testIncorrectPageCreateDTO()
    {
        $this->expectException(\DomainException::class);
        
        $dto = new CreateNewPageData([
            'name' => 'test',
            // 'vk_group_id' => 1,
            'vk_user_id' => 1,
        ]);
    }
}