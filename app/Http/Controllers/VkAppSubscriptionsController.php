<?php

namespace App\Http\Controllers;

use Error;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\SubscriptionsService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Throwable;


/**
 * Прокси контроллер для запросов на действия с группами подписок
 * Слой ввода\вывода информации
 * Вся бизнес логика должна быть заключена в соответствующие прикладные сервисы
 * В контроллере только обработка вводимых, выводимых данных и передача данных в сервисы
 */
class VkAppSubscriptionsController extends Controller
{

    public $subscriptionsService;

    public function __construct(SubscriptionsService $subscriptionsService)
    {
        $this->subscriptionsService = $subscriptionsService;
    }

    /**
     * Получение информации о сообществе и групп подписок\группы подписки
     * Для определенного просматривающего пользователя
     */
    public function getSubscriptions(Request $request): JsonResponse
    {
        $requestParams = json_decode($request->getContent(), true);
        $data = $this->subscriptionsService->getSubscriptionsForUser($requestParams);
        return $this->jsonResponse($data);
    }

    /**
     * Получение информации о сообществе и групп подписок\группы подписки для промостраницы
     * Для определенного просматривающего пользователя
     */
    public function getPromoPageData(Request $request): JsonResponse
    {
        $requestParams = json_decode($request->getContent(), true);
        $data = $this->subscriptionsService->getPromoPageData($requestParams);
        return $this->jsonResponse($data);
    }

    /**
     * Получение полного списка групп подписок
     * Например для администратора сообщества
     */
    public function getAdminSubscriptions(Request $request): JsonResponse
    {
        $requestParams = json_decode($request->getContent(), true);
        $data = $this->subscriptionsService->getSubscriptionsForAdmin($requestParams);
        return $this->jsonResponse($data);
    }

    /**
     * Подписка на группу подписчиков
     */
    public function subscribe(Request $request): JsonResponse
    {
        $requestParams = json_decode($request->getContent(), true);
        $data = $this->subscriptionsService->subscribe($requestParams);
        return $this->jsonResponse($data);
    }

    /**
     * Отписка от группы подписчиков
     */
    public function unSubscribe(Request $request): JsonResponse
    {
        $requestParams = json_decode($request->getContent(), true);
        $data = $this->subscriptionsService->unSubscribe($requestParams);
        return $this->jsonResponse($data);
    }

    /**
     * Отписка от всех подписок
     */
    public function unSubscribeAll(Request $request): JsonResponse
    {
        $requestParams = json_decode($request->getContent(), true);
        $data = $this->subscriptionsService->unSubscribeAll($requestParams);
        return $this->jsonResponse($data);
    }

    /**
     * Добавление пользователя в бота
     */
    public function addToBot(Request $request): JsonResponse
    {
        $requestParams = json_decode($request->getContent(), true);
        $data = $this->subscriptionsService->addToBot($requestParams);
        return $this->jsonResponse($data);
    }

    /**
     * Начала оплаты платной подписки
     */
    public function createOrder(Request $request): JsonResponse
    {
        $requestParams = json_decode($request->getContent(), true);
        $data = $this->subscriptionsService->createOrder($requestParams);
        return $this->jsonResponse($data);
    }

    /**
     * Проверка успешной оплаты платной подписки
     */
    public function checkOrder(Request $request): JsonResponse
    {
        $requestParams = json_decode($request->getContent(), true);
        $data = $this->subscriptionsService->checkOrder($requestParams);
        return $this->jsonResponse($data);
    }
}
