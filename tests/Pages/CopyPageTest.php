<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

use App\Pages\PagesFactory;
use App\Workers\PageBlock\ButtonReplacer;
use App\Workers\PageBlock\ImageReplacer;

class CopyPageTest extends TestCase
{
    /**
     * @group testPageCopy
     * Tests page copy name
     */
    public function testNameHelperMethod()
    {

        $page = (new PagesFactory())->create('Test', 123, 345);

        $this->assertEquals('Test', $page->getName());

        $page->setName($page->getCopyName('Test'));
        $this->assertEquals('Test (копия 1)', $page->getName());

        $page->setName($page->getCopyName($page->getName()));
        $this->assertEquals('Test (копия 2)', $page->getName());


    }

    /**
     * @group testPageCopyCorners
     * Tests page copy name corners
     */
    public function testNameHelperMethodCornerCases()
    {

        $page = (new PagesFactory())->create('Test', 123, 345);

        $page->setName('(копия 1)');

        $this->assertEquals('(копия 1)', $page->getCopyName(''));
        $this->assertEquals('(копия 2)', $page->getCopyName($page->getName()));
        $this->assertEquals('(копия 1) (копия 2)', $page->getCopyName('(копия 1)(копия 1)'));
    }

    /**
     * @group testPageCopyToGroup
     * Tests page copy to other group
     */

    public function testPageCopyToGroupButtonReplacer()
    {

        $target_vk_group_id = 1234;
        $new_button_url = 'https://vk.com/im?sel=-' . $target_vk_group_id;

        $button_replacer = new ButtonReplacer([
            'action' => 'url',
            'send_trigger' => false,
            'lead_admin' => 0,
            'text' => 'Перейти в беседу',
            'url' => $new_button_url,
            'bot_id' => 0,
            'subscription_id' => 0
        ]);

        $new_block = $button_replacer->replace([
            'sub_type' => "cover_base",
            'button' => [
                "action" => "lead",
                "id" => "602531c9e70ac959e75de6b40",
                "text" => "Оставить заявку"
            ]
        ]);

        $this->assertEquals('url', $new_block['button']['action']);
        $this->assertEquals($new_button_url, $new_block['button']['url']);

        $new_block = $button_replacer->replace([
            'sub_type' => "button_base",
            'button' => [
                "action" => "subscribe",
                "id" => "602531c9e70ac959e75de6b40",
                "text" => "Оставить заявку",
                "subscription_id" => 682988
            ]
        ]);

        $this->assertEquals('url', $new_block['button']['action']);
        $this->assertEquals($new_button_url, $new_block['button']['url']);

        $new_block = $button_replacer->replace([
            'sub_type' => "products_base",
            'items' => [
                [
                    'button' => [
                        "action" => "subscribe",
                        "id" => "602531c9e70ac959e75de6b40",
                        "text" => "Оставить заявку",
                        "subscription_id" => 682988
                    ]
                ],
                [
                    'button' => [
                        "action" => "join_community",
                        "id" => "602531c9e70ac959e75de6b40",
                        "text" => "Вступить в сообщество"
                    ]
                ],
            ]
        ]);

        $this->assertEquals('url', $new_block['items'][0]['button']['action']);
        $this->assertEquals($new_button_url, $new_block['items'][0]['button']['url']);

        $this->assertEquals('url', $new_block['items'][1]['button']['action']);
        $this->assertEquals($new_button_url, $new_block['items'][1]['button']['url']);

        $new_block = $button_replacer->replace([
            'sub_type' => "button_base",
            'button' => [
                "action" => "url",
                "id" => "602531c9e70ac959e75de6b40",
                "text" => "Оставить заявку",
                "url" => $new_button_url
            ]
        ]);

        $this->assertEquals('url', $new_block['button']['action']);
        $this->assertEquals($new_button_url, $new_block['button']['url']);

    }

    /**
     * @group testPageCopyToGroup
     * Tests page copy to other group
     */

    public function testPageCopyToGroupImageReplacer()
    {
        $default_image_url = 'https://test.com/image.png';
        $image_replacer = new ImageReplacer('/userapi.com/', $default_image_url);

        $new_block = $image_replacer->replace([
            'sub_type' => 'image_single',
            'content' => [
                'desktop_img' => 'https://s-19.userapi.com/test.png',
                'mobile_img' => 'https://s-19.userapi.com/test2.png'
            ]
        ]);

        $this->assertEquals($default_image_url, $new_block['content']['desktop_img']);
        $this->assertEquals($default_image_url, $new_block['content']['mobile_img']);

        $new_block = $image_replacer->replace([
            'sub_type' => 'cover_base',
            'background' => [
                'url' => 'https://s-19.userapi.com/test.png',
            ]
        ]);

        $this->assertEquals($default_image_url, $new_block['background']['url']);

        $new_block = $image_replacer->replace([
            'sub_type' => 'image_base',
            'items' => [
                [
                    'url' => 'http://userapi.com/image.jpg'
                ],
                [
                    'url' => 'http://userapi.com/image.svg'
                ],
                [
                    'url' => 'https://userapi.ru/t.svg'
                ],
            ]
        ]);

        $this->assertEquals($default_image_url, $new_block['items'][0]['url']);
        $this->assertEquals($default_image_url, $new_block['items'][1]['url']);
        $this->assertEquals('https://userapi.ru/t.svg', $new_block['items'][2]['url']);

        $new_block = $image_replacer->replace([
            'sub_type' => 'products_base',
            'items' => [
                [
                    'img' => [
                        'url' => 'https://test.ru/image.jpeg'
                    ]
                ],
                [
                    'img' => [
                        'url' => 'http://userapi.com/image.jpg'
                    ]
                ]
            ]
        ]);

        $this->assertEquals('https://test.ru/image.jpeg', $new_block['items'][0]['img']['url']);
        $this->assertEquals($default_image_url, $new_block['items'][1]['img']['url']);
    }
}