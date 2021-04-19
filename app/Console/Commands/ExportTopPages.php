<?php
/**
 *
 * PHP version >= 7.0
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */

namespace App\Console\Commands;


use App\Post;

use App\Pages\PageBlocksTypes;
use Exception;
use Illuminate\Console\Command;
use App\Interfaces\Pages\PageStatisticRepositoryInterface;
use App\Services\PageService;
use DateTime;
use App\Workers\VkApi;

/**
 *  
 * Эскпорт самых активных страниц
 * - По количеству просмотров
 * - По количеству достигнутых целей
 * 
 * Class ExportTopPages
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class ExportTopPages extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "pages:export-top-pages";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Export top pages statistic";

    private $pagesStatisticRepository;
    private $pagesService;
    private $blocksPublishedRepository;
    private $vkApi;

    private $report_file_path;

    public function __construct(
        PageStatisticRepositoryInterface $pagesStatisticRepository,
        PageService $pagesService,
        VkApi $vkApi
    )
    {
        parent::__construct();
        $this->pagesStatisticRepository = $pagesStatisticRepository;
        $this->pagesService = $pagesService;
        $this->vkApi = $vkApi;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {

            $this->report_file_path = $this->getFilePath();

            if (file_exists($this->report_file_path)) {
                unlink($this->report_file_path);
            }

            $this->start();

        } catch (\Exception $e) {
            var_dump($e);
        }
    }

    private function start(): void 
    {
        
        $top_pages_by_views_ids = $this->pagesStatisticRepository->getMostActivePagesByViews([
            'limit' => 100
        ]);

        fwrite(STDOUT, '[x] Get top viewed pages ELK aggregation data' . PHP_EOL);

        $top_viewd_pages_collection = $this->pagesService->find([
            '_id' => [
                '$in' => array_keys($top_pages_by_views_ids)
            ]
        ]);

        fwrite(STDOUT, '[x] Get collection from MongoDB' . PHP_EOL);

        $res = [
            ['name', 'url', 'count']
        ];

        $res[] = [
            'Most viewed', '', ''
        ];

        $vp = [];

        foreach($top_viewd_pages_collection as $page) {
            $vp[] = [
                $page->getName(),
                'https://vk.com/app5898182_-' . $page->getVkGroupId() . '#page=' . $page->getId(),
                $top_pages_by_views_ids[$page->getId()]
            ];
        }

        usort($vp, function ($vpi1, $vpi2) {
            if ($vpi1[2] == $vpi2[2]) {
                return 0;
            }
            return ($vpi1[2] > $vpi2[2]) ? -1 : 1;
        });

        foreach($vp as $vp_item) {
            $res[] = $vp_item;
        }


        $top_pages_by_goals_ids = $this->pagesStatisticRepository->getMostActivePagesByGoals([
            'limit' => 100
        ]);

        fwrite(STDOUT, '[x] Get top goals pages ELK aggregation data' . PHP_EOL);

        $top_goals_pages_collection = $this->pagesService->find([
            '_id' => [
                '$in' => array_keys($top_pages_by_goals_ids)
            ]
        ]);

        fwrite(STDOUT, '[x] Get collection from MongoDB' . PHP_EOL);
                
        $gp = [];

        foreach($top_goals_pages_collection as $page) {
            $gp[] = [
                $page->getName(),
                'https://vk.com/app5898182_-' . $page->getVkGroupId() . '#page=' . $page->getId(),
                $top_pages_by_goals_ids[$page->getId()]
            ];
        }

        usort($gp, function ($gpi1, $gpi2) {
            if ($gpi1[2] == $gpi2[2]) {
                return 0;
            }
            return ($gpi1[2] > $gpi2[2]) ? -1 : 1;
        });

        $res[] = [
            '-', '-', '-'
        ];
        $res[] = [
            'Most goals', '', ''
        ];

        foreach($gp as $gp_item) {
            $res[] = $gp_item;
        }

        $this->createReport($res);

        $this->send();

        fwrite(STDOUT, '[x] Process end' . PHP_EOL);

    }

    private function createReport(array $data)
    {
        file_put_contents($this->report_file_path, implode(';', ['f1', 'f2', 'f3'])  . PHP_EOL, FILE_APPEND);
        foreach ($data as $index => $row) {
            file_put_contents($this->report_file_path, implode(';', $row)  . PHP_EOL, FILE_APPEND);
        }
    }

    private function send() 
    {
        
        $this->vkApi->setGroupToken('5bd9a169f7b271cc353dd4f4a092219a638c949d1545705dc461222b6c604492c1fa9428f8b3cfc6f2a95');
        
        $doc = $this->vkApi->uploadMessagesAttachment($this->report_file_path, 4871362);
        
        $resp = $this->vkApi->sendMessage(
            4871362,
            "Автоматическое уведомление\r\n\r\nВыгрузка статистики выполнена успешно - статистика по страницам", 
            ["doc{$doc['owner_id']}_{$doc['id']}"]
        );

        if (isset($resp['response'])) {

        } else {
            var_dump('Error', $resp);
        }
        
    }

    private function getFilePath(): string
    {
        $current_date = (new DateTime())->format('Y-m-d');
        $file_name = 'top_pages_' . $current_date . '.csv';
        $directory = base_path() . '/tmp';
        $file_path = $directory . DIRECTORY_SEPARATOR . $file_name;

        return $file_path;
    }
}