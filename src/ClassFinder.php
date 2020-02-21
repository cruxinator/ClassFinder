<?php
namespace Cruxinator\ClassFinder;

use Composer\Autoload\ClassLoader;
use Composer\Autoload\ClassMapGenerator;
use Exception;
use ReflectionClass;
use ReflectionException;

/**
 * Class ClassFinder.
 *
 * functionality similar to get_declared_classes() with autoload support.
 *
 * @package Cruxinator\ClassFinder
 */
abstract class ClassFinder
{
    /**
     * @var array|string[]
     */
    protected static $loadedNamespaces = [];
    /**
     * @var string
     */
    protected static $vendorDir = '';
    /**
     * @var null|array|string[]
     */
    protected static $optimizedClassMap = null;

    /**
     * Explicitly loads a namespace before returning declared classes.
     *
     * @param  string         $namespace the namespace to load
     * @throws Exception
     * @return array|string[] an array with the name of the defined classes
     */
    protected static function getProjectClasses(string $namespace): array
    {
        if (in_array($namespace, self::$loadedNamespaces)) {
            return get_declared_classes();
        }
        $map = self::getClassMap($namespace);
        // now class list of maps are assembled, use class_exists calls to explicitly autoload them,
        // while not running them
        foreach ($map as $class => $file) {
            if (self::strStartsWith($namespace, $class)) {
                continue;
            }
            class_exists($class, true);
        }
        self::$loadedNamespaces[] = $namespace;
        return get_declared_classes();
    }

    /**
     * Attempts to get an optimized ClassMap failing that attempts to generate one for the namespace.
     *
     * @param  string         $namespace the namespace to generate for if necessary
     * @throws Exception
     * @return array|string[] the class map, keyed by Classname values of files
     */
    protected static function getClassMap(string $namespace): array
    {
        self::checkState();
        if (self::$optimizedClassMap !== false) {
            return self::$optimizedClassMap ;
        }
        $projectDirs = self::getProjectSearchDirs($namespace);
        $map = [];
        // Use composer's ClassMapGenerator to pull the class list out of each project search directory
        foreach ($projectDirs as $dir) {
            $map = array_merge($map, ClassMapGenerator::createMap($dir));
        }
        return $map;
    }

    /**
     * Checks if a string starts with another string.
     * Simple Helper for readability.
     *
     * @param  string $needle   the string to check
     * @param  string $haystack the input string
     * @return bool   true if haystack starts with needle, false otherwise
     */
    protected static function strStartsWith(string $needle, string $haystack):bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * Checks the state requirements (package and autoloader).
     *
     * @throws Exception thrown when a a combination of components is not available
     */
    protected static function checkState() : void
    {
        self::initClassMap();
        if (false === self::$optimizedClassMap && !class_exists(ClassMapGenerator::class)) {
            throw new Exception('Cruxinator/ClassFinder requires either composer/composer' .
             ' or an optimized autoloader(`composer dump-autoload -o`)');
        }
    }

    /**
     * Initializes the optimized class map if possible.
     */
    protected static function initClassMap() :void
    {
        if (null !== self::$optimizedClassMap) {
            return;
        }
        $autoLoader = self::getComposerAutoloader();
        $classMap = $autoLoader->getClassMap();
        self::$optimizedClassMap = isset($classMap[__CLASS__]) ? $classMap : false;
    }

    /**
     * Gets the Composer Class Loader.
     *
     * @return ClassLoader|null
     */
    protected static function getComposerAutoloader(): ?ClassLoader
    {
        $funcs = spl_autoload_functions();
        foreach ($funcs as $class) {
            if (is_array($class) && $class[0] instanceof ClassLoader) {
                return $class[0];
            }
        }
        return null;
    }

    /**
     * Gets a list of classes defined in the autoloader. get_declared_classes().
     *
     * @param  string        $namespace     namespace prefix to restrict the list (must be configured psr4 namespace
     * @param  callable|null $conditional   callable method of signature `conditional(string $className) : bool` to check to include
     * @param  bool          $includeVendor weather classes in the vendor directory should be considered
     * @throws Exception
     * @return array         the list of classes
     */
    public static function getClasses(string $namespace = '', callable $conditional = null, bool $includeVendor = true):array
    {
        $conditional = $conditional ?: function () {
            return true;
        };
        $classes = array_values(array_filter(self::getProjectClasses($namespace), function (string $class) use (
            $namespace,
            $conditional,
            $includeVendor
        ) {
            /*$dontSkip = true;
            if (!$includeVendor) {
                $dontSkip = !self::isClassInVendor($class);
            }*/
            $dontSkip = $includeVendor || !self::isClassInVendor($class);
            return substr($class, 0, strlen($namespace)) === $namespace && $dontSkip && $conditional($class) ;
        }));

        return $classes;
    }
    /**
     * Gets the Directories associated with a given namespace.
     *
     * @param  string $namespace the namespace (without preceding \
     * @return array  a list of directories containing classes for that namespace
     */
    protected static function getProjectSearchDirs(string $namespace): array
    {
        $autoloader = self::getComposerAutoloader();
        $raw = $autoloader->getPrefixesPsr4();
        return $raw[$namespace];
    }

    /**
     * Identify if the class is in the vendor directory.
     *
     * @param  string              $className the class to test
     * @throws ReflectionException
     * @return bool                true if in vendor otherwise false
     */
    protected static function isClassInVendor(string $className) : bool
    {
        $reflection = new ReflectionClass($className);
        $filename = $reflection->getFileName();
        return self::strStartsWith(self::$vendorDir, $filename);
    }
}
