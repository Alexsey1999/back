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
 *  
 * Экспорт тех пользователей, кто пользуется промо-страницами
 * - На страницах есть просмотры за последние 30 дней
 * - На страницах есть достижения целевых действий за последние 30 дней
 * 
 * Class ExportActivePromosUsers
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class ExportActivePromosUsers extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "users:get-active-promos-users";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Get users who created and use landing pages";

    private $pagesStatisticRepository;
    private $pagesRepository;
    private $vkApi;

    private $report_file_path;

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
        // Получим топ 500 самых активных сообществ по просмотрам промо-страниц
        $topCommunitiesByHits = $this->pagesStatisticRepository->getMostActiveCommunitiesByHits([
            'size' => 500
        ]);

        echo "[x] ActivePromosUsers: get top communities by hist" . PHP_EOL;

        // Получим топ 500 самых активных сообществ по достижению целей на промостраницах
        $topCommunitiesByGoals = $this->pagesStatisticRepository->getMostActiveCommunitiesByGoals([
            'size' => 500
        ]);

        echo "[x] ActivePromosUsers: get top communities by goals" . PHP_EOL;

        // Возьмем только уникальные id
        $unique_community_ids = array_unique(array_merge($topCommunitiesByHits, $topCommunitiesByGoals));
        $c = count($unique_community_ids);

        echo "[x] ActivePromosUsers: unique community_ids count - $c" . PHP_EOL;

        $user_ids_cache = [];
        
        // Для каждого сообщества получим список страниц
        foreach($unique_community_ids as $community_id) {
            $community_pages = $this->pagesRepository->getGroupPagesList($community_id);

            // Если страницы есть - возьмем первую и используем ее vk_user_id
            if (isset($community_pages[0])) {
                
                if (!in_array($community_pages[0]['vk_user_id'], $user_ids_cache)) {
                    $this->addUser($community_pages[0]['vk_user_id']);
                    $user_ids_cache[] = $community_pages[0]['vk_user_id'];
                }
                
                echo '[x] Process community - ' . $community_id . PHP_EOL;
            }

            usleep(10000);

        }

        // Отправим сформированный файл
        $this->send();

        // Удалим файл из верменной директории
        unlink($this->report_file_path);

        echo '[x] Process end' . PHP_EOL;

    }

    private function addUser($vk_user_id) 
    {
        file_put_contents($this->report_file_path, $vk_user_id . PHP_EOL, FILE_APPEND);
    }

    private function send() 
    {
        
        $this->vkApi->setGroupToken('0958756a16a1ee70968b96c3e70470fc4794deab897c30354fc2ae22da27cab2a8415dcc1f3c27093b6b5');
        
        $doc = $this->vkApi->uploadMessagesAttachment($this->report_file_path, 4871362);
        
        $resp = $this->vkApi->sendMessage(
            4871362,
            "Автоматическое уведомление\r\n\r\nВыгрузка пользователей проведена успешно - пользователи, которые создали лендинг и пользуются им", 
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
        $file_name = 'active_promo_users_' . $current_date . '.txt';
        $directory = base_path() . '/tmp';
        $file_path = $directory . DIRECTORY_SEPARATOR . $file_name;

        return $file_path;
    }
}