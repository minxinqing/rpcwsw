<?php
namespace App\Lib;

class Rpc{

    private static $client;

    private static function init(){
        $client = new \swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);
        // $client = new \swoole_client(SWOOLE_SOCK_TCP);
        if (!$client->connect('www.soa.com', 9501))
        {
            exit("connect failed. Error: {$client->errCode}\n");
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

    public static function close() {
        self::$client->close();
    }
}