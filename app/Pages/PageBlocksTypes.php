<?php

namespace App\Pages;

class PageBlocksTypes {

    /**
     * 
     */
    const COVER_BASE = [
        'key' => 'c1',
        'type' => 'cover',
        'sub_type' => 'cover_base',
        'title' => 'Обложка'
    ];

    /**
     * 
     */
    const BUTTON_BASE = [
        'key' => 'b1',
        'type' => 'button',
        'sub_type' => 'button_base',
        'title' => 'Кнопка'
    ];

    /**
     * 
     */
    const TEXT_BASE = [
        'key' => 't1',
        'type' => 'text',
        'sub_type' => 'text_base',
        'title' => 'Текст'
    ];

    /**
     * 
     */
    const ADVANTAGES_BASE = [
        'key' => 'a1',
        'type' => 'advantages',
        'sub_type' => 'advantages_base',
        'title' => 'Преимущества'
    ];

    /**
     * 
     */
    const PRODUCTS_BASE = [
        'key' => 'p1',
        'type' => 'products',
        'sub_type' => 'products_base',
        'title' => 'Товары'
    ];

    /**
     * 
     */
    const IMAGE_BASE = [
        'key' => 'i1',
        'type' => 'image',
        'sub_type' => 'image_base',
        'title' => 'Галерея изображений'
    ];

    /**
     * 
     */
    const IMAGE_SINGLE = [
        'key' => 'i2',
        'type' => 'image',
        'sub_type' => 'image_single',
        'title' => 'Баннер'
    ];

    /**
     * 
     */
    const TIMER_BASE = [
        'key' => 'tm1',
        'type' => 'timer',
        'sub_type' => 'timer_base',
        'title' => 'Таймер'
    ];

    /**
     * 
     */
    const VIDEO_BASE = [
        'key' => 'v1',
        'type' => 'video',
        'sub_type' => 'video_base',
        'title' => 'Видео'
    ];

    /**
     * 
     */
    const OTHER_SEPARATOR = [
        'key' => 'ot1',
        'type' => 'other',
        'sub_type' => 'other_separator',
        'title' => 'Разделитель'
    ];

    /**
     * 
     */
    const REVIEWS_BASE = [
        'key' => 'r1',
        'type' => 'reviews',
        'sub_type' => 'reviews_base',
        'title' => 'Отзывы'
    ];


    public static function getList(): array
    {
        return [
            self::ADVANTAGES_BASE,
            self::BUTTON_BASE,
            self::COVER_BASE,
            self::IMAGE_BASE,
            self::IMAGE_SINGLE,
            self::OTHER_SEPARATOR,
            self::PRODUCTS_BASE,
            self::TEXT_BASE,
            self::TIMER_BASE,
            self::VIDEO_BASE,
            self::REVIEWS_BASE
        ];
    }

    public static function getListByKey(): array
    {
        $list = self::getList();
        $result = [];

        foreach($list as $index => $type) { 
            $result[$type['key']] = $type;
        }

        return $result;
    }

}