<?php

/**
 * @link http://genius.haowei.me
 * @copyright Copyright (c) 2015 Genius Software LLC
 * @license http://genius.haowei.me/license
 */

namespace {

    use Genius\Object;
    use Genius\Exception\InvaildException;

    defined('APP_ENV') or define('APP_ENV', 'stable');
    defined('APP_ROOT') or define('APP_ROOT', dirname(__DIR__));
    defined('GENIUS_DEBUG') or define('GENIUS_DEBUG', false);
    defined('GENIUS_ROOT') or define('GENIUS_ROOT', __DIR__);

    define('GENIUS_VERSION', '0.0.1');
    define('GENIUS_COMMAND_LINE', PHP_SAPI == 'cli' ? true : false);

    ini_set('display_errors', GENIUS_DEBUG);
    error_reporting(E_ALL);

    abstract class Genius
    {
        /**
         * @var array
         */
        protected static $elapsed = [];
        /**
         * @var array
         */
        protected static $aliases = [];

        /**
         * @param string $alias
         * @return string
         */
        public static function getAlias($alias)
        {
            $group = explode('/', $alias);
            $base = array_shift($group);
            $path = implode('/', $group);

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
         * @return \Genius\Object
         */
        public static function userConfig($env = APP_ENV)
        {
            $env = strval($env);
            $file = APP_ROOT . '/config/config.php';
            if (!is_file($file)) throw new InvaildException(sprintf('File not found %s', $file));
            $list = require(APP_ROOT . '/config/config.php');
            if (!is_array($list)) throw new InvaildException('Configuration file to return type must be an array');
            return (new Object($list))->get($env);
        }

        /**
         * @param string $name
         * @return mixed
         * @throws InvaildException
         */
        public static function getComponents($name)
        {
            $components = Genius::userConfig()->get('components');
            if (!property_exists($components, $name)) {
                if (!GENIUS_DEBUG) return null;
                throw new InvaildException(sprintf('Undefined components: %s', $name));
            }
            return $components->get($name);
        }

        /**
         * @param string $assoc
         * @throws Genius\Exception\InvaildException
         * @return mixed
         */
        public static function elapsed($assoc)
        {
            switch ($assoc) {
                case 'time':
                    return !isset(static::$elapsed[$assoc]) ?
                        static::$elapsed[$assoc] = round(microtime(true) * 1000) :
                        round(microtime(true) * 1000) - static::$elapsed[$assoc];
                    break;
                case 'memory':
                    return !isset(static::$elapsed[$assoc]) ?
                        static::$elapsed[$assoc] = memory_get_usage() :
                        memory_get_usage() - static::$elapsed[$assoc];
                    break;
                case 'file':
                    return get_included_files();
                    break;
                default:
                    throw new InvaildException($assoc);
            }
        }

    }
}

namespace Genius {

    use Genius;
    use Genius\Exception\InvaildException;

    abstract class Application extends Genius
    {

        protected static $elapsed = [];
        private static $callable = true;

        /**
         * @var \Genius\Object
         */
        protected $route;

        /**
         * @var \Genius\Response
         */
        protected $response;

        /**
         * @return mixed
         * @throws InvaildException
         */
        public static function init()
        {

            if (!static::$callable) return null;

            if (version_compare(PHP_VERSION, '5.4.0', '<')) {
                trigger_error('PHP version cannot be less than 5.4.0', E_USER_ERROR);
            }

            static::$callable = false;

            $timezone = Genius::userConfig()->get('parameters')->get('timezone');
            date_default_timezone_set(!empty($timezone) ? Genius::userConfig()->parameters->timezone : 'Asia/Shanghai');

            spl_autoload_register([__CLASS__, 'autoLoad']);

            Genius::elapsed('time');
            Genius::elapsed('memory');

            Genius::setAlias('/', GENIUS_ROOT);
            Genius::setAlias('~', APP_ROOT);

            Application::elapsed('time');
            Application::elapsed('memory');
            Genius\Utils\Directory::create(Genius::getAlias(Genius::getComponents('log')->get('path')));

            set_error_handler('Genius\Utils\Debugger::Error');
            set_exception_handler('Genius\Utils\Debugger::Exception');
            register_shutdown_function('Genius\Utils\Debugger::Shutdown');

            return Route::resolve()->run();

        }

