<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Palette\RGB;

use App\Workers\ImgHelper;
use App\Repositories\WidgetsRepository;

class WorkersTest extends TestCase
{

    const TEMP_IMAGE_PATH = __DIR__ . '/../temp';

    protected $widgetsRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->widgetsRepository = $this->app->make(WidgetsRepository::class);
    }

    public function testMongoGetNextSortValue()
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

        $firstCreateResponse = $this->call('POST', '/create', $createBody, [], [], $headers);
        $firstWidget = json_decode($firstCreateResponse->getContent(), true)['response'];

        $nextWidgetSortValue = $this->widgetsRepository->getNextSortValueForGroup((int) $firstWidget['group_id'], (string) $firstWidget['type']);

        $nextCreateResponse = $this->call('POST', '/create', $createBody, [], [], $headers);
        $nextWidget = json_decode($nextCreateResponse->getContent(), true)['response'];

        $responseNextWidgetSortValue = (int) $nextWidget['sort'];

        $this->assertEquals(
            $nextWidgetSortValue,
            $responseNextWidgetSortValue
        );

        $deleteBody = [
            'ids' => [$firstWidget['id'], $nextWidget['id']],
            'vk_group_id' => "168143554",
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

    }

    /**
     * @group imagehelper
     * Tests image helper api
     */
    public function testResizeCrop()
    {
        
        $file_path = $this->createImage(1000, 1000);
        // Get square image 160px - 160px
        $square_image = ImgHelper::getCropedImage($file_path, 160, 160);
        $square_image_size = getimagesize($square_image);

        $this->assertEquals($square_image_size[0], 160);
        $this->assertEquals($square_image_size[1], 160);


        // Get rectaandular image image 160px - 160px
        $file_path_2 = $this->createImage(1000, 1000, 2);
        $rectangular_image = ImgHelper::getCropedImage($file_path_2, 1380, 460);
        $rectangular_image_size = getimagesize($rectangular_image);

        $this->assertEquals($rectangular_image_size[0], 1380);
        $this->assertEquals($rectangular_image_size[1], 460);


        // Get square image 160px - 240px
        $file_path_3 = $this->createImage(460, 640, 3);
        $square_image_2 = ImgHelper::getCropedImage($file_path_3, 160, 240);
        $square_image_2_size = getimagesize($square_image_2);

        $this->assertEquals($square_image_2_size[0], 160);
        $this->assertEquals($square_image_2_size[1], 240);

        // Get rectaandular image image 160px - 160px
        $file_path_4 = $this->createImage(1000, 1000, 4);
        $rectangular_image_2 = ImgHelper::getCropedImage($file_path_4, 1530, 384);
        $rectangular_image_2_size = getimagesize($rectangular_image_2);

        $this->assertEquals($rectangular_image_2_size[0], 1530);
        $this->assertEquals($rectangular_image_2_size[1], 384);


        unlink($file_path);
        unlink($file_path_2);
        unlink($file_path_3);
        unlink($file_path_4);

        rmdir(self::TEMP_IMAGE_PATH);

    }

    private function createImage(int $width, int $height, int $index = 1): string
    {

        if (!file_exists(self::TEMP_IMAGE_PATH)) {
            mkdir(self::TEMP_IMAGE_PATH, 0755);
        }

        $imagine = new Imagine();
        $size = new Box($width, $height);
        $palette = new RGB();
        $color = $palette->color('#000', 100);

        $file_path = self::TEMP_IMAGE_PATH . '/test-'. $index .'.png';

        $image = $imagine->create($size, $color);
        $image->save($file_path);



        return $file_path;
    }
}