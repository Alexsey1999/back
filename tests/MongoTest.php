<?php

use App\Repositories\WidgetsRepository;
use App\Repositories\GuideRepository;
use App\Interfaces\DbInterface;


class MongoTest extends TestCase
{

    protected $widgetsRepository;
    protected $guideRepository;
    protected $db;

    public function setUp(): void
    {
        parent::setUp();

        $this->widgetsRepository = $this->app->make(WidgetsRepository::class);
        $this->db = $this->app->make(DbInterface::class);
        $this->guideRepository = $this->app->make(GuideRepository::class);
    }

    /**
     * @group databaseConnectionCache
     */
    public function testDatabaseConnectionCache()
    {
        $connection1 = $this->db->getConnection();
        $connection2 = $this->db->getConnection();
        $this->assertEquals($connection1, $connection2);
    }

    /**
     * @group mongoCollectionCache
     */
    public function testMongoWidgetCollectionCache()
    {
        $wCollection1 = $this->widgetsRepository->getCollection();
        $wCollection2 = $this->widgetsRepository->getCollection();
        $this->assertEquals($wCollection1, $wCollection2);

        $guideCollection1 = $this->guideRepository->getCollection();
        $guideCollection2 = $this->guideRepository->getCollection();
        $this->assertEquals($guideCollection1, $guideCollection2);

        // $sharedCollection1 = Shared::getDocCollection();
        // $sharedCollection2 = Shared::getDocCollection();
        // $this->assertEquals($sharedCollection1, $sharedCollection2);
    }
}