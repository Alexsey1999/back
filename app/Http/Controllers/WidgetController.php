<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Interfaces\LoggerInterface;
use App\Services\WidgetService;

use Throwable;
use DomainException;

/**
 * Domain exceptions
 */
use App\Exceptions\Widgets\WidgetCreateException;
use App\Exceptions\Widgets\WidgetAccessException;
use App\Exceptions\Widgets\WidgetUpdateException;
use App\Exceptions\Widgets\WidgetDeleteException;
use App\Exceptions\Widgets\WidgetNotFoundException;
use App\Exceptions\Widgets\WidgetGroupOverloadException;

/**
 * Http exceptions
 */
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class WidgetController extends Controller
{
    private $logger;
    private $service;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        LoggerInterface $logger,
        WidgetService $widgetService
    )
    {
        $this->service = $widgetService;
        $this->logger = $logger;
    }


    /**
     * Creates and saves new Widget document
     */
    public function create(Request $request): JsonResponse
    {

        try {

            $data = $request->input();

            return $data;

            $widgetType = (string) $request->input('type');
            $group_id = $request->input('group_id');
            $params = json_decode($request->input('params'), true);

            if ($this->checkAccess($request, $group_id) === false) {
                throw new AccessDeniedHttpException('Access denied');
            }

            if (!isset($widgetType)) {
                throw new BadRequestHttpException('Please specify widget TYPE');
            }

            $validationResult = $this->validateCreateWidgetPostData($request);

            if ($validationResult !== true) {
                return $this->jsonResponse([
                    'result' => 'error',
                    'response' => [],
                    'errors' => $validationResult
                ]);
            }

            $newWidget = $this->service->createWidget([
                'type' => $data['type'],
                'type_api' => $data['type_api'],
                'name' => $data['name'],
                'group_id' => (int)$data['group_id'],
                'vk_user_id' => (int)$params['vk_user_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'response' => $newWidget->toArray(),
                'errors' => []
            ]);

        } catch (WidgetGroupOverloadException $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (WidgetCreateException $e) {
            throw new ServiceUnavailableHttpException($e->getMessage());
        } catch(DomainException $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }
    }


    /**
     * Update widget body by given $widget_id
     */
    public function update(string $widget_id, Request $request): JsonResponse
    {

        try {

            if (!isset($widget_id) || is_numeric($widget_id)) {
                throw new BadRequestHttpException('Неверный ID виджета');
            }

            if (!$request->input('code')) {
                throw new BadRequestHttpException("Отсутствуют данные");
            }

            $params = json_decode($request->input('params'), true);
            $code = json_decode($request->input('code'), true);

            $updatedWidget = $this->service->updateWidget($widget_id, [
                'code' => $code,
                'vk_user_id' => (int)$params['vk_user_id'],
                'group_id' => (int)$params['vk_group_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'response' => $updatedWidget,
                'errors' => []
            ]);

        } catch (WidgetNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (WidgetAccessException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch(DomainException $e) {
            return $this->sendError($e);
        } catch (\Throwable $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update widget audience by given $widget_id
     */
    public function updateAudience(string $widget_id, Request $request): JsonResponse
    {

        try {

            if (!isset($widget_id) || is_numeric($widget_id)) {
                throw new BadRequestHttpException('Invalid widget_id or widget_id is not provided');
            }

            if (!$request->input('audience')) {
                throw new BadRequestHttpException('Не передана аудитория виджета');
            }

            $params = json_decode($request->input('params'), true);

            $updated_widget = $this->service->updateWidgetAudience((string) $widget_id, [
                'audience' => json_decode($request->input('audience'), true),
                'vk_user_id' => $params['vk_user_id'],
                'group_id' => $params['vk_group_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'response' => $updated_widget,
                'errors' => []
            ]);

        } catch (WidgetNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (WidgetAccessException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (DomainException $e) {
            return $this->sendError($e);
        } catch  (\Throwable $e) {
            return $this->sendError($e);
        }
    }

    public function updateName(string $widget_id, Request $request)
    {

        try {

            $name = (string)$request->input('name');
            $params = json_decode($request->input('params'), true);

            if (!isset($name)) {
                throw new BadRequestHttpException('Widget name is not provided');
            }

            $widget = $this->service->updateWidgetName($widget_id, [
                'name' => $name,
                'group_id' => $params['vk_group_id'],
                'vk_user_id' => $params['vk_user_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'response' => $widget->toArray(),
                'errors' => []
            ]);

        } catch (WidgetNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (WidgetAccessException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (DomainException $e) {
            return $this->sendError($e);
        } catch (\Throwable $e) {
            return $this->sendError($e);
        }
    }


    /**
     * Get all widget for group by given id
     */
    public function getAll($group_id, Request $request): JsonResponse
    {

        if (!isset($group_id) || !is_numeric($group_id)) {
            throw new BadRequestHttpException('Invalid group_id or group_id is not provided');
        }

        if ($this->checkAccess($request, $group_id) === false) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $widgets = $this->service->fetchAllGroupWidgets((int)$group_id);

        return $this->jsonResponse([
            'result' => 'success',
            'response' => $widgets,
            'errors' => []
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $widget_ids = $request->input('ids');
        $params = json_decode($request->input('params'), true);

        if (!isset($widget_ids)) {
            throw new BadRequestHttpException('Не переданы виджеты для удаления');
        }

        try {

            $ids = array_map(function ($id) { return (string) $id; }, $widget_ids);

            $this->service->delete($ids, [
                'group_id' => (int) $params['vk_group_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'response' => true,
                'errors' => []
            ]);

        } catch (WidgetDeleteException $e) {
            throw new ServiceUnavailableHttpException('Ошибка при удалении виджетов');
        } catch (\DomainException $e) {
            return $this->sendError($e);
        } catch (Throwable $e) {
            return $this->sendError($e);
        }
    }


    public function sort(Request $request): JsonResponse
    {

        $params = json_decode($request->input('params'), true);
        $widgets = json_decode($request->input('widgets'), true);

        if (!$widgets) {
            throw new BadRequestHttpException('Не переданы виджеты');
        }

        try {

            $this->service->sortWidgets($widgets, [
                'group_id' => (int) $params['vk_group_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'respone' => true,
                'errors' => []
            ]);

        } catch (WidgetUpdateException $e) {
            throw new ServiceUnavailableHttpException($e->getMessage());
        } catch (\DomainException $e) {
            return $this->sendError($e);
        } catch (Throwable $e) {
            return $this->sendError($e);
        }
    }

    public function publish(Request $request): JsonResponse
    {

        $ids = $request->input('ids');
        $delete_ids = $request->input('delete_ids');
        $group_id = (int) $request->input('group_id');
        $params = json_decode($request->input('params'), true);
        $ids_to_update_last_published_state = $request->input('update_last_p_state_ids', []);

        if (!isset($ids)) {
            throw new BadRequestHttpException('Widget ids not provided');
        }

        if ($this->checkAccess($request, $group_id) === false) {
            throw new AccessDeniedHttpException('Access denied');
        }

        try {

            $this->service->publishWidgets($ids, $delete_ids, $ids_to_update_last_published_state, [
                'group_id' => (int)$params['vk_group_id'],
                'vk_user_id' => (int)$params['vk_user_id']
            ]);

            return $this->jsonResponse([
                'result'   => 'success',
                'response' => $this->service->fetchAllGroupWidgets($group_id),
                'errors'   => []
            ]);

        } catch (DomainException $e) {
            return $this->sendError($e);
        } catch (Throwable $e) {
            throw $e;
        }
    }


    public function discard(string $widget_id, Request $request): JsonResponse
    {

        $params = json_decode($request->input('params'), true);

        if (!isset($widget_id)) {
            throw new BadRequestHttpException('Widget id not provided');
        }

        try {

            $updatedWidget = $this->service->discardWidgetChanges((string)$widget_id, [
                'group_id' => (int) $params['vk_group_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'response' => $updatedWidget->toArray(),
                'errors' => []
            ]);

        } catch (WidgetAccessException $e) {
            throw new AccessDeniedException($e->getMessage());
        } catch (WidgetNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (WidgetUpdateException $e) {
            throw new ServiceUnavailableHttpException($e->getMessage());
        } catch (\DomainException $e) {
            return $this->sendError($e);
        } catch (Throwable $e) {
            return $this->sendError($e);
        }
    }

    public function clone(Request $request): JsonResponse
    {

        try {
            $widget_id = $request->input('widget_id');
            $params = json_decode($request->input('params'), true);

            if (!$widget_id) {
                throw new BadRequestHttpException('Отсутствует виджет для копирования');
            }

            $clone = $this->service->cloneWidget($widget_id, [
                'group_id' => (int)$params['vk_group_id'],
                'vk_user_id' => (int)$params['vk_user_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'response' => [
                    'all' => $this->service->fetchAllGroupWidgets($clone->group_id),
                    'new' => $clone->toArray()
                ],
                'errors' => []
            ]);

        } catch (WidgetNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (WidgetGroupOverloadException $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (WidgetAccessException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (DomainException $e) {
            return $this->sendError($e);
        } catch (\Throwable $e) {
            throw $e;
            // return $this->sendError($e);
        }


    }

    public function copyCommunity(Request $request): JsonResponse
    {

        $widget_ids = $request->input('widget_ids');
        $communities_ids = $request->input('communities_ids');
        $group_id = (int) $request->input('vk_group_id');
        $params = json_decode($request->input('params'), true);

        if (!$widget_ids || !is_array($widget_ids) || count($widget_ids) <= 0) {
            throw new BadRequestHttpException('Wrong widget_ids data');
        }

        if (!$communities_ids || !is_array($communities_ids) || count($communities_ids) <= 0) {
            throw new BadRequestHttpException('Wrong communities_ids data');
        }

        try {

            $newWidgetsIds = $this->service->copyWidgets($widget_ids, [
                'source_group_id' => (int)$params['vk_group_id'],
                'target_group_id' => (int)$communities_ids[0],
                'vk_user_id' => (int)$params['vk_user_id']
            ]);

            return $this->jsonResponse([
                'result' => 'success',
                'response' => [
                    'all' => $this->service->fetchAllGroupWidgets($group_id),
                    'new' => $newWidgetsIds
                ],
                'errors' => []
            ]);
        } catch (WidgetGroupOverloadException $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (WidgetNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (DomainException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * Validation actions
     */


    /**
     * Validate request on initial create widget
     */
    protected function validateCreateWidgetPostData(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required',
                'type'  => 'required|string',
                'type_api' => 'required|string',
                'group_id' => 'required|integer'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $e->errors();
        }

        return true;
    }
}