        /**
         * @param string $class
         * @return mixed
         * @throws InvaildException
         */
        private static function autoLoad($class)
        {
            $group = explode('\\', $class);
            list($prefix) = $group;

            switch ($prefix) {
                case 'Genius':
                    array_shift($group);
                    $class = array_pop($group);
                    $path = implode('/', array_map('strtolower', $group));
                    $file = GENIUS_ROOT . '/' . $path . '/' . $class . '.class.php';
                    break;
                default:
                    $class = array_pop($group);
                    $path = implode('/', array_map('strtolower', $group));
                    $file = APP_ROOT . '/' . $path . '/' . $class . '.class.php';
            }

            if (!is_file($file)) {
                throw new InvaildException(sprintf('File not found %s', $file));
            }

            require($file);

        }
    }

    abstract class Controller extends Application
    {

        /**
         * @return bool
         */
        abstract public function __before();

        /**
         * @return bool
         */
        abstract public function __after();

        /**
         * @return Genius\View\Render
         */
        abstract public function getView();

        /**
         * @param string $view
         * @return Genius\View\Render
         */
        abstract public function setView($view);

        /**
         * @param string $view
         * @param array $parameters [optional]
         * @return string
         */
        abstract public function render($view, array $parameters = []);

        /**
         * @param string $html
         * @param array $parameters [optional]
         * @return string
         */
        abstract public function renderHtml($html, array $parameters = []);

        /**
         * @param string $view
         * @param array $parameters [optional]
         * @return string
         */
        abstract public function renderFile($view, array $parameters = []);

        /**
         * @param string $action
         * @param array $parameter
         * @return $this
         */
        abstract public function prepare($action, array $parameter);

        /**
         * @return void
         */
        abstract public function execute();
    }

    class InlineAction
    {

        /**
         * @var Genius\Object
         */
        protected $reference = null;

        /**
         * @param Genius\Controller $mixed
         * @param string $action
         * @param array $parameters
         */
        public function __construct(Genius\Controller $mixed, $action, array $parameters = [])
        {

        }

        public function checkVaild()
        {

        }

        public function run()
        {

        }

    }

    class Route
    {
        /**
         * @var string
         */
        protected $uri;
        /**
         * @var array
         */
        protected $arguments = [];

        private function __construct()
        {
        }

        public static function resolve()
        {
            $self = new static;
            if ((array)Genius::getComponents('url')) {
                if (!GENIUS_COMMAND_LINE) {
                    $subdirectory =
                        dirname($_SERVER['SCRIPT_FILENAME']) == $_SERVER['DOCUMENT_ROOT'] ?
                            '' :
                            substr(dirname($_SERVER['SCRIPT_FILENAME']), strlen($_SERVER['DOCUMENT_ROOT']));
                    $REQUEST_URI =
                        ($offset = strpos($_SERVER['REQUEST_URI'], '?')) ?
                            substr($_SERVER['REQUEST_URI'], 0, $offset) :
                            $_SERVER['REQUEST_URI'];
                    $self->uri = substr($REQUEST_URI, strlen($subdirectory));
                    if (($self->uri == $_SERVER['SCRIPT_NAME']) || ($self->uri == substr($_SERVER['SCRIPT_NAME'], strlen($subdirectory)))) {
                        $self->uri = '/';
                    }
                } else {
                    $argv = $_SERVER['argv'];
                    array_shift($argv);
                    $self->uri = count($argv) ? array_shift($argv) : '/';
                }

            } else {
                $self->uri = !empty($_GET['route']) ? $_GET['route'] : '/';
            }

            return $self;

        }

