<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 19-1-23
 * Time: 下午2:55
 */
namespace Pool;

use Swoole\Coroutine\Channel;

/**
 *
 * @todo: 分库分表，超时，重连次数
 * Class pool
 */
class MysqlCoPool {

    private static $instance;
    /**
     * @var \Swoole\Coroutine\Channel
     */
    private $pool;  //连接池容器
    private $config;    // @todo:

    /**
     * @param null $config
     * @return MysqlCoPool
     * @throws RuntimeException
     */
    public static function getInstance($config = null) {
        if (empty(self::$instance)) {
            if (empty($config)) {
                throw new RuntimeException("paramter $config is empty");
            }
            self::$instance = new static($config);
        }

        return self::$instance;
    }


    /**
     * pool constructor.
     * @param $config
     * @throws RuntimeException
     */
    private function __construct($config){
        if (empty($this->pool)) {
            $this->config = $config;
            $this->pool = new Channel($config['pool_size']);
            for ($i = 0; $i < $config['pool_size']; $i++) {
                $mysql = new \mysqli($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
                if ($mysql == false) {
                    //连接失败，抛弃常
                    throw new RuntimeException("failed to connect mysql server.");
                } else {
                    //mysql连接存入channel
                    $this->put($mysql);
                }
            }
        }
    }

    /**
     * 句柄重新连接
     * @return \mysqli
     */
    public function handleReConnect() {
        $mysql = new \mysqli($this->config['host'], $this->config['user'], $this->config['password'], $this->config['database'], $this->config['port']);
        if ($mysql == false) {
            //连接失败，抛弃常
            throw new RuntimeException("failed to connect mysql server.");
        }

        return $mysql;
    }

    /**
     * @param $handle
     * @desc 句柄放入连接池
     */
    public function put($handle)
    {
        $this->pool->push($handle);
    }

    /**
     * @desc 获取一个连接，超时返回异常
     * @return \mysqli
     * @throws RuntimeException
     */
    public function get()
    {
        $handle = $this->pool->pop($this->config['pool_get_timeout']);
        if ($handle === false) {
            throw new RuntimeException("get handle failed, all connection is used");
        }
        return $handle;
    }

    /**
     * 连接回收
     * @param $handle
     */
    public function recycle($handle)
    {
        $this->put($handle);
    }

    /**
     * 连接池元素数量
     * @return int
     */
    public function getLength()
    {
        return $this->pool->length();
    }

}