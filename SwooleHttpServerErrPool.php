<?php

/**
 * Created by PhpStorm.
 * User: david
 * Date: 19-1-4
 * Time: 下午3:43
 */

include "vendor/autoload.php";

/**
 * 错误例子：（worker之间由于进程隔离，无法共享资源、变量）
 * 多worker共用连接池
 * Class SwooleHttpServerPool
 */
class SwooleHttpServerPool
{

    private $_server;
    /**
     * @var \Pool\MysqlPool
     */
    private $_pool_instance = null;
    /**
     * @var \Pool\RedisPool
     */
    private $_redis_pool_instance = null;

    public function __construct(\Pool\MysqlPool $pool_instance, \Pool\RedisPool $redis_pool_instance)
    {
        $this->_pool_instance = $pool_instance;
        $this->_redis_pool_instance = $redis_pool_instance;

        $this->_server = new Swoole\Http\Server("0.0.0.0", 9512);
        $this->_server->set(
            [
                'worker_num' => 4,
//                'daemonize' => true,    // daemon
                'log_level' => SWOOLE_LOG_INFO, // SWOOLE_LOG_INFO
                'trace_flags' => SWOOLE_TRACE_CLOSE,
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
            $mysql = $this->_pool_instance->get();
            $result = @$mysql->query("select * from demo");
            if(in_array($mysql->errno, [2006, 2013])) {
                $mysql = $this->_pool_instance->handleReConnect();
                $result = $mysql->query("select * from demo");
            }
            $data = $result->fetch_all();
            $response->write(json_encode($data).$html_char);
        } catch (\Exception $e) {
            $response->write($e->getMessage().$html_char);
        }
        finally {
            $this->_pool_instance->put($mysql);
            $response->write("当前可用连接数：" . $this->_pool_instance->getLength().$html_char);
        }

        // redis
        try {
            $redis = $this->_redis_pool_instance->get();
            $pong = $redis->ping();
            $response->write($pong . $html_char);
            $set = $redis->set("hello", "world", 5 * 60);
            $get = $redis->get("hello");
            $response->write("set:" . $set . "||get:" . $get . $html_char);
        } catch (\Exception $e) {
            $response->write($e->getMessage().$html_char);
        }
        finally {
            // important
            $this->_redis_pool_instance->put($redis);
        }

    }

    public function onClose()
    {
        echo __METHOD__ . PHP_EOL;
    }


}

$conf = include "config/db.conf.php";

$mysql_instance = \Pool\MysqlPool::getInstance($conf["mysql"]);
$redis_instance = \Pool\RedisPool::getInstance($conf["redis"]);


$http_server = new SwooleHttpServerPool($mysql_instance, $redis_instance);
