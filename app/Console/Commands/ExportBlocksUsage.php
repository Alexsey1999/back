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
 * Эскпорт статистики эксплуатации блоков лендингов 
 * - по типам в порядке убывания - сначала самые популярные
 * - среднее количество блоков на страницу общее
 * - Среднее количество блоков на странцу по типу?
 * 
 * Class ExportBlocksUsage
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class ExportBlocksUsage extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "pages:export-blocks-usage";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Export pages blocks usage by type";

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
        
        $types_list = PageBlocksTypes::getListByKey();
        $blocks_usage_data = $this->pagesService->getBlocksUsageStatistic();
        
        fwrite(STDOUT, '[x] Get blocks usage data' . PHP_EOL);

        $published_pages_total_count = $this->pagesService->getPublishedCount();

        fwrite(STDOUT, '[x] Get published pages count' . PHP_EOL);

        $blocks_usage_res = [];
        $blocks_total_count = 0;

        foreach ($blocks_usage_data as $index => $item) {
            $blocks_usage_res[] = [
                $item['_id'],
                $types_list[$item['_id']]['title'],
                $item['count']
            ];

            $blocks_total_count += $item['count'];
        }

        $blocks_usage_res[] = ['-', '-', '-'];
        $blocks_usage_res[] = [
            'Опубликованных страниц',
            '',
            $published_pages_total_count  
        ];

        $blocks_usage_res[] = ['-', '-', '-'];
        $blocks_usage_res[] = [
            'Cр. блоков на стрницу',
            '',
            $blocks_total_count / $published_pages_total_count  
        ];

        $this->createReport($blocks_usage_res);

        fwrite(STDOUT, '[x] Create report' . PHP_EOL);

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
            "Автоматическое уведомление\r\n\r\nВыгрузка статистики выполнена успешно - статистика по использованию блоков", 
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
        $file_name = 'blocks_usage_' . $current_date . '.csv';
        $directory = base_path() . '/tmp';
        $file_path = $directory . DIRECTORY_SEPARATOR . $file_name;

        return $file_path;
    }
}