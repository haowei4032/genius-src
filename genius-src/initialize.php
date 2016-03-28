<?php

/**
 * User: Howay
 * Date: 2015/12/4 0004
 * Time: 21:46
 */

namespace {

    use Genius\Object;
    use Genius\Exception\InvaildException;

    ini_set('display_errors', true);
    error_reporting(E_ALL);

    defined('APP_ENV') or define('APP_ENV', null);
    defined('APP_ROOT') or define('APP_ROOT', dirname(__DIR__));
    defined('GENIUS_DEBUG') or define('GENIUS_DEBUG', false);
    defined('GENIUS_ROOT') or define('GENIUS_ROOT', __DIR__);

    define('GENIUS_VERSION', '1.0');
    define('GENIUS_COMMAND_LINE', PHP_SAPI == 'CLI' ? true : false);

    abstract class Genius
    {
        protected static $aliases = [];

        public static function printf()
        {
            echo call_user_func_array([__CLASS__, 'sprintf'], func_get_args());
        }

        /**
         * @param string $format
         * @param array $args
         * @return string
         */
        public static function sprintf($format, $args)
        {
            foreach ($args as $assoc => $value) {
                if (!is_numeric($value) && !is_string($value)) continue;
                $format = str_replace('{' . $assoc . '}', strval($value), $format);
            }

            return strval($format);
        }

        /**
         * @param string $alias
         * @return string
         */
        public static function getAlias($alias)
        {
            $group = explode('/', $alias);
            $base = array_shift($group);
            $path = implode($group);

            if (isset(self::$aliases[$alias])) return self::$aliases[$alias];
            if (isset(self::$aliases[$base])) return self::$aliases[$base] . '/' . $path;
            return null;
        }

        /**
         * @param string $alias
         * @param string $path
         */
        public static function setAlias($alias, $path)
        {
            self::$aliases[$alias] = $path;
        }

        /**
         * @param null|string $env
         * @throws InvaildException
         * @return void
         */
        public static function userConfig($env = APP_ENV)
        {
            $env = strval($env);
            $file = APP_ROOT . '/config/config.php';
            if (!is_file($file)) throw new InvaildException(sprintf('File not found %s', $file));
            $list = require(APP_ROOT . '/config/config.php');
            if (!is_array($list)) throw new InvaildException('Configuration file to return type must be an array');
            $object = new Object($list);
            return $object->get($env);
        }

        /**
         * @return string
         */
        public static function parseURI()
        {
            $subdirectory =
                dirname($_SERVER['SCRIPT_FILENAME']) === $_SERVER['DOCUMENT_ROOT'] ?
                    '' :
                    substr(dirname($_SERVER['SCRIPT_FILENAME']), strlen($_SERVER['DOCUMENT_ROOT']));

            $uri = strval(strpos($_SERVER['REQUEST_URI'], '?') !== false ?
                strstr($_SERVER['REQUEST_URI'], '?', true) :
                $_SERVER['REQUEST_URI']);

            return substr($uri, strlen($subdirectory));
        }

        /**
         * @param string $name
         * @return void
         * @throws InvaildException
         */
        public static function getComponents($name)
        {
            $components = Genius::userConfig()->components;
            if (!property_exists($components, $name)) throw new InvaildException(sprintf('Undefined components: %s("%s")', __METHOD__, $name));
            return $components->$name;
        }

    }

    abstract class Data
    {
        const UNIT_STORAGE = 0x1;
        const UNIT_TIME = 0x2;

        /**
         * @param int|double $number
         * @param int $convert [optional]
         * @param int $fixed [optional]
         * @return string
         */
        public static function convert($number, $convert = self::UNIT_STORAGE, $fixed = 7)
        {
            $i = 0;
            switch ($convert) {
                case self::UNIT_STORAGE:
                    $unit = ['Byte', 'KB', 'MB', 'GB', 'TB', 'PB'];
                    while ($number >= 1024) {
                        $number /= 1024;
                        $i++;
                    }
                    return sprintf('%.' . $fixed . 'f', $number) . $unit[$i];
                    break;
                case self::UNIT_TIME:
                    $unit = ['ms', 's', 'minutes', 'hours', 'day'];
                    $offset = 1000;
                    while ($number >= $offset) {
                        $number /= $offset;
                        $i++;
                        if ($i >= 4) $i = 4;
                        $offset = $i < 4 ? 60 : 12;
                    }
                    return sprintf('%.' . $fixed . 'f', $number) . $unit[$i];
                    break;
                default:
                    return null;
            }
        }

    }

    class InlineAction
    {
        public $action = null;

