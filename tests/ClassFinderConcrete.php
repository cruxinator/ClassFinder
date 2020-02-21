<?php


namespace Tests\Cruxinator\ClassFinder;

use Cruxinator\ClassFinder\ClassFinder;

/**
 * Class ClassFinderConcrete.
 * @package Tests\Cruxinator\ClassFinder
 * @method getProjectClasses(string $namespace): array
 * @method getClassMap(string $namespace): array
 * @method strStartsWith($needle, $haystack): bool
 * @method checkState(): void
 * @method initClassMap(): void
 * @method getComposerAutoloader(): ?ClassLoader
 * @method getClasses(string $namespace = '',callable $conditional = null, bool $includeVendor = true): array
 * @method getProjectSearchDirs(string $namespace): array
 * @method isClassInVendor(string $className) : bool
 */
class ClassFinderConcrete extends ClassFinder
{
    public function __construct()
    {
        self::$loadedNamespaces = [];
        self::$optimizedClassMap = null;
        self::$vendorDir = '';
    }
    public function __call($name, $arguments)
    {
        return call_user_func_array([self::class,$name], $arguments);
    }
}
