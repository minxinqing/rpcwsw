<?php
namespace Rpcwsw;

class Client{

    private static $clientList;

    private $client;

    public function __construct($serverName) {
        $host = config('rpcwsw.instance.'.$serverName.'.host', '0.0.0.0');

        $port = config('rpcwsw.instance.'.$serverName.'.port', 9527);

        $swClient = new \swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);

        if (!$swClient->connect($host, $port))
        {
            Log::error('RPCSWS_ERROR', ['type' => 'connect error', 'code' => $swClient->errCode]);
        }

        $this->client = $swClient;
    }

    public static function instance($serverName){
        return new self($serverName);
    }

    public function api($uri, $params, $method = 'GET', $sync = true) {

        $data = [
            'api' => $uri,
            'params' => $params,
            'method' => $method,
            'sync' => $sync
        ];
        $this->client->send(json_encode($data));
        $response = $this->client->recv();
        if ($response) {
            $response = json_decode($response, true);
        }
        
        return $response;
    }

}