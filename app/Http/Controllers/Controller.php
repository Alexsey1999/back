<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    public function jsonResponse(array $data)
    {
        return response()->json($data);
    }

    public function checkAccess(Request $request, $group_id): bool
    {

        try {

            $params = $request->input('params');

            if (!$params) {
                return false;
            }

            $query_params = json_decode($params, true);

            return $group_id == $query_params['vk_group_id'];
        } catch (\Throwable $e) {
            return false;
        }

    }

    public function getVkUserId(Request $request): int
    {
        try {

            $params = $request->input('params');

            if (!$params) {
                return 0;
            }

            $query_params = json_decode($params, true);

            return isset($query_params['vk_user_id']) ? $query_params['vk_user_id'] : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function sendError(\Throwable $e)
    {
        $data = [
            'result' => 'error',
            'response' => [],
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'errors' => []
        ];

        if (false) { // В зависимости от окружения
            $data['file'] = $e->getFile();
            $data['line'] = $e->getCode();
        }

        return $this->jsonResponse($data);
    }
}
