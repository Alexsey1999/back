<?php

return [
    'test' => [
        "hosts" => [
            "http://elastic:9200"
        ],
        "requestTimeout" => 1000,
        "pingTimeout" => 1000,
        "maxRetries" => 1
    ],
    'prod' => [
        "hosts" => [
            [
                'host' => '10.50.0.4',
                'user' => 'elastic',
                'pass' => 'eiwndf720iw'
            ],
            [
                'host' => '10.50.0.5',
                'user' => 'elastic',
                'pass' => 'eiwndf720iw'
            ],
            [
                'host' => '10.50.0.6',
                'user' => 'elastic',
                'pass' => 'eiwndf720iw'
            ]
        ]
    ]
];