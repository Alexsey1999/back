<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Interfaces\LoggerInterface;
use App\Services\PageService;
use App\Services\PageStatisticService;
use App\Services\SubscriptionsService;
use App\Formatters\HttpDataSanitizer;
use App\Formatters\PageVariableReplacer;
use App\Interfaces\LeadServiceInterface;
use App\Workers\VkApi;

use Error;
use Exception;
use Throwable;
use App\Exceptions\MongoDB\InvalidObjectIdException;
use App\Exceptions\Pages\PageAccessDeniedException;
use App\Exceptions\Pages\PageMetaUpdateException;
use App\Exceptions\Pages\PageNotFoundException;
use App\Exceptions\Pages\PageMaxCountException;
use App\Exceptions\Pages\UserIsNotGroupEditorException;
use App\Exceptions\VkApi\VkApiErrorResponseException;
use App\Services\Profiler;

class PagesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private PageService $pageService;
    private LoggerInterface $logger;
    private HttpDataSanitizer $sanitizer;
    private PageStatisticService $pageStatisticService;
    private SubscriptionsService $subscriptionsService;
    private LeadServiceInterface $leadService;
    private PageVariableReplacer $pageVariableReplacer;
    private Profiler $profiler;
    private VkApi $vkApi;

    public function __construct(
        LoggerInterface $logger,
        PageService $pageService,
        HttpDataSanitizer $sanitizer,
        PageStatisticService $pageStatisticService,
        SubscriptionsService $subscriptionsService,
        LeadServiceInterface $leadService,
        PageVariableReplacer $pageVariableReplacer,
        Profiler $profiler,
        VkApi $vkApi
    )
    {
        $this->logger = $logger;
        $this->pageService = $pageService;
        $this->sanitizer = $sanitizer;
        $this->pageStatisticService = $pageStatisticService;
        $this->subscriptionsService = $subscriptionsService;
        $this->leadService = $leadService;
        $this->pageVariableReplacer = $pageVariableReplacer;
        $this->profiler = $profiler;
        $this->vkApi = $vkApi;
    }

    /**
     * - Получает данные для одной страницы
     * - Записывает хит
     * - Получает данные сообщества с основного хоста
     */
    public function get(Request $request): JsonResponse
    {
        $this->profiler->start('processing_incoming_data');

        $id = (string)strip_tags($request->input('id'));
        $id = preg_replace('/[^a-z0-9].*/', '', $id); // удаляем лишние символы

        if (!$id) {
            throw new BadRequestHttpException('Отсутствует ID');
        }

        $params = $request->input('params');

        if (!$params) {
            throw new BadRequestHttpException('Отсутствуют обязательные параметры');
        }

        try {

            $params = json_decode($params, true);

            $this->profiler->end('processing_incoming_data');

            $this->profiler->start('get_page_data');
            // Получим данные по промо-странице
            $page = $this->pageService->getOneProd($id, (int)$params['vk_group_id']);

            $this->profiler->end('get_page_data');

            $this->profiler->start('save_hit');

            $hit_data = [
                'vk_group_id' => $params['vk_group_id'],
                'vk_user_id' => $params['vk_user_id'],
                'vk_user_role' => $params["vk_viewer_group_role"],
                'vk_ref' => $params["vk_ref"],
                'vk_platform' => $params["vk_platform"],
                'page_id' => $params["page"],
                'time' => time()
            ];

            $hit_id = md5(json_encode($hit_data));
            $hit_data['hit_id'] = $hit_id;

            $this->profiler->end('save_hit');

            $this->profiler->start('get_group_data');

            $page_data = $page->toArrayProd();

            // Отфильтруем все группы подписчиков, используемые на странице
            $subscription_ids = $page->getSubscriptionIds();

            $group_data = null;

            // Получим данные по сообществу и группы подписчиков с основного хоста
            try {
                $group_data = $this->subscriptionsService->getPromoPageData([
                    "subscription_ids" => $subscription_ids,
                    "params" => $params,
                    "vk_group_id" => $params['vk_group_id'],
                    "vk_user_id" => $params['vk_user_id']

                ]);
            } catch (\Throwable $e) {
                $this->logger->save([
                    'vk_group_id' => $params['vk_group_id'],
                    'data' => json_encode([
                        'file' => 'PagesController.php',
                        'error_type' => 'get_group_data_error',
                        'message' => $e->getMessage(),
                        'code' => $e->getCode()
                    ]),
                    'params' => json_encode($params)
                ]);
            }


            if ($group_data && isset($group_data['viewer'])) {
                $this->pageVariableReplacer->setVkUserData($group_data['viewer']);
            }

            $this->profiler->end('get_group_data');

            $this->profiler->start('replace_vars');

            $page_data['blocks'] = $this->pageVariableReplacer->replace($page_data['blocks']);

            $this->profiler->end('replace_vars');

            return $this->jsonResponse([
                'result' => 'success',
                'page_data' => $page_data,
                'group_data' => $group_data,
                'hit_data' => $hit_data,
                // 'timings' => $this->profiler->getReport()
            ]);

        } catch(InvalidObjectIdException $e) {
            throw new BadRequestHttpException('Некорректный ID');
        } catch (PageAccessDeniedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (PageNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage(), 500);
        }

    }

    public function create(Request $request): JsonResponse
    {

        $name = (string)strip_tags($request->input('name'));

        if (!$name) {
            throw new BadRequestHttpException('Отсутствует наименование станицы');
        }

        $params = json_decode($request->input('params'), true);

        $data = [
            'name' => $name,
            'vk_user_id' => (int)$params['vk_user_id'],
            'vk_group_id' => (int)$params['vk_group_id']
        ];

        $newPage = $this->pageService->createPage($data);

        return $this->jsonResponse([
            'result' => 'success',
            'data' => $newPage->toArray()
        ]);
    }

    /**
     * Получение всех данных по страницам
     * Для редактирования внутри приложения
     */
    public function getAll(Request $request): JsonResponse
    {
        try {
            $params = json_decode($request->input('params'), true);

            $pages = $this->pageService->getAll((int)$params['vk_group_id']);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => [
                    'pages' => $pages->toArray(),
                    'recent_blocks' => $this->pageService->getRecentBlocks((int)$params['vk_group_id']),
                    'max_items_counters' => $this->pageService->getBlocksItemsMaxCounters()
                ]
            ]);

        } catch(InvalidObjectIdException $e) {
            throw new BadRequestHttpException('Некорректный ID');
        } catch (PageAccessDeniedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage(), 500);
        }

    }

    /**
     * Получение списка лендингов для селектов
     * Без лишних данных и операций
     * В формате
     * [
     *  'id' => string,
     *  'name' => string
     * ]
     */
    public function getList(Request $request): JsonResponse
    {
        try {
            $params = json_decode($request->input('params'), true);

            $pagesList = $this->pageService->getList((int)$params['vk_group_id']);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $pagesList
            ]);

        } catch(InvalidObjectIdException $e) {
            throw new BadRequestHttpException('Некорректный ID');
        } catch (PageAccessDeniedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage(), 500);
        }
    }

    public function changeStatus(Request $request): JsonResponse
    {
        $params = json_decode($request->input('params'), true);
        $ids = $request->input('ids');
        $status = (int)$request->input('status');

        if (!$ids || empty($ids)) {
            throw new BadRequestHttpException('Отсутствует обязательный параметр - ID');
        }

        if (!$status) {
            throw new BadRequestHttpException('Отсутствует обязательный параметр - Статус');
        }

        if (!$this->pageService->isValidStatus($status)) {
            throw new BadRequestHttpException('Некорретный обязательный параметр - Статус');
        }

        try {

            $ids = $this->sanitizer->arrayStringValues($ids);

            $res = $this->pageService->changePagesStatus($ids, $status, [
                'vk_group_id' => (int)$params['vk_group_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'pages' => $res
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'result' => 'error',
                'message' => 'Ошибка при изменении статуса лендинга'
            ]);
        }
    }

    public function rename(Request $request): JsonResponse
    {
        $id = (string)strip_tags($request->input('id'));

        if (!$id) {
            throw new BadRequestHttpException('Отсутствует ID');
        }

        $name = (string)strip_tags($request->input('name'));

        if (!$name) {
            throw new BadRequestHttpException('Отсутствует наименование станицы');
        }

        $params = json_decode($request->input('params'), true);

        try {

            $updatedPage = $this->pageService->rename($id, [
                'name' => $name,
                'vk_group_id' => (int)$params['vk_group_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $updatedPage->toArray()
            ]);

        } catch (PageMetaUpdateException $e) {
            throw new BadRequestHttpException('Ошибка при переименовании');
        }  catch (PageNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), 500);
        }
    }

    public function delete(Request $request): JsonResponse
    {

        $ids = $request->input('ids');

        if (!$ids || empty($ids)) {
            throw new BadRequestHttpException('Отсутствует обязательный параметр - ID');
        }

        $params = json_decode($request->input('params'), true);

        try {

            $res = $this->pageService->deletePages($ids, [
                'vk_group_id' => (int)$params['vk_group_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $res
            ]);

        } catch(InvalidObjectIdException $e) {
            throw new BadRequestHttpException('Некорректный ID ' . $e->getMessage());
        } catch (PageAccessDeniedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage(), 500);
        }
    }

    /**
     * Операции вставки и добавления нового блока разделены,
     * так как иначе получается слишком много логики для одного экшэна
     * если вставлять блок между какими то определенными блоками - нужно переназначить сортировку у всех блоков
     */

    /**
     * Добавление нового блока для страницы
     */
    public function addBlock(Request $request): JsonResponse
    {

        $page_id = strip_tags($request->input('page_id'));

        if (!$page_id) {
            throw new BadRequestHttpException('Отсутствует ID страницы');
        }

        $block_data = json_decode($request->input('block'), true);

        if (!$block_data) {
            throw new BadRequestHttpException('Отсутствуют данные для блока');
        }

        $params = json_decode($request->input('params'), true);

        try {

            $result = $this->pageService->addBlock($page_id, $block_data, [
                'vk_group_id' => $params['vk_group_id'],
                'vk_user_id' => $params['vk_user_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $result,
                'states' => $this->pageService->getPageStates($page_id),
                'recent_blocks' => $this->pageService->getRecentBlocks((int)$params['vk_group_id'])
            ]);

        } catch(\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Вставка нового блока после другого блока
     */
    public function insertBlock(Request $request): JsonResponse
    {

        $page_id = strip_tags($request->input('page_id'));

        if (!$page_id) {
            throw new BadRequestHttpException('Отсутствует ID страницы');
        }

        $block_insert_after = strip_tags($request->input('block_insert_after'));

        if (!$block_insert_after) {
            throw new BadRequestHttpException('Отсутствует ID блока');
        }

        $block_data = json_decode($request->input('block'), true);

        if (!$block_data) {
            throw new BadRequestHttpException('Отсутствуют данные для блока');
        }

        $params = json_decode($request->input('params'), true);

        try {

            $res = $this->pageService->insertBlock($page_id, $block_data, $block_insert_after, [
                'vk_user_id' => $params['vk_user_id'],
                'vk_group_id' => $params['vk_group_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $res,
                'states' => $this->pageService->getPageStates($page_id),
                'recent_blocks' => $this->pageService->getRecentBlocks((int)$params['vk_group_id'])
            ]);

        } catch (\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Добавление шаблона для страницы
     */
    public function addTemplate(Request $request): JsonResponse
    {

        $page_id = strip_tags($request->input('page_id'));

        if (!$page_id) {
            throw new BadRequestHttpException('Отсутствует ID страницы');
        }

        $blocks_data = json_decode($request->input('blocks'), true);

        if (!$blocks_data) {
            throw new BadRequestHttpException('Отсутствуют данные шаблона');
        }

        $params = json_decode($request->input('params'), true);

        try {

            $this->pageService->addTemplate($page_id, $blocks_data, [
                'vk_group_id' => (int)$params['vk_group_id'],
                'vk_user_id' => (int)$params['vk_user_id']
            ]);

            $page = $this->pageService->getOne($page_id, (int)$params['vk_group_id']);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $page->toArray()
            ]);

        } catch(\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    public function deleteBlock(Request $request): JsonResponse
    {

        $block_id = strip_tags($request->input('block_id'));

        if (!$block_id) {
            throw new BadRequestHttpException('Отсутствует ID блока');
        }

        $page_id = strip_tags($request->input('page_id'));

        if (!$page_id) {
            throw new BadRequestHttpException('Отсутствует ID страницы');
        }

        $params = json_decode($request->input('params'), true);


        try {

            $res = $this->pageService->deleteBlock($block_id, [
                'vk_group_id' => $params['vk_group_id'],
                'page_id' => $page_id
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $res,
                'states' => $this->pageService->getPageStates($page_id)
            ]);

        } catch (\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    public function sortBlocks(Request $request): JsonResponse
    {

        $page_id = strip_tags($request->input('page_id'));

        if (!$page_id) {
            throw new BadRequestHttpException('Отсутствует ID страницы');
        }

        $sort_data = json_decode($request->input('sort_data'), true);

        if (!$sort_data) {
            throw new BadRequestHttpException('Отсутствуют данные для сортировки');
        }

        $sort_data = array_map(function ($item) {
            return [
                'id' => strip_tags($item['id']),
                'sort' => (int)$item['sort']
            ];
        }, $sort_data);

        $params = json_decode($request->input('params'), true);

        try {

            $res = $this->pageService->sortBlocks($page_id, $sort_data, [
                'vk_group_id' => $params['vk_group_id'],
                'vk_user_id' => $params['vk_user_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $res,
                'states' => $this->pageService->getPageStates($page_id)
            ]);
        } catch (\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Редактирование блока
     */
    public function updateBlock(Request $request): JsonResponse
    {

        $block_data = $request->input('block_data');

        if (!$block_data) {
            throw new BadRequestHttpException('Отсутствуют данные блока');
        }

        $block_data = json_decode($block_data, true);
        if (!is_array($block_data)) {
            throw new BadRequestHttpException('Wrong data');
        }

        $params = json_decode($request->input('params'), true);

        /**
         * @TODO - добавить валидацию контента блока после очистки
         * - Корректность ссылок и тд.
         */

        $data = $this->sanitizer->clearBlockData($block_data);

        try {

            $result = $this->pageService->updateBlock($data, $params);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $result,
                'states' => $this->pageService->getPageStates($block_data['page_id'])
            ]);
        } catch (\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Публикация страницы
     */
    public function publish(Request $request): JsonResponse
    {
        $page_id = (string)$request->input('page_id');

        $page_id = strip_tags($page_id);

        if (!$page_id) {
            throw new BadRequestHttpException('Отсутствуют ID страницы');
        }

        $params = json_decode($request->input('params'), true);

        try {

            $result = $this->pageService->publish($page_id, $params);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => [
                    'published_blocks' => $result,
                    'states' => []
                ]
            ]);

        } catch (\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Фиксирование активного состояния при переходе по версиям страницы
     */
    public function saveState(Request $request): JsonResponse
    {
        $page_id = (string)$request->input('page_id');
        $page_id = strip_tags($page_id);

        if (!$page_id) {
            throw new BadRequestHttpException('Отсутствует ID страницы');
        }

        $state_index = (int)$request->input('state_index');

        if (!$state_index) {
            throw new BadRequestHttpException('Отсутствует версия страницы');
        }

        $params = json_decode($request->input('params'), true);

        try {

            $result = $this->pageService->saveState($page_id, $state_index, $params);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $result,
                'states' => $this->pageService->getPageStates($page_id)
            ]);

        } catch (\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Копирование страницы
     */
    public function copy(Request $request): JsonResponse
    {

        $page_id = (string)$request->input('page_id');
        $page_id = strip_tags($page_id);

        if (!$page_id) {
            throw new BadRequestHttpException('Отсутствует ID страницы');
        }

        $params = json_decode($request->input('params'), true);

        try {

            $page = $this->pageService->copyPage($page_id, $params);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $page->toArray(),
            ]);

        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'result' => 'error',
                'message' => $e->getMessage()
            ]);
        }

    }

    /**
     * Копирование страницы в другую группу
     */
    public function copyToGroup(Request $request): JsonResponse
    {
        $params = json_decode($request->input('params'), true);
        $page_id = strip_tags((string)$request->input('page_id'));
        $token = strip_tags((string)$request->input('token'));
        $target_vk_group_id = intval(strip_tags((string)$request->input('target_vk_group_id')));

        $target_group_pages_count = $this->pageService->countGroupsPages($target_vk_group_id);

        if (!$page_id) {
            throw new BadRequestHttpException('Отсутствует ID страницы');
        }

        if (!$target_vk_group_id) {
            throw new BadRequestHttpException('Отсутствует ID целевой группы');
        }

        if ($target_group_pages_count >= PageService::MAX_GROUP_PAGES_COUNT) {
            throw new BadRequestHttpException('Достигнуто максимальное количество лендингов для сообщества');
        }

        // try {
        //     if (!$this->vkApi->isGroupEditor(intval($params['vk_user_id']), $target_vk_group_id, $token)) {
        //         throw new AccessDeniedHttpException('Доступ запрещен');
        //     }
        // } catch(VkApiErrorResponseException $e) {
        //     throw new BadRequestHttpException($e->getMessage());
        // }

        try {

            $page = $this->pageService->copyPageToGroup($page_id, $target_vk_group_id, $params);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $page->toArray(),
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'result' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Сохрарение заявки от пользователя и перенаправление ее триггеры сообщества на основном сайте
     */
    public function saveLead(Request $request): JsonResponse
    {
        $data = $request->input('data');
        $params = $request->input('params');

        if (!isset($data)) {
            /**
             * @TODO - log error
             */
            throw new BadRequestHttpException('Отсутствуют необходимые данные');
        }

        if (!isset($params)) {
            /**
             * @TODO - log error
             */
            throw new BadRequestHttpException('Отсутствуют необходимые параметры');
        }

        $data = $this->sanitizer->clearLeadData(json_decode($data, true));
        $params = json_decode($params, true);

        try {

            $this->leadService->saveLead($data, $params);

        } catch (\Throwable $e) {
            /**
             * @TODO - log error
             */
            throw $e;
        }

        $data = $this->subscriptionsService->saveLead([
            "data" => $data,
            "params" => $params
        ]);

        return $this->jsonResponse($data);
    }

    /**
     * Добавлние заявки от пользователя в триггеры сообщества на основном сайте
     */
    public function saveLeadTrigger(Request $request): JsonResponse
    {
        $data = $request->input('data');
        $params = $request->input('params');

        if (!isset($data)) {
            /**
             * @TODO - log error
             */
            throw new BadRequestHttpException('Отсутствуют необходимые данные');
        }

        if (!isset($params)) {
            /**
             * @TODO - log error
             */
            throw new BadRequestHttpException('Отсутствуют необходимые параметры');
        }

        $data = $this->sanitizer->clearLeadData(json_decode($data, true));
        $params = json_decode($params, true);

        $data = $this->subscriptionsService->saveLeadTrigger([
            "data" => $data,
            "params" => $params
        ]);

        return $this->jsonResponse($data);
    }
}
