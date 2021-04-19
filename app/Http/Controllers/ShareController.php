<?php

namespace App\Http\Controllers;

use App\Services\SharedService;

use Error;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ShareController extends Controller
{

    private $sharedService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
    }

    public function createCollection(Request $request): JsonResponse 
    {

        $params = json_decode($request->input('params'), true);
        $widget_ids = $request->input('widget_ids');

        if (!$widget_ids || !is_array($widget_ids) || count($widget_ids) <= 0) {
            throw new BadRequestHttpException('Invalid widget ids or widget ids not provided');
        }

        if (!isset($params['vk_group_id'])) {
            throw new BadRequestHttpException('Group id not provided');
        }

        if (!isset($params['vk_user_id'])) {
            throw new BadRequestHttpException('User id not provided');
        }

        try {
            $group_id = (int) $params['vk_group_id'];
            $vk_user_id = (int) $params['vk_user_id'];
            
            $res = $this->sharedService->createCollection([
                'widget_ids' => $widget_ids,
                'vk_group_id' => $group_id,
                'vk_user_id' => $vk_user_id
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $res,
                'errors' => []
            ]);

        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'result' => 'error',
                'data' => null,
                'errors' => [
                    $e->getMessage()
                ]
            ]);
        }
    }

    public function getCollection(Request $request): JsonResponse 
    {

        $collection_id = (int)$request->input('collection_id');
        $params = json_decode($request->input('params'), true);

        if (!$collection_id) {
            throw new BadRequestHttpException('Неверные параметры запроса - отсутствует id коллекции');
        }

        if (!isset($params['vk_group_id'])) {
            throw new BadRequestHttpException('Неверные параметры запроса - отсутствует id сообщества');
        }

        try {

            $res = $this->sharedService->getCollection($collection_id, $params);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $res,
                'errors' => []
            ]);
        } catch (Throwable $e) {
            return $this->jsonResponse([
                'result' => 'error',
                'data' => [],
                'errors' => [
                    $e->getMessage()
                ]
            ]);
        }
    }

    public function copyCollection(Request $request): JsonResponse
    {

        $collection_id = (int) $request->input('collection_id');
        $target_vk_group_id = (int) $request->input('target_vk_group_id');
        $params = json_decode($request->input('params'), true);

        if (!isset($params['vk_group_id'])) {
            throw new BadRequestHttpException('Неверные параметры запроса - отсутствует id сообщества');
        }

        if (!$collection_id) {
            throw new BadRequestHttpException('Не передан id коллекции');
        }

        if (!$target_vk_group_id) {
            throw new BadRequestHttpException('Не передан id сообщества');
        }

        try {

            $errors = $this->sharedService->copyCollection($collection_id, $target_vk_group_id, $params);
            return $this->jsonResponse([
                'result' => 'success',
                'data' => null,
                'errors' => $errors
            ]);
        } catch (Throwable $e) {
            throw $e;
        }
    }
}