        public function run()
        {

            $this->arguments = [];
            $uri = $this->uri;
            if (($url = (array)Genius::getComponents('url'))) {
                $list = [];
                foreach ($url as $pattern => $path) {
                    $this->arguments = [];
                    $pattern = preg_replace_callback('/\<(.+?):(.+?)\>/', function ($range) {
                        $regexp = array_pop($range);
                        array_push($this->arguments, array_pop($range));
                        return sprintf('(%s)', $regexp);
                    }, $pattern);
                    $list[$pattern] = [
                        'class' => $path,
                        'arguments' => $this->arguments
                    ];
                }

                $this->arguments = [];
                foreach ($list as $pattern => $group) {
                    if (preg_match('#^\/?' . $pattern . '$#', $this->uri, $matches)) {
                        $uri = $group['class'];
                        array_shift($matches);
                        if ($matches) {
                            foreach ($group['arguments'] as $key => $name) {
                                $this->arguments[$name] = $matches[$key];
                            }
                        }
                    }
                }
            }

            $class = ['Controllers'];
            $group = explode('/', $uri);
            foreach ($group as $value) {
                if ($value) {
                    array_push($class, $value ? ucfirst($value) : 'Index');
                }
            }
            if (count($class) < 2) array_push($class, 'Index');
            $action = count($class) > 2 ? array_pop($class) : 'Index';
            $class = implode("\\", $class);
            return (new $class)->prepare($action, $this->arguments)->execute();
        }

    }

    class Object
    {

        /**
         * @param array $arguments [optional]
         */
        public function __construct(array $arguments = [])
        {
            if ($arguments) {
                foreach ($arguments as $property => $value) {
                    $this->$property = is_array($value) ? new static($value) : $value;
                }
            }
        }

        /**
         * @param string $property
         * @return void
         */
        public function get($property)
        {
            return property_exists($this, $property) ? $this->$property : null;
        }

    }

    class Response
    {
        /**
         * @var string
         */
        public $body;

        /**
         * @var array
         */
        public $header;

        public function __construct()
        {

        }

        /**
         * @return Genius\Response;
         */
        public function setDownload()
        {
            return $this;
        }

        /**
         * @return Genius\Response;
         */
        public function format($format)
        {
            return $this;
        }

        /**
         * @param string $charset
         * @return Genius\Response;
         */
        public function charset($charset)
        {
            return $this;
        }

        /**
         * @param string $text
         * @return Genius\Response;
         */
        public function context($text)
        {
            return $this;
        }

        /**
         * @return mixed
         */
        public function build()
        {
            $list = [

                100 => 'Continue',
                101 => 'Switching Protocols',
                102 => 'Processing',
                200 => 'OK',
                201 => 'Created',
                202 => 'Accepted',
                203 => 'Non-Authoritative Information',
                204 => 'No Content',
                205 => 'Reset Content',
                206 => 'Partial Content',
                207 => 'Multi-Status',
                300 => 'Multiple Choices',
                301 => 'Moved Permanently',
                302 => 'Move temporarily',
                303 => 'See Other',
                304 => 'Not Modified',
                305 => 'Use Proxy',
                306 => 'Switch Proxy',
                307 => 'Temporary Redirect',
                400 => 'Bad Request',
                401 => 'Unauthorized',
                402 => 'Payment Required',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                406 => 'Not Acceptable',
                407 => 'Proxy Authentication Required',
                408 => 'Request Timeout',
                409 => 'Conflict',
                410 => 'Gone',
                411 => 'Length Required',
                412 => 'Precondition Failed',
                413 => 'Request Entity Too Large',
                414 => 'Request-URI Too Long',
                415 => 'Unsupported Media Type',
                416 => 'Requested Range Not Satisfiable',
                417 => 'Expectation Failed',
                421 => 'There are too many connections from your internet address',
                422 => 'Unprocessable Entity',
                423 => 'Locked',
                424 => 'Failed Dependency',
                425 => 'Unordered Collection',
                426 => 'Upgrade Required',
                449 => 'Retry With',
                500 => 'Internal Server Error',
                501 => 'Not Implemented',
                502 => 'Bad Gateway',
                503 => 'Service Unavailable',
                504 => 'Gateway Timeout',
                505 => 'HTTP Version Not Supported',
                506 => 'Variant Also Negotiates',
                507 => 'Insufficient Storage',
                509 => 'Bandwidth Limit Exceeded',
                510 => 'Not Extended',
                600 => 'Unparseable Response Headers'

            ];
        }
    }

}


namespace Genius\Controller {

