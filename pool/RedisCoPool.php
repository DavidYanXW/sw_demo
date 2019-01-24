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
 * @todo: 分库分表，超时，重连次数，断线重连
 * Class pool
 */
class RedisCoPool
{

    private static $instance;
    /**
     * @var Channel
     */
    private $pool;  //连接池容器
    private $config;

    /**
     * @param null $config
     * @return RedisCoPool
     * @throws RuntimeException
     */
    public static function getInstance($config = null)
    {
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
    private function __construct($config)
    {
        if (empty($this->pool)) {
            $this->config = $config;
            $this->pool = new Channel($config['pool_size']);
            for ($i = 0; $i < $config['pool_size']; $i++) {
                $handle = new \Redis();
                $handle->connect($config['host'], $config['port']);
                if($handle==false) {
                    //连接失败，抛弃常
                    throw new RuntimeException("failed to connect redis server.");
                }
                else {
                    $this->put($handle);
                }
            }
        }
    }

    /**
     * 句柄重新连接
     * @return \Redis
     */
    public function handleReConnect() {
        $handle = new \Redis();
        $handle->connect($this->config['host'], $this->config['port']);
        if ($handle == false) {
            //连接失败，抛弃常
            throw new RuntimeException("failed to connect redis server.");
        }
        return $handle;
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
     * @desc 获取一个连接，获取失败，返回异常
     * @return \Redis
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