<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use Request;
use App\Lib\Rpc;

class SwooleServer extends Command
{
    public static $num1 = 0;
    public static $workname = false;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'swoole服务端';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $serv = new \Swoole\Server("0.0.0.0", 9501);
        $serv->set(array(
            'worker_num' => 1,   //工作进程数量
            'daemonize' => false, //是否作为守护进程
            'task_worker_num' => 0,
            'max_request' => 100,

            'heartbeat_check_interval' => 10,
            'heartbeat_idle_time' => 10,

            // 'dispatch_mode' => 3,
        ));

        $serv->on('connect', function ($serv, $fd){
            if (self::$workname == false) {
                self::$workname = str_random(10);
            }
            Log::info("fd:".$fd.". Client:Connect.");
        });

        $serv->on('receive', function ($serv, $fd, $from_id, $data) {
self::$num1 ++;

            Log::info(self::$workname.' num:'.self::$num1);
            $data = json_decode($data, true);
            $result = [
                'data' => 'empty',
                'code' => -1,
            ];

            $result = ['code' => 0, 'msg' => '', 'data' => []];
            if ($data['sync']) {
                Log::info('work.fd:'.$fd.';from_id:'.$from_id.". receive data:", $data);
                $result = $this->process($data['api'], $data['params'], $data['method']);
            } else {
                $result = json_encode($result);
                Log::info('task work.fd:'.$fd.';from_id:'.$from_id.". receive data:", $data);
                $serv->task($data);
            }
            
            $serv->send($fd, $result);
        });

        $serv->on('task', function ($serv, $task_id, $from_id, $data) {
            sleep(3);
            $result = $this->process($data['api'], $data['params'], $data['method']);
            $serv->finish($result);
        });

        //处理异步任务的结果
        $serv->on('finish', function ($serv, $task_id, $data) {
            Log::info('task finish:'.$data);
        });


        $serv->on('close', function ($serv, $fd) {
            // Rpc::close();
            Log::info("fd:".$fd.".  Client: Close.");
        });
        $serv->start();
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
                'msg' => '请求方法错误',
                'data' => [],
            ]);
        }

        Log::error('SOA API ERROR:'.$v);
        return json_encode(['code' => 101, 'msg' => '系统错误', 'data' => []]);
        
    }
}
