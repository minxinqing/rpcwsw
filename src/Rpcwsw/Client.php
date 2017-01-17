<?php
namespace Rpcwsw;

class Client{

    private static $client;

    private static function init(){
        $host = config('rpcwsw.server.host', '');
        if (!$host) {
            throw new Exception\ClientException("rpcws.server.host is not set");
        }

        $port = config('rpcwsw.server.port');
        if (!$port) {
            throw new Exception\ClientException("rpcws.server.port is not set");
        }

        $client = new \swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);
        if (!$client->connect($host, $port))
        {
            Log::error('RPCSWS_ERROR', ['type' => 'connect error', 'code' => $client->errCode]);
        }

        self::$client = $client;
    }

    public static function api($uri, $params, $method = 'GET', $sync = true) {
        self::init();

        $data = [
            'api' => $uri,
            'params' => $params,
            'method' => $method,
            'sync' => $sync
        ];
        self::$client->send(json_encode($data));
        $response = self::$client->recv();
        if ($response) {
            $response = json_decode($response, true);
        }
        
        return $response;
    }

}