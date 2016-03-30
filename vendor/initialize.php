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
        protected static $elapsed = [];
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
         * @return void
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
    use Genius\View\Render;
    use Genius\Exception\InvaildException;

    abstract class Application extends Genius
    {
        protected static $elapsed = [];
        protected $route;
        protected $text;

        public static function init()
        {
            ob_start();
            ob_implicit_flush(1);

            if (version_compare(PHP_VERSION, '5.4.0', '<')) {
                trigger_error('PHP version cannot be less than 5.4.0', E_USER_ERROR);
            }

            $timezone = Genius::userConfig()->get('parameters')->get('timezone');
            date_default_timezone_set(!empty($timezone) ? Genius::userConfig()->parameters->timezone : 'Asia/Shanghai');

            spl_autoload_register([__CLASS__, 'autoLoad']);

            Genius::elapsed('time');
            Genius::elapsed('memory');

            Genius::setAlias('/', GENIUS_ROOT);
            Genius::setAlias('~', APP_ROOT);

            Application::elapsed('time');
            Application::elapsed('memory');
            Genius\Utils\Directory::create(Genius::getComponents('log')->get('path'));

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

    abstract class Controller extends Render
    {
        /**
         * @param string $view
         * @return Genius\View\ViewRender
         */
        public function getView($view)
        {
            if (Genius::getComponents('view')) {
                return $view;
            }
            exit;
        }

        /**
         * @param string $action
         * @param array $parameter
         * @return $this
         */
        abstract public function prepare($action, $parameter);

        /**
         * @return void
         */
        abstract public function execute();
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

    class Route
    {
        protected $uri;

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

            $arguments = [];
            $url = ($url = (array)Genius::getComponents('url')) ? $url : [];
            if ($url) {
                $list = [];
                foreach ($url as $pattern => $path) {
                    if (preg_match_all('/\<(.+?)\:(.+?)\>/', $pattern, $matches)) {
                        list($unused, $name, $regexp) = $matches;
                        foreach ($unused as $key => $value) {
                            $pattern = sprintf(preg_quote(str_replace($value, '%s', $pattern), '/'), '\/?(' . $regexp[$key] . ')');
                            array_push($arguments, $name[$key]);
                        }
                    }

                    list($class, $action) = explode('/', $path);

                    $list[$pattern] = [
                        'arguments' => $arguments,
                        'class' => $class,
                        'action' => $action];
                }

                $find = 0;
                $arguments = [];
                foreach ($list as $pattern => $group) {
                    if (preg_match('/^' . $pattern . '$/', $this->uri, $matches)) {
                        $find = 1;
                        array_shift($matches);
                        foreach ($group['arguments'] as $k => $assoc) {
                            $arguments[$assoc] = $matches[$k];
                        }
                        $class = $group['class'];
                        $action = $group['action'];
                        break;
                    }
                }

                if ($find) {
                    if (preg_match('/\/(.+?)\/(.+)/', $this->uri, $matches)) {
                        array_shift($matches);
                        list($class, $action) = $matches;
                    }
                }

            } else {

                $group = explode('/', $this->uri);
                $action = array_pop($group);
                $class = count($group) > 1 ? implode('\\', $group) : array_shift($group);
                if(!$class) {
                    $class = $action;
                    $action = 'Index';
                }

            }

            $arguments = $_GET = array_merge($arguments, $_GET);
            $class = sprintf('Controllers\\%s', ucfirst($class));

            return (new $class)->prepare($action, $arguments)->execute();

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

}

namespace Genius\Controller {

    use Genius;
    use Genius\Object;
    use Genius\Controller;
    use Genius\Exception\InvaildException;

    abstract class General extends Controller
    {
        /**
         * @param string $action
         * @param array $parameter
         * @return $this|bool
         * @throws InvaildException
         */
        public function prepare($action, $parameter)
        {
            $class = get_class($this);
            $this->route = new Object([
                'action' => $action,
                'class' => $class,
                'parameter' => new Object($parameter)
            ]);

            if (method_exists($this, '__before')) $this->__before();
            $method = new \ReflectionMethod($this, $action);
            $args = [];
            $missing = [];
            foreach ($method->getParameters() as $arguments) {
                $name = $arguments->getName();
                if (array_key_exists($name, $parameter)) {
                    $args[] = $parameter[$name];
                } elseif ($arguments->isDefaultValueAvailable()) {
                    $args[] = $arguments->getDefaultValue();
                } else {
                    $missing = [];
                }
            }

            if ($missing) return false;
            $result = $method->invokeArgs($this, $args);
            if (method_exists($this, '__after')) $this->__after();

            if (!is_null($result)) {
                if (!is_numeric($result) && !is_string($result)) {
                    throw new InvaildException(sprintf('Method %s::%s() return data type error', $class, $action));
                }
            }

            $this->text = ob_get_clean() . strval($result);
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

        /**
         * @param string $html
         * @param array $parameters [optional]
         * @return string
         */
        public function renderHtml($html, array $parameters = [])
        {

        }

    }

}