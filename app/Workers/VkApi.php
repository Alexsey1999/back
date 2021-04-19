<?php

namespace App\Workers;

use App\Interfaces\HttpClientInterface;
use App\Interfaces\LoggerInterface;

use App\Exceptions\VkApi\VkApiErrorResponseException;

class VkApi
{

    const BASE_URL = 'https://api.vk.com/method';

    const GET_DOCS_MESSAGE_UPLOAD_SERVER = 'docs.getMessagesUploadServer';
    const DOCS_SAVE_METHOD = 'docs.save';
    const SEND_MESSAGES_METHOD = 'messages.send';

    const GET_UPLOAD_IMAGE_SERVER_METHOD = 'appWidgets.getGroupImageUploadServer';
    const SAVE_IMAGE_METHOD = 'appWidgets.saveGroupImage';
    const UPDATE_WIDGET_METHOD = 'appWidgets.update';
    const SET_STORAGE_METHOD = 'storage.set';
    const SET_STATUS_METHOD = 'status.set';
    const GROUPS_GET_METHOD = 'groups.get';

    const VERSION = '5.103';

    private $group_access_token;
    private $user_access_token;

    private $client;
    private $logger;

    public function __construct(HttpClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function setGroupToken(string $token)
    {
        $this->group_access_token = $token;
    }

    public function setUserAccessToken(string $token)
    {
        $this->user_access_token = $token;
    }

    public function uploadLandingImage(string $file_path, $peer_id, $type = 'doc')
    {
        $get_upload_image_server_url = self::BASE_URL . '/' . self::GET_DOCS_MESSAGE_UPLOAD_SERVER;
        $save_image_url = self::BASE_URL . '/' . self::DOCS_SAVE_METHOD;

        /**
         * Получаем URL для загрузки изображения - upload_url
         */
        $upload_server = $this->client->request('POST', $get_upload_image_server_url, [
            'query' => [
                'type' => $type,
                'peer_id' => $peer_id,
                'access_token' => $this->group_access_token,
                'v' => self::VERSION,
            ]
        ]);

        $upload_server = json_decode($upload_server, true);

        if (!isset($upload_server['response']) || !isset($upload_server['response']['upload_url'])) {

            try {
                $this->logger->save([
                    'data' => json_encode([
                        'error_type' => 'vk_api_error',
                        'code' => $upload_server['error']['error_code'],
                        'message' => json_encode($upload_server),
                        'file' => 'VkApi.php'
                    ]),

                    'params' => app('request')->input('params')
                ]);
            } catch (\Throwable $e) {

            }

            return [
                'error' => 'Ошибка при получении сервера для загрузки файла',
                'response' => $upload_server
            ];
        }

        $upload_file = $this->client->request('POST', $upload_server['response']['upload_url'], [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($file_path, 'r')
                ]
            ]
        ]);

        $upload_file = json_decode($upload_file, true);

        if (!isset($upload_file['file'])) {

            try {
                $this->logger->save([
                    'data' => json_encode([
                        'error_type' => 'vk_api_error',
                        'code' => $upload_file['error']['error_code'],
                        'message' => json_encode($upload_file),
                        'file' => 'VkApi.php'
                    ]),

                    'params' => app('request')->input('params')
                ]);
            } catch (\Throwable $e) {

            }

            return [
                'error' => 'Ошибка при загрузке файла на сервер Вконтакте',
                'response' => $upload_server
            ];
        }

        $save_file = $this->client->request('POST', $save_image_url, [
            'query' => [
                'file' => $upload_file['file'],
                'access_token' => $this->group_access_token,
                'v' => self::VERSION,
            ]
        ]);

        $save_file = json_decode($save_file, true);

        if (!isset($save_file['response']) || !isset($save_file['response']['doc'])) {
            try {
                $this->logger->save([
                    'data' => json_encode([
                        'error_type' => 'vk_api_error',
                        'code' => $save_file['error']['error_code'],
                        'message' => json_encode($save_file),
                        'file' => 'VkApi.php'
                    ]),

                    'params' => app('request')->input('params')
                ]);
            } catch (\Throwable $e) {

            }

            return [
                'error' => 'Ошибка при загрузке файла на сервер Вконтакте',
                'response' => $upload_server
            ];
        }

        $document = $save_file['response']['doc'];
        $pool = [];
        $result = [
            'filename' => ''
        ];


        foreach ($document['preview']['photo']['sizes'] as $size) {
            if (in_array($size['type'], ['i', 'd'])) continue; // эти типы чем-то отличаются, может не доступны другим + идут с ключем

            $pool[$size['width']] = $size['src'];
        }

