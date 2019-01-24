<?php

include "vendor/autoload.php";

/**
 * Created by PhpStorm.
 * User: david
 * Date: 19-1-4
 * Time: 下午3:43
 */

class SwooleHttpServerFamily
{

    private $_server;
    private $_db_conf;

    public function __construct($db_conf)
    {
        $this->_db_conf = $db_conf;

        $this->_server = new Swoole\Http\Server("0.0.0.0", 9512);
        $this->_server->set(
            [
                'worker_num' => 4,
//                'daemonize' => true,    // daemon
                'log_level' => SWOOLE_LOG_INFO, // SWOOLE_LOG_INFO
            ]
        );
        $this->_server->on("start", [$this, "onStart"]);
        $this->_server->on("workerStart", [$this, "onWorkerStart"]);
        $this->_server->on("request", [$this, "onRequest"]);
        $this->_server->on("close", [$this, "onClose"]);

        $this->_server->start();
    }

    public function onStart(\Swoole\Http\Server $server)
    {
        echo __METHOD__ . "||" . $server->host . ":" . $server->port . PHP_EOL;
    }
    public function onWorkerStart(\Swoole\Http\Server $server) {
        try {
            \Pool\MysqlCoPool::getInstance($this->_db_conf);
        } catch (\Exception $e) {
            //初始化异常，关闭服务
            echo $e->getMessage() . PHP_EOL;
            $server->shutdown();
        } catch (\Throwable $throwable) {
            //初始化异常，关闭服务
            echo $throwable->getMessage() . PHP_EOL;
            $server->shutdown();
        }
    }

    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        if ($request->server['request_uri'] == '/favicon.ico') {
            $response->status(404);
            $response->end();
            return;
        }
        //
        $html_char = "<br>";

        // string
        $hello = "hello world";
        $response->write($hello . $html_char);

        // array
        $arr = [1 => __CLASS__, 2 => __METHOD__];
        $response->write(json_encode($arr) . $html_char);

        // mysql
        try {
            $pool = \Pool\MysqlCoPool::getInstance($this->_db_conf);
            $mysql = $pool->get();
            defer(function () use ($mysql,$response,$html_char) {
                //利用defer特性，可以达到协程执行完成，归还$mysql到连接池
                \Pool\MysqlCoPool::getInstance()->put($mysql);
                $response->write("当前可用连接数：" . \Pool\MysqlCoPool::getInstance()->getLength().$html_char);
            });
            $result = $mysql->query("select * from demo");
            if(in_array($mysql->errno, [2006, 2013])) {
                $mysql = $pool->handleReConnect();
                $result = $mysql->query("select * from demo");
            }
            $data = $result->fetch_all();
            $response->write(json_encode($data).$html_char);
        } catch (\Exception $e) {
            $response->write($e->getMessage().$html_char);
        }

    }

    public function onClose()
    {
        echo __METHOD__ . PHP_EOL;
    }
}
$conf = include "config/db.conf.php";


$http_server = new SwooleHttpServerFamily($conf["mysql"]);
