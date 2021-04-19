<?php

/**
 * Инициализирующая загрузка приложения
 */

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();


/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();

// $app->withEloquent();

/**
 * Убрать когда будут конфиги Kubernetes
 */
$IS_TEST = env('TESTING') === '1';
$IS_LOCAL = false || $IS_TEST;

/*
|--------------------------------------------------------------------------
| ID приложения ВК c которого выполняется запрос по умолчанию
|--------------------------------------------------------------------------
|
| В обычных запросах всегда приходит vk_app_id
| В консольных скриптах и тестах нет. Для такого случая долже быть id по умолчанию
|
*/

$DEFAULT_VK_APP_ID = 6747989;


/**
 * Раскомментировать, когда будут конфиги Kubernetes
 */
// $vk_mini_apps = json_decode(file_get_contents(__DIR__ . '/../config_main/vk_mini_apps.json'), true);

$vk_mini_apps = require __DIR__ . '/../config/vk_mini_apps.php';

// $test_vk_user = json_decode(file_get_contents(__DIR__ . '/../config_main/test_vk_user.json'), true);

$test_vk_user = require __DIR__ . '/../config/test_vk_user.php';

/*
|--------------------------------------------------------------------------
| Хосты ElasticSearch кластера
|--------------------------------------------------------------------------
|
| Устаналиваются в зависимости от окружения.
| На проде хосты ВНУТРИ локальной сети
|
*/
// $elastic = json_decode(file_get_contents(__DIR__ . '/../config_env/elastic.json'), true);

$elastic_hosts = require __DIR__ . '/../config/elastic.php';
$elastic = $IS_LOCAL ? $elastic_hosts['test'] : $elastic_hosts['prod'];

/*
|--------------------------------------------------------------------------
| Конфиг подключения к MongoDB
|--------------------------------------------------------------------------
|
| Устаналиваются в зависимости от окружения.
| В Prod окружении так же необходимы файлы сертификатов
|
*/

$mongo = require __DIR__ . '/../config/mongodb.php';

/**
 * Раскомментировать, когда будут конфиги Kubernetes
 */
// $mongo_config = json_decode(file_get_contents(__DIR__ . '/../config_env/mongodb.widgets.json'), true);

$mongo_config = $IS_LOCAL ? $mongo['test'] : $mongo['prod'];

// if (isset($mongo_config['ca_file'])) {
//     $mongo_config['ca_file'] = str_replace('<DOCUMENT_ROOT>', dirname(__FILE__) . '/../config_env', $item['ca_file']);
// }
// if (isset($mongo_config['pem_file'])) {
//     $mongo_config['pem_file'] = str_replace('<DOCUMENT_ROOT>', dirname(__FILE__) . '/../config_env', $item['pem_file']);
// }

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/**
 * HTTP клиент для запросов на сторонние ресурсы и Api
 */
$app->bind(App\Interfaces\HttpClientInterface::class, App\Services\HttpGuzzleClient::class);

/**
 * Привязка для конструктора VK Api - по хорошему завязаться на интерфейс, а не на конкретный класс
 */
$app->bind(App\Workers\VkApi::class);

$app->bind(App\Services\SubscriptionsService::class);
$app->bind(App\Services\VkAppSettingsService::class);
$app->bind(App\Services\Profiler::class);

/**
 * Привязка для логгера ошибок
 */

// Если нужен лог в MongoDB - раскомментировать тут и закомментировать ElasticSearch
// $app->bind(App\Interfaces\LoggerInterface::class, App\Services\Logger\MongoDBErrorLogger::class);

// Если нужен лог в ElasticSearch
// Конфигурируем в зависимости от окружения
// Singleton - чтобы не создавать новый инстанс при каждом вызове - кешируем подключение
$app->singleton(
    App\Interfaces\LoggerInterface::class,
    function () use ($elastic) {
        return new App\Services\Logger\ElasticErrorLogger($elastic['hosts']);
    }
);

/**
 * Привязки сервисов для сущностей
 */

