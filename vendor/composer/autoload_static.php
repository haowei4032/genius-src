<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitacbef2c47b63ad48fd503b440ed64e76
{
    public static $prefixLengthsPsr4 = array (
        'G' => 
        array (
            'Genius\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Genius\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitacbef2c47b63ad48fd503b440ed64e76::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitacbef2c47b63ad48fd503b440ed64e76::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}