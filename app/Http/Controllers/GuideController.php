<?php

namespace App\Http\Controllers;

use Error;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Interfaces\GuideServiceInterface;
use App\Exceptions\GroupGuideNotFoundException;

class GuideController extends Controller
{

    private $guideService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(GuideServiceInterface $guideService)
    {
        $this->guideService = $guideService;
    }


   public function get(Request $request, int $vk_group_id): JsonResponse
   {

        if (!isset($vk_group_id) || !is_numeric($vk_group_id)) {
            throw new BadRequestHttpException('Bar request');
        }

        if ($this->checkAccess($request, $vk_group_id) === false) {
            throw new AccessDeniedHttpException('Access denied');
        }

        try {

            $guide = $this->guideService->getOne(['vk_group_id' => (int) $vk_group_id]);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $guide->toArray(),
                'errors' => []
            ]);

        } catch (GroupGuideNotFoundException $e) {

            $guide = $this->guideService->create($vk_group_id);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $guide->toArray(),
                'errors' => []
            ]);

        } catch (Throwable $e) {
            return $this->jsonResponse([
                'result' => 'error',
                'data' => [],
                'errors' => [
                    'Ошибка при получении информации для сообщества',
                    $e->getMessage()
                ]
            ]);
        }
   }

   public function update(Request $request, int $vk_group_id): JsonResponse
   {
        if (!isset($vk_group_id) || !is_numeric($vk_group_id)) {
            throw new BadRequestHttpException('Bar request');
        }

        if ($this->checkAccess($request, $vk_group_id) === false) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $guideData = $request->input('guide');

        try {

            $guide = json_decode($guideData, true);
            $updateResult = $this->guideService->update((int) $vk_group_id, $guide);

            return $this->jsonResponse([
                'result' => 'success',
                'data' => $updateResult,
                'errors' => []
            ]);

        } catch(Throwable $e) {
            return $this->jsonResponse([
                'result' => 'error',
                'data' => [],
                'errors' => [
                    'Ошибка при обновлении информации для сообщества',
                    $e->getMessage()
                ]
            ]);
        }
   }

   public function delete(Request $request, int $vk_group_id)
   {
        if (!isset($vk_group_id) || !is_numeric($vk_group_id)) {
            throw new BadRequestHttpException('Bar request');
        }

        if ($this->checkAccess($request, $vk_group_id) === false) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $resp = $this->guideService->delete($vk_group_id);

        if ($resp === true) {
            return $this->jsonResponse([
                'result' => 'success',
                'data' => true,
                'errors' => []
            ]);
        } else {
            return $this->jsonResponse([
                'result' => 'error',
                'data' => false,
                'errors' => [
                    'Ошибка при удалении'
                ]
            ]);
        }
   }
}
