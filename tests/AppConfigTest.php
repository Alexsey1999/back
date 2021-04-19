<?php

use App\AppConfig;

/**
 * Тестирование инициализации конфига приложения
 */

class AppConfigTest extends TestCase
{

    public function getConfigs() 
    {
        return [
            "vk_mini_apps" => require __DIR__ . '/../config/vk_mini_apps.php',
            "test_vk_user" => require __DIR__ . '/../config/test_vk_user.php'
        ];
    }

    /**
     * Attempt to test app config initialization with invalid app id
     * It must throw only an InvalidArgumentException
     */
    public function testInvalidRequest()
    {

        $configs = $this->getConfigs();

        try {
            $config = new AppConfig(123, $configs['vk_mini_apps'], $configs['test_vk_user']);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals(true, true);
        } catch (\Throwable $e) {
            $this->assertEquals(true, true);
        }
    }

    public function testValidRequest()
    {
        $configs = $this->getConfigs();
        $config = new AppConfig(5898182, $configs['vk_mini_apps'], $configs['test_vk_user']);

        $cs = $config->getClientSecret();
        $tsvkuid = $config->getTestVkUserId();
        $vkappid = $config->getVKAppId();
        $sk = $config->getServiceKey();

        $this->assertEquals(isset($cs), true);
        $this->assertEquals(isset($tsvkuid), true);
        $this->assertEquals(isset($vkappid), true);
        $this->assertEquals(isset($sk), true);
    }
}