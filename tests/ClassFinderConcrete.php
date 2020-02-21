<?php


namespace Tests\Cruxinator\ClassFinder;

use Composer\Autoload\ClassLoader;
use Cruxinator\ClassFinder\ClassFinder;

/**
 * Class ClassFinderConcrete.
 * @package Tests\Cruxinator\ClassFinder
 * @method getProjectClasses(string $namespace): array
 * @method getClassMap(string $namespace): array
 * @method strStartsWith($needle, $haystack): bool
 * @method checkState(): void
 * @method initClassMap(): void
 * @method getClasses(string $namespace = '',callable $conditional = null, bool $includeVendor = true): array
 * @method getProjectSearchDirs(string $namespace): array
 * @method isClassInVendor(string $className) : bool
 */
class ClassFinderConcrete extends ClassFinder
{
    protected static $mockClassLoader = null;

    public function __construct()
    {
        self::$loadedNamespaces = [];
        self::$optimisedClassMap = null;
        self::$vendorDir = '';
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([self::class, $name], $arguments);
    }

    /**
     * Gets a dynamically assigned autoloader
     *
     * @return ClassLoader|null
     */
    protected static function getComposerAutoloader(): ?ClassLoader
    {
        if (self::$mockClassLoader !== null) {
            return self::$mockClassLoader;
        }
        return parent::getComposerAutoloader();
    }

    protected static function setMockClassLoader($mockObject)
    {
        self::$mockClassLoader = $mockObject;
    }
}
