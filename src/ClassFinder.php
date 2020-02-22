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
 * Functionality similar to get_declared_classes(), with autoload support.
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
     * @var null|array|string[]|bool
     */
    protected static $optimisedClassMap = null;

    /**
     * Explicitly loads a namespace before returning declared classes.
     *
     * @param  string         $namespace the namespace to load
     * @throws Exception
     * @return array|string[] an array with the name of the defined classes
     */
    private static function getProjectClasses(string $namespace): array
    {
        if (in_array($namespace, self::$loadedNamespaces)) {
            return get_declared_classes();
        }
        $map = self::getClassMap($namespace);
        // now class list of maps are assembled, use class_exists calls to explicitly autoload them,
        // while not running them
        foreach ($map as $class => $file) {
            if (!self::strStartsWith($namespace, $class)) {
                continue;
            }
            class_exists($class, true);
        }
        self::$loadedNamespaces[] = $namespace;
        return get_declared_classes();
    }

    /**
     * Attempts to get an optimised ClassMap failing that attempts to generate one for the namespace.
     *
     * @param  string         $namespace the namespace to generate for if necessary
     * @throws Exception
     * @return array|string[] the class map, keyed by Classname values of files
     */
    private static function getClassMap(string $namespace): array
    {
        self::checkState();
        if (self::$optimisedClassMap !== false) {
            return self::$optimisedClassMap ;
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
    private static function strStartsWith(string $needle, string $haystack):bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * Checks the state requirements (package and autoloader).
     *
     * @throws Exception thrown when a combination of components is not available
     */
    private static function checkState() : void
    {
        self::initClassMap();
        if (false === self::$optimisedClassMap && !class_exists(ClassMapGenerator::class)) {
            throw new Exception('Cruxinator/ClassFinder requires either composer/composer' .
             ' or an optimised autoloader(`composer dump-autoload -o`)');
        }
    }

    /**
     * Initializes the optimised class map, if possible.
     */
    private static function initClassMap() :void
    {
        if (null !== self::$optimisedClassMap) {
            return;
        }
        $autoLoader = self::getComposerAutoloader();
        $classMap = $autoLoader->getClassMap();
        self::$optimisedClassMap = isset($classMap[__CLASS__]) ? $classMap : false;
    }

    /**
     * Gets the Composer Class Loader.
     *
     * @return ClassLoader|null
     */
    private static function getComposerAutoloader(): ?ClassLoader
    {
        $funcs = spl_autoload_functions();
        $classLoader = null;
        foreach ($funcs as $class) {
            if (is_array($class) && $class[0] instanceof ClassLoader) {
                $classLoader = $class[0];
            }
        }
        return $classLoader;
    }

    /**
     * Gets a list of classes defined in the autoloader. get_declared_classes().
     *
     * @param  string        $namespace     namespace prefix to restrict the list (must be configured psr4 namespace
     * @param  callable|null $conditional   callable method of signature `conditional(string $className) : bool` to check to include
     * @param  bool          $includeVendor whether classes in the vendor directory should be considered
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
    private static function getProjectSearchDirs(string $namespace): array
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
    private static function isClassInVendor(string $className) : bool
    {
        $reflection = new ReflectionClass($className);
        $filename = $reflection->getFileName();
        return self::strStartsWith(self::$vendorDir, $filename);
    }
}
