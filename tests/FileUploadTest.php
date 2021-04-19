<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class FileUploadTest extends TestCase
{
    /**
     * Попытка отправить php файл
     */
    public function testExecuteFileUpload()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer()
        ];

        $file_path = __DIR__ . '/somefile.php';

        $content = "<?php echo 'test'; ?>";

        file_put_contents($file_path, $content);

        $body = [
            "image_type" => "160x160",
            "token" => "sometoken",
            "vk_group_id" => "168143554",
            "image" => fopen($file_path, 'r'),
            'params' => json_encode(Mocks::getParams())
        ];

        $response = $this->call('POST', '/image', $body, [], [], $headers);

        $this->assertEquals(
            $response->getData()->result,
            'error'
        );

        unlink($file_path);
    }

    /**
     * Попытка отправить php файл c расширением изображения
     */
    public function testExecuteFileUploadWithFakeExtencion()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer(),
            'Content-Type' => 'image/jpeg'
        ];

        $file_path = __DIR__ . '/somefile.jpeg';

        $content = "<?php echo 'test'; ?>";

        file_put_contents($file_path, $content);

        $body = [
            "image_type" => "160x160",
            "token" => "sometoken",
            "vk_group_id" => "168143554",
            "image" => fopen($file_path, 'r'),
            'params' => json_encode(Mocks::getParams())
        ];

        
        $response = $this->call('POST', '/image', $body, [], [], $headers);

        $this->assertEquals(
            $response->getData()->result,
            'error'
        );

        unlink($file_path);
    }

    /**
     * Попытка отправить исполняемый файл  в экшене для загрузки документа
     */
    public function testExecuteDocumentUpload()
    {

        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer(),
            'Content-Type' => 'image/jpeg'
        ];

        $body = [
            "url" => "https://vk.com/doc4871362_532253538",
            "ext" => "jpg",
            "image_type" => "160x160",
            "token" => "sometoken",
            "vk_group_id" => "168143554",
            'params' => json_encode(Mocks::getParams())
        ];

        $response = $this->call('POST', '/document', $body, [], [], $headers);

        $this->assertEquals(
            $response->getData()->result,
            'error'
        );

    }

    /**
     * @group file-upload
     * Tests image upload api
     */
    public function testWrongFormatFileUpload()
    {
        $headers = [
            'HTTP_REFERER' => Mocks::getAdminReferer(),
            'Content-Type' => 'image/jpeg'
        ];

        $body = [
            "image_type" => "160",
            "token" => "sometoken",
            "vk_group_id" => "168143554",
            'params' => json_encode(Mocks::getParams())
        ];

        $response = $this->call('POST', '/image', $body, [], [], $headers);
        $this->assertEquals(
            $response->status(),
            400
        );

    }
}

?>