        krsort($pool);
        foreach ($pool as $size => $src) {
            if ($size >= 2000) continue;
            $result['filename'] = $src;
            break;
        }

        return $result;

    }

    public function uploadMessagesAttachment(string $file_path, $peer_id, $type = 'doc')
    {
        $get_upload_image_server_url = self::BASE_URL . '/' . self::GET_DOCS_MESSAGE_UPLOAD_SERVER;
        $save_image_url = self::BASE_URL . '/' . self::DOCS_SAVE_METHOD;

        /**
         * Получаем URL для загрузки изображения - upload_url
         */
        $upload_server = $this->client->request('POST', $get_upload_image_server_url, [
            'query' => [
                'type' => $type,
                'peer_id' => $peer_id,
                'access_token' => $this->group_access_token,
                'v' => self::VERSION,
            ]
        ]);

        $upload_server = json_decode($upload_server, true);

        if (!isset($upload_server['response']) || !isset($upload_server['response']['upload_url'])) {

            try {
                $this->logger->save([
                    'data' => json_encode([
                        'error_type' => 'vk_api_error',
                        'code' => $upload_server['error']['error_code'],
                        'message' => json_encode($upload_server),
                        'file' => 'VkApi.php'
                    ]),

                    'params' => app('request')->input('params')
                ]);
            } catch (\Throwable $e) {

            }

            return [
                'error' => 'Ошибка при получении сервера для загрузки файла',
                'response' => $upload_server
            ];
        }

        $upload_file = $this->client->request('POST', $upload_server['response']['upload_url'], [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($file_path, 'r')
                ]
            ]
        ]);

        $upload_file = json_decode($upload_file, true);

        if (!isset($upload_file['file'])) {

            try {
                $this->logger->save([
                    'data' => json_encode([
                        'error_type' => 'vk_api_error',
                        'code' => $upload_file['error']['error_code'],
                        'message' => json_encode($upload_file),
                        'file' => 'VkApi.php'
                    ]),

                    'params' => app('request')->input('params')
                ]);
            } catch (\Throwable $e) {

            }

            return [
                'error' => 'Ошибка при загрузке файла на сервер Вконтакте',
                'response' => $upload_server
            ];
        }

        $save_file = $this->client->request('POST', $save_image_url, [
            'query' => [
                'file' => $upload_file['file'],
                'access_token' => $this->group_access_token,
                'v' => self::VERSION,
            ]
        ]);

        $save_file = json_decode($save_file, true);

        if (!isset($save_file['response']) || !isset($save_file['response']['doc'])) {
            try {
                $this->logger->save([
                    'data' => json_encode([
                        'error_type' => 'vk_api_error',
                        'code' => $save_file['error']['error_code'],
                        'message' => json_encode($save_file),
                        'file' => 'VkApi.php'
                    ]),

                    'params' => app('request')->input('params')
                ]);
            } catch (\Throwable $e) {

            }

            return [
                'error' => 'Ошибка при загрузке файла на сервер Вконтакте',
                'response' => $upload_server
            ];
        }

        $document = $save_file['response']['doc'];
        return $document;

    }

    /**
     * Сохранение изображения для виджета сообщества на сервере ВК
     */
    public function saveGroupWidgetImage(string $file_path, string $image_type)
    {
        $get_upload_image_server_url = self::BASE_URL . '/' . self::GET_UPLOAD_IMAGE_SERVER_METHOD;
        $save_image_url = self::BASE_URL . '/' . self::SAVE_IMAGE_METHOD;

        /**
         * Получаем URL для загрузки изображения - upload_url
         */
        $upload_url_result = $this->client->request('POST', $get_upload_image_server_url, [
            'query' => [
                'access_token' => $this->group_access_token,
                'v' => self::VERSION,
                'image_type' => $image_type
            ]
        ]);

        $upload_url_result = json_decode($upload_url_result, true);

        if (!isset($upload_url_result['response']) && !isset($upload_url_result['response']['upload_url'])) {

            try {
                $this->logger->save([
                    'data' => json_encode([
                        'error_type' => 'vk_api_error',
                        'code' => $upload_url_result['error']['error_code'],
                        'message' => json_encode($upload_url_result),
                        'file' => 'VkApi.php'
                    ]),

                    'params' => app('request')->input('params')
                ]);
            } catch (\Throwable $e) {

            }

            return [
                'error' => 'Get upload url error',
                'response' => $upload_url_result
            ];
        }

        /**
         * Отправялем запрос на upload_url на загрузку изображения
         */
        $upload_file_result = $this->client->request('POST', $upload_url_result['response']['upload_url'], [
            'multipart' => [
                [
                    'name' => 'image',
                    'contents' => fopen($file_path, 'r')
                ]
            ]
        ]);

        $upload_file_result = json_decode($upload_file_result, true);

        if (!isset($upload_file_result['hash']) || !isset($upload_file_result['image'])) {
            try {
                $this->logger->save([
                    'data' => json_encode([
                        'error_type' => 'vk_api_error',
                        'code' => $upload_file_result['error']['error_code'],
                        'message' => json_encode($upload_file_result),
                        'file' => 'VkApi.php'
                    ]),

                    'params' => app('request')->input('params')
                ]);
            } catch (\Throwable $e) {

            }
            return [
                'error' => 'Image upload error',
                'response' => $upload_url_result
            ];
        }


        /**
         * Сохраняем изображение
         */
        $save_file_result = $this->client->request('GET', $save_image_url, [
            'query' => [
                'hash' => $upload_file_result['hash'],
                'image' => $upload_file_result['image'],
                'access_token' => $this->group_access_token,
                'v' => self::VERSION
            ]
        ]);

        $save_file_result = json_decode($save_file_result, true);

        if (!isset($save_file_result['response']) || !isset($save_file_result['response']['id'])) {
            try {
                $this->logger->save([
                    'data' => json_encode([
                        'error_type' => 'vk_api_error',
                        'code' => $save_file_result['error']['error_code'],
                        'message' => json_encode($save_file_result),
                        'file' => 'VkApi.php'
                    ]),

                    'params' => app('request')->input('params')
                ]);
            } catch (\Throwable $e) {

            }
            return [
                'error' => 'Image save error',
                'response' => $save_file_result
            ];
        }

        return $save_file_result;
    }

    public function sendMessage(int $user_id, string $text, $attachments = [])
    {

        $params = [
            'access_token' => $this->group_access_token,
            'v' => self::VERSION,
            'user_id' => $user_id,
            'message' => $text,
            'random_id' => time()
        ];

        if (count($attachments) > 0) {
            $params['attachment'] = implode(',', $attachments);
        }

        $url = self::BASE_URL . '/' . self::SEND_MESSAGES_METHOD;
        $result = $this->client->request('POST', $url, [
            'query' => $params
        ]);

        return json_decode($result, true);

    }

    /**
     * НЕ ИСПОЛЬЗУЕТСЯ
     * Публикация виджета в группу ВК
     * @var string $code - код виджета для метода execute()
     * @var string $type - тип виджета внутри VK
     * @var string $token - токен для доступа в группу
     * @var string $group_id - ID группы в контакте
     */
    public function publishWidget(string $code, string $type, $group_id)
    {
        $url = self::BASE_URL . '/' . self::UPDATE_WIDGET_METHOD;

        $response = $this->client->request('POST', $url, [
            'query' => [
                'access_token' => $this->group_access_token,
                'v' => self::VERSION,
                'type' => $type
            ],
            'form_params' => [
                'code' => $code
            ]
        ]);

        return json_decode($response, true);
    }

    /**
     * Обновление статуса тестового пользователя
     */
    public function updateTestUserStatus(string $status): array
    {
        $url = self::BASE_URL . '/' . self::SET_STATUS_METHOD;

        $response = $this->client->request('POST', $url, [
            'query' => [
                'access_token' => $this->user_access_token,
                'v' => self::VERSION,
                'text' => $status
            ]
        ]);

        return json_decode($response, true);
    }

    /**
     * @throws VkApiErrorResponseException
     * @param int $user_id
     * @param int $group_id
     * @param string $user_access_token
     * @return bool
     */
    public function isGroupEditor(int $user_id, int $group_id, string $user_access_token): bool
    {
        $url = self::BASE_URL . '/' . self::GROUPS_GET_METHOD;

        $response = json_decode($this->client->request('POST', $url, [
            'query' => [
                'access_token' => $user_access_token,
                'v' => self::VERSION,
                'user_id' => $user_id,
                'filter' => 'editor'
            ]
        ]), true);

        if (isset($response['error'])) {
            throw new VkApiErrorResponseException($response['error']['error_msg']);
        }

        if (is_array($response['response']['items']) && in_array($group_id, $response['response']['items'])) {
            return true;
        }

        return false;
    }
}
