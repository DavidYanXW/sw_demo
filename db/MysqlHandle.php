<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 19-1-26
 * Time: 下午3:47
 */

namespace Db;

class MysqlHandle
{

    /**
     * @var \mysqli
     */
    private $master;   //主数据库连接
    private $slave;     //从数据库连接list
    private $config;

    /**
     * @param $config
     * @return boolean
     * @throws RuntimeException
     * @desc 连接mysql
     */
    public function connect($config)
    {
        //创建主数据连接
        $master = new \mysqli();
        $res = $master->connect($config['master']['host'], $config['master']['user'], $config['master']['password'], $config['master']['database'], $config['master']['port']);
        if ($res === false) {
            //连接失败，抛弃常
            throw new RuntimeException($master->connect_error, $master->errno);
        } else {
            //存入master资源
            $this->master = $master;
        }

        //创建从数据库连接
        foreach ($config['slave'] as $conf) {
            $slave = new \mysqli();
            $res = $slave->connect($conf['host'], $conf['user'], $conf['password'], $conf['database'], $conf['port']);
            if ($res === false) {
                //连接失败，抛弃常
                throw new RuntimeException($slave->connect_error, $slave->errno);
            } else {
                //存入slave资源
                $this->slave[] = $slave;
            }
        }

        $this->config = $config;
        return true;
    }

    /**
     * @param $type
     * @param $index
     * @return MySQL
     * @desc 单个数据库重连
     */
    public function reconnect($type, $index)
    {
        //通过type判断是主还是从
        if ('master' == $type) {
            //创建主数据连接
            $master = new \mysqli();
            $res = $master->connect($this->config['master']['host'], $this->config['master']['user'], $this->config['master']['password'], $this->config['master']['database'], $this->config['master']['port']);
            if ($res === false) {
                //连接失败，抛弃常
                throw new RuntimeException($master->connect_error, $master->errno);
            } else {
                //更新主库连接
                $this->master = $master;
            }
            return $this->master;
        }

        //创建从数据连接
        $slave = new \mysqli();
        $conf = $this->config['slave'][$index];
        $res = $slave->connect($conf['host'], $conf['user'], $conf['password'], $conf['database'], $conf['port']);
        if ($res === false) {
            //连接失败，抛弃常
            throw new RuntimeException($slave->connect_error, $slave->errno);
        } else {
            //更新对应的从库连接
            $this->slave[$index] = $slave;
        }
        return $slave;
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @desc 利用__call,实现操作mysql,并能做断线重连等相关检测
     */
    public function __call($name, $arguments)
    {
        $sql = $arguments[0];
        $res = $this->chooseDb($sql);
        /**
         * var $db \mysqli
         */
        $db = $res['db'];
        $result = call_user_func_array([$db, $name], $arguments);
        if (false === $result) {
            if (in_array($db->errno, [2006, 2013])) { //断线重连
                echo "mysql reconnect" . PHP_EOL;
                $db = $this->reconnect($res['type'], $res['index']);
                $result = call_user_func_array([$db, $name], $arguments);
                return $this->parseResult($result, $db);
            }

            if (!empty($db->errno)) {  //有错误码，则抛出弃常
                throw new RuntimeException($db->error, $db->errno);
            }
        }
        return $this->parseResult($result, $db);
    }

    /**
     * @param $result
     * @param $db \mysqli
     * @return array
     * @desc 格式化返回结果：查询：返回结果集，插入：返回新增id, 更新删除等操作：返回影响行数
     * @todo: 更新无变化affected_rows=0
     */
    public function parseResult($result, $db)
    {
        if ($result === true) {
            return [
                'affected_rows' => $db->affected_rows,
                'insert_id' => $db->insert_id,
            ];
        }
        return $result;
    }

    /**
     * @param $sql
     * @desc 根据sql语句，选择主还是从
     * @ 判断有select 则选择从库， insert, update, delete等选择主库
     * @return array
     */
    protected function chooseDb($sql)
    {
        //查询语句，随机选择一个从库
        if ('select' == strtolower(substr($sql, 0, 6))) {
            if (1 == count($this->slave)) {
                $index = 0;
            } else {
                $index = array_rand($this->slave);
            }
            return [
                'type' => 'slave',
                'index' => $index,
                'db' => $this->slave[$index],
            ];
        }

        return [
            'type' => 'master',
            'index' => 0,
            'db' => $this->master
        ];
    }



}