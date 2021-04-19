<?php

namespace App\Db;

use MongoDB\Client;
use App\Dto\MongoConnectionConfig;
use App\Interfaces\DbInterface;

class MongoDB implements DbInterface
{

    const CONNECTION_TIMEOUT = 3000; // 3 секунды

    private $connection;
    private $db_name;

    public function __construct(MongoConnectionConfig $config)
    {

        $this->db_name = $config->db;

        $host = $config->host;
        $port = $config->port;
        return $host;
        // if ($config->auth_type === 'ssl') {

        //     $url = "mongodb://" . rawurlencode($config->user) . "@{$host}:{$port}/";
        //     $options = [
        //         'authSource' => '$external',
        //         'authMechanism' => 'MONGODB-X509',
        //         'ssl' => true,
        //         'connectTimeoutMS' => self::CONNECTION_TIMEOUT
        //     ];

        //     $authCredentials = [
        //         'allow_invalid_hostname' => true,
        //         'ca_file' => $config->ca_file_path,
        //         'pem_file' => $config->pem_file_path,
        //         'weak_cert_validation' => true
        //     ];

        //     $client = new Client($url, $options, $authCredentials);
        //     $db = $client->{$this->db_name};

        //     $this->connection = $db;

        // } else {

        //     $url = "mongodb://{$host}:{$port}";
        //     $options = [
        //         'connectTimeoutMS' => self::CONNECTION_TIMEOUT
        //     ];
        //     $client = new Client($url, $options);
        //     $db = $client->{$this->db_name};

        //     $this->connection = $db;

        // }
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getCollection(string $collection_name)
    {
        return $this->getConnection()->{$collection_name};
    }
}
