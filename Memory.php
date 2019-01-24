<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 19-1-3
 * Time: 下午8:43
 */

class Memory {

    public function __construct()
    {
        $a = new \Swoole\Table(100);
        $a->column("data", \Swoole\Table::TYPE_INT, 1);
        $a->count();

    }

}


