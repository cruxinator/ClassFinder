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
    private static $loadedNamespaces = [];
    /**
     * @var string
     */
    private static $vendorDir = '';
    /**
     * @var null|array|string[]
     */
    private static $optimisedClassMap = null;
    /**
     * @var bool Indicates if autoloader class map is initialised
     */
    private static $classLoaderInit = false;

    /**
     * Explicitly loads a namespace before returning declared classes.
     *
     * @param  string         $namespace the namespace to load
     * @throws Exception
     * @return array|string[] an array with the name of the defined classes
     */
    private static function getProjectClasses(string $namespace): array
    {
        if (!in_array($namespace, self::$loadedNamespaces)) {
            $map = self::getClassMap($namespace);
            array_walk($map, function ($filename, $className, $namespace) {
                assert(file_exists($filename), $filename);
                self::strStartsWith($namespace, $className) && class_exists($className, true);
            }, $namespace);
        }
        return get_declared_classes();
    }

    /**
     * Attempts to get an optimised ClassMap failing that attempts to generate one for the namespace.
     *
     * @param  string              $namespace the namespace to generate for if necessary
     * @throws Exception
     * @return null|array|string[] the class map, keyed by Classname values of files
     */
    private static function getClassMap(string $namespace): array
    {
        self::checkState();
        return null !== (self::$optimisedClassMap) ?
            self::$optimisedClassMap :
            array_reduce(self::getProjectSearchDirs($namespace),
                function ($map, $dir) {
                    return array_merge($map, ClassMapGenerator::createMap($dir));
                }, []);
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
        if (null === self::$optimisedClassMap && !class_exists(ClassMapGenerator::class)) {
            throw new Exception('Cruxinator/ClassFinder requires either composer/composer' .
             ' or an optimised autoloader(`composer dump-autoload -o`)');
        }
    }

    /**
     * Initializes the optimised class map, if possible.
     */
    private static function initClassMap() :void
    {
        if (true === self::$classLoaderInit) {
            return;
        }
        self::$classLoaderInit = true;
        $autoLoader = self::getComposerAutoloader();
        $classMap = $autoLoader->getClassMap();
        self::$optimisedClassMap = isset($classMap[__CLASS__]) ? $classMap : null;
    }

    /**
     * Gets the Composer Class Loader.
     *
     * @return ClassLoader|null
     */
    private static function getComposerAutoloader(): ?ClassLoader
    {
        return array_reduce(spl_autoload_functions(),
            function ($loader, $prospect) {
                return is_array($prospect) && $prospect[0] instanceof ClassLoader ? $prospect[0] : $loader;
            }, null);
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
        $conditional = $conditional ?: 'is_string';
        $classes = array_values(array_filter(self::getProjectClasses($namespace), function (string $class) use (
            $namespace,
            $conditional,
            $includeVendor
        ) {
            return self::strStartsWith($namespace, $class) &&
                   ($includeVendor || !self::isClassInVendor($class)) &&
                   $conditional($class);
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
        $raw = self::getComposerAutoloader()->getPrefixesPsr4();
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
        $filename = (new ReflectionClass($className))->getFileName();
        return self::strStartsWith(self::$vendorDir, $filename);
    }
}
