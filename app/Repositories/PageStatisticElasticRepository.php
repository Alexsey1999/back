<?php

namespace App\Repositories;

use Elasticsearch\ClientBuilder;

use App\Interfaces\Pages\PageStatisticRepositoryInterface;

class PageStatisticElasticRepository implements PageStatisticRepositoryInterface
{

    const HITS_INDEX_NAME_PREFIX = 'pages-hits';
    const GOALS_INDEX_NAME_PREFIX = 'pages-goals';

    const CONNECTION_TIMEOUT = 2;
    const REQUEST_TIMEOUT = 2;

    private $hosts;
    private $client;

    public function __construct(array $hosts)
    {
        $this->hosts = $hosts;
        $this->client = ClientBuilder::create()->setRetries(1)->setHosts($this->hosts)->build();
    }


    public function saveHit(array $hit_data): bool
    {
        /**
         * Сформируем имя индекса
         */
        $current_date = date('Y.m.d');
        $index_name = self::HITS_INDEX_NAME_PREFIX . '-' . $current_date;

        $index_params = [
            'index' => $index_name
        ];

        /**
         * Проверим - существует ли индекс
         */
        $index_is_exists = $this->checkIndexExists($index_params);

        /**
         * Если не существует - получим параметры маппинга для индекса и создадим новый индекс
         */
        if (false === $index_is_exists) {
            $index_params['body'] = $this->getHitIndexParams();
            $index_is_exists = $this->createIndex($index_params);
        }

        /**
         * Сформиурем новый документ
         */
        $doc_mapping = $this->getHitDocumentMapping($hit_data);
        $document = [
            'index' => $index_name,
            'type' => '_doc',
            'body' => $doc_mapping
        ];

        return $this->save($document);
    }

    public function saveGoal(array $hit_data)
    {
        /**
         * Сформируем имя индекса
         */
        $current_date = date('Y.m.d');
        $index_name = self::GOALS_INDEX_NAME_PREFIX . '-' . $current_date;

        $index_params = [
            'index' => $index_name
        ];

        /**
         * Проверим - существует ли индекс уже
         */
        $index_is_exists = $this->checkIndexExists($index_params);

        /**
         * Если не существует - получим параметры маппинга для индекса и создадим новый индекс
         */
        if (false === $index_is_exists) {
            $index_params['body'] = $this->getGoalIndexParams();
            $index_is_exists = $this->createIndex($index_params);
        }

        $doc_mapping = $this->getGoalDocumentMapping($hit_data);
        $document = [
            'index' => $index_name,
            'type' => '_doc',
            'body' => $doc_mapping
        ];

        return $this->save($document);
    }

    private function save(array $document): bool
    {
        $request = $this->configureRequest($document);
        $res = $this->client->index($request);
        return isset($res['result']) && $res['result'] === 'created';
    }

    /**
     * Преобразует данные хита в mapping для elasticSearch
     */
    private function getHitDocumentMapping(array $hit_data): array
    {
        return [
            'tags' => ['pages', 'hit'],
            '@timestamp' => $this->getTimestamp(),
            'id' => $hit_data['hit_id'],
            'page_id' => $hit_data['page_id'],
            'vk_group_id' => (string)$hit_data['vk_group_id'],
            'vk_user_id' => (string)$hit_data['vk_user_id'],
            'vk_user_role' => $hit_data['vk_user_role'],
            'vk_ref' => $hit_data['vk_ref'],
            'vk_platform' => $hit_data['vk_platform'],
        ];
    }

    private function getGoalDocumentMapping(array $goal_data): array
    {
        return [
            'tags' => ['pages', 'goal'],
            '@timestamp' => $this->getTimestamp(),
            'type' => $goal_data['type'],
            'hit_id' => $goal_data['hit_id'],
            'page_id' => $goal_data['page_id'],
            'block_id' => $goal_data['block_id'],
            'button_id' => $goal_data['button_id'],
            'vk_group_id' => (string)$goal_data['vk_group_id'],
            'vk_user_id' => (string)$goal_data['vk_user_id'],
            'vk_user_role' => $goal_data['vk_user_role'],
            'vk_ref' => $goal_data['vk_ref'],
            'vk_platform' => $goal_data['vk_platform'],
        ];
    }

