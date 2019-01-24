<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8032c32899a72b27d2494d6f3af6e164
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Pool\\' => 5,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Pool\\' => 
        array (
            0 => __DIR__ . '/../..' . '/pool',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8032c32899a72b27d2494d6f3af6e164::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8032c32899a72b27d2494d6f3af6e164::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