        /**
         * @param Genius\Controller $controller
         * @param string $action
         * @param array $parameters
         */
        public function __construct(Genius\Controller $controller, $action, array $parameters = [])
        {

        }

    }
}

namespace Genius {

    use Genius;
    use Genius\Event\Passer;
    use Genius\View\Render;
    use Genius\Exception\InvaildException;

    abstract class Application
    {
        private static $stack = [];
        public static $route;

        public static function init()
        {
            Passer::run();

            list($controller, $actionID, $arguments) = Route::transform();
            $file = APP_ROOT . '/controllers/' . $controller . '.class.php';
            if (!is_file($file)) throw new Genius\Exception\InvaildException(sprintf('Class Controllers\%s not found', ucfirst($controller)));
            require APP_ROOT . '/controllers/' . $controller . '.class.php';

            $controllerID = '\\Controllers\\' . ucfirst($controller);
            if (!class_exists($controllerID)) throw new Genius\Exception\InvaildException();
            (new $controllerID())->prepare([$actionID, $arguments])->execute();

        }

        /**
         * @param string $assoc
         * @throws Genius\Exception\InvaildException
         * @return float
         */
        public static function elapsed($assoc)
        {
            if (!in_array($assoc, ['time', 'memory'])) throw new InvaildException($assoc);

            switch ($assoc) {
                case 'time':
                    if (empty(self::$stack[$assoc])) return self::$stack[$assoc] = round(microtime(true) * 1000);
                    $now = round(microtime(true) * 1000);
                    break;
                case 'memory':
                    if (empty(self::$stack[$assoc])) return self::$stack[$assoc] = memory_get_usage();
                    $now = memory_get_usage();
                    break;
            }

            return (float)round($now - self::$stack[$assoc]);

        }
    }

    final class Route
    {
        public static $controllerID;
        public static $actionID;
        public static $parameters;
        public static $namespace;

        /**
         * @throws InvaildException
         * @return array
         */
        public static function transform()
        {
            $URI = Genius::parseURI();
            $controllerID = 'index';
            $actionID = 'index';
            $arguments = [];

            $find = false;
            $compile_rules = static::compile();

            foreach ($compile_rules as $pattern => $group) {

                if (preg_match('/^' . $pattern . '$/', $URI, $matches)) {
                    $find = true;
                    array_shift($matches);
                    foreach ($group['arguments'] as $k => $assoc) {
                        $arguments[$assoc] = $matches[$k];
                    }

                    $controllerID = $group['controllerID'];
                    $actionID = $group['actionID'];
                    break;
                }
            }

            if (!$find) {

                if (preg_match('/\/(.+?)\/(.+)/', $URI, $matches)) {
                    array_shift($matches);
                    list($controllerID, $actionID) = $matches;
                    $arguments = $_GET;
                }

            }

            $controllerID = strtolower($controllerID);
            $actionID = strtolower($actionID);

            if (!preg_match('/^[a-zAZ_]/', $controllerID)) {
                throw new Genius\Exception\InvaildException(sprintf('Controllers\%s() class name must be in [a-zA-Z_] at the beginning of any string', ucfirst($controllerID)));
            }

            if (!preg_match('/^[a-zAZ_]/', $actionID)) {
                throw new Genius\Exception\InvaildException(sprintf('Controllers\%s::%s() method name must be in [a-zA-Z_] at the beginning of any string', ucfirst($controllerID), $actionID));
            }


            return [
                self::$controllerID = $controllerID,
                self::$actionID = strtolower($actionID),
                self::$parameters = $arguments];

        }

        private static function compile()
        {
            $rules = [];
            if (!empty(Genius::userConfig()->components->url->rules)) {
                $rules = (array)Genius::userConfig()->components->url->rules;
            }

            $compile_rules = [];

            foreach ($rules as $pattern => $path) {
                $pattern = self::filter($pattern);
                $arguments = [];
                if (preg_match_all('/\<(.+?)\:(.+?)\>/', $pattern, $matches)) {
                    list($finder, $parameter, $regexp) = $matches;
                    foreach ($finder as $k => $str) {
                        $pattern = sprintf(preg_quote(str_replace($str, '%s', $pattern), '/'), '(' . $regexp[$k] . ')');
                        $arguments[] = $parameter[$k];
                    }
                } else {

                    $pattern = preg_quote($pattern, '/');

                }

                list($controllerID, $actionID) = explode('/', $path);
                $compile_rules[$pattern] = [
                    'arguments' => $arguments,
                    'controllerID' => $controllerID,
                    'actionID' => $actionID
                ];
            }

            return $compile_rules;
        }

