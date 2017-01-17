<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use App\Lib\Rpc;

class RpcClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rpc:client';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'RPC客户端';

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
        if ($a) {
            echo 'aaa';
        }

        exit;
        $rs = Rpc::api('math/add', ['num1' => 3,'num2' =>3], 'GET');
        sleep(10);
        dd($rs);
    }

}
