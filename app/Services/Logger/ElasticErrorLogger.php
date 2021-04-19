<?php

namespace App\Services\Logger;

use App\Interfaces\LoggerInterface;
use Elasticsearch\ClientBuilder;

class ElasticErrorLogger implements LoggerInterface
{
    private $hosts;
    private $client;

    public function __construct(array $hosts)
    {
        $this->hosts = $hosts;
        $this->client = ClientBuilder::create()->setRetries(1)->setHosts($this->hosts)->build();
    }
    

    public function save(array $error)
    {
        try {

            $current_date = date('Y.m.d');
            $index_name = 'client-error-' . $current_date;

            $index_params['index']  = $index_name;
            $index_is_exists = $this->checkIndexExists($index_params);

            if (false === $index_is_exists) {
                $index_params = $this->getIndexParams($index_name);
                $index_is_exists = $this->createIndex($index_params);
            }

            if ($index_is_exists) {

                $doc_params = [
                    'index' => $index_name,
                    'type' => '_doc',
                    'body' => [
                        'tags' => ['client-error', 'manual'],
                        '@timestamp' => $this->getTimestamp()
                    ]
                ];

                $error_data = json_decode($error['data'], true);

                if ($error_data) {
                    if (isset($error_data['error_type'])) {
                        $doc_params['body']['error_type'] = $error_data['error_type'];
                    }

                    if (isset($error_data['message'])) {
                        $doc_params['body']['message'] = $error_data['message'];
                    }

                    if (isset($error_data['code'])) {
                        $doc_params['body']['code'] = $error_data['code'];
                    }

                    if (isset($error_data['file'])) {
                        $doc_params['body']['file'] = $error_data['file'];
                    }
                }

                $error_params = json_decode($error['params'], true);

                if ($error_params) {
                    if (isset($error_params["vk_group_id"])) {
                        $doc_params['body']['params'][ "vk_group_id"] = $error_params[ "vk_group_id"];
                    }
                    if (isset($error_params["vk_user_id"])) {
                        $doc_params['body']['params']["vk_user_id"] = $error_params["vk_user_id"];
                    }
                    if (isset($error_params["vk_app_id"])) {
                        $doc_params['body']['params']["vk_app_id"] = $error_params["vk_app_id"];
                    }
                    if (isset($error_params["utm_source"])) {
                        $doc_params['body']['params']["utm_source"] = $error_params["utm_source"];
                    }
                    if (isset($error_params["utm_medium"])) {
                        $doc_params['body']['params']["utm_medium"] = $error_params["utm_medium"];
                    }
                    if (isset($error_params["utm_campaign"])) {
                        $doc_params['body']['params']["utm_campaign"] = $error_params["utm_campaign"];
                    }
                    if (isset($error_params["utm_term"])) {
                        $doc_params['body']['params']["utm_term"] = $error_params["utm_term"];
                    }
                    if (isset($error_params["utm_content"])) {
                        $doc_params['body']['params']["utm_content"] = $error_params["utm_content"];
                    }
                    if (isset($error_params["source"])) {
                        $doc_params['body']['params']["source"] = $error_params["source"];
                    }
                    if (isset($error_params["vk_access_token_settings"])) {
                        $doc_params['body']['params']["vk_access_token_settings"] = $error_params["vk_access_token_settings"];
                    }
                    if (isset($error_params["vk_are_notifications_enabled"])) {
                        $doc_params['body']['params']["vk_are_notifications_enabled"] = $error_params["vk_are_notifications_enabled"];
                    }
                    if (isset($error_params["vk_is_app_user"])) {
                        $doc_params['body']['params']["vk_is_app_user"] = $error_params["vk_is_app_user"];
                    }
                    if (isset($error_params["vk_is_favorite"])) {
                        $doc_params['body']['params']["vk_is_favorite"] = $error_params["vk_is_favorite"];
                    }
                    if (isset($error_params["vk_language"])) {
                        $doc_params['body']['params']["vk_language"] = $error_params["vk_language"];
                    }
                    if (isset($error_params["vk_platform"])) {
                        $doc_params['body']['params']["vk_platform"] = $error_params["vk_platform"];
                    }
                    if (isset($error_params["vk_ref"])) {
                        $doc_params['body']['params']["vk_ref"] = $error_params["vk_ref"];
                    }
                    if (isset($error_params["vk_viewer_group_role"])) {
                        $doc_params['body']['params']["vk_viewer_group_role"] = $error_params["vk_viewer_group_role"];
                    }
                }

                $request = $this->configureRequest($doc_params);
                $res = $this->client->index($request);
                return isset($res['result']) && $res['result'] === 'created';

            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function checkIndexExists(array $params = [])
    {
        $request = $this->configureRequest($params);
        return $this->client->indices()->exists($request);
    }

    public function createIndex(array $params = []) 
    {
        $request = $this->configureRequest($params);
        $response = $this->client->indices()->create($request);
        return isset($response['acknowledged']) ? $response['acknowledged'] : false;
    }

    private function getTimestamp(): string
    {
        $t = microtime(true);
        $micro = sprintf("%06d",($t - floor($t)) * 1000000);
        $d = new \DateTime(date('Y-m-d H:i:s.'.$micro, $t));
        $d->setTimezone(new \DateTimeZone("UTC"));
        $timestamp = $d->format("Y-m-d H:i:s.u");

        return $timestamp;
    }

    private function getIndexParams(string $name): array
    {
        return [
            'index' => $name,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0
                ],
                'mappings' => [
                    "dynamic_templates" => [
                        [
                            "message_field" => [
                                "path_match" => "message",
                                "match_mapping_type" => "string",
                                "mapping" => [
                                    "norms" => false,
                                    "type" => "text"
                                ]
                            ]
                        ],
                        [
                            "string_fields" => [
                                "match" => "*",
                                "match_mapping_type" => "string",
                                "mapping" => [
                                    "fields" => [
                                        "keyword" => [
                                            "ignore_above" => 256,
                                            "type" => "keyword"
                                        ]
                                    ],
                                    "norms" => false,
                                    "type" => "text"
                                ]
                            ]
                        ]
                    ],
                    'properties' => [
                        "@timestamp" => [
                            "type" => "date",
                            "format" => "yyyy-MM-dd HH:mm:ss.SSSSSS"
                        ],
                        "tags" => [
                            "type" => "text",
                            "norms" => false,
                            "fields" => [
                                "keyword" => [
                                    "type" => "keyword",
                                    "ignore_above" => 256
                                ]
                            ]
                        ],
                        "error_type" => [
                            "type" => "text",
                            "norms" => false
                        ],
                        "message" => [
                            "type" => "text",
                            "norms" => false
                        ],
                        "code" => [
                            "type" => "text",
                            "norms" => false
                        ],
                        "file" => [
                            "type" => "text",
                            "norms" => false
                        ],
                        "params" => [
                            "properties" => [
                                "vk_group_id" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "vk_user_id" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "vk_app_id" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "utm_source" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "utm_medium" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "utm_campaign" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "utm_term" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "utm_content" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "source" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "vk_access_token_settings" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "vk_are_notifications_enabled" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "vk_is_app_user" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "vk_is_favorite" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "vk_language" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "vk_platform" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "vk_ref" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                                "vk_viewer_group_role" => [
                                    "type" => "text",
                                    "norms" => false
                                ],
                            ]
                        ],
                        "auth" => [
                            "type" => "text",
                            "norms" => false,
                            "fields" => [
                                "keyword" => [
                                    "type" => "keyword",
                                    "ignore_above" => 256
                                ]
                            ]
                        ],
                        "bytes" => [
                            "type" => "text",
                            "norms" => false,
                            "fields" => [
                                "keyword" => [
                                    "type" => "keyword",
                                    "ignore_above" => 256
                                ]
                            ]
                        ],
                        "clientip" => [
                            "type" => "text",
                            "norms" => false,
                            "fields" => [
                                "keyword" => [
                                    "type" => "keyword",
                                    "ignore_above" => 256
                                ]
                            ]
                        ],
                        "geoip" => [
                            "dynamic" => "true",
                            "properties" => [
                                "ip" => [
                                    "type" => "ip"
                                ],
                                "latitude" => [
                                    "type" => "half_float"
                                ],
                                "location" => [
                                    "type" => "geo_point"
                                ],
                                "longitude" => [
                                    "type" => "half_float"
                                ]
                            ]
                        ],
                        "host" => [
                            "properties" => [
                                "name" => [
                                    "type" => "text",
                                    "norms" => false,
                                    "fields" => [
                                        "keyword" => [
                                            "type" => "keyword",
                                            "ignore_above" => 256
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "httpversion" => [
                            "type" => "text",
                            "norms" => false,
                            "fields" => [
                                "keyword" => [
                                    "type" => "keyword",
                                    "ignore_above" => 256
                                ]
                            ]
                        ],
                        "ident" => [
                            "type" => "text",
                            "norms" => false,
                            "fields" => [
                                "keyword" => [
                                    "type" => "keyword",
                                    "ignore_above" => 256
                                ]
                            ]
                        ],
                        "input" => [
                            "properties" => [
                                "type" => [
                                    "type" => "text",
                                    "norms" => false,
                                    "fields" => [
                                        "keyword" => [
                                            "type" => "keyword",
                                            "ignore_above" => 256
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "rawrequest" => [
                            "type" => "text",
                            "norms" => false,
                            "fields" => [
                                "keyword" => [
                                    "type" => "keyword",
                                    "ignore_above" => 256
                                ]
                            ]
                        ],
                        "referrer" => [
                            "type" => "text",
                            "norms" => false,
                            "fields" => [
                                "keyword" => [
                                    "type" => "keyword",
                                    "ignore_above" => 256
                                ]
                            ]
                        ],
                        "request" => [
                            "type" => "text",
                            "norms" => false,
                            "fields" => [
                                "keyword" => [
                                    "type" => "keyword",
                                    "ignore_above" => 256
                                ]
                            ]
                        ],
                        "response" => [
                            "type" => "text",
                            "norms" => false,
                            "fields" => [
                                "keyword" => [
                                    "type" => "keyword",
                                    "ignore_above" => 256
                                ]
                            ]
                        ],
                        "timestamp" => [
                            "type" => "text",
                            "norms" => false,
                            "fields" => [
                                "keyword" => [
                                    "type" => "keyword",
                                    "ignore_above" => 256
                                ]
                            ]
                        ],
                        "verb" => [
                            "type" => "text",
                            "norms" => false,
                            "fields" => [
                                "keyword" => [
                                    "type" => "keyword",
                                    "ignore_above" => 256
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Запрос конфигурируется отдельно при каждом запросе к сервису
     * Можно отправить запрос без параметров client
     */
    public function configureRequest(array $params = []): array
    {
        $request_config = [ 
            'client' => [
                'connect_timeout' => 1, // Ожидание подключения (сек)
                'timeout' => 1 // Ожидане выполнения запроса (сек)
        
            ]
        ];

        return array_merge($params, $request_config);
    }
}