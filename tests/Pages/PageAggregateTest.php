<?php

use App\Pages\Page;
use App\Pages\PagesFactory;

class PageAggregateTest extends TestCase
{

    private $pagesFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->pagesFactory = $this->app->make(PagesFactory::class);

    }

    /**
     * @group pageCreate
     */
    public function testPageCreate()
    {
        $page = $this->pagesFactory->create('Test page', 12345, 123456);
        $this->assertEquals(true, $page instanceof Page);
    }
}
