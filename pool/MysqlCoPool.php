<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 19-1-23
 * Time: 下午2:55
 */
namespace Pool;

use Db\MysqlHandle;
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
                $mysql_handle = new MysqlHandle();
                $res_conn = $mysql_handle->connect($config);
                if($res_conn === true) {
                    //mysql_handle存入channel
                    $this->put($mysql_handle);
                }
            }
        }
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
     * @desc 获取一个元素，超时返回异常
     * @return MysqlHandle
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