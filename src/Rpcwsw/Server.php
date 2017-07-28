<?php

namespace Rpcwsw;

use Illuminate\Console\Command;
use Log;
use Request;
use App\Lib\Rpc;

class Server extends Command{

    protected $signature = 'rpcwsw:server {opt}';

    protected $description = 'rpcwsw启动文件';

    protected $pidFile;
    protected $logFile;
    protected $serverPid;

    public function __construct() {
        $this->pidFile = config('rpcwsw.server.pid_file');
        if (!$this->pidFile) {
            throw new Exception\ServerException("请设置主进程ID文件路径参数 pid_file");
        }

        $this->serverPid = is_file($this->pidFile) ? file_get_contents($this->pidFile) : 0;

        $this->logFile = config('rpcwsw.server.log_file');
        if (!$this->logFile) {
            throw new Exception\ServerException("请设置日志文件路径参数 log_file");
        }

        parent::__construct();
    }

    public function handle(){
        $opt = $this->argument('opt');
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
            default:
                $this->error('错误的指令：'.$opt);
                break;
        }
    }

    public function process($api, $params, $method = 'GET') {

        $request = Request::create($api, $method, $params);
        $v = app()['Illuminate\Contracts\Http\Kernel']->handle($request);
        $statusCode = $v->getStatusCode();
        if ($statusCode == 200) {
            return $v->getContent();
        } elseif ($statusCode == 405) {
            return json_encode([
                'code' => 100102,
                'message' => '请求方法错误',
                'data' => [],
            ]);
        } elseif ($statusCode == 404) {
            return json_encode([
                'code' => 100103,
                'message' => '服务不存在',
                'data' => [],
            ]);
        }

        Log::error('rpcsws error', ['type' => 'rpcsws_error', 'data' => []]);
        
        return json_encode(['code' => 100101, 'msg' => '未知错误', 'data' => []]);
        
    }

    public function start()
    {
        if (!empty($this->serverPid) and posix_kill($this->serverPid, 0))
        {
            $this->info("服务已经存在");
            exit;
        }

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
            
            'max_conn' => config('rpcwsw.server.max_conn', 1000), //最大连接数

            'open_cpu_affinity' => config('rpcwsw.server.open_cpu_affinity', 1), //是否CPU亲和
            'open_tcp_nodelay' => config('rpcwsw.server.open_tcp_nodelay', 1), //是否启用tcp_nodelay

            'tcp_defer_accept' => config('rpcwsw.server.tcp_defer_accept', 5), //是否启用tcp_nodelay
            'log_file' => $this->logFile,

            'heartbeat_check_interval' => config('rpcwsw.server.heartbeat_check_interval', 10),
            'heartbeat_idle_time' => config('rpcwsw.server.heartbeat_idle_time', 20),

            'dispatch_mode' => 3,

            // 'open_eof_check' => true, //打开EOF检测
            // 'package_eof' => "\r\n", //设置EOF
        ));

        $serv->on('start', function($serv){
            Log::debug("on start.");
            $this->setProcessName('swoole master');
            file_put_contents($this->pidFile, $serv->master_pid);
        });

        $serv->on('managerStart', function ($serv){
            Log::debug("on managerStart.");
            $this->setProcessName('swoole manager');
        });

        $serv->on('workerStart', function ($serv, $fd){
            Log::debug('on workerStart.', ['fd' => $fd]);
            $this->setProcessName('swoole worker');
        });

        $serv->on('connect', function ($serv, $fd){
            Log::debug('on connect.', ['fd' => $fd]);
        });

        $serv->on('receive', function ($serv, $fd, $from_id, $data) {
            $data = json_decode(gzuncompress($data), true);
            Log::debug('on receive.', [
                'work_fd' =>$fd,
                'from_id' => $from_id,
                'receive_data' =>$data
            ]);

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
            Log::debug('on task', [
                'task_id' => $task_id,
                'from_id' => $from_id,
                'task_data' => $data,
            ]);
            $result = $this->process($data['api'], $data['params'], $data['method']);
            $serv->finish(json_decode($result, true));
        });

        //处理异步任务的结果
        $serv->on('finish', function ($serv, $task_id, $data) {
            Log::debug('on finish', [
                'task_id' => $task_id,
                'task_data' => $data
            ]);

            if ($data['code'] != 0){
                Log::error('task error', $data);
            }
        });


        $serv->on('close', function ($serv, $fd) {
            Log::debug('on close', [
                'fd' => $fd
            ]);
        });

        $serv->start();
    }

    public function shutdown()
    {
        if(posix_kill($this->serverPid, SIGTERM)){
            $this->info('shutdown success');
        } else {
            $this->error('shutdown failed');
        }
    }

    public function reload()
    {
        if (posix_kill($this->serverPid, SIGUSR1)) {
            $this->info('reload success');
        } else {
            $this->error('reload failed');
        }
    }

    private function setProcessName($name)
    {
        if (function_exists('cli_set_process_title'))
        {
            @cli_set_process_title($name);
        }
        else
        {
            if (function_exists('swoole_set_process_name'))
            {
                @swoole_set_process_name($name);
            }
            else
            {
                trigger_error(__METHOD__ . " failed. require cli_set_process_title or swoole_set_process_name.");
            }
        }
    }
}
