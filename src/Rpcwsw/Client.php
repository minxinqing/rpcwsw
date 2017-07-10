<?php
namespace Rpcwsw;

class Client{

    private static $clientList;

    private $client;

    public function __construct($serverName, $config) {
        if (!$config[$serverName]['host']) {
            throw new Exception($serverName . ' 的host配置不存在');
        }
        $host = $config[$serverName]['host'];

        if (!$config[$serverName]['port']) {
            throw new Exception($serverName . ' 的port配置不存在');
        }
        $port = $config[$serverName]['port'];

        $swClient = new \Swoole\Client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);

        if (!$swClient->connect($host, $port, 10)) {
            throw new Exception('服务连接失败，code:'.$swClient->errCode);
        }

        $this->client = $swClient;
    }

    public static function instance($serverName, $config = []){
        if (!self::$clientList[$serverName]) {
            !$config && $config = config('rpcwsw.instance');
            echo 'new client',"\r\n";
            self::$clientList[$serverName] = new self($serverName, $config);
        }
        
        return self::$clientList[$serverName];
    }

    public function api($uri, $params, $method = 'GET', $sync = true) {

        $data = [
            'api' => $uri,
            'params' => $params,
            'method' => $method,
            'sync' => $sync
        ];
        try{
            $this->client->send(gzcompress(json_encode($data)));
        
            $response = $this->client->recv();
            if ($response) {
                $response = json_decode($response, true);
            }
        } catch(\Exception $ex) {
            $response = ['code' => 100101, 'msg' => '未知错误', 'data' => []];
        }
        
        return $response;
    }

}