        private static function filter($pattern)
        {
            $group = explode('/', $pattern);
            foreach ($group as $k => $v) {
                if (empty($v)) unset($group[$k]);
            }
            return sprintf('/%s', implode($group));
        }

    }

    abstract class Controller extends Render
    {
        /**
         * @param string $view
         * @return Genius\View\ViewRender
         */
        public function getView($view)
        {
            if (Genius::getComponents('view')) {
                $viewrender = Genius::getComponents('view')->class;
                new $viewrender;
            }
            exit;
        }

        /**
         * @param array $group
         * @return $this
         */
        abstract public function prepare($group);

        /**
         * @return void
         */
        abstract public function execute();
    }

    class Object
    {

        /**
         * @param array $arguments [optional]
         */
        public function __construct(array $arguments = [])
        {
            if ($arguments) {
                foreach ($arguments as $ptr => $val) {
                    $this->$ptr = (is_array($val) ? new static($val) : $val);
                }
            }
        }

        /**
         * @param string $name
         * @return void
         */
        public function get($name)
        {
            return property_exists($this, $name) ? $this->$name : null;
        }

    }

}

namespace Genius\Controller {

    use Genius;
    use Genius\Object;
    use Genius\Controller;
    use Genius\Exception\InvaildException;

    abstract class General extends Controller
    {
        public function prepare($group)
        {
            list($actionID, $arguments) = $group;

            $group = explode('\\', get_class($this));
            $controllerID = array_pop($group);
            $namespace = implode('\\', $group);

            parent::$route = new Object(['controllerID' => $controllerID, 'actionID' => $actionID, 'namespace' => $namespace, 'parameters' => $arguments]);

            if (method_exists($this, '__initialize')) $this->__initialize();
            $method = new \ReflectionMethod($this, $actionID);
            $ptr = [];
            $missing = [];

            foreach ($method->getParameters() as $parameters) {
                $name = $parameters->getName();
                if (array_key_exists($name, $arguments)) {
                    $ptr[] = $arguments[$name];
                } elseif ($parameters->isDefaultValueAvailable()) {
                    $ptr[] = $parameters->getDefaultValue();
                } else {
                    $missing = [];
                }
            }

            if ($missing) throw new InvaildException();
            $result = call_user_func_array([$this, $actionID], $ptr);
            if ($result) if (!is_numeric($result) && !is_string($result)) throw new InvaildException(Genius::sprintf('Method {namespace}\{controllerID}::{actionID}() return data type error', [
                'namespace' => $namespace,
                'controllerID' => $controllerID,
                'actionID' => $actionID
            ]));
            $this->text = ob_get_clean() . ($result ? strval($result) : '');
            return $this;
        }

        public function execute()
        {
            echo $this->text;
        }
    }

    abstract class Console extends Controller
    {
    }

    abstract class Api extends Controller
    {
    }
}

namespace Genius\Event {

    use Genius;
    use Genius\Application;
    use Genius\Exception\PHPVersionException;

    abstract class Passer
    {
        public static function run()
        {
            date_default_timezone_set(!empty(Genius::userConfig()->get('parameters')->get('timezone')) ?
                Genius::userConfig()->parameters->timezone :
                'Asia/Shanghai');

            spl_autoload_register([ __CLASS__, 'autoload']);

            Application::elapsed('time');
            Application::elapsed('memory');

            Genius::setAlias('/', GENIUS_ROOT);
            Genius::setAlias('~', APP_ROOT);

            set_error_handler([__CLASS__, 'error']);
            set_exception_handler([__CLASS__, 'exception']);
            register_shutdown_function([__CLASS__, 'shutdown']);

            if (version_compare(PHP_VERSION, '5.4.0', '<')) throw new PHPVersionException('5.4.0');
        }

