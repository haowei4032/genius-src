<?php

abstract class Genius
{
    const VERSION = '0.0.1';

    protected static $aliases = [];

    public static function getVersion()
    {
        return static::VERSION;
    }

    /**
     * @param string $alias
     * @return string
     */
    public static function getAlias($alias)
    {
        $group = explode('/', $alias);
        $base = array_shift($group);
        $path = implode('/', $group);
        if (isset(static::$aliases[$alias])) return static::$aliases[$alias];
        if (isset(static::$aliases[$base])) return static::$aliases[$base] . '/' . $path;
        return null;
    }

    /**
     * @param string $alias
     * @param string $value
     */
    public static function setAlias($alias, $value)
    {
        static::$aliases[$alias] = $value;
    }


}