<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit80dd491da014323d92b6ece4fa43f5db
{
    public static $prefixesPsr0 = array (
        'C' => 
        array (
            'CFPropertyList' => 
            array (
                0 => __DIR__ . '/..' . '/rodneyrehm/plist/classes',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit80dd491da014323d92b6ece4fa43f5db::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}