        public static function exception($e)
        {
            if (ob_get_length()) ob_clean();
            $severity = !method_exists($e, 'getSeverity') ? E_ERROR : $e->getSeverity();
            $datetime = date('Y-m-d H:i:s');

            switch (error_reporting() & $severity) {
                case E_NOTICE:
                case E_USER_NOTICE:
                    $level = 'notice';
                    break;
                case E_WARNING:
                case E_USER_WARNING:
                    $level = 'warning';
                    break;
                case E_ERROR:
                case E_USER_ERROR:
                    $level = 'error';
                    break;
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    $level = 'deprecated';
                    break;
                default:
                    $level = 'info';
            }

            $list = [];

            foreach ($e->getTrace() as $value) {
                if (isset($value['file'])) {
                    array_push($list, '<li><em>' . $value['file'] . '</em> line ' . $value['line'] . '</li>');
                }
            }

            Genius::printf('<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>500 Internet Server Error</title>
<style type="text/css">
body{margin:0;padding:10px;font-family:arial,Helvetica,sans-serif;font-size:13px}
.hide{display:none;}
.exception{min-width:700px;max-width:750px;box-sizing:border-box;background:#eee;display:inline-block;}
.inside{padding:0 20px 10px 20px;}
.exception h1{margin:0;padding:35px 20px 20px 20px;line-height:20px;position:relative;display:block;font-weight:normal;color:#f00;font-size:20px;border-bottom:solid 1px #ccc;}
.exception h1 span{position:absolute;right:20px;background:#ccc;padding:4px 10px;color:#666;font-size:.5rem;line-height:1;}
.fixed {font-size:12px;display:block;background:#e0e0e0;margin:0;padding:0 20px;list-style:none;border-top:solid 1px #ddd;}
.fixed li{padding:10px 0;line-height:20px;height:20px;color:#444;display:inline-block;margin-right:10px;}
.fixed li span{display:inline-block;font-size:12px;line-height:16px;height:16px;margin:2px 0 2px 10px;border-radius:2px;padding:0 10px;background:#999;color:#fff;}
.fixed li:last-child{margin:0;}
.fixed li span.passed{background:#090;}
.fixed li span.wraning{background:#e90;}
.fixed li span.highlight{background:#e00;}
.exception .message{line-height:1.8;color:#333;padding:30px 0;}
.exception .message em{color:#f00;text-decoration:underline;}
.exception .message span.level{text-transform: capitalize;border-radius:2px;color:#fff;line-height:18px;padding:0 6px;display:inline-block;height:18px;font-size:12px;margin-right:10px;}
.exception span.info{background:#999;}
.exception span.notice{background:#090;}
.exception span.warning{background:#e90;}
.exception span.error{background:#e00;}
.exception span.deprecated{background:#c60;text-decoration:line-through;}
.exception .footer{color:#444;font-style:italic;}
.exception .footer .breakpoint{}
.exception .footer ul.list{padding-left:30px;}
.exception .footer ul.list li{line-height:1.65;}
.exception .footer .list li em{text-decoration:underline;}
</style>
</head>

<body>
<div class="exception">
<h1><strong>Genius Exception</strong><span class="datatime">{datetime}</span></h1>
<div class="inside">
<div class="message"><span class="level {level}">{level}</span>{message}</div>
<div class="footer">
<div class="breakpoint">
<span>Breakpoint list</span>
<ul class="list">
{list}
</ul>
</div>
</div>
</div>
<ul class="fixed">
<li><label>Genius</label><span>{genius_version}</span></li>
<li><label>PHP</label><span>{php_version}</span></li>
<li><label>Status</label><span class="highlight">500</span></li>
<li><label>Route</label><span>{route}</span></li>
<li><label>Memory</label><span>{memory}</span></li>
<li><label>Time</label><span>{time}</span></li>
</ul>
</div>
</body>
</html>', [
                'datetime' => $datetime,
                'level' => $level,
                'message' => $e->getMessage(),
                'list' => implode($list),
                'genius_version' => GENIUS_VERSION,
                'php_version' => PHP_VERSION,
                'route' => sprintf('%s/%s', Genius\Route::$controllerID, Genius\Route::$actionID),
                'memory' => \Data::convert(Application::elapsed('memory'), \Data::UNIT_STORAGE, 2),
                'time' => \Data::convert(Application::elapsed('time'), \Data::UNIT_TIME, 0)
            ]);
            exit; //中止脚本

        }

        public static function error($errno, $errstr, $errfile, $errline)
        {
            self::exception(new \ErrorException($errstr, 0, $errno, $errfile, $errline));
        }

        public static function shutdown()
        {
            $error = error_get_last();
            if (in_array($error['type'], [E_USER_NOTICE, E_USER_WARNING, E_USER_ERROR, E_NOTICE, E_WARNING, E_ERROR]))
                self::error($error['type'], $error['message'], $error['file'], $error['line']);
        }

        /**
         * @param string $class
         */
        public static function autoload($class)
        {
            $group = explode('\\', $class);
            var_dump($group);
            exit;
        }
    }
}

namespace Genius\Exception {

    use ErrorException;

    class InvaildException extends ErrorException
    {
    }

    class PHPVersionException extends ErrorException
    {
    }
}

namespace Genius\View {

    use Genius;
    use Genius\Application;

    abstract class ViewRender
    {
        abstract public function run();

        abstract public function runArgs(array $stack);

    }

    abstract class Render extends Application
    {
        /**
         * @param string $view
         * @param array $parameters [optional]
         * @return string
         */
        public function render($view, array $parameters = [])
        {
            return $this->getView($view)->runArgs($parameters);
        }
    }
}