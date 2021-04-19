<?php

namespace App\Http\Controllers;

use Error;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Interfaces\LoggerInterface;
use Throwable;

class LoggerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function error(Request $request, int $vk_group_id): JsonResponse 
    {

        $params = $request->input('params');
        $data = $request->input('data');

        $res = $this->logger->save([
            'vk_group_id' => $vk_group_id,
            'data' => $data,
            'params' => $params
        ]);

        return $this->jsonResponse([
            'result' => $res
        ]);
    }
}
