<?php


namespace Cruxinator\ClassFinder\Tests;

use Composer\Autoload\ClassLoader;
use Cruxinator\ClassFinder\ClassFinder;
use ReflectionClass;
use ReflectionProperty;

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

    /**
     * @param $name
     * @throws \ReflectionException
     * @return \ReflectionMethod
     */
    protected static function getMethod($name)
    {
        $class = new ReflectionClass(self::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * @param $name
     * @throws \ReflectionException
     * @return ReflectionProperty
     */
    protected static function getProperty($name)
    {
        $reflectionProperty = new ReflectionProperty(parent::class, $name);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty;
    }

    public function setOptimisedClassMap($value)
    {
        $this->optimisedClassMap = $value;
    }

    /**
     * @param $name
     * @param $arguments
     * @throws \ReflectionException
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $method = self::getMethod($name);
        return $method->invokeArgs(null, $arguments);
    }

    /**
     * @param $name
     * @param $value
     * @throws \ReflectionException
     */
    public function __set($name, $value)
    {
        $property = self::getProperty($name);
        $property->setValue(null, $value);
    }
}
