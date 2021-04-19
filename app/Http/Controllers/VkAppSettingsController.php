<?php

namespace App\Http\Controllers;

use Error;
use Throwable;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\VkAppSettingsService;

/**
 * Прокси контроллер для запросов на действия с настройками ВК приложения
 * Слой ввода\вывода информации
 * Вся бизнес логика должна быть заключена в соответствующие прикладные сервисы
 * В контроллере только обработка вводимых, выводимых данных и передача данных в сервисы 
 */
class VkAppSettingsController extends Controller
{

    public $settingsService;

    public function __construct(VkAppSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function saveAppSettings(Request $request): JsonResponse
    {
        $requestParams = $request->all();
        $data = $this->settingsService->saveAppSettings($requestParams);
        return $this->jsonResponse($data);
    }

    public function saveAppMetrics(Request $request): JsonResponse
    {
        $requestParams = $request->all();
        $data = $this->settingsService->saveAppMetrics($requestParams);
        return $this->jsonResponse($data);
    }

    public function updateSubscriptionsList(Request $request): JsonResponse
    {
        $requestParams = json_decode($request->getContent(), true);
        $data = $this->settingsService->updateSubscriptionsList($requestParams);
        return $this->jsonResponse($data);
    }

    public function saveAppBannerFile(Request $request): JsonResponse
    {
        $requestParams = $request->all();
        $file = $request->file('file');

        $fileTempPath = base_path() . '/tmp';
        $fileTempName = uniqid() . '.' . $file->getClientOriginalExtension();

        $file->move($fileTempPath, $fileTempName);
        $filePath = $fileTempPath . '/' . $fileTempName;

        $data = $this->settingsService->saveAppBannerFile($requestParams, $filePath);

        unlink($filePath);

        return $this->jsonResponse($data);
    }
}
