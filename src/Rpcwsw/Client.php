<?php
namespace Rpcwsw;

class Client{

    private static $clientList;

    private $client;

    public function __construct($serverName, $config) {
        if (!$config[$serverName]['host']) {
            throw new \Exception($serverName . ' 的host配置不存在');
        }
        $host = $config[$serverName]['host'];

        if (!$config[$serverName]['port']) {
            throw new \Exception($serverName . ' 的port配置不存在');
        }
        $port = $config[$serverName]['port'];

        $swClient = new \Swoole\Client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);

        if (!$swClient->connect($host, $port, 10)) {
            throw new \Exception('服务连接失败，code:'.$swClient->errCode);
        }

        $this->client = $swClient;
    }

    public static function instance($serverName, $config = []){
        if (!self::$clientList[$serverName]) {
            !$config && $config = config('rpcwsw.instance');
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
            $sendStr = gzcompress(json_encode($data));
            $this->client->send($sendStr."\r\n\r\n");

            $response = '';

            // 重复拉取返回数组，直至检测到结束符（swoole貌似有bug，open_eof_check不生效）
            while(true) {
                $response .= $this->client->recv();

                if (substr($response, -4) == "\r\n\r\n") {
                    $response = substr($response, 0, strlen($response) - 4);
                    $response = gzuncompress($response);
                    break;
                }
            }

            if ($response) {
                $response = json_decode($response, true);
            }
        } catch(\Exception $ex) {
            $response = ['code' => 100101, 'msg' => '未知错误', 'data' => []];
        }
        
        return $response;
    }

}
