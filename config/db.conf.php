<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 19-1-24
 * Time: 下午4:06
 */

return [
    'mysql' => [
        'pool_size' => '3',     //连接池大小
        'pool_get_timeout' => 0.5, //当在此时间内未获得到一个连接，会立即返回。（表示所以的连接都已在使用中）
        'master' => [
            'host' => '127.0.0.1',   //数据库ip
            'port' => 3306,          //数据库端口
            'user' => 'root',        //数据库用户名
            'password' => 'david', //数据库密码
            'database' => 'studio',   //默认数据库名
            'timeout' => 0.5,       //数据库连接超时时间
            'charset' => 'utf8mb4', //默认字符集
            'strict_type' => true,  //ture，会自动表数字转为int类型
        ],
        'slave' => [
            [
                'host' => '127.0.0.1',   //从数据库1ip
                'port' => 3306,          //从数据库1端口
                'user' => 'root',        //从数据库1用户名
                'password' => 'david', //从数据库1密码
                'database' => 'studio',   //默认数据库名
                'timeout' => 0.5,       //数据库连接超时时间
                'charset' => 'utf8mb4', //默认字符集
                'strict_type' => true,  //ture，会自动表数字转为int类型
            ],
            [
                'host' => '127.0.0.1',   //从数据库2ip
                'port' => 3306,          //从数据库2端口
                'user' => 'root',        //从数据库2用户名
                'password' => 'david', //数据库密码
                'database' => 'studio',   //默认数据库名
                'timeout' => 0.5,       //数据库连接超时时间
                'charset' => 'utf8mb4', //默认字符集
                'strict_type' => true,  //ture，会自动表数字转为int类型
            ]
        ],
    ],
    'redis' => [
        'host' => '127.0.0.1',   //数据库ip
        'port' => 6379,          //数据库端口
        'timeout' => 0.5,       //数据库连接超时时间
        'pool_size' => '3',     //连接池大小
        'pool_get_timeout' => 0.5, //当在此时间内未获得到一个连接，会立即返回。（表示所以的连接都已在使用中）
    ],
    'mc' => [
        'host' => '127.0.0.1',   //数据库ip
        'port' => 11211,          //数据库端口
        'timeout' => 0.5,       //数据库连接超时时间
        'pool_size' => '3',     //连接池大小
        'pool_get_timeout' => 0.5, //当在此时间内未获得到一个连接，会立即返回。（表示所以的连接都已在使用中）
    ],
];