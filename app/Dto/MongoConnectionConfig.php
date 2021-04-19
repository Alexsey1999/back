<?php

namespace App\Dto;

class MongoConnectionConfig 
{
    public string $auth_type;
    public string $db;
    public string $host;
    public string $port;
    public string $user;
    public string $ca_file_path;
    public string $pem_file_path;

    public function __construct(array $config)
    {
        $this->auth_type = $config['auth_type'];
        $this->db = $config['db'];
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->user = isset($config['user']) ? $config['user'] : '';
        $this->ca_file_path = isset($config['ca_file']) ? $config['ca_file'] : '';
        $this->pem_file_path = isset($config['pem_file']) ? $config['pem_file'] : '';
    }
}