    use Genius\Controller;
    use Genius\Response;
    use Genius\Object;

    abstract class General extends Controller
    {
        public function __before()
        {
            // TODO: Implement __before() method.
        }

        public function __after()
        {
            // TODO: Implement __after() method.
        }

        public function render($view, array $parameters = [])
        {
            // TODO: Implement render() method.
        }

        public function renderFile($view, array $parameters = [])
        {
            // TODO: Implement renderFile() method.
        }

        public function renderHtml($html, array $parameters = [])
        {
            // TODO: Implement renderHtml() method.
        }

        public function getView()
        {
            // TODO: Implement getView() method.
        }

        public function setView($view)
        {
            // TODO: Implement setView() method.
        }

        /**
         * @param string $action
         * @param array $parameter
         * @return Controller
         * @throws InvaildException
         */
        public function prepare($action, array $parameter)
        {
            $this->response = new Response();
            $this->route = new Object([
                'action' => $action,
                'class' => get_class($this),
                'parameter' => new Object($parameter)
            ]);

            if (method_exists($this, '__before')) $this->__before();
            $method = new \ReflectionMethod($this, $action);
            if (strpos($action, '__') === 0) throw new InvaildException(sprintf('Method %s::%s() does not callback', $this->route->class, $action));
            $args = $missing = [];
            foreach ($method->getParameters() as $arguments) {
                $name = $arguments->getName();
                if (isset($parameter[$name])) {
                    $args[] = $parameter[$name];
                } elseif ($arguments->isDefaultValueAvailable()) {
                    $args[] = $arguments->getDefaultValue();
                } else {
                    $missing[] = $name;
                }
            }

            if ($missing) {
                throw new InvaildException('a few arguments ');
            }

            $result = $method->invokeArgs($this, $args);
            if (method_exists($this, '__after')) $this->__after();
            switch (gettype($result)) {
                case 'integer':
                case 'double' :
                case 'boolean':
                case 'NULL':
                case 'string':
                    $this->response->body .= strval($result);
                    break;
                case 'array':
                    $this->response->header->set('Content-Type', 'application/json');
                    $this->response->body = json_encode($result);
                    break;
                default:
                    throw new InvaildException(sprintf('Method %s::%s() return data type error', $this->route->get('class'), $action));
            }
            return $this;

        }

        /**
         * @return mixed
         */
        public function execute()
        {
            return $this->response->
            format('html')->
            setDownload(false)->
            context($this->response->body)->build();

        }
    }

    abstract class Console extends Controller
    {
    }

}

namespace Genius\Event {

}

namespace Genius\Exception {

    use ErrorException;

    class InvaildException extends ErrorException
    {
        public function __construct()
        {
            return call_user_func_array('parent::__construct', func_get_args());
        }
    }

}

namespace Genius\View {

    use Genius;
    use Genius\Controller;

    abstract class ViewRender
    {
        abstract public function run();

        abstract public function runArgs(array $stack = []);

    }

    class Render extends ViewRender
    {

        public function run()
        {
        }

        public function runArgs(array $stack = [])
        {
        }

        /**
         * @param mixed $context
         * @return Genius\View\Render
         */
        public function setContext($context)
        {
            return $this;
        }

    }

}