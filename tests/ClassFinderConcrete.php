<?php


namespace Tests\Cruxinator\ClassFinder;

use Composer\Autoload\ClassLoader;
use Cruxinator\ClassFinder\ClassFinder;
use ReflectionClass;

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
    public function __construct()
    {
        $this->loadedNamespaces = [];
        $this->optimisedClassMap = null;
        $this->vendorDir = '';
    }

    protected static function getMethod($name)
    {
        $class = new ReflectionClass(self::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
    protected static function getProperty($name)
    {
        $reflectionProperty = new ReflectionProperty(self::class, 'measurements');
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty;
    }

    public function setOptimisedClassMap($value)
    {
        $this->optimisedClassMap = $value;
    }

    public function __call($name, $arguments)
    {
        $method = self::getMethod($name);
        return $method->invokeArgs(null, $arguments);
    }

    public function __set($name, $value)
    {
        $property = self::getProperty($name);
        $property->setValue(null, $value);
    }
}
