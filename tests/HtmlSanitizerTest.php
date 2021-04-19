<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

use App\Formatters\HttpDataSanitizer;

class HttpDataSanitizerTest extends TestCase
{

    /**
     * @group testSanitizeBlockDataCorners
     */
    public function testSanitizeBlockDataCorners()
    {
        $res = (new HttpDataSanitizer())->clearBlockData([]);

        $this->assertEquals([], $res);
    }

    /**
     * @group testSanitizeBlockData
     */
    public function testSanitizeBlockData()
    {
        $res1 = (new HttpDataSanitizer())->clearBlockData([
            'content' => [
                'title' => '\'"><img src=x onerror=alert();>',
                'text' => '<script>alert("test")</script>'
            ]
        ]);

        $this->assertEquals([
            'content' => [
                'title' => '\'">',
                'text'  => 'alert("test")'
            ]
        ], $res1);


        $res2 = (new HttpDataSanitizer())->clearBlockData([
            'background' => [
                'url' => '\'"><img src=x onerror=alert(document.cookie);>',
            ]
        ]);

        $this->assertEquals([
            'background' => [
                'url' => '\'">'
            ]
        ], $res2);


        $res3 = (new HttpDataSanitizer())->clearBlockData([
            'video' => [
                'autoplay' => '\'"><img src=x onerror=alert(document.cookie);>',
                'url' => 'http://site.ru/catalog?p= "><script>alert("cookie: "+document.cookie)',
                'disable_audio' => '1',
                'repeat' => '1'
            ]
        ]);

        $this->assertEquals([
            'video' => [
                'autoplay' => 0, // Преобразуется к int
                'url' => 'http://site.ru/catalog?p= ">alert("cookie: "+document.cookie)',
                'disable_audio' => 1,
                'repeat' => 1
            ]
        ], $res3);

        $res4 = (new HttpDataSanitizer())->clearBlockData([
            'vk_group_id' => '\'"><img src=x onerror=alert(document.cookie);>'
        ]);

        $this->assertEquals([
            'vk_group_id' => 0
        ], $res4);


        $res5 = (new HttpDataSanitizer())->clearBlockData([
            'button' => [
                'text' => '\'"><script>alert("test")</script>',
                'url' => "http://www.site.com/page.php?var=<script>alert('xss');</script>",
                'action' => 'url'
            ]
        ]);

        $this->assertEquals([
            'button' => [
                'text' => '\'">alert("test")',
                'url' => "http://www.site.com/page.php?var=alert('xss');",
                'action' => 'url'
            ]
        ], $res5);

        $res6 = (new HttpDataSanitizer())->clearBlockData([
            'items' => [
                0 => [
                    'url' => "http://www.site.com/page.php?var=<script>alert('xss');</script>",
                    'title' => 'Товар 1',
                    'text' => 'Описание'
                ], 
                1 => [
                    'url' => 'http://site.com',
                    'title' => '\'"><img src=x onerror=alert(document.cookie);>',
                    'text' => 'Описание'
                ],
                2 => [
                    'img' => [
                        'url' => '\'"><img src=x onerror=alert(document.cookie);>' 
                    ],
                    'name' => '\'"><img src=x onerror=alert(document.cookie);>',
                    'text' => 'Описание'
                ],
                3 => [
                    'button' => [
                        'text' => 'Text',
                        'url' => "http://www.site.com/page.php?var=<script>alert('xss');</script>"
                    ],
                    'name' => '\'"><img src=x onerror=alert(document.cookie);>',
                    'text' => 'Описание'
                ],
            ]
        ]);

        $this->assertEquals($res6, [
            'items' => [
                0 => [
                    'url' => "http://www.site.com/page.php?var=alert('xss');",
                    'title' => 'Товар 1',
                    'text' => 'Описание'
                ], 
                1 => [
                    'url' => 'http://site.com',
                    'title' => '\'">',
                    'text' => 'Описание'
                ],
                2 => [
                    'img' => [
                        'url' => '\'">' 
                    ],
                    'name' => '\'">',
                    'text' => 'Описание'
                ],
                3 => [
                    'button' => [
                        'text' => 'Text',
                        'url' => "http://www.site.com/page.php?var=alert('xss');"
                    ],
                    'name' => '\'">',
                    'text' => 'Описание'
                ],
            ]
        ]);
    }
}