    private function getHitIndexParams(): array
    {
        return [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0
            ],
            'mappings' => [
                "dynamic_templates" => [
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
                    'id' => [
                        "type" => "text",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'vk_group_id' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'vk_user_id' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'vk_user_role' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'vk_ref' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'vk_platform' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'page_id' => [
                        "type" => "keyword",
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
        ];
    }

    private function getGoalIndexParams(): array
    {

        /**
         * Используем тип keyword - так как нам необходима аггрегация по полям - общее количество, группировки
         */

        return [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0
            ],
            'mappings' => [
                "dynamic_templates" => [
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
                    'type' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'hit_id' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'page_id' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'block_id' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'button_id' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'vk_group_id' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'vk_user_id' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'vk_user_role' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'vk_ref' => [
                        "type" => "keyword",
                        "norms" => false,
                        "fields" => [
                            "keyword" => [
                                "type" => "keyword",
                                "ignore_above" => 256
                            ]
                        ]
                    ],
                    'vk_platform' => [
                        "type" => "keyword",
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
        ];
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

    /**
     * Запрос конфигурируется отдельно при каждом запросе к сервису
     * Можно отправить запрос без параметров client
     */
    private function configureRequest(array $params = []): array
    {
        $request_config = [
            'client' => [
                'connect_timeout' => self::CONNECTION_TIMEOUT, // Ожидание подключения (сек)
                'timeout' => self::REQUEST_TIMEOUT // Ожидане выполнения запроса (сек)
            ]
        ];

        return array_merge($params, $request_config);
    }


    public function getSummary(array $page_ids): array
    {

        $data = [];

        $hits_result = $this->getHitsSummary($page_ids);
        $goals_result = $this->getGoalsSummary($page_ids);

        foreach ($page_ids as $page_id) {

            if (isset($hits_result[$page_id])) {
                $data[$page_id]['hits'] = $hits_result[$page_id]['hits'];
                $data[$page_id]['unique_user_count'] = $hits_result[$page_id]['unique_user_count'];
            } else {
                $data[$page_id]['hits'] = 0;
                $data[$page_id]['unique_user_count'] = 0;
            }

            if (isset($goals_result[$page_id])) {
                $data[$page_id]['goals'] = isset($goals_result[$page_id]['goals']) ? $goals_result[$page_id]['goals'] : 0;
                $data[$page_id]['lead'] = isset($goals_result[$page_id]['lead']) ? $goals_result[$page_id]['lead'] : 0;
                $data[$page_id]['subscription'] = isset($goals_result[$page_id]['subscription']) ? $goals_result[$page_id]['subscription'] : 0;
                $data[$page_id]['join_community'] = isset($goals_result[$page_id]['join_community']) ? $goals_result[$page_id]['join_community'] : 0;
                $data[$page_id]['url'] = isset($goals_result[$page_id]['url']) ? $goals_result[$page_id]['url'] : 0;
                $data[$page_id]['bot_add'] = isset($goals_result[$page_id]['bot_add']) ? $goals_result[$page_id]['bot_add'] : 0;
            }
        }

        return $data;
    }

    /**
     * @param array $page_ids - Массив ID страниц, для которых нужно получить статистику по целевым действиям
     */
    private function getGoalsSummary(array $page_ids): array
    {

        $ids = [];
        $data = [];

        foreach ($page_ids as $id) {
            $ids[] = [
                'match_phrase' => [ // Поле page_id соответствует запрошенному $page_id
                    'page_id' => $id
                ]
            ];

            $data[$id] = [
                'goals' => 0
            ];
        }

        $query_params = [
            'index' => 'pages-goals-*',
            'body' => [
                'size' => 0, // В ответе не нужны сами документы
                'query' => [
                    'bool' => [
                        'filter' => [
                            'terms' => [ // Ищем совпадения по списку ключевых слов
                                'page_id' => $ids
                            ]
                        ]
                    ]
                ],
                'aggs' => [
                    'pages' => [
                        'terms' => [
                            'field' => 'page_id',
                        ],
                        'aggs' => [
                            'types' => [
                                'terms' => [
                                    'field' => 'type'
                                ]
                            ],
                            'goals' => [
                                'value_count' => [
                                    'field' => 'page_id'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $request = $this->configureRequest($query_params);
        $resp = $this->client->search($request);


        if (!isset($resp['aggregations'])) {
            return $data;
        }

        foreach($resp['aggregations']['pages']['buckets'] as $bucket) {
            if (isset($data[$bucket['key']])) {
                $data[$bucket['key']]['goals'] = $bucket['goals']['value'];
                foreach($bucket['types']['buckets'] as $type) {
                    $data[$bucket['key']][$type['key']] = $type['doc_count'];
                }
            }
        }

        return $data;
    }

    /**
     * @param array $page_id - Массив ID страниц, для которых нужно получить статистику по просмотрам
     * @return array
     */
    private function getHitsSummary(array $page_ids): array
    {

        $ids = [];
        $data = [];

        foreach ($page_ids as $id) {
            $ids[] = [
                'match_phrase' => [ // Поле page_id соответствует запрошенному $page_id
                    'page_id' => $id
                ]
            ];

            $data[$id] = [
                'hits' => 0,
                'unique_user_count' => 0,
            ];
        }

        $query_params = [
            'index' => 'pages-hits-*',
            'body' => [
                'size' => 0, // В ответе не нужны сами документы
                'query' => [
                    'bool' => [
                        'filter' => [
                            'terms' => [
                                'page_id' => $ids
                            ]
                        ]
                    ]
                ],
                'aggs' => [
                    // Считаем уникальные vk_user_id - количество уникальных посетителей
                    'pages' => [
                        'terms' => [
                            'field' => 'page_id',
                        ],
                        'aggs' => [
                            'unique_user_count' => [
                                'cardinality' => [
                                    'field' => 'vk_user_id'
                                ]
                            ],
                            'hits' => [
                                'value_count' => [
                                    'field' => 'page_id'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];


        $request = $this->configureRequest($query_params);
        $resp = $this->client->search($request);

        if (!isset($resp['aggregations'])) {
            return $data;
        }

        foreach($resp['aggregations']['pages']['buckets'] as $bucket) {
            if (isset($data[$bucket['key']])) {
                $data[$bucket['key']]['hits'] = $bucket['hits']['value'];
                $data[$bucket['key']]['unique_user_count'] = $bucket['unique_user_count']['value'];
            }
        }

        return $data;
    }

    public function getMostActiveCommunitiesByHits(array $params = []): array
    {

        $size = isset($params['size']) && is_int($params['size']) ? $params['size'] : 100;
        return $this->getTopActiveCommunities([
            'index' => 'pages-hits-*',
            'size' => $size
        ]);
    }

    public function getMostActiveCommunitiesByGoals(array $params = []): array
    {
        $size = isset($params['size']) && is_int($params['size']) ? $params['size'] : 100;
        return $this->getTopActiveCommunities([
            'index' => 'pages-goals-*',
            'size' => $size
        ]);
    }

    private function getTopActiveCommunities(array $params = [])
    {
        $date_lte = (new \DateTime())->format('Y-m-d\TH:i:s.u'); // Верхняя граница временного диапазона
        $date_gte = (new \DateTime())->sub(new \DateInterval('P30D'))->format('Y-m-d\TH:i:s.u'); // Нижняя граница временного диапазона

        $query_params = [
            'index' => $params['index'],
            'body' => [
                'size' => 0, // В ответе не нужны сами документы
                'query' => [
                    'bool' => [
                        'must' => [],
                        'filter' => [
                            [
                                'range' => [
                                    '@timestamp' => [
                                        "format" => "strict_date_optional_time",
                                        "gte" => $date_gte,
                                        "lte" => $date_lte
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'aggs' => [
                    "communities" => [
                        "terms" => [
                          "field" => "vk_group_id",
                          "order" => [
                            "_count" => "desc"
                          ],
                          "size" => $params['size']
                        ]
                    ]
                ]
            ]
        ];

        $request = $this->configureRequest($query_params);
        $resp = $this->client->search($request);

        if (!isset($resp['aggregations']['communities']) || !isset($resp['aggregations']['communities']['buckets'])) {
            return [];
        }

        $communities_ids = array_map(function ($item) {
            return  intval($item['key']);
        }, $resp['aggregations']['communities']['buckets']);

        return $communities_ids;
    }

    /**
     * Получение списка id страниц с самым большим количеством просмотров
     */
    public function getMostActivePagesByViews(array $params = []): array
    {

        $size = isset($params['limit']) ? (int)$params['limit'] : 10;

        $query_params = [
            'index' => 'pages-hits-*',
            'body' => [
                'size' => 0, // В ответе не нужны сами документы
                // 'query' => [],
                'aggs' => [
                    "pages_ids" => [
                        "terms" => [
                          "field" => "page_id.keyword",
                          "order" => [
                            "_count" => "desc"
                          ],
                          "size" => $size
                        ]
                    ]          
                ]
            ]
        ];

        $request = $this->configureRequest($query_params);
        $resp = $this->client->search($request);

        if (!isset($resp['aggregations']['pages_ids']) || !isset($resp['aggregations']['pages_ids']['buckets'])) {
            return [];
        }

        $res = [];

        foreach($resp['aggregations']['pages_ids']['buckets'] as $bucket) {
            $key = preg_replace('/\?.{0,}/', '', $bucket['key']);
            $res[$key] = $bucket['doc_count'];
        }

        return $res;
    }

    /**
     * Получение списка id страниц с самым большим количеством достигнутых целевых действий
     */
    public function getMostActivePagesByGoals(array $params = []): array
    {
        $size = isset($params['limit']) ? (int)$params['limit'] : 10;

        $query_params = [
            'index' => 'pages-goals-*',
            'body' => [
                'size' => 0, // В ответе не нужны сами документы
                // 'query' => [],
                'aggs' => [
                    "pages_ids" => [
                        "terms" => [
                          "field" => "page_id.keyword",
                          "order" => [
                            "_count" => "desc"
                          ],
                          "size" => $size
                        ]
                    ]          
                ]
            ]
        ];

        $request = $this->configureRequest($query_params);
        $resp = $this->client->search($request);

        if (!isset($resp['aggregations']['pages_ids']) || !isset($resp['aggregations']['pages_ids']['buckets'])) {
            return [];
        }

        $res = [];

        foreach($resp['aggregations']['pages_ids']['buckets'] as $bucket) {
            $key = preg_replace('/\?.{0,}/', '', $bucket['key']);
            $res[$key] = $bucket['doc_count'];
        }

        return $res;
    }
}
