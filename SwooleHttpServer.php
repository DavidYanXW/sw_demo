<?php

/**
 * Created by PhpStorm.
 * User: david
 * Date: 19-1-4
 * Time: 下午3:43
 */
class SwooleHttpServer
{

    private $_conf = [];
    /**
     * @var \Swoole\Http\Server
     */
    private $_server;

    public function __construct($conf)
    {
        $this->_conf = $conf;
        $this->_server = new Swoole\Http\Server("0.0.0.0", 9513);
        $this->_server->set(
            [
                'worker_num' => 4,
                'daemonize' => true,    // daemon
                'log_level' => SWOOLE_LOG_INFO,
            ]
        );
        $this->_server->on("start", [$this, "onStart"]);
        $this->_server->on("request", [$this, "onRequest"]);
        $this->_server->on("close", [$this, "onClose"]);

        $this->_server->start();
    }

    public function onStart(\Swoole\Http\Server $server)
    {
        echo __METHOD__ . "||" . $server->host . ":" . $server->port . PHP_EOL;
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

        // db
        $db = new mysqli($this->_conf['mysql']['host'], $this->_conf['mysql']['user'], $this->_conf['mysql']['password'],
            $this->_conf['mysql']['database'], $this->_conf['mysql']['port']);
        if ($db->connect_errno) {
            $response->write("failed,errno:" . $db->connect_errno . "||" . $db->connect_error . $html_char);
        } else {
            $result = $db->query("select * from demo");
            $data = $result->fetch_all();
            $response->write(json_encode($data) . $html_char);
        }
        //@important 显式关闭
        $db->close();


        // mc
        $mc = new Memcached();
        $mc->addServer($this->_conf['mc']['host'], $this->_conf['mc']['port']);
        $add = $mc->add("mc_key", "mc_value");
        $get = $mc->get("mc_key");
        //@important 显式关闭
        $mc->quit();
        $response->write($add."||".$get.$html_char);

        // redis
        $redis = new Redis();
        $redis->connect($this->_conf['redis']['host'], $this->_conf['redis']['port']);
        $pong = $redis->ping();
        $response->write($pong.$html_char);
        $set = $redis->set("hello", "world", 5*60);
        $get = $redis->get("hello");
        //@important 显式关闭
        $redis->close();
        $response->write("set:".$set."||get:".$get.$html_char);

    }

    public function onClose()
    {
        echo __METHOD__ . PHP_EOL;
    }


}

$conf = include "config/db.conf.php";
$http_server = new SwooleHttpServer($conf);