$app->bind(App\Interfaces\GuideServiceInterface::class, App\Services\GuideService::class);
$app->bind(App\Services\WidgetService::class);
$app->bind(App\Services\SharedService::class);

/**
 * Привязки репозиториев для сущностей
 */

$app->bind(App\Widgets\WidgetFactory::class);
$app->bind(App\Interfaces\GuideRepositoryInterface::class,   App\Repositories\GuideRepository::class);
$app->bind(App\Interfaces\WidgetsRepositoryInterface::class, App\Repositories\WidgetsRepository::class);
$app->bind(App\Repositories\GuideRepository::class);
$app->bind(App\Repositories\SharedRepository::class);


$app->bind(App\Interfaces\Pages\PagesRepositoryInterface::class,           App\Repositories\PagesRepository::class);
$app->bind(App\Interfaces\Pages\BlocksEditRepositoryInterface::class,      App\Repositories\BlocksEditRepository::class);
$app->bind(App\Interfaces\Pages\BlocksPublishedRepositoryInterface::class, App\Repositories\BlocksPublishedRepository::class);
$app->bind(App\Interfaces\Pages\PageStatesRepositoryInterface::class,      App\Repositories\PageStatesRepository::class);
$app->bind(App\Interfaces\Pages\BlocksUsageLogRepositoryInterface::class,  App\Repositories\BlocksUsageLogRepository::class);

$app->bind(App\Services\PageStatisticService::class);

if ($IS_TEST) {
    /**
     * При выполнении в тестовой среде - используем фэйк-репозитория для статистики
     */
    $app->bind(App\Interfaces\Pages\PageStatisticRepositoryInterface::class,   function () use ($elastic) {
        return new App\Repositories\Fakes\PageStatisticFakeRepository();
    });
} else {
    $app->bind(App\Interfaces\Pages\PageStatisticRepositoryInterface::class,   function () use ($elastic) {
        return new App\Repositories\PageStatisticElasticRepository($elastic['hosts']);
    });
}

$app->bind(App\Interfaces\LeadServiceInterface::class, App\Services\LeadService::class);
$app->bind(App\Interfaces\LeadRepositoryInterface::class, App\Repositories\LeadMongoRepository::class);
/*
|--------------------------------------------------------------------------
| Привязка глобального конфига приложения
|--------------------------------------------------------------------------
|
| В зависимости от переданного в запросе $vk_app_id устанавливается
| - Секретный ключ для проверки подписи
| - Сервисный ключ приложения
|
*/
$vk_app_id = null;

if (isset($_POST['params'])) {
    $params = json_decode($_POST['params'], true);
    if (isset($params['vk_app_id'])) {
        $vk_app_id = (int)$params['vk_app_id'];
    } else {
        $vk_app_id = $DEFAULT_VK_APP_ID;
    }
} else {
    $vk_app_id = $DEFAULT_VK_APP_ID;
}

$app->singleton(
    App\Interfaces\AppConfigProviderInterface::class,
    function () use (
        $vk_app_id,
        $vk_mini_apps,
        $test_vk_user) {
        return new App\AppConfig($vk_app_id, $vk_mini_apps, $test_vk_user);
    }
);

/**
 * Привязка для базы данных
 */

$app->singleton(App\Interfaces\DbInterface::class, function () use ($mongo_config) {
    return new App\Db\MongoDB(
        new App\Dto\MongoConnectionConfig($mongo_config)
    );
});

$app->bind(App\Formatters\MongoDBFormatter::class);


/*
|--------------------------------------------------------------------------
| Привязки для сущностей и сервисов конструктора лендингов
|--------------------------------------------------------------------------
|
*/

$app->bind(App\Pages\PagesFactory::class);


/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    App\Http\Middleware\CorsMiddleware::class
]);

/**
 * Apply Auth middleware to check request app SIGN
 */
// $app->middleware([
//     App\Http\Middleware\Authenticate::class
// ]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// $app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;
