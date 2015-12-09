<?php

/**
 * User: Howay
 * Date: 2015/12/4 0004
 * Time: 21:46
 */

namespace {

    ini_set('display_errors', true);
    error_reporting(E_ALL);

    defined('APP_ROOT') or define('APP_ROOT', dirname(__DIR__));
    defined('GENIUS_DEBUG') or define('GENIUS_DEBUG', false);
    defined('GENIUS_ROOT') or define('GENIUS_ROOT', __DIR__);

    define('GENIUS_VERSION', '1.0');
    define('GENIUS_COMMAND_LINE', in_array(strtolower(PHP_SAPI), ['cli']));

    abstract class Genius
    {
        protected static $aliases = [];

        public static function init()
        {
        }

        /**
         * @param string $format
         * @param array $args
         * @return string
         */
        public static function printf($format, $args)
        {
            foreach ($args as $assoc => $value) {
                if (!is_numeric($value) && !is_string($value)) continue;
                $format = str_replace('{' . $assoc . '}', strval($value), $format);
            }

            return $format;
        }

        /**
         * @param string $alias
         * @return null|string
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
            switch($convert)
            {
                case self::UNIT_STORAGE:
                    $unit = ['Byte', 'KB', 'MB', 'GB', 'TB', 'PB'];
                    while($number >= 1024)
                    {
                        $number /= 1024;
                        $i++;
                    }
                    return sprintf('%.'.$fixed.'f', $number) . $unit[$i];
                    break;
                case self::UNIT_TIME:
                    $unit = ['ms', 's', 'minutes', 'hours', 'day'];
                    $offset = 1000;
                    while($number >= $offset)
                    {
                        $number /= $offset;
                        $i++;
                        if($i >= 4) $i = 4;
                        $offset = $i < 4 ? 60 : 12;
                    }
                    return sprintf('%.'.$fixed.'f', $number) . $unit[$i];
                    break;
            }
        }

    }
}

namespace Genius {

    use Genius;
    use Genius\Event\Passer;
    use Genius\View\Compiler;
    use Genius\Exception\Assoc;

    abstract class Application extends Genius
    {
        private static $stack = [];
        public $controllerID = null;
        public $actionID = null;

        public function __construct()
        {
        }

        /**
         * @return void
         */
        public static function init()
        {
            Passer::run();
            require APP_ROOT . '/controllers/index.class.php';
            $controllerID = '\\controllers\\index';
            (new $controllerID())->prepare([])->execute();
        }

        /**
         * @param string $assoc
         * @throws Genius\Exception\Assoc
         * @return float
         */
        public static function elapsed($assoc)
        {
            if (!in_array($assoc, ['time', 'memory'])) {
                throw new Assoc($assoc);
            }

            switch ($assoc)
            {
                case 'time':
                    if (empty(self::$stack[$assoc])) return self::$stack[$assoc] = round(microtime(true) * 1000);
                    $now = round(microtime(true) * 1000);
                    break;
                case 'memory':
                    if (empty(self::$stack[$assoc])) return self::$stack[$assoc] = memory_get_usage();
                    $now = memory_get_usage();
                    break;
            }

            return (float) round($now - self::$stack[$assoc]);

        }
    }

    abstract class Route
    {
    }

    abstract class Controller extends Compiler
    {
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

}

namespace Genius\Controller {

    use Genius\Controller;

    abstract class General extends Controller
    {
        public function prepare($group)
        {
            self::$controllerID = 'index';
            self::$actionID = 'index';

            $this->index();
            return $this;
        }

        public function execute()
        {
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
    use Genius\Exception\PHPVersion;

    abstract class Passer
    {
        public static function run()
        {
            Application::elapsed('time');
            Application::elapsed('memory');

            Application::setAlias('@root', APP_ROOT);
            date_default_timezone_set('Asia/Shanghai');

            set_error_handler([__CLASS__, 'error']);
            set_exception_handler([__CLASS__, 'exception']);
            register_shutdown_function([__CLASS__, 'shutdown']);

            $version = '5.4.0';
            if (version_compare(PHP_VERSION, $version, '<')) throw new PHPVersion($version);
        }

        public static function exception($e)
        {
            ob_clean();
            $severity = $e->getSeverity();
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
                default:
                    $level = 'info';
            }

            $list = [];

            foreach ($e->getTrace() as $value) {
                if (isset($value['file'])) {
                    array_push($list, '<li><em>' . $value['file'] . '</em> line ' . $value['line'] . '</li>');
                }
            }

            echo Genius::printf('<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>500 Internet Server Error</title>
<style type="text/css">
body{margin:0;padding:10px;font-family:arial,Helvetica,sans-serif;font-size:13px}
.hide{display:none;}
.exception{min-width:700px;box-sizing:border-box;background:#eee;display:inline-block;}
.inside{padding:0 20px 10px 20px;}
.exception h1{margin:0;padding:35px 20px 20px 20px;line-height:20px;position:relative;display:block;font-weight:normal;color:#f00;font-size:20px;border-bottom:solid 1px #ccc;}
.exception h1 span{position:absolute;right:20px;background:#ccc;padding:4px 10px;color:#fff;font-size:.5rem;line-height:1;}
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
<li><label>Status</label><span class="{status}">{code}</span></li>
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
            'status' => 'highlight',
            'code' => 500,
            'route' => 'index/index',
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
    }
}

namespace Genius\Exception {

    use Exception;
    use ErrorException;

    class Info extends Exception
    {
        /**
         * @return int
         */
        final public function getSeverity()
        {
            return 10;
        }
    }

    class Assoc extends ErrorException
    {
    }

    class PHPVersion extends ErrorException
    {
        /**
         * Constructs the ErrorException
         * @param string $message [optional]
         * @param int $code [optional]
         * @param int $severity [optional]
         * @param string $filename [optional]
         * @param int $lineno [optional]
         * @param Exception $previous [optional]
         */
        public function __construct($message = "", $code = 0, $severity = 1, $filename = __FILE__, $lineno = __LINE__, $previous = NULL)
        {
            parent::__construct('PHP version not less than ' . $message, $code, $severity, $filename, $lineno, $previous);
        }
    }
}

namespace Genius\View {

    use Genius\Application;

    abstract class Compiler extends Application
    {
    }
}