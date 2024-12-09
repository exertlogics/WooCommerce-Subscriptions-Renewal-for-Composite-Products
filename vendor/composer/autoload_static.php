<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit473574ec73b8400d37a6e96daaba5204
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WSRCP\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WSRCP\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit473574ec73b8400d37a6e96daaba5204::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit473574ec73b8400d37a6e96daaba5204::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit473574ec73b8400d37a6e96daaba5204::$classMap;

        }, null, ClassLoader::class);
    }
}
