<?php

namespace App\Workers;

use Error;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ManipulatorInterface;

class ImgHelper 
{
    public static $quality = 100;

    /**
     * Обрезает изображение до заданных размеров
     * Если размеры исходного изображения меньше необходимых - пустое пространство будет залито черным фоном
     * @param $file_path string - полный путь до файла внутри файловой системы
     * @param $size_x - Ширина финального изображения 
     * @param $size_y - Высота финального изображения
     */
    public static function getCropedImage(string $file_path, int $box_x, int $box_y): string
    {   

        $image_info = getImageSize($file_path);
        $image_width = $image_info[0];
        $image_height = $image_info[1];

        /**
         * Если размеры корректные изначально - просто вернем изобрабражение
         */
        if ($image_width === $box_x && $image_height === $box_y) {
            $file_path = self::processThumb($file_path, $box_x, $box_y);
            return $file_path;
        }

        if ($image_width < $box_x || $image_height < $box_y) {
            self::scaleImage($file_path, $box_x, $box_y);
        } else if ($image_width > $box_x && $image_height > $box_y) {
            self::resizeImage($file_path, $box_x, $box_y);
        }

        $file_path = self::processThumb($file_path, $box_x, $box_y);
        return $file_path;
    }

    public static function scaleImage(string $file_path, int $box_x, int $box_y) 
    {
        $image_info = getImageSize($file_path);
        $image_width = $image_info[0];
        $image_height = $image_info[1];
 
        $scale_box_x = 0;
        $scale_box_y = 0;

        $ratio = 1;

        if ($image_width >= $image_height) {
            if ($box_x - $image_width > $box_y - $image_height) {
                $ratio = $box_x / $image_width;
            } else {
                $ratio = $box_y / $image_height;
            }
        } else {
            $ratio = $box_x / $image_width;
        }

        $scale_box_x = $image_width * $ratio;
        $scale_box_y = $image_height * $ratio;

        $scaleBox = new Box($scale_box_x, $scale_box_y);

        $imagine = new Imagine();
        $image = $imagine
                    ->open($file_path)
                    ->resize($scaleBox)
                    ->save($file_path);
    }

    public static function resizeImage(string $file_path, int $box_x, int $box_y) 
    {
        $image_info = getImageSize($file_path);
        $image_width = $image_info[0];
        $image_height = $image_info[1];
 
        $scale_box_x = 0;
        $scale_box_y = 0;

        $ratio = 1;

        if ($image_width >= $image_height) {
            if ($box_x - $image_width > $box_y - $image_height) {
                $ratio = $box_x / $image_width;
            } else {
                $ratio = $box_y / $image_height;
            }
        } else {
            $ratio = $box_x / $image_width;
        }

        $scale_box_x = $image_width * $ratio;
        $scale_box_y = $image_height * $ratio;

        $scaleBox = new Box($scale_box_x, $scale_box_y);

        $imagine = new Imagine();
        $image = $imagine
                    ->open($file_path)
                    ->resize($scaleBox)
                    ->save($file_path);


        return $file_path;
    }

    private static function processThumb(string $file_path, int $box_x, int $box_y)
    {

        $imagineThumb = new Imagine();
        $palette = new \Imagine\Image\Palette\RGB();
        $thumb = $imagineThumb->create(new Box($box_x, $box_y), $palette->color('#fff'));

        $imagine = new Imagine();
        $image = $imagine
                    ->open($file_path)
                    ->thumbnail(new Box($box_x, $box_y), ManipulatorInterface::THUMBNAIL_OUTBOUND);


        $thumb->paste($image, new Point(0, 0));
        $thumb->save($file_path);

        return $file_path;
    }
}