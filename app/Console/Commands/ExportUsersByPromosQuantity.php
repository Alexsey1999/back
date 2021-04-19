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

use Exception;
use Illuminate\Console\Command;
use App\Interfaces\Pages\PageStatisticRepositoryInterface;
use App\Interfaces\Pages\PagesRepositoryInterface;
use DateTime;
use App\Workers\VkApi;

/**
 * Экспорт пользователей по максимальному количеству промостраниц на пользователя
 * 
 * Class ExportUsersByPromosQuantity
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class ExportUsersByPromosQuantity extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "users:get-by-promos-quantity";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Get the top 30 users by the number of promo pages created";

    private $pagesStatisticRepository;
    private $pagesRepository;
    private $vkApi;

    public function __construct(
        PageStatisticRepositoryInterface $pagesStatisticRepository,
        PagesRepositoryInterface $pagesRepository,
        VkApi $vkApi
    )
    {
        parent::__construct();
        $this->pagesStatisticRepository = $pagesStatisticRepository;
        $this->pagesRepository = $pagesRepository;
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
            $time_start = time();
            $usersByQuantity = $this->pagesRepository->getCountByUserReport();
            $time_end = time();
            $s = $time_end - $time_start;

            echo "[x] UsersByPromos: aggregation_took: $s s." . PHP_EOL;

            $this->createUsersByQuantityReport($usersByQuantity);
        } catch (\Exception $e) {
            var_dump($e);
        }

    }

    private function createUsersByQuantityReport(array $data): void
    {
        $current_date = (new DateTime())->format('Y-m-d');
        $file_name = 'users_by_quantity_' . $current_date . '.csv';
        $directory = base_path() . '/tmp';
        $file_path = $directory . DIRECTORY_SEPARATOR . $file_name;

        if (file_exists($file_path)) {
            unlink($file_path);
        }

        $head = [
            'vk_url', // Ссылка на пользователя в ВК
            'quantity' // Количество промо-страниц
        ];

        file_put_contents($file_path, implode(';', $head) . PHP_EOL, FILE_APPEND);

        foreach($data as $user) {
            $row = implode(';', [
                'https://vk.com/id' . $user['vk_user_id'],
                $user['count']    
            ]);
            file_put_contents($file_path, $row . PHP_EOL, FILE_APPEND);
        }

        $this->send($file_path);

        unlink($file_path);

        echo '[x] Process end' . PHP_EOL;
    }

    private function send($file_path) 
    {

        $this->vkApi->setGroupToken('0958756a16a1ee70968b96c3e70470fc4794deab897c30354fc2ae22da27cab2a8415dcc1f3c27093b6b5');
        
        $doc = $this->vkApi->uploadMessagesAttachment($file_path, 4871362);
        
        $resp = $this->vkApi->sendMessage(
            4871362,
            "Автоматическое уведомление\r\n\r\nВыгрузка пользователей проведена успешно - максимальное количество лендингов в аккаунте ТОП 30", 
            ["doc{$doc['owner_id']}_{$doc['id']}"]
        );

        if (isset($resp['response'])) {
            echo '[x] Report message sent' . PHP_EOL;
        } else {
            var_dump('Error', $resp);
        }

    }
}