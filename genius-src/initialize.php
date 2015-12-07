<?php

/**
 * User: Howay
 * Date: 2015/12/4 0004
 * Time: 21:46
 */

namespace {

    use Genius\Exception\Info;

    ini_set('display_errors', true);
    error_reporting(E_ALL);

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

            if(isset(self::$aliases[$alias])) return self::$aliases[$alias];
            if(isset(self::$aliases[$base])) return self::$aliases[$base] . '/' . $path;
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
         * @param string $message
         * @param int $statusCode
         * @throws Genius\Exception\Info
         * @return void
         */
        public static function trace($message, $statusCode = 200)
        {
            //ob_clean();
            throw new Info($message);
            $text = ob_get_clean();

            header('HTTP/1.1 '. $statusCode . ' OK');
            echo $text;
        }
    }
}

namespace Genius {

    use Genius;
    use Genius\Event\Passer;
    use Genius\View\Compiler;

    abstract class Application extends Genius
    {
        /**
         * @return void
         */
        public static function init()
        {
            Passer::run();
            require APP_ROOT . '/controllers/index.class.php';
            (new \controllers\index())->prepare([])->execute();
        }
    }

    abstract class Route
    {}

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
            $this->index();
            return $this;
        }

        public function execute()
        {}
    }

    abstract class Console extends Controller
    {}

    abstract class Api extends Controller
    {}
}

namespace Genius\Event {

    use Genius;
    use Genius\Application;

    abstract class Passer
    {
        public static function run()
        {
            Application::setAlias('@root', APP_ROOT);
            date_default_timezone_set('Asia/Shanghai');

            set_error_handler([__CLASS__, 'error']);
            set_exception_handler([__CLASS__, 'exception']);
            register_shutdown_function([__CLASS__, 'shutdown']);
        }

        public static function exception($e)
        {
            switch (error_reporting() & $e->getSeverity()) {
                case E_NOTICE:
                case E_USER_NOTICE:
                    $level = 'notice';
                    break;
                case E_WARNING:
                case E_USER_WARNING:
                    $level = 'warning';
                    break;
                case E_USER_ERROR:
                case E_ERROR:
                    $level = 'error';
                    break;
                default:
                    $level = 'info';
            }

            $list = [];

            foreach($e->getTrace() as $value)
            {
                if(isset($value['file'])) {
                    array_push($list, '<li><em>'.$value['file'].'</em> line '.$value['line'].'</li>');
                }
            }
            echo Genius::printf('<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>500 Internet Server Error</title>
<style type="text/css">
body{margin:0;padding:10px;}
.exception{padding:20px;min-width:700px;box-sizing:border-box;background:#eee;display:inline-block;font-family:arial,Helvetica,sans-serif;font-size:13px}
.exception h1{line-height:20px;position:relative;display:block;font-weight:normal;color:#f00;font-size:20px;}
.exception h1 span{position:absolute;right:0;background:#ccc;padding:4px 10px;color:#fff;font-size:.5rem;line-height:1;}
.exception .message{line-height:1.8;color:#333;padding:25px 0;border-top:solid 1px #aaa;}
.exception .message em{color:#f00;text-decoration:underline;}
.exception .message span.level{border-radius:2px;color:#fff;line-height:18px;padding:0 6px;display:inline-block;height:18px;font-size:12px;margin-right:10px;}
.exception span.info{background:#999;}
.exception span.notice{background:#090;}
.exception span.warning{background:#e90;}
.exception span.error{background:#e00;}
.exception .footer{color:#444;font-style:italic;}
.exception .footer ul.list{padding-left:30px;}
.exception .footer ul.list li{line-height:1.65;}
.exception .footer .list li em{text-decoration:underline;}
</style>
</head>

<body>
<div class="exception">
<h1><strong>Genius Debugger</strong><!--<span class="datetime"></span>--></h1>
<div class="message"><span class="level {level}">{levelstr}</span>{message}</div>
<div class="footer"><span>Breakpoint list</span><ul class="list">{list}</ul></div>
</div>
<!--[ Genius v1.0 ]-->
</body>
</html>', [
                'level' => $level,
                'levelstr' => ucfirst($level),
                'message' => $e->getMessage(),
                'list' => implode("\n", $list)]);
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

    class PHPVersion extends ErrorException
    {}
}

namespace Genius\View {

    use Genius\Application;

    abstract class Compiler extends Application
    {}
}