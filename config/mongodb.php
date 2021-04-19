<?php

return [
    'test' => [
        "auth_type" => "default",
        "host" => "localhost",
        "port" => "27017",
        "db" => "widgets"
    ],
    'prod' => [
        "auth_type" => "ssl",
        "host" => "138.201.137.75",
        "port" => "27017",
        "db" => "app",
        "user" => "CN=commonuser,OU=app,O=Senler,L=Kirov,ST=KirovOblast,C=RU",
        "ca_file" =>  __DIR__ . '/../mongodb-CA-cert.crt',
        "pem_file" => __DIR__ . '/../commonuser.pem'
    ]
];
