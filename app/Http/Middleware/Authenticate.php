<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Interfaces\AppConfigProviderInterface;

class Authenticate
{

    private $globalConfigProvider;

    /**
     * Роли пользователей внутри сообщества ВК
     * Которым разрешен доступ к виджетам и к промостраницам
     */
    private $allowed_roles = ['admin', 'editor', 'moder'];

    /**
     * Роуты, которые разрешены для всех уровней пользователей внтури сообщества ВК
     */
    private $publicRoutes = [

        /**
         * Логирование ошибки с фронта
         */
        'log/error/1',

        /**
         * Доступ к коллекции виджетов
         */
        'share/get-collection',

        /**
         * Получение коллекции виджетов
         */

        'share/copy-collection',

        /**
         * Запросы для подписок проксируются через appback.senler.ru на senler.ru
         * Они пройдут валидацию там
         */
        'ajax/vkapp/*',

        /**
         * Запрос на получение данных страницы при просмотре лендинга любым пользователем - не аутентифицируем
         */
        'pages/get-one',

        /**
         * Запрос на сохранение данных заявки
         */
        'pages/save-lead',

        /**
         * Запрос на проброс заявки в триггер
         */
        'pages/save-lead-trigger',

        /**
         * Запрос на сохранение данных статистики - целевое действие
         */
        'stat/goal',

        /**
         * Запрос на сохранение данных статистики - просмотр страницы
         */
        'stat/hit'
    ];

    private $externalRoutes = [

        /**
         * Запрос на получение списка промо-страниц сообщества
         */
        'pages/get-list-external'
    ];

    public function __construct(AppConfigProviderInterface $globalConfigProvider)
    {
        $this->globalConfigProvider = $globalConfigProvider;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        // Запросы на все публичные роуты не аутентифицируем
        if ($request->is(...$this->publicRoutes)) {
            return $next($request);
        }

        if ($request->is(...$this->externalRoutes)) {
            if ($this->handleExternalRequest($request)) {
                return $next($request);
            }

            throw new AccessDeniedHttpException('Access denied');
        }

        // Авторизация по параметрам запроса переданным явно в теле запроса
        $bodyRequestParams = $request->input('params');
        if (isset($bodyRequestParams)) {
            if ($this->handleBodyStrategy($request, $next)) {
                return $next($request);
            }
        }

        // Если параметры запроса не переданы явно - попытаемся взять из referer
        $referer = $request->server('HTTP_REFERER');
        if (isset($referer)) {
            if ($this->handleReferreStrategy($request, $next)) {
                return $next($request);
            }
        }

        // Иначе выбросим исключение
        throw new AccessDeniedHttpException('Access denied');
    }

    private function handleReferreStrategy(Request $request, Closure $next)
    {

        $url = $request->server('HTTP_REFERER');

        // Получаем параметры запроса
        $query_params = $this->getQueryParams($url);

        // Формируем строку запроса для генерации подписи
        $sign_params_query = $this->getSignQueryParams($query_params);

        // Генерируем подпись на основе строки запроса и secret ключа
        $sign = $this->generateSign(
            $sign_params_query,
            $this->globalConfigProvider->getClientSecret()
        );

        if (!$sign) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $role = $query_params['vk_viewer_group_role'];

        // Сравниваем подпись и параметр роли пользователя, запустившего приложение
        $status = $sign === $query_params['sign'] && in_array($role, $this->allowed_roles);


        // Если подпись в запросе не совпала со сгенерированной подписью на сервере
        if (false === $status) {
            throw new AccessDeniedHttpException('Access denied');
        }

        return true;
    }

    private function handleBodyStrategy(Request $request, Closure $next)
    {

        try {

            $params_str = $request->input('params');

            // Получаем параметры запроса
            $query_params = json_decode($params_str, true);

            if (!is_array($query_params)) {
                throw new AccessDeniedHttpException('Wrong data');
            }

            // Формируем строку запроса для генерации подписи
            $sign_params_query = $this->getSignQueryParams($query_params);

            // Генерируем подпись на основе строки запроса и secret ключа
            $sign = $this->generateSign(
                $sign_params_query,
                $this->globalConfigProvider->getClientSecret()
            );

            if (!$sign) {
                throw new AccessDeniedHttpException('Access denied - no sign');
            }

            $role = $query_params['vk_viewer_group_role'];

            // Сравниваем подпись и параметр роли пользователя, запустившего приложение
            $status = $sign === $query_params['sign'] && in_array($role, $this->allowed_roles);

            // Если подпись в запросе не совпала со сгенерированной подписью на сервере
            if (false === $status) {
                throw new AccessDeniedHttpException('Access denied - sign corrupted' . ' ' . $sign_params_query);
            }

            return true;

        } catch (\Throwable $e) {
            throw new AccessDeniedHttpException('Access denied ' . $e->getMessage());
        }
    }

    private function handleExternalRequest(Request $request)
    {

        try {

            $params_str = $request->input('params');

            // Получаем параметры запроса
            $query_params = json_decode($params_str, true);

            if (!is_array($query_params)) {
                return false;
            }

            // Формируем строку запроса для генерации подписи
            $sign_params_query = $this->getExternalSignQueryParams($query_params);

            // Генерируем подпись на основе строки запроса и secret ключа
            $sign = $this->generateSign(
                $sign_params_query,
                $this->globalConfigProvider->getClientSecret()
            );

            if (!$sign) {
                return false;
            }

            // Сравниваем подпись и параметр роли пользователя, запустившего приложение
            $status = $sign === $query_params['sign'];

            // Если подпись в запросе не совпала со сгенерированной подписью на сервере
            return $status;

        } catch (\Throwable $e) {
            return false;
        }
    }


    private function getQueryParams(string $url): array
    {
        $query_params = [];
        parse_str(parse_url($url, PHP_URL_QUERY), $query_params);
        return $query_params;
    }

    /**
     * Генерирование строки запроса подписи для запросов из ВК приложения
     */
    private function getSignQueryParams(array $query_params): string
    {
        $sign_params = [];

        foreach ($query_params as $name => $value) {
            if (strpos($name, 'vk_') !== 0) {
                continue;
            }
            $sign_params[$name] = urldecode($value);
        }

        ksort($sign_params);

        $sign_params_query = http_build_query($sign_params);

        return $sign_params_query;
    }

    /**
     * Генерирование строки запроса подписи для внешних запросов
     */
    private function getExternalSignQueryParams(array $query_params): string
    {
        $sign_params = [];

        foreach ($query_params as $name => $value) {
            if ($name === 'sign') continue;
            $sign_params[$name] = urldecode($value);
        }

        ksort($sign_params);

        return http_build_query($sign_params);
    }


    private function generateSign(string $sign_params_query, string $secret): string
    {
        $sign = rtrim(
            strtr(
                base64_encode(hash_hmac('sha256', $sign_params_query, $secret, true)),
                '+/', '-_'
            ), '=');

        return $sign;
    }
}
