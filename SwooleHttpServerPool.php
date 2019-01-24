<?php

/**
 * Created by PhpStorm.
 * User: david
 * Date: 19-1-4
 * Time: 下午3:43
 */

include "vendor/autoload.php";
$mysql_config = [
    'host' => '127.0.0.1',   //数据库ip
    'port' => 3306,          //数据库端口
    'user' => 'root',        //数据库用户名
    'password' => 'david', //数据库密码
    'database' => 'studio',   //默认数据库名
    'timeout' => 0.5,       //数据库连接超时时间
    'charset' => 'utf8mb4', //默认字符集
    'strict_type' => true,  //true，会自动表数字转为int类型
    'pool_size' => '3',     //连接池大小
    'pool_get_timeout' => 0.5, //当在此时间内未获得到一个连接，会立即返回。（表示所以的连接都已在使用中）
];
$redis_config = [
    'host' => '127.0.0.1',   //数据库ip
    'port' => 6379,          //数据库端口
    'timeout' => 0.5,       //数据库连接超时时间
    'pool_size' => '3',     //连接池大小
    'pool_get_timeout' => 0.5, //当在此时间内未获得到一个连接，会立即返回。（表示所以的连接都已在使用中）
];
$mc_config = [
    'host' => '127.0.0.1',   //数据库ip
    'port' => 11211,          //数据库端口
    'timeout' => 0.5,       //数据库连接超时时间
    'pool_size' => '3',     //连接池大小
    'pool_get_timeout' => 0.5, //当在此时间内未获得到一个连接，会立即返回。（表示所以的连接都已在使用中）
];


class SwooleHttpServer
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



        // mc:
//        $mc = new Memcached();
//        $mc->addServer("127.0.0.1", 11211);
//        $add = $mc->add("mc_key", "mc_value");
//        $get = $mc->get("mc_key");
//        $mc->quit();
//        $response->write($add . "||" . $get . $html_char);



    }

    public function onClose()
    {
        echo __METHOD__ . PHP_EOL;
    }


}



$mysql_instance = \Pool\MysqlPool::getInstance($mysql_config);
$redis_instance = \Pool\RedisPool::getInstance($redis_config);


$http_server = new SwooleHttpServer($mysql_instance, $redis_instance);
