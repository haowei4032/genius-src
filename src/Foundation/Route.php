<?php

namespace Genius\Foundation;

class Route
{
    /**
     * @var Route
     */
    private static $instance;
    private static $method = false;
    private static $found = false;
    private static $lists = [];

    public static function any($name, $callback)
    {
        return static::$lists[] = [
            'name' => $name,
            'verb' => null,
            'callback' => $callback
        ];
    }

    public static function get($name, $callback)
    {
        return static::$lists[] = [
            'name' => $name,
            'verb' => 'GET',
            'callback' => $callback
        ];
    }

    public static function post($name, $callback)
    {
        return static::$lists[] = [
            'name' => $name,
            'verb' => 'POST',
            'callback' => $callback
        ];
    }

    public static function put($name, $callback)
    {
        return static::$lists[] = [
            'name' => $name,
            'verb' => 'PUT',
            'callback' => $callback
        ];
    }

    public static function delete($name, $callback)
    {
        return static::$lists[] = [
            'name' => $name,
            'verb' => 'DELETE',
            'callback' => $callback
        ];
    }

    public function setPrefix($prefix)
    {
        return $this;
    }

    public static function prefix($prefix)
    {
        if (!static::$instance) static::$instance = new static();
        return (static::$instance)->setPrefix($prefix);

    }

    public function setGroup($callback)
    {
        call_user_func_array($callback, [$this]);
        return $this;
    }

    public static function group($callback)
    {
        if (!static::$instance) static::$instance = new static();
        return (static::$instance)->setGroup($callback);
    }

    /**
     * @param int $code
     * @param $callback
     */
    public static function error($code, $callback)
    {

    }

    /**
     * @throws \ErrorException
     */
    public static function dispatch()
    {
        foreach (static::$lists as $next) {
            $name = $next['name'];
            $verb = $next['verb'];
            $callback = $next['callback'];

            $pattern = $name;
            $parse = parse_url($_SERVER['REQUEST_URI']);
            if (preg_match_all('#<(\w+?)(?:\:(.+?)|)>#', $pattern, $match)) {
                foreach ($match[0] as $k => $block) {
                    $pattern = str_replace($block, sprintf('(%s)', !$match[2][$k] ? '.+?' : $match[2][$k]), $pattern);
                }
            }

            if (preg_match_all('#^' . $pattern . '$#', $parse['path'], $match)) {
                static::$found = true;
                if (!$verb || $_SERVER['REQUEST_METHOD'] === $verb) {
                    static::$method = false;
                    $argv = [];
                    for ($i = 1; $i < count($match); $i++) $argv[] = $match[$i][0];
                    echo call_user_func_array($callback, $argv);
                    exit(0);
                }
            }
        }

        if (!static::$found) throw new \ErrorException('404 Not Found');
        if (!static::$method) throw new \ErrorException('405 Method Not Allowed');
    }
}