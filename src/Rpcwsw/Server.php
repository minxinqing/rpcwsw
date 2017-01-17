<?php

namespace Rpcwsw;

use Illuminate\Console\Command;
use Log;
use Request;
use App\Lib\Rpc;

class Server extends Command{

    protected $signature = 'rpcwsw:server {opt}';

    protected $description = 'rpcwsw启动文件';

    public function handle(){
        $opt = $this->arguments('opt');
        switch ($opt) {
            case 'start':
                $this->start();
                break;
            case 'shutdown':
                $this->shutdown();
                break;
            case 'reload':
                $this->reload();
                break;
            case 'restart':
                $this->shutdown();
                $this->start();
                break;
            
            default:
                $this->error('错误的指令：'.$opt);
                break;
        }
    }

    private function instance() {
        $host = config('rpcwsw.server.host', '0.0.0.0');
        $port = config('rpcwsw.server.port', '9527');

        $serv = new \Swoole\Server($host, $port, SWOOLE_PROCESS);
        $serv->set(array(
            'reactor_num' => config('rpcwsw.server.reactor_num', 8),   //线程数
            'worker_num' => config('rpcwsw.server.worker_num', 10),   //worker数量
            'task_worker_num' => config('rpcwsw.server.task_worker_num', 10), //异步worker数
            'max_request' => config('rpcwsw.server.max_request', 100), //worker最大请求数

            'backlog' => config('rpcwsw.server.backlog', 128),   //Listen队列长度
            'daemonize' => config('rpcwsw.server.daemonize', 1), //是否作为守护进程
            
            'max_conn' => config('rpcwsw.server.max_conn', 10000), //最大连接数

            'open_cpu_affinity' => config('rpcwsw.server.open_cpu_affinity', 1), //是否CPU亲和
            'open_tcp_nodelay' => config('rpcwsw.server.open_tcp_nodelay', 1), //是否启用tcp_nodelay

            'tcp_defer_accept' => config('rpcwsw.server.tcp_defer_accept', 5), //是否启用tcp_nodelay
            'log_file' => storage_path().'/log/swoole-server.log',

            'heartbeat_check_interval' => config('rpcwsw.server.heartbeat_check_interval', 10),
            'heartbeat_idle_time' => config('rpcwsw.server.heartbeat_idle_time', 20),

            'dispatch_mode' => 3,

            'open_eof_check' => true, //打开EOF检测
            'package_eof' => "\r\n", //设置EOF

        ));
        $serv->on('connect', function ($serv, $fd){
            
            // Log::info("fd:".$fd.". Client:Connect.");
        });

        $serv->on('receive', function ($serv, $fd, $from_id, $data) {
            // Log::info('work.fd:'.$fd.';from_id:'.$from_id.". receive data:", $data);
            $data = json_decode($data, true);

            $result = ['code' => 0, 'message' => '', 'data' => []];
            if ($data['sync']) {
                $result = $this->process($data['api'], $data['params'], $data['method']);
            } else {
                $result = json_encode($result);
                $serv->task($data);
            }
            
            $serv->send($fd, $result);
        });

        $serv->on('task', function ($serv, $task_id, $from_id, $data) {
            $result = $this->process($data['api'], $data['params'], $data['method']);
            $serv->finish($result);
        });

        //处理异步任务的结果
        $serv->on('finish', function ($serv, $task_id, $data) {
            // Log::info('task finish:'.$data);
        });


        $serv->on('close', function ($serv, $fd) {
            // Log::info("fd:".$fd.".  Client: Close.");
        });

        $serv->on('workerError', function($serv, $worker_id, $worker_pid, $exit_code)) {
            Log::error('RPCSWS_ERROR', ['type' => 'worker error', 'data' => [$worker_id, $worker_pid, $exit_code]]);
        }

        return $serv;
    }

    public function process($api, $params, $method = 'GET') {
        $request = Request::create($api, $method, $params);
        $v = app()['Illuminate\Contracts\Http\Kernel']->handle($request);
        $statusCode = $v->getStatusCode();
        if ($statusCode == 200) {
            return $v->getContent();
        } elseif ($statusCode == 405) {
            return json_encode([
                'code' => 102,
                'message' => '请求方法错误',
                'data' => [],
            ]);
        }

        Log::error('RPCSWS_ERROR', ['type' => 'rpc error', 'data' => $v]);
        return json_encode(['code' => 101, 'msg' => '系统错误', 'data' => []]);
        
    }

    public function start()
    {
        $this->instance()->start();
    }

    public function shutdown()
    {
        $this->instance()->shutdown();
    }

    public function reload()
    {
        $this->instance()->reload();
    }
}