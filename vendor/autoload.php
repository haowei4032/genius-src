<?php

/**
 * @link http://genius.haowei.me
 * @copyright Copyright (c) 2015 Genius Software LLC
 * @license http://genius.haowei.me/license
 */

namespace
{
	defined('APP_ENV') or define('APP_ENV', 'stable');
	defined('APP_PATH') or define('APP_PATH', dirname(__DIR__));

	defined('GENIUS_ROOT') or define('GENIUS_ROOT', __DIR__);
	defined('GENIUS_DEBUG') or define('GENIUS_DEBUG', false);
	defined('GENIUS_VERSION') or define('GENIUS_VERSION', '0.0.1');

	abstract class Genius
	{
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
			$real = implode('/', $group);
			if (isset(self::$aliases[$alias])) return self::$aliases[$alias];
			if (isset(self::$aliases[$base])) return self::$aliases[$base] .'/'. $path;
			return null;
        }

		/**
         * @param string $alias
         * @param string $value
         */
        public static function setAlias($alias, $value)
        {
            self::$aliases[$alias] = $value;
        }

		/**
		 * @param string $env
		 * @return \Genius\Object
		 */
		public static function userConfig()
		{
			
		}
	}
}

namespace Genius
{
	abstract class Application extends \Genius
	{
		/**
         * @param array $config
		 *
         */
		public static function runArguments($config)
		{
			if (version_compare(PHP_VERSION, '5.4.0', '<')) {
                trigger_error('PHP version cannot be less than 5.4.0', E_USER_ERROR);
            }

			spl_autoload_register([__CLASS__, 'autoLoad']);

		}

		/**
         * @param string $class
         * @return mixed
         */
        private static function autoLoad($class)
        {
			$group = explode('\\', $class);
			$base = reset($group);
			switch($base)
			{
				case 'Genius':
					array_shift($group);
					
					break;
			}

			
			if (!is_file($file)) {
				trigger_error('PHP version cannot be less than 5.4.0', E_USER_ERROR);
			}
		}
	}

	class Object
	{
		public function __get($name)
		{
		
		}
	}

}