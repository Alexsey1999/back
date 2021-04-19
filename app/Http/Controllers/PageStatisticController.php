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
use App\Formatters\HttpDataSanitizer;

use Error;
use Exception;
use Throwable;
use App\Exceptions\MongoDB\InvalidObjectIdException;
use App\Exceptions\Pages\PageAccessDeniedException;
use App\Exceptions\Pages\PageMetaUpdateException;
use App\Exceptions\Pages\PageNotFoundException;
use App\Exceptions\Pages\PageDeleteException;

class PageStatisticController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private PageStatisticService $pageStatisticService;

    public function __construct(
        PageStatisticService $pageStatisticService
    )
    {
        $this->pageStatisticService = $pageStatisticService;
    }

    /**
     * @TODO - провалидировать и очистить входящие данные во всех запросах
     */

    /**
     * Сохранение нового просмотра
     */
    public function hit(Request $request): JsonResponse
    {

        $hit_data = $request->input('data');

        if (!$hit_data) {
            throw new BadRequestHttpException('Hit data not provided');
        }

        $hit_data = json_decode($hit_data, true);

        try {
            $res = $this->pageStatisticService->saveHit($hit_data);
            return $this->jsonResponse([
                'success' => $res
            ]);
        } catch (\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Сохранение достижения цели
     */
    public function goal(Request $request): JsonResponse
    {

        $goal_data = $request->input('data');

        if (!$goal_data) {
            throw new BadRequestHttpException('Goal data not provided');
        }

        $goal_data = json_decode($goal_data, true);

        
        try {
            $res = $this->pageStatisticService->saveGoal($goal_data);
            return $this->jsonResponse([
                'success' => $res
            ]);
        } catch (\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

    }

    public function get(Request $request): JsonResponse
    {

        $request_data = $request->input('data');

        if (!$request_data) {
            throw new BadRequestHttpException('Некорректные данные');
        }

        $request_data = json_decode($request_data, true);

        if (!isset($request_data['page_id'])) {
            throw new BadRequestHttpException('Страница не задана');
        }

        try {

            $res = $this->pageStatisticService->getSummary([ (string) $request_data['page_id'] ]);

            $data = [];

            if (isset($res[$request_data['page_id']])) {
                $data = $res[$request_data['page_id']];
            }

            return $this->jsonResponse([
                'success' => true,
                'data' => $data,
                'test' => $request_data
            ]);
        } catch (\Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }


    
}
