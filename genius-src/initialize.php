<?php

namespace {

    defined('APP_ENV') or define('APP_ENV', 'stable');
    defined('APP_ROOT') or define('APP_ROOT', dirname(__DIR__));
    defined('GENIUS_DEBUG') or define('GENIUS_DEBUG', false);
    defined('GENIUS_ROOT') or define('GENIUS_ROOT', __DIR__);

    define('GENIUS_VERSION', '1.0');
    define('GENIUS_COMMAND_LINE', PHP_SAPI == 'cli' ? true : false);

    error_reporting(E_ALL);
    ini_set('display_errors', GENIUS_DEBUG);

    use Genius\Object;

    abstract class Genius {

        private static $aliases = [];

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

    }

}

namespace Genius {

    use Genius\Exception\InvaildException;

    class Application
    {
        protected static $elapsed = [];

        public static function init()
        {
            Event\Passer::run();
            $instance = '\\Controllers\\Index';
            $action = 'Index';
            $parameter = [];
            require APP_ROOT . '/controllers/index.class.php';
            return (new $instance)->prepare($action, $parameter)->exeucte();
        }

        /**
         * @param string $name
         * @throws InvaildException
         * @return array|float
         */
        public static function elapsed($name)
        {
            switch($name) {
                case 'time':
                    return !isset(self::$elapsed[$name]) ?
                        self::$elapsed[$name] = round(microtime(true) * 1000):
                        round(microtime(true) * 1000) - self::$elapsed[$name];
                    break;
                case 'memory':
                    return !isset(self::$elapsed[$name]) ?
                        self::$elapsed[$name] = memory_get_usage():
                        memory_get_usage() - self::$elapsed[$name];
                    break;
                case 'file':
                    return get_included_files();
                    break;
                default:
                    throw new InvaildException($name);
            }
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
                 foreach ($arguments as $property => $val) {
                     $this->$property = is_array($val) ? new static($val) : $val;
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

    use Genius\Object;

    abstract class General {

        public function prepare($action, $parameter = [])
        {
            $group = explode('\\', get_class($this));
            $class = array_pop($group);
            $namespace = implode('\\', $group);

            $this->action = $action;
            $this->class = $class;
            $this->namespace = $namespace;
            $this->route = new Object( [ 'controller' => new Object([ 'id' => $class ]) ] );
            $this->parameters = new Object( $parameter );

            $this->Index();
            return $this;
        }

        public function exeucte()
        {
            return $this;
        }
    }

    abstract class Console {}

}

namespace Genius\View {}

namespace Genius\Event {

    use Genius;
    use Genius\Application;

    abstract class Passer
    {
        public static function run()
        {
            $timezone = Genius::userConfig()->get('parameters')->get('timezone');
            date_default_timezone_set(!empty($timezone) ?
                Genius::userConfig()->parameters->timezone :
                'Asia/Shanghai');

            spl_autoload_register([ __CLASS__, 'autoload']);

            //if (!empty(Genius::userConfig()->parameters->gzip))
            //    ob_start(function_exists('ob_gzhandler') ? 'ob_gzhandler' : '');

            Application::elapsed('time');
            Application::elapsed('memory');

            Genius::setAlias('~', APP_ROOT);
            Genius::setAlias('/', GENIUS_ROOT);
            Genius::setAlias('~/runtime', APP_ROOT . '/runtime');

            /*set_error_handler([__CLASS__, 'error']);
            set_exception_handler([__CLASS__, 'exception']);
            register_shutdown_function([__CLASS__, 'shutdown']);

            if (version_compare(PHP_VERSION, '5.4.0', '<')) throw new PHPVersionException('5.4.0');*/
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

    class InvaildException extends ErrorException {}

    //abstract class General
}

